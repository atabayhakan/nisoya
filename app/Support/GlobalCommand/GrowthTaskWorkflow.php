<?php

namespace App\Support\GlobalCommand;

use App\Enums\UserStatus;
use App\Models\GlobalGrowthTask;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class GrowthTaskWorkflow
{
    public const LABELS = ['suggested' => 'Önerildi', 'in_progress' => 'Çalışılıyor', 'completed' => 'Tamamlandı', 'dismissed' => 'Ertelendi'];

    public function update(int $id, int $revision, array $data, User $actor, GeoContext $context): GlobalGrowthTask
    {
        abort_unless($actor->isAdmin() && $actor->status === UserStatus::Aktif && config('global-command.enabled'), 403);
        $data = Validator::make($data, [
            'status' => ['required', Rule::in(array_keys(self::LABELS))],
            'assignee_id' => ['nullable', 'integer', Rule::requiredIf(in_array($data['status'] ?? '', ['in_progress', 'completed'], true))],
            'completion_note' => ['nullable', 'string', 'max:2000', Rule::requiredIf(($data['status'] ?? '') === 'completed')],
        ])->validate();

        return DB::transaction(function () use ($id, $revision, $data, $actor, $context): GlobalGrowthTask {
            $task = GlobalGrowthTask::whereIn('country_code', $context->countries()->select('code'))->lockForUpdate()->findOrFail($id);
            abort_unless($task->revision === $revision, 409, 'Görev başka bir yönetici tarafından değiştirildi. Yeniden açın.');
            if ($data['assignee_id'] ?? null) {
                $assignee = User::whereKey($data['assignee_id'])->lockForUpdate()->first();
                abort_unless($assignee?->isAdmin() && $assignee->status === UserStatus::Aktif, 422, 'Aktif bir yönetici seçin.');
            }
            $before = $task->only(['status', 'assignee_id', 'completion_note']);
            $next = ['status' => $data['status'], 'assignee_id' => $data['assignee_id'] ?? null,
                'completion_note' => $data['status'] === 'completed' ? trim($data['completion_note']) : null];
            $task->fill($next);
            if (! $task->isDirty(array_keys($next))) {
                return $task;
            }
            $task->fill(['revision' => $task->revision + 1,
                'completed_at' => $next['status'] === 'completed' ? ($task->completed_at ?? now()) : null,
                'completed_by' => $next['status'] === 'completed' ? ($task->completed_by ?? $actor->id) : null]);
            $task->save();
            activity('global-command')->causedBy($actor)->performedOn($task)
                ->withProperties(['before' => $before, 'after' => $task->only(array_keys($next)), 'revision' => $task->revision])
                ->log('growth.task.updated');

            return $task;
        });
    }
}

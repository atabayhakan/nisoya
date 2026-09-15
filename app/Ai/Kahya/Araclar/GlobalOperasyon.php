<?php

namespace App\Ai\Kahya\Araclar;

use App\Enums\UserStatus;
use App\Models\User;
use App\Support\GlobalCommand\GlobalOperations;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GlobalOperasyon implements Tool
{
    public function __construct(private readonly int $actorId) {}

    public function name(): string
    {
        return 'global-operasyon';
    }

    public function description(): Stringable|string
    {
        return 'Bütün ülkeler için kiralık konut ilanlarını karşılaştırır veya onay bekleyen iş ilanlarının kalite/risk incelemesini kuyruğa alır. '
            .'Ülkeleri katalogdaki ISO kodlarıyla belirt. Sonuç üretmezsen uydurma. inspect_jobs yalnız iç inceleme oluşturur; ilanı reddetmez, kimseye mesaj göndermez.';
    }

    public function schema(JsonSchema $schema): array
    {
        return ['operation' => $schema->string()->enum(['compare_rentals', 'inspect_jobs'])->required(),
            'countries' => $schema->array()->items($schema->string())->required(),
            'month' => $schema->string()->description('compare_rentals için YYYY-MM; boşsa bu ay')];
    }

    public function handle(Request $request): Stringable|string
    {
        $actor = User::find($this->actorId);
        if (! config('global-command.enabled') || ! $actor?->isAdmin() || $actor->status !== UserStatus::Aktif) {
            return 'Bu operasyon için aktif yönetici yetkisi gerekli.';
        }
        try {
            $data = $request->all();
            $service = app(GlobalOperations::class);
            $countries = $data['countries'] ?? [];
            if (! is_array($countries)) {
                return 'Ülkeler bir liste olmalı.';
            }
            $result = match ($data['operation'] ?? '') {
                'compare_rentals' => $service->compareRentals($countries, ($data['month'] ?? '') ?: null),
                'inspect_jobs' => $service->inspectPendingJobs($countries, $actor->id),
                default => ['error' => 'Bilinmeyen operasyon.'],
            };

            return json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (ValidationException) {
            return 'Ülke veya tarih geçersiz. Katalogdaki ISO kodları ve YYYY-MM kullanın.';
        } catch (\Throwable $exception) {
            report($exception);

            return 'Operasyon tamamlanamadı; içerik sınıflandırması veya yapılandırma kontrol edilmeli.';
        }
    }
}

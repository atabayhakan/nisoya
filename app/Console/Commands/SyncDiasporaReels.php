<?php

namespace App\Console\Commands;

use App\Enums\UserStatus;
use App\Models\DiasporaAccount;
use App\Models\User;
use App\Support\GlobalCommand\DiasporaDispatch;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class SyncDiasporaReels extends Command
{
    protected $signature = 'diaspora:sync {--actor= : Yetkili yönetici ID} {--account= : Tek hesap ID}';

    protected $description = 'Doğrulanmış diaspora hesapları için tarama işlerini kuyruğa alır; örnek veri oluşturmaz.';

    public function handle(DiasporaDispatch $dispatch): int
    {
        $actor = User::find($this->option('actor') ?: config('global-command.scheduler_actor_id'));
        if (! $actor?->isAdmin() || $actor->status !== UserStatus::Aktif) {
            $this->error('Etkin yönetici için --actor seçeneğini veya scheduler_actor_id ayarını belirtin.');

            return self::FAILURE;
        }
        $query = DiasporaAccount::query();
        if ($this->option('account')) {
            $query->whereKey($this->option('account'));
        }
        try {
            $count = $dispatch->enqueue($actor, $query);
        } catch (ValidationException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
        $this->info($count.' hesap için tarama talebi alındı; tekrarlanan talepler birleştirilir.');

        return self::SUCCESS;
    }
}

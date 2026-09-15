<?php

namespace App\Services\Diaspora;

use App\Models\DiasporaAccount;
use App\Support\GlobalCommand\StrictDiasporaSync;

/** Legacy entry point: ingest never fabricates or auto-publishes posts. */
class DiasporaSyncEngine
{
    public function __construct(private readonly StrictDiasporaSync $sync) {}

    public function syncAccount(DiasporaAccount $account): array
    {
        $created = $this->sync->run($account);

        return ['account' => $account->username, 'created' => $created, 'skipped' => 0, 'autopilot_published' => 0];
    }

    public function syncAll(): array
    {
        throw new \LogicException('Toplu tarama için diaspora:sync kuyruk komutunu kullanın.');
    }
}

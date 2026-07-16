<?php

namespace App\Services\Ai\Concerns;

/**
 * Sağlayıcıların son başarısız çağrının nedenini saklaması için ortak davranış.
 * Admin panelindeki "Bağlantıyı test et" bu mesajı gösterir (aksi halde neden
 * başarısız olduğu görünmez — ör. "model görüntü desteklemiyor").
 */
trait TracksLastError
{
    protected ?string $lastError = null;

    public function lastError(): ?string
    {
        return $this->lastError;
    }
}

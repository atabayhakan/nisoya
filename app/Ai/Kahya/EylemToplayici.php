<?php

namespace App\Ai\Kahya;

use App\Models\KahyaEylemKaydi;
use App\Services\Kahya\Sohbet\KahyaSohbeti;

/**
 * Bir sohbet turu içinde araçların ürettiği eylem kayıtlarını biriktirir.
 *
 * Ajan döngüsünde model bir turda BİRDEN ÇOK eylem çalıştırabilir; sohbet
 * arayüzü ise (geri al / onayla düğmeleri için) turun kayıtlarına ihtiyaç
 * duyar. Araçlar kaydı buraya bırakır, {@see KahyaSohbeti}
 * tur bitince okur.
 *
 * Container'da scoped bağlıdır; yine de her turun başında `sifirla()` çağrılır —
 * kuyruk işçisi gibi uzun ömürlü süreçlerde bir önceki turun kayıtları
 * sızmasın.
 */
class EylemToplayici
{
    /** @var list<KahyaEylemKaydi> */
    private array $kayitlar = [];

    public function ekle(KahyaEylemKaydi $kayit): void
    {
        $this->kayitlar[] = $kayit;
    }

    /** @return list<KahyaEylemKaydi> */
    public function hepsi(): array
    {
        return $this->kayitlar;
    }

    public function son(): ?KahyaEylemKaydi
    {
        return $this->kayitlar === [] ? null : end($this->kayitlar);
    }

    public function sifirla(): void
    {
        $this->kayitlar = [];
    }
}

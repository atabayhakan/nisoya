<?php

namespace Database\Seeders\Rehber;

use App\Models\IslemTuru;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;

/**
 * Rehber içeriği dolduran seeder'ların ortak tabanı.
 *
 * ---------------------------------------------------------------------------
 * DEĞİŞMEZ KURAL: İNSAN DOĞRULAMASI EZİLMEZ
 *
 * `ReferenceDataSeeder` üzerinden HER DEPLOY'DA çalışıyoruz. Bu yüzden yalnız
 * `dogrulanma_tarihi` BOŞ olan — yani hâlâ genel iskelet taslağı olan —
 * kayıtlara dokunulur. Sahip panelden bir temsilciliğin sayfasını doğrularsa
 * (tarih dolar) o kayıt bir daha ezilmez. Bu olmadan her deploy, insanın
 * elle düzelttiği bilgiyi sessizce geri alırdı.
 *
 * ---------------------------------------------------------------------------
 * İÇERİK SÖZLEŞMESİ
 *
 * · Kaynak resmî olmalı ve İŞLEME ÖZEL olmalı (ana sayfa değil).
 * · Doğrulanmamış RAKAM YAZILMAZ — harç tutarı/işlem süresi için kaynak
 *   bulunamadıysa rakam verilmez, teyit yolu gösterilir.
 * · Notlar DÜZ METİN. Görünüm `{{ }}` ile kaçırarak basıyor; markdown
 *   yıldızları sayfada ham görünür (bir kez yaşandı, 11 yıldız).
 */
abstract class RehberIcerikSeeder extends Seeder
{
    /** Doldurulacak işlem türünün slug'ı. */
    abstract protected function slug(): string;

    /** Resmî ve işleme özel kaynak adresi. */
    abstract protected function kaynak(): string;

    /** Bu turda doğrulamanın yapıldığı tarih (Y-m-d). */
    abstract protected function dogrulamaTarihi(): string;

    /** @return list<array{ad: string, not?: string}> */
    abstract protected function evraklar(): array;

    abstract protected function sure(): string;

    abstract protected function ucret(): string;

    abstract protected function notlar(): string;

    public function run(): void
    {
        $tur = IslemTuru::query()->where('slug', $this->slug())->first();

        if (! $tur) {
            $this->command?->warn("{$this->slug()} işlem türü yok — rehber iskeleti önce kurulmalı.");

            return;
        }

        $guncellenen = TemsilcilikIslemi::query()
            ->where('islem_turu_id', $tur->id)
            ->whereNull('dogrulanma_tarihi')
            ->update([
                'evraklar' => json_encode($this->evraklar(), JSON_UNESCAPED_UNICODE),
                'sure_metni' => $this->sure(),
                'ucret_metni' => $this->ucret(),
                'notlar' => $this->notlar(),
                'resmi_kaynak_url' => $this->kaynak(),
                'dogrulanma_tarihi' => $this->dogrulamaTarihi(),
                'status' => TemsilcilikIslemi::STATUS_YAYIN,
            ]);

        $this->command?->info("{$this->slug()}: {$guncellenen} temsilcilik güncellendi.");
    }
}

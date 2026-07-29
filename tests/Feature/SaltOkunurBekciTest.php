<?php

namespace Tests\Feature;

use App\Models\KahyaCalismasi;
use App\Support\SaltOkunurBekci;
use App\Support\SaltOkunurIhlali;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Bekçinin işi tek cümleyle: "Kâhya asla yazmaz" sözünü ÖLÇÜLEBİLİR yapmak.
 *
 * Bu testlerin hepsi bir yazma denemesinin GERÇEKTEN engellendiğini gösterir —
 * istisna atılmasını değil, VERİNİN DEĞİŞMEDİĞİNİ. Aradaki fark önemli:
 * `DB::listen()` ile yazılmış bir bekçi de istisna atar, ama satır çoktan
 * yazılmıştır. İstisnayı görüp "engelledim" sanmak bu alandaki klasik hatadır.
 */
class SaltOkunurBekciTest extends TestCase
{
    use RefreshDatabase;

    public function test_okuma_gecer(): void
    {
        KahyaCalismasi::create(['tur' => 'gunluk_rapor', 'gonderildi' => true]);

        $adet = SaltOkunurBekci::icinde(fn (): int => KahyaCalismasi::query()->count());

        $this->assertSame(1, $adet);
    }

    /** ASIL İDDİA: istisna atıldı DEĞİL — satır yazılmadı. */
    public function test_insert_engellenir_ve_veri_degismez(): void
    {
        $once = KahyaCalismasi::query()->count();

        try {
            SaltOkunurBekci::icinde(function (): void {
                KahyaCalismasi::create(['tur' => 'gunluk_rapor', 'gonderildi' => true]);
            });
            $this->fail('Yazma engellenmeliydi.');
        } catch (SaltOkunurIhlali) {
            // beklenen
        }

        $this->assertSame($once, KahyaCalismasi::query()->count(), 'Satır yazılmış olmamalı.');
    }

    public function test_update_engellenir(): void
    {
        $kayit = KahyaCalismasi::create(['tur' => 'gunluk_rapor', 'gonderildi' => false]);

        $this->expectException(SaltOkunurIhlali::class);

        SaltOkunurBekci::icinde(function () use ($kayit): void {
            $kayit->update(['gonderildi' => true]);
        });
    }

    public function test_delete_engellenir(): void
    {
        $kayit = KahyaCalismasi::create(['tur' => 'gunluk_rapor', 'gonderildi' => false]);

        try {
            SaltOkunurBekci::icinde(fn () => $kayit->delete());
            $this->fail('Silme engellenmeliydi.');
        } catch (SaltOkunurIhlali) {
            // beklenen
        }

        $this->assertDatabaseHas('kahya_calismalari', ['id' => $kayit->id]);
    }

    /**
     * `statement()` ve `unprepared()` de `run()`'dan geçer — yani Schema
     * işlemleri (migration, drop) de bekçinin kapsamındadır. Bunu ayrıca
     * test ediyoruz çünkü "sadece Eloquent'i korur" varsayımı yanlış olurdu.
     */
    public function test_schema_degisikligi_engellenir(): void
    {
        try {
            SaltOkunurBekci::icinde(function (): void {
                Schema::create('bekci_deneme', function ($tablo): void {
                    $tablo->id();
                });
            });
            $this->fail('Schema değişikliği engellenmeliydi.');
        } catch (SaltOkunurIhlali) {
            // beklenen
        }

        $this->assertFalse(Schema::hasTable('bekci_deneme'));
    }

    /** Ham SQL de aynı kapıdan geçer. */
    public function test_ham_sql_yazmasi_engellenir(): void
    {
        $this->expectException(SaltOkunurIhlali::class);

        SaltOkunurBekci::icinde(function (): void {
            DB::statement("insert into kahya_calismalari (tur, created_at, updated_at) values ('gunluk_rapor', datetime('now'), datetime('now'))");
        });
    }

    /**
     * ALLOW-LIST İDDİASI: tanınmayan bir ifade "yazma değil" diye geçmez.
     * Deny-list yaklaşımı TRUNCATE/RENAME/GRANT gibi onlarca ifadeyi kaçırır.
     */
    public function test_taninmayan_ifade_de_engellenir(): void
    {
        $this->expectException(SaltOkunurIhlali::class);

        SaltOkunurBekci::icinde(fn () => DB::unprepared('vacuum'));
    }

    /** Çoklu ifade kaçağı: `select 1; drop table x`. */
    public function test_coklu_ifade_engellenir(): void
    {
        $this->expectException(SaltOkunurIhlali::class);

        SaltOkunurBekci::icinde(fn () => DB::select('select 1; select 2'));
    }

    /** Kip DAR kapsamlı: iş bitince yazma yeniden serbest olmalı. */
    public function test_kip_bitince_yazma_yeniden_serbest(): void
    {
        SaltOkunurBekci::icinde(fn (): int => KahyaCalismasi::query()->count());

        $this->assertFalse(SaltOkunurBekci::aktifMi());

        KahyaCalismasi::create(['tur' => 'gunluk_rapor', 'gonderildi' => true]);

        $this->assertSame(1, KahyaCalismasi::query()->count());
    }

    /** İstisna atılsa bile kip kapanmalı — `finally` gerçekten çalışıyor mu. */
    public function test_istisna_sonrasi_kip_kapanir(): void
    {
        try {
            SaltOkunurBekci::icinde(function (): void {
                KahyaCalismasi::create(['tur' => 'gunluk_rapor']);
            });
        } catch (SaltOkunurIhlali) {
            // beklenen
        }

        $this->assertFalse(SaltOkunurBekci::aktifMi());

        KahyaCalismasi::create(['tur' => 'gunluk_rapor', 'gonderildi' => true]);
        $this->assertSame(1, KahyaCalismasi::query()->count());
    }

    /**
     * Önbellek kip içinde `array`'e alınır: yerelde `CACHE_STORE=database` ve
     * soğuk bir önbellekte `Settings::get()` ilk okumada `cache` tablosuna
     * INSERT dener — bekçi onu haklı olarak engeller ve teşhis aracı kendi
     * kendini patlatırdı. Kip bitince eski sürücü geri gelmeli.
     */
    public function test_onbellek_kip_icinde_arrayde_kip_bitince_eski_haline_doner(): void
    {
        config(['cache.default' => 'database']);

        $icerideki = SaltOkunurBekci::icinde(fn (): string => (string) config('cache.default'));

        $this->assertSame('array', $icerideki);
        $this->assertSame('database', config('cache.default'));
    }
}

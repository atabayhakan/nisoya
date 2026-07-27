<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Http\Controllers\JobApplicationController;
use App\Jobs\BasvuruDurumBildirimi;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobListing;
use App\Models\User;
use App\Notifications\ApplicationStatusNotification;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Kanban başvuru panosu (2026-07-28).
 *
 * Panonun asıl riski görsel değil DAVRANIŞSAL: durum değiştirmek bir sürükleme
 * kadar ucuzladığı için yanlış bir hareket, geri alınamaz bir e-postaya
 * dönüşebilir. Bu dosya o sözleşmeyi mühürler.
 *
 * Not: test ortamında kuyruk `sync` olduğundan ->delay() YOK SAYILIR ve iş
 * satır içi çalışır. Bu yüzden "geri alma penceresi" testleri gecikmeye değil,
 * işin handle() içindeki YENİDEN OKUMA mantığına bakar — canlıda koruma
 * sağlayan da zaten odur.
 */
class KanbanPanoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    /** @return array{0: User, 1: JobListing} */
    private function isverenVeIlan(): array
    {
        $isveren = User::factory()->create();
        $sirket = Company::create(['user_id' => $isveren->id, 'name' => 'Acme GmbH', 'slug' => 'acme-gmbh']);
        $isveren->update(['account_type' => AccountType::Kurumsal]);
        $ilan = $sirket->jobListings()->create([
            'title' => 'Aşçı aranıyor', 'slug' => 'asci-araniyor', 'description' => 'Deneyimli aşçı.',
            'employment_type' => 'tam_zamanli', 'status' => JobStatus::Aktif->value, 'positions' => 1,
        ]);

        return [$isveren, $ilan];
    }

    private function basvuru(JobListing $ilan, string $durum = 'gonderildi'): JobApplication
    {
        return $ilan->applications()->create([
            'user_id' => User::factory()->create()->id,
            'status' => $durum,
            'notified_status' => $durum,
        ]);
    }

    // ------------------------------------------------ Bildirim politikası

    public function test_sessiz_durumlar_adaya_bildirim_gerektirmez(): void
    {
        // İşverenin iç triyaj adımları adayı ilgilendirmez; hayatını etkileyen
        // üç durum bildirilir.
        $this->assertFalse(ApplicationStatus::Gonderildi->bildirimGerektirir());
        $this->assertFalse(ApplicationStatus::Incelendi->bildirimGerektirir());
        $this->assertTrue(ApplicationStatus::Gorusme->bildirimGerektirir());
        $this->assertTrue(ApplicationStatus::Kabul->bildirimGerektirir());
        $this->assertTrue(ApplicationStatus::Red->bildirimGerektirir());
    }

    public function test_sessiz_sutuna_tasimak_kuyruga_is_atmaz(): void
    {
        Queue::fake();
        [$isveren, $ilan] = $this->isverenVeIlan();
        $basvuru = $this->basvuru($ilan);

        $this->actingAs($isveren)->patch("/panel/basvuru/{$basvuru->id}/durum", ['status' => 'incelendi'])
            ->assertRedirect();

        $this->assertSame(ApplicationStatus::Incelendi, $basvuru->refresh()->status);
        Queue::assertNotPushed(BasvuruDurumBildirimi::class);
    }

    public function test_bildirim_gerektiren_durum_gecikmeli_is_kuyruga_atar(): void
    {
        Queue::fake();
        [$isveren, $ilan] = $this->isverenVeIlan();
        $basvuru = $this->basvuru($ilan);

        $this->actingAs($isveren)->patch("/panel/basvuru/{$basvuru->id}/durum", ['status' => 'gorusme']);

        Queue::assertPushed(BasvuruDurumBildirimi::class, function (BasvuruDurumBildirimi $is) use ($basvuru) {
            // Gecikme ŞART: geri alma penceresinin tamamı buna dayanıyor.
            return $is->applicationId === $basvuru->id && $is->delay !== null;
        });
    }

    public function test_ayni_duruma_tasimak_ne_yazar_ne_kuyruga_atar(): void
    {
        Queue::fake();
        [$isveren, $ilan] = $this->isverenVeIlan();
        $basvuru = $this->basvuru($ilan, 'gorusme');
        $oncekiGuncelleme = $basvuru->updated_at;

        $this->travel(5)->minutes();
        $this->actingAs($isveren)->patch("/panel/basvuru/{$basvuru->id}/durum", ['status' => 'gorusme']);

        Queue::assertNotPushed(BasvuruDurumBildirimi::class);
        $this->assertEquals($oncekiGuncelleme, $basvuru->refresh()->updated_at, 'Kartı geldiği sütuna bırakmak DB yazmamalı');
    }

    // ------------------------------------------------ Geri alma penceresi

    public function test_geri_alinan_tasima_adaya_ulasmaz(): void
    {
        Notification::fake();
        [, $ilan] = $this->isverenVeIlan();
        $basvuru = $this->basvuru($ilan);

        // İşveren kartı "Olumsuz"a sürükledi → iş kuyruğa girdi.
        $basvuru->update(['status' => ApplicationStatus::Red]);
        // ...ve 2 dakika dolmadan fikrini değiştirip geri aldı.
        $basvuru->update(['status' => ApplicationStatus::Gonderildi]);

        (new BasvuruDurumBildirimi($basvuru->id))->handle();

        Notification::assertNothingSent();
        $this->assertSame('gonderildi', $basvuru->refresh()->notified_status);
    }

    public function test_arka_arkaya_tasimalar_tek_e_postada_birlesir(): void
    {
        Notification::fake();
        [, $ilan] = $this->isverenVeIlan();
        $basvuru = $this->basvuru($ilan);
        $aday = $basvuru->applicant;

        // Görüşme → Kabul hızlıca: iki iş kuyruğa girer, ama aday yalnız
        // SON durumu bir kez öğrenmeli.
        $basvuru->update(['status' => ApplicationStatus::Gorusme]);
        $basvuru->update(['status' => ApplicationStatus::Kabul]);

        (new BasvuruDurumBildirimi($basvuru->id))->handle();
        (new BasvuruDurumBildirimi($basvuru->id))->handle();

        Notification::assertSentToTimes($aday, ApplicationStatusNotification::class, 1);
        $this->assertSame('kabul', $basvuru->refresh()->notified_status);
    }

    public function test_is_calisirken_durum_sessize_donmusse_gonderilmez(): void
    {
        Notification::fake();
        [, $ilan] = $this->isverenVeIlan();
        $basvuru = $this->basvuru($ilan);

        $basvuru->update(['status' => ApplicationStatus::Gorusme]);
        $basvuru->update(['status' => ApplicationStatus::Incelendi]); // sessiz sütun

        (new BasvuruDurumBildirimi($basvuru->id))->handle();

        Notification::assertNothingSent();
        // İmleç ilerlemez: adaya hâlâ hiçbir şey söylenmedi.
        $this->assertSame('gonderildi', $basvuru->refresh()->notified_status);
    }

    public function test_silinmis_aday_ise_sessizce_cikar(): void
    {
        Notification::fake();
        [, $ilan] = $this->isverenVeIlan();
        $basvuru = $this->basvuru($ilan);
        $basvuru->update(['status' => ApplicationStatus::Kabul]);
        $basvuru->applicant->delete();

        (new BasvuruDurumBildirimi($basvuru->id))->handle();

        Notification::assertNothingSent();
    }

    public function test_bildirim_gonderildikten_sonra_imlec_ilerler(): void
    {
        Notification::fake();
        [, $ilan] = $this->isverenVeIlan();
        $basvuru = $this->basvuru($ilan);

        $basvuru->update(['status' => ApplicationStatus::Gorusme]);
        $this->assertTrue($basvuru->bildirimBekliyor());

        (new BasvuruDurumBildirimi($basvuru->id))->handle();

        $basvuru->refresh();
        $this->assertSame('gorusme', $basvuru->notified_status);
        $this->assertFalse($basvuru->bildirimBekliyor());

        // İkinci kez çalışırsa tekrar göndermez (kuyruk yeniden denemesi).
        (new BasvuruDurumBildirimi($basvuru->id))->handle();
        Notification::assertSentToTimes($basvuru->applicant, ApplicationStatusNotification::class, 1);
    }

    // ------------------------------------------------ Pano

    public function test_pano_bes_sutunu_ve_gercek_sayimlari_gosterir(): void
    {
        [$isveren, $ilan] = $this->isverenVeIlan();
        $this->basvuru($ilan, 'gonderildi');
        $this->basvuru($ilan, 'gonderildi');
        $this->basvuru($ilan, 'gorusme');

        $yanit = $this->actingAs($isveren)->get("/panel/is-ilani/{$ilan->id}/basvurular")->assertOk();

        foreach (ApplicationStatus::cases() as $durum) {
            $yanit->assertSee('data-durum="'.$durum->value.'"', false);
            $yanit->assertSee($durum->getLabel());
        }
        // Sessizlik görünür olmalı — jürinin "görünmez sihir olmasın" şartı.
        $yanit->assertSee('Adaya bildirim gitmez');
        $yanit->assertSee('Aday bilgilendirilir');
    }

    public function test_sutun_tavani_asilirsa_gercek_toplam_yine_gosterilir(): void
    {
        [$isveren, $ilan] = $this->isverenVeIlan();
        $tavan = JobApplicationController::SUTUN_TAVANI;
        for ($i = 0; $i < $tavan + 3; $i++) {
            $this->basvuru($ilan, 'gonderildi');
        }

        $yanit = $this->actingAs($isveren)->get("/panel/is-ilani/{$ilan->id}/basvurular")->assertOk();

        // Kart sayısı tavanla sınırlı ama sayaç gerçeği söyler.
        $this->assertSame($tavan, substr_count($yanit->getContent(), 'data-basvuru='));
        $yanit->assertSee((string) ($tavan + 3));
        $yanit->assertSee('En yeni '.$tavan.' başvuru gösteriliyor');
    }

    public function test_surukleme_json_yaniti_doner(): void
    {
        [$isveren, $ilan] = $this->isverenVeIlan();
        $basvuru = $this->basvuru($ilan);

        $this->actingAs($isveren)
            ->patchJson("/panel/basvuru/{$basvuru->id}/durum", ['status' => 'gorusme'])
            ->assertOk()
            ->assertJson(['durum' => 'gorusme', 'etiket' => 'Görüşmeye çağrıldı']);
    }

    public function test_gecersiz_durum_reddedilir(): void
    {
        Queue::fake();
        [$isveren, $ilan] = $this->isverenVeIlan();
        $basvuru = $this->basvuru($ilan);

        // Uygulama sözleşmesi: bootstrap/app.php'de shouldRenderJsonWhen yalnız
        // `api/*` için JSON render eder, dolayısıyla bu rotada doğrulama hatası
        // 422 değil "geri yönlendir + hata torbası" olur. Sürükleme yolu bunu
        // zararsız karşılar: fetch JSON çözemez, kart eski sütununa geri konur
        // ve kullanıcıya hata mesajı gösterilir (bkz. app.js kanbanPano.birak).
        $this->actingAs($isveren)
            ->patch("/panel/basvuru/{$basvuru->id}/durum", ['status' => 'uydurma'])
            ->assertRedirect()
            ->assertSessionHasErrors('status');

        $this->assertSame(ApplicationStatus::Gonderildi, $basvuru->refresh()->status);
        Queue::assertNotPushed(BasvuruDurumBildirimi::class);
    }

    public function test_baskasinin_ilanindaki_panoya_erisilemez(): void
    {
        [, $ilan] = $this->isverenVeIlan();
        $yabanci = User::factory()->create();

        $this->actingAs($yabanci)->get("/panel/is-ilani/{$ilan->id}/basvurular")->assertForbidden();
    }

    public function test_erisilebilir_yol_her_kartta_bulunur(): void
    {
        // Sürükleme bir hızlandırıcıdır; klavye/JS'siz yol ZORUNLU.
        // Eski ekranda select `onchange` ile kendiliğinden gönderiyordu ve
        // submit butonu yoktu — yani JS'siz hiçbir yol yoktu.
        [$isveren, $ilan] = $this->isverenVeIlan();
        $this->basvuru($ilan);

        $yanit = $this->actingAs($isveren)->get("/panel/is-ilani/{$ilan->id}/basvurular")->assertOk();

        $yanit->assertSee('name="status"', false);
        $yanit->assertSee('type="submit"', false);
        $yanit->assertDontSee('onchange', false);
    }
}

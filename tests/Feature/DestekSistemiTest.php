<?php

namespace Tests\Feature;

use App\Enums\ContactMessageStatus;
use App\Enums\ContactPriority;
use App\Enums\UserRole;
use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\ContactReplyNotification;
use App\Support\Destek;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Destek bileti sistemi (2026-07-27) — iletişim gelen kutusunun
 * öncelik/atama/kapanış/yanıt ile genişletilmiş hâli.
 *
 * Mevcut public form sözleşmesi ContactMessageTest'te mühürlü; bu dosya
 * yalnız YENİ davranışları kapsar.
 */
class DestekSistemiTest extends TestCase
{
    use RefreshDatabase;

    private function bilet(array $ozellikler = []): ContactMessage
    {
        return ContactMessage::create(array_merge([
            'name' => 'Ayşe Yılmaz',
            'email' => 'ayse@example.com',
            'category' => 'teknik_destek',
            'message' => 'Giriş yapamıyorum, yardım eder misiniz?',
        ], $ozellikler));
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    }

    private function moderator(): User
    {
        return User::factory()->create(['role' => UserRole::Moderator, 'email_verified_at' => now()]);
    }

    // ------------------------------------------------ Varsayılanlar

    public function test_yeni_bilet_varsayilanlari(): void
    {
        // refresh(): status/priority DB varsayılanlarından gelir, create()
        // sonrası bellekteki örnekte henüz yoktur.
        $bilet = $this->bilet()->refresh();

        $this->assertSame(ContactMessageStatus::Yeni, $bilet->status);
        $this->assertSame(ContactPriority::Normal, $bilet->priority);
        $this->assertNull($bilet->assigned_to);
        $this->assertNull($bilet->first_replied_at);
        $this->assertNull($bilet->closed_at);
        $this->assertTrue($bilet->status->acikMi());
    }

    public function test_acik_scope_yalniz_ilgilenilmesi_gerekenleri_getirir(): void
    {
        $this->bilet(['status' => 'yeni']);
        $this->bilet(['status' => 'okundu']);
        $this->bilet(['status' => 'yanitlandi']);
        $this->bilet(['status' => 'kapandi']);

        $this->assertSame(2, ContactMessage::query()->acik()->count());
    }

    // ------------------------------------------------ Yanıtlama

    public function test_yanit_misafire_e_posta_gonderir_ve_bileti_gunceller(): void
    {
        Notification::fake();

        $admin = $this->admin();
        $this->actingAs($admin);
        $bilet = $this->bilet();

        $yanit = Destek::yanitla($bilet, 'Merhaba, parolanı sıfırlama bağlantısı gönderdik.');

        Notification::assertSentOnDemand(ContactReplyNotification::class);

        $bilet->refresh();
        $this->assertSame(ContactMessageStatus::Yanitlandi, $bilet->status);
        $this->assertNotNull($bilet->first_replied_at);
        // Yanıtlayan bileti otomatik üstlenir.
        $this->assertSame($admin->id, $bilet->assigned_to);

        $this->assertNotNull($yanit->sent_at);
        $this->assertFalse($yanit->basarisizMi());
        $this->assertSame($admin->id, $yanit->user_id);
        $this->assertStringContainsString('parolanı sıfırlama', $yanit->body);
    }

    public function test_ikinci_yanit_ilk_yanit_zamanini_degistirmez(): void
    {
        Notification::fake();
        $this->actingAs($this->admin());
        $bilet = $this->bilet();

        Destek::yanitla($bilet, 'İlk yanıt.');
        $ilkZaman = $bilet->fresh()->first_replied_at;

        $this->travel(5)->minutes();
        Destek::yanitla($bilet->fresh(), 'İkinci yanıt.');

        $this->assertEquals($ilkZaman, $bilet->fresh()->first_replied_at);
        $this->assertSame(2, $bilet->replies()->count());
    }

    public function test_gonderim_basarisiz_olursa_bilet_yanitlandi_gorunmez(): void
    {
        // Kuyruk/SMTP hatası sessizce yutulmamalı: yanıt kaydedilir ama
        // hata damgalanır ve bilet "yanıtlandı"ya GEÇMEZ.
        $this->actingAs($this->admin());
        $bilet = $this->bilet();

        // Sahte kurmak yerine GERÇEK bir gönderim hatası tetiklenir:
        // geçersiz alıcı adresi Symfony Mailer'da RfcComplianceException
        // fırlatır. (Bilet doğrudan DB'ye yazıldığı için form validasyonuna
        // takılmaz — canlıda bozuk veri/erişilemez SMTP aynı yola düşer.)
        $bilet->forceFill(['email' => 'gecersiz-adres'])->save();

        $yanit = Destek::yanitla($bilet, 'Gönderilemeyecek yanıt.');

        $this->assertTrue($yanit->basarisizMi(), 'Gönderim hatası bilete damgalanmalı');
        $this->assertNotEmpty($yanit->error);
        $this->assertNull($yanit->sent_at);
        // Yanıt METNİ kaybolmamalı — sahip tekrar yazmak zorunda kalmasın.
        $this->assertStringContainsString('Gönderilemeyecek yanıt.', $yanit->body);

        $bilet->refresh();
        $this->assertSame(ContactMessageStatus::Yeni, $bilet->status);
        $this->assertNull($bilet->first_replied_at);
    }

    // ------------------------------------------------ Yetki

    public function test_yalniz_admin_yanitlayabilir(): void
    {
        $this->actingAs($this->admin());
        $this->assertTrue(Destek::yanitlayabilirMi());

        $this->actingAs($this->moderator());
        $this->assertFalse(Destek::yanitlayabilirMi(), 'Moderatör site adına dışarıya e-posta yazamamalı');

        $this->actingAs(User::factory()->create(['role' => UserRole::Uye]));
        $this->assertFalse(Destek::yanitlayabilirMi());
    }

    // ------------------------------------------------ Kapatma

    public function test_kapatma_zaman_damgasi_birakir_ve_acik_listeden_cikarir(): void
    {
        $bilet = $this->bilet(['status' => 'yanitlandi']);

        Destek::kapat($bilet);

        $bilet->refresh();
        $this->assertSame(ContactMessageStatus::Kapandi, $bilet->status);
        $this->assertNotNull($bilet->closed_at);
        $this->assertFalse($bilet->status->acikMi());
        $this->assertSame(0, ContactMessage::query()->acik()->count());
    }

    // ------------------------------------------------ Güvenlik

    public function test_markdown_enjeksiyonu_e_postada_link_uretmez(): void
    {
        // KEŞİF BULGUSU: Blade `{{ }}` yalnız < ve > kaçırır; [ ] ( )
        // dokunulmadan kalır. withSecuredEncoding() olmadan misafirin
        // mesajındaki markdown, yöneticiye giden mailde TIKLANABİLİR
        // bir link olarak render ediliyordu.
        $this->actingAs($this->admin());
        $bilet = $this->bilet();

        $bildirim = new ContactReplyNotification($bilet, '[Sahte link](https://kotu-site.example)');
        $mail = $bildirim->toMail((object) []);
        $html = (string) $mail->render();

        $this->assertStringNotContainsString('href="https://kotu-site.example"', $html);
        $this->assertStringNotContainsString('<a href="https://kotu-site.example"', $html);
    }

    public function test_yanit_e_postasi_destek_adresine_reply_to_koyar(): void
    {
        $this->actingAs($this->admin());
        $bilet = $this->bilet();

        $mail = (new ContactReplyNotification($bilet, 'Yanıt metni.'))->toMail((object) []);

        $this->assertNotEmpty($mail->replyTo);
        $this->assertSame(setting('iletisim.eposta'), $mail->replyTo[0][0]);
    }

    // ------------------------------------------------ Panel

    public function test_panel_yanitla_aksiyonu_gercekten_calisir(): void
    {
        // Destek::yanitla() birim olarak test edildi; bu test Filament
        // AKSİYON BAĞLANTISININ (modal formu → action) çalıştığını mühürler.
        Notification::fake();

        $admin = $this->admin();
        $bilet = $this->bilet();

        Livewire::actingAs($admin)
            ->test(ListContactMessages::class)
            ->callTableAction('yanitla', $bilet, ['body' => 'Panelden gönderilen yanıt.'])
            ->assertHasNoTableActionErrors();

        Notification::assertSentOnDemand(ContactReplyNotification::class);

        $bilet->refresh();
        $this->assertSame(ContactMessageStatus::Yanitlandi, $bilet->status);
        $this->assertSame(1, $bilet->replies()->count());
    }

    public function test_panel_kapat_ve_yeniden_ac_aksiyonlari(): void
    {
        $admin = $this->admin();
        $bilet = $this->bilet(['status' => 'yanitlandi']);

        // Sekme sözleşmesi: her aksiyon, kaydın GÖRÜNDÜĞÜ sekmeden çağrılır.
        // "Açık" sekmesi yalnız yeni+okundu getirdiği için yanıtlanmış bilet
        // orada yoktur (bu, tasarımın kendisi — kapanmış işler listeyi şişirmesin).
        Livewire::actingAs($admin)
            ->test(ListContactMessages::class)
            ->set('activeTab', 'yanitlandi')
            ->callTableAction('kapat', $bilet);

        $this->assertSame(ContactMessageStatus::Kapandi, $bilet->refresh()->status);
        $this->assertNotNull($bilet->closed_at);

        // Varsayılan sekme "Açık" olduğu için kapanmış bilet o sorguda yok —
        // yeniden açmak için "Kapandı" sekmesine geçilir (kullanıcı da öyle yapar).
        Livewire::actingAs($admin)
            ->test(ListContactMessages::class)
            ->set('activeTab', 'kapandi')
            ->callTableAction('yeniden_ac', $bilet);

        $bilet->refresh();
        $this->assertSame(ContactMessageStatus::Okundu, $bilet->status);
        $this->assertNull($bilet->closed_at);
    }

    public function test_panel_ustlen_aksiyonu_bileti_atar(): void
    {
        $admin = $this->admin();
        $bilet = $this->bilet();

        Livewire::actingAs($admin)
            ->test(ListContactMessages::class)
            ->callTableAction('ustlen', $bilet);

        $this->assertSame($admin->id, $bilet->refresh()->assigned_to);
    }

    public function test_moderator_yanitla_aksiyonunu_goremez(): void
    {
        $bilet = $this->bilet();

        Livewire::actingAs($this->moderator())
            ->test(ListContactMessages::class)
            ->assertTableActionHidden('yanitla', $bilet);
    }

    public function test_admin_gelen_kutusunu_acabilir(): void
    {
        $this->bilet();

        $this->actingAs($this->admin())->get('/yonetim/contact-messages')->assertOk();
    }

    public function test_uye_gelen_kutusuna_erisemez(): void
    {
        $uye = User::factory()->create(['role' => UserRole::Uye, 'email_verified_at' => now()]);

        $this->actingAs($uye)->get('/yonetim/contact-messages')->assertRedirect(route('dashboard'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\View\ComponentAttributeBag;
use Tests\TestCase;

/**
 * Tasarım sistemi denetiminin (2026-08-20) öncelikli 3 maddesini mühürler:
 * paylaşılan buton bileşeni (84 varyant → tek kaynak), marka rengini takip
 * eden gölge/parlama (canlı bug — bkz. resources/css/app.css'teki
 * --shadow-brand gerekçesi, aynı hata sınıfının ikinci sızması), ve satıcı
 * kıdemi rozetinin tek bileşene toplanması (4 yüzeyde 4 farklı tarif).
 */
class TasarimDenetimiBilesenleriTest extends TestCase
{
    // ------------------------------------------------------------- x-button

    public function test_href_verilince_link_olarak_basilir(): void
    {
        $html = view('components.button', ['href' => '/hedef'])->with('slot', 'Git')->render();

        $this->assertStringContainsString('<a href="/hedef"', $html);
        $this->assertStringNotContainsString('<button', $html);
    }

    public function test_href_verilmeyince_button_olarak_basilir(): void
    {
        $html = view('components.button', ['type' => 'submit'])->with('slot', 'Gönder')->render();

        $this->assertStringContainsString('<button type="submit"', $html);
        $this->assertStringNotContainsString('<a href', $html);
    }

    public function test_primary_varyanti_marka_izleyen_golgeyi_kullanir(): void
    {
        // Gerçek olay (2026-08-20 tasarım denetimi): birincil butonlarda
        // gölge `shadow-[rgba(62,99,240,…)]` gibi HAM bir renkle sabitti —
        // marka rengi panelden değiştirildiğinde bu butonlar eski renkte
        // kalıyordu. `shadow-brand` marka rengini `color-mix()` ile canlı
        // takip ediyor (bkz. resources/css/app.css).
        $html = view('components.button', ['variant' => 'primary'])->with('slot', 'Devam')->render();

        $this->assertStringContainsString('shadow-brand', $html);
        $this->assertStringNotContainsString('rgba(62,99,240', $html);
    }

    public function test_varyantlar_birbirinden_farkli_siniflar_uretir(): void
    {
        $primary = view('components.button', ['variant' => 'primary'])->with('slot', 'x')->render();
        $inverse = view('components.button', ['variant' => 'inverse'])->with('slot', 'x')->render();
        $outlineDark = view('components.button', ['variant' => 'outline-dark'])->with('slot', 'x')->render();
        $secondary = view('components.button', ['variant' => 'secondary'])->with('slot', 'x')->render();

        $this->assertStringContainsString('bg-emerald-700', $primary);
        $this->assertStringContainsString('bg-white text-stone-800', $inverse);
        $this->assertStringContainsString('border-white/25', $outlineDark);
        $this->assertStringContainsString('border-stone-300', $secondary);
    }

    /**
     * Gerçek olay (2026-08-21, mobilde ölçüldü): üst menüdeki "İlan Ver"
     * butonu `class="hidden md:inline-flex"` alıyordu ama mobilde de
     * görünüp taşıyordu. Derlenmiş CSS'te `.inline-flex` `.hidden`'dan
     * SONRA geliyor (HTML'deki sınıf sırasının önemi yok) — bileşenin kendi
     * sabit `inline-flex`'i her zaman kazanıyordu. Çağıran bir görünürlük
     * sınıfı verdiğinde bileşen kendi `inline-flex`'ini eklememeli.
     */
    public function test_caginan_gorunurluk_sinifi_verince_kendi_inline_flexini_eklemez(): void
    {
        $html = view('components.button', [
            'attributes' => new ComponentAttributeBag(['class' => 'hidden md:inline-flex']),
        ])->with('slot', 'İlan Ver')->render();

        $this->assertStringContainsString('hidden md:inline-flex', $html);
        $this->assertStringNotContainsString('inline-flex items-center', $html, 'Bileşen kendi sabit inline-flex\'ini eklerse "hidden" ile çakışıp mobilde de görünür.');
    }

    public function test_caginan_gorunurluk_sinifi_vermeyince_varsayilan_inline_flex_korunur(): void
    {
        $html = view('components.button', [])->with('slot', 'Devam')->render();

        $this->assertStringContainsString('inline-flex items-center', $html);
    }

    public function test_ekstra_oznitelikler_ve_siniflar_birlesir(): void
    {
        // Panel formundaki "Yayınla"/"Taslak olarak kaydet" düğmeleri name/value
        // taşıyor — bileşen bunları yutmadan geçirmeli.
        $html = view('components.button', [
            'type' => 'submit',
            'attributes' => new ComponentAttributeBag(['name' => 'eylem', 'value' => 'yayinla', 'class' => 'w-full']),
        ])->with('slot', 'İlanı Yayınla')->render();

        $this->assertStringContainsString('name="eylem"', $html);
        $this->assertStringContainsString('value="yayinla"', $html);
        $this->assertStringContainsString('w-full', $html);
        $this->assertStringContainsString('bg-emerald-700', $html, 'Varsayılan varyant sınıfları ekstra class ile silinmemeli.');
    }

    // -------------------------------------------------------- x-kidem-rozeti

    private function satici(int $ayOnce): User
    {
        return User::factory()->make(['created_at' => now()->subMonths($ayOnce)]);
    }

    public function test_pill_varyanti_kisa_kidemi_basar(): void
    {
        $html = view('components.kidem-rozeti', ['user' => $this->satici(5)])->render();

        $this->assertStringContainsString('5 aydır üye', $html);
        $this->assertStringContainsString('rounded-full', $html);
    }

    public function test_pill_varyanti_taze_hesapta_hicbir_sey_basmaz(): void
    {
        // Gerçek olay: kart bağlamında yer sınırlı — kidemKisa() 1 aydan
        // taze hesapta null döner, rozet o zaman hiç basılmamalı (eski
        // davranışla aynı, tek bileşenden).
        $html = trim(view('components.kidem-rozeti', ['user' => $this->satici(0)])->render());

        $this->assertSame('', $html);
    }

    public function test_text_varyanti_tam_cumleyi_basar_taze_hesapta_bile(): void
    {
        $html = view('components.kidem-rozeti', ['user' => $this->satici(0), 'variant' => 'text'])->render();

        $this->assertStringContainsString('yakın zamanda katıldı', $html);
    }

    public function test_pill_varyanti_baslik_ozniteligi_tam_cumleyi_tasir(): void
    {
        $html = view('components.kidem-rozeti', ['user' => $this->satici(25)])->render();

        $this->assertStringContainsString('title="Nisoya&#039;da 2 yıldır üye"', $html);
        $this->assertStringContainsString('>2 yıldır üye<', $html);
    }

    public function test_ekstra_class_birlestirilir(): void
    {
        $html = view('components.kidem-rozeti', [
            'user' => $this->satici(3),
            'variant' => 'text',
            'attributes' => new ComponentAttributeBag(['class' => 'mt-1']),
        ])->render();

        $this->assertStringContainsString('mt-1', $html);
        $this->assertStringContainsString('text-xs font-medium', $html);
    }
}

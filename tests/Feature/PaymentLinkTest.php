<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\PaymentLink;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Satıcının kendi ödeme linkini/QR kodunu ekleyip kaldırması. Nisoya bu
 * linkler/kodlar üzerinden hiçbir para akışını görmez veya işlemez.
 */
class PaymentLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
        Storage::fake('public');
    }

    public function test_user_can_add_a_payment_method_with_link(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/panel/profil/odeme-linki', [
            'method' => 'paypal',
            'detail' => 'https://paypal.me/kullaniciadi',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('payment_links', [
            'user_id' => $user->id,
            'method' => 'paypal',
            'detail' => 'https://paypal.me/kullaniciadi',
        ]);
    }

    public function test_user_can_add_a_payment_method_with_qr_code(): void
    {
        $user = User::factory()->create();
        $qr = UploadedFile::fake()->image('kaspi-qr.jpg', 500, 500);

        $this->actingAs($user)->post('/panel/profil/odeme-linki', [
            'method' => 'kaspi',
            'qr' => $qr,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $link = PaymentLink::where('user_id', $user->id)->where('method', 'kaspi')->first();
        $this->assertNotNull($link);
        $this->assertNotNull($link->qr_path);
        Storage::disk('public')->assertExists($link->qr_path);
    }

    public function test_user_cannot_add_same_method_twice(): void
    {
        $user = User::factory()->create();
        PaymentLink::create(['user_id' => $user->id, 'method' => PaymentMethod::Zelle]);

        $this->actingAs($user)->post('/panel/profil/odeme-linki', [
            'method' => 'zelle',
        ])->assertSessionHasErrors('method');

        $this->assertDatabaseCount('payment_links', 1);
    }

    public function test_add_payment_method_rejects_invalid_value(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/panel/profil/odeme-linki', [
            'method' => 'bitcoin',
        ])->assertSessionHasErrors('method');
    }

    public function test_user_can_remove_own_payment_link(): void
    {
        $user = User::factory()->create();
        $link = PaymentLink::create(['user_id' => $user->id, 'method' => PaymentMethod::Venmo]);

        $this->actingAs($user)->delete("/panel/profil/odeme-linki/{$link->id}")->assertRedirect();

        $this->assertDatabaseMissing('payment_links', ['id' => $link->id]);
    }

    public function test_user_cannot_remove_someone_elses_payment_link(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $link = PaymentLink::create(['user_id' => $owner->id, 'method' => PaymentMethod::Venmo]);

        $this->actingAs($intruder)->delete("/panel/profil/odeme-linki/{$link->id}")->assertForbidden();

        $this->assertDatabaseHas('payment_links', ['id' => $link->id]);
    }

    public function test_removing_payment_link_deletes_qr_file(): void
    {
        $user = User::factory()->create();
        $qr = UploadedFile::fake()->image('kaspi-qr.jpg', 500, 500);
        $this->actingAs($user)->post('/panel/profil/odeme-linki', ['method' => 'kaspi', 'qr' => $qr]);
        $link = PaymentLink::where('user_id', $user->id)->first();
        $qrPath = $link->qr_path;

        $this->actingAs($user)->delete("/panel/profil/odeme-linki/{$link->id}");

        Storage::disk('public')->assertMissing($qrPath);
    }

    public function test_payment_links_show_as_clickable_badge_on_public_profile(): void
    {
        $user = User::factory()->create(['username' => 'odeme-test-satici']);
        PaymentLink::create(['user_id' => $user->id, 'method' => PaymentMethod::PayPal, 'detail' => 'https://paypal.me/odemetestsatici']);
        PaymentLink::create(['user_id' => $user->id, 'method' => PaymentMethod::SepaIban, 'detail' => 'TR00 0000 0000 0000 0000 0000 00']);

        $response = $this->get("/uye/{$user->username}");

        $response->assertOk();
        $response->assertSee('https://paypal.me/odemetestsatici', false);
        $response->assertSee('TR00 0000 0000 0000 0000 0000 00');
    }

    public function test_suggested_payment_methods_for_kazakhstan_include_kaspi(): void
    {
        $suggestions = PaymentMethod::suggestedFor('KZ');

        $this->assertContains(PaymentMethod::Kaspi, $suggestions);
    }

    public function test_suggested_payment_methods_for_usa_include_zelle_and_venmo(): void
    {
        $suggestions = PaymentMethod::suggestedFor('US');

        $this->assertContains(PaymentMethod::Zelle, $suggestions);
        $this->assertContains(PaymentMethod::Venmo, $suggestions);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Settings;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\JobCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Faz İ3: doğrulanmış üye rozeti — "1. Tasarım"da eski düz ✓, "2. Tasarım"da
 * mühür rozeti (bkz. components/verified-badge.blade.php).
 */
class VerifiedBadgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Settings::forget();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, JobCategorySeeder::class]);
    }

    public function test_candidate_card_shows_plain_checkmark_in_eski_mode(): void
    {
        User::factory()->create(['name' => 'Doğrulanmış Aday', 'is_searchable' => true, 'is_verified' => true]);

        $this->get('/adaylar')
            ->assertOk()
            ->assertSee('>✓<', false)
            ->assertDontSee('nisoya-seal', false);
    }

    public function test_candidate_card_shows_muhur_badge_in_yeni_mode(): void
    {
        Settings::setMany(['gorunum.tasarim_modu' => 'yeni']);
        User::factory()->create(['name' => 'Doğrulanmış Aday', 'is_searchable' => true, 'is_verified' => true]);

        $this->get('/adaylar')
            ->assertOk()
            ->assertDontSee('>✓<', false)
            ->assertSee('Doğrulanmış üye', false);
    }

    public function test_profile_page_shows_muhur_badge_in_yeni_mode(): void
    {
        Settings::setMany(['gorunum.tasarim_modu' => 'yeni']);
        $user = User::factory()->create(['username' => 'test-usta', 'is_verified' => true]);

        $this->get(route('profiles.show', $user->username))
            ->assertOk()
            ->assertSee('Doğrulanmış');
    }
}

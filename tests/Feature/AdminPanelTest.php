<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_all_resource_pages(): void
    {
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        $pages = [
            '/yonetim',
            '/yonetim/categories',
            '/yonetim/categories/create',
            '/yonetim/listings',
            '/yonetim/listings/create',
            '/yonetim/users',
            '/yonetim/currencies',
            '/yonetim/countries',
            '/yonetim/reports',
            '/yonetim/reviews',
            '/yonetim/tags',
        ];

        foreach ($pages as $page) {
            $this->actingAs($admin)->get($page)->assertOk();
        }
    }

    public function test_member_cannot_access_admin_panel(): void
    {
        $member = User::factory()->create([
            'role' => UserRole::Uye,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($member)->get('/yonetim')->assertForbidden();
    }

    public function test_reference_data_is_seeded(): void
    {
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);

        $this->assertDatabaseHas('currencies', ['code' => 'EUR']);
        $this->assertDatabaseMissing('currencies', ['code' => 'TRY']); // TL olmamalı
        $this->assertDatabaseHas('countries', ['code' => 'DE', 'name_tr' => 'Almanya']);
        $this->assertDatabaseHas('categories', ['slug' => 'egitim-ders', 'parent_id' => null]);
    }
}

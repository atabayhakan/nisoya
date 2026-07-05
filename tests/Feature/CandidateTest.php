<?php

namespace Tests\Feature;

use App\Models\JobCategory;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\JobCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Yetenek Havuzu: opt-in aday arama (candidate search).
 */
class CandidateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, JobCategorySeeder::class]);
    }

    public function test_page_loads(): void
    {
        $this->get('/adaylar')->assertOk()->assertSee('Yetenek Havuzu');
    }

    public function test_only_opted_in_users_are_listed(): void
    {
        User::factory()->create(['name' => 'Görünür Üye', 'is_searchable' => true]);
        User::factory()->create(['name' => 'Gizli Üye', 'is_searchable' => false]);

        $this->get('/adaylar')
            ->assertOk()
            ->assertSee('Görünür Üye')
            ->assertDontSee('Gizli Üye');
    }

    public function test_deleted_and_corporate_accounts_are_excluded(): void
    {
        User::factory()->create(['name' => 'Silinmiş Üye', 'is_searchable' => true, 'status' => 'silinmis']);
        User::factory()->create(['name' => 'Şirket Üyesi', 'is_searchable' => true, 'account_type' => 'kurumsal']);

        $this->get('/adaylar')
            ->assertOk()
            ->assertDontSee('Silinmiş Üye')
            ->assertDontSee('Şirket Üyesi');
    }

    public function test_filters_by_keyword_category_and_city(): void
    {
        $category = JobCategory::first();
        User::factory()->create([
            'name' => 'Ahmet Yazılımcı', 'bio' => 'Kıdemli backend geliştirici',
            'is_searchable' => true, 'job_category_id' => $category->id, 'city' => 'Berlin', 'country_code' => 'DE',
        ]);
        User::factory()->create(['name' => 'Ayşe Aşçı', 'is_searchable' => true, 'city' => 'Munich', 'country_code' => 'DE']);

        $this->get('/adaylar?q=backend')->assertOk()->assertSee('Ahmet Yazılımcı')->assertDontSee('Ayşe Aşçı');
        $this->get('/adaylar?kategori='.$category->slug)->assertOk()->assertSee('Ahmet Yazılımcı')->assertDontSee('Ayşe Aşçı');
        $this->get('/adaylar?sehir=Berlin')->assertOk()->assertSee('Ahmet Yazılımcı')->assertDontSee('Ayşe Aşçı');
    }

    public function test_user_can_opt_in_via_profile_settings(): void
    {
        $user = User::factory()->create(['is_searchable' => false]);
        $category = JobCategory::first();

        $this->actingAs($user)->put('/panel/profil', [
            'name' => $user->name,
            'username' => $user->username,
            'country_code' => 'DE',
            'preferred_currency' => 'EUR',
            'is_searchable' => '1',
            'job_category_id' => $category->id,
        ])->assertRedirect();

        $user->refresh();
        $this->assertTrue($user->is_searchable);
        $this->assertSame($category->id, $user->job_category_id);
    }

    public function test_unchecking_toggle_opts_user_out(): void
    {
        $user = User::factory()->create(['is_searchable' => true]);

        $this->actingAs($user)->put('/panel/profil', [
            'name' => $user->name,
            'username' => $user->username,
            'country_code' => 'DE',
            'preferred_currency' => 'EUR',
        ])->assertRedirect();

        $this->assertFalse($user->refresh()->is_searchable);
    }

    public function test_account_deletion_removes_talent_pool_visibility(): void
    {
        $user = User::factory()->create(['is_searchable' => true]);

        $this->actingAs($user)->delete('/panel/profil', [
            'current_password' => 'password',
            'confirm_text' => 'HESABIMI SİL',
        ]);

        $user->refresh();
        $this->assertFalse($user->is_searchable);
        $this->assertNull($user->job_category_id);
    }
}

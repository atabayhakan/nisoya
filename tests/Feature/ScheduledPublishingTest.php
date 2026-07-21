<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Faz 4 · Zamanlanmış (ileri tarihli) yayın — CMS Sayfaları.
 */
class ScheduledPublishingTest extends TestCase
{
    use RefreshDatabase;

    private function page(string $slug, PageStatus $status, ?\DateTimeInterface $publishAt, bool $footer = false): Page
    {
        return Page::create([
            'title' => 'Sayfa '.$slug,
            'slug' => $slug,
            'blocks' => [],
            'status' => $status,
            'publish_at' => $publishAt,
            'show_in_footer' => $footer,
            'sort_order' => 0,
        ]);
    }

    // ------------------------------------------------------------ Scope

    public function test_published_scope_includes_null_and_past(): void
    {
        $this->page('hemen', PageStatus::Yayin, null);
        $this->page('gecmis', PageStatus::Yayin, now()->subDay());

        $this->assertSame(2, Page::query()->published()->count());
    }

    public function test_published_scope_excludes_future_and_draft(): void
    {
        $this->page('gelecek', PageStatus::Yayin, now()->addDay());
        $this->page('taslak', PageStatus::Taslak, null);

        $this->assertSame(0, Page::query()->published()->count());
        $this->assertSame(1, Page::query()->scheduled()->count());
    }

    public function test_is_scheduled_helper(): void
    {
        $this->assertTrue($this->page('a', PageStatus::Yayin, now()->addDay())->isScheduled());
        $this->assertFalse($this->page('b', PageStatus::Yayin, now()->subDay())->isScheduled());
        $this->assertFalse($this->page('c', PageStatus::Yayin, null)->isScheduled());
        $this->assertFalse($this->page('d', PageStatus::Taslak, now()->addDay())->isScheduled());
    }

    // ------------------------------------------------- Public görünürlük

    public function test_public_page_visible_when_due(): void
    {
        $this->page('gorunur-sayfa', PageStatus::Yayin, now()->subMinute());

        $this->get('/gorunur-sayfa')->assertOk();
    }

    public function test_public_page_hidden_when_scheduled(): void
    {
        $this->page('gizli-sayfa', PageStatus::Yayin, now()->addDay());

        $this->get('/gizli-sayfa')->assertNotFound();
    }

    // ------------------------------------------------------------ Komut

    public function test_command_refreshes_footer_cache_when_page_becomes_due(): void
    {
        $this->page('zamanli-footer', PageStatus::Yayin, now()->subMinutes(5), footer: true);

        // Sayfa zamanlanmışken oluşmuş gibi bayat (boş) önbellek koy.
        Cache::forever(Page::NAV_CACHE_KEY, []);

        $this->artisan('content:publish-due')->assertExitCode(0);

        $this->assertFalse(Cache::has(Page::NAV_CACHE_KEY));
    }

    public function test_command_is_noop_when_nothing_became_due(): void
    {
        // Zamanı yeni gelen footer sayfası yok.
        $this->page('ileride', PageStatus::Yayin, now()->addDay(), footer: true);
        Cache::forever(Page::NAV_CACHE_KEY, ['x']);

        $this->artisan('content:publish-due')->assertExitCode(0);

        $this->assertTrue(Cache::has(Page::NAV_CACHE_KEY));
    }
}

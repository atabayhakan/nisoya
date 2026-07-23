<?php

namespace Tests\Feature\Growth;

use App\Services\Growth\ContactEnricher;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContactEnricherTest extends TestCase
{
    public function test_prefers_role_based_over_personal(): void
    {
        $html = '<a href="mailto:mehmet@anadolukebap.com">Mehmet</a><a href="mailto:info@anadolukebap.com">info</a>';

        $this->assertSame('info@anadolukebap.com', (new ContactEnricher)->extractFromHtml($html));
    }

    public function test_extracts_plaintext_email(): void
    {
        $this->assertSame('iletisim@cafe.com', (new ContactEnricher)->extractFromHtml('Yazın: iletisim@cafe.com'));
    }

    public function test_filters_assets_and_third_party_addresses(): void
    {
        $html = '<img src="logo@2x.png"><script>sentry@wixpress.com</script><span>satis@bishkekmobilya.kg</span>';

        $this->assertSame('satis@bishkekmobilya.kg', (new ContactEnricher)->extractFromHtml($html));
    }

    public function test_returns_null_when_no_email(): void
    {
        $this->assertNull((new ContactEnricher)->extractFromHtml('<p>Telefon: +1 555 0100</p>'));
    }

    public function test_enrich_fetches_and_extracts_from_website(): void
    {
        Http::fake(['*' => Http::response('<footer><a href="mailto:info@site.com">yaz</a></footer>')]);

        $this->assertSame('info@site.com', (new ContactEnricher)->enrich('https://site.test'));
    }

    public function test_enrich_returns_null_without_website(): void
    {
        $this->assertNull((new ContactEnricher)->enrich(null));
        $this->assertNull((new ContactEnricher)->enrich('  '));
    }

    public function test_enrich_returns_null_on_http_failure(): void
    {
        Http::fake(['*' => Http::response('', 500)]);

        $this->assertNull((new ContactEnricher)->enrich('https://site.test'));
    }
}

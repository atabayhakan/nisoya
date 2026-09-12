<?php

namespace Tests\Unit;

use App\Services\Ai\AiJson;
use PHPUnit\Framework\TestCase;

class AiJsonTest extends TestCase
{
    public function test_pure_json_object(): void
    {
        $this->assertSame(['ok' => true], AiJson::decode('{"ok": true}'));
    }

    public function test_pure_json_array(): void
    {
        $this->assertSame([1, 2, 3], AiJson::decode('[1, 2, 3]'));
    }

    public function test_markdown_code_block(): void
    {
        $input = "```json\n{\"ok\": true}\n```";
        $this->assertSame(['ok' => true], AiJson::decode($input));
    }

    public function test_markdown_without_language_tag(): void
    {
        $input = "```\n{\"ok\": true}\n```";
        $this->assertSame(['ok' => true], AiJson::decode($input));
    }

    public function test_markdown_with_preamble(): void
    {
        $input = "Certainly! Here is your requested JSON object:\n```json\n{\"ok\": true}\n```";
        $this->assertSame(['ok' => true], AiJson::decode($input));
    }

    public function test_markdown_with_postamble(): void
    {
        $input = "```json\n{\"ok\": true}\n```\nHope that helps! Let me know if you need anything else.";
        $this->assertSame(['ok' => true], AiJson::decode($input));
    }

    public function test_markdown_with_both_preamble_and_postamble(): void
    {
        $input = "Anladım, işte test sonucu:\n```json\n{\"ok\": true}\n```\nİşlem başarıyla tamamlandı.";
        $this->assertSame(['ok' => true], AiJson::decode($input));
    }

    public function test_plain_text_surrounding_json_object(): void
    {
        $input = 'Bağlantı kontrolü yapıldı: {"ok": true} yanıtı alındı.';
        $this->assertSame(['ok' => true], AiJson::decode($input));
    }

    public function test_deepseek_reasoning_think_tags(): void
    {
        $input = "<think>\nThe user wants a test JSON response with ok=true.\nI will output {\"ok\": true}.\n</think>\n```json\n{\"ok\": true}\n```";
        $this->assertSame(['ok' => true], AiJson::decode($input));
    }

    public function test_trailing_commas(): void
    {
        $input = '{"ok": true, "items": [1, 2, ], }';
        $this->assertSame(['ok' => true, 'items' => [1, 2]], AiJson::decode($input));
    }

    public function test_utf8_bom(): void
    {
        $input = "\xEF\xBB\xBF{\"ok\": true}";
        $this->assertSame(['ok' => true], AiJson::decode($input));
    }

    public function test_python_dictionary_style(): void
    {
        $input = "{'ok': True, 'name': 'test'}";
        $this->assertSame(['ok' => true, 'name' => 'test'], AiJson::decode($input));
    }

    public function test_null_or_empty_returns_null(): void
    {
        $this->assertNull(AiJson::decode(null));
        $this->assertNull(AiJson::decode(''));
        $this->assertNull(AiJson::decode('   '));
        $this->assertNull(AiJson::decode('Bu tamamen metin, hiçbir JSON yok.'));
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Contracts\AiProvider;
use Illuminate\Support\Facades\Log;

/**
 * CMS İçerik ve Tasarım Yapay Zekâ Asistanı.
 *
 * Filament panelinde ve MCP araçlarında kullanılmak üzere;
 * Hero metinleri, duyuru bandı, SSS yanıtları, SEO meta açıklamaları
 * ve vurgu kartı metinlerini diaspora (yurtdışı Türk topluluğu) bağlamında üretir.
 */
class CmsAiAssistant
{
    public function __construct(private readonly AiProvider $ai) {}

    public function isConfigured(): bool
    {
        return $this->ai->isConfigured();
    }

    /**
     * Hero bölümü için etkili rozet, başlık, vurgu, alt başlık ve CTA butonları üretir.
     *
     * @return array{rozet: string, baslik: string, vurgu: string, alt_baslik: string, cta1_etiket: string, cta2_etiket: string}|null
     */
    public function generateHeroContent(string $topic): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $prompt = <<<PROMPT
Sen Nisoya (nisoya.com) platformunun baş metin yazarı ve büyüme uzmanısın.
Nisoya; Avrupa ve yurtdışındaki Türk toplumu için geliştirilmiş güvenli, ücretsiz Türkçe ilan ve esnaf pazaryeridir.

Aşağıdaki tema/odak doğrultusunda anasayfa Hero (manşet) alanı için etkileyici, samimi ve harekete geçirici Türkçe metinler üret.
Tema / Odak: "{$topic}"

İstenen JSON formatı:
{
  "rozet": "Kısa, dikkat çekici rozet metni (ör: '🌍 Avrupa\'daki Türkler İçin', maks 40 karakter)",
  "baslik": "Başlığın ilk satırı (ör: 'Aradığın Türk Esnaf ve Hizmet', maks 50 karakter)",
  "vurgu": "Renkli/vurgulu kelime grubu (ör: 'Artık Bir Tık Uzağında.', maks 40 karakter)",
  "alt_baslik": "Açıklayıcı alt başlık (ör: 'Nakliyeden tamire, matematikten hukuka binlerce Türkçe ilan.', maks 140 karakter)",
  "cta1_etiket": "Birincil buton etiketi (ör: 'Ücretsiz İlan Ver', maks 25 karakter)",
  "cta2_etiket": "İkincil buton etiketi (ör: 'Esnafları Keşfet', maks 25 karakter)"
}
Yanıtını SADECE geçerli bir JSON nesnesi olarak ver. Başka hiçbir metin veya açıklama ekleme.
PROMPT;

        $schema = [
            'type' => 'object',
            'properties' => [
                'rozet' => ['type' => 'string'],
                'baslik' => ['type' => 'string'],
                'vurgu' => ['type' => 'string'],
                'alt_baslik' => ['type' => 'string'],
                'cta1_etiket' => ['type' => 'string'],
                'cta2_etiket' => ['type' => 'string'],
            ],
            'required' => ['rozet', 'baslik', 'vurgu', 'alt_baslik', 'cta1_etiket', 'cta2_etiket'],
        ];

        try {
            $res = $this->ai->analyzeText($prompt, $schema, 20);

            if (is_array($res) && filled($res['baslik'] ?? null)) {
                return [
                    'rozet' => (string) ($res['rozet'] ?? '🌍 Yurt dışındaki Türkler için'),
                    'baslik' => (string) ($res['baslik'] ?? ''),
                    'vurgu' => (string) ($res['vurgu'] ?? ''),
                    'alt_baslik' => (string) ($res['alt_baslik'] ?? ''),
                    'cta1_etiket' => (string) ($res['cta1_etiket'] ?? 'Ücretsiz İlan Ver'),
                    'cta2_etiket' => (string) ($res['cta2_etiket'] ?? 'Esnafları Keşfet'),
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('CmsAiAssistant: Hero metin üretimi başarısız', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Duyuru bandı için tek satırlık vurucu metin, aksiyon butonu ve renk önerisi üretir.
     *
     * @return array{metin: string, link_metni: string, renk: string}|null
     */
    public function generateAnnouncement(string $topic): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $prompt = <<<PROMPT
Sen Nisoya (nisoya.com) platformu için duyuru bandı metni yazıyorsun.
Sitenin en üstünde ince tek satır olarak görünecektir.
Kullanıcı şu amaçla duyuru istiyor: "{$topic}"

İstenen JSON formatı:
{
  "metin": "Net, anlaşılır ve dikkat çeken duyuru cümlesi (maks 150 karakter)",
  "link_metni": "Buton metni (ör: 'İncele', 'Detaylar', 'Hemen Katıl', maks 20 karakter)",
  "renk": "Duyurunun önemine göre sadece biri: 'marka' (standart yeşil/kutlama/özellik), 'uyari' (amber/bilgilendirme), 'onemli' (kırmızı/kesinti/acil)"
}
Yanıtını SADECE geçerli bir JSON nesnesi olarak ver. Başka hiçbir metin veya açıklama ekleme.
PROMPT;

        $schema = [
            'type' => 'object',
            'properties' => [
                'metin' => ['type' => 'string'],
                'link_metni' => ['type' => 'string'],
                'renk' => ['type' => 'string', 'enum' => ['marka', 'uyari', 'onemli']],
            ],
            'required' => ['metin', 'link_metni', 'renk'],
        ];

        try {
            $res = $this->ai->analyzeText($prompt, $schema, 20);

            if (is_array($res) && filled($res['metin'] ?? null)) {
                $renk = (string) ($res['renk'] ?? 'marka');
                if (! in_array($renk, ['marka', 'uyari', 'onemli'], true)) {
                    $renk = 'marka';
                }

                return [
                    'metin' => (string) $res['metin'],
                    'link_metni' => (string) ($res['link_metni'] ?? 'İncele'),
                    'renk' => $renk,
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('CmsAiAssistant: Duyuru üretimi başarısız', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Sıkça Sorulan Sorular (SSS) için diaspora kullanıcılarına yönelik açık ve çözüm odaklı yanıt üretir.
     *
     * @return array{cevap: string}|null
     */
    public function generateFaqAnswer(string $question): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $prompt = <<<PROMPT
Sen Nisoya (nisoya.com) platformunun resmi destek ve topluluk asistanısın.
Nisoya; Avrupa'daki (özellikle Almanya, Fransa, Avusturya, Hollanda vb.) Türk diasporası için geliştirilmiş ücretsiz ilan ve hizmet platformudur.
Kullanıcılar tamamen ücretsiz ilan verebilir, Türkçe konuşan esnafları bulabilir, doğrudan mesajlaşabilir.

Şu soru için profesyonel, samimi, güven verici ve anlaşılır bir SSS cevabı yaz:
Soru: "{$question}"

İstenen JSON formatı:
{
  "cevap": "Net, açıklayıcı ve anlaşılır yanıt metni (maksimum 400 karakter)"
}
Yanıtını SADECE geçerli bir JSON nesnesi olarak ver. Başka hiçbir metin veya açıklama ekleme.
PROMPT;

        $schema = [
            'type' => 'object',
            'properties' => [
                'cevap' => ['type' => 'string'],
            ],
            'required' => ['cevap'],
        ];

        try {
            $res = $this->ai->analyzeText($prompt, $schema, 25);

            if (is_array($res) && filled($res['cevap'] ?? null)) {
                return [
                    'cevap' => (string) $res['cevap'],
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('CmsAiAssistant: SSS cevabı üretimi başarısız', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Bir sayfa başlığı veya konusu için SEO meta açıklaması üretir.
     *
     * @return array{meta_description: string}|null
     */
    public function generatePageSeo(string $title, ?string $context = null): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $contextText = filled($context) ? "Bağlam / Not: {$context}" : '';

        $prompt = <<<PROMPT
Sen Nisoya (nisoya.com) CMS platformu için SEO uzmanısın.
Aşağıdaki sayfa için Google ve sosyal medya arama sonuçlarında yüksek tıklama oranı sağlayacak 140-160 karakterlik ideal bir meta açıklaması (SEO description) yaz.

Sayfa Başlığı: "{$title}"
{$contextText}

İstenen JSON formatı:
{
  "meta_description": "140 ile 160 karakter arasında SEO meta açıklaması"
}
Yanıtını SADECE geçerli bir JSON nesnesi olarak ver. Başka hiçbir metin veya açıklama ekleme.
PROMPT;

        $schema = [
            'type' => 'object',
            'properties' => [
                'meta_description' => ['type' => 'string'],
            ],
            'required' => ['meta_description'],
        ];

        try {
            $res = $this->ai->analyzeText($prompt, $schema, 20);

            if (is_array($res) && filled($res['meta_description'] ?? null)) {
                return [
                    'meta_description' => (string) $res['meta_description'],
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('CmsAiAssistant: Sayfa SEO üretimi başarısız', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Anasayfada dönen büyük veya küçük vurgu kartları için başlık ve vurucu spot üretir.
     *
     * @return array{title: string, text: string}|null
     */
    public function generateHighlight(string $topic): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $prompt = <<<PROMPT
Sen Nisoya (nisoya.com) anasayfa vurgu kartı editörüsün.
Kullanıcının verdiği konuya uygun olarak, anasayfa kartında dönecek dikkat çekici bir başlık ve kısa spot metin üret.

Konu: "{$topic}"

İstenen JSON formatı:
{
  "title": "Vurucu, kısa kart başlığı (maksimum 45 karakter)",
  "text": "Merak uyandıran, harekete geçiren spot metin (maksimum 120 karakter)"
}
Yanıtını SADECE geçerli bir JSON nesnesi olarak ver. Başka hiçbir metin veya açıklama ekleme.
PROMPT;

        $schema = [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'text' => ['type' => 'string'],
            ],
            'required' => ['title', 'text'],
        ];

        try {
            $res = $this->ai->analyzeText($prompt, $schema, 20);

            if (is_array($res) && filled($res['title'] ?? null)) {
                return [
                    'title' => (string) $res['title'],
                    'text' => (string) ($res['text'] ?? ''),
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('CmsAiAssistant: Vurgu kartı üretimi başarısız', ['error' => $e->getMessage()]);
        }

        return null;
    }
}

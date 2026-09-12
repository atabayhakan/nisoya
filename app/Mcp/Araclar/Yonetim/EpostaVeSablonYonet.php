<?php

declare(strict_types=1);

namespace App\Mcp\Araclar\Yonetim;

use App\Services\Ai\SystemToolsAiAssistant;
use App\Support\MailTemplates;
use App\Support\Settings;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;

#[Name('nisoya_eposta_ve_sablon_yonet')]
#[Title('E-posta & Şablon Yönetimi — Bildirim şablonlarını listele, AI ile optimize et veya güncelle')]
#[Description(
    'Nisoya e-posta şablonlarını ve SMTP durumunu yönetir. '.
    'islem="sablonlari_listele" (4 ana e-posta şablonunu, aktif metinleri ve yer-tutucuları listeler), '.
    'islem="sablon_guncelle" (belirli bir şablon parçasını özelleştirir), '.
    'islem="ai_sablon_iyilestir" (yer-tutucuları koruyarak metni profesyonel/samimi tonda yapay zekâya optimize ettirir), '.
    'islem="smtp_durumu" (SMTP sağlayıcı ayarlarının güvenli özetini verir).'
)]
class EpostaVeSablonYonet extends YonetimAraci
{
    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'islem' => $schema->string()
                ->description('İşlem türü: "sablonlari_listele", "sablon_guncelle", "ai_sablon_iyilestir", "smtp_durumu".')
                ->required(),
            'sablon_anahtari' => $schema->string()
                ->description('Şablon adı: "yeni_mesaj", "destek_yaniti", "kayitli_arama", "gunluk_rapor".'),
            'parca' => $schema->string()
                ->description('Şablon parçası: "subject", "greeting", "intro", "action", "outro".'),
            'yeni_metin' => $schema->string()
                ->description('Kaydedilecek yeni şablon metni (boş bırakılırsa varsayılana sıfırlanır).'),
            'ton' => $schema->string()
                ->description('AI optimizasyon tonu: "profesyonel", "samimi", "resmi", "canli" (varsayılan: profesyonel).'),
            'otomatik_kaydet' => $schema->boolean()
                ->description('ai_sablon_iyilestir sonucunu otomatik olarak DB ayarına kaydet (varsayılan: false).'),
        ];
    }

    /** @return array<string, mixed> */
    protected function calistir(Request $request): array
    {
        $islem = strtolower((string) $request->get('islem', 'sablonlari_listele'));

        return match ($islem) {
            'sablon_guncelle' => $this->sablonGuncelle($request),
            'ai_sablon_iyilestir' => $this->aiSablonIyilestir($request),
            'smtp_durumu' => $this->smtpDurumu(),
            default => $this->sablonlariListele(),
        };
    }

    /** @return array<string, mixed> */
    private function sablonlariListele(): array
    {
        $list = [];

        foreach (MailTemplates::TEMPLATES as $key => $tpl) {
            $parts = [];
            foreach (MailTemplates::PART_LABELS as $part => $label) {
                $default = $tpl['parts'][$part];
                $override = Settings::get("mail_template.{$key}.{$part}");
                $parts[$part] = [
                    'etiket' => $label,
                    'etkin_metin' => ! empty($override) ? $override : $default,
                    'ozellestirilmis_mi' => ! empty($override),
                    'varsayilan_metin' => $default,
                ];
            }

            $list[$key] = [
                'etiket' => $tpl['label'],
                'yer_tutucular' => $tpl['placeholders'],
                'parcalar' => $parts,
            ];
        }

        return [
            'durum' => 'basarili',
            'toplam_sablon' => count($list),
            'sablonlar' => $list,
        ];
    }

    /** @return array<string, mixed> */
    private function sablonGuncelle(Request $request): array
    {
        $key = (string) $request->get('sablon_anahtari');
        $part = (string) $request->get('parca');
        $yeniMetin = (string) $request->get('yeni_metin', '');

        if (! isset(MailTemplates::TEMPLATES[$key])) {
            return [
                'durum' => 'hata',
                'mesaj' => "Geçersiz şablon anahtarı: {$key}. Geçerli şablonlar: ".implode(', ', array_keys(MailTemplates::TEMPLATES)),
            ];
        }

        if (! isset(MailTemplates::PART_LABELS[$part])) {
            return [
                'durum' => 'hata',
                'mesaj' => "Geçersiz şablon parçası: {$part}. Geçerli parçalar: ".implode(', ', array_keys(MailTemplates::PART_LABELS)),
            ];
        }

        Settings::set("mail_template.{$key}.{$part}", $yeniMetin);

        return [
            'durum' => 'basarili',
            'mesaj' => $yeniMetin === '' ? 'Şablon parçası varsayılana sıfırlandı.' : 'Şablon parçası güncellendi.',
            'sablon' => $key,
            'parca' => $part,
            'etkin_metin' => $yeniMetin !== '' ? $yeniMetin : MailTemplates::TEMPLATES[$key]['parts'][$part],
        ];
    }

    /** @return array<string, mixed> */
    private function aiSablonIyilestir(Request $request): array
    {
        $key = (string) $request->get('sablon_anahtari');
        $part = (string) $request->get('parca');
        $ton = (string) $request->get('ton', 'profesyonel');
        $otomatikKaydet = (bool) $request->get('otomatik_kaydet', false);

        if (! isset(MailTemplates::TEMPLATES[$key])) {
            return [
                'durum' => 'hata',
                'mesaj' => "Geçersiz şablon anahtarı: {$key}.",
            ];
        }

        if (! isset(MailTemplates::PART_LABELS[$part])) {
            return [
                'durum' => 'hata',
                'mesaj' => "Geçersiz şablon parçası: {$part}.",
            ];
        }

        $currentText = Settings::get("mail_template.{$key}.{$part}") ?: MailTemplates::TEMPLATES[$key]['parts'][$part];

        $assistant = app(SystemToolsAiAssistant::class);
        $result = $assistant->optimizeEmailTemplate($key, $part, $currentText, $ton);

        if ($otomatikKaydet && $result['placeholders_preserved']) {
            Settings::set("mail_template.{$key}.{$part}", $result['optimized_text']);
        }

        return [
            'durum' => 'basarili',
            'sablon' => $key,
            'parca' => $part,
            'onceki_metin' => $currentText,
            'optimize_edilen_metin' => $result['optimized_text'],
            'aciklama' => $result['explanation'],
            'yer_tutucular_korundu' => $result['placeholders_preserved'],
            'otomatik_kaydedildi' => $otomatikKaydet,
        ];
    }

    /** @return array<string, mixed> */
    private function smtpDurumu(): array
    {
        $host = Settings::get('mail.host') ?: config('mail.mailers.smtp.host');
        $port = Settings::get('mail.port') ?: config('mail.mailers.smtp.port');
        $encryption = Settings::get('mail.encryption') ?: config('mail.mailers.smtp.scheme');
        $username = Settings::get('mail.username') ?: config('mail.mailers.smtp.username');
        $fromAddress = Settings::get('mail.from_address') ?: config('mail.from.address');
        $fromName = Settings::get('mail.from_name') ?: config('mail.from.name');
        $hasPassword = ! empty(Settings::get('mail.password') ?: config('mail.mailers.smtp.password'));

        return [
            'durum' => 'basarili',
            'smtp_yapilandirilmis' => ! empty($host) && ! empty($username) && $hasPassword,
            'ayarlar' => [
                'host' => $host,
                'port' => $port,
                'encryption' => $encryption,
                'username' => $username,
                'from_address' => $fromAddress,
                'from_name' => $fromName,
                'parola_tanimli_mi' => $hasPassword,
            ],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Mcp\Araclar\Yonetim;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Services\Ai\SystemToolsAiAssistant;
use App\Services\BackupService;
use App\Support\HataKayitlari;
use App\Support\Modules;
use App\Support\Settings;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;

#[Name('nisoya_sistem_saglik_ve_hatalar')]
#[Title('Sistem Sağlık & Hata Teşhisi — Sistem sağlığı, hata logları ve felaket kurtarma denetimi')]
#[Description(
    'Nisoya platformunun teknik sağlık durumunu, en son sistem hatalarını ve kurtarma dayanıklılığını denetler. '.
    'islem="saglik_ozeti" (yönetici sayısı, 2FA, yedekler, modüller ve AI dayanıklılık skorunu sunar), '.
    'islem="hatalari_listele" (storage/logs altındaki son hataları filtreleyip döner), '.
    'islem="ai_hata_teshis" (belirli bir hatayı veya en son hatayı yapay zekâ ile analiz edip kök neden ve çözüm önerisi üretir).'
)]
class SistemSaglikVeHatalar extends YonetimAraci
{
    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'islem' => $schema->string()
                ->description('İşlem türü: "saglik_ozeti", "hatalari_listele", "ai_hata_teshis".')
                ->required(),
            'limit' => $schema->integer()
                ->description('Listelenecek hata sayısı (varsayılan: 10).'),
            'hata_index' => $schema->integer()
                ->description('ai_hata_teshis için incelenecek hata sırası (0 = en son hata).'),
            'hata_mesaji' => $schema->string()
                ->description('ai_hata_teshis için özel hata mesajı (varsa).'),
            'hata_sinifi' => $schema->string()
                ->description('ai_hata_teshis için istisna sınıfı (örn: QueryException).'),
            'dosya_satir' => $schema->string()
                ->description('ai_hata_teshis için dosya ve satır bilgisi.'),
        ];
    }

    /** @return array<string, mixed> */
    protected function calistir(Request $request): array
    {
        $islem = strtolower((string) $request->get('islem', 'saglik_ozeti'));

        return match ($islem) {
            'hatalari_listele' => $this->hatalariListele($request),
            'ai_hata_teshis' => $this->aiHataTeshis($request),
            default => $this->saglikOzeti(),
        };
    }

    /** @return array<string, mixed> */
    private function saglikOzeti(): array
    {
        $admins = User::query()->where('role', UserRole::Admin)->where('status', UserStatus::Aktif)->count();
        $twoFa = User::query()->where('role', UserRole::Admin)->where('status', UserStatus::Aktif)->whereNotNull('two_factor_confirmed_at')->count();
        $user = auth()->user() ?? User::query()->where('role', UserRole::Admin)->first();
        $remainingCodes = $user?->accountRecoveryCodesRemaining() ?? 0;
        $smtp = ! empty(Settings::get('mail.host') ?: config('mail.mailers.smtp.host'));

        $assistant = app(SystemToolsAiAssistant::class);
        $audit = $assistant->generateRecoveryAudit($admins, $twoFa, $remainingCodes, $smtp);

        $hataKayitlari = app(HataKayitlari::class);
        $sonHatalar = $hataKayitlari->sonHatalar(5);
        $backups = app(BackupService::class)->list();

        $modulesSummary = [];
        foreach (Modules::KEYS as $key) {
            $modulesSummary[$key] = [
                'etiket' => Modules::LABELS[$key],
                'aktif' => Modules::enabled($key),
            ];
        }

        return [
            'durum' => 'basarili',
            'sistem_sagligi' => [
                'guvenlik_skoru' => $audit['score'],
                'risk_seviyesi' => $audit['level'],
                'ozet' => $audit['summary'],
                'riskler' => $audit['risks'],
                'oncelikli_eylemler' => $audit['action_plan'],
            ],
            'yonetici_ve_guvenlik' => [
                'aktif_admin_sayisi' => $admins,
                'iki_faktorlu_admin_sayisi' => $twoFa,
                'kalan_kurtarma_kodlari' => $remainingCodes,
                'smtp_yapilandirilmis' => $smtp,
            ],
            'yedekleme' => [
                'toplam_yedek' => count($backups),
                'en_son_yedek' => $backups[0]['name'] ?? null,
            ],
            'hata_durumu' => [
                'log_tutuluyor' => $hataKayitlari->kayitTutuluyorMu(),
                'son_hata_sayisi' => count($sonHatalar),
                'en_son_hata' => $sonHatalar[0]['mesaj'] ?? null,
            ],
            'moduller' => $modulesSummary,
        ];
    }

    /** @return array<string, mixed> */
    private function hatalariListele(Request $request): array
    {
        $limit = max(1, min(50, (int) $request->get('limit', 10)));
        $hataKayitlari = app(HataKayitlari::class);
        $hatalar = $hataKayitlari->sonHatalar($limit);

        return [
            'durum' => 'basarili',
            'okunan_dosyalar' => array_map('basename', $hataKayitlari->dosyalar()),
            'kayit_tutuluyor' => $hataKayitlari->kayitTutuluyorMu(),
            'toplam_bulunan' => count($hatalar),
            'hatalar' => $hatalar,
        ];
    }

    /** @return array<string, mixed> */
    private function aiHataTeshis(Request $request): array
    {
        $hataMesaji = (string) $request->get('hata_mesaji', '');
        $hataSinifi = (string) $request->get('hata_sinifi', '');
        $dosyaSatir = (string) $request->get('dosya_satir', '');

        if ($hataMesaji === '') {
            $index = (int) $request->get('hata_index', 0);
            $hataKayitlari = app(HataKayitlari::class);
            $hatalar = $hataKayitlari->sonHatalar($index + 1);
            $hata = $hatalar[$index] ?? null;

            if (! $hata) {
                return [
                    'durum' => 'hata',
                    'mesaj' => 'İncelenecek sistem hatası bulunamadı. Loglar temiz olabilir.',
                ];
            }

            $hataMesaji = $hata['mesaj'];
            $hataSinifi = $hata['sinif'];
            $dosyaSatir = $hata['yer'] ?? '';
        }

        $assistant = app(SystemToolsAiAssistant::class);
        $teshis = $assistant->diagnoseSystemError($hataMesaji, $hataSinifi, $dosyaSatir);

        return [
            'durum' => 'basarili',
            'incelenen_hata' => [
                'mesaj' => $hataMesaji,
                'sinif' => $hataSinifi,
                'yer' => $dosyaSatir,
            ],
            'ai_teshisi' => $teshis,
        ];
    }
}

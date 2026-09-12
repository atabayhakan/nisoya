<?php

declare(strict_types=1);

namespace App\Mcp\Araclar\Yonetim;

use App\Enums\ListingStatus;
use App\Models\Listing;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;

#[Name('nisoya_ilan_durum_guncelle')]
#[Title('İlan Durumu Güncelle — Onayla, reddet veya yayından kaldır')]
#[Description(
    'Belirtilen ilanın durumunu günceller. Kullanım: '.
    'İlanı onaylamak için yeni_durum="aktif", reddetmek için yeni_durum="reddedildi", '.
    'incelemeye almak için yeni_durum="beklemede". Gerekçe belirtilebilir.'
)]
class IlanDurumuGuncelle extends YonetimAraci
{
    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ilan_id' => $schema->integer()
                ->description('Durumu güncellenecek ilanın ID numarası.')
                ->required(),
            'yeni_durum' => $schema->string()
                ->description('Hedef durum: aktif, reddedildi, pasif, beklemede.')
                ->required(),
            'gerekce' => $schema->string()
                ->description('Moderasyon veya durum değişikliği gerekçesi (ör. "Uygunsuz içerik temizlendi", "Spam tespit edildi").'),
        ];
    }

    /** @return array<string, mixed> */
    protected function calistir(Request $request): array
    {
        $id = (int) $request->get('ilan_id');
        $yeniDurumStr = strtolower((string) $request->get('yeni_durum'));
        $gerekce = (string) $request->get('gerekce', 'MCP yöneticisi tarafından güncellendi');

        $ilan = Listing::find($id);
        if (! $ilan) {
            return [
                'basarili' => false,
                'mesaj' => "ID'si {$id} olan ilan bulunamadı.",
            ];
        }

        $yeniStatus = match ($yeniDurumStr) {
            'aktif', 'active' => ListingStatus::Aktif,
            'reddedildi', 'rejected' => ListingStatus::Reddedildi,
            'pasif', 'passive' => ListingStatus::Pasif,
            'beklemede', 'pending' => ListingStatus::Beklemede,
            'taslak', 'draft' => ListingStatus::Taslak,
            default => null,
        };

        if ($yeniStatus === null) {
            return [
                'basarili' => false,
                'mesaj' => "Geçersiz durum: '{$yeniDurumStr}'. Geçerli değerler: aktif, reddedildi, pasif, beklemede, taslak.",
            ];
        }

        $eskiDurum = $ilan->status->getLabel();
        $ilan->status = $yeniStatus;

        if ($yeniStatus === ListingStatus::Reddedildi && $gerekce !== '') {
            $ilan->fraud_reason = $gerekce;
        } elseif ($yeniStatus === ListingStatus::Aktif) {
            $ilan->fraud_reason = null; // Onaylandığında şüpheyi temizle
        }

        $ilan->save();

        Log::info('Nisoya MCP: İlan durumu güncellendi', [
            'ilan_id' => $ilan->id,
            'eski_durum' => $eskiDurum,
            'yeni_durum' => $yeniStatus->getLabel(),
            'gerekce' => $gerekce,
        ]);

        return [
            'basarili' => true,
            'ilan_id' => $ilan->id,
            'baslik' => $ilan->title,
            'onceki_durum' => $eskiDurum,
            'guncel_durum' => $yeniStatus->getLabel(),
            'guncel_durum_kodu' => $yeniStatus->value,
            'gerekce' => $gerekce,
            'mesaj' => "İlan durumu başarıyla '{$yeniStatus->getLabel()}' olarak güncellendi.",
        ];
    }
}

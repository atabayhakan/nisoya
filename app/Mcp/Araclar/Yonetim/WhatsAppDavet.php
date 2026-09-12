<?php

declare(strict_types=1);

namespace App\Mcp\Araclar\Yonetim;

use App\Models\Listing;
use App\Models\OutreachTarget;
use App\Services\Growth\WhatsAppDavetServisi;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;

#[Name('nisoya_whatsapp_davet_onizle')]
#[Title('WhatsApp Davet Önizleme — Esnaf için vitrin sahiplenme linki')]
#[Description(
    'Keşfedilen bir işletme (OutreachTarget) veya sahipsiz vitrin ilanı (Listing) için '.
    'kişiselleştirilmiş Türkçe WhatsApp davet mesajı ve tek tıkla gönderim bağlantısı (wa.me) üretir.'
)]
class WhatsAppDavet extends YonetimAraci
{
    public function __construct(
        private readonly WhatsAppDavetServisi $davetServisi
    ) {}

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'isletme_id' => $schema->integer()
                ->description('Keşif hedefi (OutreachTarget) ID numarası.'),
            'ilan_id' => $schema->integer()
                ->description('Alternatif olarak vitrin ilanı (Listing) ID numarası.'),
        ];
    }

    /** @return array<string, mixed> */
    protected function calistir(Request $request): array
    {
        $isletmeId = $request->get('isletme_id');
        $ilanId = $request->get('ilan_id');

        if ($isletmeId) {
            $aday = OutreachTarget::with('listing')->find((int) $isletmeId);
            if (! $aday) {
                return ['hata' => "ID'si {$isletmeId} olan işletme hedefi bulunamadı."];
            }

            $waUrl = $this->davetServisi->adayIcinUrl($aday);
            $listing = $aday->listing;
            $listingUrl = $listing ? route('listings.show', [$listing->id, $listing->slug]) : url('/ilanlar');
            $claimUrl = ($listing && $listing->isClaimable()) ? url('/sahiplen/'.$listing->claim_token) : url('/panel/ilan/yeni');
            $mesajMetni = $this->davetServisi->mesaj($aday->name, $aday->city ?? 'Avrupa', $listingUrl, $claimUrl);

            return [
                'isletme_id' => $aday->id,
                'isletme_adi' => $aday->name,
                'sehir' => $aday->city,
                'vitrin_ilan_url' => $listingUrl,
                'sahiplenme_linki' => $claimUrl,
                'whatsapp_linki' => $waUrl,
                'davet_mesaj_metni' => $mesajMetni,
            ];
        }

        if ($ilanId) {
            $listing = Listing::find((int) $ilanId);
            if (! $listing) {
                return ['hata' => "ID'si {$ilanId} olan ilan bulunamadı."];
            }

            $waUrl = $this->davetServisi->listingIcinUrl($listing);
            $listingUrl = route('listings.show', [$listing->id, $listing->slug]);
            $claimUrl = $listing->isClaimable() ? url('/sahiplen/'.$listing->claim_token) : url('/panel/ilan/yeni');
            $mesajMetni = $this->davetServisi->mesaj($listing->title, $listing->city ?? 'Avrupa', $listingUrl, $claimUrl);

            return [
                'ilan_id' => $listing->id,
                'isletme_adi' => $listing->title,
                'sehir' => $listing->city,
                'vitrin_ilan_url' => $listingUrl,
                'sahiplenme_linki' => $claimUrl,
                'whatsapp_linki' => $waUrl,
                'davet_mesaj_metni' => $mesajMetni,
            ];
        }

        return ['hata' => 'Lütfen isletme_id veya ilan_id parametrelerinden en az birini belirtin.'];
    }
}

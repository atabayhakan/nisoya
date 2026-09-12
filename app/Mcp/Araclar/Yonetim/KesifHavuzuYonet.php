<?php

declare(strict_types=1);

namespace App\Mcp\Araclar\Yonetim;

use App\Models\OutreachTarget;
use App\Services\Ai\GrowthMarketingAiAssistant;
use App\Services\Growth\ClaimableListingCreator;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;

#[Name('nisoya_kesif_havuzu_yonet')]
#[Title('Keşif Havuzu & Tersine Katılım — Aday esnaf inceleme, kültürel analiz ve vitrin açma')]
#[Description(
    'Büyüme Ajanı tarafından haritadan taranan Türk esnaf adaylarını (Keşif Havuzu) yönetir. '.
    'islem="adaylari_listele" (adayları listeler; sadece_inceleme_bekleyen, sonuc, ulke filtreleri desteklenir), '.
    'islem="kulturel_analiz" (aday_id ile adayın Türk olup olmadığını AI kültürel analiziyle değerlendirir), '.
    'islem="karar_ver" (aday_id ve karar="onayla" | "reddet" ile adayı onaylar veya eler), '.
    'islem="vitrin_uret" (aday_id için otomatik sahiplenilebilir vitrin ilanı açar ve /sahiplen bağlantısı üretir), '.
    'islem="davet_olustur" (aday_id için kişiselleştirilmiş Türkçe WhatsApp / e-posta daveti hazırlar).'
)]
class KesifHavuzuYonet extends YonetimAraci
{
    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'islem' => $schema->string()
                ->description('İşlem türü: "adaylari_listele", "kulturel_analiz", "karar_ver", "vitrin_uret", "davet_olustur".')
                ->required(),
            'aday_id' => $schema->integer()
                ->description('İşlem yapılacak aday (OutreachTarget) ID numarası.'),
            'sadece_inceleme_bekleyen' => $schema->boolean()
                ->description('adaylari_listele için sadece inceleme bekleyenleri filtrele.'),
            'sonuc' => $schema->string()
                ->description('adaylari_listele için sonuç filtresi: "turkish" (Türk), "ambiguous" (Sınırda).'),
            'ulke' => $schema->string()
                ->description('adaylari_listele için ülke kodu (örn: DE, AT, FR).'),
            'limit' => $schema->integer()
                ->description('Listelenecek maksimum aday sayısı (varsayılan: 20).'),
            'karar' => $schema->string()
                ->description('karar_ver işlemi için karar: "onayla" veya "reddet".'),
        ];
    }

    /** @return array<string, mixed> */
    protected function calistir(Request $request): array
    {
        $islem = strtolower((string) $request->get('islem', 'adaylari_listele'));

        return match ($islem) {
            'kulturel_analiz' => $this->kulturelAnaliz($request),
            'karar_ver' => $this->kararVer($request),
            'vitrin_uret' => $this->vitrinUret($request),
            'davet_olustur' => $this->davetOlustur($request),
            default => $this->adaylariListele($request),
        };
    }

    /** @return array<string, mixed> */
    private function adaylariListele(Request $request): array
    {
        $query = OutreachTarget::query()->with('listing');

        if ($request->get('sadece_inceleme_bekleyen', false)) {
            $query->where('needs_review', true);
        }

        if ($sonuc = $request->get('sonuc')) {
            $query->where('detection_band', (string) $sonuc);
        }

        if ($ulke = $request->get('ulke')) {
            $query->where('country', strtoupper((string) $ulke));
        }

        $limit = min(50, max(1, (int) $request->get('limit', 20)));
        $total = $query->count();
        $items = $query->latest('id')->limit($limit)->get();

        $rows = $items->map(fn (OutreachTarget $t) => [
            'id' => $t->id,
            'name' => $t->name,
            'city' => $t->city,
            'country' => $t->country,
            'sector' => $t->sector,
            'detection_band' => $t->detection_band,
            'detection_confidence' => $t->detection_confidence,
            'needs_review' => $t->needs_review,
            'status' => $t->status,
            'has_listing' => $t->listing_id !== null,
            'listing_id' => $t->listing_id,
            'claim_url' => $t->listing && $t->listing->isClaimable() ? url('/sahiplen/'.$t->listing->claim_token) : null,
        ])->all();

        return [
            'durum' => 'basarili',
            'toplam_aday' => $total,
            'listelenen_adet' => count($rows),
            'adaylar' => $rows,
        ];
    }

    /** @return array<string, mixed> */
    private function kulturelAnaliz(Request $request): array
    {
        $id = (int) $request->get('aday_id');
        $target = OutreachTarget::find($id);

        if (! $target) {
            return ['durum' => 'hata', 'mesaj' => "ID'si {$id} olan aday bulunamadı."];
        }

        $assistant = app(GrowthMarketingAiAssistant::class);
        $analiz = $assistant->classifyTargetCulture([
            'name' => (string) $target->name,
            'city' => (string) $target->city,
            'country' => (string) $target->country,
            'sector' => (string) $target->sector,
            'signals' => (array) ($target->detection_signals ?? []),
        ]);

        return [
            'durum' => 'basarili',
            'aday_id' => $target->id,
            'isletme_adi' => $target->name,
            'sehir' => $target->city,
            'ulke' => $target->country,
            'turk_mu' => $analiz['is_turkish'],
            'guven_puani' => $analiz['confidence'],
            'kulturel_isaretler' => $analiz['cultural_indicators'],
            'gerekce' => $analiz['reasoning'],
            'oneri' => $analiz['recommendation'],
        ];
    }

    /** @return array<string, mixed> */
    private function kararVer(Request $request): array
    {
        $id = (int) $request->get('aday_id');
        $target = OutreachTarget::find($id);

        if (! $target) {
            return ['durum' => 'hata', 'mesaj' => "ID'si {$id} olan aday bulunamadı."];
        }

        $karar = strtolower((string) $request->get('karar', 'onayla'));

        if ($karar === 'onayla') {
            $target->update([
                'needs_review' => false,
                'status' => 'onayli',
                'detection_band' => 'turkish',
            ]);

            return [
                'durum' => 'basarili',
                'mesaj' => "Aday {$target->name} onaylandı ve Türk esnafı olarak işaretlendi.",
                'aday_id' => $target->id,
                'yeni_durum' => 'onayli',
            ];
        }

        $target->update([
            'needs_review' => false,
            'status' => 'reddedildi',
            'detection_band' => 'not_turkish',
        ]);

        return [
            'durum' => 'basarili',
            'mesaj' => "Aday {$target->name} elendi/reddedildi.",
            'aday_id' => $target->id,
            'yeni_durum' => 'reddedildi',
        ];
    }

    /** @return array<string, mixed> */
    private function vitrinUret(Request $request): array
    {
        $id = (int) $request->get('aday_id');
        $target = OutreachTarget::with('listing')->find($id);

        if (! $target) {
            return ['durum' => 'hata', 'mesaj' => "ID'si {$id} olan aday bulunamadı."];
        }

        if ($target->listing !== null) {
            return [
                'durum' => 'bilgi',
                'mesaj' => 'Bu aday için zaten bir vitrin mevcut.',
                'listing_id' => $target->listing->id,
                'claim_url' => url('/sahiplen/'.$target->listing->claim_token),
            ];
        }

        $creator = app(ClaimableListingCreator::class);
        $res = $creator->createFromTarget($target);
        $target->refresh();

        return [
            'durum' => 'basarili',
            'mesaj' => "{$target->name} için sahiplenilebilir vitrin başarıyla oluşturuldu.",
            'listing_id' => $res['listing']->id,
            'claim_url' => $res['claim_url'],
            'claim_token' => $res['claim_token'],
        ];
    }

    /** @return array<string, mixed> */
    private function davetOlustur(Request $request): array
    {
        $id = (int) $request->get('aday_id');
        $target = OutreachTarget::with('listing')->find($id);

        if (! $target) {
            return ['durum' => 'hata', 'mesaj' => "ID'si {$id} olan aday bulunamadı."];
        }

        $claimUrl = $target->listing && $target->listing->isClaimable()
            ? url('/sahiplen/'.$target->listing->claim_token)
            : url('/sahiplen');

        $assistant = app(GrowthMarketingAiAssistant::class);
        $davet = $assistant->draftPersonalizedOutreach([
            'name' => (string) $target->name,
            'city' => (string) $target->city,
            'country' => (string) $target->country,
            'sector' => (string) $target->sector,
        ], $claimUrl);

        $phone = $target->listing?->claim_phone ?: ($target->detection_signals['phone'] ?? null);
        $cleanPhone = $phone ? preg_replace('/[^\d+]/', '', (string) $phone) : '';
        $waLink = $cleanPhone
            ? 'https://wa.me/'.ltrim((string) $cleanPhone, '+').'?text='.rawurlencode($davet['whatsapp_message'])
            : 'https://wa.me/?text='.rawurlencode($davet['whatsapp_message']);

        return [
            'durum' => 'basarili',
            'aday_id' => $target->id,
            'isletme_adi' => $target->name,
            'claim_url' => $claimUrl,
            'whatsapp_mesaji' => $davet['whatsapp_message'],
            'whatsapp_linki' => $waLink,
            'eposta_konusu' => $davet['email_subject'],
            'eposta_govdesi' => $davet['email_body'],
            'kisisel_dokunus' => $davet['personal_touch'],
        ];
    }
}

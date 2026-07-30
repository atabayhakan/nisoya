<?php

namespace App\Services\Kahya\Eylem\Eylemler;

use App\Enums\EylemRiski;
use App\Models\BekleyenHamle;
use App\Services\Kahya\Eylem\Eylem;

/**
 * Dışa dönük bir hamleyi sahibin onay kuyruğuna kart olarak bırakır (F2).
 *
 * TASARIM FREKANSININ TEK ONAY KAPISI BU KARTLARDIR (tasarım §2.2):
 * gönderilmiş e-posta, harcanmış para ve dış itibar yedekten geri
 * yüklenemez — o yüzden sistemden dışarı çıkacak her şey önce kart olur,
 * sahip karar verir. Bu eylemin kendisi DÜŞÜK risklidir çünkü hiçbir şeyi
 * göndermez; yalnız öneriyi deftere yazar.
 *
 * F2 SINIRI: onaylanan hamle henüz otomatik UYGULANMAZ (gönderim altyapısı
 * F4'te). Onay/ret yine de kıymetli: sahip karar verir, Kâhya kararı sonraki
 * turda görür, F5 ders-cikar bu kararlardan öğrenir.
 */
class HamleOner extends Eylem
{
    public function ad(): string
    {
        return 'hamle-oner';
    }

    public function baslik(): string
    {
        return 'Hamle öner';
    }

    public function aciklama(): string
    {
        return 'Sistemden DIŞARI çıkacak bir işi (tanıtım e-postası, sosyal medya içeriği, '
            .'bir topluluğa/derneğe ulaşma teklifi...) sahibin onay kuyruğuna kart olarak '
            .'bırakır. İçeriği (ör. taslak mesajı) SEN yaz — kart, sahibin okuyup Onayla/'
            .'Reddet diyeceği bitmiş bir öneri olmalı, yarım fikir değil. Dışa dönük '
            .'HİÇBİR işi kendin yapamazsın; tek yolun bu kart. Onay kararı panelden '
            .'verilir; şu an onaylanan hamleyi sahip elle uygular (otomatik gönderim '
            .'sonraki fazda).';
    }

    public function sema(): array
    {
        return [
            'baslik' => 'Hamlenin kısa adı; eposta türünde KONU satırı olarak da gider. Ör. "TUSU\'ya tanıtım mesajı".',
            'gerekce' => 'Neden bu hamle, neden şimdi — sahibin karar cümlesi.',
            'icerik' => 'Hamlenin kendisi: gönderilecek taslak metin / yapılacak işin tam tarifi.',
            'tur' => 'İsteğe bağlı: eposta, sosyal ya da oneri (varsayılan).',
            'alici_eposta' => 'eposta türünde ZORUNLU: mesajın gideceği adres (kurumun herkese '
                .'açık iletişim adresi). Adresi UYDURMA — web-ara/isletme-kesfet ile bulduğun '
                .'gerçek adresi yaz; bulamadıysan tur=oneri bırak ve gerekçede söyle.',
            'gorev_id' => 'İsteğe bağlı: hamle bir görevin parçasıysa görevin kimliği.',
        ];
    }

    public function kurallar(): array
    {
        return [
            'baslik' => ['required', 'string', 'min:5', 'max:150'],
            'gerekce' => ['required', 'string', 'min:10', 'max:500'],
            'icerik' => ['required', 'string', 'min:20', 'max:5000'],
            'tur' => ['nullable', 'string', 'in:oneri,eposta,sosyal'],
            'alici_eposta' => ['nullable', 'email:rfc', 'max:190', 'required_if:tur,eposta'],
            'gorev_id' => ['nullable', 'integer', 'exists:kahya_gorevleri,id'],
        ];
    }

    public function risk(): EylemRiski
    {
        // Kart yazmak iç iştir; dışarı hiçbir şey gitmez. Asıl kapı kartın
        // kendisinde — onay panelde sahibindir.
        return EylemRiski::Dusuk;
    }

    public function onizleme(array $p): string
    {
        return "Onay kuyruğuna hamle kartı: \"{$p['baslik']}\" — {$p['gerekce']}";
    }

    public function uygula(array $p): array
    {
        $hamle = BekleyenHamle::create([
            'kahya_gorevi_id' => $p['gorev_id'] ?? null,
            'baslik' => (string) $p['baslik'],
            'gerekce' => (string) $p['gerekce'],
            'icerik' => (string) $p['icerik'],
            'tur' => (string) ($p['tur'] ?? 'oneri'),
            'alici_eposta' => isset($p['alici_eposta']) ? mb_strtolower(trim((string) $p['alici_eposta'])) : null,
        ]);

        return [
            'sonuc' => "\"{$hamle->baslik}\" hamle kartı onay kuyruğuna bırakıldı (#{$hamle->id}). "
                .'Sahip panelden (Kâhya & Yapay Zekâ → Bekleyen Hamleler) karar verecek.',
            'geri_alma' => ['id' => $hamle->id],
        ];
    }

    public function geriAl(array $iz): string
    {
        $hamle = BekleyenHamle::query()->find($iz['id'] ?? 0);

        if ($hamle === null) {
            return 'Hamle kartı zaten yok.';
        }

        /*
         * KARAR VERİLMİŞSE GERİ ALINMAZ: sahibin verdiği kararı silmek,
         * denetim defterinden karar silmektir. Öneriyi geri çekmek yalnız
         * karar HENÜZ verilmemişken mümkün.
         */
        if ($hamle->durum !== BekleyenHamle::DURUM_BEKLEMEDE) {
            return "Geri alınamadı: sahip bu hamle için kararını vermiş ({$hamle->durum}).";
        }

        $baslik = $hamle->baslik;
        $hamle->delete();

        return "\"{$baslik}\" hamle kartı geri çekildi.";
    }

    public function ornekler(): array
    {
        return [
            'TUSU\'ya tanıtım mesajı taslağı hazırla ve onayıma sun',
            'Dubai Türk Rehberi\'ne işbirliği teklifi kartı bırak',
        ];
    }
}

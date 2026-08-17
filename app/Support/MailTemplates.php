<?php

namespace App\Support;

/**
 * Düzenlenebilir e-posta metinleri (Faz 3 · G6).
 *
 * En çok görünen kullanıcı-yüzlü maillerin STATİK parçalarını (konu, selamlama,
 * giriş, buton etiketi, kapanış) sahibin panelden değiştirebilmesi. Dinamik
 * içerik (mesaj alıntısı, ilan listesi) kodda kalır. Yer-tutucular ({ad},
 * {gonderen}, {arama}, {sayi}) gönderim anında gerçek değerle değişir.
 *
 * Override yoksa registry'deki varsayılan kullanılır (DB > kod). Admin sayfası:
 * Site Yönetimi → E-posta Metinleri.
 */
class MailTemplates
{
    /**
     * @var array<string,array{label:string,placeholders:array<string,string>,parts:array<string,string>}>
     */
    public const TEMPLATES = [
        'yeni_mesaj' => [
            'label' => 'Yeni mesaj bildirimi',
            'placeholders' => [
                '{ad}' => 'alıcının adı',
                '{gonderen}' => 'gönderenin adı',
            ],
            'parts' => [
                'subject' => 'Nisoya: {gonderen} sana mesaj gönderdi',
                'greeting' => 'Merhaba {ad},',
                'intro' => '{gonderen} sana yeni bir mesaj gönderdi:',
                'action' => 'Mesajı görüntüle',
                'outro' => 'Nisoya — Ne İş Olursa Yaparız',
            ],
        ],
        'destek_yaniti' => [
            'label' => 'Destek bileti yanıtı',
            'placeholders' => [
                '{ad}' => 'yazan kişinin adı',
                '{konu}' => 'biletin konusu (kategori)',
            ],
            'parts' => [
                'subject' => 'Nisoya destek: {konu}',
                'greeting' => 'Merhaba {ad},',
                'intro' => 'Bize ilettiğin mesaj için teşekkürler. Yanıtımız aşağıda:',
                'action' => 'Nisoya\'ya git',
                'outro' => 'Bu e-postayı yanıtlayarak bize tekrar yazabilirsin.',
            ],
        ],
        'kayitli_arama' => [
            'label' => 'Kayıtlı arama uyarısı',
            'placeholders' => [
                '{ad}' => 'alıcının adı',
                '{arama}' => 'arama adı',
                '{sayi}' => 'yeni ilan sayısı',
            ],
            'parts' => [
                'subject' => 'Nisoya: Aramana uygun yeni ilanlar var',
                'greeting' => 'Merhaba {ad},',
                'intro' => '"{arama}" aramana uygun {sayi} yeni ilan:',
                'action' => 'İlanları gör',
                'outro' => 'Bu uyarıyı panelindeki "Aramalarım" bölümünden kapatabilirsin.',
            ],
        ],
        'gunluk_rapor' => [
            'label' => 'Günlük Kâhya raporu (yöneticiye)',
            'placeholders' => [
                '{tarih}' => 'raporun tarihi',
            ],
            // BEŞ PARÇANIN HEPSİ TANIMLI OLMAK ZORUNDA: panel formu
            // PART_LABELS üzerinden dönüyor ve eksik parça 500 veriyor.
            'parts' => [
                'subject' => 'Nisoya günlük rapor — {tarih}',
                'greeting' => 'Günaydın,',
                'intro' => 'Dünden bugüne Nisoya\'da olanlar:',
                'action' => 'Yönetim paneline git',
                'outro' => 'Bu raporu Kâhya her sabah otomatik hazırlar. Rapor gelmediği bir gün olursa komut çalışmamış demektir.',
            ],
        ],
    ];

    /** Düzenlenebilir parça adları (sıra = admin formundaki sıra). */
    public const PART_LABELS = [
        'subject' => 'Konu',
        'greeting' => 'Selamlama',
        'intro' => 'Giriş satırı',
        'action' => 'Buton etiketi',
        'outro' => 'Kapanış satırı',
    ];

    /**
     * Bir parçayı (override varsa onu, yoksa varsayılanı) yer-tutucular
     * değiştirilmiş olarak döndürür.
     *
     * @param  array<string,string>  $data  ör. ['{ad}' => 'Ali', '{gonderen}' => 'Veli']
     */
    public static function part(string $key, string $part, array $data = []): string
    {
        $default = self::TEMPLATES[$key]['parts'][$part] ?? '';
        $override = Settings::get("mail_template.{$key}.{$part}");
        $text = ($override !== null && $override !== '') ? $override : $default;

        return strtr($text, $data);
    }
}

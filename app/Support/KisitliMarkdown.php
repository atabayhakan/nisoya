<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Kâhya mesajları için KISITLI markdown: yalnız kalın, italik ve bağlantı.
 *
 * Neden tam bir markdown kütüphanesi değil: sohbet balonuna giren metin bir
 * yapay zekâ modelinden gelir ve HTML'e çevrilen her ek yetenek (ham HTML,
 * resim, başlık...) bir saldırı/bozulma yüzeyidir. Model pratikte üç şey
 * kullanır — **kalın**, *italik*, [bağlantı](adres) — gerisi düz metin kalır.
 *
 * GÜVENLİK SIRASI: önce e() ile HERŞEY kaçırılır, HTML'i sonra yalnız bu
 * sınıf üretir. Bağlantı adresi sadece http(s) ya da site içi `/` ile
 * başlayabilir (`//dis.site` protokol-göreli kaçışı da reddedilir) —
 * `javascript:` gibi şemalar hiç eşleşmez, düz metin olarak kalır.
 */
final class KisitliMarkdown
{
    public static function cevir(?string $metin): HtmlString
    {
        // Yer tutucu ayracı (US, 0x1F) metinden temizlenir: dışarıdan gelen
        // bir kontrol karakteri bizim yer tutucumuzu taklit edemesin.
        $guvenli = e(str_replace("\x1F", '', (string) $metin));

        /*
         * 1) Bağlantılar önce ayıklanıp yer tutucuya alınır: kalın/italik
         *    dönüşümü adresin içindeki yıldızları kurcalayamasın.
         */
        $linkler = [];

        $guvenli = (string) preg_replace_callback(
            '/\[([^\[\]]+)\]\(((?:https?:\/\/|\/(?!\/))[^\s()<>]*)\)/u',
            function (array $m) use (&$linkler): string {
                // Dış bağlantı yeni sekmede: sohbetin ortasından başka bir
                // siteye gidiş, sohbeti kaybettirmemeli. Site içi adres aynı
                // sekmede — balon her sayfada var, sohbet zaten kaybolmaz.
                $ek = str_starts_with($m[2], 'http') ? ' target="_blank" rel="noopener noreferrer"' : '';

                $linkler[] = '<a href="'.$m[2].'"'.$ek.'>'.self::vurgula($m[1]).'</a>';

                return "\x1F".(count($linkler) - 1)."\x1F";
            },
            $guvenli,
        );

        $guvenli = self::vurgula($guvenli);

        $guvenli = (string) preg_replace_callback(
            '/\x1F(\d+)\x1F/',
            fn (array $m): string => $linkler[(int) $m[1]] ?? '',
            $guvenli,
        );

        return new HtmlString($guvenli);
    }

    /** Kalın ve italik — satır içinde kalır, yıldızın matematiksel kullanımına (3 * 4) dokunmaz. */
    private static function vurgula(string $metin): string
    {
        $metin = (string) preg_replace('/\*\*(?=\S)([^\n]+?)(?<=\S)\*\*/u', '<strong>$1</strong>', $metin);

        return (string) preg_replace('/(?<!\*)\*(?=\S)([^*\n]+?)(?<=\S)\*(?!\*)/u', '<em>$1</em>', $metin);
    }
}

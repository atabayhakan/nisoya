<?php

namespace App\Mcp\Araclar;

use App\Services\Kahya\LogOzeti;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

/**
 * GİZLİLİK: yalnız İMZA döner — istisna sınıfı, dosya:satır ve tekrar sayısı.
 * Log mesajının METNİ taşınmaz.
 *
 * Nedeni `LogOzeti` docblock'unda uzun uzun yazılı: Laravel'in
 * `QueryException` mesajı bağlanmış değerleri içerir ve bağlanmış değer
 * kullanıcı verisidir — e-posta, telefon, oturum jetonu, parola sıfırlama
 * anahtarı. "Hangi hata, nerede, kaç kez" sorusunu yanıtlamak için imza
 * yeter; "hangi kullanıcının verisiyle" sorusu bu aracın işi değildir.
 *
 * `dizin` parametresi BİLEREK AÇILMADI: keyfi dizin okutmak yol geçişi
 * demektir. Yalnız `storage/logs` okunur.
 */
#[Name('kahya-hata-kayitlari')]
#[Title('Hata kayıtları (imza özeti)')]
#[Description(
    'Sunucunun log dosyalarındaki ERROR ve üstü kayıtları imzaya göre gruplayıp en sık '.
    'tekrarlayan 5 tanesini döner: istisna sınıfı, dosya:satır ve kaç kez. Toplam sayı tamdır. '.
    'GİZLİLİK: log mesajının metni taşınmaz — sorgu mesajları kullanıcı verisi içerebilir. '.
    'Bir hatanın tam metni gerekiyorsa sunucudaki storage/logs dosyasına bakılmalı.'
)]
#[IsReadOnly]
class HataKayitlari extends KahyaAraci
{
    public function __construct(private readonly LogOzeti $log) {}

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'saat' => $schema->integer()
                ->description('Kaç saat geriye bakılsın. Varsayılan 24, en fazla 168 (bir hafta).'),
        ];
    }

    /** @return array<string, mixed> */
    protected function topla(Request $request): array
    {
        $saat = min(168, max(1, (int) $request->get('saat', 24)));

        // MALİYET UYARISI: pencere yalnız SATIRLARI eler, OKUMAYI sınırlamaz.
        // Her .log dosyası baştan sona okunur; maliyet log klasörünün toplam
        // boyutuyla orantılıdır, pencereyle değil.
        return ['pencere_saat' => $saat] + $this->log->ozetle($saat);
    }
}

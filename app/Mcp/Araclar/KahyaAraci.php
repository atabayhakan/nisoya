<?php

namespace App\Mcp\Araclar;

use App\Support\SaltOkunurBekci;
use App\Support\SaltOkunurIhlali;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Throwable;

/**
 * Kâhya'nın bütün MCP araçlarının ortak tabanı.
 *
 * Üç şeyi TEK yerde garanti eder; her araca ayrı ayrı yazılsaydı biri
 * unutulurdu ve unutulduğu fark edilmezdi:
 *
 * 1. SALT-OKUNURLUK — araç gövdesi {@see SaltOkunurBekci::icinde()} içinde
 *    çalışır. Bir araç (ya da çağırdığı bir servis, gözlemci, önbellek
 *    sürücüsü) yazmayı denerse veritabanı katmanında engellenir. Kapsam
 *    bilerek dar: yalnız aracın çalıştığı süre. Sunucu ömrü boyunca açık
 *    kalsaydı testlerde bir araç çağrıldıktan sonra `factory()->create()`
 *    patlardı, yani bekçi test edilemez hâle gelirdi.
 *
 * 2. İSTİSNA MESAJI SIZDIRMAZ — paketin kendi hata yolu `APP_DEBUG` açıkken
 *    ham istisna mesajını yapay zekâya YOLLAR (`InteractsWithResponses::
 *    toErrorMessage()`), üstelik o dalda `report()` çağrılmadığı için log'a
 *    da düşmez. Bir `QueryException` mesajı bağlanmış değerleri, yani
 *    kullanıcı verisini içerir. Bu yüzden istisnayı paketin eline hiç
 *    bırakmıyoruz: burada yakalanır, log'a yazılır ve dışarıya yalnız sınıf
 *    adı çıkar. Kural `LogOzeti`'ndekiyle aynı.
 *
 * 3. YAPILANDIRILMIŞ ÇIKTI — `Response::structured()` hem okunabilir JSON
 *    metni hem de makine tarafında ayrıştırılmış `structuredContent` üretir.
 *
 * YENİ ARAÇ EKLERKEN: bu sınıftan türet, `topla()` yaz ve `#[IsReadOnly]`
 * KOY. `handle()` bilerek `final` — bir araç tabanı atlarsa üç garanti de
 * düşer, ve `KahyaMcpTest` içindeki bekçi testi zaten sunucudaki her aracın
 * buradan türediğini doğrular.
 *
 * ---------------------------------------------------------------------------
 * `#[IsReadOnly]` NEDEN BURADA DEĞİL, HER ARAÇTA AYRI AYRI
 *
 * PHP sınıf attribute'larını MİRAS ALDIRMAZ ve paketin `HasAnnotations`
 * trait'i `new ReflectionClass($this)->getAttributes()` ile yalnız somut
 * sınıfa bakar. Attribute burada dururken protokolde `annotations: []`
 * görünüyordu: taban sınıf "bu araçlar salt-okunur" diye bir BEYAN taşıyor
 * ama beyan tele hiç çıkmıyordu. Ölçüldü ve düzeltildi;
 * `test_her_arac_salt_okunur_ilan_eder` tekrar etmesini engelliyor.
 */
abstract class KahyaAraci extends Tool
{
    final public function handle(Request $request): Response|ResponseFactory
    {
        try {
            $veri = SaltOkunurBekci::icinde(fn (): array => $this->topla($request));
        } catch (SaltOkunurIhlali $e) {
            // Bekçinin mesajı zaten sızıntısız (yalnız ilk kelime) ve
            // eylem tarif ediyor — olduğu gibi geçsin.
            return Response::error($e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return Response::error(
                'Kâhya aracı hata verdi: '.class_basename($e).
                '. Ayrıntı sunucunun kendi log dosyasına yazıldı; mesaj kullanıcı verisi '.
                'içerebileceği için buraya taşınmıyor.'
            );
        }

        if ($veri === []) {
            return Response::text('Sonuç boş.');
        }

        return Response::structured($veri);
    }

    /**
     * Aracın gerçek işi. Salt-okunur kip AÇIKKEN çağrılır.
     *
     * @return array<string, mixed>
     */
    abstract protected function topla(Request $request): array;
}

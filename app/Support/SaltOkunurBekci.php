<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * "Kâhya canlıda ASLA yazmaz" sözünü SÖZLEŞMEYLE DEĞİL, veritabanı katmanında
 * zorlar.
 *
 * ---------------------------------------------------------------------------
 * NEDEN BÖYLE BİR ŞEY GEREKİYOR
 *
 * Bir MCP sunucusu, uzaktaki bir yapay zekânın uygulama koduna açtığı kapıdır.
 * "Yazma aracı yazmadım, o hâlde yazamaz" bir güvence değil, bir niyet
 * beyanıdır: yarın eklenecek bir araç, bir servisin içindeki `save()`, bir
 * gözlemci (observer), hatta `Cache::put` niyeti bozmadan sözü bozar.
 *
 * Bu sınıf sözü ölçülebilir bir kurala çevirir: bekçi devredeyken SELECT
 * ailesi DIŞINDA hiçbir SQL çalışmaz. Çalışmayı deneyen kod istisna alır.
 *
 * ---------------------------------------------------------------------------
 * NEDEN `beforeExecuting()` — diğer iki aday NEDEN ÇALIŞMAZ
 *
 * Laravel'de sorguya karışabileceğin üç yer var ve ikisi bu iş için SAHTE
 * güvenlik verir:
 *
 *   1. `DB::listen()` / `QueryExecuted` — `logQuery()` içinde, yani sorgu
 *      ÇALIŞTIKTAN SONRA tetiklenir. Buradan istisna atarsan istisnayı
 *      görürsün ve engellediğini sanırsın; oysa satır çoktan yazılmıştır.
 *      (Ölçüldü: listen callback'inden atılan istisnaya rağmen tablo 3→4.)
 *
 *   2. `StatementPrepared` olayı — yalnız `select()`, `selectResultSets()` ve
 *      `cursor()` tarafından tetiklenir. `statement()`/`affectingStatement()`
 *      PDO'yu doğrudan çağırır, `unprepared()` ise `exec()` kullanır. Yani bu
 *      olay tam olarak GÖRMESİ GEREKEN yazmaları göremez.
 *
 *   3. `beforeExecuting()` — `Connection::run()`'ın ilk satırında, `try`
 *      bloğunun DIŞINDA, PDO'ya dokunulmadan önce çalışır. Buradan atılan
 *      istisna `prepare`/`execute`/`exec` çağrılmadan yukarı çıkar. `select`,
 *      `insert`, `update`, `delete`, `statement`, `affectingStatement`,
 *      `unprepared`, `cursor` ve Schema/migration işlemlerinin HEPSİ buradan
 *      geçer. Tek gerçek seçenek budur.
 *
 * ---------------------------------------------------------------------------
 * ALLOW-LIST, DENY-LIST DEĞİL
 *
 * "INSERT|UPDATE|DELETE'i engelle" yaklaşımı `TRUNCATE`, `RENAME`, `GRANT`,
 * `LOCK TABLES`, `CALL <yazan_prosedür>` gibi onlarca ifadeyi kaçırır. Bu
 * yüzden kural terstir: yalnız okuma ifadeleri geçer, GERİ KALAN HER ŞEY
 * reddedilir. Bilinmeyen bir ifade eklenirse bekçi onu tanımadığı için
 * engeller — güvenli taraf.
 *
 * ---------------------------------------------------------------------------
 * KAPSAMADIĞI ŞEYLER — dürüstlük için yazılı
 *
 * Bu bekçi VERİTABANI katmanındadır. Şunları GÖRMEZ:
 *   · `Storage::put` / dosya sistemi yazımları
 *   · `Mail::send`
 *   · `Cache::put` (aşağıdaki önlem bunu ayrıca kapatır)
 *   · kuyruğa iş atma, `Process`/`exec`, `artisan down`
 *   · `DB::getPdo()->exec()` ile PDO'ya doğrudan erişim
 *   · `beginTransaction()`/`commit()` (kendi başlarına veri yazmazlar)
 *
 * Kâhya araçları bunların hiçbirini yapmaz ve `KahyaMcpTest` bunu araç araç
 * doğrular; ama bekçinin tek başına yeterli olduğu sanılmasın diye burada
 * yazılı.
 *
 * ---------------------------------------------------------------------------
 * ÖNBELLEK NEDEN `array`'e ÇEVRİLİYOR
 *
 * `Settings::get()` → `Cache::rememberForever()`. Yerel geliştirmede
 * `CACHE_STORE=database`, yani soğuk bir önbellekte ilk okuma `cache`
 * tablosuna INSERT dener ve bekçi onu haklı olarak engeller — teşhis aracı
 * kendi kendini patlatır. Üretimde sürücü Redis olduğu için sorgu çıkmaz ama
 * bu sefer de Kâhya Redis'e yazmış olur.
 *
 * Bekçi devredeyken önbellek `array`'e (süreç-içi) alınır. İki kazanç:
 * hiçbir yere yazılmaz VE değerler önbellekten değil doğrudan veritabanından
 * okunur — bir teşhis aracı için zaten doğru olan davranış budur.
 */
final class SaltOkunurBekci
{
    /**
     * Yalnız okuma ifadeleri. `with` (CTE) bilerek DIŞARIDA: MySQL 8 ve
     * PostgreSQL'de `WITH ... INSERT` geçerlidir ve bu depo CTE üretmiyor.
     */
    private const IZINLI = '/^(select|show|describe|desc|explain|pragma)\b/i';

    /**
     * İzinli bir SELECT'in içinde bile YAZAN kalıplar. `INTO OUTFILE` diske
     * dosya yazar, `FOR UPDATE` satır kilidi alır — ikisi de "salt okuma"
     * değildir ve bu depo ikisini de üretmez.
     */
    private const YASAK_KALIP = '/\binto\s+(out|dump)file\b|\bfor\s+update\b|\block\s+in\s+share\s+mode\b/i';

    private static bool $aktif = false;

    /**
     * Bekçinin HANGİ uygulama örneğine takıldığı.
     *
     * Basit bir `bool $kuruldu` bayrağı YETMEZ ve bu ders pahalıya patladı:
     * PHPUnit her test için yeni bir uygulama örneği (yeni `DatabaseManager`,
     * yeni `Connection` nesneleri) kurar, ama statik bayrak süreç boyunca
     * yaşar. Bayrakla yazıldığında bekçi yalnız SINIFTAKİ İLK TESTTE takılıydı;
     * geri kalan testlerde sessizce devre dışıydı ve testler "yazma engellendi"
     * derken aslında hiçbir şey engellenmiyordu.
     */
    private static ?object $kuruldugumuzUygulama = null;

    /**
     * Verilen işi salt-okunur kip içinde çalıştırır.
     *
     * KAPSAM BİLEREK DAR: bekçi sunucu ömrü boyunca değil, YALNIZ bir aracın
     * çalıştığı sürede açıktır. Aksi hâlde testlerde bir araç çağrıldıktan
     * sonra bekçi açık kalır ve sonraki `factory()->create()` çağrıları
     * patlar — yani bekçi test edilemez hâle gelirdi.
     *
     * @template T
     *
     * @param  Closure(): T  $is
     * @return T
     */
    public static function icinde(Closure $is): mixed
    {
        self::kur();

        $oncekiAktif = self::$aktif;
        $oncekiOnbellek = config('cache.default');

        self::$aktif = true;
        config(['cache.default' => 'array']);

        try {
            return $is();
        } finally {
            self::$aktif = $oncekiAktif;
            config(['cache.default' => $oncekiOnbellek]);
        }
    }

    public static function aktifMi(): bool
    {
        return self::$aktif;
    }

    /**
     * Bekçiyi bağlantılara TAKAR (henüz devreye almaz).
     *
     * İki yol birlikte gerekli:
     *   · hâlihazırda çözülmüş bağlantılar — `ConnectionEstablished` onlar için
     *     çoktan tetiklenmiş, geriye dönük dinlenemez;
     *   · bundan sonra kurulacaklar — `DB::purge()` sonrası yeniden kurulan
     *     bağlantı yepyeni bir nesnedir ve callback dizisi boştur.
     */
    private static function kur(): void
    {
        $uygulama = app();

        if (self::$kuruldugumuzUygulama === $uygulama) {
            return;
        }

        self::$kuruldugumuzUygulama = $uygulama;

        foreach (DB::getConnections() as $baglanti) {
            $baglanti->beforeExecuting(self::denetle(...));
        }

        Event::listen(ConnectionEstablished::class, function (ConnectionEstablished $olay): void {
            $olay->connection->beforeExecuting(self::denetle(...));
        });
    }

    /**
     * Tek sorgunun kararı.
     *
     * @throws SaltOkunurIhlali yazma denemesinde
     */
    private static function denetle(string $sql, array $bindings, Connection $baglanti): void
    {
        if (! self::$aktif) {
            return;
        }

        $temiz = ltrim($sql);

        if (! preg_match(self::IZINLI, $temiz)) {
            throw new SaltOkunurIhlali($temiz);
        }

        if (preg_match(self::YASAK_KALIP, $temiz)) {
            throw new SaltOkunurIhlali($temiz);
        }

        // Çoklu ifade: `SELECT 1; DROP TABLE x`. Bu depoda üretilen SQL'de
        // noktalı virgül hiç geçmez, o yüzden varlığı tek başına şüphelidir.
        if (str_contains($temiz, ';')) {
            throw new SaltOkunurIhlali($temiz);
        }
    }
}

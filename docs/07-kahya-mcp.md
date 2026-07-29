# Kâhya MCP sunucusu (Faz E)

Kâhya'nın teşhis servislerini bir MCP sunucusu olarak açar; böylece bir yapay
zekâ asistanı sitenin durumunu **doğrudan sorabilir**.

---

## Bu ne kazandırıyor — dürüst cevap

Sunucuya SSH erişimi zaten vardı. `php artisan tinker` ile canlı veritabanına
her şey sorulabiliyordu. Bu sunucunun getirdiği şey **erişim değil, erişimin
şekli**:

| | SSH + tinker | Kâhya MCP |
|---|---|---|
| Ne sorulabilir | her şey | yedi araç, her biri ne döndürdüğünü yazılı söylüyor |
| Yanlışlıkla veri değiştirme | **mümkün** | veritabanı katmanında engellenir |
| Kullanıcı verisi görünürlüğü | tam | log metni, ayar değerleri ve kişisel veriler bilerek dışarıda |
| Nereden çalışır | kabuk erişimi olan oturum | MCP konuşan her istemci |

Asıl kazanç üçüncü satır değil ikinci satır: **Kâhya yazamaz, ve bu bir niyet
değil bir mekanizma.**

---

## Salt-okunurluk nasıl zorlanıyor

`app/Support/SaltOkunurBekci.php`. Her araç gövdesi bu kipin içinde çalışır ve
kip açıkken `SELECT` ailesi dışında hiçbir SQL çalışmaz — deneyen kod
`SaltOkunurIhlali` alır.

Mekanizma `Connection::beforeExecuting()`. Bu hook `run()`'ın ilk satırında,
`try` bloğunun dışında, PDO'ya dokunulmadan **önce** çalışır. `select`,
`insert`, `update`, `delete`, `statement`, `affectingStatement`, `unprepared`,
`cursor` ve Schema/migration işlemlerinin hepsi buradan geçer.

İki alternatif bilerek elendi ve nedeni önemli:

- **`DB::listen()`** sorgu **çalıştıktan sonra** tetiklenir. Buradan istisna
  atarsan istisnayı görürsün ve engellediğini sanırsın; satır çoktan
  yazılmıştır. Ölçüldü: listen callback'inden atılan istisnaya rağmen tablo
  3 → 4 oldu.
- **`StatementPrepared` olayı** yalnız `select`/`cursor` için tetiklenir;
  `insert`/`update`/`delete`/`statement`/`unprepared` için **hiç
  ateşlenmez** — yani tam olarak görmesi gereken yazmaları göremez.

Kural **allow-list**: yalnız okuma ifadeleri geçer. Deny-list olsaydı
`TRUNCATE`, `RENAME`, `GRANT`, `LOCK TABLES` gibi onlarca ifade kaçardı.

### Kapsamadığı şeyler

Bekçi veritabanı katmanındadır. `Storage::put`, `Mail::send`, kuyruğa iş atma,
`Process`/`exec` ve `DB::getPdo()->exec()` kapsam DIŞIDIR. Kâhya araçları
bunların hiçbirini yapmaz ve `KahyaMcpTest` bunu araç araç doğrular — ama
bekçinin tek başına yeterli olduğu sanılmasın.

### İsteğe bağlı ikinci savunma hattı (henüz kurulu değil)

MySQL tarafında salt-okuma yetkili ayrı bir kullanıcı, uygulama katmanının
tamamen dışında kalan bir güvence olurdu. Şu an DB kullanıcısı
`GRANT ALL ON nisoya.*`. Kurmak istersen sunucuda:

```sql
CREATE USER 'nisoya_okur'@'127.0.0.1' IDENTIFIED BY '<kendi seçtiğin parola>';
GRANT SELECT ON nisoya.* TO 'nisoya_okur'@'127.0.0.1';
```

Sonra `config/database.php` içine bu kullanıcıyla ikinci bir bağlantı tanımlanıp
MCP süreci ona yönlendirilebilir. **Parolayı sen belirle ve sen gir** — bu
depoya ya da bir asistana hiçbir koşulda yazılmamalı.

---

## Bağlanma

Depoda `.mcp.json` var, iki sunucu tanımlı.

### Yerel (geliştirme veritabanı)

```bash
php artisan mcp:start kahya
```

`.mcp.json` içindeki `kahya-yerel` bunu çağırır. **PHP yolu:** taşınabilir bir
PHP kullanıyorsan (Windows'ta `D:\nisoya-tools\php\php.exe` gibi) `command`
alanını tam yola çevir; `php` PATH'te değilse sunucu sessizce bağlanamaz.

### Canlı (üretim, salt-okunur)

`kahya-canli` girdisi şunu çalıştırır:

```bash
ssh -T -o BatchMode=yes nisoya-canli "cd /var/www/nisoya && sudo -n -u www-data php artisan mcp:start kahya"
```

Üç ayrıntı ve nedenleri:

- **`nisoya-canli` bir SSH takma adıdır**, sunucu adresi değil. Bu depo
  herkese açık; adres ve kullanıcı `~/.ssh/config` içinde kalır:

  ```
  Host nisoya-canli
      HostName <VPS IP>
      User root
      IdentityFile ~/.ssh/<anahtarın>
  ```

- **`sudo -n -u www-data` şart.** Root olarak `artisan` çalıştırmak
  `storage/logs` ve `bootstrap/cache` altında root sahipli dosya bırakır;
  sonrasında php-fpm (www-data) o dosyalara yazamaz ve site loglaması sessizce
  bozulur. `-n` parola sorulmasını engeller — bir sudo istemi stdout'a düşerse
  protokol bozulur.

- **`-T`** sözde terminal istemez. Kanalın temiz olduğu ölçüldü: 41 bayt
  gönderildi, 41 bayt geri geldi, stderr boş, MOTD yok.

**Web uç noktası (`Mcp::web`) bilerek açılmadı.** Sunucu salt-okunur olsa bile
internete açık bir teşhis uç noktası, kimliği doğrulanmamış birine sitenin iç
durumunu anlatır. SSH zaten var ve kimlik doğrulaması çözülmüş durumda.

---

## Araçlar

Maliyet farkı gerçektir; ucuz olanlar serbestçe çağrılabilir.

| Araç | Maliyet | Ne söyler |
|---|---|---|
| `kahya-nabiz` | ucuz (~12 COUNT) | envanter, son 24 saat, bekleyen işler, raporun yaşı |
| `kahya-son-rapor` | çok ucuz (1 satır) | sahibe **gerçekten gönderilmiş** raporun kendisi |
| `kahya-eksik-alanlar` | ucuz | doldurulmamış kritik ayarlar, ilansız kategoriler |
| `kahya-sistem-sagligi` | ucuz | kuyruk, başarısız işler, son yedek, disk, APP_DEBUG |
| `kahya-tam-teshis` | **pahalı** | yukarıdakilerin hepsi, şimdi hesaplanmış |
| `kahya-medya-dogrula` | **pahalı** (IO) | diskte bulunmayan görseller |
| `kahya-hata-kayitlari` | **pahalı** (IO) | son N saatin hata imzaları |

`kahya-tam-teshis` bir günlük toplu işin bütçesini kullanır: ~26 sorgu +
yüzlerce dosya kontrolü + `storage/logs`'un baştan sona okunması. Sahibin sabah
gördüğü rapor yeterliyse `kahya-son-rapor` bedavadır ve **yeniden hesaplamaz** —
gelen kutusundaki sayılarla birebir aynı rakamı verir.

### Parametre sınırları kodda uygulanır

JSON Schema istemci tarafında bir öneridir. `medya_limit: 999999` gelirse
sunucu 500'e kırpar. Aksi hâlde tek bir çağrı üretimde on binlerce dosya
kontrolü başlatırdı.

---

## Verilerde bilerek olmayan şeyler

| Yok | Neden |
|---|---|
| Log mesajlarının metni | `QueryException` mesajı bağlanmış değerleri içerir: e-posta, telefon, oturum jetonu, parola sıfırlama anahtarı. Yalnız istisna sınıfı + `dosya:satır` + tekrar sayısı döner. |
| Ayar **değerleri** | `site_settings` tablosunda `mail.password`, `ai.api_anahtari`, `growth.google_places_api_key` duruyor. Yalnız "dolu mu, boş mu" döner. |
| İstisna mesajları | Paketin kendi hata yolu `APP_DEBUG` açıkken ham mesajı yapay zekâya yollar ve o dalda `report()` çağırmadığı için log'a bile düşmez. `KahyaAraci` istisnayı paketin eline hiç bırakmaz: yakalar, log'a yazar, dışarıya yalnız sınıf adı çıkar. |
| Kişisel veriler | e-posta, telefon, mesaj içeriği hiçbir aracın çıktısında yok. |

`test_hicbir_arac_sir_degeri_dondurmez` bu iddiayı her araç için tek tek
sınıyor: ayarlara bilinen sır değerleri yazılır, tüm araçlar çağrılır, çıktıda
o değerlerin geçmediği doğrulanır.

---

## Deploy

`laravel/mcp` **`require`** altında (`require-dev` değil): üretim
`composer install --no-dev` ile kuruluyor ve MCP sunucusu üretimde çalışacak.
Deploy betiği zaten `composer install` çalıştırdığı için ek adım yok.

`routes/ai.php` **silinmemeli**. Paket dosyayı bulamazsa sessizce geri döner ve
`mcp:start kahya` "sunucu bulunamadı" der — asıl sebep (dosya hiç yüklenmedi)
gizli kalır.

---

## Neler bilerek araç yapılmadı

- **`kahya:gunluk-rapor` komutu** — e-posta gönderir ve deftere yazar, yani
  salt-okuma değildir. Rapor göndermek için `/yonetim/kahya` sayfasındaki
  "Şimdi rapor gönder" düğmesi var; insan tıklaması gerektiren yer orası.
- **`LogOzeti`'nin `$dizin` parametresi** — keyfi dizin okutmak yol geçişi
  demektir. Yalnız `storage/logs` okunur.
- **Önbellek ve depolama "çalışıyor mu" kontrolü** — admin panosundaki sağlık
  widget'ı bunu ölçmek için `Cache::put` ve `Storage::put` yapar. Bir insan
  panele bakarken doğru bir testtir; Kâhya için sözün ihlalidir.

---

## Sorun giderme

**"MCP Server with name [kahya] not found"** → `routes/ai.php` yüklenmemiş.
Dosya duruyor mu? `php artisan route:clear` denenebilir.

**İstemci "parse error" diyor** → stdout'a protokol dışı bir şey yazılmış.
stdio taşımasında framing satır bazlıdır; `dd()`, `echo`, `var_dump`, kapanış
`?>` sonrası boşluk ya da stdout'a yazan bir log sürücüsü akışı bozar.
`LOG_CHANNEL` asla `stdout`'a çevrilmemeli.

**Süreç bağlantı sırasında ölüyor** → `APP_DEBUG=true` iken beklenmeyen bir
istisna JSON-RPC hatasına çevrilmez, yeniden fırlatılır ve süreç ölür. Üretimde
`APP_DEBUG=false`; yerelde bu davranışa hazır ol.

**Elle denemek için:**

```bash
printf '%s\n' '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-06-18","capabilities":{},"clientInfo":{"name":"deneme","version":"1"}}}' '{"jsonrpc":"2.0","id":2,"method":"tools/list"}' | php artisan mcp:start kahya
```

Dosya yönlendirmesi (`< dosya`) Windows'ta boş çıktı verebilir; **boru
kullan**. MCP istemcileri zaten boru kullanır.

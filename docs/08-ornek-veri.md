# Örnek (demo) veri makinesi — Faz A/B

Siteyi dolu hâlde denemek için örnek üye, ilan, görsel, sohbet, anlaşma ve
değerlendirme üretir — ve **tek tıkla geri alır**.

---

## Önce dürüst bir uyarı

Nisoya'nın bugünkü gerçek darboğazı pazaryerinin boş olması: üç ilan var ve
üçü de sahibin. Örnek veri o sayıyı istediğin kadar büyütebilir ama **hiçbirini
çözmez**. Sahte ilan gerçek satıcı getirmez; getirdiği tek şey, boş olduğunu
görmemektir.

Bu yüzden makine üç yerden kapatıldı:

1. **Kâhya teşhisi örnek kayıtları hiçbir koşulda saymaz.** `gercekEnvanter()`
   sahibin kendini kandırmasını engellemek için var; örnek veri o ölçüyü
   bozamaz. Bu bir ayar değil, koda gömülü bir kural.
2. **Varsayılan gizli.** İlanlar `taslak` doğar ve sitede hiç görünmez.
3. **Görünür kipte bile örnek ilana mesaj gönderilemez.** Cevapsız kalan bir
   mesaj, boş bir pazaryerinden daha kötüdür.

Makinenin doğru kullanımı: **sitenin dolu hâlde nasıl davrandığını denemek** —
sayfalama, sıralama, kart düzeni, moderasyon kuyrukları, anlaşma akışı,
"Doğrulanmış işlem" rozeti.

---

## Geri alma: asıl özellik

Sahte veri üretmek kolaydır; zor olan onu **eksiksiz** geri almaktır. Geride
tek bir yetim satır ya da diskte kalan tek bir dosya, "temizledim" cümlesini
yalan yapar — ve yalan olduğu fark edilmez.

### Defter (`demo_kayitlari`)

Üretilen her kayıt ve diske yazılan her dosya yolu deftere yazılır. Silme
**defterin tersi sırada** yapılır, yani çocuklar ebeveynlerinden önce silinir.

Bayrakla silme (`where is_demo = true`) neden yetmez:

- Bayrak konmamış bir tablo unutulur ve unutulduğu **fark edilmez**.
- Sıra bilinmez. Bu şemada `listings` silinince `conversations.listing_id`,
  `reviews.listing_id` ve `deals.listing_id` NULL'lanır ama **satırlar kalır**
  (`nullOnDelete`). Yanlış sıra tam olarak yetim üretir.

### Dosyalar neden defterde

`listing_images.listing_id` cascade'dir: bir ilan silinince görsel **satırları**
veritabanı seviyesinde gider ve **Eloquent baypas edilir**.
`ListingImageObserver::deleting` çalışmaz, dosyalar diskte kalır. Avatarlarda
daha da kötü — `UserObserver`'ın deleting kancası hiç yok.

Bu yüzden temizleyici gözlemcilere güvenmez: defterdeki yolları kendisi siler.

### Kanıt

`DemoVerisiTest::test_uret_sonra_sil_her_seyi_eski_haline_dondurur` yalnız satır
saymaz — **diskteki dosya sayısını** da karşılaştırır. Ölçülen: 816 dosya → 852
→ 816, her tablo eski sayısında.

`test_temizleyici_defter_disi_gercek_veriye_dokunmaz` ise tersini kanıtlar: bir
temizleme aracının en tehlikeli hatası, temizlemesi gerekmeyeni temizlemektir.
`demo-sil` **yalnız defterde yazanlara** dokunur; `is_demo` işaretli olsa bile
defter dışı bir kayda dokunmaz.

---

## Kullanım

### Yönetim paneli

`/yonetim` → **Sistem & Araçlar → Örnek Veri**. Üretim formu, partilerin listesi
ve her partinin yanında "Geri al" düğmesi. Sayfanın asıl işi üretmek değil,
**geri almayı görünür tutmak**: üretildiğini unutmak, üretmekten daha
tehlikeli.

### Komut satırı

```bash
php artisan demo:uret                      # 4 üye, üye başına 2 ilan, GİZLİ
php artisan demo:uret --uye=10 --ilan=3    # daha büyük parti
php artisan demo:uret --gorunur            # ilanlar sitede görünsün
php artisan demo:durum                     # partiler + defter dışı artıklar
php artisan demo:sil 2026-07-30-a1b2c3     # tek parti
php artisan demo:sil --hepsi               # hepsi
```

`demo:uret` üretim ortamında `--force` ister. `demo:sil` **istemez** — bilerek:
temizlenmesi zor olan bir demo makinesi, geri alınamayan bir demo makinesidir.

### Yapay zekâ (MCP)

Ayrı bir sunucu: `nisoya-demo` (`php artisan mcp:start demo`).

| Araç | Ne yapar |
|---|---|
| `demo-durum` | partiler, kapının durumu, defter dışı artıklar — **her zaman kayıtlı** |
| `demo-uret` | örnek veri üretir — kapı açıkken |
| `demo-sil` | bir partiyi geri alır — kapı açıkken |

**Kapı panelden açılır.** `demo.mcp_acik` ayarı kapalıyken `demo-uret` ve
`demo-sil` araç listesinde **hiç görünmez**. Kapıyı bir insan açar ve bir kez
açar — her çağrıda onay istemek "ajana söyleyince yapsın" isteğini boşa
çıkarırdı. `demo-durum` kapalıyken de görünür ve kapının kapalı olduğunu söyler;
sessizce kaybolan bir yetenek, hata ayıklanamaz bir yetenektir.

#### Neden Kâhya sunucusuna eklenmedi

[Kâhya sunucusu](07-kahya-mcp.md) veritabanı katmanında **yazamaz** ve bunu
testler kanıtlıyor. Yazma araçlarını oraya eklemek o kanıtı çöpe atardı:
salt-okunurluk "çoğu zaman açık bir bayrak" hâline gelirdi ve o bayrağın bir
gün yanlış kalması an meselesidir.

İki sunucu, iki taban sınıfı, iki `.mcp.json` girdisi.
`DemoMcpTest::test_kahya_sunucusu_yazma_araci_barindirmaz` ayrılığı bekliyor.

Üretim sunucusunda örnek veri üretmek istersen `.mcp.json`'a Kâhya'nınkiyle aynı
kalıpta bir girdi ekle (`ssh … mcp:start demo`). Bilerek hazır gelmiyor: bu depo
herkese açık ve canlı sunucuda sahte veri üreten bir girdinin varsayılan olarak
durması yanlış olurdu.

---

## Üretilen veri neye benziyor

| Alan | Değer |
|---|---|
| E-posta | `demo-…@demo.invalid` — RFC 2606 ile ayrılmış, asla çözümlenmeyen TLD |
| İlan başlığı | `[ÖRNEK] …` |
| Görsel | Sıfırdan üretilmiş yer tutucu: düz renk + daire + şerit + piksel yazı |
| İlan durumu | `taslak` (varsayılan) ya da `aktif` (görünür kip) |

### Görseller neden bu kadar "sahte" görünüyor

Bilerek. Bir görsel servisinden indirmek üç sorun getirirdi: üretim
sunucusunda ağ bağımlılığı, kaynağı belirsiz görsellerin lisansı, ve en
önemlisi — gerçekçi bir fotoğraf demo verisini **gerçek gibi** gösterir.

Yazı için TTF kullanılmıyor: depoda hiç TTF yok ve `public/build` altındaki
woff'lar işe yaramıyor (FreeType woff2'yi okuyamıyor; woff'lar fontsource alt
kümesi olduğu için Türkçe harfler yerine tofu basıyor — ölçüldü). Bunun yerine
GD'nin gömülü bitmap fontu küçük bir katmana yazılıp büyütülüyor. Bedeli yalnız
ASCII (`imagestring` bayt tabanlıdır, UTF-8 mojibake olur); kazancı yeni
bağımlılık olmaması ve çıkan blok/piksel görünümün "bu gerçek bir fotoğraf
değil" demesi.

---

## Bildirim ve günlük üretilmez

Veri Eloquent ile yazılır, controller'lardan geçirilmez. Aynı senaryoyu HTTP
üzerinden üretmek daha "gerçekçi" olurdu ama **gerçek bildirimler** üretirdi
(`DealNotification`, `NewReviewNotification`, `NewMessageNotification`) ve hız
sınırlarına takılırdı.

`activity_log` de bilerek kapatılıyor (`activity()->withoutLogs()`): o tablonun
modele bağlı bir FK'si yok, yani demo silindikten sonra bile satırları kalırdı.
Üretmemek, sonradan temizlemekten güvenli.

---

## Eski `DemoSeeder` ne oldu

`database/seeders/DemoSeeder.php` duruyor ve dokunulmadı — ama artık **kullanma**.
İşaretsiz veri üretir (`is_demo` yok, defter yok), görsel üretmez, sohbet/anlaşma
üretmez ve geri alınamaz. Docblock'undaki "Üretimde ÇALIŞTIRMAYIN" uyarısı
tamamen yorum seviyesindedir; kodda hiçbir ortam kontrolü yoktur.

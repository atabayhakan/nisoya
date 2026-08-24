# Nisoya AI arama — kapsam genişletmesi (Yaşam Rehberi + İş İlanları)

**Tarih:** 2026-08-25 · **Durum:** Onaylandı, uygulanıyor
**Kapsam:** Anasayfa "Nisoya AI ile ara" çubuğunun (`NisoyaAiYonlendirici`, PR #196) niyet sınıflandırmasını 3'ten 5'e çıkarmak: `rehber` | `yasam` | `ilan` | `is` | `belirsiz`.
**Devraldığı belge:** [2026-08-19 Rehber ülke-varsayılanı + Nisoya AI planı](2026-08-19-rehber-ulke-varsayilani-ve-nisoya-ai-plani.md) — §3.4'te "site-geneli arama" bilerek ERTELENMİŞTİ (içerik hacmi ince gerekçesiyle). Bu belge o ertelemenin kısmi geri alınışı: TÜM site değil, iki somut yüzey (Yaşam Rehberi + İş İlanları) — ikisi de bugün gerçek, sorgulanabilir veri taşıyor.

---

## 0. Neden şimdi

İki tetikleyici:
1. **Somut boşluk:** Bu oturumda Yaşam Rehberi'nin (Bankacılık&Finans, 5 ülke) ana sayfa entegrasyonu (F2) tamamlandı, ama `NisoyaAiYonlendirici`'nin niyet şeması hâlâ eski (`rehber`/`ilan`/`belirsiz`) — çubuğun kendi placeholder metni ("SSN'siz banka hesabı") tam olarak Yaşam Rehberi konusu ama router bunu tanımıyor, "belirsiz"e düşüp ilan linkine yönlendiriyor.
2. **Kapsam dışı bırakılan diğer yüzeyler incelendi:** SSS tek bir `Page` kaydında gömülü HTML (ayrı sorgulanabilir birim yok) — bilerek bu faza DAHİL EDİLMEDİ. hakkımızda/koşullar/gizlilik gezinme sayfası, arama hedefi değil — kapsam dışı. İş ilanları (`/isler`, `JobBrowseController`) zaten yapılandırılmış filtreler (kategori/tip/ülke/uzaktan) + `q` LIKE arama taşıyor — hazır.

---

## 1. Mimari

**Kurulu iki desen birebir korunuyor, üçüncüsü icat edilmiyor:**

| Niyet | Motor | Desen |
|---|---|---|
| `rehber` | `RehberDogalDilArama` (var) | AI çıkarır → DB doğrular/eşler → sonuç listesi |
| `yasam` | `YasamDogalDilArama` (**yeni**) | Birebir aynı desen, `RehberDogalDilArama`'nın aynadaşı |
| `ilan` | — (var) | Ham metni `/ilanlar?q=`'a handoff, o sayfanın KENDİ `DogalDilArama`'sı yorumlar |
| `is` | — (**yeni davranış, yeni servis YOK**) | `ilan` ile birebir aynı handoff deseni: `/isler?q=&ulke=` — `JobBrowseController` zaten `q` LIKE + `ulke` filtresini destekliyor |
| `belirsiz` | `RehberDogalDilArama` + `YasamDogalDilArama` (güvenlik ağı) | İkisi de dener, ikisi de boşsa `/ilanlar` linkine düşer |

**`YasamDogalDilArama`** — AI çağırmaz, `RehberDogalDilArama`'nın 4 kademeli düşüşünü taşır: ülke+kategori+anahtar-kelime (konu düzeyi, `yasam-rehberi.icerik`) → yalnız kategori (yine konu düzeyi) → yalnız anahtar kelime (`YasamKonusu.baslik`/`kisa_aciklama` LIKE) → ülkenin tüm yayında kategorileri (`yasam-rehberi.konular`). Yalnız `YasamKonuIcerigi::STATUS_YAYIN` — taslak hiç sızmaz.

**`ara()` yeniden düzenleniyor** (rehber/yasam/belirsiz artık simetrik): niyet `yasam` ise önce yasam motoru denenir, boşsa rehber motoru güvenlik ağı olarak denenir (ve tersi rehber niyetinde) — ikisi de boşsa `belirsiz`. Kişiselleştirme (`varsayilanUlkeKodu`) YALNIZ ilgili motora net işaret eden niyette uygulanır (mevcut kural, değişmiyor).

**Şema:** `yasam_kategori_slug` alanı eklendi (`islem_turu_slug`'ın kardeşi); `ulke_kodu`/`anahtar_kelimeler` zaten paylaşılıyordu. Tek AI çağrısı hâlâ tek.

**`is` niyeti için servis YOK:** `JobBrowseController` zaten `q` (başlık+açıklama LIKE) + `ulke` destekliyor — AI'nin çıkardığı `ulke_kodu`yu (zaten çıkarılıyor, ekstra şema maliyeti yok) `/isler` linkine eklemek yeterli. Yeni bir `IsIlaniDogalDilArama` yazmak bugünkü içerik/filtre yapısına göre erken optimizasyon olurdu.

**Frontend:** Sonuç listesi şekli (`{baslik, altbaslik, url}`) değişmiyor — Alpine bileşeni zaten jenerik render ediyor, dokunulmuyor. Yalnız boş-sonuç panelindeki sabit metin ("Bu konuda hazır bir rehberimiz yok" / "İlanlarda ara →") `result.niyet === 'is'` durumunda niyete uygun metne dönüyor (`niyet` zaten JSON'da dönüyordu, yeni alan gerekmiyor).

---

## 2. Maliyet/güvenlik disiplini — değişmiyor

Aynı throttle (`nisoya-ai-arama`, dokunulmuyor), aynı 7 günlük cache (şema değiştiği için cache anahtarı otomatik değişir, eski girişler sessizce çöp olur), aynı "uydurma yasak" (kategori slug'ı gerçek `YasamKategorisi` listesinde yoksa null'a düşer; `is` linkindeki `ulke_kodu` da aktif `Country` listesine karşı doğrulanır), aynı admin kill-switch, aynı "her sonuç gerçek insan-yazılı bir sayfaya çıkar" kuralı.

---

## 3. Test stratejisi

`NisoyaAiYonlendiriciTest`'in var olan ailesiyle aynı desen (sahte AI sağlayıcı, gerçek DB): `yasam` niyeti gerçek Yaşam Rehberi içeriğine yönlendirir; `yasam` boşsa `rehber` güvenlik ağı devreye girer (ve tersi); `is` niyeti `/isler?q=&ulke=` linkine yönlendirir, yeni sonuç ÜRETMEZ; geçersiz `yasam_kategori_slug` sessizce reddedilir; mevcut testler (eski fixture'larda `yasam_kategori_slug` anahtarı YOK) kırılmadan geçer (`?? null` ile geriye dönük uyumlu).

Yeni `YasamDogalDilArama` için `RehberDogalDilArama`'nın kapsadığı senaryoların aynadaşı: doğrudan eşleşme, kategori-bazlı düşüş, anahtar-kelime düşüşü, taslak içeriğin sızmaması, geçersiz ülke/kategori kodunun sessizce reddedilmesi.

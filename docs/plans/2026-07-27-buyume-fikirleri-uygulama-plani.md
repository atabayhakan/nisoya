# Büyüme Fikirleri (2026-07-27) — Uygulama Planı

> **DEVİR NOTU (2026-08-01):** B3 (Körfez SEO rehberi) ve B4 (öğrenci rehberi) maddeleri
> [ülke-adaptif rehber tasarımına](2026-08-01-ulke-adaptif-rehber-tasarimi.md) devşirildi —
> buradaki düz `/rehber/{slug}` yaklaşımı yerine ülke/şehir şeması (`/de/koeln/vekaletname`)
> geçerlidir; B4 içeriği zaten `OgrenciRehberiSeeder` ile hazır. A maddeleri (manuel erişim:
> TUSU/Dubai Rehberi/ATAA) ve B5 (Nisoya Elçisi rozeti) bu belgede geçerli kalır.

Kaynak: [docs/03-buyume-fikirleri.md](../03-buyume-fikirleri.md), `## [2026-07-27]` bölümündeki 6 öneri.
Bu belge o önerileri iki gruba ayırıp somut adımlara döker: **(A) manuel erişim** (Hakan'ın kendi
yapması gereken, dış sistemlere mesaj/e-posta içerdiği için Claude'un tek başına yapamayacağı işler)
ve **(B) kod/içerik işleri** (Nisoya deposunda uygulanabilir özellikler).

## Durum özeti

| # | Fikir | Grup | Sahip | Efor | Sıra |
|---|-------|------|-------|------|------|
| 1 | TUSU (İngiltere öğrenci birliği) duyuru rica | A | Hakan | düşük | 1 |
| 2 | Dubai Türk Rehberi karşılıklı tanıtım | A | Hakan | düşük | 2 |
| 6 | ATAA şubelerine tanıtım e-postası | A | Hakan | düşük | 3 |
| 5 | Referral sisteminde "Nisoya Elçisi" rozeti | B | Claude (kod) | düşük-orta | 1 |
| 3 | Körfez bölgesi SEO rehber sayfası (pilot) | B | Claude (kod) | orta | 2 |
| 4 | Öğrenciye özel SEO rehber sayfası | B | Claude (kod) | orta | 3 |

---

## A) Manuel erişim — Hakan'ın yapması gereken

Bunlar dış kişi/kuruma mesaj göndermeyi içerdiği için Claude tarafından otonom yapılamaz
(izin sınırları: "sending any message on the user's behalf" açık onay ister ve bu durumda
alıcı üçüncü taraf bir kurum). Claude'un burada rolü: **istenirse** taslak mesaj/e-posta
metni yazmak — gönderimi Hakan yapar.

1. **TUSU (Turkish Student Union of the UK)** — tusu-uk.org, facebook.com/tusuuk
   - İlk adım: Facebook sayfası veya iletişim formu üzerinden kısa bir tanıtım mesajı.
   - Zamanlama notu: Eylül-Ekim (sonbahar dönem başı) öncesi ulaşmak etkiyi artırır.
   - Claude'dan istenebilir: mesaj taslağı (TR/EN).

2. **Dubai Türk Rehberi** — dubairehberi.com.tr
   - İlk adım: site iletişim kanalından karşılıklı link/tanıtım teklifi.
   - Claude'dan istenebilir: kısa teklif metni taslağı.

3. **ATAA (Assembly of Turkish American Associations)** — ataa.org/component-associations
   - İlk adım: listeden 2-3 büyük şube (ör. TACA-Chicago, ATA-Houston, TACAF-Florida) seçip
     kısa bir tanıtım e-postası.
   - Claude'dan istenebilir: e-posta taslağı.

**Not:** Bu üç madde için taslak metin yazımı istenirse ayrı bir turda (kod değişikliği
gerektirmediği için plan/onay süreci olmadan) hemen yapılabilir.

---

## B) Kod/içerik işleri — Nisoya deposunda uygulanabilir

Bunlar için aşağıdaki teknik yaklaşım taslak düzeyinde; gerçek uygulamaya geçmeden önce
(özellikle SEO sayfaları için) EnterPlanMode ile kısa bir teyit turu önerilir çünkü yeni bir
route/içerik yapısı kurulacak.

### 5. Referral sisteminde "Nisoya Elçisi" rozeti (öncelik 1 — en düşük efor)

Mevcut durum: `panel/davet` ([InviteController.php](../../app/Http/Controllers/InviteController.php))
zaten `referral_code`, davet linki ve `referrals()->count()` gösteriyor ama hiçbir tanınma/teşvik
unsuru yok.

Taslak yaklaşım:
- Eşik: 3+ başarılı davet (`$user->referrals()->count() >= 3`).
- Görünürlük: `panel/davet` sayfasında + (istenirse) herkese açık profil sayfasında, mevcut
  `verified-badge` bileşeninin yanına ikinci bir rozet olarak (bkz. `resources/views/components/verified-badge.blade.php`
  deseni — aynı desende yeni bir `elci-badge` bileşeni).
- Veritabanı değişikliği gerekmez (mevcut `referrals()` ilişkisinden hesaplanır); istenirse
  ileride eşik değişebilir diye `config/site_defaults.php`'e bir sabit eklenebilir.
- Test: mevcut `tests/Feature/` desenine uygun, 3 davetli kullanıcıda rozetin göründüğünü,
  2 davetlide görünmediğini doğrulayan bir feature test.

### 3. Körfez bölgesi SEO rehber sayfası (pilot)

Mevcut durum: Nisoya'da blog/rehber altyapısı yok (`Route::get.*rehber` araması sonuçsuz) —
bu bir ilk.

Taslak yaklaşım (pilot = tek sayfa, tek ülke):
- Yeni route grubu: `/rehber/{slug}` → sabit bir controller/blade (CMS değil, ilk pilotta
  elle yazılmış statik içerik + `layout-head-meta` bileşeni ile SEO meta/JSON-LD).
- İçerik: "Dubai'de yeni taşınan Türkler için ilk 30 gün: ev eşyası ve hizmet bulma rehberi."
- Sitemap'e ekleme (mevcut sitemap üretim mekanizmasına yeni statik girdi).
- İç linkleme: ilgili ilan/hizmet kategorilerine (ev eşyası, hizmet) link.
- Ölçüm: Search Console'da bu sayfanın gösterim/tıklama verisi 2-4 hafta izlenip madde 4'e
  (öğrenci rehberi) geçmeden önce şablonun işe yarayıp yaramadığına bakılır.

### 4. Öğrenciye özel SEO rehber sayfası

Madde 3'teki pilot şablonu doğrulandıktan sonra aynı `/rehber/{slug}` altyapısıyla ikinci
sayfa: "İngiltere'de üniversite için ilk kez yurt dışına çıkan Türk öğrenciler için ikinci el
eşya ve ev kurma rehberi." Eylül-Ekim dönem başına yetişecek şekilde zamanlanmalı.

---

## Önerilen sıra

1. Rozet (B5) — bağımsız, küçük, hemen yapılabilir.
2. TUSU + Dubai Rehberi + ATAA taslak metinleri (A1/A2/A6) — istenirse hemen yazılır,
   gönderimi Hakan yapar.
3. Körfez pilot SEO sayfası (B3) — rehber altyapısını ilk kez kurduğu için EnterPlanMode
   ile kısa teyit sonrası uygulanması önerilir.
4. Sonuç görülünce öğrenci SEO sayfası (B4).

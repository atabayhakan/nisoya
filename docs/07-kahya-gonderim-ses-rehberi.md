# Kâhya Gönderim Kimliği — Amazon SES Kurulum Rehberi

Tarih: 2026-07-30 · Amaç: Kâhya'nın onaylanan e-posta hamlelerini gönderdiği
AYRI kimliği kurmak (F4 "dış eller" — bkz. Kâhya Ayarları → Dış Eller).

> **Neden ayrı kimlik?** Erişim/tanıtım postası şikâyet yerse spam damgasını
> yalnız gönderim alanı yer; nisoya.com'un işlemsel postaları (şifre sıfırlama,
> bildirim — Hostinger SMTP) hiç etkilenmez. Bu yüzden aşağıda her şey
> `mail.nisoya.com` ALT ALANI üzerine kurulur ve ana alanın DNS'ine yalnız
> yeni kayıtlar EKLENİR — mevcut hiçbir kayda dokunulmaz.

Toplam süre: ~30 dk elle iş + AWS'nin ~24 saatlik üretim onayı beklemesi.
Maliyet: SES $0.10 / 1000 e-posta — günde 10 postayla ayda ~3 cent.

---

> ## ⛔ DURUM (2026-08-09): AWS ÜRETİM ERİŞİMİNİ REDDETTİ — bu rehber BEKLEMEDE
>
> Case **178543966900428** kapandı: *"we are unable to approve a sending limit
> increase at this time."* AWS hiçbir ölçüt paylaşmadı; AUP'a uyum sağlandıktan
> sonra yeniden başvurulabileceğini yazdı.
>
> **Sahibin kararı:** soğuk e-posta otomasyonu bırakıldı. Arz kanalı artık
> **elle erişim** → `docs/10-elle-erisim-listesi.md`. Keşif havuzu yalnız
> kaynak liste olarak kalıyor.
>
> **Aşağıdaki kurulumun tamamı yapıldı ve canlıda duruyor** (SES kimliği, SNS
> konusu, geri bildirim ucu, engel listesi, tek-tık çıkış). Bozuk bir şey yok —
> yalnız sandbox sınırı var: gönderim doğrulanmış adreslerle kısıtlı. Bu rehber
> ileride *izinli* bir listeye gerçekten posta gidecekse tekrar açılır.
>
> **Reddedilenin ne olmadığı önemli:** Nisoya'nın işlemsel postası (üyelik,
> bildirim, destek bileti) Hostinger SMTP üzerinden gidiyor ve SES'e hiç bağlı
> değil. Sitenin çalışması için gereken hiçbir şey bu retten etkilenmedi.

---

## Adım 1 — AWS hesabı ve SES konsolu

1. [aws.amazon.com](https://aws.amazon.com) → hesap aç (kredi kartı ister;
   SES kullanımı bu hacimde fiilen bedava).
2. Konsolda sağ üstten bölge seç: **Europe (Frankfurt) `eu-central-1`**
   (VPS Vilnius'ta, hedef kitle Avrupa ağırlıklı — yakın bölge).
3. Arama kutusuna **SES** yaz → *Amazon Simple Email Service*.

## Adım 2 — Kimlik doğrulama: `mail.nisoya.com`

1. SES → **Identities** → **Create identity** → *Domain* seç.
2. Domain alanına: `mail.nisoya.com` (ana alan DEĞİL — bilinçli).
3. **Easy DKIM** açık kalsın (RSA_2048). *Create identity* de.
4. SES sana **3 adet CNAME** kaydı verecek (DKIM). Bunları not al.

## Adım 3 — Hostinger hPanel'de DNS kayıtları

hPanel → Domains → nisoya.com → **DNS / Name Servers** → kayıt ekle.
(SES'in verdiği gerçek değerleri kullan; aşağıdakiler biçim örneği.)

| Tür | Ad (Host) | Değer | Ne için |
|---|---|---|---|
| CNAME | `abc123._domainkey.mail` | `abc123.dkim.amazonses.com` | DKIM 1/3 |
| CNAME | `def456._domainkey.mail` | `def456.dkim.amazonses.com` | DKIM 2/3 |
| CNAME | `ghi789._domainkey.mail` | `ghi789.dkim.amazonses.com` | DKIM 3/3 |
| TXT | `_dmarc.mail` | `v=DMARC1; p=quarantine; rua=mailto:atabayhakan@outlook.com` | DMARC (yalnız alt alan için) |

**Önerilen ek: Custom MAIL FROM** (SPF hizalaması tam olsun diye):

1. SES → Identities → mail.nisoya.com → **Custom MAIL FROM domain** →
   `giden.mail.nisoya.com` gibi bir alt alan gir.
2. SES'in verdiği iki kaydı ekle:

| Tür | Ad (Host) | Değer |
|---|---|---|
| MX | `giden.mail` | `10 feedback-smtp.eu-central-1.amazonses.com` |
| TXT | `giden.mail` | `v=spf1 include:amazonses.com ~all` |

Kayıtlar yayıldıktan sonra (5 dk – birkaç saat) SES kimlik sayfasında
**Verified** görünür. Not: hPanel'de "Ad" alanına genelde alan adsız kısım
yazılır (`_dmarc.mail` gibi) — Hostinger tam ad isterse sonuna `.nisoya.com`
ekle.

## Adım 4 — Sandbox'tan çıkış (üretim erişimi)

SES yeni hesapları sandbox'ta açar: yalnız doğrulanmış adreslere gönderebilirsin.

1. SES → **Account dashboard** → *Request production access*.
2. Formu DÜRÜSTÇE doldur (örnek):
   - **Mail type:** Transactional/Outreach → "Other"
   - **Use case:** "Nisoya.com, yurtdışındaki Türkler için ücretsiz bir
     pazaryeri. Türk dernekleri ve öğrenci birliklerinin HERKESE AÇIK
     iletişim adreslerine, tek tek ve sahip onayıyla kısa tanıtım/işbirliği
     e-postaları gönderiyoruz. Günlük tavan 10; ret/şikâyet gelen adres
     kalıcı engel listesine giriyor; toplu liste yok."
   - Beklenen hacim: günde ≤10.
3. Onay tipik olarak ~24 saatte gelir. **Beklerken test için:** SES →
   Identities → *Create identity* → Email address → kendi adresini
   (atabayhakan@outlook.com) doğrula — sandbox'ta kendine gönderim yapabilirsin.

### Talep reddedilir/kapanırsa

AWS ilk yanıtta neredeyse her zaman **ek soru sorar** (ne sıklıkta
gönderiyorsun, listeyi nasıl tutuyorsun, bounce/şikâyet/çıkış nasıl
işleniyor, örnek e-posta). Yanıtlanmazsa talep 7 gün sonra kendiliğinden
kapanır — 2026-07-31'de açılan talep tam olarak böyle kapandı.

Cevap yazarken dayanabileceğin somut mekanizmalar (hepsi kodda, testli):

| Sorunun konusu | Koddaki karşılığı |
|---|---|
| Sıklık / hacim | `kahya.gunluk_gonderim_limiti` (varsayılan 10), gönderimde uygulanır |
| Liste nereden | OSM/Places'ten herkese açık işletme iletişim bilgisi; satın alınmış liste yok |
| Onay | Her mesaj tek tek sahip onayından geçer (`BekleyenHamle`) |
| Çift gönderim | `gonderildi_at` — aynı karttan iki posta çıkmaz |
| Abonelikten çıkma | `List-Unsubscribe` + `List-Unsubscribe-Post` (RFC 8058 tek tık) + gövdede görünür bağlantı |
| Bounce / şikâyet | SNS → `/webhook/ses-geri-bildirim` → kalıcı engel listesi (Adım 4b) |

## Adım 4b — Bounce/şikâyet bildirimi (SNS)

Bu adım **üretim erişimi için fiilen zorunlu**: AWS "geri bildirimleri nasıl
işliyorsun?" diye sorar ve tablo bir cevap değil, çalışan bir uç ister.

1. AWS → **SNS** → *Topics* → **Create topic** → tür `Standard`,
   ad `nisoya-ses-geri-bildirim`. Bölge **eu-central-1** (SES ile aynı olmalı).
2. Konu sayfasında **Create subscription**:
   - Protocol: **HTTPS**
   - Endpoint: `https://nisoya.com/webhook/ses-geri-bildirim`
   - *Enable raw message delivery* **KAPALI KALSIN** — uç, SNS zarfını
     (imza dâhil) bekliyor; ham teslimde imza doğrulanamaz.
3. Konunun **ARN**'sini kopyala → `/yonetim/kahya-ayarlari` → Dış Eller →
   **SES geri bildirim konusu** alanına yapıştır → Kaydet.
   > **ARN girilmeden uç hiçbir şey yapmaz** (HTTP 503 döner). Bilerek:
   > imza doğru olsa bile hangi konudan geldiğini bilmeden şikâyet kabul
   > etmek, başkasının kendi AWS hesabından bizim listemizi susturmasına
   > izin vermek olurdu.
4. SES → **Identities** → `mail.nisoya.com` → *Notifications* →
   **Bounce** ve **Complaint** için bu SNS konusunu seç.
5. Aboneliği onayla: ARN kaydedildikten sonra SNS'te aboneliğin yanındaki
   **Request confirmation**'a bas. Uç onayı kendiliğinden yapar (imza ve konu
   doğrulandıktan sonra), abonelik **Confirmed**'a döner.

Bundan sonra kalıcı bounce ve şikâyet adresleri otomatik olarak kalıcı engel
listesine girer. **Geçici bounce (dolu kutu) engellemez** — birkaç saatliğine
kutusu dolu olan gerçek bir muhatabı temelli kaybetmemek için.

## Adım 5 — SMTP kimlik bilgileri

1. SES → **SMTP settings** → *Create SMTP credentials* (bir IAM kullanıcısı
   oluşturur; ad önerisi `nisoya-kahya-gonderim`).
2. Çıkan **SMTP username** ve **SMTP password**'ü kaydet (bir daha gösterilmez).
3. Sunucu bilgileri: host `email-smtp.eu-central-1.amazonaws.com`, port **465**.

## Adım 6 — Kâhya Ayarları → Dış Eller

`/yonetim/kahya-ayarlari` → **Dış Eller** bölümü:

| Alan | Değer |
|---|---|
| SMTP sunucusu | `email-smtp.eu-central-1.amazonaws.com` |
| Port | `465` |
| SMTP kullanıcı adı | (Adım 5'teki username) |
| SMTP parolası | (Adım 5'teki password) |
| Gönderen adres | `merhaba@mail.nisoya.com` |
| Gönderen adı | `Hakan — Nisoya` |
| Günlük gönderim tavanı | `5` (ilk 2-3 hafta; sonra 10) |

Kaydet — o andan itibaren onayladığın e-posta hamle kartları bu kimlikle
gönderilir. **İlk test:** sandbox'tayken Kâhya'ya kendi adresine bir deneme
hamlesi hazırlatıp onayla; posta kutunda gör.

## Adım 7 — Isıtma ve hijyen (ilk ay)

- Tavanı 5'te tut; 2-3 hafta sorunsuz geçince 10'a çıkar. Acele etme —
  yeni kimlikten ani hacim, spam klasörünün en kısa yolu.
- SES → **Reputation metrics**'i haftada bir kontrol et: bounce < %2,
  şikâyet < %0.1 kalmalı (Kâhya Harcamaları sayfası gönderim sayısını,
  SES panosu itibarı gösterir).
- Ret cevabı gelen adresi `kahya_gonderim_engelleri` tablosuna ekle
  (Kâhya'ya "şu adresi engel listesine ekle" demek yetmez — o tablo şimdilik
  elle/panelden; Kâhya yalnız okur).
- SES'in kendi hesap-düzeyi suppression listesi açık kalsın (varsayılan).

## Sorun giderme

- **"Verified" olmuyor:** DNS yayılımını bekle; hPanel'de kayıt adlarının
  sonuna yanlışlıkla `.nisoya.com` eklenip eklenmediğini (çift ekleme) kontrol et.
- **535 Authentication failed:** SMTP parolası IAM parolası DEĞİLDİR —
  Adım 5'teki *SMTP credentials* akışından çıkanı kullan.
- **554 Message rejected (sandbox):** üretim onayı henüz gelmedi ya da alıcı
  doğrulanmamış — Adım 4.
- **Gönderildi ama spam'e düştü:** Custom MAIL FROM (Adım 3 ek) kurulu mu,
  DMARC kaydı var mı bak; ilk haftalarda kişisel, kısa, linksiz/az-linkli
  metinler kullan.

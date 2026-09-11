# Kâhya Telegram — tasarım kararı (2026-09-11)

## Sorun

Yurtdışındaki Türk diaspora WhatsApp/Telegram gruplarında (örn. "Kanada Türk
yardımlaşma grubu" 567 üye, "Rusya'daki Türkler" 3.898 üye) binlerce insan
günlük ihtiyaçlar (iş, konut, hizmet, vize/hukuk sorunu) için birbirine
soruyor — bilgi dağınık, kayboluyor, hukuki bilgi doğrulanmamış şekilde
kişiden kişiye gidiyor. Aynı gruplarda doğrudan bir rakip (kanadada.com)
zaten "ilanlarınızı ücretsiz ekleyin" tarzı tanıtım mesajı atıyor.

Sahibin tezi: "yurtdışında Türklerle iş yapmayın" algısı platformsuzluktan /
güvenin kalıcı olmamasından kaynaklanıyor. Bu doğru ama sıralama önemli:
önce hacim (arz/talep) çekilmeli, itibar altyapısı (zaten kısmen var —
FraudBlocklist, güven rozeti, kara liste, anlaşma kaydı) onun üstüne oturur.

## Karar

Üçüncü taraf gruplara girip reklam gibi görünmek yerine **kendi Telegram
grubumuzu kurup Kâhya'yı normal bir üye gibi katılımcı yapmak**. Mevcut
büyük gruplara tek seferlik, satış değil YARDIM davetiyle ("ücretsiz danışma
grubu kurduk") seed edilir.

Alınan alt-kararlar (sırayla, kullanıcıyla netleştirildi):

1. **Kapsam**: Serbest AI sohbeti + hassas konularda (vize/hukuk/para) bekçi
   — cevap engellenmez, çekince notu eklenir ve satır incelemeye düşer.
2. **Pilot**: Rusya (3.898 üye, en yüksek etkileşim + en somut/ciddi
   ihtiyaçlar — vize/deport, sahte iş teklifi). Ayrıca Rusya, Keşif
   Havuzu'nun (outreach) taradığı ülkeler listesinde bile yok.
3. **Yönlendirme**: Duruma göre — bilgi sorusuna Rehber linki, net ihtiyaca
   ("ev arıyorum") DM daveti.
4. **DM mekaniği**: Telegram'ın platform kısıtı nedeniyle bot gruptan
   kimseye kendiliğinden DM ATAMAZ. Çözüm: grupta doğal cevap + net ihtiyaç
   varsa tek satır davet + botu başlatan link; kişi tıklarsa DM'de devam
   eder. Grupta asla ısrarcı/reklam gibi görünmez.
5. **Admin yönetimi**: Her şey "Kâhya & Yapay Zekâ" panel grubunda —
   isim/persona, açık-kapalı, limit dahil.
6. **Kimlik**: KahyaAjani (araç-çağıran, admin eylemlerine erişebilen ajan)
   DEĞİL — ayrı, araçsız bir AiProvider çağrısı (güvenlik: güvenilmeyen
   genel kullanıcı girdisi admin eylemlerini tetikleyebilecek bir ajana asla
   verilmez). Kamuya açık persona adı iç "Kâhya" adından bilerek ayrı.
7. **Limitler**: Canlı bir grup (yüzlerce eşzamanlı kişi) tek kullanıcılı
   admin sohbetinden çok farklı bir hacim riski taşır — bu yüzden aylık
   AI-cevaplı mesaj tavanı (WebAramasi/IsletmeKesfi ile aynı desen) +
   ucuz "bu gerçek bir soru mu" ön-elemesi (TurkishBusinessDetector'daki
   "önce deterministik ön-eleme" ilkesiyle aynı) eklendi.
8. **WhatsApp**: Şimdilik mimari kurulmadı (YAGNI) — log/ayar kayıtlarına
   ucuz bir `kanal` alanı eklendi (varsayılan `telegram`), ileride bir
   WhatsApp varyantı aynı ekranlara oturabilsin diye.

## Mimari

```
Telegram grubu → Telegram webhook → TelegramWebhookController
  (secret_token doğrulama, HTTP 200 cömertliği)
  → TelegramGuncellemesiIsleJob (kuyruk — webhook anında döner)
    → TelegramDinleyici::gelenGuncelleme()
      - izinli grup mu? soru gibi mi? aylık limit doldu mu?
      - rehberBaglami(): YAYINDAki TemsilcilikIslemi + YasamKonuIcerigi
        (Ülke Rehberi — Kâhya'nın İÇ "El Kitabı"/RehberOku'sundan FARKLI)
      - AiProvider::analyzeText() — araçsız, tek JSON şema çağrısı
      - HassasKonuBekcisi::tespit() — vize_hukuk/para → uyarı notu ekle
      - Telegram sendMessage
      - TelegramSohbeti + KahyaHarcamasi'na logla
```

**Yeni dosyalar**: migration+model `TelegramSohbeti`, `App\Support\
HassasKonuBekcisi`, `App\Services\Kahya\Dis\TelegramDinleyici`,
`App\Jobs\TelegramGuncellemesiIsleJob`, `App\Http\Controllers\Kahya\
TelegramWebhookController`, ayar sayfası `App\Filament\Pages\KahyaTelegram`,
inceleme ekranı `TelegramSohbetleriResource`.

**Değişen dosyalar**: `config/kahya.php` (telegram varsayılanları),
`Settings::SIRLI_ANAHTARLAR` (bot_token + webhook_sirri şifreli), `routes/
web.php` + `bootstrap/app.php` (webhook rotası, CSRF muafiyeti, rate limit),
`KahyaHarcamalari` (Telegram kullanım satırı eklendi — sahibin "hepsini
buradan yönetebileyim" isteği).

## Kurulum (sahibin yapması gereken)

1. @BotFather'da bir bot oluştur, token'ı Kâhya Telegram ayarlarına yapıştır.
2. Rastgele bir "webhook gizli anahtarı" üret, aynı ekrana yaz.
3. `https://api.telegram.org/bot<TOKEN>/setWebhook?url=<webhook-url>&secret_token=<SIR>`
   adresini tarayıcıda aç.
4. Botu Rusya pilot grubuna ekle, grup ID'sini (ilk mesajdan Telegram
   Sohbetleri ekranında görünür) "İzinli grup ID" alanına yaz.
5. Ülke kodu RU, aylık limit varsayılan 600 — istenirse değiştirilebilir.

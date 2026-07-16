# Üst Menü Dönüşümü — Tasarım Planı

**Tarih:** 2026-07-16
**Durum:** Taslak — onay bekliyor
**Kapsam:** `resources/views/components/layouts/app.blade.php` içindeki header + mobil gezinme

## Neden

Header, platform tek dikeyliyken (yetenek/hizmet ilanları) tasarlandı. Faz E1/E2
(Emlak, Vasıta) ve Faz D1–D3 (Davetiye) ile birlikte üst menü artık 7 admin
linki + ülke rozeti + acil buton + tema + bildirim + kullanıcı menüsü + İlan Ver
olmak üzere 12+ öğe taşıyor ve düz bir liste olarak büyümeye devam edecek
([[nisoya-genisleme-plani]] içinde sıradaki dikeyler planlanıyor). Bu, hem
bilgi mimarisi hem de görsel olarak tavan yapmış durumda.

Bu plan üç eksende ilerliyor: **(1) bilgi mimarisi** — büyüyen dikeyleri akıllı
bir kategori yapısına toplamak, **(2) teknolojik yetenek** — Cmd+K komut
paleti ile evrensel arama, **(3) görsel/hareket dili** — mobil için native-app
hissi veren alt sekme çubuğu ve ince mikro-etkileşimler.

**Bilinçli sınırlar:** Mevcut stack (Laravel Blade + Alpine.js + Tailwind v4,
SPA framework yok) korunur — yeni ağır bağımlılık eklenmez. Platform ücretsiz
ve reklam bütçesi yok, bu yüzden **LLM/AI API'si kullanılmaz**; "akıllı arama"
mevcut DB sorgularına ve client-side fuzzy eşlemeye dayanır.

---

## 1. Bilgi mimarisi — Kategori sistemi (mega menü)

Bugün `navigation_links` tablosu düz bir liste (`label`, `url`, `sort_order`,
`is_active`, `opens_new_tab`) — admin panelden serbestçe eklenip sıralanıyor
ama gruplama/ikon alanı yok.

**Değişiklik:** Tabloya geriye dönük uyumlu, nullable 3 kolon eklenir:
`group_key` (ör. `kesfet`, `kariyer`, `yasam`, null = üst seviyede tekil link
olarak kalır), `icon` (heroicon adı, ör. `briefcase`), `description` (mega
menüde alt başlık, ör. "Elektrikçiden bakıcıya, komşundan hizmet al").

Masaüstünde header 2 birincil öğeye iner: **"Keşfet" mega menü** (İlanlar,
Yetenek Havuzu, İş İlanları, Emlak, Vasıta, Davetiye — ikon + açıklamayla,
`group_key` dolu olanlar) ve tekil linkler (Harita, Nasıl Çalışır? gibi
`group_key` boş olanlar, ikincil önemde küçük yazıyla sağda). Admin, yeni bir
dikey eklediğinde sadece doğru `group_key`'i seçer — header büyümeye devam
edebilir ama görsel olarak büyümez.

Bu, mevcut admin-yönetimli yapıyı **korur ve genişletir** — yıkıp yeniden
kurmaz.

---

## 2. Komut paleti — Cmd+K evrensel arama

Header'a bir arama simgesi (+ masaüstünde görünen `⌘K` ipucu rozeti) eklenir.
Tıklama veya `Cmd/Ctrl+K` kısayolu, Alpine.js ile yönetilen tam ekran bir
overlay açar:

- **Statik sonuçlar** (anında, client-side): nav linkleri, sık kullanılan
  sayfalar (Panelim, İlan Ver, Bildirimler, Tema Değiştir) — küçük bir JS
  dizisinden fuzzy eşleşir, ağ isteği yok.
- **Canlı sonuçlar** (300ms debounce, mevcut arama/filtre sorgularını
  yeniden kullanan hafif bir `/arama/hizli?q=` JSON endpoint'i): ilanlar, iş
  ilanları, emlak, vasıta, adaylar — tek kutudan, kategori etiketli sonuç
  listesi.
- Klavye ile tam gezinilebilir (ok tuşları, Enter, Esc), odak tuzağı (focus
  trap), ARIA `role="dialog"`.

Bu, benzer platformlarda (sahibinden, dernek siteleri) olmayan, somut ve ücretsiz
bir "teknolojik" fark yaratır — AI maliyeti olmadan.

---

## 3. Mobil — Alt sekme çubuğu (native-app hissi)

Nisoya bir PWA (manifest + service worker mevcut) ama mobil gezinme hâlâ
header altında yatay kayan bir şerit. Bunun yerine:

- **Sabit alt sekme çubuğu** (5 öğe): Ana Sayfa, Keşfet (kategori sheet'i
  açar), **İlan Ver** (ortada, yükseltilmiş/vurgulu buton), Mesajlar
  (bildirim rozetiyle), Panelim.
- Mobil üst header sadeleşir: logo + arama ikonu (komut paletini/arama
  sheet'ini açar) + acil buton + tema — geri kalanı alt çubuğa taşınır.
- "Keşfet" sekmesi, masaüstündeki mega-menü ile aynı `group_key` verisini
  kullanan dokunmatik bir alt sayfa (bottom sheet) açar.
- `env(safe-area-inset-bottom)` ile iOS/PWA tam ekran güvenli alan payı.

---

## 4. Görsel & hareket dili

- **Scroll-farkında header:** aşağı kaydırınca hafifçe küçülür/sıkışır,
  yukarı kaydırınca geri açılır (saf CSS/JS, ek bağımlılık yok).
- Mevcut `backdrop-blur` üzerine ince bir gölge/kaydırma-sınırı eklenir.
- Mikro-etkileşimler: mega menü açılışında Alpine `x-transition`, yeni
  bildirimde zil ikonunda kısa titreşim animasyonu, nav hover'da animasyonlu
  alt çizgi.
- İkonlar mevcut heroicons setinden (`x-heroicon-o-*`) — yeni asset maliyeti
  yok.
- Her yeni bileşen **koyu tema öncelikli** tasarlanır (mevcut tema
  değiştiriciyle bire bir uyumlu).

---

## 5. Teknik kısıtlar

- Yeni npm bağımlılığı yok (gerekirse Alpine'in resmi `collapse`/`focus`
  eklentileri — ikisi de hafif).
- Komut paleti, mevcut Eloquent arama/filtre mantığını yeniden kullanır;
  yeni AI/LLM çağrısı yok.
- `navigation_links` şeması **genişletilir, değiştirilmez** — admin'in özel
  link ekleme özgürlüğü (ör. ülkeye özel sayfalar) korunur.
- Performans bütçesi: eklenen toplam JS ~15-20kb gzip altında kalmalı —
  hedef kitlenin bir kısmı yurt dışında düşük bant genişliğiyle bağlanıyor.
- Erişilebilirlik: mega menü + komut paleti tam klavye gezinilebilir olmalı.

---

## 6. Fazlama (mevcut Faz D/E adlandırmasıyla tutarlı — "Faz H")

| Faz | İçerik | Risk/efor | Neden bu sırada |
|---|---|---|---|
| **H1** | Mega menü + `navigation_links` grup/ikon alanları | Düşük risk, orta efor | Kalabalığı hemen çözer, en somut acı noktası |
| **H2** | Cmd+K komut paleti + hızlı arama endpoint'i | Orta efor, en "devrimsel" his | H1'deki kategori verisini besler |
| **H3** | Mobil alt sekme çubuğu | Orta efor, mobil trafiği doğrudan etkiler | H1'deki `group_key` verisiyle "Keşfet" sheet'i çalışır |
| **H4** | Scroll-farkında header + mikro-etkileşim cilası | Düşük risk, "parlatma" fazı (D3 gibi) | Temel yapı oturduktan sonra ince ayar |

Her faz bağımsız olarak yayınlanabilir ve mevcut "Faz X — açıklama" commit
düzenine uyar.

---

## Kaçınılacaklar

- Header'a yeni ikon **eklemek** yerine mevcut öğeleri **katlamak** (ör.
  Harita + Nasıl Çalışır? mega menünün ikincil satırına iner, 9. üst seviye
  öğe olmaz).
- Komut paletini tam AI asistanına genişletme baskısı — platformun "ücretsiz
  kalır" felsefesiyle çelişir, kapsam hızlı arama + gezinme ile sınırlı kalır.
- `navigation_links` üzerinde yıkıcı şema değişikliği — admin'in bugünkü
  serbestliği bozulmamalı.

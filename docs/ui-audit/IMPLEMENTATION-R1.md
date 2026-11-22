# WAM UI yenileme — Uygulama turu 1 (Faz 0–2)

Tarih: 2026-11-11  
Ortam: lokal `http://localhost:8081` (admin)  
Commit/push/deploy: yapılmadı

## Uygulanan fazlar

### Faz 0 — Mevcut durum ve sadeleştirme
- Before ekran görüntüleri: `screenshots/before/` (admin `/my/`, profil düzenleme, oturumlar; WAM dashboard before kaydı yarış nedeniyle bozuk — after ve snapshot ile doğrulandı).
- Web’den erişilebilir yedekler `_backups/wamakademi/` altına taşındı (public dışı). HTTP 404 doğrulandı. `.gitignore` / `.dockerignore` güncellendi.
- Lokal `enableblogs=0`. Profil sayfasında “Blog” metni yok (0 eşleşme). Canlı için aynı cfg adımı gerekir.
- Sahte “Önerilen Eğitimler”, sabit sertifika/saat istatistikleri ve `href="#"` kaldırıldı.
- Dashboard linkleri WAM `mycourses.php` / `calendar.php` olacak şekilde düzeltildi.
- Navbar araması `GET /local/wamakademi/catalog.php?q=` formuna bağlandı; katalog `optional_param('q')` ile dolduruluyor.
- WAM sayfalarındaki mükerrer `styles.css` `requires->css` çağrıları kaldırıldı (tema CSS’i zaten yüklüyor).

### Faz 1 — Tasarım temeli
- Yeni: [`public/local/wamakademi/css/wam-base.css`](../../public/local/wamakademi/css/wam-base.css) — tek token kaynağı, layout, buton, navbar, dashboard, admin panel override’ları.
- `local_wamakademi_before_http_headers()` → `body.wam-ui` (çıktı başlamadan).
- `before_standard_html_head` → `wam-base.css` + mevcut account/menu JS.
- `styles.css`: yeşil/bej token bloğu alias’a çevrildi; `overflow-x: hidden !important` kaldırıldı; hero negatif margin yumuşatıldı.
- Navbar: arama formu + label; mobil Bootstrap toggler korunuyor; yan menüye primary nav kopyası + Tab odak kapanı.

### Faz 2 — Pilot ekranlar
1. **Admin `/my/`**: `limitedwidth` 830px → `--wam-page-max` 1440px; başlık ve içerik aynı sol çizgi (ölçülen: h1Left = innerLeft = 32px). Bloklar ve düzenleme modu duruyor.
2. **WAM dashboard**: kompakt `wam-page-header`, 2 gerçek istatistik, boş durum + katalog CTA, yaklaşanlar boş durumu.
3. **Profil düzenleme**: mevcut `account_ui.js` gruplaması + token/genişlik; filepicker görünür.
4. **Oturumlar**: tablo kartı, “Bu cihaz” rozeti, tehlike ikincil buton (oturum sonlandırma tıklanmadı).

## Değişen dosyalar (özet)

| Dosya | Değişiklik |
|---|---|
| `public/local/wamakademi/css/wam-base.css` | **Yeni** — token + layout + bileşenler |
| `public/local/wamakademi/lib.php` | `before_http_headers`, wam-base yükleme, hesap sayfası matcher (önceki iş) |
| `public/local/wamakademi/templates/dashboard.mustache` | Kompakt dashboard |
| `public/local/wamakademi/index.php` | Mükerrer CSS kaldırıldı; sahte sayaçlar silindi |
| `public/local/wamakademi/catalog.php` | `q` parametresi; mükerrer CSS kaldırıldı |
| `public/local/wamakademi/mycourses.php`, `calendar.php`, `js/calendar_view.php`, `js/upload.php` | Mükerrer CSS kaldırıldı |
| `public/local/wamakademi/styles.css` | Token alias, overflow-x kaldırıldı, hero margin yumuşatıldı |
| `public/local/wamakademi/js/wam_menu.js` | Mobil toggler, primary linkler, odak kapanı |
| `public/theme/boost/templates/navbar.mustache` | Arama formu |
| `public/local/wamakademi/js/account.css`, `account_ui.js` | Önceki hesap turundan; `account.css` seçicileri `#page-user-editadvanced` ile genişletildi |
| `.gitignore`, `.dockerignore` | `_backups/`, bak/faz2 kalıpları |
| Yedekler | `public/` → `_backups/wamakademi/` |

## Kaldırılan / korunan / ertelenen

**Kaldırılan (UI)**
- Blog menü/profil bağlantıları (ayar: `enableblogs=0`; veri silinmedi).
- Sahte önerilen eğitim kartları ve `href="#"`.
- Sabit 0 sertifika / öğrenme saati KPI’ları.
- Pazarlama hero’su (büyük başlık + full-bleed).
- Sahte “Yaklaşanlar” etkinlik satırları → anlamlı boş durum.
- Web kökündeki `*.bak`, `*.faz2`, `*.save`, deploy script.

**Korunan**
- Admin’in `/my/`’de kalması (`is_siteadmin` istisnası).
- Moodle blok düzenleme / düzenleme modu.
- Form gönderimi, filepicker, sesskey, yetkiler.
- Çark menüsü grupları (hesap / öğrenme / tercihler / oturum).
- Portfolyo, rozet, etiket vb. (toplu kaldırma yok).

**Ertelenen (Faz 3+)**
- Katalog / eğitimlerim / ders deneyimi görsel yenileme.
- Child tema + Boost çekirdek özelleştirmelerinin temaya taşınması.
- `styles.css` tekrarlayan hero bloklarının tam temizliği (`!important` hâlâ ~171).
- Veriye bağlı önerilen eğitimler ve gerçek takvim “yaklaşanlar”.
- Öğrenci rolü görsel testi.

## Düzeltilen yerleşim / stil sorunları
- Admin panel dar 830px kolon → 1440px üst sınır, ortak gutter.
- WAM dashboard başlık/içerik sol hizası (ölçüm: 32px).
- Çakışan yeşil/bej vs mavi token → tek kaynak `wam-base.css`.
- Global `overflow-x: hidden` kaldırıldı; yatay taşma dashboard’da yok (`sw === iw`).
- Ölü navbar araması → çalışan katalog araması.
- Mükerrer `styles.css` yükleme kaldırıldı.

## Testler (admin, lokal)

| Test | Sonuç |
|---|---|
| Blog UI kayboldu (profil) | Geçti |
| Yedek URL 404 | Geçti |
| `wam-ui` + `wam-base.css` yükleniyor | Geçti |
| Katalog `?q=excel` katalog arama alanı | Geçti (`value=excel`) |
| Dashboard kompakt başlık / boş durum | Geçti (DOM + after görseller) |
| Admin `/my/` max-width 1440; h1/içerik hizası | Geçti (1440: 32px; 1024/768: 24px) |
| Overflow `scrollWidth <= innerWidth` | Geçti (4 ekran × 1440/1024/768/390) |
| Mobil menü: yan menüde Site yönetimi + WAM linkleri | Geçti |
| Çark menüsü açılır; Escape ile kapanır | Geçti |
| TinyMCE (açıklama) yüklenir | Geçti |
| Filepicker aç/kapa (Ekle… → Dosya seçici → Kapat/Esc) | Geçti |
| Profil şehir kaydet → geri al | Geçti (`Istanbul` → boş) |
| Boş zorunlu alan doğrulama (Adı) | Geçti (sayfada kaldı / invalid) |
| Admin düzenleme modu aç/kapa (`Blok ekle`) | Geçti |
| Oturumlar “Bu cihaz” + kapat butonu görünür | Geçti (sonlandırma tıklanmadı) |
| `php -l` / `node --check` | Geçti |
| Öğrenci rolü | Test edilmedi |

## Ekran görüntüleri
- `screenshots/before/` — admin-my, user-edit, usersessions (1440). `wam-dashboard-1440.png` before kaydı geçersiz (yanlış sayfa).
- `screenshots/after/` — 4 ekran × 4 genişlik (`admin-my`, `wam-dashboard`, `user-edit`, `usersessions` @ 1440/1024/768/390).

## Test edilemeyenler
- Öğrenci / öğretmen rolleri.
- Filepicker dosya yükleme / TinyMCE içerik kaydetme.
- Gerçek oturum sonlandırma (veri değiştirir).
- Canlı / Dokploy ortamı.
- Transpoco piksel renk ölçümü (yapısal referans).

## Canlıya taşıma notları
1. `_backups/` public’e girmesin; deploy öncesi yedeklerin web kökünde olmadığını doğrula.
2. Canlıda `php admin/cli/cfg.php --name=enableblogs --set=0` (veya Site yönetimi → Gelişmiş özellikler).
3. Deploy sonrası Moodle önbelleğini temizle (`purge_caches`) — `styles.css` tema CSS’ine gömülü.
4. Bu turda commit/push yapılmadı.

## Sonraki faz (Faz 3) somut kapsam
- Katalog, Eğitimlerim, kurs detay / ders (resource + lesson_layout), pptx görüntüleyici.
- Arama/filtre kart düzenini `wam-base` token’larına bağla.
- `styles.css` içindeki tekrarlayan hero/katalog bloklarını tek sete indir.
- Öğrenci hesabı ile boş/dolu durum doğrulaması.

## Teknik borç
- Boost `navbar.mustache` / login özelleştirmeleri child temaya taşınmalı.
- Legacy callback → `db/hooks.php` göçü.
- `styles.css` !important azaltımı.
- `wam_menu.js` / `account_ui.js` AMD’ye geçiş.

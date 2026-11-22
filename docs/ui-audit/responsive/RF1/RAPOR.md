# Responsive RF1 — Uygulama raporu

Tarih: 2026-11-18  
Commit/push/deploy: **yapılmadı**  
Analiz HEAD: `28cc0033…` (RF1 öncesi kod aynı dalda; ölçümler bu turda yenilendi)

## Kapsam

| Ekran | Rota | Roller |
|---|---|---|
| HOME-WAM | `/local/wamakademi/index.php` | student, teacher, admin |
| CATALOG | `/local/wamakademi/catalog.php` | student, teacher |
| MYCOURSES | `/local/wamakademi/mycourses.php` | student, teacher |
| CALENDAR-WAM | `/local/wamakademi/calendar.php` | student, teacher, admin |

Ortak üst menü (820 px taşması) RF1 kapsamında düzeltildi.

## Ekran sonuçları

### Ortak header
- **Önce (admin 820):** navbar `scrollWidth−clientWidth` ≈ **98–99 px**; ileti ikonu sağ kenara taşıyordu.
- **Sonra (admin 820):** `navOv = 0`; ileti `right ≈ 694` (viewport 820 içinde). Chromium + WebKit.
- **Dar admin (320):** `wam-nav-compact` ile üst WAM linkleri, arama, düzenleme anahtarı gizlenir; `navOv = 0`.
- Menü 390→820 açık kalır; kapanınca `overflow` kilidi kalkar.

### HOME-WAM
- Kart grid `minmax(min(240px,100%), 1fr)`; başlık `overflow-wrap`.
- Kısa yükseklik (`max-height: 500px`) hero padding/min-height düşürür.
- Acceptance: 320/390/820/1180/1440 overflowX ≤ 2 (student/teacher/admin).

### CATALOG
- Toolbar `minmax(0,…)` sütunları; kart gövdesi `min-width: 0`; başlık 3 satır clamp; kapak `aspect-ratio: 16/9`.
- Boş arama `?q=xyznoresult` 390’da overflow 0.
- GET sorgu parametreleri yön değişiminde korunur (yeni state yok).

### MYCOURSES
- Mevcut kart/`overflow-wrap` / filtre toolbar zaten uygundu; RF1’de header + dar alan doğrulandı.
- Acceptance sweep’te overflow yok.

### CALENDAR-WAM
- **Kök neden 1:** `.wam-calendar-navigation strong { min-width: 165px/140px }` + sıkı gap → Bugün taşması (+33 @390).
- **Kök neden 2:** `body.path-local-wamakademi .wam-calendar-layout { minmax(330px) !important }` medya sorgusunu eziyordu → 320’de day-panel taşması (+52).
- **Sonra:** 320/360/390/820/1440’ta page overflowX = 0; Bugün `clipped: false` (Chromium + WebKit).

## Değişen dosyalar

| Dosya | Ne |
|---|---|
| [`public/local/wamakademi/css/wam-base.css`](../../../public/local/wamakademi/css/wam-base.css) | Esnek arama, compact nav, panel sınırları, home kart/hero |
| [`public/local/wamakademi/js/wam_menu.js`](../../../public/local/wamakademi/js/wam_menu.js) | ResizeObserver → `wam-nav-compact`; pushMq’da overlay/inert temizliği |
| [`public/local/wamakademi/styles.css`](../../../public/local/wamakademi/styles.css) | Takvim nav/layout !important medya; katalog toolbar/kart |
| [`public/local/wamakademi/js/account.css`](../../../public/local/wamakademi/js/account.css) | Takvim toolbar (wam-acc-ready yolu) |

## Taşma önce / sonra (özet)

| Durum | Önce | Sonra |
|---|---|---|
| CAL 390 Bugün | clipped, +33 px | clipped false, 0 |
| CAL 320 sayfa | +103 / +52 | 0 |
| Admin header 820 | navOv ~98 | 0 |
| HOME/CATALOG/MYCOURSES 390 | ~0 | 0 |

Kanıt: `before/`, `after/`, `measure-before-chromium.json`, `measure-acceptance.json`, `measure-final.json`.

## Test matrisi

| Motor | Viewport’lar | Roller |
|---|---|---|
| Chromium (Playwright) | 320, 360, 390×844, 844×390, 507, 820×1180, 1180×820, 1024, 1440 | student, teacher, admin |
| WebKit | 320, 360, 390, 820, 1440 (takvim + header) | student, teacher, admin |
| Gerçek iPhone/iPad Safari | — | **yapılmadı** |
| Gerçek ekran klavyesi | — | **yapılmadı** (yalnız yükseklik vekili önceki audit’te) |

Regresyon: Site yönetimi arama 390/1440 overflow 0, navOv 0. Profil düzenleme otomasyonu kısmen (öğrenci edit URL); header kabul sweep’i dört WAM ekranını kapsadı.

## Ortak header’ın etkilediği ekranlar

Tüm `body.wam-ui` sayfaları (hesap, site yönetimi, kurs). RF1 regress: `/admin/search.php`. 109 ölçülmemiş admin kategori/settings **doğrulanmış sayılmadı**.

## Geçmeyen / yapılamayan

- Gerçek cihaz Safari / safe-area
- Katalog formunda üst çubuk `#wam-course-search` gizli olduğu için otomasyon önce onu seçti; sayfa araması GET `?q=` ve `.wam-catalog-search` ile doğrulandı
- Moodle theme CSS önbelleği: `styles.css` değişikliklerinden sonra `theme_reset_all_caches()` gerekti

## Sonraki grup önerisi

**RF3 — tablolar:** `COURSE-GRADES` (+896 px), `COURSE-PARTICIPANTS` (kaybolan işlemler), `SITE-PARTICIPANTS`, `ADM-SYSUSERS`.  
Gerekçe: F-01 / F-03 P0. Eksik başlangıç: RF1 sonrası bu dört ekranın taze 390/820/1440 ölçümü; öğrenci ders verisi hâlâ ayrı.

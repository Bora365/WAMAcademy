# Responsive RF10 — F-04 yönetim ekranları

Tarih: 2026-11-18  
Faz: **RF10** (bulgu **F-04**)  
Commit/push/deploy: **yapılmadı**

## Seçilen dört ekran

| Kimlik | Rota | Kabuk |
|---|---|---|
| ADM-APPEARANCE | `/admin/category.php?category=appearance` | `wam-admin` |
| ADM-SETTINGS | `/admin/settings.php?section=frontpagesettings` | `wam-sitefront` (+ `wam-course`) |
| ADM-THEME | `/admin/settings.php?section=themesettingboost` | `wam-admin` |
| ADM-GRADES | `/admin/category.php?category=grades` | `wam-admin` |

Kategori altındaki diğer ayar sayfaları (ör. ek HTML, logolar) bu turda hedeflenmedi; RF15+ doğrulama kapsamına bırakıldı.

**RF2 açık kaldı:** COURSE-VIEW, LESSON-RESOURCE, UPLOAD-VIDEO, COURSE-EDIT — öğrenci için yayınlı örnek video yok (LESSON); RF10 admin form hattına öncelik verildi.

## Önce (baseline)

Chromium `measure-before-chromium.json` + `before/`.

| Ekran | 390 pageOv | 320 pageOv | Kök neden |
|---|---|---|---|
| ADM-APPEARANCE | **+407** | **+477** | `#emoticonsetting` tablosu sarmalayıcısız; renk diyaloğu |
| ADM-SETTINGS | **+87** | **+157** | Secondary `nav-tabs` + `.defaultsnext` select genişliği (sitefront) |
| ADM-THEME | 0 | **+21** | `colourdialogue` ~300 px |
| ADM-GRADES | **+24** | **+94** | `.form-defaultinfo` uzun CSV (`firstname,lastname,…`) kırılmıyor |

## Uygulama

### Secondary navigation
- `wam-sitefront.css` / `wam-admin.css`: `minmax(0,1fr)` grid, dış `overflow-x: hidden`, iç `.navigation` `overflow-x: auto`
- Aktif sekme yerel kaydırmada kalır; “Daha fazla” davranışı (sitefront_nav) korunur
- WebKit’te UL `scrollWidth` sızıntısı Chromium’dan farklıydı → dış `hidden` şart

### Formlar
- Admin: sarmalayıcısız tablolar (`.form-setting .mb-3:has(> table)`) yerel `overflow-x: auto`
- `.form-defaultinfo` / açıklamalar: `overflow-wrap: anywhere`
- Select / input / `.defaultsnext`: `width/max-width: 100%`; form satırları `overflow-x: auto` (WebKit select `scrollWidth`)
- Sitefront `#adminsettings` aynı desen (ADM-SETTINGS `wam-admin` yüklemez)

### Tema kontrolleri
- `.admin_colourpicker` / `img.colourdialogue`: `max-width: 100%`
- Dosya yöneticisi alanlarına dokunulmadı; kayıt / tema değiştirme **yapılmadı**

## Sonra

| Ekran | Chromium 320/390/820/1440 | WebKit 390/820/1440 |
|---|---|---|
| ADM-APPEARANCE | pageOv **0** | **0** |
| ADM-SETTINGS | **0** | **0** |
| ADM-THEME | **0** | **0** |
| ADM-GRADES | **0** | **0** |

Kaydet düğmesi (ADM-SETTINGS): “Değişiklikleri kaydet” görünür; gönderilmedi.  
Yerel nav kaydırma: `nav.scrollWidth − clientWidth` > 0 iken sayfa pageOv = 0.

## RF3 kısa kontroller

### Checkbox / dokunma
- RF3’te input’a `min-width/height: 40px` verilmişti (`--wam-control-h`); ortak `--wam-touch` = **44px**, alan yüksekliği `--wam-field-h` = **44px**
- Düzeltme: native işaret ~13×13; **`.form-check` sarmalayıcı 44×44** (katılımcılar + sistem kullanıcıları)
- Native checkbox 44px’e büyütülmedi

### `#wam-course-edit-host`
- 320 compact: anahtar host’ta, tek kopya, pageOv 0
- Aç/kapa gerçek düzenleme durumunu değiştirdi; eski haline alındı
- 1440: navbar’a döndü, host boş
- Öğrenci kurs görünümü: `editmode-switch-form` yok

### RF3 regresyon
COURSE-GRADES / COURSE-PARTICIPANTS / ACC-EDIT @390 → pageOv 0

## Değişen dosyalar

| Dosya | Etki |
|---|---|
| `css/wam-admin.css` | Nav, tablo sarmalayıcı, defaultinfo, colourpicker, form overflow (tüm `wam-admin`) |
| `css/wam-sitefront.css` | Sitefront secondary nav + frontpagesettings form |
| `css/wam-course.css` | Katılımcı checkbox hedefi (RF3 düzeltmesi) |
| `css/wam-users.css` | Sysusers checkbox hedefi |

Ortak admin CSS, RF11 kategori şablonlarını da etkiler; bu turda yalnız dört ekran doğrulandı.

## Test matrisi

| Motor | Viewport | Rol |
|---|---|---|
| Chromium | 320, 390×844, 844×390, 820, 1180, 1440 | admin |
| WebKit | 390, 820, 1440 | admin |
| Gerçek Safari / cihaz | — | **yapılmadı** |

## Engelli / açık

- Tema kaydetme, site ayarı kaydetme, not ayarı kaydetme: **işlevsel kayıt testi yok** (bilinçli)
- Renk diyaloğu ölçeklenince piksel-tıklama hassasiyeti gerçek cihazda doğrulanmadı
- LESSON-RESOURCE öğrenci verisi hâlâ yok

## Sonraki grup

**RF2 kısmen alınabilir:** COURSE-VIEW, UPLOAD-VIDEO, COURSE-EDIT veri beklemeden ilerleyebilir. LESSON-RESOURCE için yayınlı örnek video gerekir; yoksa eğitmen/admin resource ile sınırlı doğrulama.  
Alternatif: RF11 (ADM-PLUGINS / SERVER / REPORTS / SECURITY) — RF10 şablonunun doğrulanması.

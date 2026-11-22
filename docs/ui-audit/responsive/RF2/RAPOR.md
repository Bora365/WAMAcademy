# Responsive RF2 — kurs görünümü, yükleme, düzenleme

Tarih: 2026-11-18  
Faz: **RF2**  
Durum: **RF2 — üç ekran tamamlandı, LESSON açık**  
Commit/push/deploy: **yapılmadı**

## Kapsam

| Kimlik | Rota | Roller | Kabuk / CSS |
|---|---|---|---|
| COURSE-VIEW | `/course/view.php?id=2` | student, teacher, admin | `wam-course.css`, `course_nav.js` |
| UPLOAD-VIDEO | `/local/wamakademi/upload.php?courseid=2&sectionnum=0` | teacher, admin | `wam-content.css`, `upload.php` (F-06) |
| COURSE-EDIT | `/course/edit.php?id=2` | teacher, admin | `wam-forms.css`, `wam-controls.css` |

**LESSON-RESOURCE açık:** kurs 2 resource `cmid=4` (`WAM CW R1 Test Video`) `visible=0`; öğrenci için yayınlı örnek yok. Bu turda yayınlama yok.

## Önce (baseline)

Chromium `measure-before-chromium.json` + `before/`.

| Ekran | pageOv (tüm VP) | Öne çıkan taşma |
|---|---|---|
| COURSE-VIEW | **0** | Öğrenci/eğitmen başlıklar taşmıyor; düzenleme sticky footer `.actions.nav.flex-nowrap` → “Sil” `right≈447` @390 |
| UPLOAD-VIDEO | **0** | F-06: gizli taslak tablosunda “Taslak” `left:362 right:420` @390 (viewport dışı); tablo culprits |
| COURSE-EDIT | **0** | Sayfa taşması yok; form/editör dar ekran için güçlendirildi |

## Uygulama

### COURSE-VIEW (`wam-course.css`)
- Bölüm / etkinlik adları: `overflow-wrap: anywhere`, `min-width: 0`
- Gömülü `img/video/iframe` + özet tabloları: `max-width: 100%`, yerel `overflow-x: auto`
- Düzenleme sticky footer: Bootstrap `.flex-nowrap !important` aşımıyla `flex-wrap: wrap !important`; dış `overflow-x: hidden`
- Sürükleme fareye bağlı; **klavye alternatifi yok** (bilgi)

### UPLOAD-VIDEO (`wam-content.css`) — F-06
- Önizleme kartı: `.wam-status` + başlık `flex-wrap` / `max-width: 100%`
- `.wam-draft-table-wrap`: her genişlikte `overflow-x: auto`
- ≤576px: taslak tablosu kart yığını → “Taslak” viewport içinde (`left≈48`)
- Form select/input/hata/dosya adı: `min-width: 0`; dosya seç düğmesi ≥44px

### COURSE-EDIT (`wam-forms.css`)
- `mform` satırları / TinyMCE / filepicker / filemanager: `max-width: 100%`, `min-width: 0`
- Yardım/popover: `max-width: min(320px, 100vw − gutter)`; fitem `overflow: visible`

### RF10 secondary nav kırpma
- ADM-SETTINGS / ADM-APPEARANCE: dış `overflow-x: hidden`, iç `.navigation` `overflow-x: auto`, pageOv **0**; odak ilk/son sekmede OK
- COURSE-VIEW: kurs kabuğu `overflow: visible`; “Daha fazla” menüsü `position: absolute`, kırpılmıyor
- Ek CSS gerekmedi

## Sonra

| Ekran | Chromium 320/390/844×390/820/1180/1440 | WebKit 390/820 |
|---|---|---|
| COURSE-VIEW | pageOv **0**; editing sticky “Sil” kırpılmıyor | **0** |
| UPLOAD-VIDEO | pageOv **0**; Taslak offs **[]** | **0** |
| COURSE-EDIT | pageOv **0** | **0** |

### İşlevsel
- COURSE-EDIT: özet alanına geçici `RF2-AUDIT-TMP` eklendi → kaydet (`#id_saveanddisplay`) → eski HTML geri yazıldı → **summary_clean**
- UPLOAD-VIDEO: `tests_accept_sample.mp4` mevcut; filepicker diyalogu (Dosya yükle) açıldı; headless’ta diyalog kapanmadan tam “Taslak kaydet” E2E **doğrulanmadı** (yalnız layout/F-06 doğrulandı)
- LESSON: `cmid=4` hâlâ `visible=0`

### Regresyon (overflow/header)
CALENDAR-WAM, COURSE-PARTICIPANTS, ACC-EDIT × student/teacher/admin × 390 & 820 → pageOv **0**, header kırpılmıyor.

RF1 compact / RF3 `#wam-course-edit-host` bu turda bozulmadı (regresyon ekranlarında pageOv 0).

## Değişen dosyalar

| Dosya | Etki |
|---|---|
| `css/wam-course.css` | COURSE-VIEW wrap/medya; editing sticky footer |
| `css/wam-content.css` | F-06 durum şeridi + taslak tablo yığını; form min-width |
| `css/wam-forms.css` | COURSE-EDIT dar ekran mform / TinyMCE / filepicker |

## Test matrisi

| Motor | Viewport | Roller |
|---|---|---|
| Chromium | 320, 390×844, 844×390, 820, 1180, 1440 | student (VIEW), teacher, admin |
| WebKit | 390, 820 | aynı |
| Gerçek Safari / cihaz | — | **yapılmadı** |

Kanıt: `before/`, `after/`, `measure-before-chromium.json`, `measure-after-chromium.json`, `measure-after-webkit.json`.

## Engelli / açık

- **LESSON-RESOURCE** öğrenci E2E: örnek yayınlı video yok → matris `ornek-veri-yok-ogrenci`
- UPLOAD tam taslak kayıt E2E (filepicker): doğrulanmadı
- Düzenleme sürükle-bırak: klavye alternatifi yok

## Sonraki öneri (uygulanmaz)

**RF11** — ADM-PLUGINS, ADM-SERVER, ADM-REPORTS, ADM-SECURITY (RF10 şablon doğrulaması).

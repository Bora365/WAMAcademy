# Responsive RF3 — Tablolar

Tarih: 2026-11-18  
Commit/push/deploy: **yapılmadı**  
RF1 header (`wam-nav-compact`) korundu. Not kaydetme, kayıt silme, rol değiştirme tıklanmadı.

## Kapsam

| Ekran | Rota | Roller | Tür |
|---|---|---|---|
| COURSE-GRADES | `/grade/report/grader/index.php?id=2` | teacher, admin | Moodle grader |
| COURSE-PARTICIPANTS | `/user/index.php?id=2` | teacher, admin | `wam-course-participants` |
| SITE-PARTICIPANTS | `/user/index.php?id=1` | admin | aynı tablo + `wam-site-participants` |
| ADM-SYSUSERS | `/admin/user.php` | admin | `wam-users-list` reportbuilder |

**RF2 durumu:** uygulanmadı. Açık kapsam: COURSE-VIEW, LESSON-RESOURCE, UPLOAD-VIDEO, COURSE-EDIT (`04-FAZ-PLANI.md`).

## Önce (baseline)

Chromium, `measure-before-chromium.json` + `before/`.

| Ekran | Viewport | pageOv | wrapOv (sarmalayıcı) | Taşan düğüm / kural |
|---|---|---|---|---|
| COURSE-GRADES | 390 | **+896** | 912 (`.gradeparent`, `overflow-x: visible`) | `table.gradereport-grader-table` ~1270 px; iç kaydırma bilerek kapalıydı |
| COURSE-GRADES | 320 | **+966** | 982 | aynı |
| COURSE-PARTICIPANTS | 320 | **+56/+57** | 802 (`.table-responsive` zaten `auto`) | `#formactionid` select ~352 px + sütun `min-width` (ad 200 / e-posta 250) |
| COURSE-PARTICIPANTS | 390 | 0 | 732 | menü `fixed`, kırpma yok |
| SITE-PARTICIPANTS | 320 | **+9** | 671 | aynı katılımcı sarmalayıcı; secondary nav `min-width:100%` adayı |
| ADM-SYSUSERS | 320–1440 | 0 | tablo `.table-responsive` içinde | `th.c1/c2/c3` min-width 240/260/140; sayfa taşması yoktu |
| ADM-SYSUSERS | öğrenci | — | — | URL `/admin/user.php` kalır; içerik **Erişim reddedildi** (`denied-ok`) |

Kişisel adlar ekran görüntülerinde maskelenmedi (yerel kanıt); raporda ad yazılmaz.

## Uygulama

### COURSE-GRADES
- `.gradeparent`: `max-width:100%; overflow-x:auto; min-width:0`
- Üst zincir `#region-main` / `.main-inner` / `#page-content`: `min-width:0; overflow-x:visible` (sayfa `overflow-x:hidden` yok)
- Tertiary / header flex satırları `flex-wrap`
- Sticky ilk sütun sarmalayıcı içinde çalışıyor (`position:sticky; left:0`, yatay kaydırınca `left≈16` sabit) — bırakıldı
- Sütun gizlenmedi

### Compact düzenleme anahtarı (RF1 etkileşimi)
- 320’de navbar’daki `.editmode-switch-form` compact ile gizleniyordu; Moodle kurs gövdesinde ikinci anahtar yoktu
- `wam_menu.js`: compact + `wam-course-grades` / `wam-course-participants` iken anahtar `#wam-course-edit-host` (tertiary altı) içine taşınır; compact kalkınca navbar’a döner
- Doğrulama: 320 admin grades — `hostHasForm: true`, `pageOv:0`, `navOv:0`

### Katılımcılar (kurs + site)
- Üst zincir `min-width:0`; mevcut `.table-responsive` korundu
- `#formactionid` / toplu select: `max-width:100%; min-width:0`
- Checkbox hücre/input: `min-width/min-height: 40px`
- Menü: mevcut `participants_table.js` viewport kelepçesi (sağ/alt ters açılma) yeterli; unenroll/rol tıklanmadı
- Site bağlam sınıfı `wam-site-participants` doğrulandı; ayrı rol menüsü yok

### ADM-SYSUSERS
- Aynı sarmalayıcı deseni + filtre paneli `max-width:100%`, etiket `overflow-wrap`
- Checkbox 40px
- “Yeni kullanıcı” / satır menüsü açıldı, gönderilmedi
- Hesap oturumları (`ACC-SESSIONS`) bu tablo sınıfını kullanmıyor → dokunulmadı

## Sonra

Chromium `measure-after-chromium.json` + `after/`; WebKit `measure-after-webkit.json`.

| Ekran | Viewport | pageOv önce → sonra | wrapOv (yerel) | Menü |
|---|---|---|---|---|
| COURSE-GRADES | 390 | 896 → **0** | 912 | — |
| COURSE-GRADES | 320 | 966 → **0** | 982 | — |
| COURSE-GRADES | 820 / 1180 | 570/218 → **0** | 594/250 | — |
| COURSE-PARTICIPANTS | 320 | 56 → **0** | ~829 | açık, clip yok |
| COURSE-PARTICIPANTS | phoneLand kaydırılmış | — | — | son satır menü clip yok |
| SITE-PARTICIPANTS | 320 | 9 → **0** | ~698 | tetikleyici bu veri setinde yok / açılmadı |
| ADM-SYSUSERS | tüm VP | 0 → **0** | yerel kaydırma | clip yok |
| ADM-SYSUSERS | filtre 390→820 | — | — | pageOv 0 |
| WebKit grades/participants | 390, 820 | pageOv **0** | yerel >0 | menü clip yok |

## RF1 regresyon

| Ekran | VP | pageOv | not |
|---|---|---|---|
| CALENDAR-WAM | 390 | 0 | Bugün clipped: false |
| ACC-SESSIONS | 390 | 0 | |
| ACC-EDIT (profil formu) | 390 / 1440 | 0 | |

## Değişen dosyalar

| Dosya | Ne |
|---|---|
| `public/local/wamakademi/css/wam-course.css` | Grader yerel kaydırma; katılımcı zincir/select/checkbox; edit-host |
| `public/local/wamakademi/css/wam-users.css` | Sysusers sarmalayıcı + filtre max-width + checkbox |
| `public/local/wamakademi/js/wam_menu.js` | Compact’ta kurs tablo ekranlarına edit switch taşıma |
| `docs/ui-audit/responsive/RF3/*` | Ölçüm aracı, before/after, bu rapor |

## Test matrisi

| Motor | Kapsam |
|---|---|
| Chromium | 4 ekran × roller × 320, 390, 844×390, 820, 1180, 1440 + filtre resize + kaydırılmış menü |
| WebKit | grader + katılımcılar @ 390 ve 820 |
| Gerçek Safari / klavye | **yapılmadı** |

## Sonraki grup önerisi

RF3 sonrası taze ölçüme göre **önce F-04** (doğrulanmış taşma): ADM-APPEARANCE, ADM-SETTINGS, ADM-THEME, ADM-GRADES.  
**RF2** (COURSE-VIEW, LESSON-RESOURCE, UPLOAD-VIDEO, COURSE-EDIT) veri/akış borcu olarak açık kalır.

# Responsive RF13 — Yükleme ve rapor oluşturucu

Tarih: 2026-11-18  
Faz: **RF13**  
Durum: **RF13 — dört ekran tamamlandı**  
Commit/push/deploy: **yapılmadı**  
Kullanıcı/kurs yükleme gönderimi ve rapor kaydı: **yapılmadı** (bilinçli)

## Seçilen ekranlar

Plan RF13; RF11/RF12 yeniden sayılmadı.

| Kimlik | Rota | Gerekçe |
|---|---|---|
| ADM-USER-NEW | `/user/editadvanced.php?id=-1` | Yeni kullanıcı mform; şifre unmask taşması |
| ADM-UPLOADUSER | `/admin/tool/uploaduser/index.php` | CSV kullanıcı yükleme + filepicker |
| ADM-UPLOADCOURSE | `/admin/tool/uploadcourse/index.php` | Kurs CSV yükleme; WebKit select sızıntısı |
| ADM-REPORTBUILDER | `/reportbuilder/index.php` | Rapor listesi / oluşturucu kabuğu |

## Önce (baseline)

Chromium `measure-before-chromium.json` + `before/`.

| Ekran | 320 | 390 | Not |
|---|---|---|---|
| ADM-USER-NEW | **+50** | 0 | `[data-passwordunmask="edit"]` sabit `width:280px` + göz ikonu |
| ADM-UPLOADUSER | 0 | 0 | — |
| ADM-UPLOADCOURSE | 0 | 0 | Chromium OK; WebKit sonra +44 |
| ADM-REPORTBUILDER | 0 | 0 | — |

## Uygulama

### `wam-users.css` (ADM-USER-NEW)
- Şifre unmask: `width:280px` / `max-width:360px` → `%100` + `flex-wrap` + `min-width:0`
- mform / TinyMCE / filemanager: `max-width:100%`

### `wam-admin.css` (upload + reportbuilder)
- uploaduser / uploadcourse: fitem/felement `overflow-x:auto` (WebKit select `scrollWidth` sızıntısı, RF10 deseni)
- select `width/max-width:100%`
- reportbuilder sarmalayıcı: yerel `overflow-x:auto`

## Sonra

| Ekran | Chromium 320…1440 | WebKit 390…1440 |
|---|---|---|
| ADM-USER-NEW | pageOv **0** (320: 50→0) | **0** |
| ADM-UPLOADUSER | **0** | **0** |
| ADM-UPLOADCOURSE | **0** | **0** (390 WebKit 44→0) |
| ADM-REPORTBUILDER | **0** | **0** |

### Yükseklik (phone390, kaydedildi)

| Ekran | `innerHeight` | `scrollHeight` |
|---|---|---|
| ADM-USER-NEW | 844 | ~3560 |
| ADM-UPLOADUSER | 844 | ~1504 |
| ADM-UPLOADCOURSE | 844 | ~1640 |
| ADM-REPORTBUILDER | 844 | ~899 |

### Akışlar
- USER-NEW: `firstname` doldur/temizle (kayıt yok); odak username OK
- UPLOADUSER / UPLOADCOURSE: filepicker diyalogu açıldı, Escape ile kapatıldı (dosya gönderilmedi)
- REPORTBUILDER: “Yeni rapor” odak/tıklama; kayıt yok
- Odak kırpılması yok (Chromium+WebKit)

### Regresyon
ADM-APPEARANCE, ADM-PLUGINS, COURSE-PARTICIPANTS, COURSE-EDIT × Chromium+WebKit × 390/820 → pageOv **0**

## Değişen dosyalar

| Dosya | Etki |
|---|---|
| `css/wam-users.css` | USER-NEW şifre unmask + form max-width |
| `css/wam-admin.css` | uploaduser/course WebKit select; reportbuilder wrap |
| `docs/ui-audit/responsive/RF13/*` | Ölçüm, before/after, akış, bu rapor |

## Açık işler (korundu)

- RF2 UPLOAD-VIDEO E2E taslak kayıt: doğrulanmadı
- LESSON-RESOURCE öğrenci: `cmid=4` `visible=0` → `ornek-veri-yok-ogrenci`
- Bu turda gerçek CSV yükleme / kullanıcı oluşturma / rapor kaydetme yok

## Matris sayıları (güncelleme sonrası, 164)

| Durum grubu | Adet |
|---|---|
| Doğrulanan / uygulanan | **27** (+4 RF13) |
| Ölçüldü (`olculdu`) | **27** |
| Keşif (`kesfedildi-olculmedi`) | **109** |
| Veri engeli | **1** |

## Sonraki öneri (uygulanmaz)

**RF14** — COURSE-COMPLETION, PPTX-VIEW, ADM-UNSUPPORTED  
veya **RF4** hesap hattı (ACC-PREFS / ACC-EDIT / ACC-EDIT-ADV / ACC-PASSWORD) — kişisel form kabuğu hâlâ `olculdu`.

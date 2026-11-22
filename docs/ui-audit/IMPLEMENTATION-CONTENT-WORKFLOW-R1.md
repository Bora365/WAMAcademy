# İçerik iş akışı — uygulama raporu (Content Workflow R1)

Bağlantılı: [`APPLICATION-OVERVIEW.md`](../APPLICATION-OVERVIEW.md) · ekran görüntüleri: [`screenshots/content-workflow-r1/`](screenshots/content-workflow-r1/).

**Kısıtlar:** commit / push / deploy yok. Önceki UI (User/Course R1) korundu.

## 1. Analiz doğrulaması (kod + çalışan kurulum)

| İddia (önceki özet) | Doğrulama |
|---|---|
| Moodle 5.2 + Boost + `local_wamakademi` | **Doğru** — 5.2.2+ (Build 20260818) |
| Kurs oluşturma yalnızca Site yönetimi’nden; menüde yok | **Doğruydu** — menüye **Kurs oluştur** eklendi |
| Upload `js/upload.php`’de; create_module ile gizli taslak | **Doğru** — taşındı `/local/wamakademi/upload.php`; eski yol GET yönlendirmesi |
| `access` sınıfı / `createdraft` yok; belirsiz fallback | **Doğru** — `classes/access.php` + `db/access.php` (`managevideos`) eklendi; fallback kaldırıldı |
| Yayın kapalı (`publishenabled=0`) | **Doğruydu** — `publish.php` + gerçek `set_coursemodule_visible` |
| `save_progress` + %95 | **Doğru**; canlı DB’de `watchedseconds`/`watchedranges` **eksikti** (upgrade 2604/2605 uygulanmamış) → **2026082607** ile eklendi |

## 2. Değişen dosyalar

| Dosya | Değişiklik |
|---|---|
| `public/local/wamakademi/upload.php` | **Yeni** — form, taslak, önizleme, çift gönderim jetonu |
| `public/local/wamakademi/publish.php` | **Yeni** — POST+sesskey yayımla/yayından kaldır |
| `public/local/wamakademi/js/upload.php` | İnce GET yönlendirici (POST işlem yapmaz) |
| `public/local/wamakademi/classes/access.php` | **Yeni** — `can_manage_videos`, `can_create_course`, `course_create_url` |
| `public/local/wamakademi/classes/video_status.php` | **Yeni** — Taslak / Yayında / kısıtlı (bölüm/kurs/availability) |
| `public/local/wamakademi/db/access.php` | **Yeni** — `local/wamakademi:managevideos` |
| `public/local/wamakademi/db/install.xml` | `watchedseconds`, `watchedranges` alanları |
| `public/local/wamakademi/db/upgrade.php` | Savepoint **2026082607** (eksik sütunlar + `update_capabilities`) |
| `public/local/wamakademi/version.php` | `2026082607` / `0.1.1` |
| `public/local/wamakademi/css/wam-content.css` | **Yeni** — yükleme/önizleme yüzeyi |
| `public/local/wamakademi/lib.php` | Menü: Kurs oluştur, Video yükle; course config `uploadurl`; content CSS |
| `public/local/wamakademi/js/course_ui.js` | Kurs sayfasında Video yükle kısayolu |
| `public/local/wamakademi/save_progress.php` | Süre tutarlılığı, geriye gitmeyen progress, gizli etkinlikte yazma yok |
| `public/local/wamakademi/lang/{tr,en}/local_wamakademi.php` | İçerik dizeleri |

## 3. Yetkiler ve endpoint’ler

**Capability:** `local/wamakademi:managevideos` (CONTEXT_COURSE; arketip: editingteacher, manager).  
Ek zorunlu Moodle yetkileri: `moodle/course:manageactivities`, `mod/resource:addinstance`, `moodle/course:activityvisibility`.

| Endpoint | Yöntem | İş |
|---|---|---|
| `/local/wamakademi/upload.php` | GET/POST | Form + taslak oluşturma (`create_module`, visible=0) |
| `/local/wamakademi/js/upload.php` | GET → redirect | Uyumluluk |
| `/local/wamakademi/publish.php` | **yalnızca POST** + sesskey | `action=publish\|unpublish` |
| `/local/wamakademi/save_progress.php` | POST + sesskey | İlerleme (öğrenci; eğitmen önizlemesinde yazılmaz) |

**Şema:** yeni tablo yok. `local_wamakademi_progress`’e eksik sütunlar eklendi.

## 4. Ekranlar

| Ekran | Rol | Not |
|---|---|---|
| Video yükle (form) | admin, öğretmen | Eğitim kilitli (courseid ile), bölüm, ders adı, MP4, Taslak kaydet / İptal |
| Önizleme (taslak) | admin, öğretmen | Rozet **Taslak**; Düzenle / Yayımla / Eğitime dön |
| Önizleme (yayında) | admin, öğretmen | Rozet **Yayında**; Yayından kaldır |
| Kurs oluştur | admin | Moodle `/course/edit.php?category=` |
| Öğrenci upload | öğrenci | Red: “Video yükleyebileceğiniz bir eğitim bulunamadı.” |
| Öğrenci taslak ders | öğrenci | Görünmez; view kursa düşer |

## 5. Test sonuçları

### 5.1 İşlevsel / veritabanı (ayrı)

| Senaryo | Sonuç |
|---|---|
| Admin/öğretmen `can_manage_videos(c2)` | Evet; öğrenci hayır |
| Admin `can_create_course`; öğrenci hayır; öğretmen create URL → Hata | Evet |
| Taslak `create_module` → `course_modules.visible=0` | Evet (cmid=4) |
| Öğrenci uservisible taslak | Hayır |
| POST publish → visible=1; öğrenci açar | Evet |
| POST unpublish → visible=0; öğrenci kursa düşer | Evet |
| GET publish | “Geçersiz istek” |
| Bozuk sesskey publish | Hata |
| `save_progress` öğrenci: watched=30, progress korunur | Evet |
| Sona seek, boş ranges → watched artmaz; currenttime=99 | Evet |
| Eski `js/upload.php?courseid=2` → yeni URL | Evet |
| Çift gönderim jetonu | Kodda; oturum `$SESSION->wam_upload_tokens` |

**Not:** Test videosu 288 baytlık minimal MP4; oynatıcı “biçim desteklenmiyor” gösterebilir. Erişim/görünürlük/yayımlama gerçek Moodle API ile doğrulandı.

### 5.2 Görsel (ayrı)

Genişlikler 1440 / 1024 / 768 / 390; `scrollWidth = viewport` (yatay taşma yok). İncelenen görüntüler: `admin-upload-*`, `admin-preview-draft-1440`, `admin-preview-published-1440`, `admin-upload-390`, `student-upload-denied-1440`, öğretmen seti.

| Kontrol | Sonuç |
|---|---|
| Form alanları / Taslak kaydet / İptal | Görüldü |
| Önizleme rozeti + Yayımla / Yayından kaldır | Görüldü |
| Öğrenci red mesajı | Görüldü |
| Klavye / disabled gönderim | Form submit’te `wam-upload-busy` + düğme disabled |

## 6. Kalan eksikler

1. Gerçek uzun MP4 ile oynatma/kalite testi yapılmadı (yalnızca erişim zinciri).
2. Moodle activity completion eşlemesi yok.
3. Çift gönderim jetonunun tarayıcıda çift tıklama senaryosu manuel; otomatik E2E form upload (gerçek dosya) çalıştırılmadı.
4. `purge_caches` / eklenti yükseltmesi dağıtımda: `php admin/cli/upgrade.php` (veya yönetim UI) + lang/theme purge.
5. Test cmid=4 yerelde bırakıldı (taslak); istenirse silinebilir.

## 7. Dağıtım (yapılmadı)

```bash
php admin/cli/upgrade.php --non-interactive --allow-unstable
php admin/cli/purge_caches.php --lang --theme
```

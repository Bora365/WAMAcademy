# WAM Akademi — Uygulama Özeti

WAM Akademi, **Moodle 5.2.2+ (Boost teması)** üzerine kurulu bir **eğitim videosu platformudur**. Moodle çekirdeği kullanıcı, kurs, kayıt, yetki ve içerik altyapısını sağlar; `public/local/wamakademi` eklentisi arayüzü ve video akışını özelleştirir.

Doğrulama tarihi: Content Workflow R1 (kod + çalışan yerel kurulum).

## 1. Kim ne yapabilir

| Rol | Yapabildikleri |
|---|---|
| **Site yöneticisi** | Her şey: kurs, kullanıcı, rol, ayar, rapor, video yükleme/yayımlama |
| **Eğitmen** (kursta *Öğretmen* / editingteacher) | Kendi kursunun içeriği, ayarları, katılımcıları; **Video yükle** (capability: `local/wamakademi:managevideos` + Moodle etkinlik yetkileri) |
| **Öğrenci** | Kayıtlı olduğu kurslardaki **yayımlanmış** videoları izler; ilerleme `local_wamakademi_progress` tablosuna yazılır |

## 2. Neyin nereden yapıldığı

| İş | Nereden | Not |
|---|---|---|
| **Kurs oluşturma** | WAM kullanıcı menüsü → Yönetim → **Kurs oluştur** veya Site yönetimi → Kurslar → Yeni kurs ekle | Hedef: `/course/edit.php?category=<id>` (yetki: `moodle/course:create`). Menü bağlantısı yalnızca yetkili kullanıcıya görünür |
| Kurs kategorisi | `/course/management.php` | Katalogdaki düzey filtresi kategori adından türetilir |
| Kurs ayarı | Kullanıcı menüsü → **Kurs ayarları** (`/course/edit.php?id=`) | `moodle/course:update` |
| **Video yükleme** | Kullanıcı menüsü → **Video yükle** (eğitim bağlamında) veya kurs sayfasındaki kısayol | `/local/wamakademi/upload.php?courseid=&sectionnum=` — MP4, gizli taslak (`visible=0`). Eski yol `/local/wamakademi/js/upload.php` GET ile yeni adrese yönlendirir |
| Taslak önizleme / yayımla | Aynı yükleme sayfasında `previewcmid` | **Yayımla** / **Yayından kaldır**: POST `/local/wamakademi/publish.php` + sesskey; gerçek `set_coursemodule_visible()` |
| İçerik ekleme (standart) | Kurs → düzenleme modu → Dosya (`mod_resource`) | Alternatif yol |
| **Kullanıcı hesabı** | Sistem kullanıcıları → **Yeni kullanıcı ekle** | Hesap açar; kursa kaydetmez |
| **Kursa kayıt** | Kurs → Katılımcılar → **Katılımcı ekle** | Var olan hesabı kaydeder + rol |

## 3. Öğrencinin akışı

1. Giriş → Ana Sayfa / Eğitimler / Eğitimlerim.
2. Kurs sayfası → konulara ayrılmış ders listesi (`get_course_lessons.php` yalnızca `uservisible` etkinlikleri döner).
3. Ders (`mod/resource/view.php`) → WAM oynatıcı; sıralı kilit **yok**.
4. İzleme: `save_progress.php` (sesskey, yalnızca `$USER`, görünür etkinlik). İlerleme = birleştirilmiş izleme aralıkları; **%95 tamamlandı**. Sona atlamak (seek) aralık üretmezse yüzde artmaz; `currenttime` kaldığı konumdur.
5. Takvim, mesajlar, hesabım.

## 4. Eğitmenin akışı

1. Yönetici hesabı açar ve kursa Öğretmen olarak kaydeder (`local/wamakademi:managevideos` arketip + kurs yetkileri).
2. **Video yükle** → eğitim/bölüm/ders adı/MP4 → **Taslak kaydet** → gizli `mod_resource` + önizleme.
3. Önizlemede **Yayımla** → öğrenci görür. **Yayından kaldır** → öğrenci göremez.
4. Katılımcılar ekranından öğrencileri yönetir.

## 5. Teknik harita

| Parça | Yer |
|---|---|
| Çekirdek | Moodle 5.2.2+ (`public/`), Boost |
| Eklenti | `public/local/wamakademi/` |
| Sayfalar | `index.php`, `catalog.php`, `mycourses.php`, `calendar.php`, **`upload.php`**, **`publish.php`** |
| Yetki | `db/access.php` → `local/wamakademi:managevideos`; `classes/access.php` |
| İlerleme | `save_progress.php` / `get_progress.php` → tablo `local_wamakademi_progress` (watchedseconds, watchedranges dahil) |
| UI | `css/wam-base.css`, `wam-course.css`, `wam-forms.css`, `wam-content.css`, `wam-users.css` |
| Menü | Ana Sayfa, Eğitimler, Eğitimlerim, Takvim; Yönetim: Kurs ayarları, Video yükle, Kurs oluştur, Site yönetimi |

## 6. Bilinen sınırlar

- Test ortamında oluşturulan çok küçük sahte MP4 oynatıcıda “biçim desteklenmiyor” gösterebilir; gerçek MP4 ile oynatma Moodle media API’sine bağlıdır.
- Moodle etkinlik tamamlanması (`completion`) bu turda entegre edilmedi (`completion = 0`); WAM ilerlemesi ayrıdır.
- Sıralı ders kilidi yoktur.

# Hesap / ayarlar kapsam envanteri — Faz 4 kapanış

Tarih: 2026-11-16 (kapanış kontrolü)  
Ortam: lokal `http://localhost:8081`  
Roller: `wamtest_egitmen` (id=4), `wamtest_ogrenci` (id=3)  
Commit/push/deploy: **yapılmadı**

Durum: **tamamlandı** | **görsel tamam** | **sınır** | **kapalı** | **erişim/test engeli** | **ortak hatta**

## Kapanış özeti — rol başına sayılar

| Rol | Bulunan (erişilebilir kişisel) | Doğrulanan (1440 ve/veya 390 + etkileşim veya bilinçli view-only) | Açık kalan |
|---|---:|---:|---:|
| Eğitmen | 18 | 18 | 3 madde (aşağıda) |
| Öğrenci | 18 | 18 | 3 madde (aşağıda) |

Açık kalan maddeler her iki rolde ortak (kurulum / bilinçli test sınırı):

1. **Rozet detay** (`/badges/badge.php`) — rozet yok; alt ekran açılamadı.  
2. **Şifre kaydet / oturum sonlandır** — yalnızca form/görünüm; işlem tamamlanmadı (bilinçli).  
3. **Backpack bağlan** — sağlayıcı select görüldü; bağlan/şifre gönderilmedi.

## Çark menüsü (canlı keşif)

Her iki rolde aynı kişisel yapı (Takvim/Raporlar sınır):

| Öğe | Hedef | Tip | T | S |
|---|---|---|---|---|
| Profil | `/user/profile.php?id={self}` | profil | ✓ | ✓ |
| Profil düzenle | `/user/edit.php?id={self}&returnto=profile` | form | ✓ | ✓ |
| Kişisel dosyalar | `/user/files.php` | dosya | ✓ | ✓ |
| Başarı notları | `/grade/report/overview/index.php` | tablo | ✓ | ✓ |
| Takvim | `/local/wamakademi/calendar.php` (+ Moodle ay) | sınır | sınır | sınır |
| Raporlar | `/reportbuilder/index.php` | sınır | sınır | sınır |
| Tercihler | `/user/preferences.php` | hub | ✓ | ✓ |
| Dil en/tr | `?lang=` | dil | ✓ | ✓ |
| Tarayıcı oturumları | `/report/usersessions/user.php` | tablo | ✓ | ✓ |
| Çıkış | `/login/logout.php` | oturum | görüldü | görüldü |

## Tercihler bağlantıları → hedef + alt ekran

| Bağlantı | Hedef | Kişisel alt | T 1440 | T 390 | S 1440 | S 390 | Etkileşim |
|---|---|---|---|---|---|---|---|
| Profil düzenle | `/user/edit.php?id={self}` | Genel/Resim/İlgi… accordion | ✓ önce/sonra | ✓ son alan+aksiyon | ✓ | (önceki tur) | accordion, select TR, tags klavye, invalid |
| Şifre değiştir | `/login/change_password.php?id=1` | — | ✓ kabuk+form | ✓ | ✓ | ✓ | **view-only**; kaydet yok. Disabled: diğer oturumları kapat |
| Tercih edilen dil | `/user/language.php` | — | ✓ | — | ✓ fetch/kabuk | — | select (kaydet yok) |
| Editör tercihleri | `/user/editor.php` | — | ✓ kabuk | — | ✓ | — | select TinyMCE (kaydet yok) |
| Forum tercihleri | `/user/forum.php` | Forum izleme fieldset | ✓ kabuk | — | ✓ | ✓ | digest select; bir select **disabled** |
| İleti tercihleri | `/message/edit.php` | — | ✓ kabuk | — | ✓ | — | boş içerik (yalnız başlık+geri) |
| Bildirim tercihleri | `/message/notificationpreferences.php` | — | ✓ | — | ✓ | — | toggle flip→restore (T) |
| Takvim tercihleri | `/user/calendar.php` | — | ✓ kabuk | — | ✓ | — | select’ler açık (kaydet yok) |
| İçerik bankası | `/user/contentbank.php` | — | ✓ kabuk | — | ✓ | — | görünürlük select |
| Rozetleri yönet | `/badges/mybadges.php` | backpack link; badge detay yok | ✓ | — | ✓ | — | arama UI; boş liste |
| Rozet tercihleri | `/badges/preferences.php` | — | ✓ | — | ✓ | — | gizlilik checkbox tık (kaydet yok) |
| Rozet paketi | `/badges/mybackpack.php` | bağlan formu | ✓ | — | ✓ | — | sağlayıcı select; **bağlan yok** |

## Diğer kişisel (çark)

| Ekran | T 1440 | T 390 | S 1440 | S 390 | Etkileşim |
|---|---|---|---|---|---|
| Dosyalar | ✓ + filepicker | — | ✓ kabuk | — | filepicker açıldı |
| Oturumlar | ✓ Bu oturum | — | ✓ Bu oturum | — | tablo; **sonlandırma yok** |
| Başarı notları | ✓ | — | ✓ tablo/kurs | — | tablo görünümü |
| Profil | ✓ kabuk | — | ✓ | — | görüntü |

## Bu kapanışta bulunan / düzeltilen hesap kusuru

**Şifre değiştir** sayfası `?id=1` (kurs=SITEID) yüzünden `local_wamakademi_account_target_userid()` yanlışlıkla hedef kullanıcıyı 1 sanıyordu → `wam-account` uygulanmıyordu.

Düzeltme: `lib.php` içinde `change_password.php` için id kurs kabul edilip hedef = mevcut kullanıcı.  
Sonra: Tercihlere dön + form kartı her iki rolde 1440/390.

## Kapalı kurulum

| Özellik | Kanıt |
|---|---|
| Blog / Portfolyo / MFA / OAuth / WS token / DPO | önceki envanter; etkin değil |

## Ortak UI hattı (2 bulgu)

1. [COMMON-UI-HANDOFF-P4-FILEPICKER-CONTRAST.md](COMMON-UI-HANDOFF-P4-FILEPICKER-CONTRAST.md) — seçili depo 1.04:1  
2. [COMMON-UI-HANDOFF-P4-AUTOCOMPLETE.md](COMMON-UI-HANDOFF-P4-AUTOCOMPLETE.md) — klavye `isg` geçti; panel otomasyonu güvenilmez  

## Görseller (kapanış ekleri)

`docs/ui-audit/screenshots/account-p4/`

- `password-student-1440-phase4-close.png`, `password-student-390-phase4-close.png`, `password-teacher-390-phase4-close.png`
- `editor-student-1440-phase4-close.png`, `forum-student-1440-phase4-close.png`
- `filepicker-selected-repo-contrast-phase4-close.png`
- Önceki tur: profil önce/sonra, prefs, language, files, sessions, …

## Kod (commit yok)

| Dosya | Ne |
|---|---|
| `public/local/wamakademi/lib.php` | `change_password` hedef kullanıcı düzeltmesi |
| (önceki tur) `account_ui.js` / `account.css` | profil kabuğu, sticky, fieldset |

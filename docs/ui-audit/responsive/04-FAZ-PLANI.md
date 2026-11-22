# Responsive Faz 1 — Uygulama faz planı

Her faz **3–4 gerçek ekran**. Ortak bileşen sahipliği: kabuk/header → erken RF + Site yönetimi hattı; kişisel → hesap hattı (RF4–RF8).  
Bu belgede sonraki fazlar için uygulama promptu **üretilmez**.

## İlk grup neden RF1?

Ölçülen P0’lar arasında **CALENDAR-WAM** (F-02) bu gruptadır; HOME/CATALOG/MYCOURSES ise tüm rollerin günlük girişi ve menü/hamburger doğrulamasıdır. Ortak `wam-base` / `styles.css` / menü eşikleri burada düzeltilmeden sonraki kurs ve admin düzeltmeleri regress olur. Kanıt: takvim +33…103 px taşma (Chromium + WebKit 390); kabuk iPad’de sağ menü taşması (F-05) aynı header ailesi.

---

## RF1 — Öğrenci kabuğu (ilk uygulama)

| Ekran | İş | Sorunlar | Muhtemel dosyalar |
|---|---|---|---|
| HOME-WAM | Menü aç, ana sayfayı kullan | F-05 (iPad navbar) | `wam-base.css`, `wam_menu.js`, `wam_header.js`, `styles.css` |
| CATALOG | Ara / filtrele | Kabuk + liste | `styles.css`, `catalog.php`, `wam-controls.css` |
| MYCOURSES | Kayıtlı eğitimler | Kabuk | `mycourses` şablon/CSS |
| CALENDAR-WAM | Ay gez / Bugün | **F-02 P0** | `styles.css` ~2207–2241, `account.css` calendar |

**Ortak bileşen:** navbar + hamburger + `wam-menu-panel` — **ilk düzeltme burada**. Sonraki tüm fazlar menüyü yeniden doğrular.  
**Geçme:** 320/390/820/1440 overflowX≤2; Bugün görünür; 820’de kullanıcı menüsü tıklanır; menü açıkken 390→820 durumu bilinçli.

---

## RF2 — Kurs ve içerik

| Ekran | İş | Sorunlar | Dosyalar |
|---|---|---|---|
| COURSE-VIEW | Derse gir | Secondary nav | `wam-course.css`, `course_nav.js` |
| LESSON-RESOURCE | İçerik kullan | Veri: öğrenci taslak göremez; layout `lesson.css` | `lesson.css`, `lesson_layout.js` |
| UPLOAD-VIDEO | Forma ulaş | F-06 | `wam-content.css`, `upload.php` |
| COURSE-EDIT | Alan / editör | Form kabuğu | `wam-forms.css`, `wam-controls.css`, `course_ui.js` |

**Bağımlılık:** RF1 menü. LESSON için yayınlı örnek video gerekir (ortam).  
**Geçme:** Eğitmen yükleme şeridi taşmaz; ders ≤991 tek kolon; öğrenci yayınlı derste oynatıcı kullanır.

---

## RF3 — Tablolar (kritik)

| Ekran | İş | Sorunlar | Dosyalar |
|---|---|---|---|
| COURSE-GRADES | Not işle | **F-01 P0** | grader sarmalayıcı, `wam-course.css` |
| COURSE-PARTICIPANTS | Satır menüsü | **F-03** | `wam-course.css`, `participants_table.js` |
| SITE-PARTICIPANTS | Site tablo | Aynı aile | `wam-users` / course |
| ADM-SYSUSERS | Filtre / satır | Tablo min-width | `wam-users.css`, `users_ui.js` |

**Ortak:** tablo sarmalayıcı deseni — burada tanımlanır; RF13 raporlar doğrular.  
**Geçme:** 390/820’de sayfa overflowX=0; İşlemler açılır (yerel kaydırma OK).

---

## RF4 — Hesap hub + düzenleme

| Ekran | İş | Sorunlar | Dosyalar |
|---|---|---|---|
| ACC-PREFS | Hub gezin | — | `account.css`, `account_ui.js` |
| ACC-EDIT | Son alan / aksiyon | Klavye vekili | `account.css` sticky |
| ACC-EDIT-ADV | Admin form | Aynı | aynı |
| ACC-PASSWORD | Form görüntüle | — | `lib.php` matcher (zaten düzeltilmiş) |

**Sahiplik:** hesap hattı. Tasarım referansı: P4 kabuk + COMMON-CONTROLS.  
**Geçme:** 390’da son alan + Kaydet bölgesi görünür (gönderilmeden); 820/1440 regresyon yok.

---

## RF5 — Profil / dosya / oturum / notlar

ACC-PROFILE, ACC-FILES, ACC-SESSIONS, ACC-GRADES — filepicker/ortak kontrol el sıkışması; tablo wrap doğrulama.

## RF6 — Tercih formları (select)

ACC-LANGUAGE, ACC-EDITOR, ACC-FORUM, ACC-NOTIF-PREFS — `wam-controls` + account form; RF1/RF4 sonrası.

## RF7 — İleti / takvim prefs / rozet listesi

ACC-MESSAGE-PREFS, ACC-CAL-PREFS, ACC-CONTENTBANK, ACC-BADGES.

## RF8 — Rozet prefs / backpack / mesajlar / giriş

ACC-BADGE-PREFS, ACC-BACKPACK, MESSAGES, AUTH-LOGIN — login.css 575 eşiği; mesaj AMD.

## RF9 — Admin giriş

DASH-MY, ADM-SEARCH, ADM-USERS, ADM-COURSES — `wam-admin.css`, admin nav; RF1 header doğrulaması.

## RF10 — Görünüm ve ayar formları

ADM-APPEARANCE, ADM-SETTINGS, ADM-THEME, ADM-GRADES — **F-04**; secondary nav + mform.

## RF11 — Eklenti / sunucu / rapor / güvenlik kategorileri

ADM-PLUGINS, ADM-SERVER, ADM-REPORTS, ADM-SECURITY — kategori şablonu; RF9/10 deseni.

## RF12 — Kurs yönetimi formları

ADM-DEVELOPMENT, ADM-COURSE-MGMT, ADM-COURSE-NEW, ADM-CATEGORY-EDIT.

## RF13 — Yükleme ve rapor oluşturucu

ADM-UPLOADCOURSE, ADM-UPLOADUSER, ADM-USER-NEW, ADM-REPORTBUILDER — RF3 tablo sarmalayıcı doğrulama.

## RF14 — Kalan çekirdek

COURSE-COMPLETION, PPTX-VIEW, ADM-UNSUPPORTED (ve varsa boş slot).

## RF15+ — Keşfedilen admin kategori / settings

`02-EKRAN-MATRISI.csv` içinde `ADM-CAT-*` ve `ADM-SET-*` satırları (status: `kesfedildi-olculmedi`).  
Her RF 3–4 kategori veya settings section. Şablon RF9–RF10’da düzeltilir; RF15+ yalnız doğrulama + aykırı section’lar.

Matris fazları RF15–RF42 bu keşif satırlarını kapsar (ayrıntı CSV).

---

## Ortak bileşen sahiplik özeti

| Bileşen | İlk düzelten | Doğrulayan |
|---|---|---|
| Navbar / hamburger / yan panel | RF1 | Tüm fazlar |
| Secondary nav scroll | RF2 + RF10 | RF11–12 |
| Tablo sarmalayıcı | RF3 | RF5, RF13 |
| Account form kabuğu | RF4 | RF5–RF8 |
| Admin kategori grid / mform | RF9–RF10 | RF15+ |

---

## Faz atanmamış / boşluklar

| Madde | Durum |
|---|---|
| Matrisdeki tüm `id` satırları | Faz atanmış (RF1–RF42) |
| `NOTIFICATIONS-POPOVER` | Ölçümde skip (durum); kabuk RF1 ile |
| Keşfedilen ama CSV’ye alınmayan tool alt sayfaları | `discover-admin.json` 440 link; settings/tool derinliği kısmen `ADM-SET-*` (40) — kalanlar **kesfedildi, satır bekliyor** diye `01-KAPSAM` §7 |
| Öğrenci LESSON | Veri engeli |
| Gerçek Safari / safe-area | Doğrulanmadı |
| WebKit | Yalnız CALENDAR-WAM smoke |

---

## Doğrulanmamış kombinasyonlar (açık liste)

- Gerçek iPhone/iPad Safari tüm ekranlar  
- Bölünmüş pencere gerçek iPad Stage Manager (yalnız 507/320×768 vekil)  
- Ekran klavyesi gerçek (yalnız yükseklik vekili)  
- Öğrenci × LESSON-RESOURCE × tüm viewportlar  
- Çoğu `ADM-CAT-*` / `ADM-SET-*` × 390/820/1440  
- Teacher × birçok admin ekranı (yetki yok — bilinçli)  

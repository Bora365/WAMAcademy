# [FAZ 4 — HESAP VE AYARLAR]

Tarih: 2026-11-11 (kabuk) · **2026-11-16 (ortak kontroller doğrulaması)**  
Ortam: lokal `http://localhost:8081`  
Commit / push / deploy: **yapılmadı**

## 2026-11-16 — Ortak kontroller / çark doğrulaması

Kaynak: `IMPLEMENTATION-COMMON-CONTROLS-R1.md` + mevcut `wam-controls.css` / `wam-base.css`.  
Tasarım rehberi `docs/design-reference/WAM-ACCOUNT-UI-GUIDE.md` bu ağaçta yok.

### Profil düzenle (eğitmen yolu)

- Rota: Çark → Tercihler → `/user/edit.php?id=4` (doğrudan çark: `...&returnto=profile`).
- Gri zemindeki beyaz dikdörtgen: üst düzey `fieldset.wam-acc-form-card` (`account.css` surface kartı). `#region-main` şeffaf; alan yüzeyleri korunuyor.
- Düzeltmeler: context header gizlendi; H2 **Profili düzenle**; iç içe filemanager fieldset kart değil; sticky footer static + şeffaf (`bg-white` ezildi); fieldset reveal gerçek açık/kapalıya bakıyor.
- Select uzun TR, accordion, mobil son alan+aksiyon, tags klavye seçimi doğrulandı. Disabled alan yok. Şifre/MFA/oturum öldürme tetiklenmedi.

### Çark ekranları

Tercihler, dil (select), bildirim (toggle restore), dosya+filepicker, oturumlar (Bu oturum) eğitmende; öğrenci profil düzenle + tercihler 390. Ayrıntılı matris: [ACCOUNT-PHASE4-COVERAGE.md](ACCOUNT-PHASE4-COVERAGE.md).

### Ortak hatta iletilen

1. Filepicker seçili depo satırı kontrastı — [COMMON-UI-HANDOFF-P4-FILEPICKER-CONTRAST.md](COMMON-UI-HANDOFF-P4-FILEPICKER-CONTRAST.md) (1.04:1, tekrar adımları var).  
2. Autocomplete öneri paneli — [COMMON-UI-HANDOFF-P4-AUTOCOMPLETE.md](COMMON-UI-HANDOFF-P4-AUTOCOMPLETE.md) (klavye `isg` geçti; panel otomasyonu güvenilmez).

### Kapanış (2026-11-16)

- Çark + Tercihler bağlantıları eğitmen/öğrenci canlı menüyle eşleştirildi; eksik tercihler çocukları (şifre, forum, editör, ileti, takvim prefs, içerik bankası, rozetler) tamamlandı.
- **Düzeltme:** `change_password.php?id=` kurs kimliği → hesap kabuğu uygulanmıyordu; `local_wamakademi_account_target_userid()` güncellendi.
- Şifre/oturum öldürme: yalnızca görüntüleme. Kapanış sayıları: [ACCOUNT-PHASE4-COVERAGE.md](ACCOUNT-PHASE4-COVERAGE.md).

## R2 düzeltmesi (dürüst denetim)

Faz 3 / R2 iddialarından düzeltilenler:

1. **Eğitmen oturumu R2’de gerçekten açılmamıştı.** `preferences-teacher-1440.png` anomali / çok panelli olabilirdi. Faz 4’te `wamtest_egitmen` ile gerçek giriş yapıldı; tercihler ve `user/edit.php` yeniden yakalandı.
2. **1024 / 768 matrisi R2’de yoktu.** Faz 4’te tercihler 1024+768; bildirim / dosya / oturum temsilcileri 1024 ile tamamlandı.
3. **Birçok alt ekran R2’de yalnız “link” düzeyindeydi.** Bu turda change_password (görsel), language, editor, forum, calendar prefs, contentbank, mybadges, grades, notifications toggle, filepicker açıldı.
4. **r2 görüntü anomalileri güvenilmez.** Tüm “sonra” görseller `screenshots/account-p4/` altına yeniden alındı.
5. **Dokümanlardan lokal test şifreleri kaldırıldı** (`IMPLEMENTATION-R2.md`, envanter notu).

## Sayılar

| Metrik | Değer |
|---|---|
| Benzersiz ekran | 22 |
| Tasarım tamamlanan | 20 |
| Görsel doğrulanan | 18 |
| İşlevsel doğrulanan | 8 |
| Kapalı / erişilemez | 6 |
| Sınır (dokunulmadı) | 5 |

Ayrıntılı envanter: [ACCOUNT-PHASE4-COVERAGE.md](ACCOUNT-PHASE4-COVERAGE.md).

## Yapılan tasarım / kod

### Ortak hesap kabuğu
- Geri bağlantı gerçek URL (`preferencesUrl` / `profileUrl`); history yok.
- Gutter 32 / 24 / 16; başlık 28/36 (mobil 24/32); kart padding 20–24 (mobil 16).
- `focus-visible`, 150 ms geçişler, primary’de en fazla 1 px lift, `prefers-reduced-motion`.
- Tablo yatay kaydırma yalnız `.wam-acc-table-wrap` içinde.

### Tercihler
- 5 kişisel grup korundu.
- Admin rol/izin linkleri **Yönetim kısayolları** sınır grubuna ayrıldı (`data-wam-boundary="admin"`).
- Satırlar gerçek `<a href>`; kaynak liste gizleme seçicisi `.wam-acc-prefs-built` ile daraltıldı.

### Profil düzenleme (karar: native)
- `enhanceEdit` alan taşımayı ve özel `<details class="wam-acc-advanced">` üretimini kaldırdı.
- Moodle fieldset’leri `wam-acc-fieldset` kart + mevcut `.ftoggler` / collapse.
- Geçersiz alan / hash hedefi `expandMoodleFieldset` ile açılıyor.

### Bildirim / dosya / oturum
- Bildirimlerde `MutationObserver` + idempotent süsleme; tablo wrap.
- Filepicker z-index hesap kapsamında (1055); modal sekmeleri görüldü.
- Oturum satırında metin pill: **Bu oturum** (renk tek başına değil). Sonlandırma tıklanmadı.

### Matcher
- Kişisel rozet sayfaları eklendi: `badges-preferences`, `badges-mybackpack`.

## Değişen dosyalar

| Dosya | Ne yaptı |
|---|---|
| [public/local/wamakademi/js/account.css](../../public/local/wamakademi/js/account.css) | wam-base’ten taşınan hesap bloğu + Faz 4 kabuk / fieldset / reduced-motion |
| [public/local/wamakademi/js/account_ui.js](../../public/local/wamakademi/js/account_ui.js) | native edit; admin prefs grubu; bildirim observer; Bu oturum; badge prefs kind |
| [public/local/wamakademi/lib.php](../../public/local/wamakademi/lib.php) | badges preferences / mybackpack matcher |
| [public/local/wamakademi/css/wam-base.css](../../public/local/wamakademi/css/wam-base.css) | Yalnız kendi “Hesap / ayarlar ortak bileşenler” bloğu kaldırıldı (yorum bırakıldı) |
| `docs/ui-audit/ACCOUNT-PHASE4-COVERAGE.md` | Bu kapsam envanteri |
| `docs/ui-audit/IMPLEMENTATION-ACCOUNT-P4.md` | Bu rapor |
| `docs/ui-audit/screenshots/account-p4/` | 24 PNG |
| `docs/ui-audit/IMPLEMENTATION-R2.md` / `ACCOUNT-SCREEN-INVENTORY.md` | R2 düzeltme notu; şifreler silindi |

## Ortak dosya ihtiyacı (diğer hat — yapılmadı)

Hesap bileşenleri `wam-base.css`’ten çıkarıldı. Aşağıdakiler **global** gerekirse diğer hattın kararı:

1. Filepicker / moodle-dialogue `z-index: 1055` — şu an yalnız `body.wam-account` altında. Kurs/editör dışı diyaloglarda da gerekirse ortak token katmanına alınabilir.
2. Form-check / switch primary rengi — hesap dışında da istenirse `wam-base` form pilotuna eklenebilir.
3. Navbar / hide-on-scroll / Site yönetimi — bu fazın kapsamı dışı; dokunulmadı.

## Önce / sonra görseller

Hepsi `docs/ui-audit/screenshots/account-p4/`. Örnekler:

- Tercihler: `preferences-admin-1440.png`, `preferences-admin-390.png`, `preferences-teacher-1440.png`, `preferences-student-1440.png`
- Native edit: `user-editadvanced-admin-1440.png`, `user-edit-teacher-1440.png`, `user-edit-student-1440.png` (+390)
- Bildirim / dosya / filepicker / oturum: `notifications-admin-1440.png`, `files-admin-1440.png`, `files-filepicker-admin-1440.png`, `usersessions-admin-1440.png`
- Temsilci 1024: `preferences-admin-1024.png`, `notifications-student-1024.png`, `files-student-1024.png`, `usersessions-student-1024.png`

Her PNG açılıp içerik ve hassas veri yokluğu kontrol edildi.

## Güvenlik sınırları (bilinçli)

- Şifre değiştirme formu **gönderilmedi**.
- MFA / OAuth / bağlı hesap değiştirilmedi.
- Aktif oturum sonlandırma tıklanmadı.
- Sesskey dokümanlara ve ekran görüntüsü adlarına yazılmadı.

## Kalan / sonraki adımlar

1. Bildirim / dosya / oturum / edit için eksik 768 ve bazı 390 yakalamaları tamamlanabilir.
2. `message/edit.php`, badges preferences / backpack için ayrı 1440+390 seti.
3. Profil (başka kullanıcı) bu turda yeniden yakalanmadı; hızlı regresyon önerilir.
4. Tercihler kaynak listesi a11y ağacında hâlâ görünebiliyor (`display:none`); gerekirse `hidden` + `aria-hidden` ile güçlendirilebilir.
5. Katalog / Eğitimlerim / kurs / lesson UI sonraki fazlar.
6. Boost çekirdek özelleştirmelerinin child temaya taşınması.

## Lint

- `node --check public/local/wamakademi/js/account_ui.js` OK  
- `php -l public/local/wamakademi/lib.php` OK  

# WAM UI yenileme — Uygulama turu 2 (Faz 3: Hesap ve ayarlar)

Tarih: 2026-11-11  
Ortam: lokal `http://localhost:8081`  
Commit/push/deploy: yapılmadı

## Round-1 düzeltmeleri (önce uygulandı)

1. **Bozuk CSS seçicileri:** `account.css` içinde 41 toplu `#page-user-edit,\n#page-user-editadvanced…` satırı çıplak `#page-user-edit` bırakıyordu (body’ye `max-width`/`display` sızıyordu). Hepsi `body.wam-acc-edit.wam-acc-ready …` olacak şekilde yeniden yazıldı; descendant boşlukları doğrulandı.
2. **Doğrulama (CDP):** editadvanced sayfasında `bodyMax: none`, `formMax/regionMax: 760px`, `scrollWidth <= innerWidth`.
3. **Matcher daraltıldı:** `local_wamakademi_is_account_page()` artık kişisel bağlamda; `badges-` / `reportbuilder-` / admin data privacy / kurs not raporu önekleri kaldırıldı. Başka kullanıcının profili kabuk için istisna.
4. **`body.wam-account` (+ `wam-acc-edit`):** `before_http_headers` içinde ekleniyor.
5. **Pilot regresyon:** Blog metni 0; katalog `?q=excel` → “WAM Test Egitimi Excel” (1 sonuç); çark/yan menü sınıfları yükleniyor; admin `/my/` ve WAM dashboard erişilebilir.

## Tamamlanan ekranlar

| Ekran | Not |
|---|---|
| Tercihler | 5 grup; yalnız Moodle’ın sunduğu linkler |
| Profil (kendi / başka) | Hiyerarşi; admin başka kullanıcıda Mesaj + düzenle |
| edit / editadvanced | Gruplama + form max 760; body sızıntısı yok |
| Dil, forum, editör, takvim tercihleri | Ortak form kabuğu |
| Şifre değiştir | Görsel kabuk; şifre değiştirilmedi |
| Bildirim / ileti tercihleri | CSS-only; `wam-acc-notifications` |
| Kişisel dosyalar | Filemanager kartı |
| Oturumlar | Tablo sarmalayıcı + Bu cihaz; sonlandırma tıklanmadı |
| Rozetlerim | Matcher’da; 0 rozet (boş) |
| İçerik bankası tercihleri | Tercihler listesinde |

Kapalı: blog, MFA, OAuth, portfolio, web service token, rozet detay (rozet yok). Ayrıntı: [ACCOUNT-SCREEN-INVENTORY.md](ACCOUNT-SCREEN-INVENTORY.md).

## Değişen dosyalar

| Dosya | Amaç |
|---|---|
| [public/local/wamakademi/js/account.css](../../public/local/wamakademi/js/account.css) | Bozuk seçici onarımı; token alias; tercihler 2 kolon |
| [public/local/wamakademi/js/account_ui.js](../../public/local/wamakademi/js/account_ui.js) | 5 prefs grubu; dar `pageKind`; `wam-acc-edit`; idempotent `data-wam-acc-init`; notifications enhancer |
| [public/local/wamakademi/lib.php](../../public/local/wamakademi/lib.php) | Dar `is_account_page`; `wam-account` / `wam-acc-edit` body sınıfları |
| [public/local/wamakademi/css/wam-base.css](../../public/local/wamakademi/css/wam-base.css) | Hesap shell, surface, form, tablo, pill, switch, prefs grid |
| `docs/ui-audit/*` | Envanter, rapor, `screenshots/r2/` |

## Kaldırılan / sadeleştirilen

- Blog UI: ayar `enableblogs=0` (veri silinmedi); profil/tercihlerde blog metni 0.
- Hesap CSS sızıntısı (body max-width) kaldırıldı.
- Matcher’dan kurs/admin yönetim sayfaları çıkarıldı.
- Tercihler: Moodle kaynak listesi gizlenip 5 grupta yeniden düzenlendi (link eklenmedi).

## Test sonuçları

### Admin
- Tercihler 5 grup, overflow yok (1440/390).
- editadvanced: `wam-acc-edit`, form 760px, body max none.
- Forum özet kaydet → tercihlere dönüş → değeri geri al (0).
- Bildirimler / dosyalar / oturumlar / profil / başka profil.
- Katalog `?q=excel` gerçek kurs kartı.
- Blog: 0 eşleşme.

### Öğrenci (`wamtest_ogrenci`)
- WAM dashboard’a yönlendi (admin istisnası korunuyor).
- Tercihler 5 grup (Site yönetimi yok).
- `user/edit.php` grupları: Kullanıcı resmi, Temel bilgiler, Konum, Açıklama; `wam-acc-edit` açık.

### Eğitmen (`wamtest_egitmen`)
- **R2 düzeltmesi (Faz 4):** Eğitmen oturumu bu turda gerçekten açılmadı; `preferences-teacher-1440.png` anomali/çok panelli görüntü olabilir. Doğrulama Faz 4’te yeniden yapılır.
- Kurs kaydı: editingteacher @ WAMTEST01 (lokal test hesabı).

### Güvenlik sınırları (bilinçli)
- Şifre / MFA / bağlı hesap / aktif oturum sonlandırma çalıştırılmadı.

### Lint
- `php -l lib.php` OK; `node --check account_ui.js` OK.

## Regresyon (Faz 0–2 pilotları)
- Admin `/my/` ve WAM dashboard erişimi bozulmadı.
- Navbar arama → katalog sonuç.
- `wam-ui` + `wam-base.css` yükleniyor.
- Selector sızıntısı giderildi (önceki tur riski).

## Ekran görüntüleri (`screenshots/r2/`)

Dosya adında ekran–rol–genişlik. Örnekler:
- `preferences-admin-1440.png`, `preferences-admin-390.png`
- `preferences-student-1440.png`, `preferences-teacher-1440.png`
- `user-editadvanced-admin-1440.png`, `user-edit-student-1440.png`
- `notifications-admin-1440.png`, `files-admin-1440.png`, `usersessions-admin-1440.png`
- `profile-admin-1440.png`, `profile-other-admin-1440.png`

Not: `user-edit-admin-390.png` mobil form/dil yakalaması; tam 4×4 matrisin bir kısmı temsilî set + 390 tercihler/düzenleme ile karşılandı.

## Test hesapları (yalnız lokal)

| Kullanıcı | Rol |
|---|---|
| admin | siteadmin |
| wamtest_egitmen | editingteacher |
| wamtest_ogrenci | student |

Kurs: WAMTEST01 — “WAM Test Egitimi Excel”. Şifreler dokümana yazılmaz.

## Faz 4’e kalan
- Katalog / Eğitimlerim görsel yenileme
- Kurs sayfası + lesson/resource layout
- pptx görüntüleyici
- Takvim WAM sayfası derin yenileme
- Boost çekirdek özelleştirmelerinin child temaya taşınması
- `styles.css` !important azaltımı

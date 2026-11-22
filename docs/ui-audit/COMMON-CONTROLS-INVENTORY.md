# Ortak kontroller — envanter (Common Controls R1)

Ortam: Moodle **5.2.2+** / tema **boost**, yerel `http://localhost:8081`.
Body sınıfı: `wam-ui`. Token kaynağı: `public/local/wamakademi/css/wam-base.css`.

Önceki envanterler: [COURSE-UI-INVENTORY.md](COURSE-UI-INVENTORY.md), [ACCOUNT-SCREEN-INVENTORY.md](ACCOUNT-SCREEN-INVENTORY.md), [IMPLEMENTATION-R1.md](IMPLEMENTATION-R1.md), [IMPLEMENTATION-R2.md](IMPLEMENTATION-R2.md), [IMPLEMENTATION-COURSE-R1.md](IMPLEMENTATION-COURSE-R1.md), [IMPLEMENTATION-ACCOUNT-P4.md](IMPLEMENTATION-ACCOUNT-P4.md).

Doğrulama işaretleri (bu tur): **G** görüntü incelendi · **Ö** DOM ölçüldü · **—** doğrulanmadı · **S** kaynak/stil taraması.

## CSS yükleme sırası (`local_wamakademi_before_standard_html_head`)

1. `wam-base.css` (her zaman)
2. `account.css` (her zaman)
3. `wam-admin.css` (yönetim sayfaları)
4. `wam-course.css` (kurs bağlamı)
5. `wam-forms.css` (her zaman, sayfa katmanlarından sonra)
6. `wam-users.css` (kullanıcı listesi/form)
7. **`wam-controls.css`** (R1 — forms/users sonrası; ortak kontrol ailesi)

## Çakışan stil aileleri (R1 öncesi)

| Aile | Dosyalar | Sorun |
|---|---|---|
| `form-select` / `custom-select` | base, forms, admin, account, users, course | Farklı yükseklik (40 vs 44), kenarlık, ok |
| `form-autocomplete-*` | forms, course (katılımcı modal) | Modal özel kuralları forms üstüne biner |
| `dropdown-menu` | base (navbar), course (sekme/üçüncül), users (action-menu) | Paralel bağlamlar; ortak dil yok |
| `ftoggler` / collapse | forms, admin, account | Legend kenarlık/tipografi çakışması |
| `list-group` | base (yalnız odak sıfırlama) | Görsel chrome yok; tercihler özel sınıf |

Yerel `<select>` açık penceresi işletim sistemine aittir; CSS ile tam stillenemez (sınır).

## Bileşen envanteri

| # | Aile | Temsilî rota | Kaynak | R1 öncesi | R1 durumu | Masaüstü | Mobil |
|---|---|---|---|---|---|---|---|
| 1 | Select | `/course/edit.php?id=2` | wam-controls | Çakışan 40/44 px | Değişti | G | G |
| 2 | Select | `/local/wamakademi/catalog.php` | wam-controls + styles | Zayıf | Değişti | Ö | — |
| 3 | Select | `/admin/user.php` filtre | wam-controls + users | Kısmi | Değişti | Ö | — |
| 4 | Select | Sticky footer `.form-select` | wam-controls | Var | Değişti | — | — |
| 5 | Autocomplete | Kurs ayarları kategori/etiket | wam-controls | Var | Değişti (stil) | Ö | Ö |
| 6 | Autocomplete | Katılımcı ekle modal | course + controls | Modal özel | Değişti (stil) | — | — |
| 7 | Dropdown | Navbar kullanıcı/çark | controls + wam_menu.js | Özel | Görünüm ailesi | Ö | — |
| 8 | Dropdown | Üçüncül navigasyon | course + controls | Var | Uyum | — | — |
| 9 | Action menu | `/admin/user.php` satır | users + controls | Var | Değişti | G | — |
| 10 | Action menu | Katılımcılar İşlemler | course + controls | Yeni | Görünüm ailesi | G | — |
| 11 | Accordion | `/course/edit.php` ftoggler | controls + forms | Büyük kare | Değişti | Ö | Ö |
| 12 | Accordion | Hesap alan grupları | account | Özel | Korunur | — | — |
| 13 | Liste | Katalog / eğitimlerim | styles (kart) | Kart | Kart korundu | — | — |
| 14 | Liste | Tercihler | controls + account | Özel | İnce ayırıcı | Ö | — |
| 15 | Tablo | Katılımcılar / kullanıcı listesi | course / users | Mevcut | Tablo korundu | Ö | — |

## Roller

| Rol | Hesap | Not |
|---|---|---|
| Yönetici | admin | Site yönetimi + tüm formlar |
| Eğitmen | wamtest_egitmen | Kurs ayarları / katılımcı |
| Öğrenci | wamtest_ogrenci | Katalog, eğitimlerim, hesap |

## Tarama özeti (kaynak satır sayıları)

| Dosya | form-select/custom-select/autocomplete/dropdown/ftoggler/list-group yaklaşık |
|---|---|
| wam-forms.css | 35 |
| account.css | 20 |
| wam-admin.css | 18 |
| wam-users.css | 16 |
| wam-course.css | 13 |
| wam-base.css | 9 |
| styles.css | 0 (bu aileler) |

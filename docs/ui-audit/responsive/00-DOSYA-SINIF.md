# Dosya sınıflandırması — Responsive Faz 1

Bu belge hangi dosyaların **yalnız sınıflandırıldığını**, hangilerinin **içeriğinin incelendiğini** ayırır.

## Depo üst düzey

| Dizin / dosya | İçerik | Responsive etkisi | İnceleme |
|---|---|---|---|
| `public/` | Moodle web kökü | Ekran üretir | Seçici (WAM + yerel farklar) |
| `lib/` (kök) | Moodle yardımcı / paket | Dolaylı | Yalnız sınıflandırıldı |
| `admin/` (kök) | CLI/admin yardımcı | Yok | Yalnız sınıflandırıldı |
| `docs/` | Denetim dokümanları | Yok (teslim) | Okundu |
| `docker/` | Konteyner | Ortam | Yalnız sınıflandırıldı |
| `node_modules/`, `vendor/` | Bağımlılıklar | Yok | Yalnız sınıflandırıldı |
| `_backups/` | Yedek | Yok | Yalnız sınıflandırıldı |
| `scripts/` | Araçlar | Yok | Yalnız sınıflandırıldı |

İzlenen dosya sayısı (~): `git ls-files` → ~61 600 (`public` ağırlıklı).

## A — İçerik incelenen (ekran üreten WAM)

`public/local/wamakademi/` (`.patch`, `.deploy-*` hariç):

- PHP sayfalar: `index`, `catalog`, `mycourses`, `calendar`, `upload`, `publish`, `pptx_view`, ilerleme/API uçları, `lib.php`
- CSS: `css/wam-*.css`, `js/account.css`, `login.css`, `lesson.css`, `styles.css`
- JS: menü, header, admin, account, course_*, sitefront, participants, users, lesson, video_tracker, login_dev
- Mustache: `dashboard`, `mycourses`, `admin_nav`, `participants_actions`
- Yerel Moodle farkı: `public/theme/boost/layout/drawers.php`, `public/user/classes/table/participants.php`

Ayrıntılı breakpoint / min-width / fixed / JS notları keşif ajanı çıktısıyla `01-KAPSAM.md` §5 ve `03-BULGULAR.md` kök nedenlerinde birleştirildi.

## B — Yalnız sınıflandırılan

- Moodle çekirdek `public/admin`, `public/course`, `public/user`, `public/message`, `public/grade`, `public/theme/boost` (drawers.php hariç), `public/lib` üçüncü taraf
- `public/wamtest_*.html` (önceki tur HTML snapshot)
- AMD `amd/build/*.min.js` (üretilmiş)
- PHPUnit / Behat / `.github`

## C — CSS yükleme sırası (doğrulama hedefi)

`local_wamakademi_before_standard_html_head` (`lib.php` ~1558):

1. login.css (giriş)
2. wam-base → account.css → (admin) wam-admin → (kurs) wam-course → (site) wam-sitefront → wam-forms → (users) wam-users → (upload) wam-content → **wam-controls**
3. lesson.css (resource view)
4. Hook dışı: eklenti `styles.css` (Moodle otomatik)

Gerçek `<link>` sırası canlı ölçümde örnek sayfada doğrulanır (`olcum/out/css-order.json` varsa).

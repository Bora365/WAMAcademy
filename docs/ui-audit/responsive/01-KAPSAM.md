# Responsive Faz 1 — Kapsam

Tarih: 2026-11-18  
Tür: analiz, canlı ölçüm, faz planı  
Ürün kodu değişikliği: **yok**  
Commit / push / deploy: **yapılmadı**

## 1. İncelenen sürüm

| Alan | Değer |
|---|---|
| Kök | `/Users/boravarol01gmail.com/Desktop/WAMAcademy` |
| Dal | `main` (`origin/main`) |
| HEAD | `28cc0033beb339bdde0d74fea72c32ce0e0a5f95` |
| Moodle | `5.2.2+ (Build: 20260818)` (`public/version.php`) |
| Tema | **boost** (`$CFG->theme`) |
| Eklenti | `local_wamakademi` `0.1.1` / `2026082610` (çalışma ağacı `version.php`) |
| wwwroot | `http://localhost:8081` |
| DB | MariaDB `wam_academy_local` |
| PHP | 8.3.36 yerleşik sunucu, `[::1]:8081` (PID mevcut; yeniden başlatılmadı) |

### Yerel değişiklikler (korundu)

Çalışma ağacı HEAD’e göre kirli (~112 satır `git status`). Özet:

- Değişen WAM kaynakları: CSS (`wam-base`, `wam-admin`, `wam-controls`, `wam-course`, `account.css`, `login.css`), JS (`wam_menu`, `account_ui`, `course_*`, `participants_table`), `lib.php`, `upload.php`, dil dosyaları, `dashboard.mustache`, `version.php`, yetki/DB.
- Boost/çekirdek yerel fark: `public/theme/boost/layout/drawers.php`, `public/user/classes/table/participants.php`.
- İzlenmeyen: `docs/` (bu teslim dahil), `public/wamtest_*.html`, yeni sınıf/hook/CSS/JS (ör. `wam-sitefront.css`, `sitefront_nav.js`).
- Silinen yedekler: `*.bak`, `*.faz2`, `*.save` (ürün davranışına etkisi yok).

Bu Faz 1 teslimi yalnızca `docs/ui-audit/responsive/` altına yazar.

## 2. Önceki çalışmalar ve 390 / 1440 sınırları

| Belge | Tarih / ortam | Ne doğrulandı | Bu tur için sınır |
|---|---|---|---|
| [IMPLEMENTATION-R1.md](../IMPLEMENTATION-R1.md) | 2026-11-11, admin, `:8081` | Pilot: `/my/`, WAM dashboard, profil düzenleme, oturumlar | Responsive matris değil |
| [IMPLEMENTATION-COMMON-CONTROLS-R1.md](../IMPLEMENTATION-COMMON-CONTROLS-R1.md) | 2026-11-16 | Select/menü/accordion; görsel çoğunlukla **1440 + 390** | iPad / yatay / klavye kanıtı değil |
| [ACCOUNT-PHASE4-COVERAGE.md](../ACCOUNT-PHASE4-COVERAGE.md) | 2026-11-16 | Eğitmen **18**, öğrenci **18** kişisel hedef; çoğu 1440 ve/veya 390; tercihlerde bazı 1024/768 | Aynı genişlikler bu sürümün iPad/yatay kanıtı değildir |
| [IMPLEMENTATION-ACCOUNT-P4.md](../IMPLEMENTATION-ACCOUNT-P4.md) | 2026-11-16 | Hesap kabuğu ölçüleri; tasarım rehberi `WAM-ACCOUNT-UI-GUIDE.md` bu ağaçta yok | Hesap referansı olarak kullanıldı |
| Kurs / form / kullanıcı R1 envanterleri | çeşitli | Site yönetimi dağınık; ayrı site-admin responsive raporu yok | Bu turda admin ağacı yeniden keşfedilir |

**Açık ifade:** Önceki 390×… ve 1440×… kontrolleri, ölçüldükleri tarihteki çalışma ağacına aittir. iPad dikey/yatay, bölünmüş pencere, ara genişlik (1024–1280), yön değişimi ve ekran klavyesi için kanıt oluşturmazlar.

Hesap başlangıç listesi: Faz 4’teki 18’er kişisel menü hedefi. Aynı rota iki rolde **tek ekran kimliği + iki rol doğrulaması** olarak sayılır.

## 3. Dosya tarama sınıfları

| Sınıf | Kapsam | Bu turda |
|---|---|---|
| A — Ekran üreten WAM | `public/local/wamakademi` PHP/CSS/JS/Mustache (`.patch` / `.deploy-*` hariç) | İçerik incelendi |
| B — Yerel Moodle farkı | `drawers.php`, `participants.php` | Diff amacı incelendi |
| C — Moodle çekirdek UI (Boost, admin, user, course, message, …) | `public/` izlenen ~61k dosya | Yalnız rota/menü keşfi; satır satır CSS taranmadı |
| D — Üçüncü taraf / üretilmiş | `node_modules`, `vendor`, `lib/` paketleri, AMD build | Yalnız sınıflandırıldı |
| E — Test / yedek / snapshot | `public/wamtest_*.html`, `_backups`, `*.bak` | Yalnız sınıflandırıldı |
| F — Dokümantasyon | `docs/` | Okundu; önceki kanıt yeniden doğrulanmadıkça eski sayılır |

Ayrıntılı WAM envanteri ve CSS/JS bulgu adayları: bu klasördeki `03-BULGULAR.md` kök neden bölümü ve aşağıda §5.

## 4. Ortamlar ve ölçüm katmanları

| Ortam | Kullanım | Gerçek cihaz mı? |
|---|---|---|
| Cursor gömülü Chromium + CDP `Emulation.setDeviceMetricsOverride` | Katman A/B tarama, ekran görüntüsü, DOM ölçü | Hayır — yeniden boyutlandırılmış masaüstü motoru |
| Playwright Chromium (`olcum/`) | Tekrarlanabilir JSON ölçüm | Hayır |
| Playwright WebKit | Kurulumu varsa kritik akış karşılaştırması | Hayır (WebKit emülasyonu) |
| Gerçek iPhone/iPad Safari | Planlandı | **Bu makinede yok** — doğrulanmadı |
| Ekran klavyesi / Safari chrome / safe-area | Yükseklik vekili `390×420` | Vekil; gerçek klavye değil |

Katman A (her erişilen benzersiz ekran): **390×844**, **820×1180**, **1440×900**.  
Katman B (ekran türü + kritik akış): 320, 360, 844×390, 1180×820, 507×768, 320×768, 1024, 1280, eşik ±1 px, açık menüde resize.

Başarı koşulu: URL `/login/` değil; `document.readyState === complete`; `#region-main` veya eşdeğer landmark görünür; yalnız “yükleniyor” sayılmaz.

Güvenlik: çıkış, oturum sonlandırma, silme, yayımla/kaldır, ayar kaydetme, şifre gönderimi ve rozet bağlama **tıklanmaz**. Parola ve çerez dosyaya yazılmaz (`WAM_AUDIT_PASS` ortam değişkeni).

## 5. Kod incelemesi özeti (içerik incelenen)

### CSS yükleme sırası (`lib.php` `local_wamakademi_before_standard_html_head`)

1. `login.css` — yalnız giriş  
2. Giriş yapmış: `wam-base.css` → `account.css` → (admin) `wam-admin.css` → (kurs) `wam-course.css` → (site front) `wam-sitefront.css` → `wam-forms.css` → (users) `wam-users.css` → (upload) `wam-content.css` → **`wam-controls.css` (son)**  
3. `mod-resource-view`: `lesson.css`  
4. Moodle ayrıca eklenti `styles.css` yükler (hook dışı)

### Kritik eşikler (hemen altı/üstü denenecek)

576, 620, 700, 760, 768, 900, 950, 992, 1024, 1050, 1100, 1200, 1250 (+ hesapta 769).

### Yüksek risk adayları (ölçüm öncesi kod)

| Dosya:satır / seçici | Neden | Aday ekran |
|---|---|---|
| `styles.css` ~1536 `.wam-dashboard` `width:100vw` | Yatay kaydırma riski | Ana sayfa |
| `account.css` ~1809 tablo `min-width:480px` | Dar telefon taşması | Oturumlar, notlar |
| `wam-course.css` katılımcı sütun `min-width` 200–260px | Tablo yatay kaydırma | Katılımcılar |
| `wam-users.css` th min-width 240–260px | Aynı | Sistem kullanıcıları |
| `lesson.css` yan kolon 360px @ ≤991 | Ders iki kolon | Ders / resource view |
| `wam_menu.js` push @ ≥1024 vs header hide @ ≤992 | Ara bant 992–1023 | Tüm kabuk |
| `drawers.php` sol drawer boşaltma | Menü erişimi WAM hamburger’e bağlı | Mobil gezinme |

Tam satır listesi ölçüm sonrası doğrulanmış / aday ayrımıyla `03-BULGULAR.md` içindedir.

## 6. Teslim çıktıları

| Dosya | İçerik |
|---|---|
| `01-KAPSAM.md` | Bu belge |
| `02-EKRAN-MATRISI.csv` | Ekran kimlikleri, roller, test durumları, faz |
| `03-BULGULAR.md` | Öncelikli sorunlar + kanıt |
| `04-FAZ-PLANI.md` | 3–4 ekranlık uygulama grupları |
| `kanitlar/` | Ekran görüntüleri ve ölçüm JSON özetleri |
| `olcum/` | Tekrar çalıştırılabilir ölçüm aracı |

## 7. Açık sınırlar (teslim kapanışı)

### Sayılar (2026-11-18)

| Metrik | Değer |
|---|---|
| Matris satırı (benzersiz kapsam ekranı) | **164** (`02-EKRAN-MATRISI.csv`) |
| Katman A kanıtı olan (390 ve/veya 820 ve/veya 1440) | **55** |
| Keşfedildi, katman A ölçülmedi (çoğu admin kategori/settings) | **109** |
| Deep-only OK satırı | 278 / 286 |
| Evidence remeasure | 176 |
| Kanıt PNG (toplam klasör) | ~600+; özet 16 dosya `kanitlar/ozet/` |
| WebKit | CALENDAR-WAM student 390/820/1440 (taşma 390’da +33, Chromium ile uyumlu) |
| Gerçek iPhone/iPad Safari | **Doğrulanmadı** |

### Faz atanmamış erişilebilir ekranlar

Matristeki tüm satırlara RF1–RF42 atandı.  
`discover-admin.json` içindeki bazı `admin/tool/*` ve rapor alt linkleri matrise tek tek yazılmadı (settings/category örneklemesi 40+ kategori ile sınırlandı). Bunlar **keşfedildi, satır bekliyor** — bir sonraki matris genişletmesinde RF15+ altına eklenecek.

### Doğrulanmamış cihaz / rol

- Gerçek Safari (iOS/iPadOS), safe-area, dinamik araç çubuğu  
- Öğrenci × LESSON-RESOURCE (örnek video `visible=0`)  
- Çoğu `ADM-CAT-*` / `ADM-SET-*` × telefon/iPad/masaüstü  
- Eğitmen × Site yönetimi (yetki yok)

### Kanıtsız varsayımlar

- Admin kategori sayfalarının hepsinin ADM-SEARCH/ADM-APPEARANCE ile aynı taşma profiline sahip olduğu (şablon varsayımı; RF15+ doğrular)  
- `100vw` dashboard kurallarının artık zararsız olduğu (390’da overflow 0; regresyon izlenmeli)  
- Menü açıkken resize’ta açık kalmanın ürün isteği olduğu (30/30 gözlem; politika kararı)

### Ölçüm notu

Tam `measure-layerA-chromium.json` bir ara yanlışlıkla üzerine yazıldı; katman A kanıtı **ekran görüntüsü indeksi** (`measure-layerA-from-shots.json`) + `measure-evidence-chromium.json` + password/deep çıktılarıyla yeniden kuruldu. Tekrar tam tarama: `node measure.mjs --suite=layerA`.

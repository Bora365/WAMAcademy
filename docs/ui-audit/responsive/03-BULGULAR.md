# Responsive Faz 1 — Bulgular

Tarih: 2026-11-18  
Motor: Playwright Chromium (birincil), WebKit smoke (takvim)  
Gerçek iPhone/iPad Safari: **yok**  
Ekran klavyesi: yalnız `390×420` yükseklik vekili (`phoneKbProxy`)

Ölçüm kaynakları: `olcum/out/measure-evidence-chromium.json`, `measure-deep-chromium.json`, `measure-password-only.json`, `kanitlar/ozet/`.

Önceki 390/1440 turları (Hesap P4, Common Controls R1, Course/Forms R1) bu sürümün iPad/yatay/klavye kanıtı değildir.

---

## Öncelik anahtarı

| Öncelik | Anlam |
|---|---|
| P0 | Görev engeli veya büyük yatay taşma; birden fazla rol |
| P1 | Kontrol kaybı / kısmi taşma; görev zorlaşır |
| P2 | Ara genişlik / tutarsızlık; görev tamamlanır |
| Aday | Kodda görüldü; bu turda ölçülmedi veya zayıf kanıt |

---

## F-01 — Not defteri tüm sayfayı yatay kaydırıyor (P0)

| Alan | Değer |
|---|---|
| Ekran | `COURSE-GRADES` |
| Rol | teacher, admin |
| Tarayıcı | Chromium (CDP viewport) |
| Ölçü | 390×844: **+896 px**; 820×1180: **+570**; 320: **+966**; 1024: **+366**; 1180 yatay: **+218**; 1440: 0 |
| Tekrar | Kurs → Notlar (`/grade/report/grader/index.php?id=2`) |
| Beklenen | Tablo kendi sarmalayıcısında kayar; `documentElement.scrollWidth ≈ innerWidth` ([WCAG 2.2 Reflow 1.4.10](https://www.w3.org/WAI/WCAG22/Understanding/reflow.html)) |
| Görülen | Sayfa düzeyinde yatay taşma; hücre eylemleri ve ödev başlıkları viewport dışında |
| Görev | Dar ekranda not hücresi / satır işlemine ulaşmak |
| Kanıt | `kanitlar/ozet/evidence_COURSE-GRADES_teacher_phone390.png`, `…_ipadPort.png` |
| Kök neden | Moodle grader geniş tablo + WAM/Boost sarmalayıcının `overflow-x` sınırının sayfaya sızması. Katılımcı tablolarındaki sabit `min-width` ailesiyle aynı sınıf (`wam-course.css` tablo sütun min-width’leri ilgili). |
| Öneri | `#region-main` içinde `.gradeparent` / tabloyu `overflow-x: auto; max-width: 100%` sarmalayıcıya al. 390 ve 820’de `scrollWidth - innerWidth === 0` iken sarmalayıcı `scrollWidth > clientWidth` kabul. Sticky ilk sütun (öğrenci adı) ≤820’de dene. Doğrulama: teacher+admin, 320/390/820/1024. |

---

## F-02 — Takvim “Bugün” / ok kontrolleri taşır (P0)

| Alan | Değer |
|---|---|
| Ekran | `CALENDAR-WAM` |
| Rol | student, teacher, admin |
| Ölçü | 390: **+33 px** (Bugün `left:344 right:423`); 320: **+103**; 360: **+63**; klavye vekili aynı |
| WebKit | student 390/ipad/1440 ölçüldü — Chromium ile aynı taşma ailesi (smoke OK) |
| Tekrar | Takvim → ay gezinme çubuğu |
| Beklenen | Önceki / Bugün / Sonraki tek satırda sığar veya alt satıra kırılır; taşma yok |
| Görülen | `.wam-calendar-today` sağdan taşar; sayfa yatay kayar |
| Görev | Bugüne dönmek / ay değiştirmek |
| Kanıt | `kanitlar/ozet/evidence_CALENDAR-WAM_student_phone390.png`, `…_phone320.png` |
| Kök neden | [`styles.css` ~2216–2241](../../public/local/wamakademi/styles.css): `.wam-calendar-today` sabit yükseklik + yatay padding; `.wam-calendar-navigation strong { min-width: 165px }` (~2207). Navigasyon flex-wrap / gap yok. |
| Öneri | ≤767.98’de navigasyonu `flex-wrap: wrap; gap: 8px; width: 100%` yap; `strong { min-width: 0; flex: 1 1 auto }`; Bugün düğmesi `flex: 0 1 auto`. Geçme: 320 ve 390’da overflowX=0 ve Bugün tıklanabilir. |

---

## F-03 — Katılımcılar: işlemler ve sütunlar dar ekranda kaybolur (P0/P1)

| Alan | Değer |
|---|---|
| Ekran | `COURSE-PARTICIPANTS` (`SITE-PARTICIPANTS` aynı tablo ailesi) |
| Rol | teacher, admin |
| Ölçü | 390: sayfa overflow düşük ama **13 offscreen** kontrol (sıralama, quickedit, İşlemler); 320: **+57 px**; 360: +17 |
| Tekrar | Kurs → Katılımcılar; satır sonu İşlemler |
| Beklenen | Yerel tablo kaydırması veya kart yığını; İşlemler görünür/erişilir |
| Görülen | Sütun `min-width` nedeniyle içerik sağda; işlem tetikleyicileri viewport dışında kalabiliyor |
| Kanıt | `kanitlar/ozet/evidence_COURSE-PARTICIPANTS_teacher_phone390.png` |
| Kök neden | [`wam-course.css`](../../public/local/wamakademi/css/wam-course.css) ~758–824: `.wam-pc-name` 200px, email 250px, diğer sütunlar 90–140px; `.wam-row-actions__menu` min 220px fixed. |
| Öneri | ≤768’de e-posta/rol/grup sütunlarını ikincil yap veya kart satırına indir; İşlemler her satırda sticky sağ veya menü ilk görünür aksiyon. Geçme: 390’da İşlemler menüsü açılır, overflowX≤2 (yerel sarmalayıcı hariç). |

---

## F-04 — Site yönetimi görünüm / ayar formlarında yatay taşma (P1)

| Alan | Değer |
|---|---|
| Ekran | `ADM-APPEARANCE` (+407 @390), `ADM-SETTINGS` (+87 @390; secondary nav linkleri taşar), `ADM-GRADES` (+24 @390) |
| Rol | admin |
| Tekrar | Site yönetimi → Görünüm / ön sayfa ayarları |
| Beklenen | Uzun form dikey akar; secondary nav yatay kayar veya “daha fazla”ya düşer ([MDN overflow](https://developer.mozilla.org/en-US/docs/Web/CSS/overflow)) |
| Görülen | Form satırları / nav sekmeleri sayfa genişliğini aşıyor |
| Kanıt | `kanitlar/ozet/evidence_ADM-APPEARANCE_admin_phone390.png`, `evidence_ADM-SETTINGS_admin_phone390.png` |
| Kök neden | Admin mform + secondary navigation; `wam-admin` / sitefront yatay scroll kuralları bu sayfalara tam oturmuyor. Checkbox/text yan yana min içerik genişliği. |
| Öneri | Admin form satırlarında `minmax(0,1fr)` ve alan `max-width:100%`; secondary nav için `wam-sitefront` / course secondary’deki `overflow-x:auto` desenini admin ayar sayfalarına taşı. Geçme: 390’da overflowX=0; “İçerik bankası” sekmesine menü veya kaydırma ile ulaşılır. |

---

## F-05 — Üst çubuk kullanıcı / ileti menüsü iPad dikeyde taşma adayı (P1)

| Alan | Değer |
|---|---|
| Ekran | Kabuk (HOME, CATALOG, birçok admin) |
| Rol | Özellikle admin (daha çok sağ ikon) |
| Ölçü | 820×1180: Kullanıcı menüsü `left:857–934` (viewport 820) — sağdan taşar |
| Tekrar | iPad dikey genişlikte herhangi bir giriş yapmış sayfa |
| Beklenen | Navbar öğeleri 820’ye sığar veya overflow menüye düşer |
| Görülen | Sağ küme (bildirim, ileti, kullanıcı) viewport dışına taşabiliyor |
| Kanıt | evidence HOME/CATALOG admin ipadPort ölçümleri (`measure-evidence` / deep) |
| Kök neden | Sabit navbar + WAM arama + Moodle popover ikonları; 820 bandında sıkışma. Menü push eşiği 1024 (`wam_menu.js`), header hide 992 — ara bant stresi. |
| Öneri | ≤900 veya ≤1023’te arama alanını daralt / hamburger yanına al; sağ ikon grubuna `flex-shrink` ve min boşluk. Geçme: 820’de kullanıcı menüsü tıklanır, overflowX=0. |

---

## F-06 — Video yükleme “Taslak” durumu sağ kenarda kırpılır (P2)

| Alan | Değer |
|---|---|
| Ekran | `UPLOAD-VIDEO` |
| Rol | teacher, admin |
| Ölçü | 390: “Taslak” düğmesi `left:362 right:420` (kısmi taşma) |
| Kanıt | `kanitlar/ozet/evidence_UPLOAD-VIDEO_teacher_phone390.png` |
| Öneri | Üst durum şeridini `flex-wrap` veya tam genişlik yığını yap. |

---

## F-07 — Öğrenci ders oynatıcı: örnek veri yok (kapsam / veri)

| Alan | Değer |
|---|---|
| Ekran | `LESSON-RESOURCE` |
| Rol | student |
| Durum | Kurs id=2’de tek resource `visible=0` (taslak) → öğrenci `uservisible=0` |
| Admin/teacher | Açıldı (taslak görünür) |
| Not | Öğrenci E2E “ders izle” bu kurulumda tamamlanamadı. Yayınlı örnek video olmadan doğrulanmadı. |

---

## F-08 — Menü açıkken yön/genişlik değişimi (bilgi)

Deep suite: 30/30 `resize-menu-390to820` sonunda `menuStillOpen: true`. Beklenen: menü durumu korunur veya bilinçli kapanır. Korunma gözlendi; kapanma politikası ürün kararı.

---

## Standart dayanaklar (bulgulara bağlı)

| Kaynak | Kullanım |
|---|---|
| [WCAG 2.2 1.4.10 Reflow](https://www.w3.org/WAI/WCAG22/Understanding/reflow.html) | F-01…F-04: 320 CSS px’de iki boyutlu kaydırma zorunluluğu olmamalı (tablo istisnası yerel kaydırma ile) |
| [WCAG 2.2 2.4.7 Focus Visible](https://www.w3.org/WAI/WCAG22/Understanding/focus-visible.html) | Hesap formları / sticky kaydet; bu turda kaydet gönderilmedi |
| [MDN visualViewport](https://developer.mozilla.org/en-US/docs/Web/API/VisualViewport) | Klavye vekili gerçek `visualViewport` değil; gerçek Safari’de tekrarlanmalı |
| Proje tercihi | 44 px dokunma (`--wam-touch`) — standart minimum değil, WAM tercihi |

---

## Kod adayları (ölçülmedi veya zayıf)

| Aday | Dosya | Not |
|---|---|---|
| `styles.css` `.wam-dashboard` / hero `100vw` | ~1536+ | Ana sayfa 390’da overflow 0 ölçüldü; path override kısmen etkili — regresyon izle |
| `account.css` tablo `min-width:480px` | ~1809 | Oturumlar/notlar yerel wrap ile; 390’da kritik taşma görülmedi |
| `lesson.css` 360px yan kolon | ≤991 | Admin ders açıldı; öğrenci veri yok |
| Eşik dağınıklığı | 767.98 vs 700/760/769/991 | RF1 ortak token işi |

---

## Olumlu gözlemler

- Çoğu WAM kabuk ekranı (HOME, CATALOG, MYCOURSES, hesap hub) 390/820/1440’ta **sayfa overflowX ≈ 0**.
- Hamburger tüm ölçülen girişli 390’larda mevcut (`no-hamburger` yok).
- ACC-PASSWORD düzeltmesi sonrası 390/820/1440 üç rolde açıldı (kaydetme yok).
- Admin kategori şablonu keşfedildi (100+ alt ekran); temsilciler ölçüldü.

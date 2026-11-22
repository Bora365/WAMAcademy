# Eğitim ekranları — tasarım kararları (Kurs R1)

Kapsam sınıfı `body.wam-ui.wam-course` ve alt sınıfları, `local_wamakademi_course_page_kind()` tarafından Moodle bağlamından üretilir. URL'ye bakılmaz.

**Sayfanın kurs ekranı sayılması için koşullar:**
- Kullanıcı oturum açmış ve misafir değil.
- Kurs site kursu (SITEID) değil.
- `pagelayout` şunlardan biri: course, incourse, report, admin, standard, base.
- Bağlam düzeyi kurs, modül ya da blok.
- Hesap sayfası değil ve ders görüntüleyici (`mod-resource-view`) değil.

**Alt sınıflar:**

| Sınıf | Sayfalar |
|---|---|
| `wam-course-course` | Kurs düzeyindeki ekranlar |
| `wam-course-mod` | Etkinlik ekranları |
| `wam-course-participants` | pagetype `course-view-participants` |
| `wam-course-settings` | pagetype `course-edit` |
| `wam-course-grades` | pagetype `grade-*` |
| `wam-course-wide` | değerlendirici raporu ve tek görünüm |

Tokenlar `css/wam-base.css` dosyasından okunur; o dosya ortak hattındır ve değiştirilmedi.

## 1. Ortak kolon
- **Hizalama:** Başlık, sekmeler, araç çubuğu ve içerik aynı sol çizgidedir. Ölçüm: 1440'ta x=317, 1024'te 309, 768'de 24, 390'da 16. Boost'un sekmelere uyguladığı `max-width: 830px; margin: auto` kaldırıldı. Etkinlik başlığındaki 830 px sınırı da kaldırıldı; çekirdek `margin: 0 auto !important` olduğu için yalnızca `max-width: none` yeterli oldu.
- **Başlık hiyerarşisi:**
  - H1: `--wam-title` (28/36; 767 px altında 24/32).
  - Kurs düzeyi H2: `--wam-h2` (20/28).
  - H3: `--wam-h3` (18/26).
  - Kullanıcı içeriğindeki başlıklara dokunulmaz (`.no-overflow`, `.activity-altcontent`, `.summarytext`, `.course-description-item` hariç tutulur).
- **Yüzeyler:** Zemin açık gri, yüzeyler beyaz.
  - Tablo sarmalayıcısı (`.table-responsive`) ve kurs düzeyindeki `.box.generalbox`: 1 px kenarlık, 12 px köşe.
  - Etkinlik sayfalarında `generalbox` kartı uygulanmaz; eklenti kutuları çok çeşitli olduğu için çift çerçeve oluşuyordu.

## 2. Sekmeler ve “Daha fazla”
- Gerçek bağlantılar korunur (ARIA sekmesi yok). Satır 48 px; etkin sekme 2 px birincil alt çizgi ve 700 ağırlıkla gösterilir.
- Odak: 2 px birincil outline (`:focus-visible`). Üzerine gelme: metin rengi.
- `moremenu.js` taşmayı sarmalanan listenin yüksekliğinden ölçer. Bu yüzden `flex-wrap` korunur ve yükseklik sabittir; 390'da sığmayan sekmeler “Daha fazla”ya taşınır (doğrulandı).

## 3. Araç çubuğu (üçüncül gezinme)
- Negatif kenar boşlukları sıfırlandı, aralık 12 px.
- Seçim menüsü başlığı 20/28, 700 ağırlık, en az 44 px; grup başlıkları etiket stilinde.

## 4. Mobil çekmece
Kurs dizini düğmesi 991 px altında akış içine alınır ve görünür etiket taşır: “Kurs dizini”, 44 px. Boost'un `.btn` için verdiği `margin: 0 16px` sıfırlandı; düğme başlıkla aynı sol çizgidedir. Çekmecenin açık/kapalı tercihi Boost'ta kalır.

## 5. Katılımcılar
- **İşlem:** Tek birincil işlem “Katılımcı ekle”, sayfa başlığı satırının sağında. Etiket dil dizesinden gelir; JS yalnızca `.enrolusersbutton.enrol_manual_plugin` düğmesinin değerini değiştirir.
- **Ad/e-posta araması:** Yeni bir arama kutusu var. Kutu, Moodle datafilter'ının “Anahtar kelime” satırını sözleşmesindeki seçicilerle (`data-filterregion`, `data-filteraction`) doldurur ve “Uygula”ya basar. Arama sunucu tarafında kalır, böylece sayfalama ve toplu işlemler bozulmaz.
- **Gelişmiş filtreler:** Koşul kurucunun tamamı (durum, rol, yöntem, grup) bu açılır panele taşındı. Etkin koşul sayısı rozette görünür. Anahtar kelime dışında etkin bir filtre varsa panel açık başlar.
- **Baş harf filtreleri:** “Baş harfe göre” düğmesinin arkasında ikincil alandadır. Bir harf seçiliyken açık başlar.
- **Tablo:**
  - En az 860 px; yalnızca kendi kartında yatay kayar.
  - Ad `break-word` ile, e-posta (en az 260 px) `anywhere` ile sarar; veri kesilmez.
  - Durum hücresi 3 × 44 px ızgaradır. “Kurs kaydını sil” işlemi ayraçla ayrılmış ve koyu kırmızıdır (#A61B1B). Moodle'ın onay penceresi korunur.
- **Katılımcı ekle penceresi:** Yalnızca `_qf__enrol_manual_enrol_users_form` içeren pencere stillenir.
  - Hedefler 44 px; öneriler en fazla 280 px yükseklikte kayar; seçimler hap biçiminde.
  - Mobilde altbilgi düğmeleri içerik genişliğinde, tek satırda.
  - Escape pencereyi kapatır ve odak “Katılımcı ekle”ye döner (doğrulandı).

## 6. Notlar
- **Değerlendirici ve tek görünüm (`wam-course-wide`):** İçerik genişliği 1440'a açılır.
- **Tablolar kendi kaydırma alanına alınmadı.** Çekirdek kanıt:
  - `grade.scss`: başlık satırı `top: $navbar-height`, ilk sütun `left: 0`, ortalama satırı `bottom: -1px`. Hepsi görünüm alanına göre yapışkandır.
  - `stickycolspan.js`: “pinned” tespiti görünüm alanı kökenli bir IntersectionObserver ile yapılır ve sticky footer yüksekliği düşülür.
  - Taşma kabı eklenirse bu üç davranış bozulur. Bu nedenle Moodle'ın sayfa düzeyi yatay kaydırması korundu.
  - Çekmece kapalıyken 1440'ta tablo tamamen sığar (1376/1376). Mobilde sayfa yatay kaydırılır; bu Moodle'ın davranışıdır ve sınır olarak raporlanır.
- **Not defteri kurulumu:** Moodle'ın gri iç sarmalı kaldırıldı ve tek kart kaldı.
- **Kullanıcı raporu:** Beyaz zemin.
- Not hesaplama ve gösterim mantığına dokunulmadı.

## 7. Ayarlar formu
- **Genişlik ve ızgara:** En fazla 800 px. 768 px ve üstünde etiket sütunu 220 px, alan sütunu esnek. Mobilde etiket alanın üstündedir. 576 px altında formun 15 px iç boşluğu sıfırlanır ve kart iç boşluğu 16 px olur.
- **Zorunlu ve hata ayrımı:** Zorunlu simgesi gri (`--wam-muted`); hata kırmızı, 14 px, 600 ağırlık ve alanın kenarlığında da gösterilir.
- **Tarih alanları:** Gruplar tek kutu içinde; 1440'ta tek satıra sığar.
- **Pasif alanlar:** #EEF2F7 zemin, soluk metin, `not-allowed` imleci.
- **Sticky işlem çubuğu:**
  - Hizası içerikle aynıdır; Bootstrap `px-3` farkı kenar boşluklarıyla telafi edildi.
  - İçeriğin altına çubuk yüksekliği + 32 px boşluk, kaydırma alanına `scroll-padding-bottom` eklendi; son alanlar ve odak hedefleri çubuğun altında kalmaz.
  - Mobilde Boost aşağı kaydırırken çubuğu gizler, yukarı kaydırınca gösterir (çekirdek `scrollSpy`).

## 8. Etkileşim, erişilebilirlik ve hareket
- Geçişler 120 ms (`--wam-dur-fast`), simge dönüşü 160 ms. `prefers-reduced-motion: reduce` altında hepsi kapanır; 0 s olarak ölçüldü.
- Kontrast (WCAG formülüyle hesaplandı):

  | Öğe | Renk | Zemin | Oran |
  |---|---|---|---|
  | Soluk metin | #4A6280 | beyaz | 6.27:1 |
  | Soluk metin | #4A6280 | gri zemin #F4F7FB | 5.83:1 |
  | Pasif alan metni | #4A6280 | #EEF2F7 | 5.58:1 |
  | Silme işlemi | #A61B1B | beyaz | 7.52:1 |
  | Hata metni | #B91C1C | beyaz | 6.47:1 |
  | Birincil düğme metni | beyaz | #2563EB | 5.17:1 |
- Hiçbir yerde `overflow-x: hidden` yok.
- `!important` tek yerde, açıklamasıyla kullanılır: kapalı harf filtresi, çünkü Bootstrap `d-flex !important` kullanıyor.
- Eski `styles.css` içindeki `1.55rem !important` H1 kuralı kaldırıldı. Diğer eski kurs görünümü kuralları korundu.

# Uygulama raporu — Form ve gezinme yüzeyleri (Form R1)

Referans ekranlar: kategori ayarları (`/course/editcategory.php?id=1`) ve kurs yükleme (`/admin/tool/uploadcourse/index.php`). Kurallar bu iki sayfaya özel değildir; ortak katman olarak eğitim, kategori, site yönetimi ve hesap formlarına uygulanır.

Commit, push veya deploy yapılmadı. Hiçbir form kaydedilmedi:
- Doğrulama testi, zorunlu ad boşken yapılan bir gönderimdir; sunucu bunu reddetti. Kategori adı “Kategori 1” olarak kaldı.
- Editörde görsel penceresi açılıp kapatıldı; içerik eklenmedi.

**Eşzamanlı hat notu:** Çalışma sırasında “Common Controls R1” hattı `css/wam-controls.css` dosyasını oluşturdu. Alan, select, autocomplete ve accordion görünümünü (chrome) `wam-forms.css` dosyasından oraya taşıdı; dosya en son yüklenir. Aşağıdaki doğrulamalar iki katmanın birleşik hâli üzerinde yapıldı. Kontrol görünümüyle ilgili açık noktalar §6'da o hatta iletilmiştir.

## 1. Değişen dosyalar

| Dosya | Değişiklik |
|---|---|
| `public/local/wamakademi/css/wam-forms.css` | **Yeni.** Ortak form katmanı; içeriği aşağıda. |
| `public/local/wamakademi/css/wam-base.css` | `:root` içine form token'ları eklendi (değerler aşağıda); mobil değerleri ayrıca tanımlandı. Mevcut token'lar değişmedi. |
| `public/local/wamakademi/lib.php` | Kategori bağlamı için `local_wamakademi_course_page_kind()` artık `'category'` döndürür; sayfa `wam-course wam-coursecat` sınıflarını alır. `.wam-course-category` katalog rozetinde kullanıldığı için ayrı ad seçildi. Ayrıca `local_wamakademi_forms_head_html()` eklendi: yerelleştirilmiş “Zorunlu” metni `--wam-req-text` olarak verilir. `wam-forms.css` oturum açılmış tüm sayfalarda yüklenir. Head kancası artık HTML döndürür. |
| `public/local/wamakademi/css/wam-course.css` | İç içe bölüm kartları ve eski zorunlu/hata/pasif/tarih/chip kuralları kaldırıldı; bunlar ortak katmana taşındı. İç sayfa başlığı (H2) 22/30 oldu. Kategori kabuğu için H2 seçicisi eklendi. |
| `lang/tr`, `lang/en` `local_wamakademi.php` | `formrequired` dizesi eklendi: “Zorunlu” / “Required”. |
| `WAMAcademy-moodledata/lang/tr_local/moodle.php` (depo dışı) | `dndenabled_inbox` = “Dosyayı buraya sürükleyin veya dosya seçin.” Canlıya dil özelleştirmesi olarak taşınmalı; ardından `purge_caches --lang` çalıştırılmalı. |

**`wam-forms.css` kapsamı:**
- form yüzeyi ve bölümler
- etiket ızgarası
- zorunlu alan gösterimi
- yardım düğmesi
- hata metni
- tarih grupları
- form düğmeleri
- dosya seçici ve dosya yöneticisi
- sürükle-bırak yönlendirmesi
- TinyMCE çerçevesi
- hareket azaltma

**`wam-base.css` içine eklenen token'lar:**

| Token | Değer |
|---|---|
| `--wam-field-h` | 44 px |
| `--wam-field-radius` | 8 px |
| `--wam-field-pad-x` | 12 px |
| `--wam-control-border` | #7F93AE (beyaz üstünde ≥ 3:1) |
| `--wam-field-disabled-bg` | #EEF2F7 |
| `--wam-field-soft` | #F8FAFD |
| `--wam-subtitle` | 22/30 (mobilde 20/28) |
| `--wam-form-label-w` | 220 px |
| `--wam-form-surface-pad` | 24 px (mobilde 16 px) |
| `--wam-select-arrow` | ortak ok görseli |

## 2. Kurallar ve nasıl uygulandığı

**Yüzeyler**
- Kategori sayfaları eğitim kabuğunu alır: gri zemin, şeffaf ana bölge, sekmeler ince alt çizgiyle.
- Form tek beyaz yüzeydir: 24 px iç boşluk, 1 px kenarlık, 12 px köşe.
- Normal formlar en fazla 800 px; editör veya çok dosyalı alan içeren formlar en fazla 960 px.
- Bölümler kart değildir. Ayrım başlık, boşluk ve ince üst ayırıcıyla yapılır.
- Site yönetimi formlarının yüzeyi `wam-admin.css` §8'den gelir ve aynı ölçülerdedir.

**Zorunlu alan**
- Yalnızca doğrulanmış Moodle işareti hedeflenir: `.form-label-addon > .text-danger[aria-hidden="true"]` içinde exclamation ikonu.
- İkon gizlenir; yerine nötr renkte 13 px “Zorunlu” metni gösterilir.
- Formun altındaki `fdescription.required` açıklaması gizlenir; Moodle bu öğeyi zaten `aria-hidden` olarak işaretler.
- Ekran okuyucu bilgisi değişmez: alanlar `aria-required` taşır ve işaret `aria-hidden` olduğu için iki kez okunmaz.
- Genel `.text-danger` veya ikon kuralı yazılmadı.

**Hata**
- Alan kenarlığı ve odak halkası kırmızıdır; odaklanmış hatalı alan da kırmızı kalır.
- Alanın altında 14 px, 600 ağırlıkta açıklama gösterilir.
- Moodle'ın `aria-describedby` bağlantısı korunur.

**Kontroller**
- Ortak ölçüler: 44 px yükseklik, 8 px köşe, #7F93AE kenarlık; ok sağda 12 px, alan metni ile ok arasında 40 px pay.
- Odak: birincil renk kenarlık ve 3 px halka; ölçü değişmez.
- Pasif durum: #EEF2F7 zemin ve soluk metin.
- Yerel `select` öğeleri korunur. Uzun seçenekler kapalı durumda üç noktayla kısalır; açılan yerel listede tam metin görünür.
- Hesap formları daha özgül kurallarında token okuduğu için yalnızca token değeri (44) verildi.

**Otomatik tamamlama**
- Seçim etiketi kompakttır: 28 px, açık nötr zemin, ince kenarlık; Bootstrap'in `!important` renk yardımcısı değişkenle değiştirildi.
- Liste odaklanınca etkin seçim mavi çerçeveyle gösterilir.
- Metin olarak gelen ▼ işareti, select'lerle aynı ok görseline çevrilir.
- Öneri listesi 320–480 px genişliktedir.

**Yardım ve bölüm kontrolleri**
- Yardım ikonu nötr renktedir (24 px hedef; üzerine gelince birincil renk, odakta çerçeve).
- Accordion okunun arkasındaki dolu kare kaldırıldı; şeffaf 32 px alan kullanılır, üzerine gelince açık gri olur.
- Başlık ve ok aynı satırdadır.
- `aria-expanded` ve “Tümünü genişlet/daralt” davranışına dokunulmadı.

**Dosya yükleme**
- Seçme düğmesi ve sürükleme alanı tek, ince kesikli yüzeydedir.
- Büyük indirme oku gizlendi; yerine 24 px yükleme ikonu kullanılır.
- Sıra: ikon, yönlendirme metni, düğme.
- Sürükleme anında birincil kenarlık ve zemin; hata durumunda kırmızı kenarlık.
- Dosya türü ve boyut sınırı eklenmedi: kurs yükleme ekranında yapılandırmadan gelen bir sınır metni yok.

**Editör**
- TinyMCE çerçevesi ortak alan sistemindedir: 1 px #7F93AE, 8 px köşe, odakta halka.
- Araç çubuğu altında ince ayırıcı vardır; tam ekranda köşe yok.
- Menüler ve işlevler gizlenmedi.

## 3. Doğrulama

Gerçek uygulamada, yönetici olarak yapıldı.

**Ölçüm taraması:** 7 form × 4 genişlik (1440, 1024, 768, 390), birleşik CSS ile:
- kategori ayarları
- kurs ayarları
- etkinlik ekleme (ödev)
- grup oluşturma
- kurs yükleme
- kullanıcı ekleme (site yönetimi)
- hesap düzenleme

Sonuç:
- görünür kırmızı zorunluluk ikonu: 0
- görünür “Gerekli” açıklaması: 0
- “Zorunlu” işareti: her zorunlu alanda var
- 44 px'den kısa kontrol: 0
- sayfa taşması: yok

Ayrıca 12 form 1440, 768 ve 390'da iç içe yüzey açısından tarandı; eğitim, kategori ve site yönetimi formlarında iç içe kart çıkmadı.

**Durum testleri** (kategori ayarları):
- **Odak:** 44 px korunur; mavi kenarlık ve 3 px halka.
- **Sunucu hatası:** “Gerekli” metni, `is-invalid`, `aria-describedby` ve kırmızı kenarlık; odakta da kırmızı.
- **Açık liste:** Aşağı ok tuşu listeyi açar; Escape kapatır.
- **Seçim listesine klavye odağı:** Etkin etiket mavi çerçeveyle görünür.
- **Pasif durum:** Kurs ayarlarındaki bitiş tarihi önceki turda doğrulandı; aynı kural ortak katmanda.

**Editör:**
- Tam ekran: 1440 × 900, köşe yok; çıkış çalışıyor.
- Görsel penceresi: “Görsel ekle” açılıp Escape ile kapanıyor; odak “Görüntü” düğmesine dönüyor.
- 390 px'de taşan araçlar menüsü açılıyor (18 düğme) ve sayfa taşmıyor.

**Hareket azaltma:** Form katmanındaki geçişler `prefers-reduced-motion` altında kapatılır.

**Ekran görüntüleri** (`docs/ui-audit/screenshots/forms-r1/`):

| Ekran | Önce | Sonra |
|---|---|---|
| Kategori ayarları | `before-category-edit-admin-1440.png` | `after-category-edit-admin-1440.png`, `after-category-edit-error-admin-1440.png`, `after-category-autocomplete-open-admin-1440.png`, `after-category-edit-admin-390.png` |
| Kurs yükleme | `before-uploadcourse-admin-1440.png` | `after-uploadcourse-admin-1440.png`, `after-uploadcourse-admin-390.png` |

Görüntüler tarayıcının tam görünüm alanından (CDP `Page.captureScreenshot`) alındı ve tek tek açılıp incelendi. Mobil kategori görüntüsündeki mavi “button .tox-tbtn” etiketi tarayıcı aracının tıklama vurgusudur, sayfanın parçası değildir.

## 4. Doğrulanmayanlar ve sınırlar

- **Dosya yükleme durumları:** Gerçek dosya yükleme ve “Önizleme” sonraki adımı tetiklenmedi.
  - Yükleniyor, yüklenen dosya ve sürükleme anı için CSS durumları yazıldı, ancak gerçek dosyayla görülmedi.
  - Kurs yükleme önizleme ekranı bu turda açılmadı.
- **Rol ve ekran kapsamı:**
  - Eğitmen ve öğrenci rolleriyle form testi yapılmadı; bu formlar yönetici/eğitmen yetkisi ister.
  - Hesap formları (`wam-account`) kendi kart düzenini korur; bu turda yalnızca kontrol yüksekliği 44 px'e hizalandı. İstenen kapsam listesinde yoktu.
- **Kurs yükleme dosya alanı:** Moodle bu alanın gizli input'una `aria-required` koymaz. Zorunluluk görsel olarak “Zorunlu” ile görünür, ancak yardımcı teknolojiye çekirdek davranış aynen kalır; önceki durumdan farkı yok.

## 5. Diğer hatlara iletilenler

- **Common Controls R1** (`wam-controls.css`):
  - Seçim etiketi Moodle'ın `.m-1` (4 px, `!important`) boşluğu nedeniyle arama alanından 4 px içeride.
  - Öneri: seçim satırını `margin-left: -4px` ile geri kaydırıp etiketler arası boşluğu `.m-1` üzerinden bırakmak.
  - 390 görüntüsünde bölüm başlıklarının altında kısa bir çizgi ve yardım ikonlarında alt çizgi görünüyor; bu kurallar o katmandan geliyor ve kontrol edilmeli.
- **Site yönetimi:**
  - `wam-admin.css` §8'deki mform yerleşim kuralları (etiket üstte, `display:block`) artık ortak ızgarayla (220 px etiket sütunu) eziliyor; temizlenebilir.
  - 390 px'de sağ blok çekmecesinin yüzen düğmesi son alanın sağ kenarına biniyor.

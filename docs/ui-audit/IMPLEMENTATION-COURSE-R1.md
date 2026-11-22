# Uygulama raporu — Eğitim detay ve yönetim ekranları (Kurs R1)

Commit, push veya deploy yapılmadı. Diğer hatların çalışma ağacındaki değişiklikleri korundu: ortak header, site yönetimi, hesap ve katalog. Ayrıntılı envanter: `COURSE-UI-INVENTORY.md`; tasarım kararları: `COURSE-UI-DESIGN.md`.

## 1. Sayılar

| | Adet |
|---|---|
| İncelenen benzersiz ekran (yönetici, modal dahil) | 43 |
| Ek rol incelemesi | eğitmen 10 rota, öğrenci 10 rota (1440 + 390) |
| Değişen ekran | 42 (22 ekrana özgü, 20 yalnızca ortak düzen) |
| Görsel doğrulanan (ekran görüntüsü incelendi) | 13 masaüstü, 4 mobil |
| Ölçümle doğrulanan | 42 masaüstü; 13 rota 1024/768/390 |
| İncelenmeyen | 4 (veri değiştiren işlemler) |
| Kurs ekranı olmayan | 1 (Brickfield site kayıt formu) |

“Ölçümle doğrulanan” ekranlarda gerçek sayfa iframe içinde yüklendi ve şunlar ölçüldü: sol hizalama, belge genişliği, H1 ve H2 ölçüleri, kolondan taşan öğeler, hata kutuları. Bu ekranlar görsel doğrulama sayılmadı.

## 2. Değişen dosyalar

| Dosya | Değişiklik |
|---|---|
| `public/local/wamakademi/lib.php` | Eklemeler: `local_wamakademi_course_page_kind()`, `local_wamakademi_course_body_classes()`, `local_wamakademi_course_config()`. Gövde sınıfları `before_http_headers` içinde eklenir; `wam-course.css`, `wamCourseConfig` ve `course_ui.js` `before_standard_html_head` içinde yüklenir. `local_wamakademi_is_account_page()` daraltıldı: `grade-report-overview-index` yalnızca site kursunda hesap ekranıdır. |
| `public/local/wamakademi/css/wam-course.css` | Yeni dosya. Tamamı `body.wam-ui.wam-course*` kapsamında. |
| `public/local/wamakademi/js/course_ui.js` | Yeni dosya: kurs dizini etiketi, “Katılımcı ekle” etiketi, ad/e-posta araması, gelişmiş filtre ve baş harf açılırları. |
| `public/local/wamakademi/lang/tr/local_wamakademi.php`, `lang/en/local_wamakademi.php` | Sona 8 dize eklendi (`course*`). Mevcut dizelere dokunulmadı. |
| `public/local/wamakademi/styles.css` | Yalnızca “Kurs detayı sadeleştirme” bloğundaki `1.55rem !important` H1 kuralı kaldırıldı. Kural başlangıç commit'indendi; bugün başka hat değiştirmemişti. |

**Depo dışındaki ortam değişikliği (deploy adımı olarak taşınmalı):**
- Konum: `WAMAcademy-moodledata/lang/tr_local/`. Bunlar Türkçe dil paketi düzeltmeleridir; canlıda “Dil özelleştirme” ekranıyla da yapılabilir.

  | Dosya | Dize | Değer |
  |---|---|---|
  | `moodle.php` | `applyfilters` | Filtreleri uygula |
  | `moodle.php` | `filtertype` | Filtre türü |
  | `moodle.php` | `hidex` | {$a} sütununu gizle |
  | `moodle.php` | `showx` | {$a} sütununu göster |
  | `user.php` | `countparticipantsfound` | {$a} katılımcı bulundu |
  | `course.php` | `filterbyname` | Ada göre filtrele |

- Dağıtımdan sonra `php admin/cli/purge_caches.php --lang --theme` çalıştırılmalı. Tema önbelleği, `styles.css` değişikliği için gerekir.

## 3. Önce / sonra

Görseller `docs/ui-audit/screenshots/course-r1/` altında.

| Ekran | Önce | Sonra |
|---|---|---|
| Kurs (yönetici 1440) | `before-course-view-admin-1440.png` | `after-course-view-admin-1440.png` |
| Ayarlar | `before-settings-admin-1440.png` | `after-settings-admin-1440.png`, `after-settings-dates-admin-1440.png`, `after-settings-end-admin-1440.png`, `after-settings-error-admin-1440.png`, `after-settings-admin-390.png`, `after-settings-dates-admin-390.png` |
| Katılımcılar | `before-participants-admin-1440.png`, `before-participants-admin-390.png` | `after-participants-admin-1440.png`, `after-participants-search-tr-admin-1440.png`, `after-participants-advanced-admin-1440.png`, `after-participants-admin-390.png`, `after-participants-teacher-1440.png` |
| Katılımcı ekle penceresi | `before-enrol-modal-admin-1440.png` | `after-enrol-modal-noresult-admin-1440.png`, `after-enrol-modal-admin-390.png` |
| Değerlendirici raporu | `before-grader-admin-1440.png` | `after-grader-admin-1440.png`, `after-grader-drawerclosed-admin-1440.png`, `after-grader-admin-390.png` |
| Not defteri kurulumu | — | `after-grade-setup-admin-1440.png` |
| Kullanıcı raporu (öğrenci) | `before-grades-user-student-1440.png` | `after-grades-user-student-1440.png` |
| Etkinlikler | `before-activities-admin-1440.png` | `after-activities-admin-1440.png`, `after-activities-expanded-admin-1440.png` |
| Soru bankaları | — | `after-question-banks-admin-1440.png` |
| Canlı günlükler | — | `after-report-loglive-admin-1440.png` |
| Rozet | — | `after-badges-admin-1440.png` |
| Ödev | — | `after-mod-assign-admin-1440.png` |

Görsellerde yalnızca yerel test hesapları ve `@localhost.test` e-postaları var; sesskey, parola veya oturum bilgisi yok.

## 4. İşlev testleri

### Katılımcılar (yönetici, 1440)
- **Arama:**
  - “şükrü” → 1 sonuç (sunucu tarafı, Türkçe karakterle).
  - “zzzyok” → “0 katılımcı bulundu” ve boş durum.
  - Temizle → 4 sonuç; odak arama kutusuna döner.
- **Açılırlar:** Gelişmiş filtreler ve baş harf düğmeleri doğru `aria-expanded` değeri verir.
- **Toplu seçim:** Tümünü seç → 4 satır seçilir ve toplu işlem menüsü etkinleşir; seçim kaldırılınca menü yeniden pasifleşir. Hiçbir işlem çalıştırılmadı.
- **Sıralama:** E-posta artan ve azalan çalışıyor; tablo yenilendikten sonra özel araçlar yerinde kalıyor. Test sonrası “Tablo tercihlerini sıfırla” ile varsayılan sıra (soyadı) geri yüklendi.

### Katılımcı ekle penceresi
- Öneriler, seçim hapı ve “Öneri yok” durumu çalışıyor.
- Escape önce öneri listesini kapatır.
- Rol seçiminden ya da mobilde pencereden Escape → pencere kapanır, odak “Katılımcı ekle”ye döner.
- Kimse kaydedilmedi; katılımcı sayısı 4 kaldı.

### Ayarlar
- Tam ad boşaltılınca “Tam adı eksik” hatası kırmızı görünür; zorunlu simgesi gri kalır.
- Sticky çubuk “Etiketler” alanını ve “Gerekli” notunu örtmüyor.
- Değer geri yüklendi, form kirli durumu sıfırlandı, kaydedilmedi.

### Değerlendirici
- Çekmece açıkken sayfa düzeyinde yatay kayar; kapalıyken 1440'ta tablo tamamen sığar.
- Çekmece tercihi test sonrası açık konuma geri alındı.

### Roller
- Eğitmen: 10 rota hatasız.
- Öğrenci:
  - Ayarlar, değerlendirici raporu ve başka öğrencinin raporu yetki hatası verir (korundu).
  - Notlar yalnızca kendi raporunu açar.
  - Katılımcılar ekranında “Katılımcı ekle” düğmesi yok.

### Hareket azaltma
`prefers-reduced-motion: reduce` emülasyonunda sekme, seçim menüsü, açılır düğmeler ve satır işlemleri 0 s ölçüldü.

### Regresyon
Öğrenci ve yönetici olarak aşağıdaki sayfalar kontrol edildi. Hiçbiri `wam-course` sınıfı ya da `wam-course.css`/`course_ui.js` almıyor; hesap ve yönetim sınıfları değişmedi.
- Kontrol paneli, Eğitimlerim, katalog
- Profil, düzenleme, tercihler, not genel görünümü (kullanıcı menüsünden), bildirim tercihleri, rozetlerim, takvim
- Site yönetimi ana sayfası ve arama, kullanıcılar, kurs yönetimi, ayar sayfaları, site günlükleri

## 5. Sınırlar ve bilinen durumlar
- **Değerlendirici raporu kendi kaydırma alanına alınmadı.** Yapışkan başlık, sütun ve ortalama satırı görünüm alanına bağlı (`grade.scss`, `stickycolspan.js`). Mobilde sayfa yatay kayar; bu Moodle'ın davranışıdır. Ayrıntı: DESIGN §6.
- **Arama kutusu yalnızca anahtar kelime koşulunu yönetir.** Rol, durum ve grup koşulları “Gelişmiş filtreler” içindedir, çünkü datafilter'ın genel bir JS API'si yok. Arama DOM sözleşmesi (`data-filter*`) üzerinden çalışır.
- **Escape ve otomatik tamamlama:** Katılımcı ekle penceresinde, otomatik tamamlama kutusunun içindeyken Escape'i çekirdek bileşen tüketir. İlk basış öneri listesini kapatır.
- **Mobil sticky çubuk:** Aşağı kaydırırken Boost tarafından gizlenir (çekirdek davranış).
- **Moodle'ın kendi davranışı olan durumlar:**
  - Canlı günlükler sayfası başlık basmaz; tablo kendi kartında yatay kayar.
  - Soru bankalarındaki ad hoc görev uyarısı, yerelde cron çalışmadığı için görünür.
  - “0 of 3” ifadesindeki “of” çekirdek dil paketinde çevrilmemiş; `tr_local` ile düzeltilebilir.
- **Kullanıcı turu:** Değerlendirici raporundaki kullanıcı turu “Turu atla” ile kapatıldı; yöneticinin tur tercihi kaydedildi.
- **Test verisi** WAMTEST01 içinde, yerel generator ile oluşturuldu:
  - Öğrenciler: `wamtest_ogrenci2` (uzun ad ve e-posta) ve `wamtest_ogrenci3`.
  - Grup: “WAM Test Grubu”.
  - Ödev: “WAM Test Ödevi”.
  - Gerçek kayıt veya not değiştirilmedi.

## 6. Diğer hatlara ihtiyaçlar
- **Ortak header:**
  - Kurs içindeyken “Kontrol paneli” bağlantısı etkin görünüyor; kurs bağlamında “Eğitimlerim” beklenir.
  - Mobilde marka adı “WAM” olarak kısalıyor.
- **Site yönetimi:** Erişilebilirlik araç seti (Brickfield) kurstan site kayıt formuna yönleniyor; bu bir yapılandırma kararı.

## 7. İncelenmeyen veya erişilemeyen ekranlar
- Kural gereği tetiklenmedi (veri değiştiren işlemler): not içe aktarma, kurs içe aktarma, geri yükleme, kurs sıfırlama.
- Kurs ekranı değil: Erişilebilirlik araç seti, site kayıt formuna yönleniyor.
- Ödev puanlama arayüzü (`mod/assign/grader`) ve tek görünümde not kaydı açılmadı; not değiştirilmedi.

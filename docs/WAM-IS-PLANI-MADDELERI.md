# WAM Akademi Uygulaması (Şirket İçi Dijital Eğitim)

Üst yönetim Excel iş planına aktarılacak modül başlıkları ve iş maddeleri.

Bu belge yalnızca analiz ve metin önerisidir. Excel dosyası oluşturulmamış; kod, veritabanı veya yapılandırma değiştirilmemiştir.

Kaynak doğrulama: `docs/APPLICATION-OVERVIEW.md`, `docs/ROLE-ACADEMY.md`, `docs/ui-audit/IMPLEMENTATION-CONTENT-WORKFLOW-R1.md`, `public/local/wamakademi/` kodu.

---

## Excel’e aktarılacak liste

### Roller ve yetki ayrımı

- Site yöneticisi, akademi yöneticisi, eğitmen ve öğrenci işlerinin birbirinden ayrılması
- Akademi yöneticisinin eğitim açması ve mevcut çalışanı eğitime kaydetmesi
- Eğitmenin içerik ve video yönetmesi; çalışan kaydının akademi yöneticisinde kalması
- Öğrenci ekranında yönetim düğmelerinin gösterilmemesi

### Eğitimlerin oluşturulması

- Yetkili kullanıcının yeni eğitim açması
- Her eğitime değişmeyen bir eğitim numarasının otomatik verilmesi
- Eğitim adı, açıklaması ve kapak görselinin tanımlanması
- Gizli eğitimin eğitim kataloğunda listelenmemesi

### Çalışan ve eğitim kaydı

- Site yöneticisinin yeni çalışan hesabı açması
- Var olan hesabın ilgili eğitime kaydedilmesi ve kaydın kaldırılması
- Eğitime eğitmen veya öğrenci rolünün atanması
- Eğitim katılımcılarının ad, e-posta ve role göre aranıp düzenlenmesi

### Eğitim kataloğu ve kişisel alan

- Yayındaki eğitimlerin listelenmesi ve aranması
- Çalışanın kendi eğitimlerini Atandı, Devam ediyor ve Tamamlandı olarak görmesi
- Ana sayfada devam eden ve tamamlanan eğitim sayılarının gösterilmesi
- Takvimde yaklaşan etkinliklerin gösterilmesi

### Video ve eğitim içeriği

- Eğitmenin videoyu öğrenciye kapalı taslak olarak yüklemesi
- Taslağın önizlenmesi, yayımlanması ve yayından kaldırılması
- Bir eğitimdeki videoların sırasının değiştirilmesi
- Sunum dosyasının eğitim içinde açılması

### İlerleme ve tamamlanma

- Videonun gerçekten izlenen bölümlerine göre yüzde ilerlemenin kaydedilmesi
- Videonun yüzde 95’i izlenince dersin tamamlandı sayılması
- Sona atlanarak izlenmeyen videonun tamamlandı sayılmaması
- Çalışanın kendi eğitimindeki ortalama ilerlemesinin gösterilmesi

### Değerlendirme

- Çalışanın kendi notlarını görmesi
- Ödev teslimlerinin eğitim ve kişi bazında takip edilmesi
- Ödevlerin değerlendirilme durumunun raporlanması

### Raporlama

- Yetkili kullanıcının hazır rapor ekranını açması
- Eğitim, katılım ve video tamamlanma raporunun tek listede üretilmesi

### İletişim

- Site içi ileti ve bildirimlerin kullanılması
- Tamamlanmayan eğitimler için çalışana hatırlatma gönderilmesi

---

## Değerlendirme

Her madde için durum: **Mevcut ve doğrulandı** | **Kısmen mevcut / tamamlanması gerekiyor** | **Öneri / henüz mevcut değil** | **Durumu doğrulanamadı**

### Roller ve yetki ayrımı

| Madde | Durum | Kanıt |
|---|---|---|
| Site yöneticisi, akademi yöneticisi, eğitmen ve öğrenci işlerinin birbirinden ayrılması | Mevcut ve doğrulandı | `docs/ROLE-ACADEMY.md`, `public/local/wamakademi/classes/roles.php` |
| Akademi yöneticisinin eğitim açması ve mevcut çalışanı eğitime kaydetmesi | Mevcut ve doğrulandı | `roles.php` (`academymanager` yetkileri); hesap açma / eğitim silme / video yok |
| Eğitmenin içerik ve video yönetmesi; çalışan kaydının akademi yöneticisinde kalması | Mevcut ve doğrulandı | Eğitmen kayıt yetkileri PREVENT; `local/wamakademi:managevideos` |
| Öğrenci ekranında yönetim düğmelerinin gösterilmemesi | Mevcut ve doğrulandı | `lib.php` → `local_wamakademi_is_student_view`; `js/wam_menu.js` |

### Eğitimlerin oluşturulması

| Madde | Durum | Kanıt |
|---|---|---|
| Yetkili kullanıcının yeni eğitim açması | Mevcut ve doğrulandı | Menü “Eğitim oluştur”; `classes/access.php` → `course_create_url` |
| Her eğitime değişmeyen bir eğitim numarasının otomatik verilmesi | Mevcut ve doğrulandı | `local_wamakademi_coursenumber`; dil metinleri `coursenumber*` |
| Eğitim adı, açıklaması ve kapak görselinin tanımlanması | Mevcut ve doğrulandı | Moodle kurs formu + `catalog.php` / `mycourses.php` kapak kullanımı |
| Gizli eğitimin eğitim kataloğunda listelenmemesi | Mevcut ve doğrulandı | `catalog.php` (`visible`); `ROLE-ACADEMY.md` |

### Çalışan ve eğitim kaydı

| Madde | Durum | Kanıt |
|---|---|---|
| Site yöneticisinin yeni çalışan hesabı açması | Mevcut ve doğrulandı | Moodle `/user/editadvanced.php?id=-1`; `APPLICATION-OVERVIEW.md` |
| Var olan hesabın ilgili eğitime kaydedilmesi ve kaydın kaldırılması | Mevcut ve doğrulandı | Katılımcılar → Katılımcı ekle / kaydı kaldır; akademi yöneticisi `enrol/manual:*` |
| Eğitime eğitmen veya öğrenci rolünün atanması | Mevcut ve doğrulandı | Akademi yöneticisi yalnız `editingteacher` ve `student` atayabilir |
| Eğitim katılımcılarının ad, e-posta ve role göre aranıp düzenlenmesi | Mevcut ve doğrulandı | Katılımcı dil dizeleri; `js/participants_table.js` |

### Eğitim kataloğu ve kişisel alan

| Madde | Durum | Kanıt |
|---|---|---|
| Yayındaki eğitimlerin listelenmesi ve aranması | Mevcut ve doğrulandı | `catalog.php` |
| Çalışanın kendi eğitimlerini Atandı, Devam ediyor ve Tamamlandı olarak görmesi | Mevcut ve doğrulandı | `mycourses.php` durum hesaplaması |
| Ana sayfada devam eden ve tamamlanan eğitim sayılarının gösterilmesi | Mevcut ve doğrulandı | `index.php` (`ongoingcount` / `completedcount`) |
| Takvimde yaklaşan etkinliklerin gösterilmesi | Mevcut ve doğrulandı | `calendar.php` (Moodle takvim verisinin WAM ekranı) |

### Video ve eğitim içeriği

| Madde | Durum | Kanıt |
|---|---|---|
| Eğitmenin videoyu öğrenciye kapalı taslak olarak yüklemesi | Mevcut ve doğrulandı | `upload.php`; Content Workflow R1 raporu |
| Taslağın önizlenmesi, yayımlanması ve yayından kaldırılması | Mevcut ve doğrulandı | `publish.php`; Content Workflow R1 |
| Bir eğitimdeki videoların sırasının değiştirilmesi | Mevcut ve doğrulandı | `videos.php` |
| Sunum dosyasının eğitim içinde açılması | Kısmen mevcut / tamamlanması gerekiyor | `pptx_view.php` kodda var; eğitim iş akışına bağlandığı doğrulanmadı |

### İlerleme ve tamamlanma

| Madde | Durum | Kanıt |
|---|---|---|
| Videonun gerçekten izlenen bölümlerine göre yüzde ilerlemenin kaydedilmesi | Mevcut ve doğrulandı | `save_progress.php`; tablo `local_wamakademi_progress` |
| Videonun yüzde 95’i izlenince dersin tamamlandı sayılması | Mevcut ve doğrulandı | `save_progress.php` (`progress >= 95`) |
| Sona atlanarak izlenmeyen videonun tamamlandı sayılmaması | Mevcut ve doğrulandı | Content Workflow R1 testleri; boş aralık → watched artmaz |
| Çalışanın kendi eğitimindeki ortalama ilerlemesinin gösterilmesi | Mevcut ve doğrulandı | `mycourses.php`, `index.php` |

Not: Ana sayfa tamamlanması (ortalama yüzde 100) ile Eğitimlerim’deki ders tamamlanması (tüm dersler `completed`) aynı tanımı kullanmaz — kısmen tutarsız.

Resmi eğitim tamamlanması (sertifika, zorunlu sıra, Moodle tamamlanma kutusu) video ilerlemesine bağlı değildir (`completion = 0` yüklemede). Bu, kısmen mevcut / tamamlanması gereken bir boşluktur; Excel listesinde ayrı satır olarak “resmi tamamlanma birleştirmesi” öneri kapsamında yönetilir.

### Değerlendirme

| Madde | Durum | Kanıt |
|---|---|---|
| Çalışanın kendi notlarını görmesi | Mevcut ve doğrulandı | Menü not bağlantısı (`lib.php`); Moodle not ekranı — WAM’a özel değerlendirme akışı değil |
| Ödev teslimlerinin eğitim ve kişi bazında takip edilmesi | Öneri / henüz mevcut değil | WAM ödev akışı / özel ekran yok |
| Ödevlerin değerlendirilme durumunun raporlanması | Öneri / henüz mevcut değil | Aynı gerekçe |

### Raporlama

| Madde | Durum | Kanıt |
|---|---|---|
| Yetkili kullanıcının hazır rapor ekranını açması | Kısmen mevcut / tamamlanması gerekiyor | Menü → Moodle `reportbuilder`; genel araç, akademi iş raporu değil |
| Eğitim, katılım ve video tamamlanma raporunun tek listede üretilmesi | Öneri / henüz mevcut değil | İlerleme kişi–video satırı olarak durur; yönetici dışa aktarma yok |

### İletişim

| Madde | Durum | Kanıt |
|---|---|---|
| Site içi ileti ve bildirimlerin kullanılması | Kısmen mevcut / tamamlanması gerekiyor | Moodle ileti/bildirim; `get_message_people.php` kişi listesini daraltır |
| Tamamlanmayan eğitimler için çalışana hatırlatma gönderilmesi | Öneri / henüz mevcut değil | Kural veya gönderim bulunamadı |

### Durumu doğrulanamadı (ortam)

- Canlı ortam (`http://10.20.8.74`) ile yerel kod sürümünün birebir aynı olduğu bu çalışmada yeniden ölçülmedi.
- Katalogdan çalışanın kendi kendine eğitime yazılması bu kodda yok; canlıda self-enrol açık mı ayardan doğrulanamadı.

---

## Yönetim sunumunda öncelikler

### Önce gösterilecek (bitmiş iş)

1. Rol ayrımı (site yöneticisi / akademi yöneticisi / eğitmen / öğrenci)
2. Eğitim açma ve eğitim numarası
3. Çalışan hesabı ve eğitime kayıt
4. Video taslağı, önizleme ve yayım
5. Çalışanın kendi video ilerlemesi ve Eğitimlerim durumları

### Açık iş (bitmiş gibi yazılmadan)

1. Eğitim + katılım + video tamamlanma yönetim raporu
2. Resmi tamamlanmanın video ilerlemesiyle birleştirilmesi
3. Gerekiyorsa ödev teslim / değerlendirme takibi
4. Tamamlanmayan eğitim hatırlatması

### Birbirine bağlı olanlar

- Hesap açma (site yöneticisi) olmadan eğitim kaydı (akademi yöneticisi) yapılamaz.
- Eğitim kaydı olmadan öğrencinin Eğitimlerim ve ilerleme ekranı dolmaz.
- Video yayımlanmadan öğrenci içeriği görmez; taslak bilinçli olarak gizlidir.
- Yönetim raporu, mevcut ilerleme kayıtlarına bağlıdır; rapor yazılmadan bu kayıtlar yönetici listesine çıkmaz.
- Hatırlatma, tamamlanma tanımı netleşmeden (yüzde 95 video mü, eğitimin tüm dersleri mi) kurulmamalıdır.
- Ödev raporu, ancak eğitimlerde ödev kullanılacaksa ayrı modül olur; video ilerlemesinin yerine geçmez.

### Moodle hazır / WAM özel (sunum cümlesi)

Hesap, kayıt, not, ileti ve genel rapor Moodle’dır. Eğitim numarası, video taslak/yayın, izleme yüzdesi, Eğitimlerim durumları ve akademi yöneticisi rolü WAM’a özeldir.

# Eğitim detay ve yönetim ekranları — envanter (Kurs R1)

Ortam: Moodle 5.2.2+ / Boost, yerel `http://localhost:8081`, test kursu **WAMTEST01 — “WAM Test Egitimi Excel”** (course id 2, context 18).
Envanter gerçek gezinmeden (sekmeler, “Daha fazla”, üçüncül seçim menüleri) çıkarıldı; adresler URL tahmini değildir.

Roller (yerel test hesapları; parolalar bu belgede yoktur):

| Rol | Hesap | Kurs rolü |
|---|---|---|
| Yönetici | admin | site yöneticisi |
| Eğitmen | wamtest_egitmen | editingteacher |
| Öğrenci | wamtest_ogrenci | student |

Doğrulama işaretleri:
- **G**: Ekran görüntüsü alındı ve incelendi.
- **Ö**: DOM ölçümü yapıldı. Ölçülenler: sol hizalama, sayfa taşması, başlık ölçüsü ve kolondan taşan öğeler.
- **—**: Doğrulanmadı.

Masaüstü 1440; mobil 390. Ek olarak, mobil sütununda Ö yazan 13 yönetici rotası 1024 ve 768 genişliklerinde de ölçüldü.

## 1. Kurs sekmeleri (yönetici)

| # | Ekran | Rota | Amaç | Tür | Öncesi görsel sorun | Durum | Masaüstü | Mobil | Engel |
|---|---|---|---|---|---|---|---|---|---|
| 1 | Kurs | `/course/view.php?id=2` | Kurs içeriği | içerik | Sekmeler 830 px ortalı, başlıkla hizasız; H1 24.8 px (eski `!important`) | Değişti | G | Ö | — |
| 2 | Ayarlar | `/course/edit.php?id=2` | Kurs ayarları formu | form | Geniş form, etiket sütunu dar, tarih grubu sarıyor, zorunlu simgesi hata kırmızısı | Değişti | G (4 durum) | G | — |
| 3 | Katılımcılar | `/user/index.php?id=2` | Kayıtlı kullanıcılar | liste/tablo | Tek işlem yok; filtre kurucu ilk seviyede; harf filtreleri iki satır; tablo sayfa düzeyinde taşıyor | Değişti | G (4 durum) | G | — |
| 4 | Katılımcı ekle penceresi | Katılımcılar → “Katılımcı ekle” | Elle kayıt | modal | Ham Bootstrap, küçük hedefler | Değişti | G (öneri yok) | G | Kayıt yapılmadı (bilerek) |
| 5 | Kursa kayıt yöntemleri | `/enrol/instances.php?id=2` | Yöntem listesi | tablo | Ortak düzen dışı | Ortak düzen | Ö | — | Silme/düzenleme tetiklenmedi |
| 6 | Rol yeniden adlandırma | `/enrol/renameroles.php?id=2` | Rol adları | form | Ortak düzen dışı | Ortak düzen | Ö | — | Kaydedilmedi |
| 7 | Gruplar | `/group/index.php?id=2` | Grup yönetimi | çift liste | Ortak düzen dışı | Ortak düzen | Ö | Ö | — |
| 8 | Gruplamalar | `/group/groupings.php?id=2` | Gruplama listesi | tablo | Ortak düzen dışı | Ortak düzen | Ö | — | — |
| 9 | Grup önizle | `/group/overview.php?id=2` | Grup özeti | tablo | Ortak düzen dışı | Ortak düzen | Ö | — | — |
| 10 | İzinler | `/admin/roles/permissions.php?contextid=18` | Kurs izinleri | tablo | H2 32 px (H1'den büyük) | Değişti | Ö | — | İzin değiştirilmedi |
| 11 | Diğer kullanıcılar | `/enrol/otherusers.php?id=2` | Kayıtsız rol atamaları | tablo | Ortak düzen dışı | Ortak düzen | Ö | — | — |
| 12 | İzinleri kontrol et | `/admin/roles/check.php?contextid=18` | İzin denetimi | form | H2 32 px | Değişti | Ö | — | — |

## 2. Notlar (yönetici)

Notlar sekmesi `/grade/report/index.php?id=2` → değerlendirici raporuna yönlenir. Üçüncül seçim menüsü: **Görüntüle** (değerlendirici, geçmiş, gözden geçirme, tek görünüm, özet, kullanıcı), **Kurulum** (not defteri kurulumu, kurs not ayarları, değerlendirici tercihleri), **Daha fazla** (ölçekler, harfler, içe aktar, dışa aktar).

| # | Ekran | Rota | Tür | Öncesi görsel sorun | Durum | Masaüstü | Mobil | Engel |
|---|---|---|---|---|---|---|---|---|
| 13 | Değerlendirici raporu | `/grade/report/grader/index.php?id=2` | geniş tablo | 1366 px tablo 1120 px kolonda, belge 1682 px | Değişti (geniş düzen) | G (çekmece açık/kapalı) | G | Sayfa düzeyi kaydırma Moodle tasarımı (bkz. DESIGN §6) |
| 14 | Not geçmişi | `/grade/report/history/index.php?id=2` | filtre formu | Ortak düzen dışı | Ortak düzen | Ö | — | — |
| 15 | Gözden geçirme (kurs) | `/grade/report/overview/index.php?id=2` | rapor | Hesap ekranı sınıfı alıyordu | Değişti | Ö | — | — |
| 16 | Tek görünüm | `/grade/report/singleview/index.php?id=2&item=grade&itemid=2` | tablo+form | Ortak düzen dışı | Değişti (geniş düzen) | Ö | — | Not kaydedilmedi |
| 17 | Not özeti | `/grade/report/summary/index.php?id=2` | tablo | Ortak düzen dışı | Ortak düzen | Ö | — | — |
| 18 | Kullanıcı raporu | `/grade/report/user/index.php?id=2&userid=5` | tablo | Ortak düzen dışı | Ortak düzen | Ö | Ö | — |
| 19 | Not defteri kurulumu | `/grade/edit/tree/index.php?id=2` | ağaç tablo | Çift çerçeve (kart + gri sarmal) | Değişti | G | Ö | “Değişiklikleri kaydet” basılmadı |
| 20 | Kurs not ayarları | `/grade/edit/settings/index.php?id=2` | form | Ortak düzen dışı | Ortak düzen | Ö | — | Kaydedilmedi |
| 21 | Değerlendirici tercihleri | `/grade/report/grader/preferences.php?id=2` | form | Ortak düzen dışı | Ortak düzen | Ö | — | Kaydedilmedi |
| 22 | Ölçekler | `/grade/edit/scale/index.php?id=2` | tablo | Ortak düzen dışı | Ortak düzen | Ö | — | — |
| 23 | Harfler | `/grade/edit/letter/index.php?id=18` | tablo | Ortak düzen dışı | Ortak düzen | Ö | — | Düzenleme açılmadı |
| 24 | Dışa aktar (ODS) | `/grade/export/ods/index.php?id=2` | form | H2 32 px | Değişti | Ö | — | Dışa aktarma başlatılmadı |
| — | İçe aktar | `/grade/import/…` | — | — | **İncelenmedi** | — | — | Veri değiştiren işlem (kural gereği atlandı) |

## 3. Etkinlikler ve “Daha fazla” (yönetici)

| # | Ekran | Rota | Tür | Öncesi görsel sorun | Durum | Masaüstü | Mobil | Engel |
|---|---|---|---|---|---|---|---|---|
| 25 | Etkinlikler | `/course/overview.php?id=2` | akordeon + tablo | Sekmeler ortalı; H2 32 px | Değişti | G (kapalı/açık) | Ö | — |
| 26 | Raporlar | `/report/view.php?courseid=2` | bağlantı listesi | H2 32 px | Değişti | Ö | — | — |
| 27 | Günlükler | `/report/log/index.php?id=2` | filtre + tablo | Ortak düzen dışı | Ortak düzen | Ö | Ö | — |
| 28 | Canlı günlükler | `/report/loglive/index.php?id=2` | canlı tablo | Tablo kolondan geniş | Ortak düzen (tablo kendi kartında kayar) | G | — | Moodle sayfa başlığı basmıyor (çekirdek) |
| 29 | Etkinlik raporu (anahat) | `/report/outline/index.php?id=2` | tablo | Ortak düzen dışı | Ortak düzen | Ö | — | — |
| 30 | Katılım raporu | `/report/participation/index.php?id=2` | form | Ortak düzen dışı | Ortak düzen | Ö | — | — |
| 31 | Yetkinlik dökümü | `/report/competency/index.php?id=2` | rapor | Ortak düzen dışı | Ortak düzen | Ö | — | Yetkinlik çerçevesi yok (boş durum) |
| 32 | Soru bankaları | `/question/banks.php?courseid=2` | liste | `base` yerleşimi → kurs sınıfı yoktu; sekmeler 448 px'te ortalı | Değişti | G | — | Bekleyen ad hoc görev uyarısı (cron çalışmamış, ortam verisi) |
| 33 | İçerik bankası | `/contentbank/index.php?contextid=18` | dosya ızgarası | H2 32 px | Değişti | Ö | — | Yükleme yapılmadı |
| 34 | Rozet | `/badges/index.php?type=2&id=2` | liste | H2 32 px (H1 28'den büyük) | Değişti | G | Ö | Rozet oluşturulmadı |
| 35 | Yetkinlikler | `/admin/tool/lp/coursecompetencies.php?courseid=2` | liste | H2 32 px | Değişti | Ö | — | — |
| 36 | Filtreler | `/filter/manage.php?contextid=18` | ayar tablosu | H2 32 px | Değişti | Ö | — | Kaydedilmedi |
| 37 | LTI Harici araçlar | `/mod/lti/coursetools.php?id=2` | liste | H2 32 px | Değişti | Ö | — | — |
| 38 | Erişilebilirlik araç seti | `/admin/tool/brickfield/index.php?courseid=2` | **site kayıt formu** | Kurs ekranı değil, site yönetimi kayıt formuna yönleniyor | **Değişmedi** | Ö | — | Site yönetimi hattı; kayıt yapılmadı |
| 39 | Kurs yeniden kullanımı | `/backup/view.php?id=2` | bağlantı listesi | H2 32 px | Değişti | Ö | Ö | — |
| 40 | Yedekleme | `/backup/backup.php?id=2` | sihirbaz formu | Ortak düzen dışı | Ortak düzen | Ö | — | Yedek başlatılmadı |
| 41 | Kurs kopyala | `/backup/copy.php?id=2` | form | Ortak düzen dışı | Ortak düzen | Ö | — | Kopya başlatılmadı |
| — | İçe aktar / Geri yükle / Sıfırla | `/backup/import.php`, `/backup/restorefile.php`, `/course/reset.php` | — | — | **İncelenmedi** | — | — | Veri değiştiren işlemler (kural gereği atlandı) |

## 4. Etkinlik sayfaları (yönetici)

| # | Ekran | Rota | Öncesi görsel sorun | Durum | Masaüstü | Mobil |
|---|---|---|---|---|---|---|
| 42 | Ödev | `/mod/assign/view.php?id=2` | Etkinlik başlığı 830 px ortalı; açıklama çift çerçeve | Değişti | G | Ö |
| 43 | Forum | `/mod/forum/view.php?id=1` | Etkinlik başlığı 830 px ortalı | Değişti | Ö | Ö |

## 5. Rol farkları

**Eğitmen** — yöneticiyle aynı sekmeler. Fark: “Erişilebilirlik araç seti” ve “Kurs kopyala” yok. 10 rota 1440 ve 390'da ölçüldü (Ö). Katılımcılar ekranının görüntüsü alındı (G). Hata yok, hizalama tutarlı.

**Öğrenci**
- Sekmeler: Kurs, Katılımcılar, Notlar, Etkinlikler, Yetkinlikler. 10 rota 1440 ve 390'da ölçüldü.
- Notlar sekmesi kendi kullanıcı raporuna yönlenir. Görüntüsü alındı (G): yalnızca kendi notları görünür.
- Katılımcılar: arama araçları var, “Katılımcı ekle” düğmesi yok. Bu yetkiye bağlı bir durum ve korundu.
- Yetki engelleri korundu, doğrulandı:
  - `course/edit.php` → “Üzgünüz, ancak … izniniz yok”
  - değerlendirici raporu → aynı hata
  - başka öğrencinin raporu (`userid=5`) → “Notlar görüntülenemiyor”

## 6. Sayılar

| | Adet |
|---|---|
| İncelenen benzersiz ekran (yönetici; modal dahil) | 43 |
| Ek rol incelemesi (eğitmen 10 + öğrenci 10 rota, ikişer genişlik) | 20 rota |
| Değişen (ortak düzen veya ekrana özgü) | 42 (Brickfield hariç) |
| — ekrana özgü değişiklik | 22 |
| — yalnızca ortak düzen | 20 |
| Görsel doğrulanan (G, ekran görüntüsü incelendi) | 13 masaüstü + 4 mobil |
| Ölçümle doğrulanan (Ö) | 42 masaüstü; 13 rota 1024/768/390 |
| İncelenmeyen (veri değiştiren işlem) | 4: not içe aktarma, kurs içe aktarma, geri yükleme, sıfırlama |
| Erişilemeyen / kurs ekranı olmayan | 1: Brickfield site kayıt formu |

## 7. Form R1 güncellemesi (ortak form katmanı)

Ayrıntı: `IMPLEMENTATION-FORMS-R1.md`.

Sütunlar:
- **Yüzey:** tek beyaz yüzey, iç içe kart yok.
- **Zorunlu:** kırmızı ikon ve “Gerekli” açıklaması yerine “Zorunlu” metni.
- **Kontroller:** 44 px.

Doğrulama işaretleri §1'deki gibidir (G: ekran görüntüsü incelendi, Ö: DOM ölçümü). Ölçümler 1440/1024/768/390'da yapıldı.

| Ekran | Rota | Kabuk | Yüzey | Zorunlu | Kontroller | Doğrulama |
|---|---|---|---|---|---|---|
| Kategori ayarları (yeni) | `/course/editcategory.php?id=1` | eğitim kabuğu, `wam-coursecat` (yeni) | Değişti | Değişti | Değişti | G (1440 normal/hata/açık liste, 390) + Ö |
| Yeni kategori (site) | `/course/editcategory.php?parent=0` | site yönetimi | admin §8 | Değişti | Değişti | Ö |
| Kategoride kurs ekle (yeni) | `/course/edit.php?category=1` | `wam-coursecat` | Değişti | Değişti | Değişti | Ö |
| #2 Ayarlar | `/course/edit.php?id=2` | eğitim | Değişti (bölüm kartları kaldırıldı) | Değişti | Değişti | Ö |
| Etkinlik ekleme (yeni) | `/course/modedit.php?add=assign&course=2` | eğitim | Değişti | Değişti | Değişti | Ö |
| Grup oluştur (yeni) | `/group/group.php?courseid=2` | eğitim | Değişti | Değişti | Değişti (dosya alanı tek yüzey) | Ö |
| #20 Kurs not ayarları | `/grade/edit/settings/index.php?id=2` | eğitim | Değişti | — (zorunlu alan yok) | Değişti | Ö |
| #40 Yedekleme | `/backup/backup.php?id=2` | eğitim | Değişti | — | Değişti | Ö |
| Kurs yükleme (yeni) | `/admin/tool/uploadcourse/index.php` | site yönetimi | admin §8 | Değişti | Değişti (dosya alanı) | G (1440, 390) + Ö |
| Kullanıcı yükleme (yeni) | `/admin/tool/uploaduser/index.php` | site yönetimi | admin §8 | Değişti | Değişti | Ö |
| Kullanıcı ekle (yeni) | `/user/editadvanced.php?id=-1` | site yönetimi | admin §8 | Değişti | Değişti | Ö |
| Hesap düzenleme | `/user/edit.php` | hesap | Değişmedi (kapsam dışı) | Değişti | 40 → 44 px | Ö |

**Erişilemeyen veya tetiklenmeyen:**
- Gerçek dosya yüklemesi ve kurs yükleme önizleme adımı.
- Eğitmen ve öğrenci rolleriyle form testleri.

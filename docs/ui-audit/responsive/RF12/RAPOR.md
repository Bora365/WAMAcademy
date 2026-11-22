# Responsive RF12 — Kurs yönetimi formları

Tarih: 2026-11-18  
Commit/push/deploy: **yapılmadı**  
Kurs/kategori kaydetme, silme, yükleme tıklanmadı.

## Ölçüm

| Ekran | Rota | before→after pageOv (tüm VP) |
|---|---|---|
| ADM-DEVELOPMENT | `/admin/category.php?category=development` | **0 → 0** |
| ADM-COURSE-MGMT | `/course/management.php` | **0 → 0** |
| ADM-COURSE-NEW | `/course/edit.php?category=1` | **0 → 0** |
| ADM-CATEGORY-EDIT | `/course/editcategory.php?id=1` | **0 → 0** |

Yeni CSS yok (RF9/10 + course form şablonu yeterli). Kanıt: `before/`, `after/`, `relation-map.json`.

## Ekran işlevleri ve ilişkiler

### 1) ADM-DEVELOPMENT — Geliştirme

**Ne işe yarar:** Site yönetimi altında geliştirici araçları hub’ı. Hata ayıklama bayrakları, önbellek temizleme, test veri üreticileri, Behat/PHPUnit, XMLDB, hook/Swagger/web servis test istemcisi.

**Matris akrabaları:** ADM-SEARCH (Site yönetimi), ADM-SERVER, ADM-SET-DEBUGGING (RF41).

**Bu sayfadan açılan / bağlı sayfalar:**

| Sayfa | Rota |
|---|---|
| Deneysel kategori | `/admin/category.php?category=experimental` |
| Hata ayıklama | `/admin/settings.php?section=debugging` |
| SwaggerUI (REST) | `/admin/swaggerui.php` |
| Web servisi test istemcisi | `/admin/webservice/testclient.php` |
| Önbelleği temizle | `/admin/purgecaches.php` |
| Üçüncü parti kütüphaneler | `/admin/thirdpartylibs.php` |
| Kancalara genel bakış | `/admin/hooks.php` |
| Test eğitimi ver | `/admin/tool/generator/maketestcourse.php` |
| JMeter test planı | `/admin/tool/generator/maketestplan.php` |
| Test senaryoları | `/admin/tool/generator/runtestscenario.php` |
| Kabul testleri (Behat) | `/admin/tool/behat/index.php` |
| PHPUnit | `/admin/tool/phpunit/index.php` |
| Şablon kütüphanesi | `/admin/tool/templatelibrary/index.php` |
| XMLDB düzenleyicisi | `/admin/tool/xmldb/` |
| Site yönetimi sekmeleri | `/admin/search.php` (+ `#linkdevelopment` vb.) |

---

### 2) ADM-COURSE-MGMT — Eğitim ve kategori yönetimi

**Ne işe yarar:** Kategori ağacı + o kategorideki eğitim listesi. Yeni kategori/eğitim, sıralama, kategori düzenle/sil, kategori bağlamında izin/filtre/geri yükleme/içerik bankası.

**Matris akrabaları:** ADM-COURSES (RF9), ADM-COURSE-NEW / ADM-CATEGORY-EDIT (bu faz), ADM-UPLOADCOURSE (RF13), COURSE-VIEW / COURSE-EDIT (RF2), CATALOG / MYCOURSES (RF1).

**Bu sayfadan açılan / bağlı sayfalar:**

| Sayfa | Rota |
|---|---|
| Yeni kategori | `/course/editcategory.php?parent=1` |
| Kategori düzenle (= ADM-CATEGORY-EDIT) | `/course/editcategory.php?id=1` |
| Kategori kataloğu (öğrenci görünümü) | `/course/index.php?categoryid=1` |
| Yeni eğitim (= ADM-COURSE-NEW) | `/course/edit.php?category=1&returnto=catmanage` |
| Eğitimi yükle (RF13) | `/admin/tool/uploadcourse/index.php?categoryid=1` |
| İzinler | `/admin/roles/permissions.php?contextid=3` |
| Topluluklar | `/cohort/index.php?contextid=3` |
| Filtreler | `/filter/manage.php?contextid=3` |
| Eğitimi geri yükle | `/backup/restorefile.php?contextid=3` |
| Geridönüşüm kutusu | `/admin/tool/recyclebin/index.php?contextid=3` |
| İçerik bankası | `/contentbank/index.php?contextid=3` |
| Öğrenme planı şablonları | `/admin/tool/lp/learningplans.php?pagecontextid=3` |
| Yetkinlik çerçeveleri | `/admin/tool/lp/competencyframeworks.php?pagecontextid=3` |
| Yönetim (aynı sayfa, kategori/kurs seçimi) | `/course/management.php?categoryid=1` / `…&courseid=N` |
| Sıralama / sayfalama | `management.php?…&action=resortcourses…` / `perpage=` |

Kurs satırından tipik devam (matris, bu fazda tıklanmadı): `COURSE-VIEW` `/course/view.php?id=N`, `COURSE-EDIT` `/course/edit.php?id=N`.

---

### 3) ADM-COURSE-NEW — Yeni eğitim ekle

**Ne işe yarar:** Boş eğitim oluşturma formu (ad, kısa ad, kategori, görünürlük, tarih, özet, biçim, grup, tamamlama…). Kayıt sonrası kurs sayfasına / yönetime döner. Mevcut kurs düzenleme aynı şablon: `COURSE-EDIT` (`/course/edit.php?id=2`).

**Form alanları (özet):** tam/kısa ad, kategori, görünürlük, başlangıç tarihi, ID numarası, özet, biçim, gizli bölümler, dil, duyuru/not/rapor ayarları, dosya boyutu, tamamlama, grup modu.

**Bağlı sayfalar (secondary + form):**

| Sayfa | Rota |
|---|---|
| Kurs yönetimi hub | `/course/management.php` |
| Kategori görünümü | `/course/index.php?categoryid=1` |
| Kategori ayarları | `/course/editcategory.php?id=1` |
| Eğitimi yükle | `/admin/tool/uploadcourse/index.php?categoryid=1` |
| İzinler / topluluklar / filtreler / geri yükle / recycle / LP / içerik bankası | (COURSE-MGMT ile aynı kategori bağlamı) |
| Etiket yönetimi | `/tag/manage.php?tc=1` |
| Site yönetimi | `/admin/search.php` |
| Kurs varsayılan ayarları (matris) | `/admin/settings.php?section=coursesettings` (`ADM-SET-COURSESETTINGS`) |

---

### 4) ADM-CATEGORY-EDIT — Kategori ayarlarını düzenle

**Ne işe yarar:** Kategori adı, ebeveyn, ID numarası, açıklama. Yeni alt kategori için `?parent=` ile aynı form.

**Bağlı sayfalar:** COURSE-MGMT secondary ile aynı set (Kategori, Ayarlar, Eğitimi yükle, İzinler, Topluluklar, Filtreler, Geri yükle, Recycle, LP, İçerik bankası) + dönüş yolu `/course/management.php`.

---

## İlişki özeti (graf)

```
ADM-COURSES (admin kategori)
    └─ ADM-COURSE-MGMT ──┬─ ADM-CATEGORY-EDIT (düzenle / yeni parent)
                         ├─ ADM-COURSE-NEW ──► COURSE-VIEW / COURSE-EDIT
                         ├─ ADM-UPLOADCOURSE (RF13)
                         ├─ course/index.php (katalog kategori)
                         └─ izin / cohort / filter / backup / recycle / contentbank / LP

ADM-DEVELOPMENT ──► debugging, purge, generator, behat, phpunit, xmldb, swagger…
         (Site yönetimi ağacında; kurs yaşam döngüsünden ayrı)
```

## Değişen dosyalar

| Dosya | Ne |
|---|---|
| `docs/ui-audit/responsive/RF12/*` | Ölçüm, before/after, relation-map, bu rapor |

## Sonraki grup

**RF13** — ADM-UPLOADCOURSE, ADM-UPLOADUSER, ADM-USER-NEW, ADM-REPORTBUILDER.

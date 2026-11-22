# Kullanıcı ve katılımcı ekranları — envanter (User R1)

Bu belge, gerçek kuruluma (Moodle 5.2.2+, Boost + `local_wamakademi`, yerel `http://localhost:8081`) bakılarak çıkarıldı.
Aşağıdaki "doğrulandı" sütunları kaynak kod okuma ve çalışan sayfadan ölçümle (gövde sınıfı, `M.cfg.contextid`, `M.cfg.courseId`, başlık, bağlantılar) desteklenmiştir.
Kullanıcının ekran görüntüsündeki "WAM Akademi (Yerel)" ekranı yalnızca **hipotez** olarak ele alındı ve gerçek kurulumda doğrulandı (bkz. 2.5).

İlgili önceki envanterler (ayrı bir ikinci envanter açılmadı):
[`ACCOUNT-SCREEN-INVENTORY.md`](ACCOUNT-SCREEN-INVENTORY.md) (kendi hesabım ekranları),
[`COURSE-UI-INVENTORY.md`](COURSE-UI-INVENTORY.md) (eğitim ekranları),
[`IMPLEMENTATION-R1.md`](IMPLEMENTATION-R1.md) / [`IMPLEMENTATION-R2.md`](IMPLEMENTATION-R2.md) (yönetim kabuğu),
[`IMPLEMENTATION-COURSE-R1.md`](IMPLEMENTATION-COURSE-R1.md) (eğitim kabuğu, Katılımcılar tablosunun ilk sürümü),
[`COMMON-CONTROLS-INVENTORY.md`](COMMON-CONTROLS-INVENTORY.md) (ortak form bileşenleri).
Görseller: [`screenshots/user-participant-r1/`](screenshots/user-participant-r1/) (yönetim ekranlarının önceki turu: [`screenshots/admin-r1/`](screenshots/admin-r1/)).

## 1. Bağlam haritası (doğrulandı)

| Bağlam | context id | Notlar |
|---|---|---|
| Sistem (`CONTEXT_SYSTEM`) | 1 | `/admin/user.php`, `/user/editadvanced.php?id=-1`, `/admin/roles/assign.php?contextid=1` |
| Site ana sayfası (`CONTEXT_COURSE`, kurs id = `SITEID` = 1) | 2 | `/user/index.php?id=1`, `/group/index.php?id=1`, `/admin/roles/assign.php?contextid=2` |
| Eğitim (kurs id 2, `WAMTEST01`) | 18 | `/user/index.php?id=2` |
| Kullanıcı (örnek: id 3) | 16 | `/user/editadvanced.php?id=3` |

Site ana sayfası ayrı bir bağlam türü değildir; Moodle onu `SITEID` kimlikli bir **kurs** olarak tutar.
Bu yüzden aynı PHP (`user/index.php`) ve aynı tablo sınıfı (`\core_user\table\participants`) hem eğitimde hem ana sayfada çalışır.

Kurulumdaki veri (salt okunur DB sorgusu): 5 kullanıcı (+ misafir), eğitim 2'ye 4 kayıt (1 öğretmen, 3 öğrenci), **rol ataması yalnızca bağlam 18'de**; bağlam 2 ve 1'de rol ataması yok. `siteadmins = 2` (`admin`).

## 2. Ekran envanteri

Aşağıdaki sütunlar: ekranın görünür başlığı, gerçek rota, bağlam, yetki, listelenen kişiler (kural), rol/grup/durum/son erişim hangi bağlama ait, "ekle"nin anlamı, düzenle/kaldır etkisi.

### 2.1 Sistem kullanıcıları (hesap listesi)

| | |
|---|---|
| Başlık / gezinme | H1 **Sistem kullanıcıları** (eskiden "Kullanıcılar"/"Kullanıcılara gözat"); kırıntı: Hesaplar › Kullanıcılara gözat; yönetim ağacı etiketi **hâlâ "Kullanıcılara gözat"** (çekirdek dize `admin:userlist`) |
| Rota | `/admin/user.php` (sayfa türü `admin-user`, yerleşim `admin`) |
| Bağlam | Sistem, `contextid=1`; `M.cfg.courseId=1` (= SITEID, yalnızca varsayılan) |
| Yetki | `admin_externalpage_setup('editusers')`: `moodle/user:update` **veya** `moodle/user:delete` (sistem). Öğretmen ve öğrenci hesapları "Hata / izniniz yok" alır (doğrulandı). |
| Kimleri listeler | `users` sistem raporu temel koşulu: `deleted <> 1` ve misafir hariç, **tüm** hesaplar; eğitime kayıtlı olmayanlar dahil. Kural: kaynak `public/admin/classes/reportbuilder/local/systemreports/users.php` |
| Sütunlar | Ad/Soyad, E-posta, En son erişim (`user.lastaccess`, site geneli), İşlemler |
| Rol/grup/durum | **Gösterilmez** (hesap listesi; bunlar eğitime ait). Rol sütunu yoktur. |
| "Ekle" | **Yeni kullanıcı ekle** düğmesi = yeni **hesap** oluşturur (2.2). Eğitime kaydetmez. |
| Satır işlemleri | Düzenle; askıya al/geri al; kilit aç; **Hesabı sil** (onay penceresi, kendi hesabın ve site yöneticileri için yok); hesabı onayla; e-posta tekrar gönder. Her biri kendi yetki kontrolüyle (kaynak). |
| Alt ekranlar | Toplu eylemler (`/admin/user/user_bulk.php`), CSV indirme, filtre paneli (açılır, çok alanlı) |

### 2.2 Yeni sistem kullanıcısı

| | |
|---|---|
| Başlık | **Yeni kullanıcı ekle** |
| Rota | `/user/editadvanced.php?id=-1` (sayfa türü `admin-user-editadvanced`, yerleşim `admin`) |
| Bağlam | Sistem (`contextid=1`); `course` parametresi yoksa `SITEID` varsayılır. Kurs bağlamı yoktur. |
| Yetki | `require_capability('moodle/user:create', system)` |
| Etkisi | Yeni **hesap** (`user` satırı). Hiçbir `user_enrolments` / rol ataması üretmez (kaynak). |
| Formu | Genel (kullanıcı adı, kimlik doğrulama yöntemi, şifre, ad, soyad, e-posta, …), Kullanıcı resmi, Ek isimler, İlgi alanları, İsteğe bağlı; özel profil alanları, kimlik doğrulama eklentisi alanları, koşullu alanlar |

### 2.3 Başka bir kullanıcıyı düzenle

| | |
|---|---|
| Başlık | **Kullanıcıyı düzenle — {ad}** |
| Rota | `/user/editadvanced.php?id=N` (`&course=1` bağlantılarda eklenir); sayfa türü `user-editadvanced` |
| Bağlam | Kullanıcı bağlamı (örnek `contextid=16`) |
| Yetki | `moodle/user:update` (sistem). **Kendi hesabın** (`id = $USER->id`) ayrı kabuğu kullanır (hesap ekranı, `wam-account`) — bu tur kapsamı dışında, [ACCOUNT inventory](ACCOUNT-SCREEN-INVENTORY.md). |
| Etkisi | Hesap alanlarını günceller. Eğitim rolü/kaydı bu ekranda değişmez (doğrulandı: formda yok). |

### 2.4 Eğitim katılımcıları

| | |
|---|---|
| Başlık | **{Eğitim adı} — Katılımcılar** (eskiden yalnızca eğitim adı veya "Katılımcılar") |
| Rota | `/user/index.php?id={kurs}` (sayfa türü `course-view-participants`, yerleşim `incourse`) |
| Bağlam | Eğitim (örnek `contextid=18`, `courseId=2`) |
| Yetki | `course_require_view_participants`: `moodle/course:viewparticipants` **veya** `moodle/course:enrolreview`. Öğretmen ve öğrenci erişir (doğrulandı), öğrencinin listesi sade (işlemler/durum yok). |
| Kimleri listeler | O eğitime **kayıtlı** kullanıcılar (`enrolled sql`; durum filtresiyle etkin/askıda) |
| Rol/grup/durum/son erişim | **Eğitime** ait: "Eğitim rolü", "Grup", "Durum", "Eğitime son erişim" (`user_lastaccess`) |
| "Ekle" | **Katılımcı ekle** (`enrol_manual`) = **var olan** hesabı eğitime kaydeder + rol/grup atar; yeni hesap oluşturmaz (modal doğrulandı: "Kullanıcıları seç / Rol ata / Gruba ekle") |
| Satır işlemleri | Öğretmen/yönetici: Rolü düzenle, Grubu düzenle, Kayıt bilgileri, Kaydı düzenle, **Eğitim kaydını kaldır** (yalnızca kayıt; hesap silinmez) |

### 2.5 Site ana sayfası katılımcıları — kullanıcının ekran görüntüsü (hipotez → doğrulandı)

| | |
|---|---|
| Başlık | **Site ana sayfası — Katılımcılar** (ekran görüntüsünde sekme çubuğu: Ana sayfa / Ayarlar / Katılımcılar / Raporlar / Soru bankaları) |
| Rota | `/user/index.php?id=1` |
| Bağlam | Site ana sayfası (kurs `SITEID`), `contextid=2`; yerleşim `admin` (eğitimler `incourse` kullanır) |
| Yetki | Aynı `course_require_view_participants` ama **bağlam 2'de** `moodle/course:viewparticipants`. Öğretmen ve öğrenci: "izniniz yok" (doğrulandı). Yalnızca yönetici (site yöneticisi, tüm yetkiler) görür. |
| Kimleri listeler | `participants_search`, `SITEID` için "kayıtlı" kısıtını **atlar**: `deleted=0`, misafir değil — **tüm site hesapları** (doğrulandı: 5 kişi; eğitim 2 yalnızca 4 kişi, `admin` ana sayfada var, eğitimde yok) |
| Rol / grup / son erişim | **Ana sayfa bağlamına** ait; yeni kurulumda kimsenin ana sayfa rolü yok → tüm satırlar "Rol yok" (doğrulandı: bağlam 2'de rol ataması 0). "Siteye son erişim" = `user.lastaccess`. |
| Durum (kayıt) | Ana sayfada kayıt yoktur: "Durum" sütunu ve filtresi **kaldırıldı** (çekirdekte filtre etkisizdi). |
| "Ekle" | Bu ekranda eğitim kaydı yoktur; "Katılımcı ekle" **görünmez**. Hesap oluşturmak için sistem ekranına bağlanır (Yeni kullanıcı ekle). |
| Satır işlemi | Rolü düzenle (ana sayfa bağlamında) |
| Karar | **Korunur**: kısıtlı bir işlevi var (ana sayfa bağlamında rol atama + tüm hesapları görme). Başlık ve kapsam metniyle sistem listesinden ve eğitim katılımcılarından ayrılır. |

### 2.6 Diğer yollar ve alt ekranlar (incelendi; bu turda değiştirilmedi)

| Ekran | Rota | Bağlam | Başlık (H1) | Not |
|---|---|---|---|---|
| Toplu kullanıcı eylemleri | `/admin/user/user_bulk.php` | Sistem | Toplu kullanıcı eylemleri | Sistem listesinden "Seçili kullanıcılarla…" ile gelinir |
| Kullanıcı yükle (CSV) | `/admin/tool/uploaduser/index.php` | Sistem | Kullanıcı yükle | Toplu hesap oluşturma/güncelleme (veri değiştiren); çalıştırılmadı |
| Sistem toplulukları | `/cohort/index.php` | Sistem | Sistem toplulukları | Kullanıcı grupları (kohort) |
| Kullanıcı profil alanları | `/user/profile/index.php` | Sistem | Kullanıcı profil alanları | Form alanı tanımı (yeni/düzenle formundaki özel alanlar buradan gelir) |
| Sistem rollerini ata | `/admin/roles/assign.php?contextid=1` | Sistem | Sistem rollerini ata | Rol atama (sistem) |
| Ana sayfa rolleri | `/admin/roles/assign.php?contextid=2` | Site ana sayfası | Ana sayfa | **Site katılımcılarının "Rolü düzenle" öğesinin** hedefi ile aynı bağlam |
| Ana sayfa izinleri | `/admin/roles/permissions.php?contextid=2` | Site ana sayfası | Ana sayfa | |
| Ana sayfa grupları | `/group/index.php?id=1` | Site ana sayfası | WAM Akademi (Yerel) | Yerleşim `standard` (yönetim kabuğundan farklı) |
| Eğitim grupları | `/group/index.php?id=2` | Eğitim | WAM Test Egitimi Excel | Yerleşim `standard` |
| Kayıt yöntemleri | `/enrol/instances.php?id=2` | Eğitim | WAM Test Egitimi Excel | Yerleşim `admin`. **Ana sayfa için** (`id=1`) `/my/` panosuna yönlenir: ana sayfada kayıt yöntemi yok (doğrulandı) |
| Diğer kullanıcılar | `/enrol/otherusers.php?id=2` | Eğitim | "…: 0 diğer kullanıcı" | Kayıtsız, eğitimde rolü olan hesaplar |
| Ana sayfa raporları | `/report/view.php?courseid=1` | Site ana sayfası | WAM Akademi (Yerel) | |
| Hesaplar kategorisi | `/admin/category.php?category=accounts` | Sistem | Hesaplar | Yönetim ağacı bağlantıları: Kullanıcılara gözat, Yeni kullanıcı ekle |

Yukarıdaki ekranların hepsi masaüstü (1440) ve dar (390) genişlikte açılıp ekran görüntüsü alındı (`before-*` dosyaları). Bunlardan yalnızca 2.1–2.5 bu turda yeniden tasarlandı; diğerleri mevcut kabuğun (`wam-admin`, `wam-course`, `standard`) davranışında bırakıldı (bkz. IMPLEMENTATION-USER-PARTICIPANT-R1.md, "Kalan işler").

## 3. Bağlantı envanteri (doğrulandı: çalışan sayfalardan `a[href]` taraması)

| Hedef | Nerede | Not |
|---|---|---|
| `/admin/user.php` | Yönetim ağacı (Hesaplar kategorisi sayfası ve yönetim ana sayfasındaki "Kullanıcılar" sekmesi), kırıntı | Her biri farklı gezinme yüzeyi |
| `/user/editadvanced.php?id=-1` | Yönetim ağacı (Hesaplar), sistem listesindeki **Yeni kullanıcı ekle** düğmesi, site katılımcılarındaki kapsam metni (yeni) | |
| `/user/index.php?id=1` | Site ana sayfası ikincil gezinmesi "Katılımcılar" (More menu) | WAM menüsünde doğrudan bağlantı yok |
| `/user/index.php?id=N` | Eğitim ikincil gezinmesi "Katılımcılar" ve üçüncül gezinme "Kayıtlı kullanıcılar" | İki gezinme yüzeyi aynı rotaya gider (çekirdek davranış) |

`local/wamakademi` içinde kullanıcı/katılımcı **listeleyen özel sayfa yok**: `get_enrolled_users` yalnızca `catalog.php` içinde kart üzerindeki öğretmen adlarını göstermek için kullanılır (liste ekranı değil).

## 4. Hesap tekrarı kontrolü (kategori E — salt okunur sorgu, veri değişmedi)

Eşleşme ölçütleri: aynı e-posta (büyük/küçük harfe duyarsız), aynı ad+soyad, aynı `idnumber`. Sonuç: **yinelenen aday yok** (6 hesap, silinmiş hesap 0; yalnızca test dizisi `wamtest_ogrenci`, `…2`, `…3` farklı e-posta ve ad taşır). Bir kişinin hem sistem listesinde hem eğitim ve ana sayfa katılımcı listesinde görünmesi **hesap tekrarı değildir** (aynı hesap, farklı bağlam görünümü).

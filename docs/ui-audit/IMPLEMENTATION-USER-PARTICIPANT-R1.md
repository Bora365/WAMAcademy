# Kullanıcı ve katılımcı ekranları — uygulama raporu (User R1)

Bağlantılı belgeler: [`USER-PARTICIPANT-INVENTORY.md`](USER-PARTICIPANT-INVENTORY.md) · [`USER-PARTICIPANT-DUPLICATION-AUDIT.md`](USER-PARTICIPANT-DUPLICATION-AUDIT.md) ·
önceki: [`IMPLEMENTATION-COURSE-R1.md`](IMPLEMENTATION-COURSE-R1.md), [`IMPLEMENTATION-R1.md`](IMPLEMENTATION-R1.md), [`IMPLEMENTATION-R2.md`](IMPLEMENTATION-R2.md).
Ekran görüntüleri: [`screenshots/user-participant-r1/`](screenshots/user-participant-r1/).

**Kısıtlar uygulandı:** commit, push, deploy yapılmadı. Hesap/kayıt silinmedi, birleştirilmedi. Kullanıcı verisi (hesap, kayıt, rol) değiştirilmedi.
Yeni `!important` katmanı eklenmedi (aşağıda tek tek gerekçelendirilmiş üç Bootstrap yardımcı sınıfı geçersiz kılma istisnası var).

## 1. Doğrulanan ekran anlamları (özet)

- Kullanıcının ekran görüntüsü = **Site ana sayfası katılımcıları** (`/user/index.php?id=1`, bağlam 2), eğitim katılımcılarıyla aynı şablon, **tüm site hesaplarını** listeler.
- **Sistem kullanıcıları** (`/admin/user.php`, sistem bağlamı) hesap yönetimidir; **Katılımcı ekle** (eğitim) var olan hesabı kaydeder; **Yeni kullanıcı ekle** yeni hesap üretir. Ayrıntı: envanter.

## 2. Yapılanlar

### 2.1 Ortak (PHP, `local_wamakademi`)

| Dosya | Değişiklik |
|---|---|
| `public/local/wamakademi/lib.php` | `local_wamakademi_users_page_kind()` (sayfa türü + bağlam karar verir: `participants`, `list`, `new`, `edit`); `…_users_heading()` (H1), `…_users_scope_html()` (kapsam paragrafı), `…_users_config()` (JS dizeleri); gövde sınıfları `wam-users`, `wam-users-{tür}`, `wam-users-form`, `wam-site-participants`; `course_page_kind()` içine `site` türü (yalnızca ana sayfa katılımcıları, bağlama göre); `wam-users.css` ve `users_ui.js` yüklemesi. **Hata düzeltmesi:** `empty($PAGE->context)` (her zaman `true`, çünkü `moodle_page` `__isset` tanımlamaz) yerine `instanceof context`. Bu hata, başka bir değişiklikle tüm eğitim sayfalarında `wam-course` sınıfını ve eğitim CSS'ini kaldırıyordu. |
| `public/local/wamakademi/css/wam-users.css` *(yeni)* | Kapsam paragrafı, sistem kullanıcıları listesi, yeni/düzenle formu (aşağıda) |
| `public/local/wamakademi/js/users_ui.js` *(yeni)* | Sistem listesinde İşlemler etiketi ve "Hesabı sil"; form alt çubuğu hizası |
| `public/local/wamakademi/lang/{tr,en}/local_wamakademi.php` | Başlık, kapsam, sütun başlığı, "Eğitim kaydını kaldır" dizeleri (yalnızca kullanılanlar) |
| `public/user/index.php`, `public/admin/user.php`, `public/user/editadvanced.php` | `function_exists(...)` korumalı **tek satırlık** kapsam paragrafı çağrısı (eklenti kapalıysa çekirdek çıktı değişmez) |
| `public/user/classes/table/participants.php` | Bağlama göre rol ve son erişim sütun başlıkları; tek İşlemler menüsü, rol/grup kalemlerinin kaldırılması (bu tablo bir önceki turda WAM'a uyarlanmıştı) |
| `public/user/classes/output/participants_filter.php` | Ana sayfada "Durum" filtresi sunulmaz (çekirdekte etkisizdi) |

Kapsam kuralı **URL veya görünen metinle değil Moodle bağlamıyla** verilir: sayfa türü + `$PAGE->course->id === SITEID` + bağlam düzeyi.

### 2.2 Adlandırma ve bilgi mimarisi

| Ekran | H1 | Kapsam açıklaması (tek cümle) |
|---|---|---|
| `/admin/user.php` | Sistem kullanıcıları | "Sitedeki tüm kullanıcı hesapları. Eğitim kayıtları, eğitim rolleri ve gruplar her eğitimin Katılımcılar ekranından yönetilir." |
| `/user/editadvanced.php?id=-1` | Yeni kullanıcı ekle | "Yeni bir sistem hesabı oluşturur; hesabı bir eğitime kaydetmez. Eğitime kayıt için eğitimin Katılımcılar ekranındaki “Katılımcı ekle” kullanılır." |
| `/user/editadvanced.php?id=N` | Kullanıcıyı düzenle — {ad} | "Sistem hesabının bilgilerini düzenler. Eğitim rolleri ve kayıtlar bu ekranda değişmez; eğitimin Katılımcılar ekranından yönetilir." |
| `/user/index.php?id=2` | {Eğitim} — Katılımcılar | "Bu eğitime kayıtlı kullanıcılar; rol, grup, durum ve son erişim yalnızca bu eğitim için geçerlidir. “Katılımcı ekle” var olan bir hesabı eğitime kaydeder, yeni hesap oluşturmaz." |
| `/user/index.php?id=1` | Site ana sayfası — Katılımcılar | "Sitedeki tüm kullanıcı hesapları; rol, grup ve son erişim site ana sayfası bağlamına aittir, eğitim kayıtlarını göstermez. Yeni hesap oluşturmak için **Yeni kullanıcı ekle** kullanılır." |

"Hesap oluşturma" ve "eğitime kaydetme" etiketleri ayrıdır (Yeni kullanıcı ekle ↔ Katılımcı ekle). "Rol yok" artık sütun başlığıyla bağlam kazandı: **Ana sayfa rolü** (site) / **Eğitim rolü** (eğitim); "Siteye son erişim" / "Eğitime son erişim". Sistem rolü, eğitim rolü ve kayıt durumu tek sütunda karışmaz: sistem listesinde rol yok, eğitimde "Eğitim rolü" + "Durum" ayrı sütundur.

### 2.3 Katılımcı listeleri (eğitim + ana sayfa; görsel katman `css/wam-course.css` §6 + `js/course_ui.js` + `js/participants_table.js`)

| İstek | Durum |
|---|---|
| Görünür, etiketli ad/e-posta araması | **Yapıldı**: "Ad veya e-posta ile ara" + Ara; "Aramayı temizle"; "N katılımcı bulundu" |
| Koşul oluşturucu gizli | **Yapıldı**: "Gelişmiş filtreler" altında daraltılmış (çekirdek filtre altyapısı korunur) |
| Uzun iki alfabe sırası | **Yapıldı**: "Baş harfe göre" altında; Ad ve Soyad çubukları ayrı kalır (çekirdek `tifirst`/`tilast`, AND) |
| Etkin filtre görünür | Baş harf etkinse panel kendiliğinden açılır (doğrulandı: `?tifirst=W&tilast=O`) |
| Sade sütunlar, tek satır başlık, 12–16 px dolgu | **Yapıldı**: ad+avatar, e-posta (`min-width 250px`, yalnızca kendi hücresinde kırar), rol/grup/son erişim/durum tek satır |
| Sıralama göstergesi başlığın yanında, `aria-sort` | **Yapıldı** (doğrulandı: e-postaya göre sıralama `ascending`) |
| Rol/grup kalemleri kalkar, tek İşlemler menüsü | **Yapıldı**; menü `fixed` konumlu (kaydırma alanında kırpılmaz) |
| Klavye: Enter açar, Escape kapatır, odak geri döner | **Doğrulandı** (admin + öğretmen) |
| Yalnızca yetkili işlemler | Öğretmen: Rolü düzenle / Grubu düzenle / Kayıt bilgileri / Kaydı düzenle / Eğitim kaydını kaldır. Yönetici ana sayfada: Rolü düzenle. Öğrenci: İşlemler sütunu yok. |
| Eğitim kaydını kaldır ≠ hesabı sil | **Ayrı**: katılımcıda "Eğitim kaydını kaldır"; hesap silme yalnızca sistem listesinde "Hesabı sil" |
| Mobilde yalnızca tablo bölgesinde yatay kaydırma | **Doğrulandı** (390: sayfa `scrollWidth = viewport`) |

### 2.4 Sistem kullanıcıları listesi (`wam-users.css` + `users_ui.js`)

- Ad hücresi avatarı sabit, ad metni kendi başlangıç hizasında kalır (uzun ad avatarın altına inmez).
- E-posta sütunu (`min-width: 260px`) yalnızca kendi hücresinde kırılır; "Hiçbir zaman" gibi kısa hücreler tek satır.
- Satır sonundaki çekirdek eylem menüsü tetikleyicisi **"İşlemler ▾"** düğmesine dönüştürüldü (aynı token'lar: kenarlık, yarıçap, 44 px hedef); erişilebilir ad "İşlemler: {ad}". Çekirdek menü öğeleri, yetki geri çağrıları ve onay pencereleri aynen kalır; silme öğesi "Hesabı sil" ve kırmızı. Kurulum yalnızca bu rapora uygulanır (`body.wam-users-list`), rapor AJAX ile yeniden çizildiğinde `MutationObserver` tekrar uygular.
- **Filtre paneli**: 400 px genişlik, her filtrede etiket üstte ve işleç/değer tam genişlikte (önceden "herhangi bir de…" diye kesiliyordu), "Uygula / Hepsini sıfırla" çubuğu alanları örtmeyen zeminli satır. "Filtreler (1)" etkin filtre sayısını panel kapalıyken gösterir (çekirdek). Boş sonuç: "Kullanıcı bulunamadı".
- **Bilinçli olarak yapılmadı**: ad/e-posta tek kutu araması. Çekirdek rapor filtreleri **VE** ile birleşir; "ad VEYA e-posta" sağlanamaz, ve ad filtresine yönlendirip e-postayı atlamak yanıltıcı olur. Etiketli filtre paneli korundu.

### 2.5 Yeni / düzenle kullanıcı formu (`body.wam-users-form`)

Ortak form katmanı (`css/wam-forms.css`: 44 px alanlar, yardım simgesinin etikete bitişik yerleşimi, sakin "Zorunlu" metni, güçlü hata iletisi) olduğu gibi kullanıldı; ek olarak:

- Form yüzeyi **800 px**, etiket sütunu 240 px (uzun etiket "Bir kimlik denetimi metodu seçin" tek satıra sığar).
- Bölümler kompakt satırlar (başlık + ok), büyük renkli akordeon kareler yok (ortak katmandan).
- **Kullanıcı resmi** yükleyici: kompakt (96 px bırakma alanı, mesaj kırpılmıyor, sınırlama metni solda ve sakin).
- Kaydet/İptal çubuğu form yüzeyinin sol kenarıyla hizalı (`users_ui.js`: yüzeyin `left` değerini CSS değişkenine yazar; `ResizeObserver` ile `#region-main` boyu değişince güncellenir; yalnızca kapalı çekmeceyle doğrulandı); içeriği örtmez (Moodle sayfa alt boşluğu).
- **Şifre düzenleme adımı** iki adımlı bırakıldı (yer tutucu alan boyutunda, kalem simgesi sağda). Otomatik açmak denendi ve **geri alındı**: Moodle `passwordunmask` bileşeni düzenlemeyi odak çıkışına bağlar (odaktan çıkınca kapanır), odaksız açmak desteklenen bir yol değil.
- Özel profil alanları, kimlik doğrulama yöntemi, koşullu alanlar, doğrulama ve form gönderimi **değişmedi** (yalnızca görünüm).

### 2.6 `!important` istisnaları (yeni "örtme" katmanı değil)

`wam-users.css` içinde üç kural Bootstrap yardımcı sınıflarının kendi `!important`'ını geçersiz kılar ve satır içi yorumla gerekçelidir: alt çubuktaki `.justify-content-end`, `.px-3` ve `.mb-3`. Başka hiçbir kural `!important` içermez.

## 3. Sayılar (ayrı)

| Ölçü | Sayı | Hangi ekranlar |
|---|---|---|
| İncelenen ekran | **18** | Envanter §2.1–2.6: 5 ana + 13 alt/ilişkili ekran |
| Değiştirilen ekran | **5** | Sistem kullanıcıları, Yeni kullanıcı, Kullanıcıyı düzenle, Eğitim katılımcıları (başlık, kapsam, başlıklar), Site ana sayfası katılımcıları |
| Görsel olarak doğrulanan ekran | **5** | Her biri yönetici ile 1440/1024/768/390; ek olarak eğitim katılımcıları öğretmen ve öğrenci ile 1440/390; sistem ve düzenleme ekranlarının izinsiz roller için hata sayfası 1440/390 |
| İncelenip dokunulmayan ekran | 13 | Toplu eylemler, kullanıcı yükle, topluluklar, profil alanları, sistem/ana sayfa rolleri, izinler, gruplar (ana sayfa/eğitim), kayıt yöntemleri, diğer kullanıcılar, ana sayfa raporları, Hesaplar kategorisi |

## 4. Doğrulama

### 4.1 Görsel doğrulama (veri değiştirmeyen)

Başsız Microsoft Edge + CDP (IDE tarayıcısı, emüle edilmiş genişliklerde çiftlenmiş görüntü verdiği için kullanılmadı). Her açılışta yatay taşma ölçüldü (`documentElement.scrollWidth` = pencere genişliği, tüm ekran × genişlik çiftlerinde).

| Ekran | Rol | Genişlikler | Sonuç |
|---|---|---|---|
| Sistem kullanıcıları | admin | 1440/1024/768/390 | H1, kapsam, İşlemler düğmesi, filtre paneli ✔; 390'da tablo kendi bölgesinde kayar |
| Yeni kullanıcı | admin | 1440/1024/768/390 | Form 800 px, yardım etikete bitişik, kompakt bölümler, resim yükleyici, alt çubuk hizalı ✔ |
| Kullanıcıyı düzenle | admin | 1440/1024/768/390 | Aynı ✔; kapsam paragrafı |
| Eğitim katılımcıları | admin, öğretmen, öğrenci | admin 4 genişlik; öğretmen/öğrenci 1440+390 | Rol bazlı sütun/işlem farkı ✔ |
| Site ana sayfası katılımcıları | admin | 4 genişlik | "Ana sayfa rolü" vb. ✔ |
| Yetkisiz erişim | öğretmen, öğrenci | 1440/390 | `/admin/user.php`, `/user/editadvanced.php?id=-1`, `/user/index.php?id=1` → "izniniz yok" ✔ (izin matrisi ile uyumlu) |

Bakılan görüntüler (açılıp incelendi): `after-site-participants-admin-1440/390`, `after-sysusers-admin-390`, `after-edituser-admin-1440`, `after-course-participants-{teacher,student}-1440`, `after-course-participants-admin-768`, `after-newuser-admin-390`, `func-*` dosyaları, ve alt kısımda anılan `before-*` karşılıkları.

Not: tam sayfa ekran görüntüleri yüksek sayfalarda küçültülür; ayrıntı kontrolleri 1440 görünüm penceresi kırpımlarıyla ayrıca yapıldı (örn. yeni kullanıcı formu üst/alt).

### 4.2 İşlevsel doğrulama (salt okuma; veri değiştirmez)

Arama/filtre/sıralama yalnızca **görünüm tercihlerini** değiştirir. Sistem listesindeki filtre kullanıcı tercihi olarak saklandığı için test sonunda "Hepsini sıfırla" ile temizlendi.

| Senaryo | Sonuç |
|---|---|
| Arama "şükrü", "ŞÜKRÜ", "sukru" (ana sayfa) | Her üçü **1 katılımcı** (Şükrü Çağlar…) ✔ |
| Arama e-posta "wamtest.ogrenci@" | 1 (WAM Ogrenci) ✔ |
| Sonuçsuz arama | "0 katılımcı bulundu" + "Gösterilecek bir şey yok" ✔ |
| Aramayı temizle | 5 katılımcıya döner ✔ |
| Baş harf: Ad=W → 2; Ad=W + Soyad=O → 1; Ad=Ş → Şükrü; Ad=İ + Soyad=O → 0 | Çekirdek davranışı (iki çubuk VE ile birleşir); Türkçe harflerin hepsi çubukta (Ç Ğ… İ Ö Ş Ü) ✔ |
| Sıralama (Ad Soyad ↔ E-posta) | `aria-sort="ascending"`, doğru sıra ✔ |
| Sistem listesi filtre: Tam adı içerir "şükrü" | 1 satır; "zzzyok" → "Kullanıcı bulunamadı"; `Filtreler (1)` etiketi; sıfırla → 5 ✔ |
| Klavye: İşlemler Enter/Escape | Açılır, ilk öğeye odak; Escape kapanır, odak tetikleyiciye döner ✔ |
| Katılımcı ekle penceresi (yalnızca açıldı) | Kullanıcıları seç / Rol ata / Gruba ekle ✔ (gönderilmedi) |

### 4.3 Doğrulanamayan veya yapılmayan

- **Veri değiştiren işlevsel testler yapılmadı** (kayıt ekleme/kaldırma, hesap oluşturma/silme/askıya alma, rol/grup değişimi, form kaydetme, toplu eylem). Gerçek hesap ve kayıtlar korunduğu için. Bu yollar yalnızca görsel ve kod okuma ile doğrulandı.
- Formda **hata durumu** (boş zorunlu alan, geçersiz e-posta) görsel olarak tetiklenmedi (gönderim gerektirir). Stil ortak katmandan gelir.
- Yalnızca 6 hesaplık yerel veriyle denendi: çok sayfalı sayfalama (50+ satır) ve çok uzun grup adı satırları bu veriyle görülemedi; uzun ad (Şükrü Çağlar Öztürk-Ağaoğlu Uzunsoyadlıoğulları) ve uzun e-posta (`wamtest.ogrenci2.cok.uzun.eposta.adresi@localhost.test`) test hesabı olarak vardı ve doğrulandı.
- Tarayıcı: yalnızca Chromium (Edge). Safari/Firefox denenmedi.

## 5. Kalan işler ve notlar

1. **Yönetim ağacı etiketi** "Kullanıcılara gözat" ↔ H1 "Sistem kullanıcıları" uyumsuz; çözüm dil özelleştirmesinde (`tr_local/admin.php: userlist`). Depoda değil.
2. **Ana sayfada üçüncül gezinme etiketi** "Kayıtlı kullanıcılar": ana sayfada kayıt yok, ama etiket çekirdekten (aynı bileşen). Değiştirmek global metin değişimi gerektirir; yapılmadı.
3. **Alt ekranlar** (ana sayfa grupları `standard` yerleşim, rol atama, izinler, kayıt yöntemleri, diğer kullanıcılar, toplu eylemler, kullanıcı yükleme, topluluklar, profil alanları) incelendi ama mevcut kabukta bırakıldı; yerleşimleri birbirinden farklı (`standard` / `admin` / `report`).
4. Tablo boşken "Seçili kullanıcılarla…" kontrolü görünür (çekirdek); isteğe bağlı gizlenebilir.
5. Moodle'un Türkçe baş harf filtresi aksan duyarsız: "O" seçilince "Ö…" soyadlar da gelir (örnek: Öztürk). Çekirdek veritabanı harmanı davranışı; çubukta "Ö" ayrıca bulunur.
6. Şifre alanı (bkz. 2.5) iki adımlı.
7. 768 px üst çubukta "Düzenleme modu" metni iki satıra kırılıyor (başlık kabuğu, bu turun kapsamı dışı).
8. Yönetim ana sayfası tek kullanıcı ekranı kopyası bulunamadı; kaldırılan menü bağlantısı yok.

## 6. Dağıtım adımları (yapılmadı)

- `php admin/cli/purge_caches.php --lang --theme` (yeni dil dizeleri ve CSS sürümü için).
- İsteğe bağlı: `moodledata/lang/tr_local/admin.php` içinde `$string['userlist'] = 'Sistem kullanıcıları';`.

## 7. Yerel test ortamı (açıklama)

- Yerel test hesaplarının parolaları bu çalışma için `WamLocal#2026x` olarak ayarlandı (`admin`, `wamtest_egitmen`, `wamtest_ogrenci`; yalnızca yerel ortam). Depoya yazılmadı.
- `public/` kökünde bu çalışmaya ait geçici dosya bırakılmadı (`wamtest_users.html` silindi); `wamtest_catalog.html`, `wamtest_courseedit.html`, `wamtest_home.html` başka işlerden kaldı, dokunulmadı.
- `local_wamakademi_account_target_userid()` içinde `!empty($PAGE->context)` ifadesi aynı sebeple her zaman yanlış değerlendirilir; **dokunulmadı**, ayrı inceleme önerilir.

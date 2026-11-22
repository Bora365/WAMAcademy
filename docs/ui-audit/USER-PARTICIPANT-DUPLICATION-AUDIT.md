# Kullanıcı ve katılımcı ekranları — tekrar denetimi (User R1)

Kaynak envanter: [`USER-PARTICIPANT-INVENTORY.md`](USER-PARTICIPANT-INVENTORY.md). Uygulama: [`IMPLEMENTATION-USER-PARTICIPANT-R1.md`](IMPLEMENTATION-USER-PARTICIPANT-R1.md).

İlke: "benzer görünüyor" ≠ "aynı iş". Her aday için **ortak/farklı işlev, veri kapsamı, yetki farkı, kullanıcı karışıklığı, öneri** verildi.
**Hiçbir hesap silinmedi, birleştirilmedi, taşınmadı.** Kategori E yalnızca salt okunur sorguyla bakıldı.

## Özet

| Kat. | Aday | Gerçek tekrar mı? | Karar |
|---|---|---|---|
| A | "Kullanıcılara gözat", "Yeni kullanıcı ekle" birden çok yerde | Hayır (farklı gezinme yüzeyleri, aynı hedef) | Korundu; bağlantı kaldırılmadı |
| A | Eğitimde "Katılımcılar" sekmesi + "Kayıtlı kullanıcılar" açılırı | Hayır (çekirdek ikincil + üçüncül gezinme) | Korundu |
| A | "Katılımcı ekle" düğmesi tablonun altında ve üstünde | Çekirdek davranışı (uzun listede iki erişim noktası) | Korundu (not düşüldü) |
| B | Aynı işi yapan farklı özel sayfalar | **Bulunamadı** | — |
| C | Eğitim katılımcıları ↔ site ana sayfası katılımcıları | Aynı şablon, **farklı bağlam** | İkisi de korundu; başlık, kapsam metni, sütun başlıkları bağlama göre ayrıldı |
| C | Sistem kullanıcıları ↔ site ana sayfası katılımcıları | Benzer liste, **farklı yetki ve işlev** | İkisi de korundu; birbirine ve "Yeni kullanıcı"ya yönlendirme eklendi |
| C | Liste "İşlemler" menüleri (çekirdek eylem menüsü ↔ WAM menüsü) | Aynı görsel bileşen, iki farklı teknik | Ortak token'lı aynı görünüm; aynı etiket |
| D | Aynı kişi birden çok listede | Beklenen | Başlık ve kapsam metni ile ayrıştırıldı |
| E | Olası yinelenen hesap | **Bulunamadı** (6 hesap) | Rapor; işlem yok |

## A. Aynı ekrana giden birden çok menü bağlantısı

Tarama: çalışan sayfalarda `a[href]` ([envanter §3](USER-PARTICIPANT-INVENTORY.md)).

### A1. `/admin/user.php` ve `/user/editadvanced.php?id=-1`

- **Ortak:** aynı hedef rota. **Farklı:** yönetim ağacı (Hesaplar kategorisi sayfası ve yönetim ana sayfasındaki "Kullanıcılar" sekmesi) ve listenin kendi **Yeni kullanıcı ekle** düğmesi farklı bağlamlardan girer; bir listeye girmeden ya da listede iken yeni hesap oluşturabilmek meşru bir ihtiyaç.
- **Veri/yetki:** aynı (`moodle/user:create` / `update`).
- **Karışıklık:** bağlantı sayısından değil **etiket uyumsuzluğundan** doğuyordu: yönetim ağacı ve kırıntı "Kullanıcılara gözat", sayfa H1'i eskiden "Kullanıcılar". H1 artık **Sistem kullanıcıları**.
- **Öneri/uygulama:** bağlantı kaldırılmadı (yönetim ağacı Moodle'ın standart, yetkiye göre üretilen yapısıdır; kaldırmak bağlantıları kırar). **Dağıtım notu:** ağaç etiketi çekirdek dizesi `admin:userlist`; tutarlılık için dil özelleştirmesinde (`moodledata/lang/tr_local/admin.php`) `$string['userlist'] = 'Sistem kullanıcıları';` önerilir. Depoda değil, ortam yapılandırmasındadır; bu turda uygulanmadı.

### A2. `/user/index.php?id=N`: "Katılımcılar" sekmesi ve "Kayıtlı kullanıcılar" açılırı

- Çekirdek: ikincil gezinme (sekme) + üçüncül gezinme (açılır). İkincisi diğer alt ekranlara (Kayıt yöntemleri, Gruplar, İzinler, Diğer kullanıcılar) geçiş içindir; bir kopya değil.
- **Karar:** korundu. Site ana sayfasında açılırın etiketi yine "Kayıtlı kullanıcılar"; oysa ana sayfada kayıt yok (bkz. Kalan işler).

### A3. "Katılımcı ekle" iki kez (tablo üstü ve altı)

- Çekirdek `user/index.php` hem üstte (üçüncül gezinmeyle) hem altta (`d-flex justify-content-end`) gösterir. İşlev aynı; uzun listede kaydırmadan düğmeye ulaşma amaçlı.
- **Karar:** dokunulmadı (davranış değişmesi riski, kazanç düşük). Gerekirse CSS ile 6+ satırda alt düğme gizlenebilir; ayrı karar olarak bırakıldı.

## B. Aynı işi yapan farklı özel sayfalar

`local/wamakademi` taranarak kullanıcı/kayıt listeleyen sayfa aranmıştır. **Yok.** `get_enrolled_users` yalnızca `catalog.php` içinde kartlarda öğretmen adlarını göstermek için kullanılır; liste ekranı değildir. Bu kategoride birleştirilecek bir şey yoktur.

## C. Aynı şablonun farklı bağlamlarda kullanımı

### C1. Eğitim katılımcıları ↔ Site ana sayfası katılımcıları (aynı şablon: `/user/index.php`)

| | Eğitim (`id=2`, bağlam 18) | Site ana sayfası (`id=1`, bağlam 2) |
|---|---|---|
| Ortak | aynı PHP, aynı `\core_user\table\participants`, aynı arama/gelişmiş filtre/baş harf, aynı İşlemler menüsü | |
| Liste kuralı | O eğitime **kayıtlı** kullanıcılar | **Tüm** site hesapları (kayıt kısıtı atlanır) |
| Rol/grup | Eğitim rolü, eğitim grubu | Ana sayfa rolü; ana sayfada grup yok |
| Durum | Kayıt durumu (Etkin/Askıda/Süresi dolmuş) | Yok (filtre ve sütun kaldırıldı) |
| Son erişim | `user_lastaccess` (eğitime) | `user.lastaccess` (siteye) |
| Yetki | `viewparticipants` (öğretmen/öğrenci dahil) | Aynı yetki **ana sayfa bağlamında**: yalnızca yönetici |
| Ekle | Katılımcı ekle = var olan hesabı **kaydeder** | Yok; hesap için sistem ekranı |
| Kaldır | Eğitim kaydını kaldır (hesap kalır) | Yok (kayıt yok) |

- **Karışıklık:** kullanıcının ekran görüntüsü (ana sayfa) eğitimdeki ekranla birebir aynı görünüyor; ancak veri kuralı farklı. Eskiden başlık yalnızca "WAM Akademi (Yerel)", rol sütunu "Rol", son erişim "Son erişim" idi ve herkes "Rol yok" görünüyordu.
- **Öneri/uygulama:** iki ekran **birleştirilmedi** (farklı bağlam, farklı veri). Aynı bileşen ortak kullanıldı; bağlama göre başlık (**Site ana sayfası — Katılımcılar** / **{Eğitim} — Katılımcılar**), tek cümlelik kapsam metni, "Ana sayfa rolü / Eğitim rolü", "Siteye son erişim / Eğitime son erişim" ayrıldı; "Rol yok" sütun başlığıyla bağlam kazandı.

### C2. Sistem kullanıcıları ↔ Site ana sayfası katılımcıları

| | Sistem kullanıcıları | Site ana sayfası katılımcıları |
|---|---|---|
| Ortak | Aynı hesap kümesi (tüm hesaplar) | |
| İşlev | **Hesap yönetimi**: oluştur, düzenle, askıya al, sil, onayla | **Ana sayfa bağlamında rol/grup yönetimi** ve gözlem |
| Yetki | `user:update`/`user:delete` | `course:viewparticipants` (ana sayfa) |
| Teknik | reportbuilder sistem raporu | `participants` dinamik tablosu |

- **Karışıklık riski:** ikisi de "tüm kullanıcılar". Farklı sorulara cevap verirler.
- **Karar:** ikisi korundu; kapsam metinleri birbirinden ayrılıyor ve ana sayfa ekranı "Yeni hesap oluşturmak için **Yeni kullanıcı ekle**" bağlantısıyla sistem ekranına yönlendiriyor (yeni hesap yalnızca orada üretilir).

### C3. Satır "İşlemler" menüsü (katılımcı tablosu ↔ sistem raporu)

- Teknik fark: katılımcı tablosunda WAM menüsü (`participants_table.js`, çıktı `templates/participants_actions.mustache`), sistem raporunda çekirdek `core action_menu` (kebab).
- Görsel fark: eskiden üç nokta ve tarayıcı varsayılanına benzer düğmeler; katılımcıda ayrıca rol/grup kalem simgeleri vardı.
- **Uygulama:** ikisi de aynı etiket ("İşlemler") ve aynı token'larla (kenarlık, yarıçap, 44 px hedef, ok) çizilir; sistem raporunda kurulum `users_ui.js` ile yalnızca bu rapora uygulanır. Kod paylaşılmadı çünkü iki bileşenin işaretlemesi çekirdek tarafından farklı üretiliyor; görünüm token'lar aracılığıyla ortak.
- **Silme ayrımı:** sistem ekranında **Hesabı sil** (kırmızı, onay penceresi çekirdeğe ait); katılımcı ekranında **Eğitim kaydını kaldır** (yalnızca kayıt). İki işlem farklı yerde, farklı etiketle.

### C4. Yeni / düzenle kullanıcı formu

Aynı form sınıfı ve şablon, iki yerleşimde (`new`: yönetim kabuğu; `edit`: kullanıcı bağlamı). Aynı CSS ile ortak görünüm; birleştirilecek ayrı iki ekran yok.

## D. Aynı kişinin birden çok listede görünmesi

`wamtest_ogrenci` hem **Sistem kullanıcıları**, hem **Site ana sayfası — Katılımcılar**, hem **WAM Test Eğitimi — Katılımcılar** listelerinde görünür; `admin` ilk ikisinde. Bu hesap tekrarı değil, **aynı hesabın farklı bağlam görünümü**. Kullanıcı karışıklığını azaltmak için her listenin başlığı ve kapsam metni hangi bağlamda olduğunu söyler. Hiçbir liste diğerinin yerine geçmedi.

## E. Olası yinelenen hesaplar (salt okunur; ayrı rapor)

`user` tablosunda (silinmemiş, misafir hariç) üç ölçüte göre gruplandı: aynı e-posta, aynı ad+soyad, aynı `idnumber`.
**Sonuç: aday yok.** 6 hesap, silinmiş hesap 0. Test hesaplarının kullanıcı adları ardışık (`wamtest_ogrenci`, `…2`, `…3`) fakat e-posta, ad ve soyadları farklı.
İleride gerçek veriyle yinelenme çıkarsa işlem manuel yapılmalıdır; bu çalışmada hiçbir hesap silinmedi/birleştirilmedi/taşınmadı.

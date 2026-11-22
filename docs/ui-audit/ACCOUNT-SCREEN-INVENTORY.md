# Hesap / ayarlar ekran envanteri (Faz 3 — R2; Faz 4’te yeniden doğrulanır)

Tarih: 2026-11-11  
Ortam: lokal `http://localhost:8081`  
Roller: admin (id=2), `wamtest_egitmen` (id=4), `wamtest_ogrenci` (id=3)  
Test kursu: `WAMTEST01` / “WAM Test Egitimi Excel”

**R2 düzeltmesi:** Eğitmen oturumu gerçekten doğrulanmadı; 1024/768 matrisi yapılmadı; birçok alt ekran yalnız “link açıldı” düzeyinde kaldı; r2 klasöründeki bazı görüntüler anomali/yeniden adlandırılmış olabilir. Güncel kapsam: `ACCOUNT-PHASE4-COVERAGE.md`.

Durum anahtarı: **tamamlandı** | **bu kurulumda kapalı** | **erişim/test engeli** | **sonraki faz**

| Ekran | Rota (sesskey yok) | Üst ekran | Admin | Eğitmen | Öğrenci | Durum | Not |
|---|---|---|---|---|---|---|---|
| Profil (kendi) | `/user/profile.php?id={self}` | Çark / menü | tamamlandı | tamamlandı | tamamlandı | tamamlandı | Blog yok (`enableblogs=0`) |
| Profil (başka) | `/user/profile.php?id={other}` | Kullanıcılar / mesaj | tamamlandı | — | tamamlandı | tamamlandı | Öğrenci→admin: “ayrıntılar kapalı”; admin→öğrenci: düzenle + mesaj |
| Profil düzenle (gelişmiş) | `/user/editadvanced.php?id={self}` | Tercihler / profil | tamamlandı | tamamlandı* | tamamlandı* | tamamlandı | `*`: öğrenci/eğitmen `/user/edit.php` kullanabilir; admin advanced |
| Profil düzenle (standart) | `/user/edit.php` | Tercihler | — | tamamlandı | tamamlandı | tamamlandı | Form kabuğu ortak |
| Tercihler | `/user/preferences.php` | Çark | tamamlandı | tamamlandı | tamamlandı | tamamlandı | 5 grup: hesap, güvenlik, dil/editör, iletişim, dosyalar |
| Şifre değiştir | `/login/change_password.php` | Tercihler | tamamlandı (görsel) | tamamlandı (görsel) | tamamlandı (görsel) | erişim/test engeli | Şifre değiştirilmedi (bilinçli) |
| Dil tercihleri | `/user/language.php` | Tercihler | tamamlandı | tamamlandı | tamamlandı | tamamlandı | Form max 760px |
| Forum tercihleri | `/user/forum.php` | Tercihler | tamamlandı | tamamlandı | tamamlandı | tamamlandı | Digest kaydet → geri alındı |
| Editör tercihleri | `/user/editor.php` | Tercihler | tamamlandı (açıldı) | — | — | tamamlandı | Ortak form kabuğu |
| Bildirim tercihleri | `/message/notificationpreferences.php` | Tercihler | tamamlandı | — | — | tamamlandı | Toggle’lar CSS; AJAX kayıt Moodle |
| İleti tercihleri | `/message/edit.php` | Tercihler | tamamlandı (açıldı) | — | — | tamamlandı | Ortak bildirim kabuğu |
| Kişisel dosyalar | `/user/files.php` | Profil / çark | tamamlandı | — | — | tamamlandı | Filemanager korundu |
| Oturumlar | `/report/usersessions/user.php` | Profil / tercihler | tamamlandı | — | — | tamamlandı | “Bu cihaz”; sonlandırma tıklanmadı |
| Takvim tercihleri | `/user/calendar.php` | Tercihler | tamamlandı (link) | tamamlandı (link) | tamamlandı (link) | tamamlandı | Form kabuğu |
| İçerik bankası tercihleri | `/user/contentbank.php` | Tercihler | tamamlandı (link) | tamamlandı (link) | tamamlandı (link) | tamamlandı | Form kabuğu |
| Rozetlerim / yönet | `/badges/mybadges.php` vb. | Tercihler | tamamlandı (link) | tamamlandı (link) | tamamlandı (link) | tamamlandı | 0 rozet; boş durum |
| Rozet detay | `/badges/badge.php` | Rozetler | — | — | — | erişim/test engeli | Rozet yok |
| Veri saklama özeti | profil gizlilik linki | Profil | tamamlandı (link) | — | — | tamamlandı | |
| Veri talepleri | `/admin/tool/dataprivacy/mydatarequests.php` | — | — | — | — | bu kurulumda kapalı / erişim | DPO ayarı yok; etkinleştirilmedi |
| MFA tercihler | `/admin/tool/mfa/user_preferences.php` | — | — | — | — | bu kurulumda kapalı | `tool_mfa` enabled=0 |
| Bağlı hesaplar (OAuth) | `/auth/oauth2/linkedlogins.php` | — | — | — | — | bu kurulumda kapalı | issuer=0 |
| Portfolyo | `/user/portfolio.php` | — | — | — | — | bu kurulumda kapalı | `enableportfolios=0` |
| Güvenlik anahtarları | `/user/managetoken.php` | — | — | — | — | bu kurulumda kapalı | web services kapalı |
| Blog | — | — | — | — | — | bu kurulumda kapalı | `enableblogs=0`; UI’da 0 eşleşme |
| Notlara genel bakış | `/grade/report/overview/index.php` | Profil | tamamlandı (link) | — | — | tamamlandı | Kişisel; kurs not raporu hariç |
| Kurs not raporu (öğretmen) | `/grade/report/user/index.php` | Kurs | — | — | — | sonraki faz | Kişisel hesap kapsamı dışı |
| Site rozet yönetimi | `/badges/index.php` vb. | Site yönetimi | — | — | — | sonraki faz / dışı | Matcher’dan çıkarıldı |
| Report builder | `/reportbuilder/` | Site yönetimi | — | — | — | dışı | Matcher’dan çıkarıldı |

## Ayar doğrulamaları (kapalı özellikler)
- `enableblogs=0`
- `enableportfolios=0`
- `tool_mfa` enabled=0
- OAuth2 issuer sayısı=0
- `enablewebservices=0`

## Tercihler grupları (Moodle’ın render ettiği linkler)
1. Hesap ve profil — Profil düzenle  
2. Güvenlik ve oturumlar — Şifre değiştir (oturumlar tercihler listesinde yoksa profil üzerinden)  
3. Dil ve editör — Dil, Editör  
4. İletişim ve bildirimler — Forum, İleti, Bildirim  
5. Dosyalar ve diğer — Takvim, içerik bankası, rozetler; admin’de ayrıca rol/izin linkleri  

Boş grup render edilmez. Yalnızca Moodle’ın ürettiği `<a>` öğeleri taşınır.

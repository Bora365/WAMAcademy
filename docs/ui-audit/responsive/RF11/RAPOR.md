# Responsive RF11 — Eklenti / sunucu / rapor / güvenlik

Tarih: 2026-11-18  
Faz: **RF11**  
Durum: **RF11 — dört ekran doğrulandı (RF9/10 şablonu yeterli)**  
Commit/push/deploy: **yapılmadı**  
Eklenti yükleme / ayar kaydetme: **tıklanmadı** (bilinçli)

## Seçilen ekranlar

Plan numaralandırması korundu; RF10/RF2 yeniden sayılmadı.

| Kimlik | Rota | Gerekçe |
|---|---|---|
| ADM-PLUGINS | `/admin/category.php?category=modules` | `04-FAZ-PLANI` RF11; eklenti hub — RF10 kategori şablonu |
| ADM-SERVER | `/admin/category.php?category=server` | Sunucu hub; uzun ayar listesi / secondary nav |
| ADM-REPORTS | `/admin/category.php?category=reports` | Rapor kategorisi; alt kategori gezinmesi |
| ADM-SECURITY | `/admin/category.php?category=security` | Güvenlik hub; uzun içerik + settings çocuk sayfaları |

## Önce / sonra

Chromium: `measure-before-chromium.json` + `before/` → `measure-after-chromium.json` + `after/` (24’er görüntü).  
WebKit: `measure-after-webkit.json` (24).  
Akış: `flows-regression.json`, `admin-search-flow.json`.

| Ekran | Chromium 320…1440 | WebKit 320…1440 | pageOv |
|---|---|---|---|
| ADM-PLUGINS | 0 | 0 | **0** |
| ADM-SERVER | 0 | 0 | **0** |
| ADM-REPORTS | 0 | 0 | **0** |
| ADM-SECURITY | 0 | 0 | **0** |

Secondary nav dar ekranda yerel kaydırır; sayfa `overflowX` artmaz. Kategori link listesi `#region-main` içinde sığar.

### Yükseklik (kaydedildi, phone390)

| Ekran | `innerHeight` | `scrollHeight` |
|---|---|---|
| ADM-PLUGINS | 844 | ~2756 |
| ADM-SERVER | 844 | ~14948 |
| ADM-REPORTS | 844 | ~1952 |
| ADM-SECURITY | 844 | ~17153 |
| Admin arama `?query=plugin` | 844 | ~9885 |

## Uygulama

**Yeni ürün CSS/JS yok.** RF9/RF10 `wam-admin.css` kategori + secondary deseni bu ekranlara taşındı; ek kural gerekmedi. Sorunsuz ekranlar mevcut hâliyle doğrulandı (genel overflow kapatılmadı).

## Gerçek akışlar

| Akış | Sonuç |
|---|---|
| Her RF11 ekranında alt linke tıkla (ör. Etkinlik modülleri / Sistem yolu / Yapay zeka raporları / IP engelleyicisi) | 16/16 Chromium + 16/16 WebKit; hedef pageOv **0** |
| Secondary sekme odak (`Genel`) | 16/16 odak + viewport içi |
| Admin arama `plugin` (`/admin/search.php`) | Chromium+WebKit pageOv **0**, sonuç linkleri görünür |
| “Daha fazla” menüsü | Bu viewport’larda `d-none` / görünür değil (sekme kaydırması yeterli) |

## Regresyon (ortak stil değişmedi; yine ölçüldü)

ADM-APPEARANCE, ADM-SETTINGS, COURSE-PARTICIPANTS, COURSE-EDIT × Chromium+WebKit × 390/820 → pageOv **0**, header kırpılmıyor.

## Değişen dosyalar

| Dosya | Etki |
|---|---|
| `docs/ui-audit/responsive/RF11/*` | Ölçüm, before/after, WebKit, akış/regresyon, bu rapor |
| Ürün CSS/JS | **yok** |

## Test matrisi

| Motor | Viewport | Rol |
|---|---|---|
| Chromium | 320, 390×844, 844×390, 820, 1180, 1440 | admin |
| WebKit | aynı | admin |
| Gerçek Safari / cihaz | — | **yapılmadı** |

## Açık işler (korundu)

- **RF2 UPLOAD-VIDEO** tam taslak kayıt E2E: doğrulanmadı
- **LESSON-RESOURCE** öğrenci: `ornek-veri-yok-ogrenci` (yayın yok)
- Ayar kaydetme / eklenti kurma: bu turda yok

## Matris sayıları (`02-EKRAN-MATRISI.csv`, 164 satır)

| Durum grubu | Adet |
|---|---|
| Doğrulanan / uygulanan (`*uygulandi*` / `*dogrulandi*`) | **23** |
| Ölçüldü, henüz uygulanmadı (`olculdu`) | **31** |
| Keşfedildi, ölçülmedi (`kesfedildi-olculmedi`) | **109** |
| Veri engeli (`ornek-veri-yok-ogrenci`) | **1** |

RF11 satırları: `rf11-dogrulandi` (4). RF12 zaten `rf12-dogrulandi`.

## Sonraki öneri (uygulanmaz)

**RF13** — yükleme ve rapor oluşturucu:

| Kimlik | Rota |
|---|---|
| ADM-USER-NEW | `/user/editadvanced.php?id=-1` |
| ADM-UPLOADUSER | `/admin/tool/uploaduser/index.php` |
| ADM-UPLOADCOURSE | `/admin/tool/uploadcourse/index.php` |
| ADM-REPORTBUILDER | `/reportbuilder/index.php` |

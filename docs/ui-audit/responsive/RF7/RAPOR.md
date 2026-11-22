# Responsive RF7 — İleti / takvim / içerik bankası / rozetler

Tarih: 2026-11-19  
Faz: **RF7**  
Durum: **RF7 — dört ekran tamamlandı**  
Commit/push/deploy: **yapılmadı**  
Tercih kaydı: **yapılmadı** (bilinçli)

## Seçilen ekranlar

| Kimlik | Rota | Roller |
|---|---|---|
| ACC-MESSAGE-PREFS | `/message/edit.php` | student, teacher, admin |
| ACC-CAL-PREFS | `/user/calendar.php` | student, teacher, admin |
| ACC-CONTENTBANK | `/user/contentbank.php` | student, teacher, admin |
| ACC-BADGES | `/badges/mybadges.php` | student, teacher, admin |

## Önce

Chromium + WebKit: tüm VP **pageOv 0**.  
CAL / CONTENTBANK: select formları RF4/RF6 kabuğunda; taşma yok.  
BADGES: boş durum + arama formu OK.  

**MESSAGE-PREFS:** klasik `mform` yok — Moodle ileti **settings drawer** (radio gizlilik + toggle). pageOv 0; kontroller görünür.

## Uygulama

### `js/account.css` (RF7)
- `#page-message-edit`: drawer max-width; settings metin wrap; mobil drawer tam genişlik
- calendar / contentbank select: `width/max-width:100%`
- badges: liste max-width; mobil arama butonları flex-wrap

## Sonra

| Ekran | Chromium | WebKit |
|---|---|---|
| ACC-MESSAGE-PREFS | pageOv **0** | **0** |
| ACC-CAL-PREFS | **0** | **0** |
| ACC-CONTENTBANK | **0** | **0** |
| ACC-BADGES | **0** | **0** |

### Akışlar
- MESSAGE: drawer + radio/toggle odak
- CAL / CONTENTBANK: select odak; Kaydet `scrollIntoView` (kayıt yok)
- BADGES: liste veya boş durum görünür
- BAD **none** (Chromium + WebKit)

### Regresyon
ACC-NOTIF-PREFS, ACC-PREFS, ACC-LANGUAGE, HOME-WAM × 390/820 → pageOv **0**

## Değişen dosyalar

| Dosya | Etki |
|---|---|
| `js/account.css` | RF7 drawer / select / badges |
| `docs/ui-audit/responsive/RF7/*` | Ölçüm, before/after, akış, bu rapor |

## Açık işler (korundu)

- RF2 UPLOAD-VIDEO E2E; LESSON öğrenci; PPTX ortam/veri

## Matris (164)

Doğrulanan **~45** (+4 RF7) · keşif **109** · engeller LESSON+PPTX

## Sonraki öneri (uygulanmaz)

**RF8** — ACC-BADGE-PREFS, ACC-BACKPACK, MESSAGES, AUTH-LOGIN

# Responsive RF4 — Hesap hub + düzenleme

Tarih: 2026-11-18  
Faz: **RF4**  
Durum: **RF4 — dört ekran tamamlandı**  
Commit/push/deploy: **yapılmadı**  
Profil / şifre kaydı: **yapılmadı** (bilinçli)

## Seçilen ekranlar

Plan RF4; RF14/RF13 yeniden sayılmadı.

| Kimlik | Rota | Roller |
|---|---|---|
| ACC-PREFS | `/user/preferences.php` | student, teacher, admin |
| ACC-EDIT | `/user/edit.php?id={self}` | student, teacher |
| ACC-EDIT-ADV | `/user/editadvanced.php?id={self}` | admin |
| ACC-PASSWORD | `/login/change_password.php` | student, teacher, admin |

## Önce

Chromium: tüm VP pageOv **0**.  
WebKit derin ölçüm: ACC-EDIT / ACC-EDIT-ADV @320 → pageOv **+9** (`#id_timezone` select `scrollWidth`).

Not: Hesap düzenlemede sticky footer bilinçli `position: static` (`account.css` — sabit çubuk son alanı/odak örtmesin). Kaydet formu sonunda; `scrollIntoView` ile 390’da görünür.

## Uygulama

### `js/account.css`
- Hesap `mform` / fitem / felement: `overflow-x: auto`, `min-width: 0`
- select: `width/max-width: 100%` (WebKit timezone sızıntısı)
- TinyMCE / filemanager / filepicker: `max-width: 100%`

## Sonra

| Ekran | Chromium | WebKit |
|---|---|---|
| ACC-PREFS | pageOv **0** | **0** |
| ACC-EDIT | **0** | **0** (320: 9→0) |
| ACC-EDIT-ADV | **0** | **0** (320: 9→0) |
| ACC-PASSWORD | **0** | **0** |

### Akışlar
- PREFS: tercih linkine tıkla (ör. editör/şifre), pageOv 0
- EDIT / EDIT-ADV: alan odak; Kaydet `scrollIntoView` → görünür (kayıt yok)
- PASSWORD: parola alanları odak; Kaydet görünür (gönderilmedi)
- Klavye vekili 390×420: Kaydet hâlâ görünür

### Regresyon
HOME-WAM, COURSE-EDIT, ADM-USER-NEW × 390/820 → pageOv **0**

## Değişen dosyalar

| Dosya | Etki |
|---|---|
| `js/account.css` | WebKit select + form max-width |
| `docs/ui-audit/responsive/RF4/*` | Ölçüm, before/after, akış, bu rapor |

## Açık işler (korundu)

- RF2 UPLOAD-VIDEO E2E; LESSON öğrenci; PPTX ortam/veri

## Matris (164)

Doğrulanan **~33** (+4 RF4) · keşif **109** · engeller LESSON+PPTX

## Sonraki öneri (uygulanmaz)

**RF5** — ACC-PROFILE, ACC-FILES, ACC-SESSIONS, ACC-GRADES  
veya **RF15** — `ADM-CAT-*` keşif doğrulaması.

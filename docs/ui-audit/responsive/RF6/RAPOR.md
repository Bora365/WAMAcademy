# Responsive RF6 — Tercih formları (select) + bildirim

Tarih: 2026-11-18  
Faz: **RF6**  
Durum: **RF6 — dört ekran tamamlandı**  
Commit/push/deploy: **yapılmadı**  
Tercih kaydı: **yapılmadı** (bilinçli)

## Seçilen ekranlar

| Kimlik | Rota | Roller |
|---|---|---|
| ACC-LANGUAGE | `/user/language.php` | student, teacher, admin |
| ACC-EDITOR | `/user/editor.php` | student, teacher, admin |
| ACC-FORUM | `/user/forum.php` | student, teacher, admin |
| ACC-NOTIF-PREFS | `/message/notificationpreferences.php` | student, teacher, admin |

## Önce

Chromium + WebKit: pageOv **0** (LANGUAGE / EDITOR / FORUM).  
Select’ler `max-width:100%` (RF4); WebKit option `scrollWidth` form içinde kalıyor.

**ACC-NOTIF-PREFS:** `preference-table`’a verilen `wam-acc-table` mobil `display:block` kuralı matrisi bozuyordu — hücreler ~450px, wrap içinde yatay kaydırma, kanal etiketleri dağınık. phone390 tableW **480** (wrap ~294).

## Uygulama

### `js/account.css`
- Mobil card-stack: `table.wam-acc-table:not(.preference-table)` — tercih matrisi hariç
- RF6 bildirim: satır kartları; `preference-name` tam genişlik; kanal `td` flex + `data-label` (Tercih/Web)
- LANGUAGE / EDITOR / FORUM select: `width/max-width:100%`, ellipsis

## Sonra

| Ekran | Chromium | WebKit |
|---|---|---|
| ACC-LANGUAGE | pageOv **0** | **0** |
| ACC-EDITOR | **0** | **0** |
| ACC-FORUM | **0** | **0** |
| ACC-NOTIF-PREFS | **0** (phone tableW 480→**292**) | **0** |

Masaüstü/tablet tableOv ~4–5 px (önemsiz scrollbar).

### Akışlar
- LANGUAGE / EDITOR / FORUM: select odak; Kaydet `scrollIntoView` (kayıt yok)
- NOTIF: tablo + toggle odak (gönderim yok)
- BAD **none** (Chromium + WebKit)

### Regresyon
ACC-PREFS, ACC-PASSWORD, ACC-FILES, HOME-WAM × 390/820 → pageOv **0**

## Değişen dosyalar

| Dosya | Etki |
|---|---|
| `js/account.css` | preference-table mobil kart; select width |
| `docs/ui-audit/responsive/RF6/*` | Ölçüm, before/after, akış, bu rapor |

## Açık işler (korundu)

- RF2 UPLOAD-VIDEO E2E; LESSON öğrenci; PPTX ortam/veri

## Matris (164)

Doğrulanan **~41** (+4 RF6) · keşif **109** · engeller LESSON+PPTX

## Sonraki öneri (uygulanmaz)

**RF7** — ACC-MESSAGE-PREFS, ACC-CAL-PREFS, ACC-CONTENTBANK, ACC-BADGES

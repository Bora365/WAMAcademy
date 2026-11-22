# Responsive RF14 — Kalan çekirdek

Tarih: 2026-11-18  
Faz: **RF14**  
Durum: **RF14 — iki ekran tamamlandı, PPTX açık**  
Commit/push/deploy: **yapılmadı**  
Tamamlanma ayarı kaydı / PPTX dönüşümü: **yapılmadı**

## Seçilen ekranlar

Plan RF14 (3 ekran); önceki fazlar yeniden sayılmadı.

| Kimlik | Rota | Sonuç |
|---|---|---|
| COURSE-COMPLETION | `/course/completion.php?id=2` | Doğrulandı + WebKit select düzeltmesi |
| ADM-UNSUPPORTED | `/admin/category.php?category=unsupported` | Doğrulandı (boş kategori hub; şablon OK) |
| PPTX-VIEW | `/local/wamakademi/pptx_view.php?id=7` | **Açık** — kurs kaynaklarında `.pptx` yok; LibreOffice yok |

## Önce

Chromium `measure-before-chromium.json` + `before/`.

| Ekran | Chromium pageOv | WebKit not |
|---|---|---|
| COURSE-COMPLETION | **0** (tüm VP) | 320’de select `scrollWidth` → **+43** (sonra ölçüldü) |
| ADM-UNSUPPORTED | **0** | **0** |
| PPTX-VIEW | exception sayfası pageOv 0 | “PPTX dosyası bulunamadı” |

## Uygulama

### `wam-course.css` (COURSE-COMPLETION)
- `form.mform[action*="completion.php"]` + fitem/felement: `overflow-x: auto`, `min-width: 0`
- select: `width/max-width: 100%` (WebKit option sızıntısı, RF10/RF13 deseni)

### PPTX-VIEW
Ürün CSS yok. Ortam/veri engeli: örnek PPTX resource yok; `libreoffice`/`soffice` kurulu değil. Başarılı oynatıcı doğrulaması yapılmadı.

## Sonra

| Ekran | Chromium | WebKit |
|---|---|---|
| COURSE-COMPLETION | pageOv **0** | **0** (320: 43→0) |
| ADM-UNSUPPORTED | **0** | **0** |
| PPTX-VIEW | blocked | blocked |

### Akışlar
- COMPLETION: aggregation select odak; fieldset aç (kayıt yok)
- UNSUPPORTED: secondary sekme odak + komşu sekmeye gezinme
- Regresyon: ADM-APPEARANCE, ADM-UPLOADCOURSE, COURSE-EDIT, COURSE-PARTICIPANTS × 390/820 → pageOv **0**

## Değişen dosyalar

| Dosya | Etki |
|---|---|
| `css/wam-course.css` | Completion form WebKit taşması |
| `docs/ui-audit/responsive/RF14/*` | Ölçüm, before/after, akış, bu rapor |

## Açık işler

- **PPTX-VIEW:** örnek `.pptx` + LibreOffice gerekir
- **RF2 UPLOAD-VIDEO E2E** ve **LESSON-RESOURCE** öğrenci (`visible=0`) hâlâ açık

## Matris (164)

| Durum | Adet (yaklaşık) |
|---|---|
| Doğrulanan / uygulanan | **29** (+2 RF14; PPTX açık) |
| `olculdu` | kalan çekirdek / hesap |
| `kesfedildi-olculmedi` | **109** (RF15+) |
| Veri/ortam engeli | LESSON + PPTX |

## Sonraki öneri (uygulanmaz)

**RF4** — ACC-PREFS, ACC-EDIT, ACC-EDIT-ADV, ACC-PASSWORD  
veya **RF15** — ilk `ADM-CAT-*` keşif grubu (RF10 şablon doğrulaması).

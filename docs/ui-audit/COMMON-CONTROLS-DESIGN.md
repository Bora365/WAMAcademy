# Ortak kontroller — tasarım (Common Controls R1)

Referans görsel bu turda eklenmedi; aşağıdaki dil yazılı spesifikasyon olarak kullanıldı.

## Dil

| Öğe | Değer |
|---|---|
| Yüzey | `--wam-surface` (#FFFFFF) |
| Metin | `--wam-ink` (#0F2747) — yüzey üstünde ~15:1 |
| Kenarlık | `--wam-control-border` (#7F93AE) — yüzey üstünde ~3.1:1 (UI bileşeni) |
| Yükseklik | `--wam-ctl-h` → 44 px |
| Köşe | `--wam-ctl-radius` → 8 px |
| Yatay boşluk | `--wam-ctl-pad-x` → 12 px (select sağda 40 px chevron payı) |
| Metin boyutu | 15 px (`--wam-body`) |
| Chevron | `--wam-select-arrow` (tek SVG, sağda) |
| Hover | `--wam-ctl-hover-bg` / açık mavi soft |
| Focus | `--wam-focus` halkası + primary kenarlık |
| Seçili | soft arka plan + 3 px sol inset |
| Disabled | `--wam-field-disabled-bg` |
| Hata | `--wam-danger` kenarlık |
| Geçiş | `--wam-dur-ctl` 140 ms; `prefers-reduced-motion: reduce` → yok |
| Panel gölge | `--wam-ctl-panel-shadow` |
| Panel z | `--wam-ctl-panel-z` 1060 |

## Bileşen aileleri

1. **Yerel select / alan** — JS ile değiştirilmez. Açık `<option>` penceresi işletim sistemine aittir.
2. **Özel açılır panel** — `.dropdown-menu`, `.wam-gear__menu`, `.wam-row-actions__menu`, kullanıcı menüsü: beyaz yüzey, 44 px satır, iç kaydırma, viewport sınırları.
3. **Autocomplete / çoklu seçim** — kompakt chip, erişilebilir kaldırma, öneri paneli, loading / sonuç yok sınıfları.
4. **Accordion** — `.ftoggler` + 28 px chevron düğme, 18 px başlık; büyük renkli kare yok.
5. **Liste** — `.list-group` ince ayırıcı; katalog kart ızgarası korunur; tercihler listesi tek yüzey.

## Kaynak

- Tokenlar: `public/local/wamakademi/css/wam-base.css`
- Ortak kurallar: `public/local/wamakademi/css/wam-controls.css` (forms/users sonrası yüklenir)
- Sayfa özel ince ayarlar: course / users / account (davranış korunur)

## Önceki belgeler

[COURSE-UI-DESIGN.md](COURSE-UI-DESIGN.md), [IMPLEMENTATION-COURSE-R1.md](IMPLEMENTATION-COURSE-R1.md), [IMPLEMENTATION-ACCOUNT-P4.md](IMPLEMENTATION-ACCOUNT-P4.md).

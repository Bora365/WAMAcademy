# Uygulama raporu — Ortak kontroller R1

Tarih: 2026-11-16. Commit / push / deploy yapılmadı.

## Özet

Select, açılır menü, autocomplete, accordion ve liste görünümleri tek token ailesine bağlandı. Yeni dosya `wam-controls.css`; çakışan base/admin/forms/account select kuralları sadeleştirildi.

## Değişen dosyalar

| Dosya | Değişiklik |
|---|---|
| `public/local/wamakademi/css/wam-controls.css` | **Yeni** — ortak kontrol ailesi |
| `public/local/wamakademi/css/wam-base.css` | `--wam-ctl-*` tokenları; eski 40 px `.form-select` bloğu kaldırıldı |
| `public/local/wamakademi/css/wam-forms.css` | Select/autocomplete görsel kuralları controls’a taşındı (yorum) |
| `public/local/wamakademi/css/wam-admin.css` | Yinelenen form alan bloğu kaldırıldı |
| `public/local/wamakademi/js/account.css` | Yinelenen form-select / focus bloğu kaldırıldı |
| `public/local/wamakademi/css/wam-users.css` | Action menü radius/gölge → ctl token |
| `public/local/wamakademi/css/wam-course.css` | İşlemler menüsü radius/gölge → ctl token |
| `public/local/wamakademi/lib.php` | `wam-controls.css` forms/users sonrası yüklenir |
| `docs/ui-audit/COMMON-CONTROLS-*.md` | Envanter + tasarım + bu rapor |
| `docs/ui-audit/screenshots/common-controls-r1/` | Sonrası görseller |

## Ekran sayıları

| | Sayı |
|---|---|
| Kaynak taramasında incelenen aile | 5 (select, dropdown, autocomplete, accordion, liste) |
| Stil / kod değiştirilen yüzey | Ortak + admin + course + users + account CSS |
| DOM ile doğrulanan sayfa anlık görüntüsü | 6 (course edit, catalog, users, participants, prefs, home) |
| Görüntü alınıp incelenen | 4 (1440 course edit, 1440 users menu, 1440 participants menu, 390 course edit) |
| Rol anlık görüntüsü | admin (öğrenci/eğitmen HTML üretildi; görsel menü doğrulaması admin) |

## Görsel test

| Kontrol | Sonuç |
|---|---|
| Select 44 px / 8 px / 15 px / pad-right 40 px | Geçti (course edit 1440 ve 390) |
| Chevron tek SVG, metin çakışması yok | Geçti (pad-right 40) |
| Ftoggler 18 px, chevron 28×28 | Geçti |
| Action / İşlemler menüsü beyaz, 44 px satır | Geçti |
| Sayfa yatay taşması (1440, 390) | Yok |
| ink/surface kontrast | 14.98:1 |
| Yerel `<select>` açık pencere | İşletim sistemi; CSS ile stillenemez (sınır) |

## İşlevsel test

| Kontrol | Sonuç | Not |
|---|---|---|
| wam-controls.css yükleniyor | Geçti | `?v=` filemtime |
| Form name/id/sesskey | Dokunulmadı | — |
| Katılımcı İşlemler menüsü açılma | Geçti | Ağ kapalı snapshot |
| Kullanıcı listesi action menu | Geçti | — |
| Autocomplete AJAX sonuçları | Doğrulanmadı | Snapshot’ta ağ yok; chip/öneri stilleri DOM sınıflarına bağlı |
| Türkçe arama (İ/ı) | Doğrulanmadı | Canlı sunucu yanıtı gerekir |
| Escape / odak dönüşü menülerde | Kısmen | Önceki turda İşlemler menüsü doğrulandı; bu turda stil odaklı |
| Öğrenci / eğitmen görünümü | Kısmen | CSS global `wam-ui`; rol farkı bağlantı setinde |

## Temsilî ölçümler (admin, course edit)

| Viewport | Select H | pad-right | overflow | ftoggler | autocomplete alanı |
|---|---|---|---|---|---|
| 1440 | 44 | 40px | yok | var | var |
| 390 | 44 | 40px | yok | var | var |

## Görseller

`docs/ui-audit/screenshots/common-controls-r1/after/`

- `1440-course-edit-selects.png`
- `390-course-edit-selects.png`
- `1440-users-action-menu.png`
- `1440-participants-actions-menu.png`

Gerçek “önce” görüntüsü bu turda ayrı kaydedilmedi; önceki course/forms R1 görselleri `screenshots/course-r1` ve `forms-r1` altında referans.

## Kalan işler

1. Canlı oturumda autocomplete arama / loading / sonuç yok + Türkçe arama.
2. 1024 ve 768 için ayrı ekran görüntüsü (DOM ölçüldü; Emulation ile 390/1440 görüntü alındı).
3. Eğitmen ve öğrenci ile menü/select görsel teyidi.
4. Katalog sıralama select’inin styles.css eski kurallarıyla görsel farkının canlı teyidi.
5. Hesap formlarındaki 10 px radius (account.css) ile ortak 8 px ailesinin bilinçli farkı — istenirse hizalanır.

## Doğrulanmayan (tamamlandı sayılmaz)

- AJAX autocomplete sonuç listesi içeriği
- Modal içi katılımcı ekle autocomplete (ağ kapalı)
- Sticky footer select etkileşimi
- Disabled / hata durumlarının görsel ekran görüntüsü (kurallar var; durum DOM’da zorlanmadı)

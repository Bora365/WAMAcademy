# Ortak UI hattı — Filepicker seçili depo kontrastı

Tarih: 2026-11-16  
Kaynak faz: Hesap Faz 4 kapanış  
Kapsam önerisi: ortak kontroller / diyalog teması (`wam-controls` veya Boost filepicker override)

## Bulgu

Dosya seçici sol menüsünde **seçili depo** satırında metin ile zemin kontrastı WCAG AA’nın altında.

| Öğe | Değer |
|---|---|
| Seçici | `.file-picker a.nav-link.active` |
| Metin rengi | `rgb(37, 99, 235)` (`--wam-primary` / link mavisi) |
| Zemin | `rgb(15, 108, 191)` (Boost pills active) |
| Kontrast oranı | **1.04:1** (hedef ≥ 4.5:1 normal metin) |

## Tekrar adımları

1. Eğitmen veya öğrenci ile giriş yap.
2. Çark → **Kişisel dosyalar** → `/user/files.php`.
3. Filemanager’da **Ekle** / `fp-btn-add` ile **Dosya seçici**yi aç.
4. Sol listede ilk depo (**Son Dosyalar**) seçili (active) durumda.
5. DevTools Computed: active `a.nav-link` üzerinde `color` ve `background-color` ölç.

Kanıt görseli: `docs/ui-audit/screenshots/account-p4/filepicker-selected-repo-contrast-phase4-close.png`

## Hesap katmanı notu

Hesap CSS’i filepicker z-index’ini sınırlar; seçili satır rengi Boost/nav-pills’ten gelir. Düzeltme hesap-özel değil, ortak diyalog/kontrol hattında yapılmalı.

## Beklenen

Seçili satırda açık metin (beyaz) veya daha açık zemin + koyu metin; kontrast ≥ 4.5:1.

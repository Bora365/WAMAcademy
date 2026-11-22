# Ortak UI hattı — Form autocomplete öneri paneli

Tarih: 2026-11-16  
Kaynak faz: Hesap Faz 4 (R1 boşluk + kapanış)  
Kapsam önerisi: ortak kontroller (`wam-controls` autocomplete)

## Bulgu

Hesap **Profili düzenle → İlgi alanları** etiket alanında:

| Senaryo | Sonuç |
|---|---|
| Türkçe yazım (`eğit` / `eğitim`) | Alan kabul ediyor |
| Klavye ile seçim | **Geçti** — Enter sonrası seçenek `isg` listbox’ta selected görüldü |
| Öneri paneli otomasyonu | **Güvenilir değil** — `.form-autocomplete-suggestions` çoğu denemede `display:none` / boş HTML kaldı |

Bu bir kesin görsel regresyon kanıtı değil; otomasyonun paneli yakalayamaması + ara sıra boş panel. Ortak autocomplete görünürlük / odak / AJAX sonuç listesi doğrulaması önerilir.

## Tekrar adımları (manuel)

1. Eğitmen: Çark → Tercihler → Profil düzenle → `/user/edit.php?id={self}`.
2. **İlgi alanları** bölümünü aç.
3. “Etiketleri girin” alanına `isg` veya `eğitim` yaz (sistemde etiket yoksa önce CLI ile seed).
4. Öneri listesinin açılıp açılmadığını gözle.
5. Ok tuşları / Enter ile seç; seçimin chip / listbox’ta göründüğünü doğrula.
6. Anlamsız metin (`zzzqqqxxx`) ile boş sonuç / oluşturma davranışını not et.

## Hesap katmanı notu

Alan Moodle `data-fieldtype=tags` / form-autocomplete. Hesap CSS alan yüzeyini korur; öneri paneli çekirdek + ortak kontrol stiline bağlı. Hesap tarafında ikinci bir autocomplete mekanizması eklenmedi.

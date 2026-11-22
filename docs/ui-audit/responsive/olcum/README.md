# Responsive ölçüm aracı

Ürün kodunu değiştirmez. Yerel Moodle’a bağlanır, oturum açar, viewport ölçer, JSON ve PNG üretir.

## Kurulum

```bash
cd docs/ui-audit/responsive/olcum
npm install playwright@1.49.1
npx playwright install chromium
# İsteğe bağlı: npx playwright install webkit
```

## Çalıştırma

```bash
export WAM_BASE_URL=http://localhost:8081
export WAM_AUDIT_PASS='…'   # parola; dosyaya yazılmaz
node discover.mjs --role student
node measure.mjs --suite layerA
node measure.mjs --suite deep
```

Çıktılar: `../kanitlar/` ve `../olcum/out/`.

## Başarı koşulu

Sayfa başarılı sayılmaz eğer:

- URL `/login/` içeriyorsa
- `#region-main`, `main`, veya `.login-container` (yalnız giriş ekranı) yoksa
- `document.readyState` complete değilse

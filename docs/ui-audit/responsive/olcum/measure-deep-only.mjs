/**
 * Yalnız katman B: her rolde her kind için 1 temsilci × ara viewportlar + resize/klavye.
 * Katman A'yı tekrar etmez (measure-layerA-chromium.json kullanılır).
 */
import { chromium } from 'playwright';
import path from 'node:path';
import {
  BASE, PASS, VIEWPORTS, ensureDirs, login, logout, writeJson, OUT,
  pageReady, measurePage, openMenu, closeMenu, screenshot,
} from './lib.mjs';
import { SCREENS, LAYER_B_EXTRA } from './screens.mjs';

async function resolvePath(page, screen) {
  if (screen.resolve === 'firstResource') {
    await page.goto(`${BASE}/course/view.php?id=2`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(500);
    const href = await page.evaluate(() => {
      const a = document.querySelector('a[href*="/mod/resource/view.php"]');
      return a ? a.getAttribute('href') : null;
    });
    if (!href) return null;
    return href.startsWith('http') ? new URL(href).pathname + new URL(href).search : href;
  }
  if (screen.selfId || screen.edit) {
    const id = await page.evaluate(() => {
      const a = document.querySelector('a[href*="/user/profile.php?id="]');
      if (a) return new URL(a.href).searchParams.get('id');
      return null;
    });
    if (!id) return screen.path;
    if (screen.id === 'ACC-PROFILE') return `/user/profile.php?id=${id}`;
    if (screen.id === 'ACC-EDIT') return `/user/edit.php?id=${id}&returnto=profile`;
    if (screen.id === 'ACC-EDIT-ADV') return `/user/editadvanced.php?id=${id}`;
  }
  return screen.path;
}

async function run(page, screen, role, vpKey, results) {
  const vp = VIEWPORTS[vpKey];
  await page.setViewportSize({ width: vp.width, height: vp.height });
  const rel = await resolvePath(page, screen);
  if (!rel) {
    results.push({ id: screen.id, role, viewport: vp.label, status: 'skipped-no-data' });
    return;
  }
  await page.goto(`${BASE}${rel}`, { waitUntil: 'domcontentloaded', timeout: 30000 }).catch(() => null);
  await page.waitForTimeout(500);
  const ready = await pageReady(page, { allowLogin: !!screen.allowLogin });
  if (!ready.ok) {
    results.push({
      id: screen.id, role, viewport: vp.label, w: vp.width, h: vp.height,
      status: ready.isLogin ? 'redirect-login' : 'not-ready', url: ready.url,
    });
    return;
  }
  let menuOpened = false;
  if (vp.width <= 1024) menuOpened = await openMenu(page);
  const metrics = await measurePage(page);
  const shotName = `deep_${screen.id}_${role}_${vp.label}`.replace(/[^a-zA-Z0-9_-]/g, '_');
  let shot = null;
  try { shot = await screenshot(page, shotName); } catch { /* ignore */ }
  if (menuOpened) await closeMenu(page);
  results.push({
    id: screen.id, role, viewport: vp.label, w: vp.width, h: vp.height,
    status: 'ok', url: ready.url, metrics,
    findingHints: [
      ...(metrics.overflowXPx > 2 ? [`overflowX=${metrics.overflowXPx}`] : []),
      ...(metrics.offscreenActions?.length ? [`offscreen=${metrics.offscreenActions.length}`] : []),
    ],
    screenshot: shot ? path.basename(shot) : null,
    engine: 'chromium', kind: screen.kind,
  });
  process.stdout.write('.');
}

async function main() {
  if (!PASS) throw new Error('WAM_AUDIT_PASS gerekli');
  ensureDirs();
  const browser = await chromium.launch({ headless: true });
  const results = [];

  // Guest login deep viewports
  {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    const login = SCREENS.find((s) => s.id === 'AUTH-LOGIN');
    for (const vp of LAYER_B_EXTRA) await run(page, login, 'guest', vp, results);
    await ctx.close();
  }

  for (const role of ['student', 'teacher', 'admin']) {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await login(page, role);
    const screens = SCREENS.filter((s) => s.roles?.includes(role) && !s.skipMeasureNav && !s.optional);
    const byKind = new Map();
    for (const s of screens) {
      if (!byKind.has(s.kind)) byKind.set(s.kind, s);
    }
    for (const screen of byKind.values()) {
      for (const vp of LAYER_B_EXTRA) {
        await run(page, screen, role, vp, results);
      }
      // open menu then resize 390 → 820
      await page.setViewportSize({ width: 390, height: 844 });
      const rel = await resolvePath(page, screen);
      if (rel) {
        await page.goto(`${BASE}${rel}`, { waitUntil: 'domcontentloaded' }).catch(() => null);
        await openMenu(page);
        await page.setViewportSize({ width: 820, height: 1180 });
        await page.waitForTimeout(400);
        const m = await measurePage(page);
        results.push({
          id: screen.id, role, viewport: 'resize-menu-390to820', status: 'ok',
          metrics: m, menuStillOpen: m.menuOpen, engine: 'chromium', kind: 'resize-state',
        });
        await closeMenu(page);
      }
    }
    await logout(page);
    await ctx.close();
    console.log(`\nrole ${role} deep-only done`);
  }

  writeJson(`${OUT}/measure-deep-chromium.json`, {
    suite: 'deep-only',
    engine: 'chromium',
    base: BASE,
    generatedAt: new Date().toISOString(),
    count: results.length,
    ok: results.filter((r) => r.status === 'ok').length,
    results,
  });
  console.log(`\nWrote measure-deep-chromium.json (${results.length})`);
  await browser.close();
}

main().catch((e) => { console.error(e); process.exit(1); });

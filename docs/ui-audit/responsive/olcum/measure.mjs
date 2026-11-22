/**
 * Katman A / deep ölçüm. Parola: WAM_AUDIT_PASS
 */
import { chromium, webkit } from 'playwright';
import path from 'node:path';
import {
  BASE, PASS, VIEWPORTS, ensureDirs, login, logout, writeJson, OUT,
  pageReady, measurePage, openMenu, closeMenu, screenshot,
} from './lib.mjs';
import { SCREENS, LAYER_A, LAYER_B_EXTRA } from './screens.mjs';

const suite = (process.argv.find((a) => a.startsWith('--suite=')) || '--suite=layerA').split('=')[1];
const engineName = (process.argv.find((a) => a.startsWith('--engine=')) || '--engine=chromium').split('=')[1];
const roleFilter = process.argv.find((a) => a.startsWith('--role='))?.split('=')[1];
const idFilter = process.argv.find((a) => a.startsWith('--id='))?.split('=')[1];

async function resolvePath(page, screen, role) {
  if (screen.path && !screen.selfId && !screen.edit && screen.resolve !== 'firstResource') {
    return screen.path;
  }
  if (screen.selfId || screen.edit) {
    const me = await page.evaluate(() => {
      const m = document.body?.innerHTML?.match(/\/user\/(?:profile|edit)\.php\?id=(\d+)/);
      return m ? m[1] : null;
    });
    // Prefer Moodle sess
    const id = await page.evaluate(async () => {
      // from navbar profile link
      const a = document.querySelector('a[href*="/user/profile.php?id="]');
      if (a) {
        const u = new URL(a.href);
        return u.searchParams.get('id');
      }
      return null;
    });
    const uid = id || me;
    if (!uid) return screen.path;
    if (screen.id === 'ACC-PROFILE') return `/user/profile.php?id=${uid}`;
    if (screen.id === 'ACC-EDIT') return `/user/edit.php?id=${uid}&returnto=profile`;
    if (screen.id === 'ACC-EDIT-ADV') return `/user/editadvanced.php?id=${uid}`;
    return screen.path;
  }
  if (screen.resolve === 'firstResource') {
    await page.goto(`${BASE}/course/view.php?id=2`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(600);
    const href = await page.evaluate(() => {
      const a = document.querySelector('a[href*="/mod/resource/view.php"]');
      return a ? a.getAttribute('href') : null;
    });
    if (!href) return null;
    return href.startsWith('http') ? new URL(href).pathname + new URL(href).search : href;
  }
  return screen.path;
}

async function runOne(page, screen, role, vpKey, results) {
  const vp = VIEWPORTS[vpKey];
  await page.setViewportSize({ width: vp.width, height: vp.height });
  const rel = await resolvePath(page, screen, role);
  if (!rel) {
    results.push({
      id: screen.id, role, viewport: vp.label, w: vp.width, h: vp.height,
      status: 'skipped-no-data', engine: engineName,
    });
    return;
  }
  const url = rel.startsWith('http') ? rel : `${BASE}${rel}`;
  try {
    await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 45000 });
    await page.waitForTimeout(700);
  } catch (e) {
    results.push({
      id: screen.id, role, viewport: vp.label, w: vp.width, h: vp.height,
      status: 'nav-error', error: String(e.message || e), engine: engineName,
    });
    return;
  }

  const ready = await pageReady(page, { allowLogin: !!screen.allowLogin });
  if (!ready.ok) {
    results.push({
      id: screen.id, role, viewport: vp.label, w: vp.width, h: vp.height,
      status: ready.isLogin ? 'redirect-login' : 'not-ready',
      url: ready.url, engine: engineName,
    });
    return;
  }

  // Optional interactions for taskability (non-destructive)
  let menuOpened = false;
  if (['shell', 'list', 'course', 'admin'].includes(screen.kind) && vp.width <= 1024) {
    menuOpened = await openMenu(page);
  }

  if (screen.kind === 'table' && /participants|user\.php|usersessions/i.test(rel)) {
    const act = page.locator('.wam-row-actions__toggle, .action-menu a, [data-toggle="dropdown"]').first();
    if (await act.count()) await act.click({ timeout: 2000 }).catch(() => {});
    await page.waitForTimeout(300);
  }

  if (screen.kind === 'form' && screen.id === 'ACC-EDIT') {
    // expand last fieldset if collapsed
    await page.locator('.ftoggler').last().click({ timeout: 2000 }).catch(() => {});
    await page.waitForTimeout(200);
  }

  const metrics = await measurePage(page);
  const shotName = `${screen.id}_${role}_${vp.label}_${engineName}`.replace(/[^a-zA-Z0-9_-]/g, '_');
  let shot = null;
  try {
    shot = await screenshot(page, shotName);
  } catch {
    shot = null;
  }

  if (menuOpened) await closeMenu(page);

  const findingHints = [];
  if (metrics.overflowXPx > 2) findingHints.push(`overflowX=${metrics.overflowXPx}`);
  if (metrics.offscreenActions?.length) findingHints.push(`offscreen=${metrics.offscreenActions.length}`);
  if (metrics.focusOk && metrics.focusOk.visibleInViewport === false) findingHints.push('focus-clipped');
  if (vp.width <= 390 && !metrics.hasHamburger && !screen.allowLogin) findingHints.push('no-hamburger');

  results.push({
    id: screen.id,
    role,
    viewport: vp.label,
    w: vp.width,
    h: vp.height,
    status: 'ok',
    url: ready.url,
    title: ready.title,
    bodyClasses: ready.bodyClasses,
    metrics,
    findingHints,
    screenshot: shot ? path.basename(shot) : null,
    engine: engineName,
    kind: screen.kind,
    task: screen.task,
  });
  process.stdout.write('.');
}

async function main() {
  if (!PASS && suite !== 'guest') {
    console.error('WAM_AUDIT_PASS gerekli');
    process.exit(1);
  }
  ensureDirs();
  const engine = engineName === 'webkit' ? webkit : chromium;
  let browser;
  try {
    browser = await engine.launch({ headless: true });
  } catch (e) {
    writeJson(`${OUT}/engine-${engineName}-unavailable.json`, { error: String(e) });
    console.error(`Engine ${engineName} yok:`, e.message);
    process.exit(2);
  }

  const viewports = suite === 'deep' ? [...LAYER_A, ...LAYER_B_EXTRA] : LAYER_A;
  const results = [];
  const roles = roleFilter ? [roleFilter] : ['student', 'teacher', 'admin'];

  // Guest login screen once
  if (!roleFilter || roleFilter === 'guest') {
    const context = await browser.newContext();
    const page = await context.newPage();
    const loginScreen = SCREENS.find((s) => s.id === 'AUTH-LOGIN');
    for (const vp of viewports.filter((v) => suite === 'deep' || LAYER_A.includes(v))) {
      await runOne(page, loginScreen, 'guest', vp, results);
    }
    await context.close();
  }

  for (const role of roles) {
    if (role === 'guest') continue;
    const context = await browser.newContext();
    const page = await context.newPage();
    try {
      await login(page, role);
    } catch (e) {
      results.push({ role, status: 'login-failed', error: String(e.message || e), engine: engineName });
      await context.close();
      continue;
    }

    const screens = SCREENS.filter((s) => {
      if (idFilter && s.id !== idFilter) return false;
      if (s.roles && !s.roles.includes(role) && !(s.roles.includes('guest') && role === 'guest')) return false;
      if (s.skipMeasureNav) return false;
      if (suite === 'layerA' && s.optional) return false;
      return true;
    });

    // Layer A: all screens × 3 viewports
    // Deep: subset of kinds × extra viewports (plus layer A already in list for deep suite)
    const deepKinds = new Set(['shell', 'list', 'form', 'table', 'player', 'admin', 'account', 'calendar', 'messages', 'files', 'auth', 'course']);
    for (const screen of screens) {
      const vps = suite === 'deep'
        ? (deepKinds.has(screen.kind) ? viewports : LAYER_A)
        : LAYER_A;
      // For deep suite, only run extra viewports on representatives to save time?
      // Plan says every unique screen gets layer A; deep for each screen type.
      // So for deep: layer A all + layer B for one per kind per role.
      let useVps = LAYER_A;
      if (suite === 'deep') {
        useVps = LAYER_A;
      }
      for (const vp of useVps) {
        await runOne(page, screen, role, vp, results);
      }
    }

    if (suite === 'deep') {
      // One representative per kind with extra viewports
      const seenKind = new Set();
      for (const screen of screens) {
        if (seenKind.has(screen.kind)) continue;
        seenKind.add(screen.kind);
        for (const vp of LAYER_B_EXTRA) {
          await runOne(page, screen, role, vp, results);
        }
        // Resize while menu open
        await page.setViewportSize({ width: 390, height: 844 });
        const rel = await resolvePath(page, screen, role);
        if (rel) {
          await page.goto(`${BASE}${rel}`, { waitUntil: 'domcontentloaded' }).catch(() => {});
          await openMenu(page);
          await page.setViewportSize({ width: 820, height: 1180 });
          await page.waitForTimeout(400);
          const m = await measurePage(page);
          results.push({
            id: screen.id, role, viewport: 'resize-menu-390to820', status: 'ok',
            metrics: m, engine: engineName, kind: 'resize-state',
            menuStillOpen: m.menuOpen,
          });
          await closeMenu(page);
        }
      }
    }

    await logout(page);
    await context.close();
    console.log(`\nrole ${role} done`);
  }

  const outFile = `${OUT}/measure-${suite}-${engineName}.json`;
  writeJson(outFile, {
    suite,
    engine: engineName,
    base: BASE,
    generatedAt: new Date().toISOString(),
    count: results.length,
    ok: results.filter((r) => r.status === 'ok').length,
    results,
  });
  console.log(`\nWrote ${outFile} (${results.length} rows, ${results.filter((r) => r.status === 'ok').length} ok)`);
  await browser.close();
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});

/**
 * RF11: gezinme/arama/odak + RF10/PARTICIPANTS/COURSE-EDIT regresyon
 * WAM_AUDIT_PASS=… node flows-and-regression.mjs
 */
import { chromium, webkit } from '../olcum/node_modules/playwright/index.mjs';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { BASE, PASS, login, logout, pageReady, measurePage } from '../olcum/lib.mjs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const OUT = path.join(__dirname, 'flows-regression.json');

const RF11 = [
  { id: 'ADM-PLUGINS', path: '/admin/category.php?category=modules', linkText: /Etkinlik|Activity|mod_/i },
  { id: 'ADM-SERVER', path: '/admin/category.php?category=server', linkText: /Oturum|Session|Sistem|System|Yol|Path/i },
  { id: 'ADM-REPORTS', path: '/admin/category.php?category=reports', linkText: /Yorum|Comment|Log|Günlük|Event/i },
  { id: 'ADM-SECURITY', path: '/admin/category.php?category=security', linkText: /Site ilkeleri|Site policies|IP|HTTP/i },
];

const REGRESSION = [
  { id: 'ADM-APPEARANCE', path: '/admin/category.php?category=appearance', role: 'admin' },
  { id: 'ADM-SETTINGS', path: '/admin/settings.php?section=frontpagesettings', role: 'admin' },
  { id: 'COURSE-PARTICIPANTS', path: '/user/index.php?id=2', role: 'teacher' },
  { id: 'COURSE-EDIT', path: '/course/edit.php?id=2', role: 'teacher' },
];

const VPS = [
  { w: 390, h: 844, l: 'phone390' },
  { w: 820, h: 1180, l: 'ipadPort' },
  { w: 1180, h: 820, l: 'ipadLand' },
  { w: 1440, h: 900, l: 'desk1440' },
];

async function probe(page) {
  return page.evaluate(() => {
    const pageOv = Math.max(0, document.documentElement.scrollWidth - innerWidth);
    const nav = document.querySelector('.secondary-navigation .navigation');
    const header = document.querySelector('.navbar.fixed-top, header.navbar');
    const hr = header?.getBoundingClientRect();
    return {
      pageOv,
      ih: innerHeight,
      iw: innerWidth,
      scrollH: document.documentElement.scrollHeight,
      secOv: nav ? Math.max(0, nav.scrollWidth - nav.clientWidth) : null,
      headerW: hr ? Math.round(hr.width) : null,
      headerClipped: hr ? hr.right > innerWidth + 2 : null,
      body: (document.body.className.match(/wam-\S+/g) || []).slice(0, 8),
    };
  });
}

async function flowOne(page, screen, vp) {
  await page.setViewportSize({ width: vp.w, height: vp.h });
  await page.goto(`${BASE}${screen.path}`, { waitUntil: 'domcontentloaded', timeout: 45000 });
  await page.waitForTimeout(400);
  const ready = await pageReady(page);
  if (!ready.ok) return { id: screen.id, vp: vp.l, status: ready.isLogin ? 'login' : 'not-ready', url: ready.url };

  const metrics = await measurePage(page);
  const table = await probe(page);

  // Focus first secondary tab / first category link
  const focus = await page.evaluate(() => {
    const tab = document.querySelector('.secondary-navigation .nav-tabs > .nav-item > .nav-link');
    const link = document.querySelector('#region-main a[href*="admin"], #region-main .wam-adm-groups a, #region-main .admin_category a');
    const target = tab || link;
    if (!target) return { ok: false };
    target.focus();
    const r = target.getBoundingClientRect();
    return {
      ok: document.activeElement === target || target.contains(document.activeElement),
      label: (target.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 40),
      inView: r.top < innerHeight && r.bottom > 0 && r.left < innerWidth && r.right > 0,
    };
  });

  // Open Daha fazla if present and visible
  let more = null;
  const moreBtn = page.locator('.secondary-navigation .dropdownmoremenu:not(.d-none) > .dropdown-toggle').first();
  if (await moreBtn.count() && await moreBtn.isVisible().catch(() => false)) {
    await moreBtn.click({ timeout: 3000 }).catch(() => null);
    await page.waitForTimeout(250);
    more = await page.evaluate(() => {
      const menu = document.querySelector('.secondary-navigation .dropdownmoremenu .dropdown-menu.show, .dropdown-menu.show');
      if (!menu) return { open: false };
      const r = menu.getBoundingClientRect();
      return {
        open: r.height > 0,
        clippedX: r.right > innerWidth + 2 || r.left < -2,
        top: Math.round(r.top),
        h: Math.round(r.height),
      };
    });
    await page.keyboard.press('Escape').catch(() => null);
  }

  // Real navigation: click a child settings/category link, land, go back
  let navFlow = { clicked: false };
  const child = page.locator('#region-main a[href*="/admin/"]').filter({ hasText: screen.linkText }).first();
  if (!(await child.count())) {
    // fallback first admin link in main
    const any = page.locator('#region-main a[href*="/admin/settings"], #region-main a[href*="/admin/category"], #region-main a[href*="/admin/tool"]').first();
    if (await any.count()) {
      const href = await any.getAttribute('href');
      const text = (await any.innerText()).trim().slice(0, 40);
      await any.click();
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(400);
      const after = await probe(page);
      navFlow = { clicked: true, text, href, url: page.url(), pageOv: after.pageOv };
      await page.goto(`${BASE}${screen.path}`, { waitUntil: 'domcontentloaded' });
    }
  } else {
    const href = await child.getAttribute('href');
    const text = (await child.innerText()).trim().slice(0, 40);
    await child.click();
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(400);
    const after = await probe(page);
    navFlow = { clicked: true, text, href, url: page.url(), pageOv: after.pageOv };
    await page.goto(`${BASE}${screen.path}`, { waitUntil: 'domcontentloaded' });
  }

  // Admin search on ADM-PLUGINS only at 390 (ADM-SEARCH; RF11 kategori akışına bağlı)
  let search = null;
  if (screen.id === 'ADM-PLUGINS' && vp.l === 'phone390') {
    try {
      await page.goto(`${BASE}/admin/search.php`, { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(400);
      const q = page.locator('#adminsearchquery, form.adminsearchform input[name="query"], #region-main input[name="query"]').first();
      if (await q.count() && await q.isVisible().catch(() => false)) {
        await q.fill('plugin');
        const submit = page.locator('form.adminsearchform button[type=submit], form.adminsearchform input[type=submit], #region-main button[type=submit]').first();
        if (await submit.count()) await submit.click();
        else await page.keyboard.press('Enter');
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(500);
        const after = await probe(page);
        search = {
          ok: true,
          pageOv: after.pageOv,
          url: page.url(),
          hasResults: await page.locator('#region-main a[href*="admin"]').count(),
        };
      } else {
        search = { ok: false, reason: 'no-visible-admin-search-input' };
      }
    } catch (e) {
      search = { ok: false, reason: e.message.slice(0, 120) };
    }
  }

  return {
    id: screen.id,
    vp: vp.l,
    w: vp.w,
    h: vp.h,
    status: 'ok',
    overflowXPx: metrics.overflowXPx,
    table,
    focus,
    more,
    navFlow,
    search,
  };
}

async function runEngine(engineName) {
  const engine = engineName === 'webkit' ? webkit : chromium;
  const browser = await engine.launch({ headless: true });
  const results = { engine: engineName, flows: [], regression: [] };

  const ctx = await browser.newContext();
  const page = await ctx.newPage();
  await login(page, 'admin');

  for (const screen of RF11) {
    for (const vp of VPS) {
      const row = await flowOne(page, screen, vp);
      results.flows.push(row);
      process.stdout.write((row.overflowXPx || 0) > 2 || (row.table?.pageOv || 0) > 2 ? 'X' : '.');
    }
  }
  console.log(`\n${engineName} flows done`);

  await logout(page);
  await ctx.close();

  // Regression with roles
  for (const role of ['admin', 'teacher']) {
    const c2 = await browser.newContext();
    const p2 = await c2.newPage();
    await login(p2, role);
    for (const screen of REGRESSION.filter((s) => s.role === role)) {
      for (const vp of [{ w: 390, h: 844, l: 'phone390' }, { w: 820, h: 1180, l: 'ipadPort' }]) {
        await p2.setViewportSize({ width: vp.w, height: vp.h });
        await p2.goto(`${BASE}${screen.path}`, { waitUntil: 'domcontentloaded', timeout: 45000 });
        await p2.waitForTimeout(400);
        const ready = await pageReady(p2);
        if (!ready.ok) {
          results.regression.push({ id: screen.id, role, vp: vp.l, status: 'not-ready' });
          continue;
        }
        const m = await measurePage(p2);
        const t = await probe(p2);
        results.regression.push({
          id: screen.id, role, vp: vp.l, status: 'ok',
          overflowXPx: m.overflowXPx, pageOv: t.pageOv, headerClipped: t.headerClipped, ih: t.ih,
        });
        process.stdout.write((m.overflowXPx || 0) > 2 ? 'X' : '.');
      }
    }
    await logout(p2);
    await c2.close();
    console.log(`\n${engineName} regression ${role} done`);
  }

  await browser.close();
  return results;
}

async function main() {
  if (!PASS) throw new Error('WAM_AUDIT_PASS gerekli');
  const chromiumRes = await runEngine('chromium');
  const webkitRes = await runEngine('webkit');
  const out = { chromium: chromiumRes, webkit: webkitRes };
  fs.writeFileSync(OUT, JSON.stringify(out, null, 2));
  console.log('Wrote', OUT);

  const bad = [];
  for (const eng of [chromiumRes, webkitRes]) {
    for (const r of eng.flows) {
      if (r.status !== 'ok' || (r.overflowXPx || 0) > 2 || (r.table?.pageOv || 0) > 2) bad.push(['flow', eng.engine, r]);
      if (r.navFlow && r.navFlow.clicked && (r.navFlow.pageOv || 0) > 2) bad.push(['nav', eng.engine, r]);
      if (r.more?.clippedX) bad.push(['more', eng.engine, r]);
    }
    for (const r of eng.regression) {
      if (r.status !== 'ok' || (r.overflowXPx || 0) > 2 || (r.pageOv || 0) > 2 || r.headerClipped) bad.push(['reg', eng.engine, r]);
    }
  }
  console.log('BAD', bad.length ? JSON.stringify(bad.slice(0, 8), null, 2) : 'none');
}

main().catch((e) => { console.error(e); process.exit(1); });

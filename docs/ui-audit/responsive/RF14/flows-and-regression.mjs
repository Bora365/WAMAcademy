/**
 * RF14: gezinme/odak + RF10/RF13/COURSE-EDIT regresyon
 */
import { chromium, webkit } from '../olcum/node_modules/playwright/index.mjs';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { BASE, PASS, login, logout, pageReady, measurePage } from '../olcum/lib.mjs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const OUT = path.join(__dirname, 'flows-regression.json');

const VPS = [
  { w: 390, h: 844, l: 'phone390' },
  { w: 820, h: 1180, l: 'ipadPort' },
  { w: 1180, h: 820, l: 'ipadLand' },
  { w: 1440, h: 900, l: 'desk1440' },
];

const REGRESSION = [
  { id: 'ADM-APPEARANCE', path: '/admin/category.php?category=appearance', role: 'admin' },
  { id: 'ADM-UPLOADCOURSE', path: '/admin/tool/uploadcourse/index.php', role: 'admin' },
  { id: 'COURSE-EDIT', path: '/course/edit.php?id=2', role: 'teacher' },
  { id: 'COURSE-PARTICIPANTS', path: '/user/index.php?id=2', role: 'teacher' },
];

async function probe(page) {
  return page.evaluate(() => {
    const pageOv = Math.max(0, document.documentElement.scrollWidth - innerWidth);
    const header = document.querySelector('.navbar.fixed-top, header.navbar');
    const hr = header?.getBoundingClientRect();
    return {
      pageOv,
      ih: innerHeight,
      scrollH: document.documentElement.scrollHeight,
      headerClipped: hr ? hr.right > innerWidth + 2 : null,
      mainText: (document.querySelector('#region-main')?.innerText || '').trim().slice(0, 120),
    };
  });
}

async function runEngine(name) {
  const engine = name === 'webkit' ? webkit : chromium;
  const browser = await engine.launch({ headless: true });
  const out = { engine: name, flows: [], pptx: null, regression: [] };

  // COURSE-COMPLETION flows (teacher)
  {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await login(page, 'teacher');
    for (const vp of VPS) {
      await page.setViewportSize({ width: vp.w, height: vp.h });
      await page.goto(`${BASE}/course/completion.php?id=2`, { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(400);
      const ready = await pageReady(page);
      const metrics = ready.ok ? await measurePage(page) : null;
      const table = ready.ok ? await probe(page) : null;
      const focus = ready.ok ? await page.evaluate(() => {
        const el = document.querySelector('#id_overall_aggregation, #region-main select, #region-main input:not([type=hidden])');
        if (!el) return { ok: false };
        el.focus();
        const r = el.getBoundingClientRect();
        return { ok: true, id: el.id, inView: r.top < innerHeight && r.left < innerWidth, clipped: r.right > innerWidth + 1 };
      }) : null;
      // Expand a collapsed fieldset (no save)
      let expand = null;
      const tog = page.locator('#region-main .ftoggler .icons-collapse-expand, #region-main a[data-toggle=collapse]').first();
      if (await tog.count()) {
        await tog.click().catch(() => null);
        await page.waitForTimeout(200);
        expand = { clicked: true, pageOv: (await probe(page)).pageOv };
      }
      out.flows.push({
        id: 'COURSE-COMPLETION', role: 'teacher', vp: vp.l, w: vp.w, h: vp.h,
        status: ready.ok ? 'ok' : 'not-ready',
        overflowXPx: metrics?.overflowXPx, table, focus, expand,
      });
      process.stdout.write((metrics?.overflowXPx || 0) > 2 ? 'X' : '.');
    }
    await logout(page);
    await ctx.close();
    console.log(`\n${name} completion done`);
  }

  // ADM-UNSUPPORTED (admin)
  {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await login(page, 'admin');
    for (const vp of VPS) {
      await page.setViewportSize({ width: vp.w, height: vp.h });
      await page.goto(`${BASE}/admin/category.php?category=unsupported`, { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(400);
      const ready = await pageReady(page);
      const metrics = ready.ok ? await measurePage(page) : null;
      const table = ready.ok ? await probe(page) : null;
      const focus = ready.ok ? await page.evaluate(() => {
        const tab = document.querySelector('.secondary-navigation .nav-link, #region-main a');
        if (!tab) return { ok: false, reason: 'no-focus-target' };
        tab.focus();
        return { ok: true, label: (tab.textContent || '').trim().slice(0, 40) };
      }) : null;
      // Navigate away via secondary if possible
      let nav = null;
      const link = page.locator('.secondary-navigation .nav-link:not(.active)').first();
      if (await link.count() && await link.isVisible().catch(() => false)) {
        const text = (await link.innerText()).trim().slice(0, 30);
        await link.click();
        await page.waitForLoadState('domcontentloaded');
        nav = { clicked: true, text, pageOv: (await probe(page)).pageOv, url: page.url() };
        await page.goto(`${BASE}/admin/category.php?category=unsupported`, { waitUntil: 'domcontentloaded' });
      }
      out.flows.push({
        id: 'ADM-UNSUPPORTED', role: 'admin', vp: vp.l, w: vp.w, h: vp.h,
        status: ready.ok ? 'ok' : 'not-ready',
        overflowXPx: metrics?.overflowXPx, table, focus, nav,
      });
      process.stdout.write((metrics?.overflowXPx || 0) > 2 ? 'X' : '.');
    }

    // PPTX probe once
    await page.setViewportSize({ width: 390, height: 844 });
    const resp = await page.goto(`${BASE}/local/wamakademi/pptx_view.php?id=7`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(400);
    const ct = resp?.headers()?.['content-type'] || '';
    const body = await page.evaluate(() => (document.body?.innerText || '').slice(0, 160));
    out.pptx = {
      status: /application\/pdf/i.test(ct) ? 'ok-pdf' : 'data-or-env-blocked',
      contentType: ct,
      body: body.replace(/\s+/g, ' '),
      pageOv: await page.evaluate(() => Math.max(0, document.documentElement.scrollWidth - innerWidth)),
      note: 'No .pptx on course resources; LibreOffice not installed',
    };
    await logout(page);
    await ctx.close();
    console.log(`\n${name} unsupported+pptx done`);
  }

  for (const role of ['admin', 'teacher']) {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await login(page, role);
    for (const screen of REGRESSION.filter((s) => s.role === role)) {
      for (const vp of [{ w: 390, h: 844, l: 'phone390' }, { w: 820, h: 1180, l: 'ipadPort' }]) {
        await page.setViewportSize({ width: vp.w, height: vp.h });
        await page.goto(`${BASE}${screen.path}`, { waitUntil: 'domcontentloaded' });
        await page.waitForTimeout(350);
        const ready = await pageReady(page);
        if (!ready.ok) {
          out.regression.push({ id: screen.id, role, vp: vp.l, status: 'not-ready' });
          continue;
        }
        const m = await measurePage(page);
        const t = await probe(page);
        out.regression.push({
          id: screen.id, role, vp: vp.l, status: 'ok',
          overflowXPx: m.overflowXPx, pageOv: t.pageOv, headerClipped: t.headerClipped,
        });
        process.stdout.write((m.overflowXPx || 0) > 2 ? 'X' : '.');
      }
    }
    await logout(page);
    await ctx.close();
    console.log(`\n${name} regression ${role} done`);
  }

  await browser.close();
  return out;
}

async function main() {
  if (!PASS) throw new Error('WAM_AUDIT_PASS gerekli');
  const payload = { chromium: await runEngine('chromium'), webkit: await runEngine('webkit') };
  fs.writeFileSync(OUT, JSON.stringify(payload, null, 2));
  console.log('Wrote', OUT);
  const bad = [];
  for (const eng of [payload.chromium, payload.webkit]) {
    for (const r of eng.flows) {
      if (r.status !== 'ok' || (r.overflowXPx || 0) > 2 || (r.table?.pageOv || 0) > 2) bad.push(['flow', eng.engine, r]);
    }
    for (const r of eng.regression) {
      if (r.status !== 'ok' || (r.overflowXPx || 0) > 2 || r.headerClipped) bad.push(['reg', eng.engine, r]);
    }
  }
  console.log('BAD', bad.length ? bad : 'none');
  console.log('PPTX', payload.chromium.pptx?.status, payload.webkit.pptx?.status);
}

main().catch((e) => { console.error(e); process.exit(1); });

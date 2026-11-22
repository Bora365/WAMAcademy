/**
 * RF13: form/odak/gezinme + RF10/RF11/PARTICIPANTS/COURSE-EDIT regresyon
 */
import { chromium, webkit } from '../olcum/node_modules/playwright/index.mjs';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { BASE, PASS, login, logout, pageReady, measurePage } from '../olcum/lib.mjs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const OUT = path.join(__dirname, 'flows-regression.json');

const RF13 = [
  { id: 'ADM-USER-NEW', path: '/user/editadvanced.php?id=-1' },
  { id: 'ADM-UPLOADUSER', path: '/admin/tool/uploaduser/index.php' },
  { id: 'ADM-UPLOADCOURSE', path: '/admin/tool/uploadcourse/index.php' },
  { id: 'ADM-REPORTBUILDER', path: '/reportbuilder/index.php' },
];

const REGRESSION = [
  { id: 'ADM-APPEARANCE', path: '/admin/category.php?category=appearance', role: 'admin' },
  { id: 'ADM-PLUGINS', path: '/admin/category.php?category=modules', role: 'admin' },
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
    const header = document.querySelector('.navbar.fixed-top, header.navbar');
    const hr = header?.getBoundingClientRect();
    return {
      pageOv,
      ih: innerHeight,
      scrollH: document.documentElement.scrollHeight,
      headerClipped: hr ? hr.right > innerWidth + 2 : null,
    };
  });
}

async function flow(page, screen, vp) {
  await page.setViewportSize({ width: vp.w, height: vp.h });
  await page.goto(`${BASE}${screen.path}`, { waitUntil: 'domcontentloaded', timeout: 45000 });
  await page.waitForTimeout(500);
  const ready = await pageReady(page);
  if (!ready.ok) return { id: screen.id, vp: vp.l, status: 'not-ready' };
  const metrics = await measurePage(page);
  const table = await probe(page);

  const focus = await page.evaluate(() => {
    const field = document.querySelector(
      '#region-main input:not([type=hidden]):not([disabled]), #region-main select:not([disabled]), #region-main textarea:not([disabled]), #region-main a.form-control, #region-main .btn-primary'
    );
    if (!field) return { ok: false };
    field.focus();
    const r = field.getBoundingClientRect();
    return {
      ok: true,
      tag: field.tagName,
      id: field.id || '',
      label: (field.getAttribute('aria-label') || field.textContent || field.name || '').trim().replace(/\s+/g, ' ').slice(0, 40),
      inView: r.top < innerHeight && r.bottom > 0 && r.left < innerWidth && r.right > 0,
      clipped: r.right > innerWidth + 1,
    };
  });

  // Screen-specific light interaction (no destructive save)
  let action = { kind: 'none' };
  try {
    if (screen.id === 'ADM-USER-NEW') {
      const name = page.locator('#id_firstname, input[name=firstname]').first();
      if (await name.count()) {
        await name.fill('RF13');
        action = { kind: 'firstname-fill', ok: true };
        await name.fill(''); // clear without save
      }
    } else if (screen.id === 'ADM-UPLOADUSER' || screen.id === 'ADM-UPLOADCOURSE') {
      const choose = page.locator('#region-main .fp-btn-choose, #region-main .filepicker-filelist a, #region-main .dndupload-arrow').first();
      if (await choose.count()) {
        await choose.click({ force: true }).catch(() => null);
        await page.waitForTimeout(400);
        const dlg = await page.locator('.moodle-dialogue-base[aria-hidden="false"], .file-picker').count();
        await page.keyboard.press('Escape').catch(() => null);
        await page.evaluate(() => {
          document.querySelectorAll('.moodle-dialogue-base').forEach((el) => {
            el.setAttribute('aria-hidden', 'true');
            el.style.display = 'none';
          });
        });
        action = { kind: 'filepicker-open', dlgVisible: dlg > 0 };
      } else {
        action = { kind: 'filepicker-missing' };
      }
    } else if (screen.id === 'ADM-REPORTBUILDER') {
      const newBtn = page.locator('#region-main a[href*="edit.php"], #region-main button, #region-main .btn').filter({ hasText: /Yeni|New|Oluştur|Create|Custom/i }).first();
      if (await newBtn.count()) {
        const href = await newBtn.getAttribute('href');
        if (href) {
          await page.goto(new URL(href, BASE).toString(), { waitUntil: 'domcontentloaded' });
          await page.waitForTimeout(400);
          const after = await probe(page);
          action = { kind: 'open-new-report', pageOv: after.pageOv, url: page.url() };
          await page.goto(`${BASE}${screen.path}`, { waitUntil: 'domcontentloaded' });
        } else {
          await newBtn.click({ timeout: 3000 }).catch(() => null);
          action = { kind: 'click-new', url: page.url() };
        }
      } else {
        // filter/search on list
        const q = page.locator('#region-main input[type=search], #region-main input[name*=search]').first();
        if (await q.count() && await q.isVisible().catch(() => false)) {
          await q.fill('user');
          action = { kind: 'list-filter', ok: true };
        } else {
          action = { kind: 'list-only', links: await page.locator('#region-main a').count() };
        }
      }
    }
  } catch (e) {
    action = { kind: 'error', error: e.message.slice(0, 100) };
  }

  return {
    id: screen.id, vp: vp.l, w: vp.w, h: vp.h, status: 'ok',
    overflowXPx: metrics.overflowXPx, table, focus, action,
  };
}

async function runEngine(name) {
  const engine = name === 'webkit' ? webkit : chromium;
  const browser = await engine.launch({ headless: true });
  const out = { engine: name, flows: [], regression: [] };

  const ctx = await browser.newContext();
  const page = await ctx.newPage();
  await login(page, 'admin');
  for (const screen of RF13) {
    for (const vp of VPS) {
      const row = await flow(page, screen, vp);
      out.flows.push(row);
      process.stdout.write((row.overflowXPx || 0) > 2 || (row.table?.pageOv || 0) > 2 ? 'X' : '.');
    }
  }
  console.log(`\n${name} flows done`);
  await logout(page);
  await ctx.close();

  for (const role of ['admin', 'teacher']) {
    const c = await browser.newContext();
    const p = await c.newPage();
    await login(p, role);
    for (const screen of REGRESSION.filter((s) => s.role === role)) {
      for (const vp of [{ w: 390, h: 844, l: 'phone390' }, { w: 820, h: 1180, l: 'ipadPort' }]) {
        await p.setViewportSize({ width: vp.w, height: vp.h });
        await p.goto(`${BASE}${screen.path}`, { waitUntil: 'domcontentloaded', timeout: 45000 });
        await p.waitForTimeout(400);
        const ready = await pageReady(p);
        if (!ready.ok) {
          out.regression.push({ id: screen.id, role, vp: vp.l, status: 'not-ready' });
          continue;
        }
        const m = await measurePage(p);
        const t = await probe(p);
        out.regression.push({
          id: screen.id, role, vp: vp.l, status: 'ok',
          overflowXPx: m.overflowXPx, pageOv: t.pageOv, headerClipped: t.headerClipped,
        });
        process.stdout.write((m.overflowXPx || 0) > 2 ? 'X' : '.');
      }
    }
    await logout(p);
    await c.close();
    console.log(`\n${name} regression ${role} done`);
  }
  await browser.close();
  return out;
}

async function main() {
  if (!PASS) throw new Error('WAM_AUDIT_PASS gerekli');
  // reset teacher pass too
  const chromiumRes = await runEngine('chromium');
  const webkitRes = await runEngine('webkit');
  const payload = { chromium: chromiumRes, webkit: webkitRes };
  fs.writeFileSync(OUT, JSON.stringify(payload, null, 2));
  console.log('Wrote', OUT);
  const bad = [];
  for (const eng of [chromiumRes, webkitRes]) {
    for (const r of eng.flows) {
      if (r.status !== 'ok' || (r.overflowXPx || 0) > 2 || (r.table?.pageOv || 0) > 2) bad.push(['flow', eng.engine, r.id, r.vp, r.overflowXPx]);
      if (r.focus?.clipped) bad.push(['focus-clip', eng.engine, r.id, r.vp]);
    }
    for (const r of eng.regression) {
      if (r.status !== 'ok' || (r.overflowXPx || 0) > 2 || r.headerClipped) bad.push(['reg', eng.engine, r]);
    }
  }
  console.log('BAD', bad.length ? bad : 'none');
}

main().catch((e) => { console.error(e); process.exit(1); });

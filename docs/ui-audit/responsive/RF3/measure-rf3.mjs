/**
 * RF3 önce/sonra ölçüm — COURSE-GRADES, PARTICIPANTS, SITE-PARTICIPANTS, ADM-SYSUSERS
 * PHASE=before|after WAM_AUDIT_PASS=… node measure-rf3.mjs [--engine=chromium|webkit]
 */
import { chromium, webkit } from '../olcum/node_modules/playwright/index.mjs';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import {
  BASE, PASS, VIEWPORTS, login, logout, pageReady, measurePage,
} from '../olcum/lib.mjs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const PHASE = process.env.PHASE || 'before';
const engineName = (process.argv.find((a) => a.startsWith('--engine=')) || '--engine=chromium').split('=')[1];
const OUT_DIR = path.join(__dirname, PHASE);
const JSON_OUT = path.join(__dirname, `measure-${PHASE}-${engineName}.json`);

const SCREENS = [
  { id: 'COURSE-GRADES', path: '/grade/report/grader/index.php?id=2', roles: ['teacher', 'admin'], wrap: '.gradeparent, #grade-report-grader, .reporttable, #region-main' },
  { id: 'COURSE-PARTICIPANTS', path: '/user/index.php?id=2', roles: ['teacher', 'admin'], wrap: '.table-responsive, #participants' },
  { id: 'SITE-PARTICIPANTS', path: '/user/index.php?id=1', roles: ['admin'], wrap: '.table-responsive, #participants' },
  { id: 'ADM-SYSUSERS', path: '/admin/user.php', roles: ['admin'], wrap: '.reportbuilder-table, .table-responsive, [data-region="reportbuilder-table"]' },
];

const VPS = ['phone320', 'phone390', 'phoneLand', 'ipadPort', 'ipadLand', 'desk1440'];

fs.mkdirSync(OUT_DIR, { recursive: true });

async function probeTable(page, wrapSelector) {
  return page.evaluate((sel) => {
    const pageOv = Math.max(0, document.documentElement.scrollWidth - window.innerWidth);
    const wraps = sel.split(',').map((s) => s.trim());
    let wrap = null;
    for (const s of wraps) {
      const el = document.querySelector(s);
      if (el) { wrap = el; break; }
    }
    const table = document.querySelector('#participants, .gradeparent table, .reportbuilder-table, table.generaltable, table');
    const wrapRect = wrap ? wrap.getBoundingClientRect() : null;
    const wrapOv = wrap ? Math.max(0, wrap.scrollWidth - wrap.clientWidth) : null;
    const bodyCls = (document.body.className || '').match(/wam-[^\s]+/g) || [];

    // Find non-fixed elements extending past viewport
    const overflowEls = [];
    for (const el of document.querySelectorAll('body *')) {
      const st = getComputedStyle(el);
      if (st.position === 'fixed' || st.display === 'none') continue;
      const r = el.getBoundingClientRect();
      if (r.width < 2 || r.height < 2) continue;
      if (r.right > window.innerWidth + 2) {
        overflowEls.push({
          cls: (el.className || '').toString().slice(0, 70),
          tag: el.tagName,
          right: Math.round(r.right),
          minW: st.minWidth,
          w: Math.round(r.width),
        });
        if (overflowEls.length >= 8) break;
      }
    }

    const actions = document.querySelector('.wam-row-actions__trigger, .action-menu a.dropdown-toggle, td .dropdown-toggle');
    const editSwitch = document.querySelector('.editmode-switch-form, [data-action="toggle-edit"], .tertiary-navigation');

    return {
      pageOv,
      wrapFound: !!wrap,
      wrapTag: wrap ? wrap.tagName + '.' + (wrap.className || '').toString().slice(0, 40) : null,
      wrapClientW: wrap ? wrap.clientWidth : null,
      wrapScrollW: wrap ? wrap.scrollWidth : null,
      wrapOv,
      tableW: table ? Math.round(table.getBoundingClientRect().width) : null,
      bodyCls,
      overflowEls,
      hasActionTrigger: !!actions,
      hasTertiaryOrEdit: !!editSwitch,
      gradeparent: !!document.querySelector('.gradeparent'),
    };
  }, wrapSelector);
}

async function openRowMenu(page) {
  const trigger = page.locator('.wam-row-actions__trigger, .action-menu a.dropdown-toggle, td.c4 .dropdown-toggle, .action-menu .dropdown-toggle').last();
  if (await trigger.count()) {
    await trigger.click({ timeout: 3000 }).catch(() => {});
    await page.waitForTimeout(300);
    return page.evaluate(() => {
      const menu = document.querySelector('.wam-row-actions__menu:not([hidden]), .dropdown-menu.show, .action-menu .dropdown-menu.show');
      if (!menu) return { open: false };
      const r = menu.getBoundingClientRect();
      return {
        open: true,
        left: Math.round(r.left),
        right: Math.round(r.right),
        top: Math.round(r.top),
        bottom: Math.round(r.bottom),
        clippedRight: r.right > window.innerWidth + 1,
        clippedBottom: r.bottom > window.innerHeight + 1,
        clippedLeft: r.left < -1,
      };
    });
  }
  return { open: false, reason: 'no-trigger' };
}

async function run() {
  if (!PASS) throw new Error('WAM_AUDIT_PASS gerekli');
  const engine = engineName === 'webkit' ? webkit : chromium;
  const browser = await engine.launch({ headless: true });
  const results = [];

  for (const role of ['teacher', 'admin']) {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await login(page, role);

    for (const screen of SCREENS.filter((s) => s.roles.includes(role))) {
      for (const vk of VPS) {
        const vp = VIEWPORTS[vk];
        await page.setViewportSize({ width: vp.width, height: vp.height });
        await page.goto(`${BASE}${screen.path}`, { waitUntil: 'domcontentloaded', timeout: 45000 });
        await page.waitForTimeout(700);
        const ready = await pageReady(page);
        if (!ready.ok) {
          results.push({ id: screen.id, role, viewport: vp.label, status: ready.isLogin ? 'redirect-login' : 'not-ready', url: ready.url });
          continue;
        }
        const metrics = await measurePage(page);
        const table = await probeTable(page, screen.wrap);
        let menu = null;
        if (['COURSE-PARTICIPANTS', 'SITE-PARTICIPANTS', 'ADM-SYSUSERS'].includes(screen.id) && vp.width <= 820) {
          // scroll table to end first
          await page.evaluate(() => {
            const wrap = document.querySelector('.table-responsive');
            if (wrap) wrap.scrollLeft = wrap.scrollWidth;
          });
          menu = await openRowMenu(page);
          await page.keyboard.press('Escape').catch(() => {});
        }
        const name = `${screen.id}_${role}_${vp.label}`;
        await page.screenshot({ path: path.join(OUT_DIR, `${name}.png`), fullPage: false });
        // Mask: we don't OCR; report notes personal data in screenshots
        results.push({
          id: screen.id, role, viewport: vp.label, w: vp.width, h: vp.height,
          status: 'ok', url: ready.url, metrics, table, menu, engine: engineName, phase: PHASE,
          screenshot: `${name}.png`,
        });
        process.stdout.write('.');
      }
    }
    await logout(page);
    await ctx.close();
    console.log(`\n${role} ${PHASE} done`);
  }

  // student must not access ADM-SYSUSERS (Moodle hata sayfası aynı URL'de kalabilir)
  {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await login(page, 'student');
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto(`${BASE}/admin/user.php`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(500);
    const denied = await page.evaluate(() => {
      const t = (document.title || '') + ' ' + (document.querySelector('#region-main')?.innerText || '');
      return /erişim reddedildi|access denied|capability/i.test(t) || !document.querySelector('table.reportbuilder-table, .reportbuilder-table, table.generaltable');
    });
    results.push({
      id: 'ADM-SYSUSERS', role: 'student', viewport: 'phone390',
      status: denied ? 'denied-ok' : 'unexpected-access',
      url: page.url(), phase: PHASE, engine: engineName,
    });
    await logout(page);
    await ctx.close();
  }

  // Filtre açıkken 390→820 (ADM-SYSUSERS); tablo kaydırılmışken son satır menüsü
  {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await login(page, 'admin');
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto(`${BASE}/admin/user.php`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(600);
    const filterBtn = page.locator('[data-filter-action="toggle"], button:has-text("Filtre"), .btn-filters, [data-toggle="collapse"]').first();
    if (await filterBtn.count()) await filterBtn.click({ timeout: 2000 }).catch(() => {});
    await page.waitForTimeout(300);
    await page.setViewportSize({ width: 820, height: 1180 });
    await page.waitForTimeout(400);
    const m = await measurePage(page);
    results.push({
      id: 'ADM-SYSUSERS', role: 'admin', viewport: 'resize-filter-390to820',
      status: 'ok', metrics: m, phase: PHASE, engine: engineName,
    });
    // short height landscape
    await page.setViewportSize({ width: 844, height: 390 });
    await page.goto(`${BASE}/user/index.php?id=2`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(500);
    await page.evaluate(() => {
      const wrap = document.querySelector('.table-responsive');
      if (wrap) { wrap.scrollLeft = wrap.scrollWidth; wrap.scrollTop = wrap.scrollHeight; }
    });
    const menu = await openRowMenu(page);
    results.push({
      id: 'COURSE-PARTICIPANTS', role: 'admin', viewport: 'phoneLand',
      state: 'scrolled-last-menu', status: 'ok', menu,
      metrics: await measurePage(page), phase: PHASE, engine: engineName,
    });
    await logout(page);
    await ctx.close();
  }

  fs.writeFileSync(JSON_OUT, JSON.stringify({ phase: PHASE, engine: engineName, count: results.length, results }, null, 2));
  console.log(`Wrote ${JSON_OUT}`);
  await browser.close();
}

run().catch((e) => { console.error(e); process.exit(1); });

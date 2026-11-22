/**
 * RF7: ileti/takvim/contentbank select + rozet listesi + regresyon
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
  { id: 'ACC-NOTIF-PREFS', path: '/message/notificationpreferences.php', role: 'student' },
  { id: 'ACC-PREFS', path: '/user/preferences.php', role: 'student' },
  { id: 'ACC-LANGUAGE', path: '/user/language.php', role: 'student' },
  { id: 'HOME-WAM', path: '/local/wamakademi/index.php', role: 'student' },
];

async function probe(page) {
  return page.evaluate(() => ({
    pageOv: Math.max(0, document.documentElement.scrollWidth - innerWidth),
    hasSelect: !!document.querySelector('#region-main select, form.mform select'),
    hasBadges: !!document.querySelector('.badge-item, .badge-list, #region-main .badge, [data-region="mybadges"]'),
  }));
}

async function saveVisible(page) {
  const loc = page.locator('#id_submitbutton, form.mform input[name=submitbutton], #region-main input[type=submit]').first();
  if (!(await loc.count())) return { ok: false, reason: 'no-save' };
  await loc.scrollIntoViewIfNeeded().catch(() => null);
  await page.waitForTimeout(120);
  return page.evaluate(() => {
    const save = document.querySelector('#id_submitbutton, form.mform input[name=submitbutton], #region-main input[type=submit]');
    const r = save?.getBoundingClientRect();
    return {
      ok: !!(r && r.height > 0 && r.top < innerHeight && r.bottom > 0),
      top: r ? Math.round(r.top) : null,
      pageOv: Math.max(0, document.documentElement.scrollWidth - innerWidth),
    };
  });
}

async function focusSelect(page) {
  return page.evaluate(() => {
    const el = document.querySelector('#region-main select, form.mform select');
    if (!el) return { ok: false };
    el.focus();
    const r = el.getBoundingClientRect();
    return {
      ok: true,
      id: el.id || el.name,
      w: Math.round(r.width),
      sw: el.scrollWidth,
      inView: r.top < innerHeight && r.left < innerWidth && r.width > 0,
      overflow: r.right > innerWidth + 2,
      pageOv: Math.max(0, document.documentElement.scrollWidth - innerWidth),
    };
  });
}

async function runEngine(name) {
  const engine = name === 'webkit' ? webkit : chromium;
  const browser = await engine.launch({ headless: true });
  const out = { engine: name, flows: [], regression: [] };

  {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await login(page, 'student');

    for (const vp of VPS) {
      await page.setViewportSize({ width: vp.w, height: vp.h });

      // MESSAGE-PREFS: Moodle ileti ayarları drawer (radio/toggle; mform yok)
      await page.goto(`${BASE}/message/edit.php?id=3`, { waitUntil: 'domcontentloaded', timeout: 45000 });
      await page.waitForTimeout(700);
      const msgReady = await pageReady(page);
      const msg = await page.evaluate(() => {
        const drawer = document.querySelector('[data-region="right-hand-drawer"], .drawer.drawer-right, .message-app');
        const radio = document.querySelector('input[type=radio]');
        const toggle = document.querySelector('[data-region="view-settings"] input[type=checkbox], .custom-control-input, input[type=checkbox]');
        const target = radio || toggle;
        if (target) {
          target.focus();
          target.scrollIntoView({ block: 'nearest' });
        }
        const r = (target || drawer)?.getBoundingClientRect();
        return {
          hasDrawer: !!drawer,
          hasRadio: !!radio,
          hasToggle: !!toggle,
          inView: r ? r.top < innerHeight && r.left < innerWidth && r.width > 0 : false,
          drawerW: drawer ? Math.round(drawer.getBoundingClientRect().width) : null,
          pageOv: Math.max(0, document.documentElement.scrollWidth - innerWidth),
        };
      });
      out.flows.push({
        id: 'ACC-MESSAGE-PREFS', role: 'student', vp: vp.l, w: vp.w, h: vp.h,
        status: msgReady.ok ? 'ok' : 'not-ready',
        overflowXPx: (await measurePage(page)).overflowXPx,
        table: await probe(page),
        message: msg,
      });

      for (const item of [
        { id: 'ACC-CAL-PREFS', path: '/user/calendar.php' },
        { id: 'ACC-CONTENTBANK', path: '/user/contentbank.php' },
      ]) {
        await page.goto(`${BASE}${item.path}`, { waitUntil: 'domcontentloaded', timeout: 45000 });
        await page.waitForTimeout(400);
        const ready = await pageReady(page);
        const select = await focusSelect(page);
        const save = await saveVisible(page);
        out.flows.push({
          id: item.id, role: 'student', vp: vp.l, w: vp.w, h: vp.h,
          status: ready.ok ? 'ok' : 'not-ready',
          overflowXPx: (await measurePage(page)).overflowXPx,
          table: await probe(page),
          select,
          save,
        });
      }

      // BADGES: liste / boş durum görünür
      await page.goto(`${BASE}/badges/mybadges.php`, { waitUntil: 'domcontentloaded', timeout: 45000 });
      await page.waitForTimeout(500);
      const badgesReady = await pageReady(page);
      const badges = await page.evaluate(() => {
        const list = document.querySelector('#region-main .badge-list, #region-main [data-region="mybadges"], #region-main .badges');
        const item = document.querySelector('.badge-item, .badge-list .badge, #region-main .card, #region-main .badge');
        const empty = document.querySelector('#region-main .alert, #region-main .no-overflow, #region-main p');
        const target = item || list || empty;
        if (target) target.scrollIntoView({ block: 'nearest' });
        const r = target?.getBoundingClientRect();
        return {
          hasList: !!list,
          hasItem: !!item,
          hasEmptyHint: !!empty,
          inView: r ? r.top < innerHeight && r.left < innerWidth : false,
          pageOv: Math.max(0, document.documentElement.scrollWidth - innerWidth),
        };
      });
      out.flows.push({
        id: 'ACC-BADGES', role: 'student', vp: vp.l, w: vp.w, h: vp.h,
        status: badgesReady.ok ? 'ok' : 'not-ready',
        overflowXPx: (await measurePage(page)).overflowXPx,
        table: await probe(page),
        badges,
      });
    }
    await logout(page).catch(() => {});
    await ctx.close();
  }

  {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await login(page, 'admin');
    await page.setViewportSize({ width: 390, height: 844 });
    for (const pathRel of [
      '/message/edit.php',
      '/user/calendar.php',
      '/user/contentbank.php',
      '/badges/mybadges.php',
    ]) {
      await page.goto(`${BASE}${pathRel}`, { waitUntil: 'domcontentloaded', timeout: 45000 });
      await page.waitForTimeout(350);
      const ready = await pageReady(page);
      out.flows.push({
        id: `ADMIN-${pathRel.split('/').pop()}`, role: 'admin', vp: 'phone390', w: 390, h: 844,
        status: ready.ok ? 'ok' : 'not-ready',
        overflowXPx: (await measurePage(page)).overflowXPx,
        table: await probe(page),
      });
    }
    await logout(page).catch(() => {});
    await ctx.close();
  }

  for (const item of REGRESSION) {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await login(page, item.role);
    for (const vp of [{ w: 390, h: 844, l: 'phone390' }, { w: 820, h: 1180, l: 'ipadPort' }]) {
      await page.setViewportSize({ width: vp.w, height: vp.h });
      await page.goto(`${BASE}${item.path}`, { waitUntil: 'domcontentloaded', timeout: 45000 });
      await page.waitForTimeout(350);
      const ready = await pageReady(page);
      out.regression.push({
        id: item.id, role: item.role, vp: vp.l,
        status: ready.ok ? 'ok' : 'not-ready',
        overflowXPx: (await measurePage(page)).overflowXPx,
        table: await probe(page),
      });
    }
    await logout(page).catch(() => {});
    await ctx.close();
  }

  await browser.close();
  return out;
}

async function main() {
  if (!PASS) throw new Error('WAM_AUDIT_PASS gerekli');
  const chromiumOut = await runEngine('chromium');
  const webkitOut = await runEngine('webkit');
  const payload = { chromium: chromiumOut, webkit: webkitOut };
  fs.writeFileSync(OUT, JSON.stringify(payload, null, 2));
  const bad = [];
  for (const eng of ['chromium', 'webkit']) {
    for (const r of [...payload[eng].flows, ...payload[eng].regression]) {
      const ov = r.overflowXPx ?? r.table?.pageOv ?? 0;
      if (r.status !== 'ok' || ov > 2) bad.push({ eng, id: r.id, vp: r.vp, status: r.status, ov });
      if (r.select?.overflow) bad.push({ eng, id: r.id, vp: r.vp, status: 'select-overflow', ov });
    }
  }
  console.log(`Wrote ${OUT}`);
  console.log(bad.length ? `BAD ${bad.length}` : 'BAD none');
  if (bad.length) console.log(JSON.stringify(bad.slice(0, 25), null, 2));
}

main().catch((e) => { console.error(e); process.exit(1); });

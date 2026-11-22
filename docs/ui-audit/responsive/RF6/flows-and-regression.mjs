/**
 * RF6: select odak / Kaydet görünür / bildirim toggle + regresyon
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
  { id: 'ACC-PREFS', path: '/user/preferences.php', role: 'student' },
  { id: 'ACC-PASSWORD', path: '/login/change_password.php', role: 'student' },
  { id: 'ACC-FILES', path: '/user/files.php', role: 'student' },
  { id: 'HOME-WAM', path: '/local/wamakademi/index.php', role: 'student' },
];

async function probe(page) {
  return page.evaluate(() => ({
    pageOv: Math.max(0, document.documentElement.scrollWidth - innerWidth),
    hasSelect: !!document.querySelector('#region-main select, form.mform select'),
    hasTable: !!document.querySelector('.notificationpreferences table, .preference-table, #region-main table'),
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

      for (const item of [
        { id: 'ACC-LANGUAGE', path: '/user/language.php' },
        { id: 'ACC-EDITOR', path: '/user/editor.php' },
        { id: 'ACC-FORUM', path: '/user/forum.php' },
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

      // NOTIF: tablo + ilk toggle odak (kaydetme yok)
      await page.goto(`${BASE}/message/notificationpreferences.php`, { waitUntil: 'domcontentloaded', timeout: 45000 });
      await page.waitForTimeout(600);
      const notifReady = await pageReady(page);
      const notif = await page.evaluate(() => {
        const table = document.querySelector('.notificationpreferences table, .preference-table, #region-main table');
        const toggle = document.querySelector(
          '.notificationpreferences input[type=checkbox], .preference-table input[type=checkbox], [role=switch], .custom-control-input'
        );
        if (toggle) {
          toggle.focus();
          toggle.scrollIntoView({ block: 'nearest' });
        }
        const r = (toggle || table)?.getBoundingClientRect();
        return {
          hasTable: !!table,
          hasToggle: !!toggle,
          tableOv: table ? Math.max(0, table.scrollWidth - table.clientWidth) : null,
          inView: r ? r.top < innerHeight && r.left < innerWidth : false,
          pageOv: Math.max(0, document.documentElement.scrollWidth - innerWidth),
        };
      });
      out.flows.push({
        id: 'ACC-NOTIF-PREFS', role: 'student', vp: vp.l, w: vp.w, h: vp.h,
        status: notifReady.ok ? 'ok' : 'not-ready',
        overflowXPx: (await measurePage(page)).overflowXPx,
        table: await probe(page),
        notif,
      });
    }
    await logout(page).catch(() => {});
    await ctx.close();
  }

  // admin phone390 smoke
  {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await login(page, 'admin');
    await page.setViewportSize({ width: 390, height: 844 });
    for (const pathRel of [
      '/user/language.php',
      '/user/editor.php',
      '/user/forum.php',
      '/message/notificationpreferences.php',
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

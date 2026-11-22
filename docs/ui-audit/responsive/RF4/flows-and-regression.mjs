/**
 * RF4: hub gezinme / form odak / Kaydet görünürlüğü + regresyon
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
  { id: 'COURSE-EDIT', path: '/course/edit.php?id=2', role: 'teacher' },
  { id: 'ADM-USER-NEW', path: '/user/editadvanced.php?id=-1', role: 'admin' },
  { id: 'HOME-WAM', path: '/local/wamakademi/index.php', role: 'student' },
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

async function saveVisible(page) {
  const loc = page.locator('#id_submitbutton, form.mform input[name=submitbutton]').first();
  if (!(await loc.count())) return { ok: false, reason: 'no-save' };
  await loc.scrollIntoViewIfNeeded().catch(() => null);
  await page.waitForTimeout(150);
  return page.evaluate(() => {
    const save = document.querySelector('#id_submitbutton, form.mform input[name=submitbutton]');
    const r = save?.getBoundingClientRect();
    return {
      ok: !!(r && r.height > 0 && r.top < innerHeight && r.bottom > 0),
      top: r ? Math.round(r.top) : null,
      pageOv: Math.max(0, document.documentElement.scrollWidth - innerWidth),
    };
  });
}

async function runEngine(name) {
  const engine = name === 'webkit' ? webkit : chromium;
  const browser = await engine.launch({ headless: true });
  const out = { engine: name, flows: [], regression: [] };

  // student: prefs + edit + password
  {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await login(page, 'student');
    for (const vp of VPS) {
      await page.setViewportSize({ width: vp.w, height: vp.h });

      // PREFS hub: click a preference link and back
      await page.goto(`${BASE}/user/preferences.php`, { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(350);
      const prefsReady = await pageReady(page);
      let prefsNav = null;
      const prefLink = page.locator('#region-main a[href*="editor.php"], #region-main a[href*="change_password"], #region-main a[href*="/user/"]').first();
      if (await prefLink.count()) {
        const text = (await prefLink.innerText()).trim().slice(0, 40);
        const href = await prefLink.getAttribute('href');
        await prefLink.click();
        await page.waitForLoadState('domcontentloaded');
        prefsNav = { text, href, pageOv: (await probe(page)).pageOv, url: page.url() };
        await page.goto(`${BASE}/user/preferences.php`, { waitUntil: 'domcontentloaded' });
      }
      out.flows.push({
        id: 'ACC-PREFS', role: 'student', vp: vp.l, w: vp.w, h: vp.h,
        status: prefsReady.ok ? 'ok' : 'not-ready',
        overflowXPx: (await measurePage(page)).overflowXPx,
        table: await probe(page),
        prefsNav,
      });

      // EDIT
      await page.goto(`${BASE}/user/edit.php?id=3&returnto=profile`, { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(400);
      const editReady = await pageReady(page);
      const focus = await page.evaluate(() => {
        const el = document.querySelector('#id_firstname, #region-main input:not([type=hidden])');
        if (!el) return { ok: false };
        el.focus();
        const r = el.getBoundingClientRect();
        return { ok: true, id: el.id, inView: r.top < innerHeight && r.left < innerWidth };
      });
      // type without save
      const fn = page.locator('#id_firstname').first();
      if (await fn.count()) {
        const cur = await fn.inputValue();
        await fn.fill(cur); // no-op keep
      }
      const save = await saveVisible(page);
      out.flows.push({
        id: 'ACC-EDIT', role: 'student', vp: vp.l, w: vp.w, h: vp.h,
        status: editReady.ok ? 'ok' : 'not-ready',
        overflowXPx: (await measurePage(page)).overflowXPx,
        table: await probe(page),
        focus,
        save,
      });

      // PASSWORD (view only)
      await page.goto(`${BASE}/login/change_password.php`, { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(350);
      const pwReady = await pageReady(page);
      const pwSave = await saveVisible(page);
      const pwFocus = await page.evaluate(() => {
        const el = document.querySelector('input[type=password]');
        if (!el) return { ok: false };
        el.focus();
        return { ok: true, count: document.querySelectorAll('input[type=password]').length };
      });
      out.flows.push({
        id: 'ACC-PASSWORD', role: 'student', vp: vp.l, w: vp.w, h: vp.h,
        status: pwReady.ok ? 'ok' : 'not-ready',
        overflowXPx: (await measurePage(page)).overflowXPx,
        table: await probe(page),
        focus: pwFocus,
        save: pwSave,
      });
      process.stdout.write('.');
    }
    await logout(page);
    await ctx.close();
    console.log(`\n${name} student flows done`);
  }

  // admin EDIT-ADV
  {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await login(page, 'admin');
    for (const vp of VPS) {
      await page.setViewportSize({ width: vp.w, height: vp.h });
      await page.goto(`${BASE}/user/editadvanced.php?id=2`, { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(400);
      const ready = await pageReady(page);
      const save = await saveVisible(page);
      out.flows.push({
        id: 'ACC-EDIT-ADV', role: 'admin', vp: vp.l, w: vp.w, h: vp.h,
        status: ready.ok ? 'ok' : 'not-ready',
        overflowXPx: (await measurePage(page)).overflowXPx,
        table: await probe(page),
        save,
      });
      process.stdout.write('.');
    }
    await logout(page);
    await ctx.close();
    console.log(`\n${name} admin edit-adv done`);
  }

  for (const role of ['student', 'teacher', 'admin']) {
    const screens = REGRESSION.filter((s) => s.role === role);
    if (!screens.length) continue;
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await login(page, role);
    for (const screen of screens) {
      for (const vp of [{ w: 390, h: 844, l: 'phone390' }, { w: 820, h: 1180, l: 'ipadPort' }]) {
        await page.setViewportSize({ width: vp.w, height: vp.h });
        await page.goto(`${BASE}${screen.path}`, { waitUntil: 'domcontentloaded' });
        await page.waitForTimeout(300);
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
      if (r.status !== 'ok' || (r.overflowXPx || 0) > 2 || (r.table?.pageOv || 0) > 2) bad.push(['flow', eng.engine, r.id, r.vp]);
      if (r.save && r.save.ok === false && r.id !== 'ACC-PREFS') bad.push(['save', eng.engine, r.id, r.vp, r.save]);
    }
    for (const r of eng.regression) {
      if (r.status !== 'ok' || (r.overflowXPx || 0) > 2 || r.headerClipped) bad.push(['reg', eng.engine, r]);
    }
  }
  console.log('BAD', bad.length ? JSON.stringify(bad.slice(0, 10), null, 2) : 'none');
}

main().catch((e) => { console.error(e); process.exit(1); });

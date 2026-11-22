/**
 * RF5: profil gezinme / dosya alanı / oturum satırı / not tablosu + regresyon
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
  { id: 'HOME-WAM', path: '/local/wamakademi/index.php', role: 'student' },
  { id: 'COURSE-EDIT', path: '/course/edit.php?id=2', role: 'teacher' },
];

async function probe(page) {
  return page.evaluate(() => {
    const pageOv = Math.max(0, document.documentElement.scrollWidth - innerWidth);
    return {
      pageOv,
      ih: innerHeight,
      scrollH: document.documentElement.scrollHeight,
      hasTable: !!document.querySelector('#region-main table, .generaltable, .wam-acc-sessions-table'),
      hasFilemanager: !!document.querySelector('.filemanager, .fp-navbar, #region-main .file-picker'),
      hasProfile: !!document.querySelector('.userprofile, .profile_tree, #region-main'),
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

      // PROFILE: open edit link if present
      await page.goto(`${BASE}/user/profile.php?id=3`, { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(400);
      const profileReady = await pageReady(page);
      let profileNav = null;
      // TinyMCE edit sayfasına tıklama PHP built-in sunucuyu kilitleyebilir — buton görünürlüğü yeterli (RF4 edit doğrulandı)
      const editLink = page.locator('#region-main a.btn[href*="edit.php"], .userprofile a.btn[href*="edit.php"]').first();
      if (await editLink.count()) {
        const text = (await editLink.innerText().catch(() => 'edit')).trim().slice(0, 40);
        const href = await editLink.getAttribute('href');
        const box = await editLink.boundingBox();
        profileNav = {
          text,
          href,
          inView: !!(box && box.y < vp.h && box.x < vp.w),
          pageOv: (await probe(page)).pageOv,
        };
      } else {
        profileNav = { text: null, inView: false, pageOv: (await probe(page)).pageOv };
      }
      out.flows.push({
        id: 'ACC-PROFILE', role: 'student', vp: vp.l, w: vp.w, h: vp.h,
        status: profileReady.ok ? 'ok' : 'not-ready',
        overflowXPx: (await measurePage(page)).overflowXPx,
        table: await probe(page),
        profileNav,
      });

      // FILES: filemanager visible / add button focus
      await page.goto(`${BASE}/user/files.php`, { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(700);
      const filesReady = await pageReady(page);
      const filesProbe = await page.evaluate(() => {
        const fm = document.querySelector('.filemanager, .fp-navbar, .filemanager-container');
        const add = document.querySelector('.fp-btn-add, .filemanager .btn, .fp-navbar a, .fp-navbar button');
        if (add) add.focus();
        const r = (add || fm)?.getBoundingClientRect();
        return {
          hasFm: !!fm,
          hasAdd: !!add,
          inView: r ? r.top < innerHeight && r.left < innerWidth && r.width > 0 : false,
          pageOv: Math.max(0, document.documentElement.scrollWidth - innerWidth),
        };
      });
      out.flows.push({
        id: 'ACC-FILES', role: 'student', vp: vp.l, w: vp.w, h: vp.h,
        status: filesReady.ok ? 'ok' : 'not-ready',
        overflowXPx: (await measurePage(page)).overflowXPx,
        table: await probe(page),
        files: filesProbe,
      });

      // SESSIONS: see table / first action
      await page.goto(`${BASE}/report/usersessions/user.php`, { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(500);
      const sessReady = await pageReady(page);
      const sessProbe = await page.evaluate(() => {
        const row = document.querySelector('.wam-acc-sessions-table tbody tr, #region-main table tbody tr');
        const btn = document.querySelector('.wam-acc-session-logout, #region-main table a, #region-main table button, #region-main table input[type=submit]');
        if (btn) btn.scrollIntoView({ block: 'nearest' });
        const r = (btn || row)?.getBoundingClientRect();
        return {
          rows: document.querySelectorAll('.wam-acc-sessions-table tbody tr, #region-main table tbody tr').length,
          hasAction: !!btn,
          inView: r ? r.top < innerHeight && r.left < innerWidth : false,
          pageOv: Math.max(0, document.documentElement.scrollWidth - innerWidth),
        };
      });
      out.flows.push({
        id: 'ACC-SESSIONS', role: 'student', vp: vp.l, w: vp.w, h: vp.h,
        status: sessReady.ok ? 'ok' : 'not-ready',
        overflowXPx: (await measurePage(page)).overflowXPx,
        table: await probe(page),
        sessions: sessProbe,
      });

      // GRADES overview
      await page.goto(`${BASE}/grade/report/overview/index.php`, { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(500);
      const gradesReady = await pageReady(page);
      const gradesProbe = await page.evaluate(() => {
        const table = document.querySelector('#overview-grade, .grade-report-overview table, #region-main .generaltable, #region-main table');
        const link = document.querySelector('#region-main table a, #overview-grade a');
        if (link) link.focus();
        const r = (link || table)?.getBoundingClientRect();
        return {
          hasTable: !!table,
          hasLink: !!link,
          inView: r ? r.top < innerHeight && r.left < innerWidth : false,
          pageOv: Math.max(0, document.documentElement.scrollWidth - innerWidth),
        };
      });
      out.flows.push({
        id: 'ACC-GRADES', role: 'student', vp: vp.l, w: vp.w, h: vp.h,
        status: gradesReady.ok ? 'ok' : 'not-ready',
        overflowXPx: (await measurePage(page)).overflowXPx,
        table: await probe(page),
        grades: gradesProbe,
      });
    }
    await logout(page).catch(() => {});
    await ctx.close();
  }

  // admin grades + sessions quick check at phone390
  {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await login(page, 'admin');
    await page.setViewportSize({ width: 390, height: 844 });
    for (const pathRel of ['/report/usersessions/user.php', '/grade/report/overview/index.php', '/user/files.php', '/user/profile.php?id=2']) {
      await page.goto(`${BASE}${pathRel}`, { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(400);
      const ready = await pageReady(page);
      out.flows.push({
        id: `ADMIN-${pathRel.split('?')[0].split('/').pop()}`, role: 'admin', vp: 'phone390', w: 390, h: 844,
        status: ready.ok ? 'ok' : 'not-ready',
        overflowXPx: (await measurePage(page)).overflowXPx,
        table: await probe(page),
        url: page.url(),
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
      await page.goto(`${BASE}${item.path}`, { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(400);
      const ready = await pageReady(page);
      const m = await measurePage(page);
      out.regression.push({
        id: item.id, role: item.role, vp: vp.l, status: ready.ok ? 'ok' : 'not-ready',
        overflowXPx: m.overflowXPx, table: await probe(page),
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
    }
  }
  console.log(`Wrote ${OUT}`);
  console.log(bad.length ? `BAD ${bad.length}` : 'BAD none');
  if (bad.length) console.log(JSON.stringify(bad.slice(0, 20), null, 2));
}

main().catch((e) => { console.error(e); process.exit(1); });

/**
 * RF1 önce/sonra ölçüm — HOME, CATALOG, MYCOURSES, CALENDAR + header 820.
 * Kullanım: PHASE=before|after WAM_AUDIT_PASS=… node measure-rf1.mjs [--engine=chromium|webkit]
 */
import { chromium, webkit } from '../olcum/node_modules/playwright/index.mjs';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import {
  BASE, PASS, VIEWPORTS, login, logout, pageReady, measurePage, openMenu, closeMenu,
} from '../olcum/lib.mjs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const PHASE = process.env.PHASE || 'before';
const engineName = (process.argv.find((a) => a.startsWith('--engine=')) || '--engine=chromium').split('=')[1];
const OUT_DIR = path.join(__dirname, PHASE);
const JSON_OUT = path.join(__dirname, `measure-${PHASE}-${engineName}.json`);

const SCREENS = [
  { id: 'HOME-WAM', path: '/local/wamakademi/index.php', roles: ['student', 'teacher', 'admin'] },
  { id: 'CATALOG', path: '/local/wamakademi/catalog.php', roles: ['student', 'teacher'] },
  { id: 'MYCOURSES', path: '/local/wamakademi/mycourses.php', roles: ['student', 'teacher'] },
  { id: 'CALENDAR-WAM', path: '/local/wamakademi/calendar.php', roles: ['student', 'teacher', 'admin'] },
];

const VPS = ['phone320', 'phone390', 'phoneLand', 'split507', 'ipadPort', 'ipadLand', 'desk1024', 'desk1440'];

fs.mkdirSync(OUT_DIR, { recursive: true });

async function shot(page, name) {
  const file = path.join(OUT_DIR, `${name}.png`);
  await page.screenshot({ path: file, fullPage: false });
  return path.basename(file);
}

async function headerProbe(page) {
  return page.evaluate(() => {
    const nav = document.querySelector('.navbar.wam-navbar');
    const user = document.querySelector('#usernavigation .usermenu .dropdown-toggle, #usernavigation .usermenu button, .usermenu .dropdown-toggle');
    const msg = document.querySelector('#usernavigation .popover-region[data-region="popover-region-messages"] .popover-region-toggle, #usernavigation a[data-region="popover-region-toggle"]');
    const search = document.querySelector('.wam-nav-search input');
    const topNav = document.querySelector('.wam-top-nav');
    const r = (el) => {
      if (!el) return null;
      const b = el.getBoundingClientRect();
      return { left: Math.round(b.left), right: Math.round(b.right), top: Math.round(b.top), w: Math.round(b.width), h: Math.round(b.height) };
    };
    return {
      navOverflow: nav ? Math.max(0, nav.scrollWidth - nav.clientWidth) : null,
      user: r(user),
      msg: r(msg),
      search: r(search),
      topNavVisible: !!(topNav && getComputedStyle(topNav).display !== 'none' && topNav.getBoundingClientRect().width > 0),
      compact: document.body.classList.contains('wam-nav-compact'),
      iw: window.innerWidth,
    };
  });
}

async function calendarProbe(page) {
  return page.evaluate(() => {
    const today = document.querySelector('.wam-calendar-today');
    const nav = document.querySelector('.wam-calendar-navigation');
    const strong = document.querySelector('.wam-calendar-navigation strong');
    const r = (el) => {
      if (!el) return null;
      const b = el.getBoundingClientRect();
      return { left: Math.round(b.left), right: Math.round(b.right), w: Math.round(b.width) };
    };
    return { today: r(today), nav: r(nav), strong: r(strong), todayClipped: today ? today.getBoundingClientRect().right > window.innerWidth + 1 : null };
  });
}

async function run() {
  if (!PASS) throw new Error('WAM_AUDIT_PASS gerekli');
  const engine = engineName === 'webkit' ? webkit : chromium;
  const browser = await engine.launch({ headless: true });
  const results = [];

  for (const role of ['student', 'teacher', 'admin']) {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await login(page, role);

    for (const screen of SCREENS.filter((s) => s.roles.includes(role))) {
      for (const vk of VPS) {
        const vp = VIEWPORTS[vk];
        await page.setViewportSize({ width: vp.width, height: vp.height });
        await page.goto(`${BASE}${screen.path}`, { waitUntil: 'domcontentloaded', timeout: 45000 });
        await page.waitForTimeout(600);
        const ready = await pageReady(page);
        if (!ready.ok) {
          results.push({ id: screen.id, role, viewport: vp.label, status: ready.isLogin ? 'redirect-login' : 'not-ready', url: ready.url });
          continue;
        }
        const metrics = await measurePage(page);
        const header = await headerProbe(page);
        let cal = null;
        if (screen.id === 'CALENDAR-WAM') cal = await calendarProbe(page);
        const name = `${screen.id}_${role}_${vp.label}`;
        const screenshot = await shot(page, name);
        results.push({
          id: screen.id, role, viewport: vp.label, w: vp.width, h: vp.height,
          status: 'ok', url: ready.url, metrics, header, calendar: cal, screenshot, engine: engineName, phase: PHASE,
        });
        process.stdout.write('.');
      }

      // Menu open + resize 390→820
      if (screen.id === 'HOME-WAM') {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto(`${BASE}${screen.path}`, { waitUntil: 'domcontentloaded' });
        await page.waitForTimeout(400);
        await openMenu(page);
        await page.setViewportSize({ width: 820, height: 1180 });
        await page.waitForTimeout(400);
        const m = await measurePage(page);
        results.push({
          id: screen.id, role, viewport: 'resize-menu-390to820', status: 'ok',
          metrics: m, menuOpen: m.menuOpen, engine: engineName, phase: PHASE,
        });
        await closeMenu(page);
      }
    }
    await logout(page);
    await ctx.close();
    console.log(`\n${role} ${PHASE} done`);
  }

  // Catalog filter open state (student)
  {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await login(page, 'student');
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto(`${BASE}/local/wamakademi/catalog.php?q=xyznoresult`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(500);
    const metrics = await measurePage(page);
    await shot(page, 'CATALOG_student_phone390_emptysearch');
    results.push({ id: 'CATALOG', role: 'student', viewport: 'phone390', state: 'empty-search', status: 'ok', metrics, phase: PHASE, engine: engineName });
    await logout(page);
    await ctx.close();
  }

  fs.writeFileSync(JSON_OUT, JSON.stringify({ phase: PHASE, engine: engineName, count: results.length, results }, null, 2));
  console.log(`Wrote ${JSON_OUT}`);
  await browser.close();
}

run().catch((e) => { console.error(e); process.exit(1); });

/**
 * RF12 önce/sonra — ADM-DEVELOPMENT, ADM-COURSE-MGMT, ADM-COURSE-NEW, ADM-CATEGORY-EDIT
 * PHASE=before|after WAM_AUDIT_PASS=… node measure-rf12.mjs [--engine=chromium|webkit]
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
  { id: 'ADM-DEVELOPMENT', path: '/admin/category.php?category=development' },
  { id: 'ADM-COURSE-MGMT', path: '/course/management.php' },
  { id: 'ADM-COURSE-NEW', path: '/course/edit.php?category=1' },
  { id: 'ADM-CATEGORY-EDIT', path: '/course/editcategory.php?id=1' },
];

const VPS = ['phone320', 'phone390', 'phoneLand', 'ipadPort', 'ipadLand', 'desk1440'];

fs.mkdirSync(OUT_DIR, { recursive: true });

async function probeAdmin(page) {
  return page.evaluate(() => {
    const pageOv = Math.max(0, document.documentElement.scrollWidth - window.innerWidth);
    const bodyCls = (document.body.className || '').match(/wam-[^\s]+/g) || [];
    const sec = document.querySelector('.secondary-navigation .navigation');
    const secOv = sec ? Math.max(0, sec.scrollWidth - sec.clientWidth) : null;
    const overflowEls = [];
    for (const el of document.querySelectorAll('body *')) {
      const st = getComputedStyle(el);
      if (st.position === 'fixed' || st.display === 'none' || st.visibility === 'hidden') continue;
      let clipped = false;
      let p = el.parentElement;
      while (p && p !== document.body) {
        const ps = getComputedStyle(p);
        if (/(auto|scroll|hidden)/.test(ps.overflowX)) {
          clipped = true;
          break;
        }
        p = p.parentElement;
      }
      if (clipped) continue;
      const r = el.getBoundingClientRect();
      if (r.width < 2 || r.height < 2) continue;
      if (r.right > window.innerWidth + 2) {
        overflowEls.push({
          tag: el.tagName,
          id: el.id || '',
          cls: (el.className || '').toString().slice(0, 80),
          right: Math.round(r.right),
          w: Math.round(r.width),
        });
        if (overflowEls.length >= 10) break;
      }
    }
    return {
      pageOv,
      bodyCls,
      secOv,
      hasMform: !!document.querySelector('form.mform, #mform1, form[action*="edit"]'),
      hasCourseTree: !!document.querySelector('#course-category-listings, .course-category-listings, [data-region="course-category"]'),
      overflowEls,
    };
  });
}

async function shot(page, name) {
  const file = path.join(OUT_DIR, `${name}.png`);
  await page.screenshot({ path: file, fullPage: false });
  return path.basename(file);
}

async function run() {
  if (!PASS) throw new Error('WAM_AUDIT_PASS gerekli');
  const engine = engineName === 'webkit' ? webkit : chromium;
  const browser = await engine.launch({ headless: true });
  const results = [];
  const context = await browser.newContext();
  const page = await context.newPage();
  await login(page, 'admin');
  for (const screen of SCREENS) {
    for (const vpKey of VPS) {
      const vp = VIEWPORTS[vpKey];
      await page.setViewportSize({ width: vp.width, height: vp.height });
      let status = 'ok';
      let error = null;
      try {
        await page.goto(`${BASE}${screen.path}`, { waitUntil: 'domcontentloaded', timeout: 60000 });
        await page.waitForTimeout(700);
      } catch (e) {
        status = 'nav-error';
        error = String(e.message || e);
      }
      const ready = status === 'ok' ? await pageReady(page) : { ok: false };
      if (status === 'ok' && !ready.ok) {
        status = ready.isLogin ? 'redirect-login' : 'not-ready';
      }
      let metrics = null;
      let probe = null;
      let screenshot = null;
      if (status === 'ok') {
        metrics = await measurePage(page);
        probe = await probeAdmin(page);
        screenshot = await shot(page, `${screen.id}_admin_${vp.label}`);
      }
      results.push({
        id: screen.id, role: 'admin', viewport: vp.label, w: vp.width, h: vp.height,
        status, error, metrics, probe, screenshot, phase: PHASE, engine: engineName,
      });
      console.log(`${PHASE} ${screen.id} ${vp.label} status=${status} pageOv=${probe?.pageOv ?? '-'}`);
    }
  }
  await logout(page).catch(() => {});
  await context.close();
  await browser.close();
  fs.writeFileSync(JSON_OUT, JSON.stringify({ phase: PHASE, engine: engineName, count: results.length, results }, null, 2));
  console.log(`Wrote ${JSON_OUT} (${results.length})`);
}

run().catch((e) => {
  console.error(e);
  process.exit(1);
});

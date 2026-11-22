/**
 * RF2 önce/sonra — COURSE-VIEW, UPLOAD-VIDEO, COURSE-EDIT
 * PHASE=before|after WAM_AUDIT_PASS=… node measure-rf2.mjs [--engine=chromium|webkit]
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
  { id: 'COURSE-VIEW', path: '/course/view.php?id=2', roles: ['student', 'teacher', 'admin'] },
  { id: 'UPLOAD-VIDEO', path: '/local/wamakademi/upload.php?courseid=2&sectionnum=0', roles: ['teacher', 'admin'] },
  { id: 'COURSE-EDIT', path: '/course/edit.php?id=2', roles: ['teacher', 'admin'] },
];

const VPS = engineName === 'webkit'
  ? ['phone390', 'ipadPort']
  : ['phone320', 'phone390', 'phoneLand', 'ipadPort', 'ipadLand', 'desk1440'];

fs.mkdirSync(OUT_DIR, { recursive: true });

async function probe(page) {
  return page.evaluate(() => {
    const pageOv = Math.max(0, document.documentElement.scrollWidth - window.innerWidth);
    const bodyCls = (document.body.className || '').match(/wam-[^\s]+/g) || [];
    const culprits = [];
    for (const el of document.querySelectorAll('#region-main *, .secondary-navigation *, #page-header *, .wam-content-shell *, .wam-videos__row *')) {
      if (el.closest('.drawer, .message-app')) continue;
      const st = getComputedStyle(el);
      if (st.position === 'fixed' || st.display === 'none') continue;
      const r = el.getBoundingClientRect();
      if (r.width < 2 || r.height < 2) continue;
      if (r.right > window.innerWidth + 2) {
        culprits.push({
          tag: el.tagName,
          cls: (el.className || '').toString().slice(0, 70),
          right: Math.round(r.right),
          w: Math.round(r.width),
          minW: st.minWidth,
          text: (el.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 40),
        });
        if (culprits.length >= 8) break;
      }
    }
    const draftBadge = document.querySelector('.wam-status--draft, .wam-videos__badge--draft, .badge');
    const statusBar = document.querySelector('.wam-content-card__title, .wam-videos__header, .wam-videos__row');
    const editMode = document.body.classList.contains('editing');
    const secNav = document.querySelector('.secondary-navigation .navigation');
    return {
      pageOv,
      bodyCls,
      culprits,
      editMode,
      draftRight: draftBadge ? Math.round(draftBadge.getBoundingClientRect().right) : null,
      statusRight: statusBar ? Math.round(statusBar.getBoundingClientRect().right) : null,
      secNavOv: secNav ? Math.max(0, secNav.scrollWidth - secNav.clientWidth) : null,
      secNavOvX: secNav ? getComputedStyle(secNav).overflowX : null,
      hasFileInput: !!document.querySelector('input[type=file]'),
      hasEditor: !!document.querySelector('.tox-tinymce, .felement[data-fieldtype=editor]'),
      saveBtn: !!document.querySelector('#id_submitbutton, input[name=submitbutton], button[type=submit].btn-primary'),
    };
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
          results.push({
            id: screen.id, role, viewport: vp.label,
            status: ready.isLogin ? 'redirect-login' : 'not-ready', url: ready.url, phase: PHASE, engine: engineName,
          });
          continue;
        }
        if (vp.width <= 1024) await openMenu(page).catch(() => {});
        const metrics = await measurePage(page);
        const table = await probe(page);
        if (vp.width <= 1024) await closeMenu(page).catch(() => {});
        const name = `${screen.id}_${role}_${vp.label}${engineName === 'webkit' ? '_webkit' : ''}`;
        await page.screenshot({ path: path.join(OUT_DIR, `${name}.png`), fullPage: false });
        results.push({
          id: screen.id, role, viewport: vp.label, w: vp.width, h: vp.height,
          status: 'ok', url: ready.url, metrics, table, screenshot: `${name}.png`,
          phase: PHASE, engine: engineName,
        });
        process.stdout.write(table.pageOv > 2 ? 'X' : '.');
      }

      // Teacher edit-mode probe on COURSE-VIEW (open only; no save of course structure)
      if (screen.id === 'COURSE-VIEW' && role === 'teacher' && engineName === 'chromium') {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto(`${BASE}${screen.path}`, { waitUntil: 'domcontentloaded' });
        await page.waitForTimeout(400);
        const edit = page.locator('.editmode-switch-form input[type=checkbox], #wam-course-edit-host input[type=checkbox]').first();
        if (await edit.count()) {
          const before = await edit.isChecked().catch(() => false);
          if (!before) {
            await Promise.all([
              page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 15000 }).catch(() => null),
              edit.click({ timeout: 3000 }).catch(() => null),
            ]);
            await page.waitForTimeout(500);
          }
          const metrics = await measurePage(page);
          const table = await probe(page);
          await page.screenshot({ path: path.join(OUT_DIR, `COURSE-VIEW_teacher_phone390_editing.png`), fullPage: false });
          results.push({
            id: 'COURSE-VIEW', role: 'teacher', viewport: 'phone390', state: 'editing',
            status: 'ok', metrics, table, phase: PHASE, engine: engineName,
          });
          // restore
          const edit2 = page.locator('.editmode-switch-form input[type=checkbox]').first();
          if (await edit2.count() && await edit2.isChecked()) {
            await Promise.all([
              page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 15000 }).catch(() => null),
              edit2.click({ timeout: 3000 }).catch(() => null),
            ]);
          }
        }
      }
    }
    await logout(page);
    await ctx.close();
    console.log(`\n${role} ${PHASE} done`);
  }

  fs.writeFileSync(JSON_OUT, JSON.stringify({ phase: PHASE, engine: engineName, count: results.length, results }, null, 2));
  console.log(`Wrote ${JSON_OUT}`);
  await browser.close();
}

run().catch((e) => { console.error(e); process.exit(1); });

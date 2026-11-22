/**
 * RF7 — ACC-MESSAGE-PREFS, ACC-CAL-PREFS, ACC-CONTENTBANK, ACC-BADGES
 * PHASE=before|after WAM_AUDIT_PASS=… node measure-rf7.mjs [--engine=chromium|webkit]
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
  { id: 'ACC-MESSAGE-PREFS', path: '/message/edit.php', roles: ['student', 'teacher', 'admin'] },
  { id: 'ACC-CAL-PREFS', path: '/user/calendar.php', roles: ['student', 'teacher', 'admin'] },
  { id: 'ACC-CONTENTBANK', path: '/user/contentbank.php', roles: ['student', 'teacher', 'admin'] },
  { id: 'ACC-BADGES', path: '/badges/mybadges.php', roles: ['student', 'teacher', 'admin'] },
];

const VPS = engineName === 'webkit'
  ? ['phone390', 'phoneLand', 'ipadPort', 'ipadLand', 'desk1440']
  : ['phone320', 'phone390', 'phoneLand', 'ipadPort', 'ipadLand', 'desk1440'];

fs.mkdirSync(OUT_DIR, { recursive: true });

async function probe(page) {
  return page.evaluate(() => {
    const pageOv = Math.max(0, document.documentElement.scrollWidth - window.innerWidth);
    const culprits = [];
    const roots = '#region-main *, form.mform *, .badge-list *, .badge-item *, table *, select, .custom-select';
    for (const el of document.querySelectorAll(roots)) {
      if (el.closest('.drawer, .message-app')) continue;
      const st = getComputedStyle(el);
      if (st.position === 'fixed' || st.display === 'none') continue;
      let clipped = false;
      let p = el.parentElement;
      while (p && p !== document.body) {
        const ps = getComputedStyle(p);
        if (/(auto|scroll|hidden)/.test(ps.overflowX)) { clipped = true; break; }
        p = p.parentElement;
      }
      if (clipped) continue;
      const r = el.getBoundingClientRect();
      if (r.width < 2 || r.height < 2) continue;
      if (r.right > window.innerWidth + 2) {
        culprits.push({
          tag: el.tagName,
          cls: (el.className || '').toString().slice(0, 70),
          id: el.id || '',
          right: Math.round(r.right),
          w: Math.round(r.width),
          minW: st.minWidth,
          sw: el.scrollWidth,
          text: (el.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 36),
        });
        if (culprits.length >= 12) break;
      }
    }

    const selects = [...document.querySelectorAll('#region-main select, form.mform select')];
    const selectInfo = selects.slice(0, 8).map((s) => {
      const r = s.getBoundingClientRect();
      const st = getComputedStyle(s);
      return {
        id: s.id || s.name,
        w: Math.round(r.width),
        sw: s.scrollWidth,
        maxW: st.maxWidth,
        overflow: r.right > innerWidth + 2,
      };
    });

    const badges = document.querySelectorAll(
      '.badge-item, .badge-list .badge, #region-main .badge, .badges-list li, [data-region="badges"] .card'
    );
    const table = document.querySelector('#region-main table, .generaltable');
    const save = document.querySelector(
      '#id_submitbutton, form.mform input[name=submitbutton], #region-main input[type=submit]'
    );
    const saveR = save?.getBoundingClientRect();

    return {
      pageOv,
      bodyCls: (document.body.className || '').match(/wam-[^\s]+/g) || [],
      culprits,
      ih: innerHeight,
      scrollH: document.documentElement.scrollHeight,
      hasMform: !!document.querySelector('form.mform'),
      selectCount: selects.length,
      selectInfo,
      hasTable: !!table,
      tableOv: table ? Math.max(0, table.scrollWidth - (table.clientWidth || 0)) : null,
      badgeCount: badges.length,
      hasBadgeList: badges.length > 0 || !!document.querySelector('#region-main .badge-list, #region-main [data-region="mybadges"]'),
      saveInView: saveR ? saveR.top < innerHeight && saveR.bottom > 0 && saveR.left < innerWidth : null,
      saveBottom: saveR ? Math.round(saveR.bottom) : null,
    };
  });
}

async function run() {
  if (!PASS) throw new Error('WAM_AUDIT_PASS gerekli');
  const engine = engineName === 'webkit' ? webkit : chromium;
  const browser = await engine.launch({ headless: true });
  const results = [];

  for (const role of ['student', 'teacher', 'admin']) {
    const screens = SCREENS.filter((s) => s.roles.includes(role));
    if (!screens.length) continue;
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await login(page, role);

    for (const screen of screens) {
      for (const vk of VPS) {
        const vp = VIEWPORTS[vk];
        await page.setViewportSize({ width: vp.width, height: vp.height });
        let status = 'ok';
        let error = null;
        try {
          await page.goto(`${BASE}${screen.path}`, { waitUntil: 'domcontentloaded', timeout: 45000 });
          await page.waitForTimeout(700);
        } catch (e) {
          status = 'nav-error';
          error = String(e.message || e).slice(0, 160);
        }
        const ready = status === 'ok' ? await pageReady(page) : { ok: false };
        if (status === 'ok' && !ready.ok) status = ready.isLogin ? 'redirect-login' : 'not-ready';

        let metrics = null;
        let table = null;
        let screenshot = null;
        if (status === 'ok') {
          metrics = await measurePage(page);
          table = await probe(page);
          const name = `${screen.id}_${role}_${vp.label}${engineName === 'webkit' ? '_webkit' : ''}`;
          screenshot = `${name}.png`;
          await page.screenshot({ path: path.join(OUT_DIR, screenshot), fullPage: false });
        }
        const ov = table?.pageOv ?? metrics?.overflowXPx ?? 0;
        results.push({
          id: screen.id, role, viewport: vp.label, w: vp.width, h: vp.height,
          status, error, metrics, table, screenshot, phase: PHASE, engine: engineName,
          url: page.url(),
        });
        process.stdout.write(status === 'ok' ? (ov > 2 ? 'X' : '.') : '!');
        console.log(` ${PHASE} ${screen.id} ${role} ${vp.label} status=${status} pageOv=${ov}`);
      }
    }
    await logout(page).catch(() => {});
    await ctx.close();
  }

  await browser.close();
  fs.writeFileSync(JSON_OUT, JSON.stringify({ phase: PHASE, engine: engineName, count: results.length, results }, null, 2));
  console.log(`Wrote ${JSON_OUT} (${results.length})`);
}

run().catch((e) => { console.error(e); process.exit(1); });

/**
 * RF5 — ACC-PROFILE, ACC-FILES, ACC-SESSIONS, ACC-GRADES
 * PHASE=before|after WAM_AUDIT_PASS=… node measure-rf5.mjs [--engine=chromium|webkit]
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

const KNOWN_IDS = { admin: 2, teacher: 4, student: 3 };

const SCREENS = [
  { id: 'ACC-PROFILE', pathFor: (id) => `/user/profile.php?id=${id}`, roles: ['student', 'teacher', 'admin'] },
  { id: 'ACC-FILES', path: '/user/files.php', roles: ['student', 'teacher', 'admin'] },
  { id: 'ACC-SESSIONS', path: '/report/usersessions/user.php', roles: ['student', 'teacher', 'admin'] },
  { id: 'ACC-GRADES', path: '/grade/report/overview/index.php', roles: ['student', 'teacher', 'admin'] },
];

const VPS = engineName === 'webkit'
  ? ['phone390', 'phoneLand', 'ipadPort', 'ipadLand', 'desk1440']
  : ['phone320', 'phone390', 'phoneLand', 'ipadPort', 'ipadLand', 'desk1440'];

fs.mkdirSync(OUT_DIR, { recursive: true });

async function resolveUid(page, role) {
  if (KNOWN_IDS[role]) return KNOWN_IDS[role];
  const id = await page.evaluate(() => {
    const a = document.querySelector('a[href*="/user/profile.php?id="], a[href*="/user/edit.php?id="]');
    if (!a) return null;
    try { return new URL(a.href).searchParams.get('id'); } catch { return null; }
  });
  return id ? Number(id) : null;
}

async function probe(page) {
  return page.evaluate(() => {
    const pageOv = Math.max(0, document.documentElement.scrollWidth - window.innerWidth);
    const bodyCls = (document.body.className || '').match(/wam-[^\s]+/g) || [];
    const culprits = [];
    const roots = '#region-main *, form.mform *, .filemanager *, .fp-content *, table *, .userprofile *, .profile_tree *';
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
          text: (el.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 36),
        });
        if (culprits.length >= 12) break;
      }
    }

    const table = document.querySelector('#region-main table, .wam-acc-sessions-table, .generaltable');
    const fm = document.querySelector('.filemanager, .fp-navbar, .file-picker');
    const profile = document.querySelector('.userprofile, .profile_tree, #region-main .card');
    const tr = table?.getBoundingClientRect();

    return {
      pageOv,
      bodyCls,
      culprits,
      ih: innerHeight,
      scrollH: document.documentElement.scrollHeight,
      hasTable: !!table,
      tableOv: table ? Math.max(0, table.scrollWidth - (table.clientWidth || 0)) : null,
      tableRight: tr ? Math.round(tr.right) : null,
      hasFilemanager: !!fm,
      hasProfile: !!profile,
      filepickerBtns: document.querySelectorAll('.fp-btn-add, .filemanager-container, .fp-navbar button, .btn').length,
      sessionRows: document.querySelectorAll('.wam-acc-sessions-table tbody tr, #region-main table tbody tr').length,
      gradeRows: document.querySelectorAll('.grade-report-overview table tbody tr, #overview-grade table tbody tr, #region-main .generaltable tbody tr').length,
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
    const uid = await resolveUid(page, role);

    for (const screen of screens) {
      const rel = screen.pathFor ? screen.pathFor(uid) : screen.path;
      for (const vk of VPS) {
        const vp = VIEWPORTS[vk];
        await page.setViewportSize({ width: vp.width, height: vp.height });
        let status = 'ok';
        let error = null;
        try {
          await page.goto(`${BASE}${rel}`, { waitUntil: 'domcontentloaded', timeout: 45000 });
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
          url: page.url(), uid,
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

/**
 * RF14 — COURSE-COMPLETION, PPTX-VIEW, ADM-UNSUPPORTED
 * PHASE=before|after WAM_AUDIT_PASS=… node measure-rf14.mjs [--engine=chromium|webkit]
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
  { id: 'COURSE-COMPLETION', path: '/course/completion.php?id=2', roles: ['teacher', 'admin'] },
  // PPTX requires resource CM with .pptx; probe known visible resource + dedicated path
  { id: 'PPTX-VIEW', path: '/local/wamakademi/pptx_view.php?id=7', roles: ['admin', 'teacher', 'student'] },
  { id: 'ADM-UNSUPPORTED', path: '/admin/category.php?category=unsupported', roles: ['admin'] },
];

const VPS = engineName === 'webkit'
  ? ['phone390', 'phoneLand', 'ipadPort', 'ipadLand', 'desk1440']
  : ['phone320', 'phone390', 'phoneLand', 'ipadPort', 'ipadLand', 'desk1440'];

fs.mkdirSync(OUT_DIR, { recursive: true });

async function probe(page) {
  return page.evaluate(() => {
    const pageOv = Math.max(0, document.documentElement.scrollWidth - window.innerWidth);
    const bodyCls = (document.body.className || '').match(/wam-[^\s]+/g) || [];
    const culprits = [];
    for (const el of document.querySelectorAll('#region-main *, .secondary-navigation *, form.mform *, table *')) {
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
          text: (el.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 40),
        });
        if (culprits.length >= 10) break;
      }
    }
    const err = document.querySelector('.errorcode, .errormessage, #region-main .alert-danger, .notifyproblem');
    return {
      pageOv,
      bodyCls,
      culprits,
      ih: innerHeight,
      scrollH: document.documentElement.scrollHeight,
      contentTypeHint: document.contentType || '',
      isPdf: /application\/pdf/i.test(document.contentType || '') || !!document.querySelector('embed[type*="pdf"], iframe[src*="pdf"]'),
      errorText: err ? (err.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 120) : null,
      title: document.title || '',
      hasMform: !!document.querySelector('form.mform'),
      hasTable: !!document.querySelector('#region-main table'),
      hasCompletion: !!document.querySelector('#region-main .completion-progress, #region-main [data-region*="completion"], form[action*="completion"]'),
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
        let metrics = null;
        let table = null;
        let screenshot = null;
        try {
          const resp = await page.goto(`${BASE}${screen.path}`, { waitUntil: 'domcontentloaded', timeout: 45000 });
          await page.waitForTimeout(600);
          const ct = resp?.headers()?.['content-type'] || '';
          if (screen.id === 'PPTX-VIEW') {
            // PDF binary or Moodle exception HTML
            const ready = await pageReady(page, { allowLogin: false }).catch(() => ({ ok: false }));
            table = await probe(page).catch(() => ({ pageOv: 0, errorText: 'probe-fail' }));
            metrics = await measurePage(page).catch(() => ({ overflowXPx: 0 }));
            if (/application\/pdf/i.test(ct) || table?.isPdf) {
              status = 'ok-pdf';
            } else if (table?.errorText || /exception|bulunamadı|başarısız|error/i.test(table?.title || '') || /PPTX|exception/i.test(await page.content().then((h) => h.slice(0, 2000)))) {
              status = 'data-or-env-blocked';
              error = table?.errorText || ct || 'pptx-unavailable';
            } else if (!ready.ok) {
              status = ready.isLogin ? 'redirect-login' : 'not-ready';
            }
          } else {
            const ready = await pageReady(page);
            if (!ready.ok) status = ready.isLogin ? 'redirect-login' : 'not-ready';
            else {
              metrics = await measurePage(page);
              table = await probe(page);
            }
          }
        } catch (e) {
          status = 'nav-error';
          error = String(e.message || e).slice(0, 200);
        }

        if (status === 'ok' || status === 'ok-pdf' || status === 'data-or-env-blocked') {
          const name = `${screen.id}_${role}_${vp.label}${engineName === 'webkit' ? '_webkit' : ''}`;
          screenshot = `${name}.png`;
          await page.screenshot({ path: path.join(OUT_DIR, screenshot), fullPage: false }).catch(() => null);
        }

        const ov = table?.pageOv ?? metrics?.overflowXPx ?? 0;
        results.push({
          id: screen.id, role, viewport: vp.label, w: vp.width, h: vp.height,
          status, error, metrics, table, screenshot, phase: PHASE, engine: engineName, url: page.url(),
        });
        process.stdout.write(status.startsWith('ok') ? (ov > 2 ? 'X' : '.') : '!');
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

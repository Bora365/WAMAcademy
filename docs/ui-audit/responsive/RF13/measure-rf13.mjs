/**
 * RF13 — ADM-USER-NEW, ADM-UPLOADUSER, ADM-UPLOADCOURSE, ADM-REPORTBUILDER
 * PHASE=before|after WAM_AUDIT_PASS=… node measure-rf13.mjs [--engine=chromium|webkit]
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
  { id: 'ADM-USER-NEW', path: '/user/editadvanced.php?id=-1' },
  { id: 'ADM-UPLOADUSER', path: '/admin/tool/uploaduser/index.php' },
  { id: 'ADM-UPLOADCOURSE', path: '/admin/tool/uploadcourse/index.php' },
  { id: 'ADM-REPORTBUILDER', path: '/reportbuilder/index.php' },
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
    for (const el of document.querySelectorAll('#region-main *, .secondary-navigation *, form.mform *, .reportbuilder-wrapper *, table *')) {
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
        if (culprits.length >= 10) break;
      }
    }
    const sec = document.querySelector('.secondary-navigation .navigation');
    return {
      pageOv,
      bodyCls,
      culprits,
      ih: innerHeight,
      scrollH: document.documentElement.scrollHeight,
      secOv: sec ? Math.max(0, sec.scrollWidth - sec.clientWidth) : null,
      hasMform: !!document.querySelector('form.mform'),
      hasTable: !!document.querySelector('#region-main table'),
      hasFilepicker: !!document.querySelector('[data-fieldtype=filepicker], .filepicker-filelist, input[type=file]'),
      hasEditor: !!document.querySelector('.tox-tinymce, .felement[data-fieldtype=editor]'),
      hasReportBuilder: !!document.querySelector('.reportbuilder-wrapper, [data-region="reportbuilder"], .reportbuilder-report'),
      saveBtn: !!document.querySelector('#id_submitbutton, #id_submitbutton_add, input[name=submitbutton], button[type=submit].btn-primary'),
    };
  });
}

async function run() {
  if (!PASS) throw new Error('WAM_AUDIT_PASS gerekli');
  const engine = engineName === 'webkit' ? webkit : chromium;
  const browser = await engine.launch({ headless: true });
  const results = [];
  const ctx = await browser.newContext();
  const page = await ctx.newPage();
  await login(page, 'admin');

  for (const screen of SCREENS) {
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
        error = String(e.message || e);
      }
      const ready = status === 'ok' ? await pageReady(page) : { ok: false };
      if (status === 'ok' && !ready.ok) status = ready.isLogin ? 'redirect-login' : 'not-ready';

      let metrics = null;
      let table = null;
      let screenshot = null;
      if (status === 'ok') {
        metrics = await measurePage(page);
        table = await probe(page);
        const name = `${screen.id}_admin_${vp.label}${engineName === 'webkit' ? '_webkit' : ''}`;
        screenshot = `${name}.png`;
        await page.screenshot({ path: path.join(OUT_DIR, screenshot), fullPage: false });
      }
      results.push({
        id: screen.id, role: 'admin', viewport: vp.label, w: vp.width, h: vp.height,
        status, error, metrics, table, screenshot, phase: PHASE, engine: engineName, url: page.url(),
      });
      const ov = table?.pageOv ?? metrics?.overflowXPx ?? 0;
      process.stdout.write(ov > 2 ? 'X' : (status === 'ok' ? '.' : '!'));
      console.log(` ${PHASE} ${screen.id} ${vp.label} status=${status} pageOv=${ov}`);
    }
  }

  await logout(page).catch(() => {});
  await ctx.close();
  await browser.close();
  fs.writeFileSync(JSON_OUT, JSON.stringify({ phase: PHASE, engine: engineName, count: results.length, results }, null, 2));
  console.log(`Wrote ${JSON_OUT} (${results.length})`);
}

run().catch((e) => { console.error(e); process.exit(1); });

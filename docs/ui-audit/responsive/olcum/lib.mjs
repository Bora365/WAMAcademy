import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
export const ROOT = path.resolve(__dirname, '..');
export const KANIT = path.join(ROOT, 'kanitlar');
export const OUT = path.join(__dirname, 'out');

export const BASE = process.env.WAM_BASE_URL || 'http://localhost:8081';
export const PASS = process.env.WAM_AUDIT_PASS || '';

export const USERS = {
  admin: 'admin',
  teacher: 'wamtest_egitmen',
  student: 'wamtest_ogrenci',
};

export const VIEWPORTS = {
  phone390: { width: 390, height: 844, label: 'phone390' },
  phone320: { width: 320, height: 568, label: 'phone320' },
  phone360: { width: 360, height: 740, label: 'phone360' },
  phoneLand: { width: 844, height: 390, label: 'phoneLand' },
  phoneKb: { width: 390, height: 420, label: 'phoneKbProxy' },
  ipadPort: { width: 820, height: 1180, label: 'ipadPort' },
  ipadLand: { width: 1180, height: 820, label: 'ipadLand' },
  split507: { width: 507, height: 768, label: 'split507' },
  split320: { width: 320, height: 768, label: 'split320' },
  desk1024: { width: 1024, height: 768, label: 'desk1024' },
  desk1280: { width: 1280, height: 800, label: 'desk1280' },
  desk1440: { width: 1440, height: 900, label: 'desk1440' },
};

export function ensureDirs() {
  fs.mkdirSync(KANIT, { recursive: true });
  fs.mkdirSync(OUT, { recursive: true });
}

export function writeJson(file, data) {
  ensureDirs();
  fs.writeFileSync(file, JSON.stringify(data, null, 2));
}

export async function login(page, role) {
  if (!PASS) {
    throw new Error('WAM_AUDIT_PASS ortam değişkeni gerekli');
  }
  const user = USERS[role];
  if (!user) throw new Error(`Bilinmeyen rol: ${role}`);
  await page.goto(`${BASE}/login/index.php`, { waitUntil: 'networkidle' });
  await page.waitForSelector('#username', { state: 'visible' });
  await page.fill('#username', user);
  await page.fill('#password', PASS);
  await page.click('#loginbtn');
  await page.waitForLoadState('domcontentloaded');
  await page.waitForTimeout(800);
  // Moodle bazen loginredirect ile aynı sayfada kalır; bir kez daha dene
  if (page.url().includes('/login/')) {
    await page.fill('#username', user);
    await page.fill('#password', PASS);
    await page.click('#loginbtn');
    await page.waitForLoadState('networkidle').catch(() => {});
    await page.waitForTimeout(1000);
  }
  const url = page.url();
  if (url.includes('/login/')) {
    throw new Error(`Giriş başarısız: ${role} → ${url}`);
  }
}

export async function logout(page) {
  await page.goto(`${BASE}/login/logout.php`, { waitUntil: 'domcontentloaded' }).catch(() => {});
}

/** Sayfa gerçekten yüklü mü (girişe düşmüş mü)? */
export async function pageReady(page, { allowLogin = false } = {}) {
  await page.waitForLoadState('domcontentloaded').catch(() => {});
  // complete olmasa da interactive + main yeterli
  for (let i = 0; i < 20; i++) {
    const info = await page.evaluate((allowLogin) => {
      const url = location.href;
      const isLogin = /\/login\/(?!change_password\.php)/.test(url);
      const main =
        document.querySelector('#region-main') ||
        document.querySelector('main') ||
        document.querySelector('[role="main"]') ||
        document.querySelector('#page-content') ||
        (allowLogin ? document.querySelector('.login-container, #login') : null);
      return {
        url,
        readyState: document.readyState,
        isLogin,
        hasMain: !!main,
        title: document.title || '',
        bodyClasses: document.body ? document.body.className : '',
      };
    }, allowLogin);
    const ok =
      (info.readyState === 'complete' || info.readyState === 'interactive') &&
      info.hasMain &&
      (allowLogin || !info.isLogin);
    if (ok) return { ok: true, ...info };
    await page.waitForTimeout(200);
  }
  const last = await page.evaluate((allowLogin) => {
    const url = location.href;
    const isLogin = /\/login\/(?!change_password\.php)/.test(url);
    const main =
      document.querySelector('#region-main') ||
      document.querySelector('main') ||
      document.querySelector('[role="main"]') ||
      document.querySelector('#page-content') ||
      (allowLogin ? document.querySelector('.login-container, #login') : null);
    return {
      url,
      readyState: document.readyState,
      isLogin,
      hasMain: !!main,
      title: document.title || '',
      bodyClasses: document.body ? document.body.className : '',
    };
  }, allowLogin);
  return { ok: false, ...last };
}

export async function measurePage(page) {
  return page.evaluate(() => {
    const doc = document.documentElement;
    const body = document.body;
    const sw = Math.max(doc.scrollWidth, body ? body.scrollWidth : 0);
    const iw = window.innerWidth;
    const sh = Math.max(doc.scrollHeight, body ? body.scrollHeight : 0);
    const ih = window.innerHeight;
    const overflowX = Math.max(0, sw - iw);

    const sticky = [];
    const fixed = [];
    for (const el of document.querySelectorAll('*')) {
      const st = getComputedStyle(el);
      if (st.position === 'fixed') {
        const r = el.getBoundingClientRect();
        if (r.width > 0 && r.height > 0) {
          fixed.push({
            tag: el.tagName,
            cls: (el.className || '').toString().slice(0, 80),
            top: Math.round(r.top),
            left: Math.round(r.left),
            w: Math.round(r.width),
            h: Math.round(r.height),
          });
        }
      }
      if (st.position === 'sticky' && sticky.length < 20) {
        sticky.push((el.className || el.tagName).toString().slice(0, 60));
      }
    }

    // Viewport dışı veya neredeyse görünmeyen etkileşimli kontroller
    const offscreen = [];
    const actions = document.querySelectorAll(
      'a, button, input, select, textarea, [role="button"], .btn, .dropdown-toggle'
    );
    for (const el of actions) {
      const r = el.getBoundingClientRect();
      const st = getComputedStyle(el);
      if (st.display === 'none' || st.visibility === 'hidden' || st.opacity === '0') continue;
      if (r.width < 2 || r.height < 2) continue;
      const fullyRight = r.left >= iw - 2;
      const fullyLeft = r.right <= 2;
      const fullyBelow = r.top >= ih - 2;
      const clipped = r.right > iw + 1 || r.left < -1;
      if (fullyRight || fullyLeft || (clipped && r.left > iw * 0.5)) {
        const label = (el.getAttribute('aria-label') || el.textContent || el.id || '')
          .trim()
          .replace(/\s+/g, ' ')
          .slice(0, 60);
        offscreen.push({
          label,
          left: Math.round(r.left),
          right: Math.round(r.right),
          top: Math.round(r.top),
          cls: (el.className || '').toString().slice(0, 60),
        });
        if (offscreen.length >= 15) break;
      }
    }

    // Odak: ilk görünür form alanı
    let focusOk = null;
    const field = document.querySelector(
      'input:not([type=hidden]):not([disabled]), select:not([disabled]), textarea:not([disabled])'
    );
    if (field) {
      field.focus();
      const r = field.getBoundingClientRect();
      const vis =
        r.top < ih &&
        r.bottom > 0 &&
        r.left < iw &&
        r.right > 0 &&
        r.width > 0 &&
        r.height > 0;
      focusOk = {
        tag: field.tagName,
        id: field.id || '',
        visibleInViewport: vis,
        top: Math.round(r.top),
        bottom: Math.round(r.bottom),
      };
    }

    const hamburger = document.querySelector('.wam-hamburger, .wam-menu-toggle, [aria-controls="wam-side-menu"]');
    const gear = document.querySelector('.wam-gear, #user-menu-toggle, .usermenu .dropdown-toggle, [data-region="usermenu"]');

    return {
      scrollWidth: sw,
      innerWidth: iw,
      scrollHeight: sh,
      innerHeight: ih,
      overflowXPx: overflowX,
      fixedCount: fixed.length,
      fixedSample: fixed.slice(0, 8),
      stickySample: sticky.slice(0, 8),
      offscreenActions: offscreen,
      focusOk,
      hasHamburger: !!hamburger,
      hasGear: !!gear,
      menuOpen: document.body.classList.contains('wam-menu-open'),
    };
  });
}

export async function openMenu(page) {
  const btn = page.locator('.wam-hamburger, .wam-menu-toggle').first();
  if (await btn.count()) {
    await btn.click({ timeout: 3000 }).catch(() => {});
    await page.waitForTimeout(400);
    return true;
  }
  return false;
}

export async function closeMenu(page) {
  if (await page.locator('.wam-menu-overlay').count()) {
    await page.locator('.wam-menu-overlay').click({ timeout: 2000 }).catch(() => {});
  }
  await page.keyboard.press('Escape').catch(() => {});
  await page.waitForTimeout(200);
}

export async function screenshot(page, name) {
  ensureDirs();
  const file = path.join(KANIT, `${name}.png`);
  await page.screenshot({ path: file, fullPage: false });
  return file;
}

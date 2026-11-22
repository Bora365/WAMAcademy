/**
 * Menü ve admin ağacından ekran adaylarını toplar. Silme/kaydetme tıklamaz.
 */
import { chromium } from 'playwright';
import {
  BASE, PASS, USERS, ensureDirs, login, logout, writeJson, OUT, pageReady,
} from './lib.mjs';

const role = (process.argv.find((a) => a.startsWith('--role=')) || '--role=student').split('=')[1];

async function collectLinks(page) {
  return page.evaluate(() => {
    const out = [];
    const seen = new Set();
    const add = (href, label, source) => {
      if (!href) return;
      let u;
      try {
        u = new URL(href, location.origin);
      } catch {
        return;
      }
      if (u.origin !== location.origin) return;
      if (u.pathname.includes('/login/logout')) return;
      if (u.searchParams.has('sesskey') && /delete|remove|destroy|unenrol|suspend/i.test(u.href)) return;
      const key = u.pathname + u.search.replace(/([?&])(sesskey|userid|id)=[^&]*/g, '');
      // Normalize: keep meaningful params (category, section, courseid) but drop userid variants for listing
      const norm = u.pathname + '?' + [...u.searchParams.entries()]
        .filter(([k]) => !['sesskey', 'userid', 'user', 'return'].includes(k))
        .map(([k, v]) => `${k}=${v}`)
        .join('&');
      const id = norm.replace(/\?$/, '');
      if (seen.has(id)) return;
      seen.add(id);
      out.push({
        href: u.pathname + u.search,
        label: (label || '').trim().replace(/\s+/g, ' ').slice(0, 80),
        source,
      });
    };

    document.querySelectorAll('a[href]').forEach((a) => {
      const src =
        a.closest('.wam-menu-panel') ? 'side-menu' :
        a.closest('.wam-gear__menu, .usermenu, .dropdown-menu') ? 'user-menu' :
        a.closest('.wam-adm, .admin_tree, #settingsnav, .block_settings') ? 'admin-nav' :
        a.closest('.secondary-navigation, .moremenu') ? 'secondary' :
        a.closest('#region-main') ? 'main' :
        'other';
      add(a.href, a.textContent, src);
    });
    return out;
  });
}

async function main() {
  if (!PASS) {
    console.error('WAM_AUDIT_PASS gerekli');
    process.exit(1);
  }
  ensureDirs();
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await context.newPage();

  await login(page, role);
  await page.goto(`${BASE}/local/wamakademi/index.php`, { waitUntil: 'networkidle' }).catch(() =>
    page.goto(`${BASE}/`, { waitUntil: 'domcontentloaded' })
  );
  await page.waitForTimeout(800);

  // Open side menu
  const ham = page.locator('.wam-hamburger, .wam-menu-toggle').first();
  if (await ham.count()) await ham.click().catch(() => {});
  await page.waitForTimeout(500);

  // Open gear / user menu
  const gear = page.locator('.wam-gear, .usermenu .dropdown-toggle, #user-action-menu').first();
  if (await gear.count()) await gear.click().catch(() => {});
  await page.waitForTimeout(400);

  const ready = await pageReady(page);
  const links = await collectLinks(page);

  // Preferences hub links
  await page.goto(`${BASE}/user/preferences.php`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(500);
  const prefLinks = await collectLinks(page);

  // Admin search if admin
  let adminLinks = [];
  if (role === 'admin') {
    await page.goto(`${BASE}/admin/search.php`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(800);
    const admHam = page.locator('.wam-hamburger, .wam-menu-toggle').first();
    if (await admHam.count()) await admHam.click().catch(() => {});
    await page.waitForTimeout(400);
    adminLinks = await collectLinks(page);

    // Top admin categories
    for (const cat of ['users', 'courses', 'appearance', 'plugins', 'server', 'reports', 'development']) {
      await page.goto(`${BASE}/admin/category.php?category=${cat}`, { waitUntil: 'domcontentloaded' }).catch(() => {});
      await page.waitForTimeout(400);
      const more = await collectLinks(page);
      adminLinks.push(...more);
    }
  }

  // Course if available
  await page.goto(`${BASE}/local/wamakademi/mycourses.php`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(500);
  const courseLinks = await collectLinks(page);

  const merged = [...links, ...prefLinks, ...adminLinks, ...courseLinks];
  const byHref = new Map();
  for (const L of merged) {
    if (!byHref.has(L.href)) byHref.set(L.href, L);
  }

  const result = {
    role,
    user: USERS[role],
    base: BASE,
    ready,
    count: byHref.size,
    links: [...byHref.values()].sort((a, b) => a.href.localeCompare(b.href)),
  };
  writeJson(`${OUT}/discover-${role}.json`, result);
  console.log(`discover ${role}: ${result.count} links → out/discover-${role}.json`);

  await logout(page);
  await browser.close();
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});

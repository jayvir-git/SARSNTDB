import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import puppeteer from 'puppeteer-core';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const CHROME = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE = 'http://127.0.0.1/SARSNTDB';

const OUT_DIRS = [
  path.join(ROOT, '_incoming', 'jim-kelley', '2026-09-09_pango-snv-lineages', 'sent'),
  'C:\\Users\\jayvir\\Pictures\\Saved Pictures\\SARSNTDB_Jim_2026-09-09_pango',
];

const SCHEMES = [
  'artic_v3',
  'artic_v4_1',
  'artic_v5_3',
  'midnight_1200',
  'varskip',
  'varskip_vss1a',
];

function schemeParams() {
  const p = new URLSearchParams();
  p.set('coord', '21987');
  p.set('ref', 'G');
  p.set('alt', 'A');
  p.set('layout', 'detailed');
  p.set('schemes_submitted', '1');
  for (const code of SCHEMES) {
    p.append('schemes[]', code);
  }
  return p;
}

const shots = [
  {
    file: '01_mutations_detail_G21987A_pango.png',
    url: `${BASE}/MutationsDetail.php?Start=21987&End=21987`,
    wait: 'table tbody tr.snv-primer-row',
    fullPage: true,
  },
  {
    file: '02_mutations_search_primer_dropdown.png',
    url: `${BASE}/MutationsSearch.php`,
    wait: '#Primer',
    fullPage: false,
    clipForm: true,
  },
  {
    file: '03_snv_G21987A_lineages_all_primers.png',
    url: `${BASE}/SnvPrimerView.php?${schemeParams().toString()}`,
    wait: '.tsg-breakpoint-panel',
    fullPage: true,
  },
  {
    file: '04_snv_G21987A_primer_73_LEFT.png',
    url: `${BASE}/SnvPrimerView.php?${schemeParams().toString()}&primer=163`,
    wait: '.tsg-breakpoint-panel',
    fullPage: true,
  },
];

for (const dir of OUT_DIRS) {
  fs.mkdirSync(dir, { recursive: true });
}

try {
  const probe = await fetch(`${BASE}/MutationsSearch.php`);
  if (!probe.ok) {
    throw new Error('HTTP ' + probe.status);
  }
} catch (e) {
  process.stderr.write('Apache not reachable at 127.0.0.1. Start XAMPP Apache and retry.\n' + e + '\n');
  process.exit(1);
}

const browser = await puppeteer.launch({
  executablePath: CHROME,
  headless: 'new',
  defaultViewport: { width: 1400, height: 900, deviceScaleFactor: 1 },
  args: ['--hide-scrollbars', '--disable-gpu'],
});

try {
  const page = await browser.newPage();
  page.setDefaultTimeout(60000);
  for (const shot of shots) {
    process.stdout.write(`Capturing ${shot.file}\n`);
    await page.goto(shot.url, { waitUntil: 'domcontentloaded' });
    if (shot.wait) {
      await page.waitForSelector(shot.wait, { timeout: 20000 });
    }
    await page.evaluate(() => window.scrollTo(0, 0));
    await new Promise((r) => setTimeout(r, 600));
    let buf;
    if (shot.clipForm) {
      const box = await page.evaluate(() => {
        const fieldset = document.getElementById('Mutations_row');
        const r = fieldset.getBoundingClientRect();
        const heading = document.querySelector('h4.search-header');
        const top = heading ? heading.getBoundingClientRect().top : 0;
        return {
          x: Math.max(0, r.left - 12),
          y: Math.max(0, top - 8),
          width: Math.min(1400, r.width + 24),
          height: r.bottom - Math.max(0, top - 8) + 16,
        };
      });
      buf = await page.screenshot({
        type: 'png',
        clip: {
          x: box.x,
          y: box.y,
          width: box.width,
          height: box.height,
        },
      });
    } else {
      buf = await page.screenshot({ fullPage: !!shot.fullPage, type: 'png' });
    }
    for (const dir of OUT_DIRS) {
      fs.writeFileSync(path.join(dir, shot.file), buf);
    }
  }
  process.stdout.write('Done\n');
} finally {
  await browser.close();
}

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import puppeteer from 'puppeteer-core';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const CHROME = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE = 'http://127.0.0.1/SARSNTDB';

const OUT_DIRS = [
  path.join(ROOT, '_incoming', 'jim-kelley', '2026-09-11_nearby-snvs-indels', 'sent'),
  'C:\\Users\\jayvir\\Pictures\\Saved Pictures\\SARSNTDB_Jim_2026-09-11_nearby',
];

const SCHEMES = [
  'artic_v3',
  'artic_v4_1',
  'artic_v5_3',
  'midnight_1200',
  'varskip',
  'varskip_vss1a',
];

function schemeParams(extra) {
  const p = new URLSearchParams();
  p.set('layout', 'detailed');
  p.set('schemes_submitted', '1');
  for (const code of SCHEMES) {
    p.append('schemes[]', code);
  }
  Object.entries(extra || {}).forEach(([key, value]) => {
    p.set(key, String(value));
  });
  return p;
}

function junctionUrl() {
  const p = schemeParams();
  p.set('left', '5249');
  p.set('right', '23191');
  return `${BASE}/TwoSegmentStructures.php?${p.toString()}`;
}

const shots = [
  {
    file: '01_mutations_search_no_primer_row.png',
    url: `${BASE}/MutationsSearch.php`,
    wait: '#submit_coord_btn',
    clipForm: true,
  },
  {
    file: '02_mutations_detail_G21987A_lineages.png',
    url: `${BASE}/MutationsDetail.php?Start=21987&End=21987`,
    wait: 'table tbody tr.snv-primer-row',
    clipSelector: 'table.sortable',
    clipIndex: 0,
    pad: 24,
  },
  {
    file: '02b_mutations_detail_indel_table.png',
    url: `${BASE}/MutationsDetail.php?Start=21987&End=21987`,
    wait: '.indel-heading',
    clipIndel: true,
  },
  {
    file: '03_indel_21987_primers.png',
    url: `${BASE}/SnvPrimerView.php?${schemeParams({
      coord: 21987,
      ref: 'GTGTTTATT',
      alt: 'G',
      kind: 'indel',
    }).toString()}`,
    wait: '.tsg-breakpoint-panel',
    fullPage: true,
  },
  {
    file: '04_junction_17943_right_C23202A.png',
    url: junctionUrl(),
    wait: '.tsg-breakpoint-panel',
    waitPanels: 2,
    scrollLabel: 'C23202A',
    clipRight: true,
  },
  {
    file: '05_snv_G21987A_nearby_snvs.png',
    url: `${BASE}/SnvPrimerView.php?${schemeParams({
      coord: 21987,
      ref: 'G',
      alt: 'A',
    }).toString()}`,
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
  defaultViewport: { width: 1400, height: 1100, deviceScaleFactor: 1 },
  args: ['--hide-scrollbars', '--disable-gpu'],
});

try {
  const page = await browser.newPage();
  page.setDefaultTimeout(60000);
  for (const shot of shots) {
    process.stdout.write('Capturing ' + shot.file + '\n');
    await page.goto(shot.url, { waitUntil: 'domcontentloaded' });
    if (shot.wait) {
      await page.waitForSelector(shot.wait, { timeout: 20000 });
    }
    if (shot.waitPanels) {
      await page.waitForFunction(
        (n) => document.querySelectorAll('.tsg-breakpoint-panel').length >= n,
        {},
        shot.waitPanels
      );
    }
    if (shot.scrollLabel) {
      await page.waitForFunction(
        (label) => Array.from(document.querySelectorAll('.tsg-nearby-snv-label'))
          .some((el) => el.textContent === label),
        {},
        shot.scrollLabel
      );
      await page.evaluate((label) => {
        const el = Array.from(document.querySelectorAll('.tsg-nearby-snv-label'))
          .find((node) => node.textContent === label);
        if (el) {
          el.scrollIntoView({ block: 'center' });
        }
      }, shot.scrollLabel);
    } else {
      await page.evaluate(() => window.scrollTo(0, 0));
    }
    await new Promise((r) => setTimeout(r, 700));
    let buf;
    if (shot.clipSelector) {
      const box = await page.evaluate((sel, idx, pad) => {
        const nodes = document.querySelectorAll(sel);
        const el = nodes[idx] || nodes[0];
        const r = el.getBoundingClientRect();
        return {
          x: Math.max(0, r.left - pad),
          y: Math.max(0, r.top - pad),
          width: Math.min(1380, r.width + pad * 2),
          height: Math.min(700, r.height + pad * 2),
        };
      }, shot.clipSelector, shot.clipIndex || 0, shot.pad || 16);
      buf = await page.screenshot({
        type: 'png',
        clip: { x: box.x, y: box.y, width: box.width, height: box.height },
      });
    } else if (shot.clipIndel) {
      const box = await page.evaluate(() => {
        const heading = document.querySelector('.indel-heading');
        const table = document.querySelector('.datagrid-indel table') || document.querySelectorAll('table.sortable')[1];
        const top = heading.getBoundingClientRect().top;
        const left = Math.min(heading.getBoundingClientRect().left, table.getBoundingClientRect().left);
        const right = Math.max(heading.getBoundingClientRect().right, table.getBoundingClientRect().right);
        const bottom = table.getBoundingClientRect().bottom;
        return {
          x: Math.max(0, left - 12),
          y: Math.max(0, top - 12),
          width: Math.min(1380, right - left + 24),
          height: bottom - top + 24,
        };
      });
      buf = await page.screenshot({
        type: 'png',
        clip: { x: box.x, y: box.y, width: box.width, height: box.height },
      });
    } else if (shot.clipForm) {
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
    } else if (shot.clipRight) {
      const box = await page.evaluate(() => {
        const panels = document.querySelectorAll('.tsg-breakpoint-panel');
        const right = panels[1] || panels[0];
        const r = right.getBoundingClientRect();
        const title = right.querySelector('.tsg-breakpoint-title');
        const topEl = title || right;
        const top = topEl.getBoundingClientRect().top + window.scrollY;
        const left = r.left + window.scrollX;
        return {
          x: Math.max(0, left - 8),
          y: Math.max(0, top - 8),
          width: Math.min(1200, r.width + 16),
          height: Math.min(900, r.height + 24),
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

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { spawnSync } from 'child_process';
import puppeteer from 'puppeteer-core';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const CHROME = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const OUT_DIR = 'C:\\Users\\jayvir\\Pictures\\Saved Pictures\\SARSNTDB_NJ_table_junctions';
const PHP = 'C:\\xampp\\php\\php.exe';
const LIST_SCRIPT = path.join(__dirname, '_list_nj_ids.php');
const BASE = 'http://127.0.0.1/SARSNTDB/TwoSegmentStructures.php';
const SCHEMES = [
  'artic_v3',
  'artic_v4_1',
  'artic_v5_3',
  'midnight_1200',
  'varskip',
  'varskip_vss1a',
];

function pad(n, w) {
  return String(n).padStart(w, '0');
}

function buildUrl(id) {
  const params = new URLSearchParams();
  params.set('id', String(id));
  params.set('schemes_submitted', '1');
  for (const code of SCHEMES) {
    params.append('schemes[]', code);
  }
  return `${BASE}?${params.toString()}`;
}

const listed = spawnSync(PHP, [LIST_SCRIPT], { encoding: 'utf8' });
if (listed.status !== 0) {
  process.stderr.write(listed.stderr || listed.stdout || 'php list failed\n');
  process.exit(1);
}
const rows = JSON.parse(listed.stdout);
fs.mkdirSync(OUT_DIR, { recursive: true });

const browser = await puppeteer.launch({
  executablePath: CHROME,
  headless: 'new',
  defaultViewport: { width: 1400, height: 900, deviceScaleFactor: 1 },
  args: ['--hide-scrollbars', '--disable-gpu'],
});

try {
  const page = await browser.newPage();
  page.setDefaultTimeout(60000);
  let i = 0;
  for (const row of rows) {
    i += 1;
    const name = `${pad(i, 2)}_NJ_${row.size}_${row.left}-${row.right}.png`;
    const dest = path.join(OUT_DIR, name);
    const url = buildUrl(row.id);
    process.stdout.write(`[${i}/${rows.length}] ${name} id=${row.id}\n`);
    await page.goto(url, { waitUntil: 'domcontentloaded' });
    await page.waitForFunction(() => typeof window.TwoSegmentViz === 'object');
    await page.waitForFunction(() => document.querySelectorAll('.tsg-breakpoint-panel').length >= 2);
    await page.evaluate(() => window.scrollTo(0, 0));
    await new Promise((r) => setTimeout(r, 400));
    await page.screenshot({ path: dest, fullPage: true, type: 'png' });
  }
  process.stdout.write(`Wrote ${rows.length} screenshots to ${OUT_DIR}\n`);
} finally {
  await browser.close();
}

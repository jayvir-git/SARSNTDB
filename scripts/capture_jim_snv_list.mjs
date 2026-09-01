import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import puppeteer from 'puppeteer-core';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const CHROME = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE = 'http://127.0.0.1/SARSNTDB/SnvPrimerView.php';
const SCHEMES = [
  'artic_v3',
  'artic_v4_1',
  'artic_v5_3',
  'midnight_1200',
  'varskip',
  'varskip_vss1a',
];

const snvs = [
  { label: 'G21987A', coord: 21987, ref: 'G', alt: 'A' },
  { label: 'G22813T', coord: 22813, ref: 'G', alt: 'T' },
  { label: 'G24410A', coord: 24410, ref: 'G', alt: 'A' },
  { label: 'C25000T', coord: 25000, ref: 'C', alt: 'T' },
  { label: 'A26530G', coord: 26530, ref: 'A', alt: 'G' },
  { label: 'C26577G', coord: 26577, ref: 'C', alt: 'G' },
  { label: 'T15521A', coord: 15521, ref: 'T', alt: 'A' },
];

const outDirs = [
  path.join(ROOT, '_incoming', 'jim-kelley', '2026-09-01_snv-primer-list', 'sent'),
  'C:\\Users\\jayvir\\Pictures\\Saved Pictures\\SARSNTDB_Jim_SNVs_2026-09-01',
];

function buildUrl(snv) {
  const p = new URLSearchParams();
  p.set('coord', String(snv.coord));
  p.set('ref', snv.ref);
  p.set('alt', snv.alt);
  p.set('layout', 'detailed');
  p.set('schemes_submitted', '1');
  for (const code of SCHEMES) {
    p.append('schemes[]', code);
  }
  return `${BASE}?${p.toString()}`;
}

for (const dir of outDirs) {
  fs.mkdirSync(dir, { recursive: true });
}

try {
  const probe = await fetch(buildUrl(snvs[0]));
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
  let i = 0;
  for (const snv of snvs) {
    i += 1;
    const file = `${String(i).padStart(2, '0')}_${snv.label}_all_primers_detailed.png`;
    process.stdout.write(`[${i}/${snvs.length}] ${file}\n`);
    await page.goto(buildUrl(snv), { waitUntil: 'domcontentloaded' });
    await page.waitForFunction(() => typeof window.TwoSegmentViz === 'object');
    await page.waitForSelector('.tsg-breakpoint-panel', { timeout: 20000 });
    await page.evaluate(() => window.scrollTo(0, 0));
    await new Promise((r) => setTimeout(r, 500));
    const buf = await page.screenshot({ fullPage: true, type: 'png' });
    for (const dir of outDirs) {
      fs.writeFileSync(path.join(dir, file), buf);
    }
  }
  process.stdout.write('Done\n');
} finally {
  await browser.close();
}

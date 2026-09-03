import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import puppeteer from 'puppeteer-core';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const CHROME = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE = 'http://127.0.0.1/SARSNTDB';
const PACKET = path.join(ROOT, '_incoming', 'jim-kelley', '2026-09-02_all-junctions-snv-compact', 'sent');
const PICTURES = 'C:\\Users\\jayvir\\Pictures\\Saved Pictures\\SARSNTDB_Jim_2026-09-02_remaining';
const CSV = path.join(ROOT, '_incoming', 'jim-kelley', 'NJ-table-small.csv');
const SKIP_SIZE = 17943;
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

function schemeQuery() {
  const p = new URLSearchParams();
  p.set('schemes_submitted', '1');
  for (const code of SCHEMES) {
    p.append('schemes[]', code);
  }
  return p;
}

function junctionUrl(left, right, layout) {
  const p = schemeQuery();
  p.set('left', String(left));
  p.set('right', String(right));
  p.set('layout', layout);
  return `${BASE}/TwoSegmentStructures.php?${p.toString()}`;
}

function snvUrl(snv) {
  const p = schemeQuery();
  p.set('coord', String(snv.coord));
  p.set('ref', snv.ref);
  p.set('alt', snv.alt);
  p.set('layout', 'compact');
  return `${BASE}/SnvPrimerView.php?${p.toString()}`;
}

function parseJunctions() {
  const text = fs.readFileSync(CSV, 'utf8').replace(/^\uFEFF/, '');
  const rows = [];
  for (const line of text.split(/\r?\n/)) {
    if (!line.trim() || /^Size,/i.test(line)) {
      continue;
    }
    const parts = line.split(',');
    const size = Number(parts[0]);
    const left = Number(parts[1]);
    const right = Number(parts[2]);
    if (!size || !left || !right || size === SKIP_SIZE) {
      continue;
    }
    rows.push({ size, left, right });
  }
  return rows;
}

const junctions = parseJunctions();
const shots = [];
let n = 0;
for (const j of junctions) {
  for (const layout of ['detailed', 'compact']) {
    n += 1;
    shots.push({
      file: `junctions/${String(n).padStart(2, '0')}_NJ_${j.size}_${j.left}-${j.right}_${layout}.png`,
      url: junctionUrl(j.left, j.right, layout),
    });
  }
}
snvs.forEach((snv, i) => {
  shots.push({
    file: `snvs/${String(i + 1).padStart(2, '0')}_${snv.label}_all_primers_compact.png`,
    url: snvUrl(snv),
  });
});

for (const dir of [PACKET, PICTURES]) {
  fs.mkdirSync(path.join(dir, 'junctions'), { recursive: true });
  fs.mkdirSync(path.join(dir, 'snvs'), { recursive: true });
}

try {
  const probe = await fetch(`${BASE}/TwoSegmentStructures.php?left=1890&right=2882&layout=compact`);
  if (!probe.ok) {
    throw new Error('HTTP ' + probe.status);
  }
} catch (e) {
  process.stderr.write('Apache not reachable at 127.0.0.1. Start XAMPP Apache and retry.\n' + e + '\n');
  process.exit(1);
}

process.stdout.write(`Capturing ${shots.length} shots (${junctions.length} junctions x 2 + ${snvs.length} SNV compact)\n`);

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
  for (const shot of shots) {
    i += 1;
    process.stdout.write(`[${i}/${shots.length}] ${shot.file}\n`);
    await page.goto(shot.url, { waitUntil: 'domcontentloaded' });
    await page.waitForFunction(() => typeof window.TwoSegmentViz === 'object');
    await page.waitForSelector('.tsg-breakpoint-panel', { timeout: 20000 });
    await page.evaluate(() => window.scrollTo(0, 0));
    await new Promise((r) => setTimeout(r, 400));
    const buf = await page.screenshot({ fullPage: true, type: 'png' });
    fs.writeFileSync(path.join(PACKET, shot.file), buf);
    fs.writeFileSync(path.join(PICTURES, shot.file), buf);
  }
  process.stdout.write('Done\n');
} finally {
  await browser.close();
}

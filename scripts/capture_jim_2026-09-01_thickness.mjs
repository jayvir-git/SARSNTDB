import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import puppeteer from 'puppeteer-core';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const CHROME = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE = 'http://127.0.0.1/SARSNTDB';
const PACKET = path.join(ROOT, '_incoming', 'jim-kelley', '2026-09-01_primer-thickness-compact', 'sent');
const PICTURES = 'C:\\Users\\jayvir\\Pictures\\Saved Pictures\\SARSNTDB_Jim_2026-09-01_thickness';
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

function junctionUrl(layout) {
  const p = schemeQuery();
  p.set('left', '5249');
  p.set('right', '23191');
  p.set('layout', layout);
  return `${BASE}/TwoSegmentStructures.php?${p.toString()}`;
}

function snvUrl(snv) {
  const p = schemeQuery();
  p.set('coord', String(snv.coord));
  p.set('ref', snv.ref);
  p.set('alt', snv.alt);
  p.set('layout', 'detailed');
  return `${BASE}/SnvPrimerView.php?${p.toString()}`;
}

const shots = [
  { file: '01_junction_17943_artic_v3_detailed.png', url: `${BASE}/TwoSegmentStructures.php?left=5249&right=23191&layout=detailed`, wait: '.tsg-breakpoint-panel' },
  { file: '02_junction_17943_artic_v3_compact.png', url: `${BASE}/TwoSegmentStructures.php?left=5249&right=23191&layout=compact`, wait: '.tsg-breakpoint-panel' },
  { file: '03_junction_17943_all_schemes_detailed.png', url: junctionUrl('detailed'), wait: '.tsg-breakpoint-panel' },
  { file: '04_junction_17943_all_schemes_compact.png', url: junctionUrl('compact'), wait: '.tsg-breakpoint-panel' },
];
snvs.forEach((snv, i) => {
  shots.push({
    file: `${String(i + 5).padStart(2, '0')}_${snv.label}_all_primers_detailed.png`,
    url: snvUrl(snv),
    wait: '.tsg-breakpoint-panel',
  });
});

for (const dir of [PACKET, PICTURES]) {
  fs.mkdirSync(dir, { recursive: true });
}

try {
  const probe = await fetch(`${BASE}/SnvPrimerView.php?coord=23202`);
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
  for (const shot of shots) {
    i += 1;
    process.stdout.write(`[${i}/${shots.length}] ${shot.file}\n`);
    await page.goto(shot.url, { waitUntil: 'domcontentloaded' });
    await page.waitForFunction(() => typeof window.TwoSegmentViz === 'object');
    await page.waitForSelector(shot.wait, { timeout: 20000 });
    await page.evaluate(() => window.scrollTo(0, 0));
    await new Promise((r) => setTimeout(r, 500));
    const buf = await page.screenshot({ fullPage: true, type: 'png' });
    fs.writeFileSync(path.join(PACKET, shot.file), buf);
    fs.writeFileSync(path.join(PICTURES, shot.file), buf);
  }
  process.stdout.write('Done\n');
} finally {
  await browser.close();
}

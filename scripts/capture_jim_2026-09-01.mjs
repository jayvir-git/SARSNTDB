import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import puppeteer from 'puppeteer-core';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const CHROME = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE = 'http://127.0.0.1/SARSNTDB';

const OUT_DIRS = [
  path.join(ROOT, '_incoming', 'jim-kelley', '2026-08-31_snv-primer-window', 'sent'),
  path.join(ROOT, '_incoming', 'jim-kelley', '2026-08-28_primer-pair-lines', 'sent'),
  'C:\\Users\\jayvir\\Pictures\\Saved Pictures\\SARSNTDB_Jim_2026-09-01',
];

const shots = [
  {
    file: '01_mutations_detail_1pct_near_23202.png',
    url: `${BASE}/MutationsDetail.php?Start=23100&End=23350&MinPercent=1`,
    wait: null,
    packets: ['snv'],
  },
  {
    file: '02_snv_23202_primers_detailed.png',
    url: `${BASE}/SnvPrimerView.php?coord=23202&layout=detailed`,
    wait: '.tsg-breakpoint-panel',
    packets: ['snv'],
  },
  {
    file: '03_junction_17943_primers_detailed.png',
    url: `${BASE}/TwoSegmentStructures.php?left=5249&right=23191&layout=detailed`,
    wait: '.tsg-breakpoint-panel',
    packets: ['pair'],
  },
  {
    file: '04_junction_17943_primers_compact.png',
    url: `${BASE}/TwoSegmentStructures.php?left=5249&right=23191&layout=compact`,
    wait: '.tsg-breakpoint-panel',
    packets: ['pair'],
  },
];

for (const dir of OUT_DIRS) {
  fs.mkdirSync(dir, { recursive: true });
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
    const buf = await page.screenshot({ fullPage: true, type: 'png' });
    for (const dir of OUT_DIRS) {
      if (dir.includes('snv-primer') && !shot.packets.includes('snv')) continue;
      if (dir.includes('primer-pair') && !shot.packets.includes('pair')) continue;
      fs.writeFileSync(path.join(dir, shot.file), buf);
    }
  }
  process.stdout.write('Done\n');
} finally {
  await browser.close();
}

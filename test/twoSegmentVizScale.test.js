'use strict';

var assert = require('assert');
var fs = require('fs');
var path = require('path');

global.window = {};
global.document = { readyState: 'complete', addEventListener: function () {}, getElementById: function () { return null; } };
require('../JS/twoSegmentViz.js');

var viz = global.window.TwoSegmentViz;
assert.ok(viz, 'TwoSegmentViz should be exported');
assert.strictEqual(typeof viz.breakpointWindow, 'function', 'breakpointWindow should be exported');
assert.strictEqual(typeof viz.breakpointPrimerBox, 'function', 'breakpointPrimerBox should be exported');

var leftWindow = viz.breakpointWindow(5249);
assert.deepStrictEqual(leftWindow, { start: 4449, end: 6049, breakpoint: 5249 });

var rightWindow = viz.breakpointWindow(23191);
assert.deepStrictEqual(rightWindow, { start: 22391, end: 23991, breakpoint: 23191 });

var primer = { coord_start: 5231, coord_end: 5259 };
var box = viz.breakpointPrimerBox(primer, leftWindow);
var expectedWidth = 29 * viz.TRACK_WIDTH / 1601;
assert.ok(box, 'primer overlapping the breakpoint window should produce a box');
assert.ok(Math.abs(box.width - expectedWidth) < 0.001, 'primer width should be drawn to scale');
assert.strictEqual(box.lengthNt, 29);

var outside = viz.breakpointPrimerBox({ coord_start: 7000, coord_end: 7030 }, leftWindow);
assert.strictEqual(outside, null, 'primer outside the breakpoint window should be excluded');

var css = fs.readFileSync(path.join(__dirname, '..', 'two_segment_viz.css'), 'utf8');
assert.match(css, /\.tsg-primer-arrow-right[\s\S]*?clip-path:\s*polygon/,
  'primer arrows should be a single clip-path so the shaft stays centered on the head');
assert.doesNotMatch(css, /\.tsg-primer-arrow::before[\s\S]*?height:\s*3\.5px/,
  'primer shafts should not use a subpixel top offset');
assert.match(css, /\.tsg-pair-connector[\s\S]*?color-mix/,
  'pair connectors should use a paler mix of the primer color');
assert.match(css, /\.tsg-pair-connector[\s\S]*?height:\s*1px/,
  'pair connectors should be a 1px bar so translateY(-50%) actually centers them');
assert.doesNotMatch(css, /\.tsg-primer-arrow[\s\S]*?min-width:\s*16px;/,
  'primer arrows should not retain the misleading 16 px minimum width');

assert.strictEqual(typeof viz.layoutCompactPairs, 'function', 'layoutCompactPairs should be exported');
var compactLaid = viz.layoutCompactPairs([
  { primer_name: 'nCoV_14_LEFT', direction: 'R', coord_start: 4500, coord_end: 4530 },
  { primer_name: 'nCoV_14_RIGHT', direction: 'L', coord_start: 4900, coord_end: 4930 },
  { primer_name: 'nCoV_15_LEFT', direction: 'R', coord_start: 4510, coord_end: 4540 },
  { primer_name: 'nCoV_15_RIGHT', direction: 'L', coord_start: 4910, coord_end: 4940 },
  { primer_name: 'nCoV_20_LEFT', direction: 'R', coord_start: 5800, coord_end: 5830 },
  { primer_name: 'nCoV_20_RIGHT', direction: 'L', coord_start: 5980, coord_end: 6010 }
], leftWindow);
function laneOf(name) {
  var hit = compactLaid.filter(function (item) { return item.primer.primer_name === name; })[0];
  assert.ok(hit, name + ' should be laid out');
  return hit.lane;
}
assert.strictEqual(laneOf('nCoV_14_LEFT'), laneOf('nCoV_14_RIGHT'),
  'a pair should share one stripe so the connector lines up');
assert.strictEqual(laneOf('nCoV_15_LEFT'), laneOf('nCoV_15_RIGHT'),
  'overlapping pair 15 should still share one stripe');
assert.notStrictEqual(laneOf('nCoV_14_LEFT'), laneOf('nCoV_15_LEFT'),
  'overlapping pairs should not sit on top of each other');
assert.strictEqual(laneOf('nCoV_20_LEFT'), laneOf('nCoV_14_LEFT'),
  'a pair that does not overlap should move back up to the top stripe');

assert.match(css, /\.tsg-nearby-snv-guide[\s\S]*?border-left:\s*1px dotted/,
  'nearby SNVs should be a pale dotted guide');
assert.match(css, /\.tsg-nearby-snv-label[\s\S]*?color:\s*#c0c0c0/,
  'nearby SNV labels should be light gray');
assert.match(css, /\.tsg-pango-marker-guide[\s\S]*?border-left:\s*1px dashed/,
  'Pango markers should be a dashed guide distinct from Original nearby SNVs');

assert.strictEqual(typeof viz.nearbySnvsForWindow, 'function', 'nearbySnvsForWindow should be exported');
assert.strictEqual(typeof viz.pangoMarkersForWindow, 'function', 'pangoMarkersForWindow should be exported');
global.window.TSG_NEARBY_SNVS = [
  { coordinate: 23202, reference: 'C', alternate: 'A', label: 'C23202A' },
  { coordinate: 21000, reference: 'G', alternate: 'T', label: 'G21000T' }
];
global.window.TSG_SHOW_NEARBY_SNVS = true;
var rightHits = viz.nearbySnvsForWindow(rightWindow);
assert.strictEqual(rightHits.length, 1, 'only SNVs inside the right 17943 window should be kept');
assert.strictEqual(rightHits[0].label, 'C23202A', 'C23202A is 11 nt from right breakpoint 23191');
assert.ok(viz.nearbySnvsEnabled(), 'Show nearby SNVs defaults to on');

global.window.TSG_PANGO_MARKERS = [
  { coordinate: 23202, reference: 'C', alternate: 'A', kind: 'snv', label: 'C23202A' },
  { coordinate: 685, reference: 'AAAGTCATTT', alternate: 'A', kind: 'indel', label: '685 deletion REF=AAAGTCATTT ALT=A' }
];
var pangoRight = viz.pangoMarkersForWindow(rightWindow);
assert.strictEqual(pangoRight.length, 1, 'only Pango markers inside the window should be kept');
assert.strictEqual(pangoRight[0].label, 'C23202A');
var pangoLeft = viz.pangoMarkersForWindow({ start: 1, end: 800, breakpoint: 400 });
assert.strictEqual(pangoLeft.length, 0, 'Pango indels are not drawn as nearby markers (D-89)');

// A one-sided alt pairs with the regular primer of the same number (Jim, 2026-11-03, D-87).
var altWindow = { start: 1001, end: 2600, breakpoint: 1800 };
var altScheme = [
  { primer_name: 'X_7_LEFT', direction: 'R', coord_start: 1100, coord_end: 1120 },
  { primer_name: 'X_7_RIGHT', direction: 'L', coord_start: 1500, coord_end: 1520 },
  { primer_name: 'X_7_RIGHT_alt1', direction: 'L', coord_start: 1530, coord_end: 1550 },
  { primer_name: 'X_8_LEFT_alt1', direction: 'R', coord_start: 1400, coord_end: 1420 },
  { primer_name: 'X_8_LEFT', direction: 'R', coord_start: 1410, coord_end: 1430 },
  { primer_name: 'X_8_RIGHT', direction: 'L', coord_start: 1800, coord_end: 1820 },
  { primer_name: 'X_9_LEFT_alt1', direction: 'R', coord_start: 1900, coord_end: 1920 },
  { primer_name: 'X_9_RIGHT_alt1', direction: 'L', coord_start: 2300, coord_end: 2320 }
];
function altInfo(name) { return viz.primerPairInfo(name); }
function names(list) { return list.map(function (p) { return p.primer_name; }); }
assert.deepStrictEqual(names(viz.altMates(altInfo('X_7_RIGHT_alt1'), [altScheme[2]], altScheme)), ['X_7_LEFT'],
  'a RIGHT alt alone borrows the regular LEFT');
assert.deepStrictEqual(names(viz.altMates(altInfo('X_8_LEFT_alt1'), [altScheme[3]], altScheme)), ['X_8_RIGHT'],
  'a LEFT alt alone borrows the regular RIGHT');
assert.deepStrictEqual(viz.altMates(altInfo('X_9_LEFT_alt1'), [altScheme[6], altScheme[7]], altScheme), [],
  'alts with both sides pair with each other');
assert.deepStrictEqual(viz.altMates(altInfo('X_7_LEFT'), [altScheme[0], altScheme[1]], altScheme), [],
  'regular pairs are unchanged');
var altCompact = viz.layoutCompactPairs(altScheme.slice(0, 3), altWindow, altScheme);
var altLane = altCompact.filter(function (item) { return item.pairKey === '7_alt'; });
assert.deepStrictEqual(names(altLane.map(function (item) { return item.primer; })).sort(), ['X_7_LEFT', 'X_7_RIGHT_alt1'],
  'compact: the alt stripe repeats the regular LEFT arrow');
assert.strictEqual(altLane[0].lane, altLane[1].lane, 'compact: borrowed mate shares the alt stripe');

// Hover readout maps the mouse x back to a genome coordinate (Jim, Zoom 2026-11-06).
var hover = global.window.TSG_hoverCoordAt;
assert.strictEqual(hover(0, leftWindow), 4449, 'left edge is the window start');
assert.strictEqual(hover(viz.TRACK_WIDTH - 0.01, leftWindow), 6049, 'right edge is the window end');
assert.strictEqual(hover(viz.breakpointPrimerBox({ coord_start: 5249, coord_end: 5249 }, leftWindow).left + 0.01, leftWindow), 5249, 'x of the breakpoint maps back to it');

// Nearby SNV labels never overlap: close labels go to new lanes, far ones reuse lane 0 (D-91).
var lanes = global.window.TSG_layoutLabelLanes([
  { x: 100, text: 'C21575T' }, { x: 105, text: 'G21987A' }, { x: 110, text: 'T22000C' }, { x: 400, text: 'A22813G' }
]);
assert.strictEqual(lanes.lanes, 3, 'three close labels need three lanes');
assert.deepStrictEqual(lanes.items.map(function (e) { return e.lane; }), [0, 1, 2, 0]);

console.log('twoSegmentViz breakpoint scale assertions passed');

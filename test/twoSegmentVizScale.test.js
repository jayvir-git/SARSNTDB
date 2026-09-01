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
assert.match(css, /\.tsg-primer-arrow::before[\s\S]*?height:\s*3px;/,
  'primer shaft should be 3 px thick');
assert.match(css, /\.tsg-pair-connector[\s\S]*?color-mix/,
  'pair connectors should use a paler mix of the primer color');
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

console.log('twoSegmentViz breakpoint scale assertions passed');

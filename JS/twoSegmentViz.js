/**
 * Two-segment structure visualization (sgmRNA first subtype).
 * Uses the same linear scale as JS/main.js: 29903 nt -> 900 px.
 */
(function (global) {
  'use strict';

  var GENOME_TOTAL = 29903;
  var TRACK_WIDTH = 900;
  var PAIR_LANE_HEIGHT = 14;
  var COMPACT_LANE_HEIGHT = 12;
  var ARROW_HEIGHT = 10;

  var SCHEME_COLORS = {
    artic_v3: '#d81b60',
    artic_v4_1: '#7b1fa2',
    artic_v5_3: '#ef6c00',
    midnight_1200: '#00838f',
    varskip: '#2e7d32',
    varskip_vss1a: '#1565c0'
  };

  function mapPos(num) {
    var n = Number(num);
    if (!isFinite(n) || n < 0) {
      return 0;
    }
    return (n * TRACK_WIDTH) / GENOME_TOTAL;
  }

  // Gene map — copied from main.js for visual parity with Repeats / intragenome views.
  var genests = [266, 806, 2720, 8555, 10055, 10973, 11843, 12092, 12686, 13025, 13442, 13442, 16237, 18040, 19621, 20659, 21563, 25393, 26245, 26523, 27202, 27394, 27756, 27894, 28274, 29558];
  var geneed = [805, 2719, 8554, 10054, 10972, 11842, 12091, 12685, 13024, 13441, 13480, 16236, 18039, 19620, 20658, 21552, 25384, 26220, 26472, 27191, 27387, 27759, 27887, 28259, 29533, 29674];
  var genecolor = ['DAF7A6', 'FFC300', 'FF5733', 'C70039', '900C3F', '85929e', '2596be', '9925be', 'be4d25', '49be25', 'd966ff', '668cff', 'ff66b3', '5a4e45', '915e3d', 'fe7f2d', 'fda53a', 'fcca46', 'cfc664', 'a1c181', '71a588', '619b8a', '0e6251', '21618c', '9b59b6', '7d3c98'];

  function gapLength(left, right) {
    var L = Number(left);
    var R = Number(right);
    if (!isFinite(L) || !isFinite(R)) {
      return 0;
    }
    // Closed intervals [from, left] and [right, to]: internal gap nt count.
    return Math.max(0, R - L - 1);
  }

  function esc(s) {
    if (s === null || s === undefined) {
      return '';
    }
    var d = document.createElement('div');
    d.textContent = String(s);
    return d.innerHTML;
  }

  function renderGenomeBar(container) {
    var wrap = document.createElement('div');
    wrap.className = 'tsg-genome-wrap';

    var title = document.createElement('div');
    title.className = 'tsg-row-title';
    title.textContent = 'Genome reference (same scale as Repeats view)';
    wrap.appendChild(title);

    var c1 = document.createElement('div');
    c1.className = 'tsg-coords-row';
    c1.innerHTML = '<span>5\'</span><span>3\'</span>';
    wrap.appendChild(c1);

    var bar = document.createElement('div');
    bar.className = 'tsg-genome-bar';
    for (var i = 0; i < genests.length; i++) {
      var blk = document.createElement('div');
      blk.className = 'tsg-gene-block';
      blk.style.background = '#' + genecolor[i];
      blk.style.marginLeft = mapPos(genests[i]) + 'px';
      blk.style.width = Math.max(0, mapPos(geneed[i]) - mapPos(genests[i])) + 'px';
      bar.appendChild(blk);
    }
    wrap.appendChild(bar);

    var c2 = document.createElement('div');
    c2.className = 'tsg-coords-row';
    c2.innerHTML = '<span>1</span><span>29903</span>';
    wrap.appendChild(c2);

    container.appendChild(wrap);
  }

  function breakpointWindow(coord) {
    var breakpoint = Math.round(Number(coord));
    var halfWindow = Math.max(0, Number(global.TSG_PRIMER_WINDOW || 800));
    return {
      start: Math.max(1, breakpoint - halfWindow),
      end: Math.min(GENOME_TOTAL, breakpoint + halfWindow),
      breakpoint: breakpoint
    };
  }

  function mapBreakpointPos(coord, windowRange) {
    var span = windowRange.end - windowRange.start + 1;
    return ((Number(coord) - windowRange.start) * TRACK_WIDTH) / span;
  }

  function breakpointPrimerBox(primer, windowRange) {
    var primerStart = Math.min(Number(primer.coord_start), Number(primer.coord_end));
    var primerEnd = Math.max(Number(primer.coord_start), Number(primer.coord_end));
    var clippedStart = Math.max(primerStart, windowRange.start);
    var clippedEnd = Math.min(primerEnd, windowRange.end);
    if (!isFinite(primerStart) || !isFinite(primerEnd) || clippedStart > clippedEnd) {
      return null;
    }
    var pxPerNt = TRACK_WIDTH / (windowRange.end - windowRange.start + 1);
    return {
      left: (clippedStart - windowRange.start) * pxPerNt,
      width: (clippedEnd - clippedStart + 1) * pxPerNt,
      lengthNt: primerEnd - primerStart + 1,
      clippedStart: clippedStart,
      clippedEnd: clippedEnd
    };
  }

  function primerPairInfo(name) {
    var raw = String(name || '');
    var match = raw.match(/(\d+)_(LEFT|RIGHT)(_alt\w*)?/i);
    if (!match) {
      return { key: raw, label: raw, num: 99999, isAlt: false };
    }
    var num = parseInt(match[1], 10);
    var isAlt = !!match[3];
    return {
      key: String(num) + (isAlt ? '_alt' : ''),
      label: isAlt ? String(num) + ' alt' : String(num),
      num: num,
      isAlt: isAlt
    };
  }

  function groupPrimersByPair(primers) {
    var groups = {};
    (primers || []).forEach(function (primer) {
      var info = primerPairInfo(primer.primer_name);
      if (!groups[info.key]) {
        groups[info.key] = { info: info, primers: [] };
      }
      groups[info.key].primers.push(primer);
    });
    return Object.keys(groups).map(function (key) {
      return groups[key];
    }).sort(function (a, b) {
      if (a.info.num !== b.info.num) {
        return a.info.num - b.info.num;
      }
      if (a.info.isAlt !== b.info.isAlt) {
        return a.info.isAlt ? 1 : -1;
      }
      return a.info.key.localeCompare(b.info.key);
    });
  }

  function primerLayout() {
    return global.TSG_PRIMER_LAYOUT === 'compact' ? 'compact' : 'detailed';
  }

  function laneHeightPx() {
    return primerLayout() === 'compact' ? COMPACT_LANE_HEIGHT : PAIR_LANE_HEIGHT;
  }

  function arrowTopForLane(lane) {
    var h = laneHeightPx();
    return lane * h + Math.max(0, (h - ARROW_HEIGHT) / 2);
  }

  function arrowCenterY(lane) {
    return arrowTopForLane(lane) + ARROW_HEIGHT / 2;
  }

  function isLeftPrimer(primer) {
    var name = String(primer.primer_name || '');
    if (/RIGHT/i.test(name)) {
      return false;
    }
    if (/LEFT/i.test(name)) {
      return true;
    }
    return String(primer.direction).toUpperCase() === 'R';
  }

  function renderPairConnectors(track, laidOut, color) {
    var lefts = [];
    var rights = [];
    laidOut.forEach(function (item) {
      if (isLeftPrimer(item.primer)) {
        lefts.push(item);
      } else {
        rights.push(item);
      }
    });

    function draw(x1, x2, yPos) {
      var a = Math.max(0, Math.min(x1, x2));
      var b = Math.min(TRACK_WIDTH, Math.max(x1, x2));
      if (b - a < 1) {
        return;
      }
      var line = document.createElement('span');
      line.className = 'tsg-pair-connector';
      line.style.left = a + 'px';
      line.style.width = (b - a) + 'px';
      line.style.top = yPos + 'px';
      line.style.setProperty('--primer-color', color);
      track.appendChild(line);
    }

    if (lefts.length && rights.length) {
      lefts.forEach(function (leftItem, i) {
        var rightItem = rights[Math.min(i, rights.length - 1)];
        var yPos = (arrowCenterY(leftItem.lane) + arrowCenterY(rightItem.lane)) / 2;
        draw(leftItem.box.left + leftItem.box.width, rightItem.box.left, yPos);
      });
      for (var extra = lefts.length; extra < rights.length; extra++) {
        draw(0, rights[extra].box.left, arrowCenterY(rights[extra].lane));
      }
      return;
    }
    lefts.forEach(function (leftItem) {
      draw(leftItem.box.left + leftItem.box.width, TRACK_WIDTH, arrowCenterY(leftItem.lane));
    });
    rights.forEach(function (rightItem) {
      draw(0, rightItem.box.left, arrowCenterY(rightItem.lane));
    });
  }

  function renderPrimerArrows(track, laidOut, color, breakpointKind) {
    laidOut.forEach(function (item) {
      var primer = item.primer;
      var info = primerPairInfo(primer.primer_name);
      var arrow = document.createElement('span');
      var direction = String(primer.direction).toUpperCase() === 'L' ? 'left' : 'right';
      arrow.className = 'tsg-primer-arrow tsg-primer-arrow-' + direction;
      arrow.style.left = item.box.left + 'px';
      arrow.style.top = arrowTopForLane(item.lane) + 'px';
      arrow.style.width = item.box.width + 'px';
      arrow.style.setProperty('--primer-color', color);
      arrow.setAttribute('aria-label', primer.primer_name);
      arrow.setAttribute('data-pair', info.key);
      arrow.setAttribute('data-length-nt', item.box.lengthNt);
      arrow.setAttribute('data-breakpoint-kind', breakpointKind);
      arrow.title = primer.primer_name + ' | pair ' + info.label + ' | ' +
        primer.coord_start + '\u2013' + primer.coord_end +
        ' (' + item.box.lengthNt + ' nt) | points ' + direction +
        (primer.pool_name ? ' | pool ' + primer.pool_name : '');
      track.appendChild(arrow);
    });
  }

  function appendPairTrack(row, laidOut, color, windowRange, pairLabelText, breakpointKind, skipConnectors) {
    if (!laidOut.length) {
      return;
    }
    var h = laneHeightPx();
    var laneCount = laidOut.reduce(function (maxLane, item) {
      return Math.max(maxLane, item.lane + 1);
    }, 1);
    var track = document.createElement('div');
    track.className = 'tsg-primer-track tsg-pair-track';
    track.style.height = Math.max(h, laneCount * h) + 'px';
    renderBorderGuide(track, windowRange);
    if (pairLabelText && primerLayout() !== 'compact') {
      var pairLabel = document.createElement('span');
      pairLabel.className = 'tsg-pair-label';
      pairLabel.textContent = pairLabelText;
      track.appendChild(pairLabel);
    }
    if (!skipConnectors) {
      renderPairConnectors(track, laidOut, color);
    }
    renderPrimerArrows(track, laidOut, color, breakpointKind);
    row.appendChild(track);
  }

  function layoutPrimerLanes(primers, windowRange) {
    var lanes = [];
    var laidOut = [];
    (primers || []).forEach(function (primer) {
      var box = breakpointPrimerBox(primer, windowRange);
      if (!box) {
        return;
      }
      var lane = 0;
      while (lane < lanes.length && box.left <= lanes[lane] + 4) {
        lane += 1;
      }
      if (lane === lanes.length) {
        lanes.push(-1);
      }
      lanes[lane] = box.left + box.width;
      laidOut.push({ primer: primer, box: box, lane: lane });
    });
    return laidOut;
  }

  function layoutCompactPairs(primers, windowRange) {
    var prepared = [];
    groupPrimersByPair(primers).forEach(function (group) {
      var items = [];
      group.primers.forEach(function (primer) {
        var box = breakpointPrimerBox(primer, windowRange);
        if (box) {
          items.push({ primer: primer, box: box });
        }
      });
      if (!items.length) {
        return;
      }
      var hasLeft = false;
      var hasRight = false;
      var minX = TRACK_WIDTH;
      var maxX = 0;
      items.forEach(function (item) {
        if (isLeftPrimer(item.primer)) {
          hasLeft = true;
        } else {
          hasRight = true;
        }
        minX = Math.min(minX, item.box.left);
        maxX = Math.max(maxX, item.box.left + item.box.width);
      });
      if (hasLeft && !hasRight) {
        maxX = TRACK_WIDTH;
      }
      if (hasRight && !hasLeft) {
        minX = 0;
      }
      prepared.push({
        items: items,
        minX: minX,
        maxX: maxX,
        num: group.info.num,
        isAlt: group.info.isAlt
      });
    });
    prepared.sort(function (a, b) {
      if (a.minX !== b.minX) {
        return a.minX - b.minX;
      }
      if (a.num !== b.num) {
        return a.num - b.num;
      }
      if (a.isAlt !== b.isAlt) {
        return a.isAlt ? 1 : -1;
      }
      return 0;
    });
    var lanes = [];
    var laidOut = [];
    prepared.forEach(function (pair) {
      var lane = 0;
      while (lane < lanes.length && pair.minX <= lanes[lane] + 4) {
        lane += 1;
      }
      if (lane === lanes.length) {
        lanes.push(-1);
      }
      lanes[lane] = pair.maxX;
      pair.items.forEach(function (item) {
        laidOut.push({ primer: item.primer, box: item.box, lane: lane });
      });
    });
    return laidOut;
  }

  function renderBorderGuide(track, windowRange) {
    var guide = document.createElement('span');
    guide.className = 'tsg-primer-border-guide';
    guide.style.left = mapBreakpointPos(windowRange.breakpoint, windowRange) + 'px';
    track.appendChild(guide);
  }

  function renderBreakpointAxis(container, windowRange) {
    var axis = document.createElement('div');
    axis.className = 'tsg-breakpoint-axis';
    var start = document.createElement('span');
    start.className = 'tsg-axis-start';
    start.textContent = windowRange.start;
    axis.appendChild(start);
    var center = document.createElement('span');
    center.className = 'tsg-axis-breakpoint';
    center.style.left = mapBreakpointPos(windowRange.breakpoint, windowRange) + 'px';
    center.textContent = windowRange.breakpoint;
    axis.appendChild(center);
    var end = document.createElement('span');
    end.className = 'tsg-axis-end';
    end.textContent = windowRange.end;
    axis.appendChild(end);
    container.appendChild(axis);
  }

  function renderSchemeBreakpointRow(container, scheme, primers, windowRange, breakpointKind) {
    var code = String(scheme.code);
    var row = document.createElement('div');
    row.className = 'tsg-primer-row';

    var label = document.createElement('div');
    label.className = 'tsg-primer-row-label';
    label.innerHTML = '<span class="tsg-scheme-swatch" style="background:' +
      (SCHEME_COLORS[code] || '#555') + '"></span>' + esc(scheme.label) +
      ' <span class="text-muted">(' + primers.length + ' in window)</span>';
    row.appendChild(label);

    if (!primers.length) {
      var empty = document.createElement('div');
      empty.className = 'tsg-primer-empty';
      empty.textContent = 'No primers in this breakpoint window.';
      row.appendChild(empty);
      container.appendChild(row);
      return;
    }

    var color = SCHEME_COLORS[code] || '#555';
    if (primerLayout() === 'compact') {
      var compactLaid = layoutCompactPairs(primers, windowRange);
      appendPairTrack(row, compactLaid, color, windowRange, '', breakpointKind, true);
      var compactTrack = row.lastChild;
      groupPrimersByPair(primers).forEach(function (group) {
        var groupLaid = compactLaid.filter(function (item) {
          return primerPairInfo(item.primer.primer_name).key === group.info.key;
        });
        if (groupLaid.length && compactTrack) {
          renderPairConnectors(compactTrack, groupLaid, color);
        }
      });
    } else {
      groupPrimersByPair(primers).forEach(function (group) {
        appendPairTrack(row, layoutPrimerLanes(group.primers, windowRange), color, windowRange, group.info.label, breakpointKind, false);
      });
    }
    container.appendChild(row);
  }

  function renderBreakpointPanel(container, entry, breakpointKind, breakpoint, all, selected) {
    var windowRange = breakpointWindow(breakpoint);
    var panel = document.createElement('div');
    panel.className = 'tsg-breakpoint-panel tsg-breakpoint-' + breakpointKind;

    var title = document.createElement('div');
    title.className = 'tsg-breakpoint-title';
    var kindLabel = 'Right breakpoint';
    if (breakpointKind === 'left') {
      kindLabel = 'Left breakpoint';
    } else if (breakpointKind === 'snv') {
      kindLabel = 'SNV position';
    }
    title.innerHTML = '<strong>' + esc(kindLabel) +
      ': ' + esc(breakpoint) + '</strong> &nbsp;|&nbsp; window ' + esc(windowRange.start) +
      '\u2013' + esc(windowRange.end) + ' (\u00b1' + Number(global.TSG_PRIMER_WINDOW || 800) + ' nt)';
    panel.appendChild(title);
    renderBreakpointAxis(panel, windowRange);

    selected.forEach(function (scheme) {
      var code = String(scheme.code);
      var primers = all.filter(function (primer) {
        return String(primer.scheme_code) === code &&
          Number(primer.coord_end) >= windowRange.start && Number(primer.coord_start) <= windowRange.end;
      });
      renderSchemeBreakpointRow(panel, scheme, primers, windowRange, breakpointKind);
    });
    container.appendChild(panel);
  }

  function renderBreakpointPanels(container, entry) {
    var selected = global.TSG_SELECTED_SCHEMES || [];
    if (!selected.length) {
      var none = document.createElement('div');
      none.className = 'tsg-primer-none';
      none.textContent = 'Primer arrows hidden (no schemes selected).';
      container.appendChild(none);
      return;
    }

    var all = (global.TSG_PRIMERS && global.TSG_PRIMERS[String(entry.id)]) || [];
    var section = document.createElement('div');
    section.className = 'tsg-primer-section tsg-layout-' + primerLayout();
    var heading = document.createElement('div');
    heading.className = 'tsg-breakpoint-section-title';
    heading.textContent = 'Primer breakpoint views (primers drawn to scale)';
    section.appendChild(heading);
    renderBreakpointPanel(section, entry, 'left', Number(entry.coord_left), all, selected);
    renderBreakpointPanel(section, entry, 'right', Number(entry.coord_right), all, selected);

    var note = document.createElement('div');
    note.className = 'tsg-primer-direction-note';
    note.textContent = primerLayout() === 'compact'
      ? 'Compact view: pair numbers hidden. Each pair stays on its own stripe; non-overlapping pairs share a row. Pale lines connect LEFT/RIGHT mates; if a mate is offscreen the line runs to the edge of the track.'
      : 'Detailed view: each LEFT/RIGHT pair is on its own line (alts on a separate line). Pale lines connect mates; if a mate is offscreen the line runs to the edge of the track. Arrow direction: LEFT / + points right; RIGHT / \u2212 points left. The dashed guide marks the breakpoint.';
    section.appendChild(note);
    container.appendChild(section);
  }

  function renderStructureTrack(container, entry) {
    var from = Number(entry.coord_from);
    var left = Number(entry.coord_left);
    var right = Number(entry.coord_right);
    var to = Number(entry.coord_to);
    var gLen = gapLength(left, right);

    var block = document.createElement('div');
    block.className = 'tsg-structure-block';

    var t = document.createElement('div');
    t.className = 'tsg-row-title';
    var jk = entry.junction_kind ? String(entry.junction_kind).toUpperCase() : '';
    var kindPart = jk ? ', ' + jk : '';
    t.textContent = entry.name + ' (' + entry.subtype + kindPart + ', id ' + entry.id + ')';
    block.appendChild(t);

    var meta = document.createElement('div');
    meta.className = 'tsg-row-meta';
    var rep = entry.repeat_seq ? entry.repeat_seq : '—';
    meta.innerHTML =
      'from=' + esc(from) + ', left=' + esc(left) + ', right=' + esc(right) + ', to=' + esc(to) +
      ' &nbsp;|&nbsp; repeat: <code>' + esc(rep) + '</code><br>' +
      'Gap border (end seg1): <strong>' + esc(left) + '</strong> &nbsp;|&nbsp; Gap border (start seg2): <strong>' + esc(right) + '</strong> &nbsp;|&nbsp; ' +
      'Gap length (nt): <strong>' + esc(gLen) + '</strong>';
    block.appendChild(meta);

    var c5 = document.createElement('div');
    c5.className = 'tsg-coords-row';
    c5.innerHTML = '<span>5\'</span><span>3\'</span>';
    block.appendChild(c5);

    var track = document.createElement('div');
    track.className = 'tsg-track';

    var x0 = mapPos(from);
    var xL = mapPos(left);
    var xR = mapPos(right);
    var xT = mapPos(to);

    var seg1 = document.createElement('div');
    seg1.className = 'tsg-seg';
    seg1.style.marginLeft = x0 + 'px';
    seg1.style.width = Math.max(1, xL - x0) + 'px';
    track.appendChild(seg1);

    var gap = document.createElement('div');
    gap.className = 'tsg-gap';
    gap.style.marginLeft = xL + 'px';
    gap.style.width = Math.max(1, xR - xL) + 'px';
    track.appendChild(gap);

    var seg2 = document.createElement('div');
    seg2.className = 'tsg-seg';
    seg2.style.marginLeft = xR + 'px';
    seg2.style.width = Math.max(1, xT - xR) + 'px';
    track.appendChild(seg2);

    block.appendChild(track);

    var c3 = document.createElement('div');
    c3.className = 'tsg-coords-row';
    c3.innerHTML = '<span>1</span><span>29903</span>';
    block.appendChild(c3);

    renderBreakpointPanels(block, entry);

    if (left >= right) {
      var warn = document.createElement('div');
      warn.className = 'tsg-alert';
      warn.textContent = 'Warning: coord_left should be less than coord_right for a non-empty gap; check curated data.';
      block.appendChild(warn);
    }

    container.appendChild(block);
  }

  function showSelected() {
    var out = document.getElementById('tsg-viz-output');
    if (!out) {
      return;
    }
    out.innerHTML = '';

    var data = global.TSG_ENTRIES || {};
    var boxes = document.querySelectorAll('input[name="tsg_sel[]"]:checked');
    if (!boxes.length) {
      out.innerHTML = '<div class="tsg-alert">Select one or more rows, then click <strong>Show</strong>.</div>';
      return;
    }

    var frag = document.createElement('div');
    var header = document.createElement('h4');
    header.className = 'search-header';
    header.textContent = 'Selected two-segment structures (above genome)';
    frag.appendChild(header);

    var inner = document.createElement('div');
    inner.id = 'tsg-viz-inner';
    for (var i = 0; i < boxes.length; i++) {
      var id = boxes[i].value;
      var entry = data[id];
      if (!entry) {
        continue;
      }
      renderStructureTrack(inner, entry);
    }
    frag.appendChild(inner);
    renderGenomeBar(frag);
    out.appendChild(frag);
  }

  function showSnvWindow() {
    var out = document.getElementById('tsg-viz-output');
    if (!out) {
      return;
    }
    out.innerHTML = '';
    var coord = Number(global.TSG_SNV_COORD);
    if (!isFinite(coord) || coord < 1) {
      out.innerHTML = '<div class="tsg-alert">Missing or invalid SNV coordinate.</div>';
      return;
    }
    var selected = global.TSG_SELECTED_SCHEMES || [];
    var all = global.TSG_SNV_PRIMERS || [];
    var frag = document.createElement('div');
    frag.className = 'tsg-layout-' + primerLayout();
    var header = document.createElement('h4');
    header.className = 'search-header';
    header.textContent = 'Primers near SNV ' + coord + ' (\u00b1800 nt)';
    frag.appendChild(header);
    if (!selected.length) {
      var none = document.createElement('div');
      none.className = 'tsg-primer-none';
      none.textContent = 'Primer arrows hidden (no schemes selected).';
      frag.appendChild(none);
    } else {
      var section = document.createElement('div');
      section.className = 'tsg-primer-section tsg-layout-' + primerLayout();
      renderBreakpointPanel(section, { id: 'snv' }, 'snv', coord, all, selected);
      var note = document.createElement('div');
      note.className = 'tsg-primer-direction-note';
      note.textContent = 'Dashed guide is the SNV. Pale lines connect primer pairs; offscreen mates run to the edge of the track.';
      section.appendChild(note);
      frag.appendChild(section);
    }
    renderGenomeBar(frag);
    out.appendChild(frag);
  }

  function setPrimerLayout(layout, rerender) {
    global.TSG_PRIMER_LAYOUT = layout === 'compact' ? 'compact' : 'detailed';
    var detailed = document.getElementById('tsgLayoutDetailed');
    var compact = document.getElementById('tsgLayoutCompact');
    if (detailed) {
      detailed.checked = global.TSG_PRIMER_LAYOUT === 'detailed';
    }
    if (compact) {
      compact.checked = global.TSG_PRIMER_LAYOUT === 'compact';
    }
    var hidden = document.getElementById('tsgLayoutHidden');
    if (hidden) {
      hidden.value = global.TSG_PRIMER_LAYOUT;
    }
    try {
      var url = new URL(window.location.href);
      url.searchParams.set('layout', global.TSG_PRIMER_LAYOUT);
      window.history.replaceState({}, '', url.toString());
    } catch (ignore) {}
    if (rerender) {
      if (global.TSG_SNV_COORD) {
        showSnvWindow();
      } else if (document.querySelectorAll('input[name="tsg_sel[]"]:checked').length) {
        showSelected();
      }
    }
  }

  function bindLayoutToggle() {
    var params;
    try {
      params = new URLSearchParams(window.location.search);
      if (params.get('layout') === 'compact') {
        global.TSG_PRIMER_LAYOUT = 'compact';
      }
    } catch (ignore) {}
    var detailed = document.getElementById('tsgLayoutDetailed');
    var compact = document.getElementById('tsgLayoutCompact');
    if (detailed) {
      detailed.checked = primerLayout() !== 'compact';
      detailed.addEventListener('change', function () {
        setPrimerLayout(this.checked ? 'detailed' : 'compact', true);
      });
    }
    if (compact) {
      compact.checked = primerLayout() === 'compact';
      compact.addEventListener('change', function () {
        setPrimerLayout(this.checked ? 'compact' : 'detailed', true);
      });
    }
  }

  if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', bindLayoutToggle);
    } else {
      bindLayoutToggle();
    }
  }

  global.TwoSegmentViz = {
    showSelected: showSelected,
    showSnvWindow: showSnvWindow,
    setPrimerLayout: setPrimerLayout,
    mapPos: mapPos,
    breakpointWindow: breakpointWindow,
    breakpointPrimerBox: breakpointPrimerBox,
    primerPairInfo: primerPairInfo,
    layoutCompactPairs: layoutCompactPairs,
    GENOME_TOTAL: GENOME_TOTAL,
    TRACK_WIDTH: TRACK_WIDTH
  };
})(window);

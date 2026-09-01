/**
 * CanvasJS charts for JunctionGroupQuery.php.
 * One chart at a time; legend (color key) sits below the plot.
 */
(function (global) {
    var PALETTE = [
        '#6d78ad', '#df7970', '#4f81bc', '#c0504e', '#9bbb58',
        '#8064a2', '#4bacc6', '#f79646', '#2c4d75', '#772c2a',
        '#5f7530', '#4d3b62', '#276a7c', '#b65708', '#91afd7'
    ];

    function colorFor(name, i) {
        return PALETTE[i % PALETTE.length];
    }

    function seriesToColumn(series, type) {
        return (series || []).map(function (s, i) {
            return {
                type: type,
                name: s.name,
                color: colorFor(s.name, i),
                showInLegend: true,
                dataPoints: s.dataPoints || []
            };
        });
    }

    function emptyNote(containerId, message) {
        var el = document.getElementById(containerId);
        if (!el) {
            return;
        }
        el.innerHTML = '<p class="text-muted" style="padding:12px;">' + message + '</p>';
        var key = document.getElementById('jqColorKey');
        if (key) {
            key.innerHTML = '';
        }
    }

    function fillColorKey(series) {
        var key = document.getElementById('jqColorKey');
        if (!key) {
            return;
        }
        if (!series || !series.length) {
            key.innerHTML = '';
            return;
        }
        var html = '<div class="jq-color-key-title">Color key</div><ul class="jq-color-key-list">';
        series.forEach(function (s, i) {
            html += '<li><span class="jq-swatch" style="background:' + colorFor(s.name, i) +
                '"></span>' + String(s.name).replace(/</g, '&lt;') + '</li>';
        });
        html += '</ul>';
        key.innerHTML = html;
    }

    function renderOne(containerId, opts) {
        var series = opts.series || [];
        var hasPoints = series.some(function (s) {
            return s.dataPoints && s.dataPoints.length;
        });
        if (!hasPoints) {
            emptyNote(containerId, opts.empty || 'No data for the current filters.');
            return;
        }
        var el = document.getElementById(containerId);
        if (!el) {
            return;
        }
        el.innerHTML = '';
        var data = seriesToColumn(series, opts.type || 'column');
        var chart = new CanvasJS.Chart(containerId, {
            title: { text: opts.title || '', fontSize: 16 },
            theme: 'light2',
            animationEnabled: true,
            exportEnabled: true,
            toolTip: { shared: true },
            legend: {
                fontSize: 13,
                cursor: 'pointer',
                verticalAlign: 'bottom',
                horizontalAlign: 'center'
            },
            axisX: {
                labelAngle: opts.labelAngle || -35,
                labelFontSize: 11,
                interval: 1
            },
            axisY: {
                title: opts.yTitle || '% of samples',
                includeZero: true,
                labelFontSize: 12
            },
            data: data
        });
        chart.render();
        fillColorKey(data);
    }

    global.JunctionQueryCharts = {
        render: function (payload) {
            if (!payload || !global.CanvasJS) {
                return;
            }
            var type = payload.chartType || 'clustered';
            var block = payload.charts && payload.charts[type] ? payload.charts[type] : null;
            if (!block) {
                emptyNote('jqChartMain', 'Choose a chart type.');
                return;
            }
            renderOne('jqChartMain', {
                title: block.title,
                yTitle: block.yTitle,
                series: block.series,
                type: type === 'stacked' ? 'stackedColumn' : 'column',
                empty: block.empty
            });
        }
    };
})(window);

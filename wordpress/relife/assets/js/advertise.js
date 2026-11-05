/* Interaktívny graf (čistý SVG, bez knižníc): hover/dotyk/klávesnica, prepínač Mesačne/Spolu. */
(function () {
	'use strict';
	var root = document.querySelector('.js-chart');
	if (!root) { return; }
	var pts;
	try { pts = JSON.parse(root.getAttribute('data-points') || '[]'); } catch (e) { pts = []; }
	if (!pts.length) { return; }
	var unit = root.getAttribute('data-unit') || '';
	var area = root.querySelector('.js-chart-area');
	var totalEl = root.querySelector('.js-chart-total');
	var NS = 'http://www.w3.org/2000/svg';
	var mode = 'month';
	var fmt = function (n) { return n.toLocaleString('sk-SK'); };

	function series() {
		if (mode === 'month') { return pts.map(function (p) { return p.value; }); }
		var s = 0;
		return pts.map(function (p) { s += p.value; return s; });
	}
	function niceMax(v) {
		var p = Math.pow(10, Math.floor(Math.log10(v || 1)));
		var n = v / p;
		return (n <= 1 ? 1 : n <= 2 ? 2 : n <= 5 ? 5 : 10) * p;
	}
	function el(name, attrs) {
		var e = document.createElementNS(NS, name);
		for (var k in attrs) { e.setAttribute(k, attrs[k]); }
		return e;
	}

	var tip = document.createElement('div');
	tip.className = 'rl-chart__tip';
	tip.hidden = true;
	area.appendChild(tip);

	function draw(animate) {
		var old = area.querySelector('svg');
		if (old) { area.removeChild(old); }
		var w = Math.max(area.clientWidth, 280);
		var h = w < 520 ? 260 : 340;
		var m = { t: 16, r: 18, b: 34, l: 46 };
		var data = series();
		var max = niceMax(Math.max.apply(null, data) * 1.05);
		var iw = w - m.l - m.r, ih = h - m.t - m.b;
		var x = function (i) { return m.l + (data.length === 1 ? iw / 2 : (iw * i) / (data.length - 1)); };
		var y = function (v) { return m.t + ih - (v / max) * ih; };
		var svg = el('svg', { viewBox: '0 0 ' + w + ' ' + h, width: w, height: h, role: 'img', 'aria-label': 'Graf' });

		for (var g = 0; g <= 4; g++) {
			var gv = (max / 4) * g, gy = y(gv);
			svg.appendChild(el('line', { x1: m.l, x2: w - m.r, y1: gy, y2: gy, class: 'rl-chart__grid' }));
			var t = el('text', { x: m.l - 8, y: gy + 4, 'text-anchor': 'end', class: 'rl-chart__ax' });
			t.textContent = gv >= 1000 ? (gv / 1000).toLocaleString('sk-SK') + ' tis.' : String(Math.round(gv));
			svg.appendChild(t);
		}
		pts.forEach(function (p, i) {
			var lx = el('text', { x: x(i), y: h - 10, 'text-anchor': i === 0 && data.length > 1 ? 'start' : i === data.length - 1 && data.length > 1 ? 'end' : 'middle', class: 'rl-chart__ax' });
			lx.textContent = p.label;
			svg.appendChild(lx);
		});

		var d = data.map(function (v, i) { return (i ? 'L' : 'M') + x(i).toFixed(1) + ' ' + y(v).toFixed(1); }).join(' ');
		var base = (h - m.b).toFixed(1);
		svg.appendChild(el('path', { d: d + ' L' + x(data.length - 1).toFixed(1) + ' ' + base + ' L' + x(0).toFixed(1) + ' ' + base + ' Z', class: 'rl-chart__area' }));
		var line = el('path', { d: d, class: 'rl-chart__line' });
		svg.appendChild(line);

		var dots = [];
		data.forEach(function (v, i) {
			var c = el('circle', { cx: x(i), cy: y(v), r: 5, class: 'rl-chart__dot', tabindex: 0, 'aria-label': pts[i].label + ': ' + fmt(v) + ' ' + unit });
			var show = function () {
				dots.forEach(function (o) { o.classList.remove('is-on'); });
				c.classList.add('is-on');
				tip.innerHTML = '<b>' + pts[i].label + '</b><span>' + fmt(v) + ' ' + unit + '</span>';
				tip.hidden = false;
				var tw = tip.offsetWidth;
				var left = Math.min(Math.max(x(i) - tw / 2, 4), w - tw - 4);
				tip.style.left = left + 'px';
				tip.style.top = Math.max(y(v) - tip.offsetHeight - 14, 0) + 'px';
			};
			var hide = function () { c.classList.remove('is-on'); tip.hidden = true; };
			c.addEventListener('mouseenter', show);
			c.addEventListener('focus', show);
			c.addEventListener('mouseleave', hide);
			c.addEventListener('blur', hide);
			c.addEventListener('click', show);
			dots.push(c);
			svg.appendChild(c);
			// väčšia plocha na dotyk
			var hit = el('circle', { cx: x(i), cy: y(v), r: 18, fill: 'transparent' });
			hit.addEventListener('mouseenter', show);
			hit.addEventListener('mouseleave', hide);
			hit.addEventListener('click', show);
			svg.appendChild(hit);
		});
		area.appendChild(svg);

		if (animate && !window.matchMedia('(prefers-reduced-motion: reduce)').matches && line.getTotalLength) {
			var len = line.getTotalLength();
			line.style.strokeDasharray = len;
			line.style.strokeDashoffset = len;
			line.getBoundingClientRect();
			line.style.transition = 'stroke-dashoffset .9s ease';
			line.style.strokeDashoffset = 0;
		}
	}

	root.querySelectorAll('.rl-seg__btn').forEach(function (b) {
		b.addEventListener('click', function () {
			mode = b.getAttribute('data-mode');
			root.querySelectorAll('.rl-seg__btn').forEach(function (o) {
				var on = o === b;
				o.classList.toggle('is-on', on);
				o.setAttribute('aria-pressed', on ? 'true' : 'false');
			});
			tip.hidden = true;
			draw(true);
		});
	});

	if (totalEl) { totalEl.textContent = fmt(pts.reduce(function (s, p) { return s + p.value; }, 0)); }
	var timer;
	window.addEventListener('resize', function () { clearTimeout(timer); timer = setTimeout(function () { draw(false); }, 150); });
	draw(true);
})();

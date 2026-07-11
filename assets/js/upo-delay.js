/**
 * Organic Kratom USA Performance Optimizer — delayed script loader.
 *
 * Scripts rewritten to type="upo-delayed-script" are executed on the first user
 * interaction (or after an optional fallback timeout). External scripts load
 * sequentially so dependency order is preserved.
 *
 * @package UPO
 */
(function () {
	'use strict';

	var config = window.upoDelay || {};
	var triggered = false;
	var events = [
		'keydown',
		'mousedown',
		'mousemove',
		'touchstart',
		'touchmove',
		'wheel',
		'scroll'
	];

	/**
	 * Recreate a delayed <script> as a live, executable one.
	 *
	 * @param {HTMLScriptElement} oldScript Placeholder script.
	 * @param {Function}          done      Callback when this script is ready.
	 */
	function activate(oldScript, done) {
		var script = document.createElement('script');
		var i;

		for (i = 0; i < oldScript.attributes.length; i++) {
			var attr = oldScript.attributes[i];
			if (attr.name === 'type' || attr.name === 'data-upo-src') {
				continue;
			}
			script.setAttribute(attr.name, attr.value);
		}

		var src = oldScript.getAttribute('data-upo-src');

		if (src) {
			script.src = src;
			script.onload = script.onerror = function () {
				done();
			};
			if (oldScript.parentNode) {
				oldScript.parentNode.removeChild(oldScript);
			}
			document.body.appendChild(script);
			// done() is invoked by onload/onerror above.
		} else {
			script.text = oldScript.text || oldScript.textContent || '';
			if (oldScript.parentNode) {
				oldScript.parentNode.replaceChild(script, oldScript);
			}
			done();
		}
	}

	/**
	 * Load every delayed script in document order.
	 */
	function loadAll() {
		if (triggered) {
			return;
		}
		triggered = true;

		events.forEach(function (evt) {
			window.removeEventListener(evt, loadAll, { passive: true });
		});

		var scripts = Array.prototype.slice.call(
			document.querySelectorAll('script[type="upo-delayed-script"]')
		);
		var index = 0;

		function next() {
			if (index >= scripts.length) {
				finish();
				return;
			}
			activate(scripts[index++], next);
		}

		next();
	}

	/**
	 * Re-dispatch lifecycle events so libraries that hook them initialise.
	 */
	function finish() {
		try {
			document.dispatchEvent(new Event('DOMContentLoaded', { bubbles: true }));
		} catch (e) {}
		try {
			window.dispatchEvent(new Event('load'));
		} catch (e) {}
		try {
			document.dispatchEvent(new CustomEvent('upo:delayed-scripts-loaded'));
		} catch (e) {}
	}

	events.forEach(function (evt) {
		window.addEventListener(evt, loadAll, { passive: true });
	});

	if (config.timeout && config.timeout > 0) {
		window.setTimeout(loadAll, config.timeout * 1000);
	}
})();

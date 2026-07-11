/**
 * Organic Kratom USA Performance Optimizer — YouTube facade loader.
 *
 * Replaces the lightweight preview with the real iframe on click / keyboard
 * activation.
 *
 * @package UPO
 */
(function () {
	'use strict';

	/**
	 * Swap a facade element for the real YouTube player.
	 *
	 * @param {HTMLElement} el Facade element.
	 */
	function loadPlayer(el) {
		if (el.getAttribute('data-loaded') === '1') {
			return;
		}
		el.setAttribute('data-loaded', '1');

		var id = el.getAttribute('data-id');
		var host = el.getAttribute('data-host') || 'youtube.com';
		if (!id) {
			return;
		}

		var iframe = document.createElement('iframe');
		iframe.setAttribute('src', 'https://www.' + host + '/embed/' + id + '?autoplay=1&rel=0');
		iframe.setAttribute('frameborder', '0');
		iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
		iframe.setAttribute('allowfullscreen', '');
		iframe.setAttribute('title', 'YouTube video player');
		iframe.className = 'upo-yt__iframe';

		el.innerHTML = '';
		el.appendChild(iframe);
	}

	/**
	 * Preconnect to YouTube hosts the moment the user shows intent.
	 */
	function warmConnections() {
		var hosts = [
			'https://www.youtube.com',
			'https://www.google.com',
			'https://googleads.g.doubleclick.net',
			'https://static.doubleclick.net',
			'https://i.ytimg.com'
		];
		hosts.forEach(function (host) {
			var link = document.createElement('link');
			link.rel = 'preconnect';
			link.href = host;
			document.head.appendChild(link);
		});
	}

	function bind(el) {
		el.addEventListener('pointerover', warmConnections, { once: true });
		el.addEventListener('click', function () {
			loadPlayer(el);
		});
		el.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				loadPlayer(el);
			}
		});
	}

	function init() {
		var facades = document.querySelectorAll('.upo-yt');
		Array.prototype.forEach.call(facades, bind);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

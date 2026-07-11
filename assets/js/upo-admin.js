/**
 * Organic Kratom USA Performance Optimizer — admin behaviours.
 *
 * @package UPO
 */
(function ($) {
	'use strict';

	var cfg = window.upoAdmin || {};
	var i18n = cfg.i18n || {};

	/**
	 * Fire a guarded AJAX request.
	 *
	 * @param {Object}   data     Payload (action + params).
	 * @param {Function} onDone   Success callback (response.data).
	 * @param {Function} onAlways Always callback.
	 */
	function ajax(data, onDone, onAlways) {
		data.nonce = cfg.nonce;
		$.post(cfg.ajaxUrl, data)
			.done(function (res) {
				if (res && res.success) {
					onDone(res.data || {});
				} else {
					window.alert((res && res.data && res.data.message) || i18n.error);
				}
			})
			.fail(function () {
				window.alert(i18n.error);
			})
			.always(function () {
				if (onAlways) {
					onAlways();
				}
			});
	}

	function busy($btn, on) {
		if (on) {
			$btn.data('label', $btn.html()).prop('disabled', true).html('<span class="upo-spinner"></span> ' + (i18n.working || ''));
		} else {
			$btn.prop('disabled', false).html($btn.data('label'));
		}
	}

	$(function () {
		/* Animate the score gauge. */
		var $gauge = $('#upo-score-gauge');
		if ($gauge.length) {
			$gauge[0].style.setProperty('--upo-pct', parseInt($gauge.data('score'), 10) || 0);
		}

		/* Confirm destructive forms. */
		$('.upo-confirm').on('submit', function (e) {
			if (!window.confirm(i18n.confirm)) {
				e.preventDefault();
			}
		});

		/* Database: clean a single task. */
		$(document).on('click', '.upo-db-clean', function () {
			var $btn = $(this);
			var task = $btn.data('task');
			busy($btn, true);
			ajax({ action: 'upo_db_task', task: task }, function (data) {
				if (data.report) {
					$.each(data.report, function (key, val) {
						$('.upo-num[data-count="' + key + '"]').text(val);
					});
				}
			}, function () {
				busy($btn, false);
			});
		});

		/* Database: optimize tables. */
		$('#upo-db-optimize').on('click', function () {
			var $btn = $(this);
			busy($btn, true);
			ajax({ action: 'upo_db_optimize' }, function (data) {
				window.alert(data.message || i18n.done);
			}, function () {
				busy($btn, false);
			});
		});

		/* Diagnostics: recalculate plugin sizes. */
		$('#upo-refresh-sizes').on('click', function () {
			var $btn = $(this);
			busy($btn, true);
			ajax({ action: 'upo_refresh_plugin_sizes' }, function (data) {
				var $body = $('#upo-plugin-sizes tbody').empty();
				(data.rows || []).forEach(function (row) {
					$body.append('<tr><td>' + escapeHtml(row.name) + '</td><td class="upo-num">' + escapeHtml(row.size) + '</td></tr>');
				});
			}, function () {
				busy($btn, false);
			});
		});

		/* Diagnostics: scan for missing alt text. */
		$('#upo-scan-alt').on('click', function () {
			var $btn = $(this);
			busy($btn, true);
			ajax({ action: 'upo_scan_alt' }, function (data) {
				var rows = data.rows || [];
				var $out = $('#upo-alt-results').empty();
				if (!rows.length) {
					$out.html('<p class="upo-good-msg"><span class="dashicons dashicons-yes-alt"></span> ' + escapeHtml(i18n.done) + '</p>');
					return;
				}
				var html = '<table class="upo-table"><thead><tr><th>Post</th><th class="upo-num">Missing</th></tr></thead><tbody>';
				rows.forEach(function (r) {
					var link = r.edit ? '<a href="' + escapeAttr(r.edit) + '">' + escapeHtml(r.title) + '</a>' : escapeHtml(r.title);
					html += '<tr><td>' + link + '</td><td class="upo-num">' + escapeHtml(String(r.missing)) + '</td></tr>';
				});
				html += '</tbody></table>';
				$out.html(html);
			}, function () {
				busy($btn, false);
			});
		});
	});

	function escapeHtml(str) {
		return String(str == null ? '' : str).replace(/[&<>"']/g, function (c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c];
		});
	}

	function escapeAttr(str) {
		return escapeHtml(str);
	}
})(jQuery);

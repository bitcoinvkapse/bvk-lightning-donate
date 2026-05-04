(function () {
	'use strict';

	if (typeof window.bvkldConfig === 'undefined') {
		return;
	}

	var cfg = window.bvkldConfig;
	var i18n = cfg.i18n || {};

	function emit(type, data) {
		try {
			if (typeof cfg.onEvent === 'function') {
				cfg.onEvent(type, data || {});
			}
		} catch (e) { /* no-op */ }
	}

	function isMobile() {
		var coarse = window.matchMedia && window.matchMedia('(pointer: coarse)').matches;
		var ua = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
		return coarse && ua;
	}

	function showToast(wrap, msg) {
		var toast = wrap.querySelector('.bvkld-toast');
		if (!toast) return;
		toast.textContent = msg;
		toast.hidden = false;
		toast.classList.add('bvkld-toast-visible');
		clearTimeout(toast._bvkldTimer);
		toast._bvkldTimer = setTimeout(function () {
			toast.classList.remove('bvkld-toast-visible');
			setTimeout(function () { toast.hidden = true; }, 300);
		}, 1800);
	}

	function copyToClipboard(text) {
		if (navigator.clipboard && navigator.clipboard.writeText) {
			return navigator.clipboard.writeText(text);
		}
		return new Promise(function (resolve, reject) {
			try {
				var ta = document.createElement('textarea');
				ta.value = text;
				ta.style.position = 'fixed';
				ta.style.opacity = '0';
				document.body.appendChild(ta);
				ta.select();
				document.execCommand('copy');
				document.body.removeChild(ta);
				resolve();
			} catch (e) {
				reject(e);
			}
		});
	}

	function showError(wrap, msg) {
		var err = wrap.querySelector('.bvkld-error');
		if (!err) return;
		err.textContent = msg;
		err.hidden = false;
	}

	function clearError(wrap) {
		var err = wrap.querySelector('.bvkld-error');
		if (err) {
			err.hidden = true;
			err.textContent = '';
		}
	}

	function setLoading(wrap, loading) {
		var btn = wrap.querySelector('.bvkld-send');
		if (!btn) return;
		btn.disabled = loading;
		var label = btn.querySelector('.bvkld-send-label');
		var loadingEl = btn.querySelector('.bvkld-send-loading');
		if (label) label.hidden = loading;
		if (loadingEl) loadingEl.hidden = !loading;
	}

	function getSelectedSats(wrap) {
		var active = wrap.querySelector('.bvkld-amt.bvkld-active');
		if (!active) return 0;
		var sats = parseInt(active.getAttribute('data-sats'), 10);
		if (sats > 0) return sats;
		var customInput = wrap.querySelector('.bvkld-custom-amount');
		if (customInput) {
			var v = parseInt(customInput.value, 10);
			if (v > 0) return v;
		}
		return 0;
	}

	function lnAddressToLnurl(address) {
		var parts = address.split('@');
		if (parts.length !== 2) return null;
		var user = parts[0];
		var domain = parts[1];
		return 'https://' + domain + '/.well-known/lnurlp/' + user;
	}

	function showQR(wrap, invoice) {
		var qrArea = wrap.querySelector('.bvkld-qr-area');
		var qrEl = wrap.querySelector('.bvkld-qr');
		var invoiceBtn = wrap.querySelector('.bvkld-invoice');
		if (!qrArea || !qrEl || !invoiceBtn) return;

		qrEl.innerHTML = '';
		new QRCode(qrEl, {
			text: invoice.toUpperCase(),
			width: 256,
			height: 256,
			correctLevel: QRCode.CorrectLevel.M
		});

		invoiceBtn.textContent = invoice;
		invoiceBtn.setAttribute('data-invoice', invoice);
		qrArea.hidden = false;
	}

	function hideQR(wrap) {
		var qrArea = wrap.querySelector('.bvkld-qr-area');
		var qrEl = wrap.querySelector('.bvkld-qr');
		if (qrArea) qrArea.hidden = true;
		if (qrEl) qrEl.innerHTML = '';
	}

	async function fetchInvoice(address, sats) {
		var lnurl = lnAddressToLnurl(address);
		if (!lnurl) throw new Error('Invalid Lightning address');

		var msats = sats * 1000;

		var res = await fetch(lnurl, { headers: { 'Accept': 'application/json' } });
		if (!res.ok) throw new Error('LNURL fetch failed');
		var params = await res.json();
		if (!params.callback) throw new Error('No callback URL');

		if (params.minSendable && msats < params.minSendable) {
			var e = new Error('range');
			e.code = 'range';
			throw e;
		}
		if (params.maxSendable && msats > params.maxSendable) {
			var er = new Error('range');
			er.code = 'range';
			throw er;
		}

		var sep = params.callback.indexOf('?') === -1 ? '?' : '&';
		var cbUrl = params.callback + sep + 'amount=' + msats;

		if (params.commentAllowed && params.commentAllowed > 0 && cfg.pageSlug) {
			var comment = String(cfg.pageSlug).slice(0, params.commentAllowed);
			cbUrl += '&comment=' + encodeURIComponent(comment);
		}

		var res2 = await fetch(cbUrl, { headers: { 'Accept': 'application/json' } });
		if (!res2.ok) throw new Error('Callback fetch failed');
		var data = await res2.json();
		if (!data.pr) throw new Error('No invoice in response');
		return data.pr;
	}

	async function sendSats(wrap) {
		clearError(wrap);
		hideQR(wrap);

		var sats = getSelectedSats(wrap);
		if (!sats || sats <= 0) {
			showError(wrap, i18n.errorAmount || 'Invalid amount');
			return;
		}

		emit('send_click', { sats: sats });
		setLoading(wrap, true);

		try {
			var invoice = await fetchInvoice(cfg.address, sats);
			emit('invoice_generated', { sats: sats });

			if (isMobile()) {
				window.location.href = 'lightning:' + invoice;
			} else {
				showQR(wrap, invoice);
			}
		} catch (err) {
			if (err && err.code === 'range') {
				showError(wrap, i18n.errorRange || 'Amount out of range');
			} else {
				showError(wrap, i18n.errorGeneric || 'Error');
			}
			emit('error', { sats: sats, message: String(err && err.message || err) });
		} finally {
			setLoading(wrap, false);
		}
	}

	function selectAmount(wrap, btn) {
		var all = wrap.querySelectorAll('.bvkld-amt');
		for (var i = 0; i < all.length; i++) {
			all[i].classList.remove('bvkld-active');
		}
		btn.classList.add('bvkld-active');

		var isCustom = btn.classList.contains('bvkld-custom-toggle');
		var customBox = wrap.querySelector('.bvkld-custom-input');
		if (customBox) {
			customBox.hidden = !isCustom;
			if (isCustom) {
				var input = customBox.querySelector('.bvkld-custom-amount');
				if (input) { input.focus(); }
			}
		}

		hideQR(wrap);
		clearError(wrap);

		var sats = parseInt(btn.getAttribute('data-sats'), 10) || 0;
		emit('amount_select', { sats: sats, custom: isCustom });
	}

	function attachCopy(wrap, btn, getValue) {
		if (!btn) return;
		btn.addEventListener('click', function () {
			var v = getValue();
			if (!v) return;
			copyToClipboard(v).then(function () {
				showToast(wrap, i18n.copied || 'Copied');
				emit('copy', { value: v });
			}).catch(function () {
				showToast(wrap, i18n.copyFailed || 'Copy failed');
			});
		});
	}

	function observeImpression(wrap) {
		if (!('IntersectionObserver' in window)) {
			emit('impression', {});
			return;
		}
		var seen = false;
		var obs = new IntersectionObserver(function (entries) {
			entries.forEach(function (e) {
				if (e.isIntersecting && !seen) {
					seen = true;
					emit('impression', {});
					obs.disconnect();
				}
			});
		}, { threshold: 0.3 });
		obs.observe(wrap);
	}

	function initWidget(wrap) {
		var amtButtons = wrap.querySelectorAll('.bvkld-amt');
		for (var i = 0; i < amtButtons.length; i++) {
			amtButtons[i].addEventListener('click', (function (btn) {
				return function () { selectAmount(wrap, btn); };
			})(amtButtons[i]));
		}

		var sendBtn = wrap.querySelector('.bvkld-send');
		if (sendBtn) {
			sendBtn.addEventListener('click', function () { sendSats(wrap); });
		}

		attachCopy(wrap, wrap.querySelector('.bvkld-invoice'), function () {
			var el = wrap.querySelector('.bvkld-invoice');
			return el ? el.getAttribute('data-invoice') : '';
		});

		attachCopy(wrap, wrap.querySelector('.bvkld-address'), function () {
			return cfg.address || '';
		});

		observeImpression(wrap);
	}

	function init() {
		var wraps = document.querySelectorAll('.bvkld-wrap');
		for (var i = 0; i < wraps.length; i++) {
			initWidget(wraps[i]);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

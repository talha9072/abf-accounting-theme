/**
 * Buy now pop-up (Figma "Buy Now / Company" and "Buy Now / Self Assessment").
 *
 * Any link with ?buy=<package> opens it (abf_accounting_buy_url()). Company services:
 * Find company → Select company → Your details → Verify → Order summary → Payment.
 * Self Assessment: Your details → Verify → Order summary → Payment. Logged-in customers skip
 * Verify. Back keeps what was typed; X or Esc closes. Server: inc/buy-now.php.
 */
(() => {
	const cfg = window.abfBuy;
	if (!cfg || !cfg.services || !cfg.services.length) {
		return;
	}

	const services = Object.fromEntries(cfg.services.map((s) => [s.key, s]));
	const loggedIn = Boolean(cfg.user);

	const state = {
		open: false,
		step: 'find',
		service: cfg.services[0].key,
		query: '',
		searched: '',
		results: null,
		company: null, // { name, number }
		name: cfg.user ? cfg.user.name : '',
		email: cfg.user ? cfg.user.email : '',
		phone: cfg.user ? cfg.user.phone : '',
		token: '',
		code: ['', '', '', ''],
		resendAt: 0,
		resendsLeft: 3,
		summary: null,
		terms: false,
		failedOrder: null, // { order, key, view } after an unsuccessful bank payment
		errors: {},
		message: '',
		notice: '',
		busy: false,
	};

	let root;
	let lastFocus;
	let timer;

	/* ---------------------------------------------------------------- */
	/* Helpers                                                          */
	/* ---------------------------------------------------------------- */

	const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
	const money = (n) => '£' + Number(n).toLocaleString('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
	const service = () => services[state.service];
	const isCompany = () => service().flow === 'company';

	/** UK mobile → "7400123456" (digits after +44), or ''. */
	const mobileDigits = (raw) => {
		let d = String(raw).replace(/\D/g, '');
		if (d.startsWith('0044')) {
			d = d.slice(4);
		} else if (d.startsWith('44')) {
			d = d.slice(2);
		}
		if (d.startsWith('0')) {
			d = d.slice(1);
		}
		return /^7\d{9}$/.test(d) ? d : '';
	};

	async function api(path, body) {
		const headers = { Accept: 'application/json' };
		if (body) {
			headers['Content-Type'] = 'application/json';
		}
		if (cfg.nonce && document.body.classList.contains('logged-in')) {
			headers['X-WP-Nonce'] = cfg.nonce;
		}
		let res;
		try {
			res = await fetch(cfg.api + path, { method: body ? 'POST' : 'GET', headers, body: body ? JSON.stringify(body) : undefined, credentials: 'same-origin' });
		} catch (e) {
			throw { message: 'We couldn’t connect. Please check your internet connection and try again.' };
		}
		const data = await res.json().catch(() => ({}));
		if (!res.ok) {
			throw { message: data.message || 'Something went wrong. Please try again.', data: data.data || {} };
		}
		return data;
	}

	/* ---------------------------------------------------------------- */
	/* Steps                                                            */
	/* ---------------------------------------------------------------- */

	function stages() {
		const list = [];
		if (isCompany()) {
			list.push(['company', 'Find company', ['find', 'select']]);
		}
		list.push(['details', 'Your details', ['details']]);
		if (!loggedIn) {
			list.push(['verify', 'Verify', ['verify']]);
		}
		list.push(['payment', 'Payment', ['summary', 'payment']]);
		return list;
	}

	function progress() {
		const list = stages();
		const current = list.findIndex((s) => s[2].includes(state.step));
		return (
			'<ol class="abf-buy__progress" aria-label="Progress">' +
			list
				.map(([, label], i) => {
					const cls = i < current ? 'is-done' : i === current ? 'is-current' : '';
					return `<li class="abf-buy__stage ${cls}"${i === current ? ' aria-current="step"' : ''}>${i < current ? '<span aria-hidden="true">✓ </span>' : ''}${esc(label)}${i < current ? '<span class="screen-reader-text"> (done)</span>' : ''}</li>`;
				})
				.join('') +
			'</ol>'
		);
	}

	const icons = {
		close: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true"><path d="M3 3l8 8M11 3l-8 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
		search: '<svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true"><circle cx="8" cy="8" r="5.5" stroke="currentColor" stroke-width="1.5"/><path d="M12.5 12.5L15 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
		lock: '<svg width="12" height="14" viewBox="0 0 12 14" fill="none" aria-hidden="true"><rect x="1" y="6" width="10" height="7" rx="1.5" fill="#E8A33D"/><path d="M3.5 6V4.5a2.5 2.5 0 0 1 5 0V6" stroke="#B9822C" stroke-width="1.4"/></svg>',
		chevron: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true"><path d="M3.5 5.5L7 9l3.5-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
	};

	const fieldError = (key) => (state.errors[key] ? `<p class="abf-buy__error" id="abf-buy-${key}-error">${esc(state.errors[key])}</p>` : '');
	const invalid = (key) => (state.errors[key] ? ` aria-invalid="true" aria-describedby="abf-buy-${key}-error"` : '');

	function serviceField() {
		// Unlisted services (Charity & CIC) only appear while they are the chosen one.
		const options = cfg.services.filter((s) => s.listed || s.key === state.service).map((s) => `<option value="${esc(s.key)}"${s.key === state.service ? ' selected' : ''}>${esc(s.label)}</option>`).join('');
		// The visible label wraps on phones (Figma); the native select sits on top, invisible.
		return `<div class="abf-buy__field"><label for="abf-buy-service">Service</label><div class="abf-buy__input abf-buy__input--select"><span class="abf-buy__select-label" aria-hidden="true">${esc(service().label)}</span><select id="abf-buy-service" data-bind="service">${options}</select>${icons.chevron}</div></div>`;
	}

	function textField(key, label, type, placeholder, autocomplete, extra = '') {
		return `<div class="abf-buy__field"><label for="abf-buy-${key}">${label}</label><div class="abf-buy__input${state.errors[key] ? ' is-invalid' : ''}">${extra}<input id="abf-buy-${key}" type="${type}" data-bind="${key}" value="${esc(state[key])}" placeholder="${esc(placeholder)}" autocomplete="${autocomplete}"${invalid(key)}></div>${fieldError(key)}</div>`;
	}

	const contactLink = (lead) => {
		const url = cfg.contact + (state.query ? '?company=' + encodeURIComponent(state.query) : '') + '#callback-form';
		return `<p class="abf-buy__aside">${lead} <a href="${esc(url)}">Contact us</a></p>`;
	};

	const actions = (back, next, nextLabel, opts = {}) =>
		`<div class="abf-buy__actions${back ? '' : ' is-single'}">` +
		(back ? `<button type="button" class="abf-buy__btn abf-buy__btn--ghost" data-action="${back}">${opts.backLabel || 'Back'}</button>` : '') +
		(next ? `<button type="${opts.submit ? 'submit' : 'button'}" class="abf-buy__btn"${opts.submit ? '' : ` data-action="${next}"`}${state.busy || opts.disabled ? ' disabled' : ''}>${state.busy ? '<span class="abf-buy__spinner" aria-hidden="true"></span>' : ''}${nextLabel}</button>` : '') +
		'</div>';

	const banner = () =>
		(state.message ? `<div class="abf-buy__alert" role="alert">${esc(state.message)}</div>` : '') +
		(state.notice ? `<div class="abf-buy__alert is-info" role="status">${esc(state.notice)}</div>` : '');

	const views = {
		find: () => `
			<h2 class="abf-buy__title" id="abf-buy-title">Find your company</h2>
			<p class="abf-buy__sub">Search by company name or number. We use Companies House records to find your company.</p>
			${banner()}
			<form class="abf-buy__form" data-submit="search" novalidate>
				${serviceField()}
				<div class="abf-buy__field"><label for="abf-buy-query">Company name or number</label><div class="abf-buy__input abf-buy__input--search${state.errors.query ? ' is-invalid' : ''}">${icons.search}<input id="abf-buy-query" type="search" data-bind="query" value="${esc(state.query)}" placeholder="Search company name or number" autocomplete="organization"${invalid('query')}></div>${fieldError('query')}</div>
				${actions('', 'search', 'Search', { submit: true })}
			</form>
			${contactLink('Can’t find your business?')}`,

		select: () => {
			const list = state.results || [];
			const body = list.length
				? `<div class="abf-buy__results" role="listbox" aria-label="Companies found">${list
						.map(
							(c) =>
								`<button type="button" role="option" class="abf-buy__result${state.company && state.company.number === c.number ? ' is-selected' : ''}" aria-selected="${state.company && state.company.number === c.number}" data-action="pick" data-number="${esc(c.number)}" data-name="${esc(c.name)}"><span class="abf-buy__result-name">${esc(c.name)}</span><span class="abf-buy__result-number">${esc(c.number)}</span></button>`
						)
						.join('')}</div><p class="abf-buy__note">Results come from Companies House.</p>`
				: `<div class="abf-buy__empty" role="status"><p class="abf-buy__empty-title">No companies found for “${esc(state.searched)}”</p><p>Check the spelling or search by your company number. New business? Contact us and we will help you get started.</p></div>`;
			return `
				<h2 class="abf-buy__title" id="abf-buy-title">Select your company</h2>
				${banner()}
				<form class="abf-buy__form" data-submit="search" novalidate>
					<div class="abf-buy__field"><label for="abf-buy-query">Company name or number</label><div class="abf-buy__input abf-buy__input--search is-filled">${icons.search}<input id="abf-buy-query" type="search" data-bind="query" value="${esc(state.query)}" placeholder="Search company name or number" autocomplete="organization"></div></div>
				</form>
				${body}
				${actions('back-find', list.length ? 'to-details' : '', 'Continue', { disabled: !state.company })}
				${contactLink(list.length ? 'Can’t see your company?' : 'Can’t find your business?')}`;
		},

		details: () => {
			const company = isCompany();
			return `
				<h2 class="abf-buy__title" id="abf-buy-title">${company ? 'Confirm your details' : 'Your details'}</h2>
				${company ? '' : '<p class="abf-buy__sub">Tell us who you are. We’ll send you a code to check it’s you.</p>'}
				${banner()}
				<form class="abf-buy__form" data-submit="details" novalidate>
					${company ? `<div class="abf-buy__field"><label for="abf-buy-company">Company name</label><div class="abf-buy__input is-readonly"><input id="abf-buy-company" type="text" value="${esc(state.company ? state.company.name : '')}" readonly></div></div>` : serviceField()}
					${textField('name', 'Full name', 'text', 'Your full name', 'name')}
					${textField('email', 'Email address', 'email', company ? 'name@company.co.uk' : 'name@example.co.uk', 'email')}
					${textField('phone', 'Contact number', 'tel', '7400 123456', 'tel-national', '<span class="abf-buy__prefix">+44</span>')}
					${actions(company ? 'back-select' : '', 'details', 'Next', { submit: true })}
				</form>`;
		},

		verify: () => {
			const wait = Math.max(0, Math.ceil((state.resendAt - Date.now()) / 1000));
			const resend =
				state.resendsLeft > 0
					? `<button type="button" class="abf-buy__link" data-action="resend"${wait ? ' disabled' : ''}>${wait ? `Resend code in ${wait}s` : 'Resend code'}</button>`
					: '<p class="abf-buy__note abf-buy__note--center">No more codes can be sent. Go back to start again, or call us.</p>';
			return `
				<h2 class="abf-buy__title" id="abf-buy-title">Verify it’s you</h2>
				<p class="abf-buy__sub">Enter the 4-digit code we sent to your ${cfg.sms ? 'email address and mobile number' : 'email address'}.</p>
				${banner()}
				<form class="abf-buy__form" data-submit="verify" novalidate>
					<fieldset class="abf-buy__code${state.errors.code ? ' is-invalid' : ''}"><legend class="screen-reader-text">4-digit code</legend>${state.code
						.map((d, i) => `<input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" ${i === 0 ? 'autocomplete="one-time-code"' : 'autocomplete="off"'} aria-label="Digit ${i + 1}" data-digit="${i}" value="${esc(d)}">`)
						.join('')}</fieldset>
					${fieldError('code')}
					<div class="abf-buy__resend" aria-live="polite">${resend}</div>
					<p class="abf-buy__note abf-buy__note--center">Having trouble? Call <a href="${esc(cfg.phoneUri)}">${esc(cfg.phone)}</a> and we will help you verify.</p>
					${actions('back-details', 'verify', 'Verify', { submit: true })}
				</form>`;
		},

		summary: () => {
			const s = service();
			const sum = state.summary || {};
			const company = sum.company || state.company;
			const head = company
				? `<p class="abf-buy__summary-name">${esc(company.name)}</p><p>Your order number will be shown after payment.</p>${company.address ? `<p>Company no. ${esc(company.number)} · Registered office: ${esc(company.address)}</p>` : `<p>Company no. ${esc(company.number)}</p>`}`
				: `<p class="abf-buy__summary-name">${esc(state.name)}</p><p>Your order number will be shown after payment.</p>`;
			return `
				<h2 class="abf-buy__title" id="abf-buy-title">Order summary</h2>
				${banner()}
				${sum.repeat ? `<div class="abf-buy__alert is-warning" role="status">This company already has an order for this service on ${esc(sum.repeat)}. Do you want to continue?</div>` : ''}
				<div class="abf-buy__summary">
					<div class="abf-buy__summary-head">${head}</div>
					<div class="abf-buy__summary-line"><span>${esc(s.name)} — ${esc(s.summary)}</span><strong>${money(s.price)}</strong></div>
					<div class="abf-buy__summary-total"><span>Total</span><strong>${money(s.price)}</strong></div>
				</div>
				${s.key === 'partnership-tax-return' ? '<p class="abf-buy__note">Covers the partnership’s SA800 return. Each partner’s own Self Assessment can be added, and additional fees may apply for complex returns.</p>' : s.flow === 'personal' ? '<p class="abf-buy__note">Additional fees may apply for complex returns, foreign income, capital gains and other specialist requirements.</p>' : ''}
				${actions(loggedIn ? 'back-details' : 'back-verify', 'to-payment', 'Pay by bank')}`;
		},

		payment: () => {
			const total = state.failedOrder ? state.failedOrder.view.total : service().price;
			return `
				<h2 class="abf-buy__title" id="abf-buy-title">Payment</h2>
				${state.failedOrder && state.failedOrder.failed ? '<div class="abf-buy__alert" role="alert"><strong>Your payment didn’t go through.</strong> Your bank did not approve the payment. You have not been charged. Please try again.</div>' : ''}
				${banner()}
				<form class="abf-buy__form" data-submit="pay" novalidate>
					<div class="abf-buy__pay">
						<div class="abf-buy__pay-method">
							<div class="abf-buy__option"><span class="abf-buy__radio" aria-hidden="true"></span><div><p class="abf-buy__option-title">Pay by bank</p><p>Pay securely from your bank account with GoCardless. You will approve the payment in your bank app. No card needed.</p></div></div>
							<p class="abf-buy__secure">${icons.lock}Secure bank payments by GoCardless</p>
						</div>
						<div class="abf-buy__total"><p>Your order</p><p class="abf-buy__total-amount">${money(total)}</p><p class="abf-buy__total-cur">GBP</p></div>
					</div>
					<label class="abf-buy__check${state.errors.terms ? ' is-invalid' : ''}"><input type="checkbox" data-bind="terms"${state.terms ? ' checked' : ''}${invalid('terms')}><span>I agree to the <a href="${esc(cfg.terms)}" target="_blank" rel="noopener">Terms &amp; Conditions for One-Off Services</a></span></label>
					${fieldError('terms')}
					${actions('cancel', 'pay', 'Continue to your bank', { submit: true, backLabel: 'Cancel' })}
				</form>`;
		},
	};

	/* ---------------------------------------------------------------- */
	/* Render                                                           */
	/* ---------------------------------------------------------------- */

	function build() {
		root = document.createElement('div');
		root.className = 'abf-buy';
		root.hidden = true;
		root.innerHTML = '<div class="abf-buy__overlay" data-action="close"></div><div class="abf-buy__modal" role="dialog" aria-modal="true" aria-labelledby="abf-buy-title"><div class="abf-buy__inner"></div></div>';
		document.body.appendChild(root);
		root.addEventListener('click', onClick);
		root.addEventListener('input', onInput);
		root.addEventListener('change', onInput);
		root.addEventListener('submit', onSubmit);
		root.addEventListener('keydown', onKeydown);
		root.addEventListener('paste', onPaste);
	}

	function render(focusFirst = true) {
		const inner = root.querySelector('.abf-buy__inner');
		inner.innerHTML = `<div class="abf-buy__top">${progress()}<button type="button" class="abf-buy__close" data-action="close" aria-label="Close">${icons.close}</button></div>${views[state.step]()}`;
		if (focusFirst) {
			const target = root.querySelector('[aria-invalid="true"]') || root.querySelector('[data-digit]:not([value]), [data-digit][value=""]') || root.querySelector('#abf-buy-query') || root.querySelector('.abf-buy__inner input:not([readonly]), .abf-buy__inner select') || root.querySelector('.abf-buy__title');
			if (target) {
				if (target.classList.contains('abf-buy__title')) {
					target.setAttribute('tabindex', '-1');
				}
				target.focus({ preventScroll: true });
			}
		}
		root.querySelector('.abf-buy__modal').scrollTop = 0;
	}

	function go(step) {
		state.step = step;
		state.errors = {};
		state.message = '';
		state.notice = '';
		render();
		if (step === 'verify') {
			tick();
		}
	}

	function tick() {
		clearInterval(timer);
		timer = setInterval(() => {
			if (state.step !== 'verify' || !state.open) {
				clearInterval(timer);
				return;
			}
			const btn = root.querySelector('[data-action="resend"]');
			const wait = Math.max(0, Math.ceil((state.resendAt - Date.now()) / 1000));
			if (btn) {
				btn.disabled = wait > 0;
				btn.textContent = wait ? `Resend code in ${wait}s` : 'Resend code';
			}
			if (!wait) {
				clearInterval(timer);
			}
		}, 1000);
	}

	function open(key) {
		if (!root) {
			build();
		}
		if (key && services[key] && key !== state.service) {
			setService(key, true);
		} else if (!state.open && state.step === 'payment' && !state.failedOrder) {
			state.step = 'summary';
		}
		if (!state.token && ['verify', 'summary', 'payment'].includes(state.step)) {
			state.step = isCompany() ? 'find' : 'details';
		}
		lastFocus = document.activeElement;
		state.open = true;
		root.hidden = false;
		document.documentElement.classList.add('abf-buy-open');
		render();
	}

	function close() {
		state.open = false;
		root.hidden = true;
		document.documentElement.classList.remove('abf-buy-open');
		clearInterval(timer);
		if (lastFocus && lastFocus.focus) {
			lastFocus.focus({ preventScroll: true });
		}
	}

	/** Change the service. Company ↔ Self Assessment swaps the steps; a company that doesn't fit is cleared. */
	function setService(key, fromButton = false) {
		const before = service();
		state.service = key;
		const after = service();
		state.token = '';
		state.summary = null;
		if (before.flow !== after.flow || before.key !== after.key) {
			// LLP / CIC / Charity & CIC / limited companies are searched separately.
			if (after.flow === 'company' && (before.flow !== 'company' || before.group !== after.group)) {
				state.company = null;
				state.results = null;
			}
		}
		if (after.flow === 'company') {
			state.step = state.company && !fromButton ? state.step : 'find';
			if (!['find', 'select', 'details'].includes(state.step)) {
				state.step = 'find';
			}
		} else {
			state.step = 'details';
		}
	}

	/* ---------------------------------------------------------------- */
	/* Events                                                           */
	/* ---------------------------------------------------------------- */

	function onInput(e) {
		const el = e.target;
		const bind = el.dataset.bind;
		if (bind === 'service' && e.type === 'change') {
			setService(el.value);
			state.errors = {};
			render(false);
			root.querySelector('#abf-buy-service')?.focus();
			return;
		}
		if (bind === 'terms') {
			state.terms = el.checked;
			if (el.checked && state.errors.terms) {
				delete state.errors.terms;
				el.removeAttribute('aria-invalid');
				el.closest('.abf-buy__check')?.classList.remove('is-invalid');
				root.querySelector('#abf-buy-terms-error')?.remove();
			}
			return;
		}
		if (bind) {
			state[bind] = el.value;
			if (state.errors[bind]) {
				delete state.errors[bind];
				el.removeAttribute('aria-invalid');
				el.closest('.abf-buy__input')?.classList.remove('is-invalid');
				el.closest('.abf-buy__field')?.querySelector('.abf-buy__error')?.remove();
			}
			return;
		}
		if (el.dataset.digit !== undefined && e.type === 'input') {
			const i = Number(el.dataset.digit);
			const digits = el.value.replace(/\D/g, '');
			if (digits.length > 1) {
				fillCode(digits, i);
				return;
			}
			state.code[i] = digits;
			el.value = digits;
			el.setAttribute('value', digits);
			if (digits && i < 3) {
				root.querySelector(`[data-digit="${i + 1}"]`).focus();
			}
			if (state.code.every(Boolean)) {
				submitVerify();
			}
		}
	}

	function fillCode(digits, from = 0) {
		digits
			.slice(0, 4 - from)
			.split('')
			.forEach((d, k) => {
				state.code[from + k] = d;
				const box = root.querySelector(`[data-digit="${from + k}"]`);
				box.value = d;
				box.setAttribute('value', d);
			});
		const next = state.code.findIndex((d) => !d);
		root.querySelector(`[data-digit="${next === -1 ? 3 : next}"]`).focus();
		if (state.code.every(Boolean)) {
			submitVerify();
		}
	}

	function onPaste(e) {
		const box = e.target.closest('[data-digit]');
		if (!box) {
			return;
		}
		const digits = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
		if (digits) {
			e.preventDefault();
			fillCode(digits, 0);
		}
	}

	function onKeydown(e) {
		if (e.key === 'Escape') {
			e.preventDefault();
			close();
			return;
		}
		if (e.key === 'Backspace' && e.target.dataset.digit !== undefined && !e.target.value) {
			const i = Number(e.target.dataset.digit);
			if (i > 0) {
				const prev = root.querySelector(`[data-digit="${i - 1}"]`);
				state.code[i - 1] = '';
				prev.value = '';
				prev.focus();
				e.preventDefault();
			}
		}
		if (e.key === 'Tab') {
			const focusable = [...root.querySelectorAll('button:not([disabled]), input:not([disabled]), select, a[href]')].filter((el) => el.offsetParent !== null);
			const first = focusable[0];
			const last = focusable[focusable.length - 1];
			if (e.shiftKey && document.activeElement === first) {
				e.preventDefault();
				last.focus();
			} else if (!e.shiftKey && document.activeElement === last) {
				e.preventDefault();
				first.focus();
			}
		}
	}

	function onClick(e) {
		const el = e.target.closest('[data-action]');
		if (!el) {
			return;
		}
		const action = el.dataset.action;
		const map = {
			close,
			cancel: close,
			'back-find': () => go('find'),
			'back-select': () => go(state.results ? 'select' : 'find'),
			'back-details': () => go('details'),
			'back-verify': () => go('verify'),
			pick: () => {
				state.company = { name: el.dataset.name, number: el.dataset.number };
				root.querySelectorAll('.abf-buy__result').forEach((r) => {
					const on = r === el;
					r.classList.toggle('is-selected', on);
					r.setAttribute('aria-selected', String(on));
				});
				const next = root.querySelector('[data-action="to-details"]');
				if (next) {
					next.disabled = false;
				}
			},
			'to-details': () => state.company && go('details'),
			resend: resend,
			'to-payment': () => go('payment'),
		};
		if (map[action]) {
			e.preventDefault();
			map[action]();
		}
	}

	function onSubmit(e) {
		e.preventDefault();
		if (state.busy) {
			return;
		}
		const handlers = { search, details: submitDetails, verify: submitVerify, pay };
		handlers[e.target.dataset.submit]?.();
	}

	async function run(fn) {
		state.busy = true;
		state.message = '';
		render(false);
		try {
			await fn();
		} catch (err) {
			state.message = err.silent ? '' : err.message || 'Something went wrong.';
			const data = err.data || {};
			if (data.fields) {
				state.errors = Object.fromEntries(Object.entries(data.fields).map(([k, v]) => [k, v === true ? state.message : v]));
				state.message = data.fields.terms ? '' : state.message;
			}
			if (data.restart) {
				state.token = '';
				state.code = ['', '', '', ''];
				state.step = 'details';
			}
			if (data.step === 'find') {
				state.step = 'find';
			}
			if (typeof data.resendIn === 'number') {
				state.resendAt = Date.now() + data.resendIn * 1000;
			}
			if (typeof data.resendsLeft === 'number') {
				state.resendsLeft = data.resendsLeft;
			}
		}
		state.busy = false;
		render(true);
	}

	/* ---------------------------------------------------------------- */
	/* Actions                                                          */
	/* ---------------------------------------------------------------- */

	function search() {
		const q = state.query.trim();
		if (q.length < 2) {
			state.errors = { query: 'Type at least 2 letters of your company name, or its number.' };
			render();
			return;
		}
		run(async () => {
			const data = await api(`companies?service=${encodeURIComponent(state.service)}&q=${encodeURIComponent(q)}`);
			state.results = data.results || [];
			state.searched = q;
			if (state.company && !state.results.some((c) => c.number === state.company.number)) {
				state.company = null;
			}
			if (state.results.length === 1) {
				state.company = state.results[0];
			}
			state.step = 'select';
		});
	}

	function submitDetails() {
		const errors = {};
		const name = state.name.trim().replace(/\s+/g, ' ');
		if (name.length < 2 || !name.includes(' ')) {
			errors.name = 'Please enter your first and last name.';
		}
		if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(state.email.trim())) {
			errors.email = 'Please enter a valid email address.';
		}
		const digits = mobileDigits(state.phone);
		if (!digits) {
			errors.phone = 'Please enter a UK mobile number.';
		}
		if (Object.keys(errors).length) {
			state.errors = errors;
			render();
			return;
		}
		run(async () => {
			const data = await api('code', {
				service: state.service,
				company: state.company ? state.company.number : '',
				name,
				email: state.email.trim(),
				phone: '+44' + digits,
			});
			state.token = data.token;
			state.code = ['', '', '', ''];
			state.errors = {};
			if (data.verified) {
				await loadSummary();
				state.step = 'summary';
				return;
			}
			state.resendAt = Date.now() + (data.resendIn || 60) * 1000;
			state.resendsLeft = data.resendsLeft ?? 3;
			if (cfg.sms && data.sent && !data.sent.sms) {
				state.notice = 'We’ve emailed your code. If a text doesn’t arrive, use the code from the email.';
			}
			state.step = 'verify';
			tick();
		});
	}

	function submitVerify() {
		if (!state.code.every(Boolean)) {
			state.errors = { code: 'Enter all 4 digits of your code.' };
			render();
			return;
		}
		run(async () => {
			try {
				await api('code/check', { token: state.token, code: state.code.join('') });
			} catch (err) {
				state.code = ['', '', '', ''];
				if (!(err.data || {}).restart) {
					state.errors = { code: err.message };
					err.silent = true;
				}
				throw err;
			}
			await loadSummary();
			state.step = 'summary';
		});
	}

	function resend() {
		run(async () => {
			const data = await api('code/resend', { token: state.token });
			state.resendAt = Date.now() + (data.resendIn || 60) * 1000;
			state.resendsLeft = data.resendsLeft ?? state.resendsLeft - 1;
			state.code = ['', '', '', ''];
			state.notice = 'We’ve sent you a new code.';
			tick();
		});
	}

	async function loadSummary() {
		state.summary = await api('summary', { token: state.token });
	}

	function pay() {
		if (!state.terms) {
			state.errors = { terms: 'Please agree to the Terms & Conditions to continue.' };
			render();
			return;
		}
		run(async () => {
			const data = state.failedOrder
				? await api('order/retry', { order: state.failedOrder.order, key: state.failedOrder.key, terms: true })
				: await api('order', { token: state.token, terms: true });
			state.busy = true;
			window.location.assign(data.redirect);
			await new Promise(() => {}); // keep the button busy while the bank page loads
		});
	}

	/* ---------------------------------------------------------------- */
	/* Start                                                            */
	/* ---------------------------------------------------------------- */

	/** ?buy=<package> links (any page, same site) open the pop-up instead of navigating. */
	document.addEventListener(
		'click',
		(e) => {
			if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
				return;
			}
			const a = e.target.closest('a[href*="buy="]');
			if (!a) {
				return;
			}
			const url = new URL(a.getAttribute('href'), window.location.href);
			const key = url.searchParams.get('buy');
			if (url.origin !== window.location.origin || !services[key]) {
				return;
			}
			e.preventDefault();
			state.failedOrder = null;
			open(key);
		},
		true
	);

	/** Contact page: "Contact us" from the pop-up pre-fills the message with the company name. */
	function prefillContact() {
		const company = new URLSearchParams(window.location.search).get('company');
		const box = document.getElementById('abf-message');
		if (company && box && !box.value) {
			box.value = `Company: ${company.slice(0, 120)}\nI couldn’t find my company in the Buy now search.`;
		}
	}

	async function fromUrl() {
		const params = new URLSearchParams(window.location.search);
		const buy = params.get('buy');
		if (!buy) {
			return;
		}
		const clean = new URL(window.location.href);
		['buy', 'order', 'key'].forEach((p) => clean.searchParams.delete(p));
		window.history.replaceState(null, '', clean.pathname + clean.search + clean.hash);

		if (buy === 'payment' && params.get('order') && params.get('key')) {
			try {
				const view = await api('order/status', { order: params.get('order'), key: params.get('key') });
				if (view.paid) {
					return;
				}
				state.failedOrder = { order: params.get('order'), key: params.get('key'), view, failed: true };
				if (services[view.service]) {
					state.service = view.service;
				}
				state.company = view.company;
				state.token = 'order';
				state.step = 'payment';
				open();
			} catch (e) {
				// Unknown order: nothing to show.
			}
			return;
		}
		if (services[buy]) {
			open(buy);
		}
	}

	prefillContact();
	fromUrl();
})();

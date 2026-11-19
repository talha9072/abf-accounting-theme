/**
 * Request a callback pop-up (Figma "Request a callback / Step 1 — Your details, Step 2 — Verify,
 * Step 3 — Thank you"). Every link to /contact/#callback-form opens it; without JS the link
 * still goes to the form on Contact. Looks come from buy-now.css (.abf-buy__*) plus
 * .abf-callback__* for the side panel and the extra fields. Server: inc/callback.php.
 */
(() => {
	const cfg = window.abfCallback;
	if (!cfg) {
		return;
	}
	const loggedIn = Boolean(cfg.user);

	const blank = () => ({
		step: 'details',
		name: cfg.user ? cfg.user.name : '',
		email: cfg.user ? cfg.user.email : '',
		phone: cfg.user ? cfg.user.phone : '',
		date: '',
		time: 'asap',
		message: '',
		website: '',
		token: '',
		code: ['', '', '', ''],
		resendAt: 0,
		resendsLeft: 3,
		done: null, // { first, when }
		errors: {},
		message_: '',
		notice: '',
		busy: false,
	});
	let state = blank();
	let open_ = false;
	let root;
	let lastFocus;
	let timer;
	let recaptchaLoading;

	const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

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
		const headers = { Accept: 'application/json', 'Content-Type': 'application/json' };
		if (cfg.nonce && document.body.classList.contains('logged-in')) {
			headers['X-WP-Nonce'] = cfg.nonce;
		}
		let res;
		try {
			res = await fetch(cfg.api + path, { method: 'POST', headers, body: JSON.stringify(body), credentials: 'same-origin' });
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
	/* reCAPTCHA v3 (only when its keys are saved in ABF settings)      */
	/* ---------------------------------------------------------------- */

	function loadRecaptcha() {
		if (!cfg.recaptcha || recaptchaLoading) {
			return recaptchaLoading;
		}
		recaptchaLoading = new Promise((resolve) => {
			const s = document.createElement('script');
			s.src = 'https://www.google.com/recaptcha/api.js?render=' + encodeURIComponent(cfg.recaptcha);
			s.async = true;
			s.onload = () => window.grecaptcha.ready(resolve);
			s.onerror = () => resolve();
			document.head.appendChild(s);
		});
		return recaptchaLoading;
	}

	async function recaptchaToken() {
		if (!cfg.recaptcha) {
			return '';
		}
		await loadRecaptcha();
		try {
			return await window.grecaptcha.execute(cfg.recaptcha, { action: 'callback' });
		} catch (e) {
			return '';
		}
	}

	/* ---------------------------------------------------------------- */
	/* Views                                                            */
	/* ---------------------------------------------------------------- */

	const icons = {
		close: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true"><path d="M3 3l8 8M11 3l-8 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
		chevron: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true"><path d="M3.5 5.5L7 9l3.5-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
	};

	function progress() {
		const list = [['details', 'Your details']];
		if (!loggedIn) {
			list.push(['verify', 'Verify']);
		}
		list.push(['done', 'Done']);
		const current = list.findIndex((s) => s[0] === state.step);
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

	const fieldError = (key) => (state.errors[key] ? `<p class="abf-buy__error" id="abf-cb-${key}-error">${esc(state.errors[key])}</p>` : '');
	const invalid = (key) => (state.errors[key] ? ` aria-invalid="true" aria-describedby="abf-cb-${key}-error"` : '');
	const wrap = (key, inner, extraClass = '') => `<div class="abf-buy__input${extraClass}${state.errors[key] ? ' is-invalid' : ''}">${inner}</div>`;

	function textField(key, label, type, placeholder, autocomplete, prefix = '') {
		return `<div class="abf-buy__field"><label for="abf-cb-${key}">${label}</label>${wrap(key, `${prefix}<input id="abf-cb-${key}" type="${type}" data-bind="${key}" value="${esc(state[key])}" placeholder="${esc(placeholder)}" autocomplete="${autocomplete}"${invalid(key)}>`)}${fieldError(key)}</div>`;
	}

	/** Slots still open on the chosen day (all of them on later days). */
	function openSlots() {
		return cfg.slots.filter((t) => state.date !== cfg.today || t > nowLondon());
	}

	/** "HH:MM" in London, from the server's clock plus the time the page has been open. */
	const loadedAt = Date.now();
	function nowLondon() {
		const [h, m] = cfg.now.split(':').map(Number);
		const mins = h * 60 + m + Math.floor((Date.now() - loadedAt) / 60000);
		return `${String(Math.floor(mins / 60) % 24).padStart(2, '0')}:${String(mins % 60).padStart(2, '0')}`;
	}

	function timeField() {
		const slots = openSlots();
		const options = ['<option value="asap"' + (state.time === 'asap' ? ' selected' : '') + '>As soon as possible</option>']
			.concat(slots.map((t) => `<option value="${t}"${t === state.time ? ' selected' : ''}>${t}</option>`))
			.join('');
		return `<div class="abf-buy__field"><label for="abf-cb-time">Preferred time</label>${wrap('time', `<select id="abf-cb-time" data-bind="time"${invalid('time')}>${options}</select>${icons.chevron}`, ' abf-callback__select')}${fieldError('time')}</div>`;
	}

	const banner = () =>
		(state.message_ ? `<div class="abf-buy__alert" role="alert">${esc(state.message_)}</div>` : '') +
		(state.notice ? `<div class="abf-buy__alert is-info" role="status">${esc(state.notice)}</div>` : '');

	const button = (label, opts = {}) =>
		`<button type="${opts.submit ? 'submit' : 'button'}" class="abf-buy__btn${opts.ghost ? ' abf-buy__btn--ghost' : ''}"${opts.action ? ` data-action="${opts.action}"` : ''}${state.busy && !opts.ghost ? ' disabled' : ''}>${state.busy && !opts.ghost ? '<span class="abf-buy__spinner" aria-hidden="true"></span>' : ''}${label}</button>`;

	const views = {
		details: () => `
			<h2 class="abf-buy__title" id="abf-cb-title">Request a callback</h2>
			<p class="abf-buy__sub">A free call with an ABF accountant, at a time that suits you.</p>
			${banner()}
			<form class="abf-buy__form" data-submit="details" novalidate>
				${textField('name', 'Full name', 'text', 'Your full name', 'name')}
				${textField('email', 'Email address', 'email', 'name@company.co.uk', 'email')}
				${textField('phone', 'UK mobile number', 'tel', '7400 123456', 'tel-national', '<span class="abf-buy__prefix">+44</span>')}
				<div class="abf-callback__row">
					<div class="abf-buy__field"><label for="abf-cb-date">Preferred date</label>${wrap('date', `<input id="abf-cb-date" type="date"${state.date ? '' : ' class="is-empty"'} data-bind="date" value="${esc(state.date)}" min="${cfg.today}" max="${cfg.maxDate}" placeholder="dd/mm/yyyy"${invalid('date')}>`, ' abf-callback__date')}${fieldError('date')}</div>
					${timeField()}
				</div>
				<div class="abf-buy__field"><label for="abf-cb-message">Message (optional)</label>${wrap('message', `<textarea id="abf-cb-message" data-bind="message" rows="3" maxlength="${cfg.maxChars}" placeholder="Tell us briefly what you need help with"${invalid('message')}>${esc(state.message)}</textarea>`, ' abf-callback__area')}${fieldError('message')}</div>
				<div class="abf-callback__hp" aria-hidden="true"><label for="abf-cb-website">Leave this empty</label><input id="abf-cb-website" type="text" data-bind="website" tabindex="-1" autocomplete="off"></div>
				<p class="abf-callback__consent">By continuing, you agree to our <a href="${esc(cfg.privacy)}" target="_blank" rel="noopener">Privacy Policy</a> and <a href="${esc(cfg.terms)}" target="_blank" rel="noopener">Terms of Service</a>. We only use your details to arrange your call.${cfg.recaptcha ? ' This form is protected by reCAPTCHA; Google’s <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Privacy Policy</a> and <a href="https://policies.google.com/terms" target="_blank" rel="noopener">Terms</a> apply.' : ''}</p>
				<div class="abf-buy__actions abf-callback__actions">${button(loggedIn ? 'Request callback' : 'Next', { submit: true })}</div>
			</form>`,

		verify: () => {
			const wait = Math.max(0, Math.ceil((state.resendAt - Date.now()) / 1000));
			const resend =
				state.resendsLeft > 0
					? `<button type="button" class="abf-buy__link" data-action="resend"${wait ? ' disabled' : ''}>${wait ? `Resend code in ${wait}s` : 'Resend code'}</button>`
					: '<p class="abf-buy__note">No more codes can be sent. Go back to start again, or call us.</p>';
			return `
				<h2 class="abf-buy__title" id="abf-cb-title">Verify it’s you</h2>
				<p class="abf-buy__sub">Enter the 4-digit code we sent to your ${cfg.sms ? 'email address and mobile number' : 'email address'}.</p>
				${banner()}
				<form class="abf-buy__form" data-submit="verify" novalidate>
					<fieldset class="abf-buy__code${state.errors.code ? ' is-invalid' : ''}"><legend class="screen-reader-text">4-digit code</legend>${state.code
						.map((d, i) => `<input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" ${i === 0 ? 'autocomplete="one-time-code"' : 'autocomplete="off"'} aria-label="Digit ${i + 1}" data-digit="${i}" value="${esc(d)}">`)
						.join('')}</fieldset>
					${fieldError('code')}
					<div class="abf-callback__resend" aria-live="polite">${resend}</div>
					<p class="abf-buy__note">Having trouble? Call <a href="${esc(cfg.phoneUri)}">${esc(cfg.phone)}</a> and we will help you verify.</p>
					<div class="abf-buy__actions">${button('Back', { ghost: true, action: 'back' })}${button('Verify', { submit: true })}</div>
				</form>`;
		},

		done: () => `
			<h2 class="abf-buy__title" id="abf-cb-title">Thank you, ${esc(state.done.first)}.</h2>
			<p class="abf-callback__confirm">We will call you ${esc(state.done.when)}.</p>
			<p class="abf-callback__note">We have also sent these details to your email. Need to change the time? Call <a href="${esc(cfg.phoneUri)}">${esc(cfg.phone)}</a>.</p>
			<div class="abf-buy__actions abf-callback__actions"><button type="button" class="abf-buy__btn" data-action="close">Close</button></div>`,
	};

	const side = () => `
		<aside class="abf-callback__side" aria-hidden="true">
			<img class="abf-callback__logo" src="${esc(cfg.logo)}" alt="" width="136" height="40">
			<p class="abf-callback__side-title">Speak to an accountant, not a call centre.</p>
			<ul class="abf-callback__points">
				<li>A qualified accountant calls you</li>
				<li>At a time that suits you</li>
				<li>No obligation, no pressure</li>
			</ul>
		</aside>`;

	/* ---------------------------------------------------------------- */
	/* Render                                                           */
	/* ---------------------------------------------------------------- */

	function build() {
		root = document.createElement('div');
		root.className = 'abf-buy abf-callback';
		root.hidden = true;
		root.innerHTML = `<div class="abf-buy__overlay" data-action="close"></div><div class="abf-buy__modal abf-callback__modal" role="dialog" aria-modal="true" aria-labelledby="abf-cb-title"><div class="abf-callback__layout"><div class="abf-buy__inner"></div>${side()}</div></div>`;
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
		root.classList.toggle('is-done', state.step === 'done');
		if (focusFirst) {
			const target =
				root.querySelector('[aria-invalid="true"]') ||
				root.querySelector('[data-digit]:not([value]), [data-digit][value=""]') ||
				(state.step === 'details' ? root.querySelector('#abf-cb-name') : null) ||
				root.querySelector('.abf-buy__title');
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
		state.message_ = '';
		state.notice = '';
		render();
		if (step === 'verify') {
			tick();
		}
	}

	function tick() {
		clearInterval(timer);
		timer = setInterval(() => {
			if (state.step !== 'verify' || !open_) {
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

	function open() {
		if (!root) {
			build();
		}
		if (state.step === 'done') {
			// A new request keeps who they are and starts again.
			const keep = { name: state.name, email: state.email, phone: state.phone };
			state = Object.assign(blank(), keep);
		}
		lastFocus = document.activeElement;
		open_ = true;
		root.hidden = false;
		document.documentElement.classList.add('abf-buy-open');
		render();
		loadRecaptcha();
	}

	function close() {
		open_ = false;
		root.hidden = true;
		document.documentElement.classList.remove('abf-buy-open');
		clearInterval(timer);
		if (lastFocus && lastFocus.focus) {
			lastFocus.focus({ preventScroll: true });
		}
	}

	/* ---------------------------------------------------------------- */
	/* Events                                                           */
	/* ---------------------------------------------------------------- */

	function clearError(el, key) {
		if (!state.errors[key]) {
			return;
		}
		delete state.errors[key];
		el.removeAttribute('aria-invalid');
		el.closest('.abf-buy__input')?.classList.remove('is-invalid');
		el.closest('.abf-buy__field')?.querySelector('.abf-buy__error')?.remove();
	}

	function onInput(e) {
		const el = e.target;
		const bind = el.dataset.bind;
		if (bind) {
			state[bind] = el.value;
			clearError(el, bind);
			if (bind === 'date') {
				el.classList.toggle('is-empty', !el.value);
			}
			if (bind === 'date' && e.type === 'change') {
				// Today: only slots still ahead; a time that has gone goes back to "As soon as possible".
				if (!openSlots().includes(state.time)) {
					state.time = 'asap';
				}
				const select = root.querySelector('#abf-cb-time');
				const field = select.closest('.abf-buy__field');
				const tmp = document.createElement('div');
				tmp.innerHTML = timeField();
				field.replaceWith(tmp.firstElementChild);
				if (state.errors.time) {
					delete state.errors.time;
				}
			}
			if (bind === 'time') {
				clearError(root.querySelector('#abf-cb-date'), 'date');
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
			const focusable = [...root.querySelectorAll('button:not([disabled]), input:not([disabled]):not([tabindex="-1"]), select, textarea, a[href]')].filter((el) => el.offsetParent !== null);
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
		const map = { close, back: () => go('details'), resend };
		if (map[el.dataset.action]) {
			e.preventDefault();
			map[el.dataset.action]();
		}
	}

	function onSubmit(e) {
		e.preventDefault();
		if (state.busy) {
			return;
		}
		({ details: submitDetails, verify: submitVerify })[e.target.dataset.submit]?.();
	}

	async function run(fn) {
		state.busy = true;
		state.message_ = '';
		render(false);
		try {
			await fn();
		} catch (err) {
			state.message_ = err.silent ? '' : err.message || 'Something went wrong.';
			const data = err.data || {};
			if (data.fields) {
				state.errors = data.fields;
			}
			if (data.restart) {
				state.token = '';
				state.code = ['', '', '', ''];
				state.step = 'details';
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
		if (state.date) {
			const day = new Date(state.date + 'T12:00:00');
			if (state.date < cfg.today) {
				errors.date = 'Please choose today or a later date.';
			} else if (state.date > cfg.maxDate) {
				errors.date = 'Please choose a date in the next two months.';
			} else if (day.getDay() === 0 || day.getDay() === 6) {
				errors.date = 'We call Monday to Friday. Please choose a weekday.';
			}
		} else if (state.time !== 'asap') {
			errors.date = 'Please choose a date for this time.';
		}
		if (Object.keys(errors).length) {
			state.errors = errors;
			render();
			return;
		}
		run(async () => {
			const data = await api('callback', {
				name,
				email: state.email.trim(),
				phone: '+44' + digits,
				date: state.date,
				time: state.time,
				message: state.message.trim(),
				website: state.website,
				recaptcha: await recaptchaToken(),
			});
			state.token = data.token;
			state.code = ['', '', '', ''];
			state.errors = {};
			if (data.done) {
				state.done = { first: data.first, when: data.when };
				state.step = 'done';
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
			let data;
			try {
				data = await api('callback/check', { token: state.token, code: state.code.join('') });
			} catch (err) {
				state.code = ['', '', '', ''];
				if (!(err.data || {}).restart && err.message && !/couldn’t send/.test(err.message)) {
					state.errors = { code: err.message };
					err.silent = true;
				}
				throw err;
			}
			state.done = { first: data.first, when: data.when };
			state.step = 'done';
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

	/* ---------------------------------------------------------------- */
	/* Start                                                            */
	/* ---------------------------------------------------------------- */

	/** Links to the Contact page's callback form open the pop-up instead (the chat button keeps its own behaviour). */
	document.addEventListener(
		'click',
		(e) => {
			if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
				return;
			}
			const a = e.target.closest('a[href*="#callback-form"]');
			if (!a || a.id === 'abf-chat' || a.closest('.abf-callback')) {
				return;
			}
			const url = new URL(a.getAttribute('href'), window.location.href);
			if (url.origin !== window.location.origin || url.hash !== '#callback-form') {
				return;
			}
			e.preventDefault();
			open();
		},
		true
	);
})();

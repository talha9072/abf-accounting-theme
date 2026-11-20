/**
 * ABF Accounting — mobile and tablet menu (core Navigation overlay, see inc/menu.php).
 *
 * - Who we help / Services / Resources start closed and open one at a time: tapping the row or
 *   its chevron opens it, tapping again closes it. Their links are all in the sub-menu.
 * - Buy now and Callback in the menu close it, then the pop-up opens as usual.
 * - <html> gets abf-menu-open while the menu is open.
 */
(function () {
	'use strict';

	const overlay = document.querySelector('.abf-header .wp-block-navigation__responsive-container');
	if (!overlay) {
		return;
	}

	const isOpen = () => overlay.classList.contains('is-menu-open');
	const items = () => overlay.querySelectorAll('.wp-block-navigation__container > .wp-block-navigation-submenu');

	function setItem(li, open) {
		li.classList.toggle('abf-sub-open', open);
		const toggle = li.querySelector(':scope > .wp-block-navigation-submenu__toggle');
		if (toggle) {
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		}
	}

	function closeAll() {
		items().forEach((li) => setItem(li, false));
	}

	// Capture phase: runs before the core handlers, so the row toggles instead of navigating
	// and the core hover/click sub-menu logic stays out of the open menu.
	document.addEventListener(
		'click',
		(e) => {
			if (!isOpen()) {
				return;
			}
			const hit = e.target.closest(
				'.wp-block-navigation__container > .wp-block-navigation-submenu > .wp-block-navigation-item__content, .wp-block-navigation__container > .wp-block-navigation-submenu > .wp-block-navigation-submenu__toggle'
			);
			if (!hit || !overlay.contains(hit)) {
				return;
			}
			e.preventDefault();
			e.stopPropagation();
			const li = hit.parentElement;
			const open = !li.classList.contains('abf-sub-open');
			closeAll();
			setItem(li, open);
		},
		true
	);

	// Buy now / Callback open a pop-up: close the menu first (the pop-up handlers already ran).
	overlay.addEventListener('click', (e) => {
		if (!e.target.closest('.abf-menu-footer__btn, .abf-menu-bar__buy')) {
			return;
		}
		const close = overlay.querySelector('.wp-block-navigation__responsive-container-close');
		if (close && isOpen()) {
			close.click();
		}
	});

	// Core sets aria-expanded="true" on every sub-menu toggle when the overlay opens; put the
	// real (closed) state back once it has rendered.
	new MutationObserver(() => {
		const open = isOpen();
		document.documentElement.classList.toggle('abf-menu-open', open);
		closeAll();
		if (open) {
			requestAnimationFrame(closeAll);
		}
	}).observe(overlay, { attributes: true, attributeFilter: ['class'] });
})();

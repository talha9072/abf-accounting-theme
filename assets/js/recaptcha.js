/**
 * ABF Accounting — reCAPTCHA v3 for the callback form. Gets a token for the "callback" action
 * just before the form is sent; the server checks it (inc/settings.php).
 */
(function () {
	'use strict';

	var form = document.getElementById('callback-form');
	if (!form || !window.grecaptcha) {
		return;
	}
	var key = form.getAttribute('data-recaptcha');
	var field = form.querySelector('input[name="g-recaptcha-response"]');
	var sending = false;

	form.addEventListener('submit', function (event) {
		if (sending || !key || !field) {
			return;
		}
		event.preventDefault();
		var button = form.querySelector('[type="submit"]');
		if (button) {
			button.disabled = true;
		}
		grecaptcha.ready(function () {
			grecaptcha.execute(key, { action: 'callback' }).then(function (token) {
				field.value = token;
				sending = true;
				form.submit();
			}, function () {
				// Google could not be reached: send anyway, the server decides.
				sending = true;
				form.submit();
			});
		});
	});
})();

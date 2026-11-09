/**
 * ABF Accounting — small front-end behaviours.
 * Review carousel: the previous/next buttons scroll the track by one card.
 */
(function () {
	'use strict';

	document.querySelectorAll('.abf-reviews__carousel').forEach(function (carousel) {
		var track = carousel.querySelector('.abf-reviews__track');
		if (!track) {
			return;
		}
		var step = function (dir) {
			var card = track.firstElementChild;
			var gap = parseFloat(getComputedStyle(track).columnGap) || 0;
			var width = card ? card.getBoundingClientRect().width + gap : track.clientWidth;
			var atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 4;
			if (dir > 0 && atEnd) {
				track.scrollTo({ left: 0, behavior: 'smooth' });
				return;
			}
			track.scrollBy({ left: dir * width, behavior: 'smooth' });
		};
		var prev = carousel.querySelector('.abf-reviews__prev');
		var next = carousel.querySelector('.abf-reviews__next');
		if (prev) {
			prev.addEventListener('click', function () { step(-1); });
		}
		if (next) {
			next.addEventListener('click', function () { step(1); });
		}
	});
})();

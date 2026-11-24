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

	// Home video: the poster's play link swaps in the YouTube player (privacy-enhanced domain),
	// so nothing loads from YouTube until the visitor presses play. Ctrl/middle-click still opens
	// the video on YouTube.
	document.querySelectorAll('.abf-video__link[data-abf-video]').forEach(function (link) {
		link.addEventListener('click', function (e) {
			var id = link.getAttribute('data-abf-video');
			var box = link.closest('.abf-video');
			if (!box || !/^[A-Za-z0-9_-]{11}$/.test(id || '') || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) {
				return;
			}
			e.preventDefault();
			var frame = document.createElement('iframe');
			frame.className = 'abf-video__frame';
			frame.src = 'https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&rel=0&playsinline=1';
			frame.title = link.getAttribute('data-abf-video-title') || 'Video';
			frame.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share; fullscreen';
			frame.referrerPolicy = 'strict-origin-when-cross-origin';
			frame.setAttribute('allowfullscreen', '');
			box.appendChild(frame);
			box.classList.add('is-playing');
			frame.focus();
		});
	});
})();

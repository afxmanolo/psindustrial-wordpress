(() => {
	'use strict';
	const button = document.querySelector('.nav-toggle');
	const nav = document.getElementById('site-navigation');
	if (!button || !nav) return;
	button.hidden = false;
	button.addEventListener('click', () => {
		const expanded = button.getAttribute('aria-expanded') === 'true';
		button.setAttribute('aria-expanded', String(!expanded));
		nav.hidden = expanded;
	});
})();


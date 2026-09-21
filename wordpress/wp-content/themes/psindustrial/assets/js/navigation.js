(() => {
	'use strict';
	const button = document.querySelector('.nav-toggle');
	const nav = document.getElementById('site-navigation');
	if (!button || !nav) return;
	button.hidden = false;
	const mobile = window.matchMedia('(max-width: 991px)');
	const disclosures = [];
	if (document.body.classList.contains('psi-catalog')) {
		nav.querySelectorAll('li').forEach((item, index) => {
			const list = item.querySelector(':scope > .sub-menu');
			const link = item.querySelector(':scope > a');
			if (!list || !link) return;
			const toggle = document.createElement('button');
			toggle.type = 'button';
			toggle.className = 'submenu-toggle';
			toggle.textContent = '▾';
			toggle.setAttribute('aria-label', link.textContent.trim());
			list.id = `catalog-submenu-${index}`;
			toggle.setAttribute('aria-controls', list.id);
			link.after(toggle);
			item.classList.add('has-disclosure');
			const setOpen = (open) => {
				list.hidden = !open;
				toggle.setAttribute('aria-expanded', String(open));
			};
			setOpen(false);
			disclosures.push({item, toggle, setOpen});
			let openedByHover = false;
			toggle.addEventListener('click', (event) => {
				setOpen(event.detail > 0 && openedByHover ? true : list.hidden);
				openedByHover = false;
			});
			item.addEventListener('pointerenter', (event) => {
				if (!mobile.matches && event.pointerType === 'mouse' && list.hidden) { setOpen(true); openedByHover = true; }
			});
			item.addEventListener('pointerleave', (event) => {
				openedByHover = false;
				if (event.pointerType === 'mouse' && !item.contains(document.activeElement)) setOpen(false);
			});
			item.addEventListener('focusout', (event) => {
				if (!item.contains(event.relatedTarget)) setOpen(false);
			});
			item.addEventListener('keydown', (event) => {
				if (event.key === 'Escape' && !list.hidden) {
					event.preventDefault(); event.stopPropagation(); setOpen(false); toggle.focus();
				}
				if (event.key === 'ArrowDown' && (event.target === link || event.target === toggle)) {
					event.preventDefault(); setOpen(true); list.querySelector('a')?.focus();
				}
			});
		});
		const sync = () => {
			nav.hidden = mobile.matches;
			button.setAttribute('aria-expanded', String(!mobile.matches));
			disclosures.forEach(({setOpen}) => setOpen(false));
		};
		sync(); mobile.addEventListener('change', sync);
		document.addEventListener('click', (event) => {
			disclosures.forEach(({item, setOpen}) => { if (!item.contains(event.target)) setOpen(false); });
		});
		nav.addEventListener('keydown', (event) => {
			if (event.key === 'Escape' && mobile.matches) {
				nav.hidden = true; button.setAttribute('aria-expanded', 'false'); button.focus();
			}
		});
	}
	button.addEventListener('click', () => {
		const expanded = button.getAttribute('aria-expanded') === 'true';
		button.setAttribute('aria-expanded', String(!expanded));
		nav.hidden = expanded;
		if (expanded) disclosures.forEach(({setOpen}) => setOpen(false));
	});
})();

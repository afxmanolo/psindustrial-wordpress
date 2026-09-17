/* Native media controls. No legacy scripts or framework. */
(() => {
	'use strict';
	document.querySelectorAll('.psi-media-control').forEach((control) => {
		const input = control.querySelector('.psi-media-value');
		const list = control.querySelector('.psi-media-list');
		const kind = control.dataset.kind;
		let value;
		try { value = JSON.parse(input.value); } catch { value = kind === 'single' ? 0 : []; }
		if (kind !== 'single' && !Array.isArray(value)) value = [];
		const persist = () => { input.value = JSON.stringify(value); input.dispatchEvent(new Event('change', { bubbles: true })); };
		const render = () => {
			list.replaceChildren();
			const items = kind === 'single' ? (value ? [value] : []) : value;
			items.forEach((item, index) => {
				const row = document.createElement('li');
				const id = kind === 'pdf' ? item.attachment_id : item;
				const text = document.createElement('span');
				text.textContent = psiMediaLabels.item + ' #' + id + ' ';
				row.append(text);
				if (kind === 'pdf') {
					const label = document.createElement('input');
					label.type = 'text'; label.maxLength = 200;
					label.value = item.label; label.setAttribute('aria-label', psiMediaLabels.label);
					label.addEventListener('input', () => { item.label = label.value; persist(); });
					row.append(label);
				}
				const button = (label, action) => {
					const b = document.createElement('button'); b.type = 'button'; b.className = 'button';
					b.textContent = label; b.addEventListener('click', action); row.append(b); return b;
				};
				if (kind !== 'single') {
					button(psiMediaLabels.up, () => { [value[index - 1], value[index]] = [value[index], value[index - 1]]; persist(); render(); }).disabled = index === 0;
					button(psiMediaLabels.down, () => { [value[index + 1], value[index]] = [value[index], value[index + 1]]; persist(); render(); }).disabled = index === value.length - 1;
				}
				button(psiMediaLabels.remove, () => { if (kind === 'single') value = 0; else value.splice(index, 1); persist(); render(); });
				list.append(row);
			});
		};
		control.querySelector('.psi-media-select').addEventListener('click', () => {
			const frame = wp.media({ title: psiMediaLabels.choose, button: { text: psiMediaLabels.use }, library: { type: kind === 'pdf' ? 'application/pdf' : 'image' }, multiple: kind !== 'single' });
			frame.on('select', () => {
				const selection = frame.state().get('selection').toJSON();
				if (kind === 'single') value = selection[0] ? selection[0].id : 0;
				else selection.forEach((media) => {
					if (!value.some((entry) => (kind === 'pdf' ? entry.attachment_id : entry) === media.id)) {
						value.push(kind === 'pdf' ? { attachment_id: media.id, label: media.title || media.filename, language: '' } : media.id);
					}
				});
				persist(); render();
			});
			frame.open();
		});
		render();
	});
})();


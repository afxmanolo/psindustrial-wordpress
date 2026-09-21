(() => {
 'use strict';
 const slider = document.querySelector('.home-slider');
 if (!slider) return;
 const slides = [...slider.querySelectorAll('.home-slide')];
 const dots = [...slider.querySelectorAll('.home-slide-dot')];
 const controls = slider.querySelector('.home-slider-controls');
 const pause = slider.querySelector('.home-slider-pause');
 const reduced = matchMedia('(prefers-reduced-motion: reduce)');
 let current = 0, stopped = reduced.matches, hovered = false, focused = false, visible = true, timer;
 const show = (index) => {
  current = (index + slides.length) % slides.length;
  slides.forEach((slide, i) => { slide.hidden = i !== current; });
  dots.forEach((dot, i) => dot.setAttribute('aria-pressed', String(i === current)));
 };
 const schedule = () => {
  clearTimeout(timer);
  if (!stopped && !hovered && !focused && visible && !document.hidden && !reduced.matches) timer = setTimeout(() => { show(current + 1); schedule(); }, 4000);
 };
 const updatePause = () => {
  pause.hidden = reduced.matches;
  pause.textContent = stopped ? '▶' : 'Ⅱ';
  pause.setAttribute('aria-label', stopped ? pause.dataset.play : pause.dataset.pause);
 };
 slider.classList.add('is-enhanced'); controls.hidden = false; show(0); updatePause();
 dots.forEach((dot, i) => dot.addEventListener('click', () => { show(i); schedule(); }));
 pause.addEventListener('click', () => { stopped = !stopped; updatePause(); schedule(); });
 slider.addEventListener('keydown', (event) => {
  if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
   event.preventDefault(); show(current + (event.key === 'ArrowRight' ? 1 : -1)); dots[current].focus(); schedule();
  }
 });
 slider.addEventListener('pointerenter', e => { if (e.pointerType === 'mouse') { hovered = true; schedule(); } });
 slider.addEventListener('pointerleave', () => { hovered = false; schedule(); });
 slider.addEventListener('focusin', () => { focused = true; schedule(); });
 slider.addEventListener('focusout', e => { focused = slider.contains(e.relatedTarget); schedule(); });
 let start;
 slider.addEventListener('pointerdown', e => { if (e.pointerType !== 'mouse') start = {x:e.clientX,y:e.clientY}; }, {passive:true});
 slider.addEventListener('pointercancel', () => { start = null; });
 slider.addEventListener('pointerup', e => {
  if (!start) return;
  const dx = e.clientX - start.x, dy = e.clientY - start.y; start = null;
  if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) { show(current + (dx < 0 ? 1 : -1)); schedule(); }
 }, {passive:true});
 reduced.addEventListener('change', () => { stopped = reduced.matches; updatePause(); schedule(); });
 document.addEventListener('visibilitychange', schedule);
 if ('IntersectionObserver' in window) new IntersectionObserver(entries => { visible = entries[0].isIntersecting; schedule(); }).observe(slider);
 schedule();
})();

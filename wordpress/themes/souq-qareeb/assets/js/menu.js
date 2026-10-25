(() => {
  const toggle = document.querySelector('.sq-menu-toggle');
  const nav = document.getElementById('sq-primary-nav');
  if (!toggle || !nav) return;
  const close = () => {
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'فتح القائمة');
    nav.classList.remove('sq-nav--open');
  };
  toggle.addEventListener('click', () => {
    const opening = toggle.getAttribute('aria-expanded') !== 'true';
    toggle.setAttribute('aria-expanded', String(opening));
    toggle.setAttribute('aria-label', opening ? 'إغلاق القائمة' : 'فتح القائمة');
    nav.classList.toggle('sq-nav--open', opening);
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
      close();
      toggle.focus();
    }
  });
  document.addEventListener('click', (event) => {
    if (!nav.contains(event.target) && !toggle.contains(event.target)) close();
  });
  nav.addEventListener('click', (event) => {
    if (event.target.closest('a')) close();
  });
  window.matchMedia('(min-width: 761px)').addEventListener('change', close);
})();
(() => {
  const tabs = [...document.querySelectorAll('[data-tab]')];
  const panels = [...document.querySelectorAll('[data-panel]')];
  const activate = (name, focus = false) => {
    tabs.forEach(tab => {
      const active = tab.dataset.tab === name;
      tab.classList.toggle('is-active', active);
      tab.setAttribute('aria-selected', String(active));
      tab.tabIndex = active ? 0 : -1;
      if (active && focus) tab.focus();
    });
    panels.forEach(panel => {
      const active = panel.dataset.panel === name;
      panel.classList.toggle('is-active', active);
      panel.hidden = !active;
    });
    if (name === 'play') window.dispatchEvent(new CustomEvent('nintage:emulator-tab-open'));
  };
  tabs.forEach((tab, index) => {
    tab.addEventListener('click', () => activate(tab.dataset.tab));
    tab.addEventListener('keydown', event => {
      if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
      event.preventDefault();
      let next = index;
      if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
      if (event.key === 'ArrowLeft') next = (index - 1 + tabs.length) % tabs.length;
      if (event.key === 'Home') next = 0;
      if (event.key === 'End') next = tabs.length - 1;
      activate(tabs[next].dataset.tab, true);
    });
  });
  const initial = new URLSearchParams(location.search).get('tab');
  if (tabs.some(tab => tab.dataset.tab === initial)) activate(initial);
})();

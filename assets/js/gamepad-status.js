(() => {
  const status = document.getElementById('gamepadStatus');
  if (!status) return;
  const refresh = () => {
    if (!navigator.getGamepads) {
      status.textContent = 'MANETTE : NAVIGATEUR INCOMPATIBLE';
      status.classList.add('is-disconnected');
      return;
    }
    const pads = [...navigator.getGamepads()].filter(Boolean);
    const pad = pads.find(item => item.connected);
    if (pad) {
      status.textContent = `MANETTE : ${pad.id.slice(0, 34)}`;
      status.title = `${pad.id} · ${pad.buttons.length} boutons · ${pad.axes.length} axes`;
      status.classList.add('is-connected');
      status.classList.remove('is-disconnected');
    } else {
      status.textContent = 'MANETTE : NON DÉTECTÉE';
      status.title = 'Connecte une manette puis appuie sur un bouton.';
      status.classList.remove('is-connected');
      status.classList.add('is-disconnected');
    }
  };
  window.addEventListener('gamepadconnected', refresh);
  window.addEventListener('gamepaddisconnected', refresh);
  window.addEventListener('focus', refresh);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
  setInterval(refresh, 750);
  refresh();
})();

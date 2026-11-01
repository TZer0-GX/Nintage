(() => {
  const frame = document.getElementById('emulatorFrame');
  const host = document.getElementById('emulatorHost');
  const status = document.getElementById('emulatorStatus');
  const fullscreen = document.getElementById('fullscreenEmulator');
  if (!frame || !host) return;

  const DATA_PATH = 'https://cdn.emulatorjs.org/stable/data/';
  let started = false;
  let loading = false;
  let failed = false;

  function showMessage(title, detail) {
    host.innerHTML = '';
    const box = document.createElement('div');
    box.className = 'emulator-placeholder';
    const strong = document.createElement('strong');
    strong.textContent = title;
    const span = document.createElement('span');
    span.textContent = detail;
    box.append(strong, span);
    host.appendChild(box);
  }

  function start() {
    if (started || loading) return;
    const rom = frame.dataset.rom;
    const core = frame.dataset.core;
    const title = frame.dataset.title || 'Jeu rétro';
    if (!rom) {
      showMessage('ROM introuvable', 'Aucun fichier ROM n’est associé à ce jeu.');
      status.textContent = 'ROM non configurée.';
      return;
    }
    if (!core) {
      showMessage('Plateforme non prise en charge', 'Aucun cœur d’émulation n’est configuré pour cette plateforme.');
      status.textContent = 'Cœur indisponible.';
      return;
    }
    loading = true;
    status.textContent = 'Chargement du moteur d’émulation…';
    showMessage('Chargement de l’émulateur…', 'Le premier lancement peut prendre un peu de temps.');

    // Configuration EmulatorJS : NES, Game Boy, Game Boy Color, SNES, Nintendo 64 et PS1.
    window.EJS_player = '#emulatorPlayer';
    window.EJS_core = core;
    window.EJS_gameUrl = rom;
    window.EJS_gameName = title;
    window.EJS_pathtodata = DATA_PATH;
    window.EJS_startOnLoaded = true;
    window.EJS_color = '#ffeb00';
    window.EJS_backgroundColor = '#171717';
    window.EJS_language = 'fr-FR';
    // Le menu intégré d’EmulatorJS expose les contrôles, la manette et les états de sauvegarde.
    host.innerHTML = '<div id="emulatorPlayer"></div>';

    const script = document.createElement('script');
    script.src = `${DATA_PATH}loader.js`;
    script.async = true;
    script.onload = () => {
      loading = false;
      started = true;
      status.textContent = `Moteur ${core} chargé. Si le jeu ne démarre pas, vérifie le format de la ROM.`;
    };
    script.onerror = () => {
      loading = false;
      failed = true;
      showMessage('Impossible de charger EmulatorJS', 'Vérifie ta connexion Internet, les extensions anti-pistage ou l’accès au CDN EmulatorJS.');
      status.textContent = 'Échec du chargement.';
    };
    document.body.appendChild(script);
  }

  window.addEventListener('nintage:emulator-tab-open', start);
  if (document.querySelector('[data-panel="play"]')?.classList.contains('is-active')) start();
  fullscreen?.addEventListener('click', async () => {
    try {
      if (document.fullscreenElement) await document.exitFullscreen();
      else await frame.requestFullscreen();
    } catch (_) {
      status.textContent = 'Le navigateur a refusé le plein écran.';
    }
  });
  window.addEventListener('pageshow', () => { if (failed) status.textContent = 'Échec du chargement. Recharge la page pour réessayer.'; });
})();

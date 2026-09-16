/* global Capacitor, KpkStorage, KpkPin */
(function () {
  const DEFAULT_SERVER = 'https://спо-прогресс.рф';

  const screens = {
    boot: document.getElementById('screen-boot'),
    settings: document.getElementById('screen-settings'),
    lock: document.getElementById('screen-lock'),
    setup: document.getElementById('screen-setup-pin'),
  };

  const els = {
    serverUrl: document.getElementById('server-url'),
    settingsError: document.getElementById('settings-error'),
    btnSaveServer: document.getElementById('btn-save-server'),
    pinInput: document.getElementById('pin-input'),
    pinDots: document.getElementById('pin-dots'),
    lockError: document.getElementById('lock-error'),
    lockHint: document.getElementById('lock-hint'),
    btnBiometric: document.getElementById('btn-biometric'),
    btnOpenSettings: document.getElementById('btn-open-settings'),
    setupPin: document.getElementById('setup-pin'),
    setupPin2: document.getElementById('setup-pin2'),
    setupBio: document.getElementById('setup-bio'),
    setupError: document.getElementById('setup-error'),
    btnSavePin: document.getElementById('btn-save-pin'),
  };

  let state = {
    serverUrl: '',
    pinSet: false,
    bioEnabled: false,
    unlocked: false,
    awaitingPinSetup: false,
    browserOpen: false,
    listenersBound: false,
    authInProgress: false,
    suppressLockResumeUntil: 0,
  };

  function show(name) {
    Object.keys(screens).forEach((key) => {
      screens[key].hidden = key !== name;
    });
  }

  function setError(node, message) {
    if (!node) {
      return;
    }
    if (!message) {
      node.hidden = true;
      node.textContent = '';
      return;
    }
    node.hidden = false;
    node.textContent = message;
  }

  function normalizeUrl(raw) {
    let url = String(raw || '').trim();
    if (!url) {
      return '';
    }
    if (!/^https?:\/\//i.test(url)) {
      url = 'https://' + url;
    }
    return url.replace(/\/+$/, '');
  }

  function isLoginUrl(url) {
    try {
      const u = new URL(url);
      const path = (u.pathname || '').toLowerCase();
      return path.endsWith('/login.php') || path === '/login.php' || /\/login\.php$/i.test(path);
    } catch (e) {
      return /login\.php/i.test(String(url || ''));
    }
  }

  function isLoggedInUrl(url) {
    if (!url || isLoginUrl(url)) {
      return false;
    }
    try {
      const base = new URL(state.serverUrl);
      const u = new URL(url);
      if (u.origin !== base.origin) {
        return false;
      }
      const path = (u.pathname || '').toLowerCase();
      if (!path || path === '/') {
        return false;
      }
      return !/logout\.php/i.test(path);
    } catch (e) {
      return !isLoginUrl(url);
    }
  }

  function loginUrl() {
    return state.serverUrl.replace(/\/+$/, '') + '/login.php';
  }

  function siteHomeUrl() {
    return state.serverUrl.replace(/\/+$/, '') + '/dashboard.php';
  }

  const PIN_LENGTH = 4;

  function renderPinDots(len) {
    els.pinDots.innerHTML = '';
    for (let i = 0; i < PIN_LENGTH; i += 1) {
      const dot = document.createElement('span');
      if (i < len) {
        dot.classList.add('filled');
      }
      els.pinDots.appendChild(dot);
    }
  }

  function plugin(name) {
    const cap = globalThis.Capacitor;
    if (!cap) {
      return null;
    }
    if (cap.Plugins && cap.Plugins[name]) {
      return cap.Plugins[name];
    }
    if (typeof cap.getPlugin === 'function') {
      try {
        return cap.getPlugin(name);
      } catch (e) {
        return null;
      }
    }
    return null;
  }

  function waitForCapacitor(timeoutMs = 3000) {
    return new Promise((resolve) => {
      const start = Date.now();
      const tick = () => {
        if (globalThis.Capacitor && globalThis.Capacitor.Plugins) {
          resolve(true);
          return;
        }
        if (Date.now() - start >= timeoutMs) {
          resolve(false);
          return;
        }
        setTimeout(tick, 40);
      };
      tick();
    });
  }

  async function bioAvailable() {
    const NativeBiometric = plugin('NativeBiometric');
    if (!NativeBiometric) {
      return false;
    }
    try {
      const result = await NativeBiometric.isAvailable();
      return !!(result && result.isAvailable);
    } catch (e) {
      return false;
    }
  }

  function suppressLockResume(ms) {
    state.suppressLockResumeUntil = Date.now() + (ms || 2000);
  }

  function shouldIgnoreLockResume() {
    return state.authInProgress || Date.now() < state.suppressLockResumeUntil;
  }

  async function runBiometric() {
    const NativeBiometric = plugin('NativeBiometric');
    if (!NativeBiometric) {
      throw new Error('Биометрия недоступна');
    }
    state.authInProgress = true;
    suppressLockResume(3000);
    try {
      await NativeBiometric.verifyIdentity({
        reason: 'Вход в СПО-ПРОГРЕСС',
        title: 'СПО-ПРОГРЕСС',
        subtitle: 'Подтвердите личность',
        description: 'Используйте отпечаток или Face ID',
        negativeButtonText: 'Отмена',
      });
      suppressLockResume(3000);
    } finally {
      state.authInProgress = false;
    }
  }

  function cookieUrl() {
    try {
      return new URL(state.serverUrl).origin + '/';
    } catch (e) {
      return String(state.serverUrl || '').replace(/\/+$/, '') + '/';
    }
  }

  async function saveSessionCookies() {
    const InAppBrowser = plugin('InAppBrowser');
    const SessionCookie = plugin('SessionCookie');
    if (!InAppBrowser || !state.serverUrl) {
      return;
    }
    try {
      const cookies = await InAppBrowser.getCookies({
        url: cookieUrl(),
        includeHttpOnly: true,
      });
      const cleaned = {};
      Object.keys(cookies || {}).forEach((key) => {
        if (key && cookies[key] != null && key !== 'url') {
          cleaned[key] = String(cookies[key]);
        }
      });
      if (Object.keys(cleaned).length > 0) {
        await KpkStorage.setSessionCookies(cleaned);
      }
      if (SessionCookie && SessionCookie.flush) {
        await SessionCookie.flush();
      }
    } catch (e) {
      // ignore
    }
  }

  async function restoreSessionCookies() {
    const SessionCookie = plugin('SessionCookie');
    const cookies = await KpkStorage.getSessionCookies();
    if (!cookies || Object.keys(cookies).length === 0 || !state.serverUrl) {
      return {};
    }
    if (SessionCookie && SessionCookie.setCookies) {
      try {
        await SessionCookie.setCookies({
          url: cookieUrl(),
          cookies,
        });
      } catch (e) {
        // ignore
      }
    }
    return cookies;
  }

  function cookiesToHeader(cookies) {
    return Object.keys(cookies || {})
      .map((key) => key + '=' + cookies[key])
      .join('; ');
  }

  async function closeBrowserPreservingSession() {
    const InAppBrowser = plugin('InAppBrowser');
    await saveSessionCookies();
    if (InAppBrowser && state.browserOpen) {
      try {
        await InAppBrowser.close();
      } catch (e) { /* ignore */ }
    }
    state.browserOpen = false;
  }

  async function ensureBrowserListeners() {
    const InAppBrowser = plugin('InAppBrowser');
    if (!InAppBrowser || state.listenersBound) {
      return;
    }
    state.listenersBound = true;

    await InAppBrowser.addListener('urlChangeEvent', async (event) => {
      const url = event && event.url ? event.url : '';
      if (!url) {
        return;
      }
      if (isLoggedInUrl(url)) {
        await saveSessionCookies();
      }
      if (state.awaitingPinSetup && isLoggedInUrl(url)) {
        state.awaitingPinSetup = false;
        await closeBrowserPreservingSession();
        show('setup');
        return;
      }
      if (state.pinSet && isLoginUrl(url)) {
        await KpkStorage.clearSessionCookies();
      }
    });

    await InAppBrowser.addListener('closeEvent', async () => {
      state.browserOpen = false;
      await saveSessionCookies();
      if (state.awaitingPinSetup) {
        return;
      }
      if (state.pinSet) {
        await showLock();
      }
    });
  }

  async function openSite(startUrl) {
    const InAppBrowser = plugin('InAppBrowser');
    const url = startUrl || siteHomeUrl();
    if (!InAppBrowser) {
      window.open(url, '_blank');
      return;
    }
    await ensureBrowserListeners();
    const cookies = await restoreSessionCookies();
    const cookieHeader = cookiesToHeader(cookies);
    state.browserOpen = true;
    const options = {
      url,
      backgroundColor: '#f1f5f9',
      toolbarType: 'blank',
      isPresentAfterPageLoad: false,
      disableGoBackOnNativeApplication: false,
      activeNativeNavigationForWebview: true,
    };
    if (cookieHeader) {
      options.headers = { Cookie: cookieHeader };
    }
    await InAppBrowser.openWebView(options);
  }

  async function openLoginFlow() {
    state.awaitingPinSetup = !(await KpkStorage.isPinSet());
    show('boot');
    await openSite(loginUrl());
  }

  async function afterUnlock() {
    suppressLockResume(3000);
    state.unlocked = true;
    show('boot');
    await openSite(siteHomeUrl());
    suppressLockResume(2000);
  }

  async function showLock() {
    if (state.authInProgress) {
      return;
    }
    state.unlocked = false;
    show('lock');
    els.pinInput.value = '';
    renderPinDots(0);
    setError(els.lockError, '');
    els.lockHint.textContent = 'Введите PIN';
    const canBio = state.bioEnabled && (await bioAvailable());
    els.btnBiometric.hidden = !canBio;
    if (canBio) {
      try {
        await runBiometric();
        await afterUnlock();
        return;
      } catch (e) {
        // пользователь отменил — ждём PIN
      }
    }
    els.pinInput.focus();
  }

  async function boot() {
    show('boot');
    state.serverUrl = (await KpkStorage.getServerUrl()) || DEFAULT_SERVER;
    state.pinSet = await KpkStorage.isPinSet();
    state.bioEnabled = await KpkStorage.isBioEnabled();

    if (state.pinSet) {
      const material = await KpkStorage.getPinMaterial();
      if (material.length !== PIN_LENGTH) {
        await KpkStorage.clearPin();
        state.pinSet = false;
        state.bioEnabled = false;
      }
    }

    if (!state.serverUrl) {
      els.serverUrl.value = '';
      show('settings');
      return;
    }

    if (state.pinSet) {
      await showLock();
      return;
    }

    await openLoginFlow();
  }

  els.btnSaveServer.addEventListener('click', async () => {
    const url = normalizeUrl(els.serverUrl.value);
    setError(els.settingsError, '');
    if (!url) {
      setError(els.settingsError, 'Укажите адрес сервера');
      return;
    }
    try {
      // eslint-disable-next-line no-new
      new URL(url);
    } catch (e) {
      setError(els.settingsError, 'Некорректный URL');
      return;
    }
    await KpkStorage.setServerUrl(url);
    state.serverUrl = url;
    if (state.pinSet && !state.unlocked) {
      await showLock();
      return;
    }
    if (state.pinSet && state.unlocked) {
      show('boot');
      await openSite(siteHomeUrl());
      return;
    }
    await openLoginFlow();
  });

  els.btnOpenSettings.addEventListener('click', () => {
    els.serverUrl.value = state.serverUrl || '';
    show('settings');
  });

  els.pinInput.addEventListener('input', async () => {
    const pin = els.pinInput.value.replace(/\D/g, '').slice(0, PIN_LENGTH);
    els.pinInput.value = pin;
    renderPinDots(pin.length);
    setError(els.lockError, '');
    if (pin.length < PIN_LENGTH) {
      return;
    }
    const material = await KpkStorage.getPinMaterial();
    const ok = await KpkPin.verify(pin, material.salt, material.hash);
    if (ok) {
      await afterUnlock();
      return;
    }
    setError(els.lockError, 'Неверный PIN');
    els.pinInput.value = '';
    renderPinDots(0);
  });

  els.btnBiometric.addEventListener('click', async () => {
    try {
      await runBiometric();
      await afterUnlock();
    } catch (e) {
      setError(els.lockError, 'Биометрия не пройдена');
    }
  });

  els.btnSavePin.addEventListener('click', async () => {
    setError(els.setupError, '');
    const pin = els.setupPin.value;
    const pin2 = els.setupPin2.value;
    if (!KpkPin.isValidPin(pin)) {
      setError(els.setupError, 'PIN должен состоять из 4 цифр');
      return;
    }
    if (pin !== pin2) {
      setError(els.setupError, 'PIN не совпадает');
      return;
    }
    const salt = KpkPin.randomSalt();
    const hash = await KpkPin.hashPin(pin, salt);
    await KpkStorage.savePin(salt, hash, PIN_LENGTH);
    const wantBio = !!els.setupBio.checked && (await bioAvailable());
    await KpkStorage.setBioEnabled(wantBio);
    state.pinSet = true;
    state.bioEnabled = wantBio;
    state.unlocked = true;
    suppressLockResume(3000);
    show('boot');
    await openSite(siteHomeUrl());
    suppressLockResume(2000);
  });

  async function bindAppLifecycle() {
    const App = plugin('App');
    if (!App) {
      return;
    }
    App.addListener('appStateChange', async ({ isActive }) => {
      if (!isActive) {
        if (state.browserOpen && !state.authInProgress) {
          await saveSessionCookies();
        }
        return;
      }
      if (shouldIgnoreLockResume()) {
        return;
      }
      if (!state.pinSet) {
        return;
      }
      if (!state.unlocked) {
        return;
      }
      await closeBrowserPreservingSession();
      await showLock();
    });
  }

  document.addEventListener('DOMContentLoaded', async () => {
    renderPinDots(0);
    await waitForCapacitor();
    await bindAppLifecycle();
    await boot();
  });
})();

/* global Capacitor */
(function (global) {
  const KEYS = {
    serverUrl: 'server_url',
    pinSalt: 'pin_salt',
    pinHash: 'pin_hash',
    pinLength: 'pin_length',
    bioEnabled: 'bio_enabled',
    pinSet: 'pin_set',
    sessionCookies: 'session_cookies',
  };

  async function prefs() {
    if (global.Capacitor && global.Capacitor.Plugins && global.Capacitor.Plugins.Preferences) {
      return global.Capacitor.Plugins.Preferences;
    }
    const memory = global.__kpkPrefsMemory || (global.__kpkPrefsMemory = {});
    return {
      async get({ key }) {
        const v = memory[key];
        return { value: v === undefined ? null : v };
      },
      async set({ key, value }) {
        memory[key] = String(value);
      },
      async remove({ key }) {
        delete memory[key];
      },
    };
  }

  async function get(key, fallback = null) {
    const p = await prefs();
    const { value } = await p.get({ key });
    return value === null || value === undefined ? fallback : value;
  }

  async function set(key, value) {
    const p = await prefs();
    await p.set({ key, value: String(value) });
  }

  async function remove(key) {
    const p = await prefs();
    await p.remove({ key });
  }

  global.KpkStorage = {
    KEYS,
    get,
    set,
    remove,
    async getServerUrl() {
      return get(KEYS.serverUrl, '');
    },
    async setServerUrl(url) {
      await set(KEYS.serverUrl, url.replace(/\/+$/, ''));
    },
    async isPinSet() {
      return (await get(KEYS.pinSet, '0')) === '1';
    },
    async getPinMaterial() {
      return {
        salt: await get(KEYS.pinSalt, ''),
        hash: await get(KEYS.pinHash, ''),
        length: parseInt(await get(KEYS.pinLength, '0'), 10) || 0,
      };
    },
    async savePin(salt, hash, length) {
      await set(KEYS.pinSalt, salt);
      await set(KEYS.pinHash, hash);
      await set(KEYS.pinLength, String(length));
      await set(KEYS.pinSet, '1');
    },
    async clearPin() {
      await remove(KEYS.pinSalt);
      await remove(KEYS.pinHash);
      await remove(KEYS.pinLength);
      await set(KEYS.pinSet, '0');
      await set(KEYS.bioEnabled, '0');
    },
    async isBioEnabled() {
      return (await get(KEYS.bioEnabled, '0')) === '1';
    },
    async setBioEnabled(on) {
      await set(KEYS.bioEnabled, on ? '1' : '0');
    },
    async getSessionCookies() {
      const raw = await get(KEYS.sessionCookies, '');
      if (!raw) {
        return {};
      }
      try {
        const parsed = JSON.parse(raw);
        return parsed && typeof parsed === 'object' ? parsed : {};
      } catch (e) {
        return {};
      }
    },
    async setSessionCookies(cookies) {
      await set(KEYS.sessionCookies, JSON.stringify(cookies || {}));
    },
    async clearSessionCookies() {
      await remove(KEYS.sessionCookies);
    },
  };
})(window);

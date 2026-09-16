(function (global) {
  function toHex(buffer) {
    return Array.from(new Uint8Array(buffer))
      .map((b) => b.toString(16).padStart(2, '0'))
      .join('');
  }

  function fromHex(hex) {
    const out = new Uint8Array(hex.length / 2);
    for (let i = 0; i < out.length; i += 1) {
      out[i] = parseInt(hex.substr(i * 2, 2), 16);
    }
    return out;
  }

  function randomSalt(bytes = 16) {
    const arr = new Uint8Array(bytes);
    crypto.getRandomValues(arr);
    return toHex(arr);
  }

  async function hashPin(pin, saltHex) {
    const enc = new TextEncoder();
    const keyMaterial = await crypto.subtle.importKey(
      'raw',
      enc.encode(String(pin)),
      'PBKDF2',
      false,
      ['deriveBits']
    );
    const bits = await crypto.subtle.deriveBits(
      {
        name: 'PBKDF2',
        salt: fromHex(saltHex),
        iterations: 120000,
        hash: 'SHA-256',
      },
      keyMaterial,
      256
    );
    return toHex(bits);
  }

  function isValidPin(pin) {
    return /^[0-9]{4}$/.test(String(pin || ''));
  }

  global.KpkPin = {
    randomSalt,
    hashPin,
    isValidPin,
    async verify(pin, saltHex, hashHex) {
      if (!saltHex || !hashHex) {
        return false;
      }
      const next = await hashPin(pin, saltHex);
      return next === hashHex;
    },
  };
})(window);

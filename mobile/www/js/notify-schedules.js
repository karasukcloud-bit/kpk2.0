/* global Capacitor */
(function (global) {
  const ID_OFFSET = 100000;

  function plugin(name) {
    const cap = global.Capacitor;
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

  function parseTime(time) {
    const parts = String(time || '09:00').split(':');
    const hour = Math.max(0, Math.min(23, parseInt(parts[0], 10) || 0));
    const minute = Math.max(0, Math.min(59, parseInt(parts[1], 10) || 0));
    return { hour, minute };
  }

  /** Наш weekday 1=Пн..7=Вс → Capacitor 1=Вс..7=Сб */
  function toCapacitorWeekday(weekday) {
    const monBased = Math.max(1, Math.min(7, parseInt(weekday, 10) || 1));
    return monBased === 7 ? 1 : monBased + 1;
  }

  function notificationId(scheduleId) {
    return ID_OFFSET + Math.max(1, parseInt(scheduleId, 10) || 0);
  }

  function buildSchedule(item) {
    const { hour, minute } = parseTime(item.time);
    const frequency = String(item.frequency || 'daily');
    const base = {
      id: notificationId(item.id),
      title: String(item.title || 'СПО-ПРОГРЕСС'),
      body: String(item.body || ''),
      channelId: 'mobile_schedules',
      smallIcon: 'ic_stat_notify',
      schedule: {
        allowWhileIdle: true,
        repeats: true,
      },
    };

    // Только `on` + repeats: на Android ветка `every` перекрывает `on`
    // и ставит alarm на now+interval, игнорируя час/минуту.
    if (frequency === 'weekly') {
      base.schedule.on = {
        weekday: toCapacitorWeekday(item.weekday),
        hour,
        minute,
        second: 0,
      };
    } else if (frequency === 'monthly') {
      base.schedule.on = {
        day: Math.max(1, Math.min(31, parseInt(item.month_day, 10) || 1)),
        hour,
        minute,
        second: 0,
      };
    } else {
      base.schedule.on = { hour, minute, second: 0 };
    }

    return base;
  }

  async function ensurePermissions() {
    const LocalNotifications = plugin('LocalNotifications');
    if (!LocalNotifications) {
      return false;
    }
    try {
      let perm = await LocalNotifications.checkPermissions();
      if (perm.display !== 'granted') {
        perm = await LocalNotifications.requestPermissions();
      }
      return perm.display === 'granted';
    } catch (e) {
      return false;
    }
  }

  async function ensureChannel() {
    const LocalNotifications = plugin('LocalNotifications');
    if (!LocalNotifications || !LocalNotifications.createChannel) {
      return;
    }
    try {
      await LocalNotifications.createChannel({
        id: 'mobile_schedules',
        name: 'Расписание',
        description: 'Напоминания по расписанию колледжа',
        importance: 5,
        visibility: 1,
        sound: 'default',
        vibration: true,
      });
    } catch (e) {
      // ignore
    }
  }

  async function clearScheduled() {
    const LocalNotifications = plugin('LocalNotifications');
    if (!LocalNotifications) {
      return;
    }
    try {
      const pending = await LocalNotifications.getPending();
      const list = (pending && pending.notifications) || [];
      const ours = list.filter((n) => (n.id || 0) >= ID_OFFSET);
      if (ours.length) {
        await LocalNotifications.cancel({
          notifications: ours.map((n) => ({ id: n.id })),
        });
      }
    } catch (e) {
      // ignore
    }
  }

  async function fetchSchedules(serverUrl) {
    const base = String(serverUrl || '').replace(/\/+$/, '');
    if (!base) {
      return [];
    }
    const response = await fetch(base + '/api/mobile_notification_schedules.php', {
      method: 'GET',
      headers: { Accept: 'application/json' },
      cache: 'no-store',
    });
    if (!response.ok) {
      throw new Error('HTTP ' + response.status);
    }
    const data = await response.json();
    if (!data || !data.success || !Array.isArray(data.schedules)) {
      return [];
    }
    return data.schedules;
  }

  async function syncFromServer(serverUrl) {
    const LocalNotifications = plugin('LocalNotifications');
    if (!LocalNotifications) {
      return { ok: false, reason: 'plugin' };
    }
    const allowed = await ensurePermissions();
    if (!allowed) {
      return { ok: false, reason: 'permission' };
    }
    await ensureChannel();
    const schedules = await fetchSchedules(serverUrl);
    await clearScheduled();
    if (schedules.length === 0) {
      return { ok: true, count: 0 };
    }
    const notifications = schedules.map(buildSchedule);
    await LocalNotifications.schedule({ notifications });
    return { ok: true, count: notifications.length };
  }

  global.KpkNotifySchedules = {
    syncFromServer,
    ensurePermissions,
  };
})(window);

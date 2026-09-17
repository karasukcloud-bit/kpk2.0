# Мобильная оболочка Android (Capacitor) — КПК Учёт

Оболочка открывает ваш PHP-сайт во встроенном WebView. Файлы веб-приложения **не изменяются**.

- Package ID: `ru.kpk.attendance`
- Магазин: **RuStore**
- Локальная защита: PIN (4–6 цифр) + опционально отпечаток / Face Unlock

## Требования

- Node.js 18+ (рекомендуется 20)
- JDK 17
- Android Studio (Android SDK, Platform Tools)
- Для устройства: USB-отладка или эмулятор

## Быстрый старт

```bash
cd mobile
npm install
npx cap sync android
npx cap open android
```

В Android Studio: Run на устройстве/эмуляторе.

Либо из консоли (нужен `ANDROID_HOME`):

```bash
cd mobile/android
.\gradlew.bat assembleDebug
```

APK: `mobile/android/app/build/outputs/apk/debug/app-debug.apk`

## Первый запуск

1. Укажите **базовый URL** сайта (например `https://ваш-домен.ru` без слэша в конце).
2. Войдите логином/паролем сайта.
3. Создайте PIN и при желании включите биометрию.
4. Далее при открытии приложения — PIN или отпечаток, затем сайт с сохранённой PHP-сессией (cookie).

Смена сервера (в т.ч. на внутренний HTTP/LAN) — кнопка «Сменить сервер» на экране блокировки или после входа.

## Сборка релиза для RuStore

Подробный чеклист и тексты для витрины: [`RUSTORE.md`](RUSTORE.md).

### 1. Keystore

Уже создан локально: `kpk-release.keystore` + `keystore.properties`  
Секреты: `RELEASE_CREDENTIALS.txt` (не коммитить, хранить резервную копию).

### 2. AAB / APK

```bash
cd mobile
npm run release
```

- AAB: `android/app/build/outputs/bundle/release/app-release.aab`
- APK: `android/app/build/outputs/apk/release/app-release.apk`

Перед каждой публикацией поднимайте `versionCode` и `versionName` в [`android/app/build.gradle`](android/app/build.gradle).

### 3. RuStore Console

1. Создайте приложение с package name `ru.kpk.attendance`.
2. Загрузите AAB (или APK), заполните описание, скриншоты, политику: `/privacy.php`.
3. Укажите тестовый логин для модераторов.
4. Отправьте на модерацию.

## Структура

```
mobile/
  www/                 # UI оболочки (PIN, настройки, запуск WebView)
  android/             # нативный проект Capacitor
  capacitor.config.json
  package.json
  keystore.properties.example
  README.md
```

## Плагины

| Пакет | Назначение |
|-------|------------|
| `@capacitor/preferences` | URL сервера, hash PIN |
| `@capgo/inappbrowser` | WebView сайта + события URL |
| `@capgo/capacitor-native-biometric` | отпечаток / Face |
| `@capacitor/app` | блокировка при возврате в приложение |
| `@capacitor/local-notifications` | уведомления по расписанию с сервера |

## Уведомления по расписанию

Админ: `admin/mobile_notifications.php` — текст, время, ежедневно/еженедельно/ежемесячно.

API для приложения: `GET /api/mobile_notification_schedules.php`

После входа по PIN приложение запрашивает разрешение и синхронизирует расписания (локальное время устройства, `on` + repeats).

**Проверка в debug:**
1. В админке создайте уведомление на время через 1–2 минуты (ежедневно).
2. Переустановите/откройте приложение, разрешите уведомления.
3. Сверните приложение — в шторке должно появиться уведомление.
4. На Android 12+: при необходимости разрешите «Будильники и напоминания» для точного времени.

## Безопасность PIN

PIN не хранится открытым текстом: PBKDF2-SHA256 (120000 итераций) + случайная соль в Preferences. Биометрия только подтверждает владельца устройства после настройки PIN.

## Важно

- Менять `applicationId` после публикации в RuStore нельзя без нового приложения в магазине.
- Для внутреннего HTTP-сервера cleartext разрешён в debug/релизе через network security config; для публичного контура предпочтителен HTTPS.
- PHP-сайт по-прежнему отвечает за авторизацию пользователей; оболочка только защищает доступ к уже установленному приложению на устройстве.

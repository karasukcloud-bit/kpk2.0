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

### 1. Keystore (один раз)

```bash
cd mobile
keytool -genkey -v -keystore android/kpk-release.keystore -alias kpk -keyalg RSA -keysize 2048 -validity 10000
copy keystore.properties.example keystore.properties
```

Отредактируйте `mobile/keystore.properties` (пароли и путь). Файл **не коммитить**.

### 2. AAB / APK

```bash
cd mobile
npx cap sync android
cd android
.\gradlew.bat bundleRelease
.\gradlew.bat assembleRelease
```

- AAB: `android/app/build/outputs/bundle/release/app-release.aab` (предпочтительно для RuStore)
- APK: `android/app/build/outputs/apk/release/app-release.apk`

Перед каждой публикацией поднимайте `versionCode` и `versionName` в [`android/app/build.gradle`](android/app/build.gradle).

### 3. RuStore Console

1. Создайте приложение с package name `ru.kpk.attendance`.
2. Загрузите AAB/APK, заполните описание, скриншоты, политику конфиденциальности.
3. Укажите, что приложение использует биометрию и доступ в интернет к вашему серверу учёта.
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

## Безопасность PIN

PIN не хранится открытым текстом: PBKDF2-SHA256 (120000 итераций) + случайная соль в Preferences. Биометрия только подтверждает владельца устройства после настройки PIN.

## Важно

- Менять `applicationId` после публикации в RuStore нельзя без нового приложения в магазине.
- Для внутреннего HTTP-сервера cleartext разрешён в debug/релизе через network security config; для публичного контура предпочтителен HTTPS.
- PHP-сайт по-прежнему отвечает за авторизацию пользователей; оболочка только защищает доступ к уже установленному приложению на устройстве.

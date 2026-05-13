# Деплой на REG.RU (Apache + MySQL)

## 1) Что уже подготовлено в проекте
- Symfony переведён на `APP_ENV=prod`.
- DSN БД переключён на MySQL.
- Для Apache добавлены `.htaccess` правила (в корне и `public/`).

## 2) Что настроить на хостинге
1. PHP 8.2+ (лучше 8.3).
2. Включить расширения: `pdo_mysql`, `intl`, `zip`, `gd`, `opcache`.
3. База MySQL: создать БД, пользователя, пароль.
4. SSL-сертификат для `rk03.pro` и `www.rk03.pro`.

## 3) Переменные окружения (обязательно)
На REG.RU задай в окружении (или через `.env.local`, если панель не умеет env vars):

```dotenv
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=CHANGE_ME_TO_LONG_RANDOM_SECRET
APP_BASE_URL=https://rk03.pro
DATABASE_URL="mysql://DB_USER:DB_PASS@DB_HOST:3306/DB_NAME?serverVersion=8.0.36&charset=utf8mb4"
MAILER_DSN=null://null
```

## 4) Установка на сервере
```bash
composer install --no-dev --optimize-autoloader
php bin/console cache:clear --env=prod
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
php bin/console cache:warmup --env=prod
```

## 5) Веб-корень
Предпочтительно: DocumentRoot = `public/`.

Если на тарифе нельзя поменять DocumentRoot, используется корневой `.htaccess`, который проксирует в `public/`.

## 6) Права доступа
- `var/` и `public/uploads/` должны быть доступны для записи веб-серверу.

## 7) Пост-релиз проверка
- Открывается `https://rk03.pro`.
- Работает логин/регистрация.
- Загружаются изображения/баннеры.
- Нет 500 в логах после прогрева кэша.

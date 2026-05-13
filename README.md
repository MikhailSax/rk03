# rk03.pro — Docker окружение

Проект запускается в Docker с сервисами:
- `nginx` (веб-сервер)
- `app` (PHP-FPM + Symfony)
- `database` (MySQL)
- `mailer` (Mailpit)

## Быстрый запуск

```bash
docker compose up -d --build
```

После старта:
- приложение: `https://rk03.pro (или http://localhost:8080 для локальной проверки)`
- Mailpit UI: `http://localhost:8025`
- MySQL: `localhost:3306`

## Доступ по локальной сети (для коллег)

1. Узнайте IP машины, где запущен Docker:
   ```bash
   hostname -I
   ```
2. Откройте порт `8080` в firewall (если включен).
3. Коллеги смогут открыть проект по адресу:
   - `http://<ВАШ_ЛОКАЛЬНЫЙ_IP>:8080`

Например: `http://192.168.1.25:8080`.

## Полезные команды

Выполнить миграции:
```bash
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
```

Остановить окружение:
```bash
docker compose down
```


## Деплой на REG.RU

См. пошаговую инструкцию: `DEPLOY_REG_RU.md`.

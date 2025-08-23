# Asbuild

Foundation for a Laravel 11 backend and Vue 3 single-page application.

## Deployment on Plesk

1. Set the domain's document root to `public`.
2. Copy `backend/.env.example` to `backend/.env` and set database credentials.
3. Install dependencies and generate an app key:
   ```bash
   cd backend && composer install && php artisan key:generate
   ```
4. Build the frontend:
   ```bash
   cd frontend && npm install && npm run build
   ```

## Cron jobs

Configure scheduled tasks in Plesk to keep queues and schedules running:

```
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /path/to/backend && php artisan queue:work --once >> /dev/null 2>&1
```

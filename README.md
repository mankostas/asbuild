# Asbuild

Foundation for a Laravel 11 backend and Vue 3 single-page application.

## Production checklist for Plesk

1. Set the domain's document root to `public`.
2. Configure PHP 8.1+ with:
   - `memory_limit=512M`
   - `max_execution_time=120`
   - `upload_max_filesize=50M` and `post_max_size=50M`
   - Enable the `imagick` extension
3. Environment:
   - Copy `backend/.env.example` to `backend/.env` and adjust `APP_URL` and database credentials.
   - Set `API_MODE=development` to stream logs to the console when running `php artisan serve`; use `production` to write logs to `storage/logs/laravel.log`.
   - Install dependencies and generate an app key:
     ```bash
     cd backend && composer install && php artisan key:generate
     ```
   - Run database migrations:
     ```bash
     php artisan migrate --force
     ```
4. Build the frontend:
   ```bash
   cd frontend && npm install && npm run build
   ```
5. Queues & cron jobs:
   - Ensure `QUEUE_CONNECTION=database` in `.env`.
   - Configure scheduled tasks in Plesk:
     ```
     * * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
     * * * * * cd /path/to/backend && php artisan queue:work --once >> /dev/null 2>&1
     ```
6. Health check: visit `/api/health` to confirm the app is running.

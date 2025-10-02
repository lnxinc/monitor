# MSP Monitor

MSP Monitor is a Laravel + Vue application built for managed service providers to track
infrastructure health across data warehouses, Spectrum servers, customer websites, SSL
certificates, and third-party telemetry delivered via webhooks. Checks run on a schedule
through dedicated drivers (HTTP, ICMP ping, TCP port, SSL certificate) with alert routing,
maintenance windows, and public status pages baked in.

## Key Capabilities
- Multi-protocol monitoring with driver architecture (HTTP, ping, TCP, SSL, webhook ingest)
- Per-monitor policies and notification channels (email, Slack webhooks)
- Maintenance windows to suppress noise during planned work
- UniFi Site Manager integration for automated device sync
- Public-facing status page support via Inertia/SPA frontend
- Queue-driven execution and centralized incident tracking

## Requirements
- PHP **8.2** or newer with required extensions (`bcmath`, `ctype`, `openssl`, `pdo`, `mbstring`, `curl`)
- Composer **2.x**
- Node.js **18+** and npm (or pnpm/yarn) for frontend assets
- Redis, SQS, or database queue driver (database queue enabled out of the box)
- A SQL database (MySQL 8+, MariaDB 10.5+, or PostgreSQL 13+)
- Supervisor/Process manager for long-running queue workers and scheduled commands

Optional:
- Access to UniFi Site Manager API (configure `UNIFI_SITE_MANAGER_BASE_URL` and `UNIFI_SITE_MANAGER_API_KEY`)
- Mail provider credentials for outbound alerts and user notifications

## First-Time Setup
1. **Clone & Install Dependencies**
   ```bash
   git clone <repo-url>
   cd monitor
   composer install --no-interaction --prefer-dist
   npm install
   ```

2. **Configure Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Update `.env` with database credentials, queue driver, mail settings, and any optional
   integrations (Slack, UniFi, etc.).

3. **Run Migrations**
   ```bash
   php artisan migrate --force
   ```

4. **Build Frontend Assets** (development)
   ```bash
   npm run dev
   ```
   For production: `npm run build` (or `npm run build && npm run build:ssr` if SSR is used).

## Local Development
- Launch the bundled dev stack: `composer run dev`
  - Serves the app, queue listener, log tailing, and Vite via `concurrently`.
- Alternatively run services individually:
  ```bash
  php artisan serve
  php artisan queue:listen --tries=1
  npm run dev
  ```
- Execute the scheduler locally with: `php artisan schedule:work`

## Deployment Guide
1. **Install PHP Dependencies** on the target host (as in setup) using `composer install --no-dev --optimize-autoloader`.
2. **Install Frontend Assets** via `npm ci && npm run build` from the release directory.
3. **Set Environment** variables and ensure `.env` has production credentials.
4. **Run Database Migrations** during each deploy: `php artisan migrate --force`.
5. **Cache Optimizations** (optional but recommended):
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
6. **Queue Worker**: supervise `php artisan queue:work --sleep=1 --tries=3` (or use Horizon).
7. **Scheduler**: configure cron to run every minute:
   ```cron
   * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
   ```
   This dispatches monitor checks (`monitors:run`) and UniFi sync jobs.
8. **Permissions**: grant web server user write access to `storage/` and `bootstrap/cache/`.

## Monitoring & Maintenance
- Manual ad-hoc monitor run: `php artisan monitors:run` or POST to `/monitors/{id}/run` in the UI.
- Webhook monitor ingestion endpoint: `POST /api/webhooks/monitors/{token}` with JSON payload.
- Queue health: tail `storage/logs/laravel.log` and `storage/logs/schedule.log` for job/scheduler output.

## Testing
```bash
php artisan test        # Runs full Pest test suite
```

CI/CD pipelines should execute the test suite and a linter/formatter (e.g., `vendor/bin/pint`) prior to deploys.

## Additional Notes
- Configure trusted proxies/load balancers via `App\Http\Middleware\TrustProxies` if deploying behind a proxy.
- For SSL/TLS probing support ensure the host running checks can establish outbound connections
  (ICMP, TCP, HTTP/HTTPS as needed).
- When using the ping driver on Linux, grant the PHP process permission to run `ping` (setcap or wrapper).


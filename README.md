# Log Cabin for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/forestry/logcabin-laravel.svg)](https://packagist.org/packages/forestry/logcabin-laravel)
[![Tests](https://github.com/forestrylabs/logcabin-laravel/actions/workflows/tests.yml/badge.svg)](https://github.com/forestrylabs/logcabin-laravel/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/forestry/logcabin-laravel.svg)](https://packagist.org/packages/forestry/logcabin-laravel)
[![License](https://img.shields.io/packagist/l/forestry/logcabin-laravel.svg)](LICENSE)

Ships logs and health heartbeats from a Laravel application to a central
[Log Cabin](https://github.com/forestrylabs/logcabin) panel.

Once installed, existing `Log::error()` calls and unhandled exceptions are delivered to your
panel with no code changes, delivery is queued so a panel outage never blocks your app, and a
self-scheduled heartbeat reports the site's health every few minutes.

## Requirements

- PHP 8.2 or higher
- Laravel 11, 12 or 13

## Installation

Install the package via Composer:

```bash
composer require forestry/logcabin-laravel
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=logcabin-config
```

Then set the following in your `.env`:

```dotenv
LOGCABIN_ENDPOINT=https://logcabin.example.com
LOGCABIN_TOKEN=the-token-issued-from-the-Log-Cabin-panel
QUEUE_CONNECTION=database
```

The token comes from the Log Cabin panel: open the site under **Sites**, then use the
**Generate API Token** action on the site's edit page. The plaintext token is only shown once,
so copy it straight into `.env`. The token alone identifies the site.

> **Note:** No Laravel Sanctum install is needed on your app. This package is a plain HTTP
> client that sends an `Authorization: Bearer <token>` header; Sanctum only lives on the Log
> Cabin panel to verify that token.

`QUEUE_CONNECTION` matters: log and heartbeat delivery is queued so a Log Cabin outage never
blocks the client app's own requests. If the app already uses a non-sync queue driver (redis,
database, etc.) nothing further is needed. If it's on `sync`, switch it to `database` (run
`php artisan queue:table && php artisan migrate` if that table doesn't exist yet) and make sure
a queue worker is running (see [Production setup](#production-setup) below).

## Configuration

All settings are driven by environment variables, so you rarely need to edit the published
config file directly.

| Variable | Default | Description |
| --- | --- | --- |
| `LOGCABIN_ENDPOINT` | `https://logcabin.example.com` | Base URL of the Log Cabin panel. |
| `LOGCABIN_TOKEN` | `null` | API token identifying the site. |
| `LOGCABIN_ENABLED` | `true` | Master switch; when `false`, nothing is delivered. |
| `LOGCABIN_QUEUE` | `default` | Queue name the delivery jobs run on. |
| `LOGCABIN_LOG_LEVEL` | `error` | Minimum log level captured by the `logcabin` channel. |
| `LOGCABIN_AUTO_ATTACH` | `true` | Append the `logcabin` channel to the `stack` channel automatically. |
| `LOGCABIN_HEARTBEAT_INTERVAL` | `5` | Minutes between automatic heartbeats. |

## Usage

### Automatic error capture

By default, the package appends a `logcabin` log channel to your app's `stack` channel, so
existing `Log::error()` calls and unhandled exceptions ship automatically with no code changes
required. Set `LOGCABIN_AUTO_ATTACH=false` to disable this if your app doesn't log through
`stack`.

### Manual reporting

Report events or exceptions explicitly through the `LogCabin` facade:

```php
use Forestry\LogCabin\Laravel\Facades\LogCabin;

LogCabin::report('payment.failed', 'Charge declined', ['order_id' => $order->id]);
LogCabin::reportException($exception);
```

### Heartbeats

A `logcabin:heartbeat` command is registered and self-scheduled (every
`LOGCABIN_HEARTBEAT_INTERVAL` minutes, default 5). No changes to your app's scheduler are
required, as long as `php artisan schedule:run` is already running via cron.

## Production setup

Three background processes need to be running on the server for this package to actually deliver
anything. It fails silently if they aren't, by design, so that it never takes the app down.

1. **Cron entry for the scheduler** (drives both the heartbeat and the scheduler-liveness
   signal):
   ```cron
   * * * * * cd /path/to/your/app && php artisan schedule:run >> /dev/null 2>&1
   ```
2. **A queue worker** processing the queue named in `LOGCABIN_QUEUE` (default `default`). This
   is what actually sends the HTTP requests for logs and heartbeats. Under Supervisor:
   ```ini
   [program:app-queue-worker]
   command=php /path/to/your/app/artisan queue:work --sleep=3 --tries=3 --max-time=3600
   directory=/path/to/your/app
   autostart=true
   autorestart=true
   numprocs=1
   user=www-data
   redirect_stderr=true
   stdout_logfile=/path/to/your/app/storage/logs/queue-worker.log
   ```
   Reload with `supervisorctl reread && supervisorctl update && supervisorctl restart
   app-queue-worker`. If the app already runs a queue worker for its own jobs, this package's
   jobs ride along on the same worker. No separate process is needed unless you want the
   `LOGCABIN_QUEUE` isolated.
3. **Outbound HTTPS access** from the server to `LOGCABIN_ENDPOINT`. Check firewall and egress
   rules if tokens validate locally (`php artisan tinker` then
   `Http::withToken(...)->post(...)`) but nothing shows up in the panel.

Quick end-to-end check after deploying:

```bash
php artisan logcabin:heartbeat   # run once manually; should queue a heartbeat job
php artisan queue:work --once    # process it
```

Then confirm the site shows a recent `last_seen_at` in the Log Cabin panel. If it doesn't move,
check `storage/logs/laravel.log` on the client site. The package's jobs log delivery failures
there after retries are exhausted, via the `single` channel rather than through `logcabin`
itself, to avoid a retry loop when the app's default log channel includes `logcabin`.

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes.

## License

The GNU Affero General Public License v3.0 or later (AGPL-3.0-or-later). See [LICENSE](LICENSE)
for details.

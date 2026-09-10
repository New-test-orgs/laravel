# Script Executions

Laravel web app for previewing store migrations and running PHP scripts from GitHub. You create a migration, paste a permalink to an allowed repository, and a queued job fetches, loads, and executes the script. Progress and logs are stored in the database and shown in the UI.

Scripts must come from one of these repositories:

- [cart2cart/cart2cart-customization-laravel](https://github.com/cart2cart/cart2cart-customization-laravel)
- [New-test-orgs/laravel](https://github.com/New-test-orgs/laravel)

Use a blob permalink with a commit SHA (short or full), for example:

```text
https://github.com/cart2cart/cart2cart-customization-laravel/blob/a1b2c3d/scripts/demo.php
```

When a script is queued, the worker loads source and target store credentials from Cart2Cart and injects both into `env()` for that run:

| Variable | Store |
| --- | --- |
| `SOURCE_CART_ID` | Source cart id |
| `SOURCE_STORE_URL` | Source store URL |
| `SOURCE_ACCOUNT_EMAIL` | Source account email |
| `SOURCE_ACCOUNT_TOKEN` | Source account token |
| `SOURCE_CONNECTION` | Source connection type |
| `SOURCE_CART_VERSION` | Source cart version |
| `SOURCE_VARS` | Source extra vars as JSON |
| `SOURCE_VALIDATED` | Source validation flag |
| `TARGET_CART_ID` | Target cart id |
| `TARGET_STORE_URL` | Target store URL |
| `TARGET_ACCOUNT_EMAIL` | Target account email |
| `TARGET_ACCOUNT_TOKEN` | Target account token |
| `TARGET_CONNECTION` | Target connection type |
| `TARGET_CART_VERSION` | Target cart version |
| `TARGET_VARS` | Target extra vars as JSON |
| `TARGET_VALIDATED` | Target validation flag |

Example:

```php
env('SOURCE_STORE_URL');
env('SOURCE_ACCOUNT_TOKEN');
env('TARGET_STORE_URL');
env('TARGET_ACCOUNT_TOKEN');
```

`scripts/dump-store-credentials.php` logs both sets so you can confirm the payload. The variables are cleared when the script finishes.

## Requirements

- PHP 8.3 or later, with Composer
- Node.js 20.19 or later, with npm
- SQLite (default) or another database supported by Laravel

## Getting started

```bash
git clone <repository-url>
cd laravel
composer run setup
```

That command installs PHP and Node dependencies, copies `.env.example` to `.env` if needed, generates the application key, runs migrations, and builds frontend assets.

Alternatively, step by step:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

## Configuration

Edit `.env` after setup. The values that matter for this app:

| Variable | Purpose |
| --- | --- |
| `APP_URL` | Base URL (default `http://localhost:8000`) |
| `DB_CONNECTION` | Database driver (default `sqlite`) |
| `QUEUE_CONNECTION` | Must stay `database` so script jobs run in the background |
| `GITHUB_TOKEN` | GitHub personal access token used to fetch script files |
| `C2C_API_BASE_URL` | Cart2Cart REST API base URL (default `https://api.newapp.shopping-cart-migration.com`) |
| `C2C_USER_EMAIL` | Cart2Cart admin login email |
| `C2C_USER_PASSWORD` | Cart2Cart admin login password |

Create `database/database.sqlite` if you use SQLite and the file does not exist yet:

```bash
touch database/database.sqlite
php artisan migrate
```

## Running the app

Start the HTTP server, queue worker, log tail, and Vite together:

```bash
composer run dev
```

Or run them separately:

```bash
php artisan serve
php artisan queue:work
php artisan pail
npm run dev
```

Open [http://localhost:8000](http://localhost:8000).

Script jobs time out after 30 minutes and are not retried. The queue worker must stay running or queued executions will not start.

## Tests

```bash
composer test
```

Or:

```bash
php artisan test
```

## License

MIT

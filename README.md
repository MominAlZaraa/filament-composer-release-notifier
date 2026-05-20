# Filament Composer Release Notifier

[![License](https://img.shields.io/packagist/l/mominalzaraa/filament-composer-release-notifier?style=flat-square)](https://packagist.org/packages/mominalzaraa/filament-composer-release-notifier)

Compare packages declared in your root `composer.json` with versions pinned in `composer.lock`, determine the **latest published version** (default: **Packagist** `p2` JSON, no token), optionally enrich with GitHub release/compare data, cache results in the database, and surface them in Filament (resource + dashboard widget). Optional HTML email summary when mail is configured.

**Requirements**: PHP ^8.3 · Laravel ^12.0 \| ^13.0 · Filament ^5.0

## Installation

```bash
composer require mominalzaraa/filament-composer-release-notifier
```

Publish and run migrations:

```bash
php artisan vendor:publish --tag="filament-composer-release-notifier-migrations"
php artisan migrate
```

Publish config (optional):

```bash
php artisan vendor:publish --tag="filament-composer-release-notifier-config"
```

Register the panel plugin (example):

```php
use MominAlZaraa\FilamentComposerReleaseNotifier\FilamentComposerReleaseNotifierPlugin;

$panel->plugins([
    FilamentComposerReleaseNotifierPlugin::make()
        ->resource(enabled: true)
        ->widget(enabled: true)
        ->mailReports(enabled: true),
]);
```

### Filament + Tailwind v4 (`viteTheme`)

If this package’s Blade views live under `vendor/mominalzaraa/filament-composer-release-notifier`, add a Tailwind `@source` entry in your Filament theme CSS (e.g. `resources/css/app.css`) so utilities used in the release-details modal are included when you run `npm run build`:

```css
@source '../../vendor/mominalzaraa/filament-composer-release-notifier/resources/views/**/*.blade.php';
```

## Version source (Packagist vs GitHub)

By default (`FILAMENT_COMPOSER_RELEASE_NOTIFIER_VERSION_SOURCE=packagist` or `version_source` = `packagist` in config), the sync job reads **public** Composer metadata from `repo.packagist.org` (`/p2/{vendor}/{package}.json`). No GitHub token is required for that step.

When the lock file’s `source.url` points at `github.com`, the package stores a **browser** compare link built as `https://github.com/{owner}/{repo}/compare/{installed}...{latest}` — this uses only the public website URL, not the GitHub API.

Optional: set `FILAMENT_COMPOSER_RELEASE_NOTIFIER_PACKAGIST_FETCH_GITHUB_COMMITS=true` to also call GitHub’s **compare API** for commit summaries while still using Packagist for versions (anonymous rate limits apply without a token).

Switch to `version_source` = `github` to use **GitHub Releases** + compare API as the primary source (useful when you care about GitHub release notes/tags rather than Packagist tags).

Packagist options: `FILAMENT_COMPOSER_RELEASE_NOTIFIER_PACKAGIST_URL`, `FILAMENT_COMPOSER_RELEASE_NOTIFIER_PACKAGIST_TIMEOUT`, `FILAMENT_COMPOSER_RELEASE_NOTIFIER_PACKAGIST_USER_AGENT` (see published config).

## Queue & sync

- After login, a **queued job** refreshes snapshots once per session (configure your queue worker). Logging out clears that session flag so the **next login** can queue a fresh sync again.
- On the **Composer packages** screen, use **Check again** to run a full refresh immediately (runs the sync job synchronously so it works even if a queue worker is not running).
- For **GitHub API** mode or optional commit fetch: set `GITHUB_TOKEN` in `.env` (and `github.token` in config) for higher rate limits and private repositories.

## Testing

From this package’s root (after a successful `composer install` with dev dependencies):

```bash
composer test
```

You can also run only the unit tests from a host application that already has Pest, for example:

```bash
./vendor/bin/pest /path/to/filament-composer-release-notifier/tests/Unit
```

Feature tests boot Laravel via `orchestra/testbench` and should be executed from the package root so `tests/Pest.php` applies to `tests/Feature/`.

## Local development & tests

`composer install` for this package downloads several GitHub-hosted dist archives. If you hit **“Could not authenticate against github.com”**, configure a token:

```bash
composer config --global github-oauth.github.com YOUR_GITHUB_TOKEN
```

CI uses `secrets.GITHUB_TOKEN` via `COMPOSER_AUTH` (see [.github/workflows/run-tests.yml](.github/workflows/run-tests.yml)).

## Suggested git checkpoints (staged delivery)

1. Package skeleton + CI + Pest bootstrap  
2. Migrations, model, composer lock reader  
3. GitHub client, sync service, queue job  
4. Filament plugin, resource, widget  
5. Login listener  
6. Mail report + tests  
7. App integration (path repository, panel plugin, migrations)

## Privacy

In **packagist** mode, the job calls **Packagist** (and optionally GitHub’s API if you enable commit fetch or use `version_source=github`). It uses dependency names and public repository metadata from your lock file. It does not send your application source code.

## License

MIT. See [LICENSE](LICENSE).

# Security Policy

## Supported versions

Security fixes are applied to the latest release of this package.

| Version | Supported |
| ------- | --------- |
| 1.x     | ✅        |
| < 1.0   | ❌        |

## Reporting a vulnerability

Please **do not** open a public GitHub issue for security vulnerabilities.

Report privately by emailing **[support@mominpert.com](mailto:support@mominpert.com)** with:

- A clear description of the issue and impact
- Steps to reproduce (or a minimal proof of concept)
- Affected package version(s) and your Laravel / Filament versions

You can also use [GitHub private vulnerability reporting](https://github.com/MominAlZaraa/filament-composer-release-notifier/security/advisories/new) if it is enabled on this repository.

## What to expect

- We aim to acknowledge reports within **72 hours**
- We will keep you informed while investigating and preparing a fix
- Once a fix is available, we will coordinate disclosure and credit you if you wish

## Scope

This plugin compares Composer package versions (Packagist / optional GitHub APIs), surfaces them in Filament, and can send informational email digests. It does **not** run `composer update`. Reports are especially welcome for issues that could:

- Exfiltrate GitHub tokens or other credentials configured for version checks
- Leak private repository release metadata to unauthorized panel users
- Abuse scheduled jobs / notifications to spam or escalate privileges
- Cause unexpected outbound requests or SSRF via package metadata URLs

Out of scope: vulnerabilities solely in upstream dependencies (Laravel, Filament, Composer metadata providers) unless our usage introduces a distinct risk.

Thank you for helping keep this package and its users safe.

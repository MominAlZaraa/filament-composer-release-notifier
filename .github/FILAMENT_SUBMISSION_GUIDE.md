# Listing on the Filament plugin directory

The Filament website now uses an **author admin UI** for plugins (no GitHub PR flow to `filamentphp.com` for new listings).

## 1. Request author access

1. Open **[filamentphp.com/author](https://filamentphp.com/author)** and request access to your author profile.  
2. After it is linked to your account, add or update plugins from the dashboard.

## 2. Prepare assets (this repository)

| Item | Location / note |
|------|------------------|
| Plugin image (16:9, ≥2560×1440, JPEG) | [.github/plugin-banner.jpg](plugin-banner.jpg) |
| README images | Use **absolute** `https://raw.githubusercontent.com/MominAlZaraa/filament-composer-release-notifier/main/...` URLs so they embed on filamentphp.com ([guideline #8](https://github.com/filamentphp/filamentphp.com/blob/main/PLUGIN_REVIEW_GUIDELINES.md)) |
| Duplicate hero + README banner | If the same banner URL appears twice on the plugin page, add `class="filament-hidden"` to the README `<img>` ([guideline #6](https://github.com/filamentphp/filamentphp.com/blob/main/PLUGIN_REVIEW_GUIDELINES.md)) |
| Author avatar (1:1, ≥1000×1000) | See [.github/AUTHOR.md](AUTHOR.md) |
| Sponsoring | [.github/FUNDING.yml](FUNDING.yml) |

## 3. Copy-paste helper

Structured fields for drafts or the author UI live in [.github/PLUGIN_INFO.json](PLUGIN_INFO.json) (name, slug, package, description, tags, requirements, screenshot URLs).

## 4. Documentation quality

- Capitalize **Filament** correctly everywhere.  
- State clearly that the plugin is **informational** and does **not** run `composer update`.  
- Prefer screenshots that **highlight** the widget and resource (not only a full-panel chrome shot), per [review scenario #7](https://github.com/filamentphp/filamentphp.com/blob/main/PLUGIN_REVIEW_GUIDELINES.md).

## 5. Categories

Pick valid categories in the author UI (examples that often fit this package: **Developer tool**, **Widget**, **Kit** — follow whatever the current site lists).

## Official references

- [Plugin review guidelines](https://github.com/filamentphp/filamentphp.com/blob/main/PLUGIN_REVIEW_GUIDELINES.md)  
- [filamentphp.com repository README (archived PR workflow)](https://github.com/filamentphp/filamentphp.com/blob/main/README.md) — points to **filamentphp.com/author** for submissions now  

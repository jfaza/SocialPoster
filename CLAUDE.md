# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

`socialposter` is a custom ExpressionEngine add-on for generating, scheduling, and reviewing social posts via OpenAI. It was scaffolded with the EE CLI generators — preserve the generated add-on layout and naming conventions.

## Key Paths

- `addon.setup.php` — add-on metadata, service bindings, actions, commands, and CP routes
- `Actions/GeneratePost.php` — CSRF-protected generation action endpoint
- `Commands/CommandRunSchedule.php` — CLI scheduler for due posts
- `ControlPanel/Routes/` — CP route handlers (keep thin: parse input → call service → return view)
- `ControlPanel/Sidebar.php` — CP sidebar definition
- `Service/` — all business logic (OpenAI, generation, scheduling, publishing, templates, storage)
- `database/migrations/` — install and schema migrations
- `views/` — CP views and add-on CSS (presentational only)
- `language/english/socialposter_lang.php` — language strings

## Service Layer Responsibilities

| Service | Responsibility |
|---|---|
| `OpenAiClient` | OpenAI HTTP transport only |
| `SocialPostGenerator` | Generation behavior and prompt logic |
| `Publisher` | Publishing to EE or external platforms (Instagram etc.) |
| `TemplateManager` | Reusable generation templates (length, tone, audience, citations, links, schema, image style) |
| `Scheduler` | Recurring schedule execution; must be CLI-safe |

Do not put integration logic directly in views or CP route classes.

## CLI Commands

Run from the project root (`/var/www/html`):

```bash
php system/ee/eecli.php socialposter:run-schedule
php system/ee/eecli.php socialposter:run-schedule --limit=5
php system/ee/eecli.php addons:list
php system/ee/eecli.php migrate:addon --help
```

## Verification

Lint a single file after editing:
```bash
php -l path/to/file.php
```

Lint all add-on PHP files:
```bash
for f in \
  /var/www/html/system/user/addons/socialposter/*.php \
  /var/www/html/system/user/addons/socialposter/Actions/*.php \
  /var/www/html/system/user/addons/socialposter/Commands/*.php \
  /var/www/html/system/user/addons/socialposter/ControlPanel/*.php \
  /var/www/html/system/user/addons/socialposter/ControlPanel/Routes/*.php \
  /var/www/html/system/user/addons/socialposter/Service/*.php \
  /var/www/html/system/user/addons/socialposter/database/migrations/*.php \
  /var/www/html/system/user/addons/socialposter/views/*.php; \
do php -l "$f" || exit 1; done
```

When changing CP behavior, review the affected route and view together. When changing schema or install behavior, check both `upd.socialposter.php` and the relevant migration.

## OpenAI Models

Configured in `Service/SocialPostGenerator.php`. Current defaults:
- Text: `gpt-6.1-sol` (options: `gpt-6.1-terra`, `gpt-6-astra`, `gpt-6-luna`)
- Image: `gpt-image-2.5-sunburst` (options: `gpt-image-2.5-flare`, `gpt-image-2`)

## Rules

- Keep all OpenAI and platform integration logic inside `Service/` — never in views or CP routes.
- `GeneratePost` action must remain CSRF-protected; validate all input before passing to services.
- Schedules and history rows may store `template_id` to reuse generation defaults.
- Keep scheduler code CLI-safe — no control-panel-only assumptions.
- Do not edit EE core files under `system/ee/` for add-on changes.
- Do not expose OpenAI keys, secrets, CP URLs, or database credentials in code, logs, views, or fixtures.
- Prefer EE services, models, and documented APIs over hard-coded framework internals.

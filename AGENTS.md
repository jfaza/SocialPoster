---
name: Socialposter Add-on Instructions
description: Local development instructions for the ExpressionEngine socialposter add-on
---

# Socialposter Add-on

`socialposter` is a custom ExpressionEngine add-on for generating, scheduling,
and reviewing social posts. It was scaffolded with ExpressionEngine CLI
generators, so preserve the generated add-on layout and naming conventions.

# Important Paths

- `addon.setup.php`: add-on metadata, services, actions, commands, and CP routes.
- `Actions/GeneratePost.php`: CSRF-protected generation action.
- `Commands/CommandRunSchedule.php`: CLI schedule runner for generated posts.
- `ControlPanel/Routes/`: ExpressionEngine control panel routes.
- `ControlPanel/Sidebar.php`: add-on CP sidebar definition.
- `Service/`: business logic, publishing, scheduling, and integrations.
- `database/migrations/`: install and schema migrations.
- `views/`: control panel views and add-on CSS.
- `language/english/socialposter_lang.php`: add-on language lines.

# Implementation Guidelines

- Keep OpenAI API and post generation logic in namespaced services under
  `Service/`; do not put integration logic directly in views or CP route classes.
- Use `OpenAiClient` for OpenAI transport concerns and `SocialPostGenerator` for
  generation behavior.
- Use `Publisher` for publishing generated content into ExpressionEngine or
  external platforms. Keep platform-specific publishing logic out of CP views.
- Use `TemplateManager` for reusable generation templates. Schedules and history
  rows may store `template_id` so recurring posts can reuse defaults for length,
  tone, audience, citations, links, schema, and image style.
- Use `Scheduler` and the `socialposter:run-schedule` command for recurring
  generation. Keep schedule execution CLI-safe and avoid control-panel-only
  assumptions in scheduler code.
- Keep `GeneratePost` CSRF-protected and validate CP/action input before passing
  it into services.
- Prefer ExpressionEngine services, models, helpers, and documented APIs over
  hard-coded framework internals.
- Keep CP route classes thin: parse request data, call services, then return the
  relevant view.
- Keep views mostly presentational. Avoid database writes, API calls, or complex
  generation decisions inside view files.
- Do not edit ExpressionEngine core files under `system/ee/` for add-on changes.
- Do not expose OpenAI keys, site secrets, CP URLs, or database credentials in
  code, logs, views, or fixtures.

# Verification

For PHP edits, run `php -l` on each edited file. For broader changes, lint all
add-on PHP files:

```bash
for f in /var/www/html/system/user/addons/socialposter/*.php /var/www/html/system/user/addons/socialposter/Actions/*.php /var/www/html/system/user/addons/socialposter/Commands/*.php /var/www/html/system/user/addons/socialposter/ControlPanel/*.php /var/www/html/system/user/addons/socialposter/ControlPanel/Routes/*.php /var/www/html/system/user/addons/socialposter/Service/*.php /var/www/html/system/user/addons/socialposter/database/migrations/*.php /var/www/html/system/user/addons/socialposter/views/*.php; do php -l "$f" || exit 1; done
```

Useful ExpressionEngine CLI commands from the project root:

```bash
php system/ee/eecli.php addons:list
php system/ee/eecli.php migrate:addon --help
php system/ee/eecli.php socialposter:run-schedule
```

When changing CP behavior, review the affected route and view together. When
changing schema or install behavior, check both `upd.socialposter.php` and the
relevant migration.

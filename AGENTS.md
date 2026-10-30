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

# Current Workflow Reference

- Main control-panel areas now include shared `News Sources`, general
  `Generate`, `Calendar`, `History`, `Published`, `Publish Blog`, and the
  Instagram-specific tools. Prefer shared placement for capabilities that can
  serve both website and Instagram generation.
- `News Sources` is no longer just an Instagram helper. General website/blog
  generation can use current feed material and hot-article context.
- The main generator page in `views/Index.php` has been simplified into a
  top-to-bottom flow. Keep it focused on one authoring path instead of
  reintroducing cluttered parallel sections or a separate result-tab mindset.
- The Templates page in `views/Templates.php` is organized around
  `Purpose -> Behavior -> Instructions`. Keep prompt instructions prominent and
  keep the library scannable rather than turning it back into a long flat form.
- The template library includes a default website template named
  `Hot Tech & Dev Blog Article`. It is intended for timely technology/developer
  blog articles and has a custom article structure branch in
  `Service/TemplateManager.php`.
- The generic `Publish` route is intentionally blog-focused. Do not present
  unsupported targets there as actionable publish options unless their workflow
  is genuinely implemented.
- Schedule editing is supported from the calendar/schedules UI. Recurrence is
  no longer limited to vague presets; the current model supports once/every,
  interval-based recurrence, and weekday selection for weekly schedules.
- The calendar month view renders schedule placeholders for upcoming runs. Keep
  that visibility if the calendar UI is changed.
- Schedule forms show the real saved template details and prompt instructions,
  not invented summaries. When adding previews, prefer exact saved template data
  or a link back to the template editor.
- `Planned Topics` is optional queue input, one topic/instruction per line. If
  it is blank, the scheduler falls back to template instructions and/or hot news
  context depending on the workflow.
- History now surfaces the template used for generation. Preserve template
  traceability in future history or publish views.
- Duplicate-topic protection is a shared concern, not a scheduler-only hack.
  Reuse the duplicate-guard support in `Service/SocialPostGenerator.php` and
  related services rather than inventing per-screen duplicate checks.
- Current duplicate protection has been wired into:
  - scheduled generation
  - main manual Generate flow
  - Instagram single-image assist and generation
  - carousel assist and generation
- Facebook is currently treated as a publishing destination, not a distinct
  topic-generation workflow. Do not assume parity with website/Instagram
  generation flows unless that work is explicitly added.
- `Actions/GeneratePost.php` has been refactored into named internal handlers by
  workflow mode while preserving the single endpoint contract. Keep new modes in
  dedicated handlers or move them into dedicated actions/services if they grow.
- Packaging note: the add-on directory currently contains local development
  artifacts such as `.git` and `carousel/venv`. Treat cleanup/exclusion of those
  artifacts as packaging work, not as runtime dependencies.

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
- When updating default templates, account for existing installs. If a template
  already exists, prefer targeted sync/update behavior for known stock defaults
  rather than overwriting user-customized templates.
- Use `Scheduler` and the `socialposter:run-schedule` command for recurring
  generation. Keep schedule execution CLI-safe and avoid control-panel-only
  assumptions in scheduler code.
- Keep recurrence logic centralized in `Scheduler`; do not duplicate schedule
  timing behavior in routes or views.
- Keep `GeneratePost` CSRF-protected and validate CP/action input before passing
  it into services.
- Keep `GeneratePost` thin at the top level: route by mode, then delegate to
  named handlers/services rather than growing a single giant `process()` branch.
- Prefer ExpressionEngine services, models, helpers, and documented APIs over
  hard-coded framework internals.
- Keep CP route classes thin: parse request data, call services, then return the
  relevant view.
- Keep views mostly presentational. Avoid database writes, API calls, or complex
  generation decisions inside view files.
- When a CP screen previews template behavior or source context, use exact saved
  data from services/routes rather than synthesizing a loose summary in the view.
- Reuse shared hot-news/source-management capabilities across website and
  Instagram workflows when timely topic context is needed.
- Reuse shared duplicate-guard behavior from generator services for any new
  workflow that allows the model to choose or suggest topics.
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

When changing workflow-heavy areas, also verify the surrounding connected
surfaces:

- `Generate` changes: check `Actions/GeneratePost.php`, `views/Index.php`, and
  any route data feeding that view.
- `Template` changes: check `Service/TemplateManager.php`,
  `ControlPanel/Routes/Templates.php`, and `views/Templates.php`.
- `Schedule` changes: check `Service/Scheduler.php`,
  `ControlPanel/Routes/Calendar.php`, `views/Calendar.php`, and relevant
  migrations.
- `Publish` changes: check `Service/Publisher.php`,
  `ControlPanel/Routes/Publish.php`, and `views/Publish.php`.

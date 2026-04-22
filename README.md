# SocialPoster

SocialPoster is an ExpressionEngine add-on for generating, editing, scheduling,
and publishing social post content with OpenAI.

It can generate post copy, SEO metadata, hashtags, recommended topics, and
images, then store the results for review or scheduled publishing from the
ExpressionEngine control panel.

## Features

- OpenAI text and image generation.
- Control panel screens for generation, history, publishing, scheduling, and
  reusable templates.
- Encrypted API key storage.
- Generated image storage under the ExpressionEngine uploads path.
- Scheduled generation through the `socialposter:run-schedule` CLI command.
- PHP syntax linting through GitHub Actions.

## Requirements

- ExpressionEngine with add-on support.
- PHP compatible with the installed ExpressionEngine version.
- An OpenAI API key with access to the configured text and image models.

Default models are configured in `Service/SocialPostGenerator.php`:

- Text: `gpt-5.4`
- Image: `gpt-image-1.5`

## Installation

Place this repository at:

```text
system/user/addons/socialposter
```

Then install the add-on from the ExpressionEngine control panel, or use the
ExpressionEngine CLI from the project root:

```bash
php system/ee/eecli.php addons:install socialposter
```

If the add-on is already installed and migrations have changed, run the add-on
update flow from ExpressionEngine.

## Configuration

Open the SocialPoster settings screen in the ExpressionEngine control panel and
configure:

- OpenAI API key
- Text model, including `gpt-5.4` and `gpt-5.4-mini`
- Image model, including `gpt-image-2`
- Image size
- Image quality

The API key is encrypted before it is stored.

## Scheduling

Due schedules can be processed from the ExpressionEngine project root:

```bash
php system/ee/eecli.php socialposter:run-schedule
```

To limit the number of schedules processed in one run:

```bash
php system/ee/eecli.php socialposter:run-schedule --limit=5
```

For production use, run this command from cron or another scheduler.

## Development

Important paths:

- `addon.setup.php`: add-on metadata, service bindings, and commands.
- `Actions/GeneratePost.php`: generation action endpoint.
- `Commands/CommandRunSchedule.php`: scheduled generation command.
- `ControlPanel/Routes/`: control panel route handlers.
- `Service/`: OpenAI, generation, scheduling, publishing, storage, and template
  services.
- `database/migrations/`: database schema migrations.
- `views/`: control panel views and CSS.

Run PHP syntax checks before committing:

```bash
find . \
  -path './.git' -prune -o \
  -type f -name '*.php' -print0 \
  | xargs -0 -n 1 php -l
```

## CI

GitHub Actions runs PHP syntax linting on pushes to `main` and on pull requests.
The workflow is defined in:

```text
.github/workflows/ci.yml
```

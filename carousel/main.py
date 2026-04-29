from __future__ import annotations

import argparse
from pathlib import Path

from ai.generator import generate_with_ai
from ai.template_generator import generate_template, repair_template
from core.config import DEFAULT_TEMPLATE
from core.exporter import export_images, preview_template
from core.files import make_new
from core.news import list_channels, run_news
from core.templates import (
    list_templates,
    load_schema,
    select_template,
    template_html,
    validate_template,
)


def main() -> None:
    parser = argparse.ArgumentParser(description="AI Instagram carousel generator")
    sub = parser.add_subparsers(dest="command", required=True)

    new_cmd = sub.add_parser("new", help="Create a starter data.json without AI")
    new_cmd.add_argument("topic")
    new_cmd.add_argument("--template", default=DEFAULT_TEMPLATE)

    gen_cmd = sub.add_parser("generate", help="Generate data.json with OpenAI")
    gen_cmd.add_argument("topic")
    gen_cmd.add_argument("--template", default=None)
    gen_cmd.add_argument("--style", default="base")
    gen_cmd.add_argument(
        "--audience",
        default="developers, creators, and AI-curious professionals",
    )
    gen_cmd.add_argument(
        "--export",
        action="store_true",
        help="Export PNGs after generating JSON",
    )
    gen_cmd.add_argument(
        "--auto-template",
        action="store_true",
        help="Auto-select template based on style and topic",
    )

    export_cmd = sub.add_parser("export", help="Export PNGs from an existing data.json")
    export_cmd.add_argument("data_json")
    export_cmd.add_argument("--template", default=None)

    tpl_cmd = sub.add_parser("gen-template", help="Generate a new template package")
    tpl_cmd.add_argument("name")
    tpl_cmd.add_argument("--brief", default=None)
    tpl_cmd.add_argument(
        "--preview",
        action="store_true",
        help="Export example.json through the template immediately after generation",
    )

    prev_cmd = sub.add_parser("preview-template", help="Export example.json through a template")
    prev_cmd.add_argument("name")

    repair_cmd = sub.add_parser("repair-template", help="Rebuild schema.json from example.json")
    repair_cmd.add_argument("name")

    validate_cmd = sub.add_parser("validate-template", help="Validate a template package")
    validate_cmd.add_argument("name")

    batch_cmd = sub.add_parser(
        "batch", help="Generate posts from a topics file (one topic per line)"
    )
    batch_cmd.add_argument("topics_file")
    batch_cmd.add_argument("--template", default=None)
    batch_cmd.add_argument("--style", default="base")
    batch_cmd.add_argument(
        "--auto-template",
        action="store_true",
        help="Auto-select template per topic",
    )
    batch_cmd.add_argument(
        "--export",
        action="store_true",
        help="Export PNGs after generating each post",
    )

    sub.add_parser("templates", help="List available templates")

    news_cmd = sub.add_parser("news", help="Fetch real news articles and export a carousel")
    news_cmd.add_argument("channel", help="Channel name: ai, tech, dev, security, startups")
    news_cmd.add_argument("--count", type=int, default=6, help="Number of articles (default 6)")
    news_cmd.add_argument("--export", action="store_true", help="Export PNGs after fetching")

    sub.add_parser("channels", help="List available news channels")

    args = parser.parse_args()

    if args.command == "templates":
        list_templates()

    elif args.command == "new":
        template_html(args.template)
        load_schema(args.template)
        folder = make_new(args.topic, args.template)
        print(f"Created {folder}")
        print(f"Edit {folder / 'data.json'} then run:")
        print(f"python main.py export {folder / 'data.json'}")

    elif args.command == "generate":
        template = args.template
        if args.auto_template:
            template = select_template(style=args.style, topic=args.topic)
            print(f"Auto-selected template: {template}")
        elif template is None:
            template = DEFAULT_TEMPLATE

        folder = generate_with_ai(
            topic=args.topic,
            template=template,
            audience=args.audience,
            style=args.style,
        )

        print(f"Generated {folder / 'data.json'}")
        print(f"Caption: {folder / 'caption.txt'}")
        print(f"Hashtags: {folder / 'hashtags.txt'}")

        if args.export:
            export_images(folder / "data.json", template)
        else:
            print(f"Run export with: python main.py export {folder / 'data.json'}")

    elif args.command == "export":
        export_images(Path(args.data_json), args.template)

    elif args.command == "gen-template":
        generate_template(args.name, args.brief)
        if args.preview:
            print("Previewing template with example.json...")
            out = preview_template(args.name)
            print(f"Preview saved to {out}")

    elif args.command == "preview-template":
        out = preview_template(args.name)
        print(f"Preview saved to {out}")

    elif args.command == "repair-template":
        repair_template(args.name)

    elif args.command == "validate-template":
        issues = validate_template(args.name)
        if not issues:
            print(f"Template '{args.name}' is valid.")
        else:
            print(f"Template '{args.name}' has issues:")
            for issue in issues:
                print(f"  - {issue}")

    elif args.command == "channels":
        list_channels()

    elif args.command == "news":
        folder = run_news(args.channel, args.count)
        print(f"Saved {folder / 'data.json'}")
        if args.export:
            export_images(folder / "data.json", "news-card")
            print(f"Exported to {folder / 'exports'}")
        else:
            print(f"Run export with: python main.py export {folder / 'data.json'}")

    elif args.command == "batch":
        topics_path = Path(args.topics_file)
        if not topics_path.exists():
            raise FileNotFoundError(f"Topics file not found: {topics_path}")

        topics = [
            line.strip()
            for line in topics_path.read_text(encoding="utf-8").splitlines()
            if line.strip() and not line.strip().startswith("#")
        ]

        if not topics:
            print("No topics found in file.")
            return

        print(f"Processing {len(topics)} topic(s)...")

        for i, topic in enumerate(topics, start=1):
            print(f"\n[{i}/{len(topics)}] {topic}")

            template = args.template
            if args.auto_template:
                template = select_template(style=args.style, topic=topic)
                print(f"  Auto-selected template: {template}")
            elif template is None:
                template = DEFAULT_TEMPLATE

            try:
                folder = generate_with_ai(
                    topic=topic,
                    template=template,
                    style=args.style,
                )
                print(f"  Generated {folder / 'data.json'}")

                if args.export:
                    export_images(folder / "data.json", template)

            except Exception as e:
                print(f"  Failed: {e}")


if __name__ == "__main__":
    main()

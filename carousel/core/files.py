from __future__ import annotations

import json
from pathlib import Path
from typing import Any

from core.config import DEFAULT_ACCENT, DEFAULT_BRAND
from core.utils import clean_text, post_dir


def starter_json(topic: str, template: str) -> dict[str, Any]:
    return {
        "brand": DEFAULT_BRAND,
        "accent": DEFAULT_ACCENT,
        "template": template,
        "style": "base",
        "caption": f"A quick breakdown of {topic}. Save this for later.",
        "hashtags": [
            "#aitools",
            "#artificialintelligence",
            "#productivity",
            "#tech",
            "#aiforbusiness",
        ],
        "slides": [
            {
                "type": "cover",
                "tag": "AI Tools",
                "number": "",
                "slide_num": "01 / 06",
                "category": "AI Tools",
                "title": topic,
                "subtitle": "A practical breakdown for builders, creators, and developers.",
                "cta": "",
                "items": [],
            },
            {
                "type": "content",
                "tag": "",
                "number": "01",
                "slide_num": "02 / 06",
                "category": "Start Here",
                "title": "Use tools that\nsolve real\nproblems.",
                "subtitle": "",
                "cta": "",
                "items": [
                    {"tag": "Workflow", "name": "Pick one workflow", "desc": "Choose a daily task you repeat often."},
                    {"tag": "Testing", "name": "Test one tool", "desc": "Use it for a week before switching."},
                    {"tag": "Measure", "name": "Measure the result", "desc": "Keep what improves quality or saves time."},
                ],
            },
            {
                "type": "content",
                "tag": "",
                "number": "02",
                "slide_num": "03 / 06",
                "category": "Workflow",
                "title": "Make AI part\nof the process.",
                "subtitle": "",
                "cta": "",
                "items": [
                    {"tag": "Research", "name": "Research", "desc": "Gather ideas and examples faster."},
                    {"tag": "Draft", "name": "Draft", "desc": "Create a useful first version quickly."},
                    {"tag": "Refine", "name": "Refine", "desc": "Edit for clarity, tone, and usefulness."},
                ],
            },
            {
                "type": "content",
                "tag": "",
                "number": "03",
                "slide_num": "04 / 06",
                "category": "Tip",
                "title": "Do not chase\nevery new\ntool.",
                "subtitle": "",
                "cta": "",
                "items": [
                    {"tag": "Focus", "name": "Avoid tool hopping", "desc": "Switching too often kills momentum."},
                    {"tag": "Prompts", "name": "Build templates", "desc": "Reusable prompts beat random prompting."},
                    {"tag": "Library", "name": "Save examples", "desc": "Keep outputs that worked well."},
                ],
            },
            {
                "type": "content",
                "tag": "",
                "number": "04",
                "slide_num": "05 / 06",
                "category": "Execution",
                "title": "Turn experiments\ninto systems.",
                "subtitle": "",
                "cta": "",
                "items": [
                    {"tag": "Steps", "name": "Document steps", "desc": "Write the repeatable workflow."},
                    {"tag": "Automate", "name": "Automate pieces", "desc": "Start with the most boring step."},
                    {"tag": "Review", "name": "Review weekly", "desc": "Improve what you use most."},
                ],
            },
            {
                "type": "outro",
                "tag": "",
                "number": "",
                "slide_num": "06 / 06",
                "category": "AI Tools Daily Lab",
                "title": "Start small.\nUse it daily.\nThen expand.",
                "subtitle": "The best AI stack is the one you actually use.",
                "cta": "Save this post and follow for weekly AI workflows.",
                "cta_action": "Follow for daily drops",
                "items": [],
            },
        ],
    }


def write_post_files(folder: Path, data: dict[str, Any]) -> None:
    data = clean_text(data)

    folder.mkdir(parents=True, exist_ok=True)
    (folder / "exports").mkdir(exist_ok=True)

    (folder / "data.json").write_text(
        json.dumps(data, indent=2, ensure_ascii=False),
        encoding="utf-8",
    )

    caption = data.get("caption", "")
    hashtags = " ".join(data.get("hashtags", []))

    (folder / "caption.txt").write_text(caption + "\n", encoding="utf-8")
    (folder / "hashtags.txt").write_text(hashtags + "\n", encoding="utf-8")


def make_new(topic: str, template: str) -> Path:
    folder = post_dir(topic, template)
    write_post_files(folder, starter_json(topic, template))
    return folder


def make_render_data(data_json: Path) -> Path:
    data = json.loads(data_json.read_text(encoding="utf-8"))
    data = clean_text(data)

    render_path = data_json.parent / "_render_data.json"
    render_path.write_text(
        json.dumps(data, indent=2, ensure_ascii=False),
        encoding="utf-8",
    )

    return render_path

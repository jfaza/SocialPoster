from __future__ import annotations

import re
from datetime import date
from pathlib import Path
from typing import Any

from core.config import CONTENT_DIR


def clean_text(value: Any) -> Any:
    """Recursively convert literal backslash-n into real newline chars."""
    if isinstance(value, str):
        return value.replace("\\n", "\n")
    if isinstance(value, list):
        return [clean_text(item) for item in value]
    if isinstance(value, dict):
        return {key: clean_text(val) for key, val in value.items()}
    return value


def slugify(text: str) -> str:
    slug = re.sub(r"[^a-zA-Z0-9]+", "-", text.lower()).strip("-")
    return slug[:60] or "carousel"


def week_name(d: date) -> str:
    # Sunday-start content week. Add 1 so Jan 1 is always week-01.
    return f"week-{int(d.strftime('%U')) + 1:02d}"


def day_dir(d: date | None = None) -> Path:
    d = d or date.today()
    return CONTENT_DIR / f"{d:%Y}" / f"{d:%m}" / week_name(d) / f"{d:%Y-%m-%d}"


def next_post_number(folder: Path) -> int:
    if not folder.exists():
        return 1

    numbers: list[int] = []
    for child in folder.iterdir():
        if child.is_dir():
            match = re.match(r"^(\d{3})-", child.name)
            if match:
                numbers.append(int(match.group(1)))

    return max(numbers) + 1 if numbers else 1


def post_dir(topic: str, template: str, d: date | None = None) -> Path:
    parent = day_dir(d)
    number = next_post_number(parent)
    return parent / f"{number:03d}-{slugify(topic)}-{slugify(template)}"

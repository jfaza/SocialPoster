from __future__ import annotations

import json
from functools import lru_cache
from pathlib import Path
from typing import Any

from core.config import DEFAULT_TEMPLATE, TEMPLATES_DIR


def template_dir(template: str) -> Path:
    folder = TEMPLATES_DIR / template
    if not folder.exists():
        raise FileNotFoundError(f"Template not found: {folder}")
    return folder


def template_html(template: str) -> Path:
    path = template_dir(template) / "slides.html"
    if not path.exists():
        raise FileNotFoundError(f"Template HTML not found: {path}")
    return path


def template_schema_path(template: str) -> Path:
    path = template_dir(template) / "schema.json"
    if not path.exists():
        raise FileNotFoundError(f"Template schema not found: {path}")
    return path


@lru_cache(maxsize=32)
def load_schema(template: str) -> dict[str, Any]:
    return json.loads(template_schema_path(template).read_text(encoding="utf-8"))


def get_templates() -> list[dict[str, Any]]:
    templates: list[dict[str, Any]] = []

    if not TEMPLATES_DIR.exists():
        return templates

    for folder in sorted(TEMPLATES_DIR.iterdir()):
        if not folder.is_dir():
            continue

        has_required = (folder / "slides.html").exists() and (folder / "schema.json").exists()
        if not has_required:
            continue

        meta_file = folder / "meta.json"
        if meta_file.exists():
            try:
                meta = json.loads(meta_file.read_text(encoding="utf-8"))
            except json.JSONDecodeError:
                meta = {}
        else:
            meta = {}

        meta["folder"] = folder.name
        meta.setdefault("name", folder.name)
        meta.setdefault("description", "")
        meta.setdefault("style", [])
        meta.setdefault("best_for", [])
        meta.setdefault("density", "")
        meta.setdefault("tone", "")
        meta.setdefault("tags", [])
        templates.append(meta)

    return templates


def list_templates() -> None:
    templates = get_templates()

    if not templates:
        print("No templates found.")
        return

    for t in templates:
        print(t["folder"])
        if t.get("description"):
            print(f"  - {t['description']}")
        if t.get("style"):
            print(f"  - styles: {', '.join(t['style'])}")
        if t.get("best_for"):
            print(f"  - best for: {', '.join(t['best_for'])}")
        print()


def resolve_template_from_data(data_json: Path, template_arg: str | None) -> str:
    if template_arg:
        return template_arg

    try:
        data = json.loads(data_json.read_text(encoding="utf-8"))
        template = data.get("template")
        if isinstance(template, str) and template.strip():
            return template.strip()
    except Exception:
        pass

    return DEFAULT_TEMPLATE


def validate_template(template: str) -> list[str]:
    """
    Validate a template package. Returns a list of issue strings.
    Empty list means the template is valid.
    """
    issues: list[str] = []
    folder = TEMPLATES_DIR / template

    if not folder.exists():
        return [f"Template folder not found: {folder}"]

    for fname in ("slides.html", "schema.json", "example.json"):
        if not (folder / fname).exists():
            issues.append(f"Missing required file: {fname}")

    if issues:
        return issues

    try:
        schema = json.loads((folder / "schema.json").read_text(encoding="utf-8"))
        if not isinstance(schema, dict):
            issues.append("schema.json: root must be an object")
        elif schema.get("type") != "object":
            issues.append(f"schema.json: type must be 'object', got '{schema.get('type')}'")
        else:
            props = schema.get("properties", {})
            required = schema.get("required", [])
            if not isinstance(props, dict):
                issues.append("schema.json: 'properties' must be an object")
            if not isinstance(required, list):
                issues.append("schema.json: 'required' must be an array")
            else:
                missing = [k for k in props if k not in required]
                if missing:
                    issues.append(f"schema.json: properties not in 'required': {missing}")
    except json.JSONDecodeError as e:
        issues.append(f"schema.json: invalid JSON — {e}")

    try:
        example = json.loads((folder / "example.json").read_text(encoding="utf-8"))
        if not isinstance(example, dict):
            issues.append("example.json: root must be an object")
        else:
            slides = example.get("slides")
            if not isinstance(slides, list):
                issues.append("example.json: missing 'slides' array")
            elif len(slides) != 6:
                issues.append(f"example.json: expected 6 slides, got {len(slides)}")
    except json.JSONDecodeError as e:
        issues.append(f"example.json: invalid JSON — {e}")

    if not (folder / "meta.json").exists():
        issues.append("meta.json: missing (optional but recommended)")

    return issues


def select_template(style: str = "base", topic: str = "") -> str:
    """
    Score available templates against style and topic keywords.
    Returns the folder name of the best match, or DEFAULT_TEMPLATE.
    """
    templates = get_templates()
    if not templates:
        return DEFAULT_TEMPLATE

    topic_lower = topic.lower()
    scored: list[tuple[int, str]] = []

    for t in templates:
        score = 0
        if style in (t.get("style") or []):
            score += 2
        for kw in (t.get("best_for") or []):
            if kw.lower() in topic_lower:
                score += 1
        for tag in (t.get("tags") or []):
            if tag.lower() in topic_lower:
                score += 1
        scored.append((score, t["folder"]))

    scored.sort(key=lambda x: -x[0])
    return scored[0][1] if scored else DEFAULT_TEMPLATE

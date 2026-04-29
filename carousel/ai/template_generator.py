from __future__ import annotations

import json
import os
from pathlib import Path
from typing import Any

from dotenv import load_dotenv
from openai import OpenAI

from ai.prompts import load_prompt
from core.config import DEFAULT_ACCENT, DEFAULT_BRAND, ROOT, TEMPLATES_DIR
from core.utils import clean_text


DEFAULT_HASHTAGS = [
    "#aitools",
    "#artificialintelligence",
    "#developers",
    "#productivity",
    "#tech",
]


def normalize_example(
    example: dict[str, Any],
    template_name: str,
    meta: dict[str, Any] | None = None,
) -> dict[str, Any]:
    """
    Make sure example.json has enough top-level fields for our system
    and consistent slide fields for schema generation.
    """
    meta = meta or {}
    example = clean_text(example)

    example.setdefault("brand", DEFAULT_BRAND)
    example.setdefault("series", "Developer Edition")
    example.setdefault("accent", DEFAULT_ACCENT)
    example["template"] = template_name
    example.setdefault("style", "base")
    example.setdefault("caption", f"A carousel generated with the {template_name} template.")
    example.setdefault("hashtags", DEFAULT_HASHTAGS)

    slides = example.get("slides")
    if not isinstance(slides, list) or not slides:
        raise ValueError("example.json must include a non-empty slides array.")

    total = len(slides)

    item_keys: set[str] = set()
    for slide in slides:
        if isinstance(slide, dict):
            for item in slide.get("items", []) or []:
                if isinstance(item, dict):
                    item_keys.update(item.keys())

    for idx, slide in enumerate(slides, start=1):
        if not isinstance(slide, dict):
            raise ValueError("Each slide must be an object.")

        slide.setdefault("id", f"slide-{idx}")

        if "type" not in slide:
            if idx == 1:
                slide["type"] = "cover"
            elif idx == total:
                slide["type"] = "outro"
            else:
                slide["type"] = "content"

        slide.setdefault("slide_num", idx)
        slide.setdefault("category", "")
        slide.setdefault("title", "")
        slide.setdefault("subtitle", "")
        slide.setdefault("cta", "")
        slide.setdefault("cta_action", "")

        if "items" not in slide or not isinstance(slide["items"], list):
            slide["items"] = []

        normalized_items = []
        for item in slide["items"]:
            if not isinstance(item, dict):
                continue

            for key in item_keys:
                item.setdefault(key, "")

            normalized_items.append(item)

        slide["items"] = normalized_items

    return example


def schema_from_values(values: list[Any], path: tuple[str, ...] = ()) -> dict[str, Any]:
    """
    Infer a strict OpenAI-compatible JSON Schema from example values.
    Object fields always become required because OpenAI strict schemas require it.
    """
    values = [v for v in values if v is not None]

    if not values:
        return {"type": "string"}

    if all(isinstance(v, dict) for v in values):
        return schema_from_objects(values, path)

    if all(isinstance(v, list) for v in values):
        all_items: list[Any] = []
        for arr in values:
            all_items.extend(arr)

        if all_items:
            item_schema = schema_from_values(all_items, path + ("[]",))
        else:
            item_schema = {
                "type": "object",
                "additionalProperties": False,
                "required": [],
                "properties": {},
            }

        schema: dict[str, Any] = {
            "type": "array",
            "items": item_schema,
        }

        field_name = path[-1] if path else ""

        if field_name == "slides":
            schema["minItems"] = 6
            schema["maxItems"] = 6
        elif field_name == "items":
            max_found = max((len(arr) for arr in values if isinstance(arr, list)), default=0)
            schema["minItems"] = 0
            schema["maxItems"] = max(max_found, 3)
        elif field_name == "hashtags":
            schema["minItems"] = 5
            schema["maxItems"] = 15

        return schema

    first = values[0]
    field_name = path[-1] if path else ""

    if field_name == "type" and "slides" in path:
        return {
            "type": "string",
            "enum": ["cover", "content", "list", "outro"],
        }

    if isinstance(first, bool):
        return {"type": "boolean"}

    if isinstance(first, int) and not isinstance(first, bool):
        return {"type": "integer"}

    if isinstance(first, float):
        return {"type": "number"}

    return {"type": "string"}


def schema_from_objects(
    objects: list[dict[str, Any]],
    path: tuple[str, ...] = (),
) -> dict[str, Any]:
    keys: list[str] = []

    for obj in objects:
        for key in obj.keys():
            if key not in keys:
                keys.append(key)

    properties: dict[str, Any] = {}

    for key in keys:
        values = [obj.get(key) for obj in objects]
        properties[key] = schema_from_values(values, path + (key,))

    return {
        "type": "object",
        "additionalProperties": False,
        "required": keys,
        "properties": properties,
    }


def schema_from_example(example: dict[str, Any]) -> dict[str, Any]:
    return schema_from_objects([example])


def normalize_meta(raw_meta: Any, template_name: str) -> dict[str, Any]:
    if not isinstance(raw_meta, dict):
        raw_meta = {}

    raw_meta.setdefault("name", template_name)
    raw_meta.setdefault("description", f"{template_name} carousel template")
    raw_meta.setdefault("style", ["base"])
    raw_meta.setdefault("best_for", ["tools", "breakdowns", "developer content"])
    raw_meta.setdefault("density", "medium")
    raw_meta.setdefault("tone", "practical")
    raw_meta.setdefault("tags", [template_name])

    return raw_meta


def _load_via_openai(prompt: str, name: str) -> str:
    client = OpenAI()
    response = client.chat.completions.create(
        model=os.getenv("TEMPLATE_MODEL", os.getenv("OPENAI_MODEL", "gpt-4o")),
        messages=[{"role": "user", "content": prompt + f"\n\nTemplate folder/name: {name}"}],
        response_format={"type": "json_object"},
    )
    content = response.choices[0].message.content
    if not content:
        raise RuntimeError("OpenAI returned empty content.")
    return content


def _strip_json(text: str) -> str:
    """Extract raw JSON from a response that may be wrapped in markdown fences."""
    text = text.strip()
    if text.startswith("```"):
        start = text.find("{")
        end = text.rfind("}")
        if start != -1 and end != -1:
            return text[start : end + 1]
    return text


def _load_via_anthropic(prompt: str, name: str) -> str:
    import anthropic  # lazy import — only required when using this provider

    client = anthropic.Anthropic(api_key=os.getenv("ANTHROPIC_API_KEY"))
    model = os.getenv("TEMPLATE_MODEL", "claude-opus-4-7")

    response = client.messages.create(
        model=model,
        max_tokens=16000,
        messages=[
            {"role": "user", "content": prompt + f"\n\nTemplate folder/name: {name}"},
        ],
    )
    return _strip_json(response.content[0].text)


def load_template_package_from_model(name: str, brief: str | None = None) -> dict[str, Any]:
    load_dotenv(ROOT / ".env")

    prompt = load_prompt("template/template_generator")

    if brief:
        brief_path = ROOT / "design_briefs" / f"{brief}.txt"
        if not brief_path.exists():
            raise FileNotFoundError(f"Design brief not found: {brief_path}")
        design_brief = brief_path.read_text(encoding="utf-8")
        prompt += f"\n\nDESIGN BRIEF:\n{design_brief}"

    provider = os.getenv("TEMPLATE_PROVIDER", "").lower()
    if not provider:
        provider = "anthropic" if os.getenv("ANTHROPIC_API_KEY") else "openai"

    print(f"Generating template with {provider} ({os.getenv('TEMPLATE_MODEL', 'default model')})...")

    if provider == "anthropic":
        content = _load_via_anthropic(prompt, name)
    else:
        content = _load_via_openai(prompt, name)

    return json.loads(content)


def write_template_package(
    name: str,
    html: str,
    example: dict[str, Any],
    meta: dict[str, Any],
) -> Path:
    template_path = TEMPLATES_DIR / name
    template_path.mkdir(parents=True, exist_ok=True)

    example = normalize_example(example, name, meta)
    schema = schema_from_example(example)

    (template_path / "slides.html").write_text(html, encoding="utf-8")

    (template_path / "example.json").write_text(
        json.dumps(example, indent=2, ensure_ascii=False),
        encoding="utf-8",
    )

    (template_path / "schema.json").write_text(
        json.dumps(schema, indent=2, ensure_ascii=False),
        encoding="utf-8",
    )

    (template_path / "meta.json").write_text(
        json.dumps(meta, indent=2, ensure_ascii=False),
        encoding="utf-8",
    )

    return template_path


def generate_template(name: str, brief: str | None = None) -> Path:
    """
    Generate a new template package with GPT.
    The schema is rebuilt from example.json to avoid broken model-generated schemas.
    """
    data = load_template_package_from_model(name, brief)

    html = data.get("html")
    example = data.get("example")
    meta = normalize_meta(data.get("meta"), name)

    if not isinstance(html, str) or not html.strip():
        raise RuntimeError("Template output missing valid 'html' string.")

    if not isinstance(example, dict):
        raise RuntimeError("Template output missing valid 'example' object.")

    template_path = write_template_package(name, html, example, meta)
    print(f"Template created: {template_path}")
    return template_path


def repair_template(name: str) -> Path:
    """
    Repair an existing template by rebuilding schema.json from example.json.
    Does NOT overwrite slides.html.
    """
    template_path = TEMPLATES_DIR / name
    if not template_path.exists():
        raise FileNotFoundError(f"Template not found: {template_path}")

    example_path = template_path / "example.json"
    if not example_path.exists():
        raise FileNotFoundError(f"Missing example.json: {example_path}")

    meta_path = template_path / "meta.json"
    if meta_path.exists():
        meta = normalize_meta(json.loads(meta_path.read_text(encoding="utf-8")), name)
    else:
        meta = normalize_meta({}, name)

    example = json.loads(example_path.read_text(encoding="utf-8"))
    example = normalize_example(example, name, meta)
    schema = schema_from_example(example)

    example_path.write_text(
        json.dumps(example, indent=2, ensure_ascii=False),
        encoding="utf-8",
    )

    (template_path / "schema.json").write_text(
        json.dumps(schema, indent=2, ensure_ascii=False),
        encoding="utf-8",
    )

    meta_path.write_text(
        json.dumps(meta, indent=2, ensure_ascii=False),
        encoding="utf-8",
    )

    print(f"Template repaired: {template_path}")
    return template_path
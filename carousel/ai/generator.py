from __future__ import annotations

import json
import os
from pathlib import Path

from dotenv import load_dotenv
from openai import OpenAI

from ai.prompts import load_prompt
from core.config import DEFAULT_ACCENT, DEFAULT_BRAND, ROOT
from core.files import write_post_files
from core.templates import load_schema, template_html
from core.utils import clean_text, post_dir


def generate_with_ai(
    topic: str,
    template: str,
    audience: str = "developers, creators, and AI-curious professionals",
    style: str = "base",
) -> Path:
    load_dotenv(ROOT / ".env")

    template_html(template)
    schema = load_schema(template)

    model = os.getenv("OPENAI_MODEL", "gpt-5.4-mini")
    client = OpenAI()

    system = load_prompt(style)

    user = (
        f"Topic: {topic}\n"
        f"Audience: {audience}\n"
        f"Template name: {template}\n"
        f"Style: {style}\n"
        f"Brand: {DEFAULT_BRAND}\n"
        f"Accent color: {DEFAULT_ACCENT}\n"
        f"Create a {style} Instagram carousel."
    )

    response = client.chat.completions.create(
        model=model,
        messages=[
            {"role": "system", "content": system},
            {"role": "user", "content": user},
        ],
        response_format={
            "type": "json_schema",
            "json_schema": {
                "name": "instagram_carousel",
                "strict": True,
                "schema": schema,
            },
        },
    )

    content = response.choices[0].message.content
    if not content:
        raise RuntimeError("The model returned empty content.")

    data = clean_text(json.loads(content))
    data["template"] = template
    data["style"] = style

    folder = post_dir(topic, template)
    write_post_files(folder, data)

    return folder

from __future__ import annotations

from pathlib import Path

from core.config import PROMPTS_DIR


def resolve_prompt_path(name: str) -> Path:
    """
    Resolve prompt names into files.

    Supports:
    - base                      -> prompts/content/base.txt
    - viral                     -> prompts/content/viral.txt
    - technical                 -> prompts/content/technical.txt
    - content/base              -> prompts/content/base.txt
    - template/template_generator -> prompts/template/template_generator.txt
    - parts/title_rules         -> prompts/parts/title_rules.txt
    - something.txt             -> prompts/something.txt
    """
    clean_name = name.strip().replace("\\", "/")

    if clean_name.endswith(".txt"):
        candidate = PROMPTS_DIR / clean_name
    elif "/" in clean_name:
        candidate = PROMPTS_DIR / f"{clean_name}.txt"
    else:
        candidate = PROMPTS_DIR / "content" / f"{clean_name}.txt"

    if candidate.exists():
        return candidate

    fallback = PROMPTS_DIR / "content" / "base.txt"
    if fallback.exists():
        print(f"⚠️ Prompt '{name}' not found. Falling back to content/base.txt.")
        return fallback

    raise FileNotFoundError(f"Prompt not found: {candidate}")


def load_prompt(name: str = "base") -> str:
    path = resolve_prompt_path(name)
    return load_prompt_file(path)


def load_prompt_file(path: Path, seen: set[Path] | None = None) -> str:
    """
    Loads a prompt file and expands @include statements.

    Example inside a prompt file:
    @include ../parts/role.txt
    @include ../parts/title_rules.txt
    """
    path = path.resolve()
    seen = seen or set()

    if path in seen:
        raise RuntimeError(f"Circular prompt include detected: {path}")

    seen.add(path)

    lines: list[str] = []

    for line in path.read_text(encoding="utf-8").splitlines():
        stripped = line.strip()

        if stripped.startswith("@include "):
            include_target = stripped.replace("@include ", "", 1).strip()
            include_path = (path.parent / include_target).resolve()

            if not include_path.exists():
                raise FileNotFoundError(f"Included prompt file not found: {include_path}")

            lines.append(load_prompt_file(include_path, seen))
        else:
            lines.append(line)

    return "\n".join(lines).strip()
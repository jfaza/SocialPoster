from pathlib import Path

# Always the carousel/ directory, regardless of invocation location
ROOT = Path(__file__).resolve().parents[1]

CONTENT_DIR = ROOT / "content"
TEMPLATES_DIR = ROOT / "templates"
PROMPTS_DIR = ROOT / "prompts"

DEFAULT_TEMPLATE = "minimal-list"
DEFAULT_BRAND = "fazaeli.dev"
DEFAULT_ACCENT = "#C65A2E"

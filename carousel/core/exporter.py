from __future__ import annotations

import json
import shutil
from pathlib import Path

from playwright.sync_api import sync_playwright

from core.config import ROOT, TEMPLATES_DIR
from core.files import make_render_data
from core.server import start_server
from core.templates import resolve_template_from_data, template_html


def preview_template(template: str) -> Path:
    """
    Export the template's example.json through its slides.html.
    Saves PNGs to templates/{name}/preview/ so you can inspect the design
    immediately after generation without running a full generate.
    """
    template_dir = TEMPLATES_DIR / template
    example_json = template_dir / "example.json"

    if not example_json.exists():
        raise FileNotFoundError(f"No example.json found in {template_dir}")

    # Write a render copy next to example.json
    data = json.loads(example_json.read_text(encoding="utf-8"))
    render_path = template_dir / "_preview_data.json"
    render_path.write_text(json.dumps(data, indent=2, ensure_ascii=False), encoding="utf-8")

    html_path = template_dir / "slides.html"
    if not html_path.exists():
        raise FileNotFoundError(f"No slides.html found in {template_dir}")

    output_dir = template_dir / "preview"
    if output_dir.exists():
        shutil.rmtree(output_dir)
    output_dir.mkdir()

    relative_data = "/" + render_path.relative_to(ROOT).as_posix()
    relative_html = "/" + html_path.relative_to(ROOT).as_posix()

    httpd, port = start_server(ROOT)
    url = f"http://127.0.0.1:{port}{relative_html}?data={relative_data}"

    try:
        with sync_playwright() as p:
            browser = p.chromium.launch()
            page = browser.new_page(
                viewport={"width": 1200, "height": 1200},
                device_scale_factor=1,
            )
            page.goto(url, wait_until="networkidle")
            page.wait_for_selector(".slide", timeout=10000)
            page.wait_for_timeout(1000)

            slides = page.locator(".slide")
            count = slides.count()

            if count == 0:
                raise RuntimeError("No .slide elements found — check the template HTML.")

            for i in range(count):
                slide = slides.nth(i)
                slide.scroll_into_view_if_needed()
                out = output_dir / f"slide_{i + 1:02d}.png"
                slide.screenshot(path=str(out))
                print(f"  Preview {out.name}")

            browser.close()
    finally:
        httpd.shutdown()
        render_path.unlink(missing_ok=True)

    return output_dir


def export_images(data_json: Path, template: str | None = None) -> Path:
    data_json = data_json.resolve()

    if not data_json.exists():
        raise FileNotFoundError(f"Could not find {data_json}")

    selected_template = resolve_template_from_data(data_json, template)
    html_path = template_html(selected_template)

    render_data = make_render_data(data_json)

    output_dir = data_json.parent / "exports"
    output_dir.mkdir(parents=True, exist_ok=True)

    relative_data = "/" + render_data.relative_to(ROOT).as_posix()
    relative_html = "/" + html_path.relative_to(ROOT).as_posix()

    httpd, port = start_server(ROOT)
    url = f"http://127.0.0.1:{port}{relative_html}?data={relative_data}"

    try:
        with sync_playwright() as p:
            browser = p.chromium.launch()

            page = browser.new_page(
                viewport={"width": 1200, "height": 1200},
                device_scale_factor=1,
            )

            page.goto(url, wait_until="networkidle")
            page.wait_for_selector(".slide", timeout=10000)
            page.wait_for_timeout(1000)

            slides = page.locator(".slide")
            count = slides.count()

            if count == 0:
                raise RuntimeError("No .slide elements found in template.")

            for i in range(count):
                slide = slides.nth(i)
                slide.scroll_into_view_if_needed()

                output_file = output_dir / f"slide_{i + 1:02d}.png"
                slide.screenshot(path=str(output_file))

                print(f"Saved {output_file}")

            browser.close()

    finally:
        httpd.shutdown()

    return output_dir

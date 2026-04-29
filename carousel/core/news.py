from __future__ import annotations

import hashlib
import json
import re
from datetime import date, datetime
from pathlib import Path
from typing import Any

import feedparser
import requests

from core.config import ROOT
from core.utils import post_dir

CHANNELS_FILE = ROOT / "channels.json"
IMAGE_CACHE = ROOT / "content" / "cache" / "news_images"


def load_channels() -> dict[str, Any]:
    if not CHANNELS_FILE.exists():
        raise FileNotFoundError(f"channels.json not found at {CHANNELS_FILE}")
    return json.loads(CHANNELS_FILE.read_text(encoding="utf-8"))


def list_channels() -> None:
    channels = load_channels()
    print("Available news channels:")
    for key, info in channels.items():
        print(f"  {key:<12} {info['description']}")


_HEADERS = {"User-Agent": "Mozilla/5.0 (compatible; NewsBot/1.0)"}


def _get_entry_image(entry: Any) -> str | None:
    """Try all common RSS media fields to find an image URL."""
    for media in getattr(entry, "media_content", []) or []:
        url = media.get("url", "")
        if url and re.search(r"\.(jpe?g|png|webp)", url, re.I):
            return url

    for thumb in getattr(entry, "media_thumbnail", []) or []:
        url = thumb.get("url", "")
        if url:
            return url

    for enc in getattr(entry, "enclosures", []) or []:
        if enc.get("type", "").startswith("image/"):
            return enc.get("href", "")

    for link in getattr(entry, "links", []) or []:
        if link.get("type", "").startswith("image/"):
            return link.get("href", "")

    return None


def _scrape_og(url: str) -> tuple[str | None, str | None]:
    """Fetch article page and extract og:image and og:description. Returns (image_url, description)."""
    try:
        resp = requests.get(url, timeout=10, headers=_HEADERS)
        resp.raise_for_status()
        html = resp.text

        def _og(prop: str) -> str | None:
            m = re.search(
                rf'<meta[^>]+property=["\']og:{prop}["\'][^>]+content=["\']([^"\']+)["\']',
                html, re.I,
            )
            if not m:
                m = re.search(
                    rf'<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:{prop}["\']',
                    html, re.I,
                )
            return m.group(1).strip() if m else None

        return _og("image"), _og("description")
    except Exception:
        return None, None


def _strip_html(text: str) -> str:
    text = re.sub(r"<[^>]+>", " ", text)
    text = re.sub(r"\s+", " ", text).strip()
    return text


def _cache_image(url: str) -> str | None:
    """Download image to local cache, return server-relative path or None."""
    IMAGE_CACHE.mkdir(parents=True, exist_ok=True)

    ext = re.search(r"\.(jpe?g|png|webp|gif)", url.split("?")[0], re.I)
    suffix = ext.group(0).lower() if ext else ".jpg"
    filename = hashlib.md5(url.encode()).hexdigest() + suffix
    local = IMAGE_CACHE / filename

    if local.exists():
        return "/" + local.relative_to(ROOT).as_posix()

    try:
        resp = requests.get(url, timeout=12, headers=_HEADERS)
        resp.raise_for_status()
        local.write_bytes(resp.content)
        return "/" + local.relative_to(ROOT).as_posix()
    except Exception:
        return None


def fetch_articles(channel: str, count: int = 6) -> list[dict[str, Any]]:
    channels = load_channels()
    if channel not in channels:
        available = ", ".join(channels.keys())
        raise ValueError(f"Unknown channel '{channel}'. Available: {available}")

    info = channels[channel]
    articles: list[dict[str, Any]] = []
    seen: set[str] = set()

    for feed_url in info["feeds"]:
        if len(articles) >= count * 2:
            break
        try:
            feed = feedparser.parse(feed_url)
            for entry in feed.entries:
                title = (entry.get("title") or "").strip()
                if not title or title in seen:
                    continue
                seen.add(title)

                summary = _strip_html(
                    entry.get("summary") or entry.get("description") or ""
                )[:240].strip()

                source = (feed.feed.get("title") or feed_url).strip()

                parsed = entry.get("published_parsed") or entry.get("updated_parsed")
                try:
                    date_str = datetime(*parsed[:3]).strftime("%b %d, %Y") if parsed else ""
                except Exception:
                    date_str = ""

                articles.append({
                    "title": title,
                    "summary": summary,
                    "source": source,
                    "date": date_str,
                    "channel_name": info["name"],
                    "image_url": _get_entry_image(entry),
                    "link": entry.get("link", ""),
                    "needs_scrape": not _get_entry_image(entry) or len(summary) < 80,
                })

                if len(articles) >= count * 2:
                    break

        except Exception as e:
            print(f"  Warning: could not fetch {feed_url}: {e}")

    # Enrich via og: scraping when RSS has no image or short summary
    result: list[dict[str, Any]] = []
    for article in articles[:count]:
        image_url = article["image_url"]
        summary = article["summary"]

        if article.get("needs_scrape") and article["link"]:
            print(f"  Scraping og: {article['title'][:55]}...")
            og_image, og_desc = _scrape_og(article["link"])
            if not image_url and og_image:
                image_url = og_image
            if og_desc and len(og_desc) > len(summary):
                summary = og_desc[:400].strip()

        image_path = ""
        if image_url:
            print(f"  Downloading image: {article['title'][:55]}...")
            image_path = _cache_image(image_url) or ""

        result.append({
            "title": article["title"],
            "summary": summary,
            "source": article["source"],
            "date": article["date"],
            "channel_name": article["channel_name"],
            "image": image_path,
            "link": article["link"],
        })

    return result


def build_news_slides(channel: str, count: int = 6) -> dict[str, Any]:
    articles = fetch_articles(channel, count)
    channels = load_channels()
    channel_info = channels.get(channel, {})

    slides = [
        {
            "type": "news",
            "slide_num": str(i),
            "category": channel_info.get("name", channel.upper()),
            "title": a["title"],
            "subtitle": a["summary"],
            "source": a["source"],
            "date": a["date"],
            "image": a["image"],
            "link": a["link"],
            "items": [],
            "cta": "",
        }
        for i, a in enumerate(articles, start=1)
    ]

    return {
        "brand": "ai.tools.daily.lab",
        "accent": "#2563EB",
        "channel": channel,
        "template": "news-card",
        "slides": slides,
    }


def run_news(channel: str, count: int = 6) -> Path:
    """Fetch articles, write data.json, return folder path."""
    print(f"Fetching {count} articles from '{channel}' channel...")
    data = build_news_slides(channel, count)

    folder = post_dir(f"news-{channel}", "news-card")
    folder.mkdir(parents=True, exist_ok=True)

    (folder / "data.json").write_text(
        json.dumps(data, indent=2, ensure_ascii=False),
        encoding="utf-8",
    )

    return folder

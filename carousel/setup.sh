#!/usr/bin/env bash
# Sets up the Python venv for the carousel generator.
# Run once after deploying: bash setup.sh
set -e
DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$DIR"

echo "Creating Python venv..."
python3 -m venv venv

echo "Installing Python dependencies..."
./venv/bin/pip install --quiet --upgrade pip
./venv/bin/pip install --quiet -r requirements.txt

echo "Installing Playwright browser..."
./venv/bin/playwright install chromium --with-deps

mkdir -p content

echo "Carousel setup complete. Python: $(./venv/bin/python --version)"

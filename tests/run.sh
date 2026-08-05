#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

php tests/run.php
bash tests/test-security-static.sh
bash tests/test-browser-smoke.sh

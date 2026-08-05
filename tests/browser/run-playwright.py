from __future__ import annotations

import json
import sys
from pathlib import Path

from playwright.sync_api import TimeoutError as PlaywrightTimeoutError
from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parents[2]
DOM = ROOT / "tests/browser/browser-smoke-dom.html"
SCREENSHOT = ROOT / "tests/browser/browser-smoke.png"
SUCCESS_SCREENSHOT = ROOT / "tests/browser/browser-smoke-success.png"
CONSOLE = ROOT / "tests/browser/browser-console.json"
CONFIG_JS = ROOT / "tests/browser/harness-config.js"
FRONTEND_JS = ROOT / "assets/js/foundation-frontend.js"
RUNNER_JS = ROOT / "tests/browser/browser-smoke-runner.js"
FRONTEND_CSS = ROOT / "assets/css/foundation-frontend.css"

messages: list[dict[str, str]] = []
page_errors: list[str] = []
navigations: list[str] = []
result = "FAIL"
detail = "Browser smoke did not complete."

with sync_playwright() as playwright:
    browser = playwright.chromium.launch(
        executable_path="/usr/bin/chromium",
        headless=False,
        args=["--no-sandbox", "--disable-dev-shm-usage", "--disable-gpu", "--no-first-run"],
    )
    context = browser.new_context(viewport={"width": 1440, "height": 1100})
    page = context.new_page()
    page.on("console", lambda msg: messages.append({"type": msg.type, "text": msg.text}))
    page.on("pageerror", lambda error: page_errors.append(str(error)))
    page.on("framenavigated", lambda frame: navigations.append(frame.url) if frame == page.main_frame else None)

    try:
        page.set_content(
            "<!doctype html><html lang=\"en\"><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"><title>Foundation Project Calculator browser smoke</title></head><body><button type=\"button\" data-foundation-calculator-open>Open calculator</button><div id=\"foundation-app-overlay\" hidden></div><pre id=\"smoke-result\" aria-live=\"polite\">RUNNING</pre></body></html>",
            wait_until="domcontentloaded",
            timeout=5_000,
        )
        page.add_style_tag(path=str(FRONTEND_CSS))
        page.add_script_tag(path=str(CONFIG_JS))
        # Install the mocked transport and smoke runner before the calculator starts.
        page.add_script_tag(path=str(RUNNER_JS))
        page.add_script_tag(path=str(FRONTEND_JS))
        page.wait_for_function("document.body.dataset.smokeStage === 'success' || document.body.dataset.smoke === 'fail'", timeout=15_000)
        if (page.get_attribute("body", "data-smoke-stage") or "") == "success":
            page.screenshot(path=str(SUCCESS_SCREENSHOT), full_page=True, timeout=5_000)
            page.evaluate("document.body.dataset.smokeCapture = 'done'")
        page.wait_for_function("document.body.dataset.smoke === 'pass' || document.body.dataset.smoke === 'fail'", timeout=15_000)
        smoke_state = page.get_attribute("body", "data-smoke") or ""
        smoke_text = page.locator("#smoke-result").inner_text(timeout=2_000)
        if smoke_state == "pass":
            result = "PASS"
            detail = smoke_text
        else:
            detail = smoke_text or "The browser runner reported failure."
    except PlaywrightTimeoutError as error:
        try:
            detail = page.locator("#smoke-result").inner_text(timeout=1_000)
        except Exception:
            detail = f"Timed out: {error}"
    finally:
        try:
            page.wait_for_load_state("domcontentloaded", timeout=2_000)
        except Exception:
            pass
        try:
            DOM.write_text(page.content(), encoding="utf-8")
        except Exception as error:
            DOM.write_text("<!-- Could not capture DOM: %s -->" % error, encoding="utf-8")
        try:
            page.screenshot(path=str(SCREENSHOT), full_page=True, timeout=5_000)
        except Exception as error:
            messages.append({"type": "capture-error", "text": str(error)})
        browser.close()

CONSOLE.write_text(
    json.dumps({"console": messages, "page_errors": page_errors, "navigations": navigations}, indent=2, ensure_ascii=False),
    encoding="utf-8",
)

print(f"{result}\t{detail}")
if page_errors:
    print("Page errors:", *page_errors, sep="\n", file=sys.stderr)
sys.exit(0 if result == "PASS" and not page_errors else 1)

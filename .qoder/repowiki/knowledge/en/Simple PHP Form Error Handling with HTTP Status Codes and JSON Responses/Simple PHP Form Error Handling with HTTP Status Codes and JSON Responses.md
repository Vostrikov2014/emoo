---
kind: error_handling
name: Simple PHP Form Error Handling with HTTP Status Codes and JSON Responses
category: error_handling
scope:
    - '**'
source_files:
    - send-brief.php
    - index.html
---

## What system/approach is used

This repository is a static single-page marketing site with one server-side endpoint: `send-brief.php`, a plain PHP script that processes the "Brief" form submission via PHP's built-in `mail()` function. There is no error-handling framework, no custom exception classes, no middleware layer, and no centralized error logger beyond PHP's native `error_log()`. Errors are handled inline within the single script using early returns (`exit`) after setting an appropriate HTTP response code and echoing a JSON payload.

## Key files and packages

- `send-brief.php` — the only backend file; contains all request validation, sanitization, mail sending, and error responses.
- `index.html` — the frontend form (lines ~782–818) posts to `send-brief.php` via `fetch`/AJAX (the JS at the bottom of the file handles the submit button, loading state, and success/error UI).
- `.htaccess` — Apache configuration (not examined in detail here); could influence 404/500 behavior but no custom error pages are referenced.

## Architecture and conventions

The error handling follows a flat, procedural pattern inside `send-brief.php`:

1. **Method guard** — If the request is not `POST`, the script sets `http_response_code(405)` and returns `{success:false, message:"Метод не разрешён"}` as JSON, then `exit`s immediately.
2. **Honeypot anti-bot** — If the hidden `website_url` field is filled, the script silently returns `{success:true, message:"Бриф успешно отправлен"}` with status 200, effectively dropping bot submissions without logging or alerting.
3. **Input sanitization** — All `$_POST` fields are passed through `trim(strip_tags(...))` before use.
4. **Validation errors** — A local `$errors = []` array accumulates human-readable Russian messages (e.g. `'Некорректное имя'`, `'Требуется телефон или email'`, `'Некорректный телефон или email'`, `'Слишком длинное название компании'`, `'Некорректная площадь'`, `'Слишком длинное сообщение'`). When non-empty, the script sets `http_response_code(400)` and returns `{success:false, errors:[...list...]}`.
5. **Mail send failure** — After calling `mail()`, if it returns false, the script sets `http_response_code(500)` and returns `{success:false, message:"Ошибка при отправки письма"}`.
6. **Success path** — On successful mail delivery, the script logs a structured line via `error_log(sprintf("[%s] BRIEF: name=%s, contact=%s, ip=%s\n", ...))` and returns `{success:true, message:"Бриф успешно отправлен"}`.

There is **no try/catch**, **no custom exceptions**, **no global error handler**, and **no panic/recover equivalent**. The entire script is a single linear flow guarded by early-exit conditions.

## Conventions and constraints observed

- Every error branch explicitly sets an HTTP status code via `http_response_code()` before returning JSON — 405 for wrong method, 400 for validation failures, 500 for mail delivery failure, 200 for honeypot drops and success.
- All client-facing responses are JSON with a `success` boolean plus either a `message` string or an `errors` array of localized (Russian) strings.
- Validation rules are enforced server-side in PHP even though the HTML form also uses `required` attributes and a honeypot field — both layers coexist.
- User-facing error messages are hardcoded Russian strings; there is no i18n mechanism for error messages despite the page being bilingual.
- Internal diagnostics go only to PHP's default error log via `error_log()`; there is no structured logging library, no log rotation config in this repo, and no on-screen debug output.
- The frontend (`index.html`) handles user feedback for success (showing `#formOk` with an animated checkmark) and presumably for errors (the submit button toggles between a label and a loading spinner), but the specific JS error-display logic was truncated in the read and not fully analyzed here.
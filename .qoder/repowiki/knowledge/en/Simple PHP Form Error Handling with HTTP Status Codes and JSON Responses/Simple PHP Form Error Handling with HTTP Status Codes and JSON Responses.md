---
kind: error_handling
name: Simple PHP Form Error Handling with HTTP Status Codes and JSON Responses
category: error_handling
scope:
    - '**'
source_files:
    - send-brief.php
---

## Overview

This repository is a static bilingual landing page for EMOO exhibition company, backed by a single PHP endpoint (`send-brief.php`) that handles form submissions. Error handling is minimal and localized entirely within this one script — there are no shared error libraries, middleware, or framework abstractions.

## Approach

The script uses **inline validation with an errors array** and returns structured JSON responses paired with appropriate HTTP status codes:

- **405 Method Not Allowed**: Returned when the request is not `POST` (line 10–15). The response body is `{"success": false, "message": "Метод не разрешён"}`.
- **Silent rejection of honeypot bots**: If the hidden `website_url` field is filled, the script responds with `200 OK` and a fake success message to avoid alerting automated bots (lines 21–27).
- **400 Bad Request**: When client-side validation fails on the server side, all accumulated validation messages are returned in an `errors` array inside the JSON payload (lines 36–71).
- **500 Internal Server Error**: Returned when `mail()` fails to send the email (lines 111–115), with a generic failure message.
- **200 OK**: Successful submission, including the honeypot case which intentionally masquerades as success.

All responses use `application/json; charset=UTF-8` content type via `http_response_code()` and `header()`, then `exit` immediately after echoing the JSON — there is no try/catch or global error handler.

## Validation Strategy

Validation is performed inline using PHP built-ins:
- `trim(strip_tags(...))` sanitizes input before validation.
- `mb_strlen` enforces length constraints (name: 2–100 chars, company: ≤200, message: ≤2000).
- `filter_var($contact, FILTER_VALIDATE_EMAIL)` validates email format.
- A regex validates phone number format.
- Whitelist check against `$valid_areas` ensures area selection is valid.

Each failing rule appends a Russian-language error string to the `$errors[]` array, which is then batched into a single 400 response.

## Logging

Successful submissions are logged via `error_log()` with a structured prefix `[YYYY-MM-DD HH:MM:SS] BRIEF:` containing name, contact, and IP address (line 106–107). There is no logging for validation failures or mail delivery errors beyond the HTTP 500 response.

## Conventions Observed

- Every error path sets both an HTTP status code and a JSON body before calling `exit` — no exceptions or custom error types are used.
- User-facing messages are in Russian; there is no i18n layer for error strings.
- Input is always sanitized with `strip_tags` before any further processing.
- The honeypot technique is used as a lightweight anti-bot measure rather than CAPTCHA.
- No `try/catch` blocks exist anywhere in the script; unhandled PHP warnings/notices would propagate as-is.
- There is no centralized error-handling module — this file is self-contained.
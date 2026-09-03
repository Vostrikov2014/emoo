---
kind: logging_system
name: Minimal PHP error_log-based logging for form submission
category: logging_system
scope:
    - '**'
source_files:
    - send-brief.php
---

This repository is a static single-page marketing site with one server-side script (`send-brief.php`). There is no dedicated logging framework, logger class, log directory, or structured logging library. The only logging-related code is a single use of PHP's built-in `error_log()` function.

**What is used**
- PHP core `error_log()` (line 116–117 of `send-brief.php`) to record successful brief submissions.
- No PSR-3 logger, no Monolog, no custom logger class, no log rotation, no log levels (debug/info/warn/error), and no centralized configuration.

**Where it lives**
- `send-brief.php` — the sole place where any log output is produced. It writes one line per successful form submission:
  ```php
  error_log(sprintf("[%s] BRIEF: name=%s, contact=%s, ip=%s\n",
      date('Y-m-d H:i:s'), $name, $contact, $_SERVER['REMOTE_ADDR'] ?? '—'));
  ```
- The log message is a plain-text line containing a timestamp, the submitted name, contact, and client IP address.

**Architecture and conventions**
- Logging is ad-hoc and co-located with the business logic in the form handler; there is no separation between logging infrastructure and application code.
- Only success paths are logged (successful mail send). Validation failures and rejected POST requests return JSON responses without writing logs.
- Log format is a simple key=value-style string wrapped in brackets around a `date('Y-m-d H:i:s')` timestamp. There is no machine-parsable structured format (no JSON, no syslog severity).
- Output goes to PHP's default error log sink (typically the web server's error log or `php.ini`'s `error_log` destination); no file path or stream is specified.

**Conventions and constraints**
- Because this is a minimal landing page, there are no enforced logging rules beyond what the single script does. The observed convention is: log successful outbound actions via `error_log()` with a human-readable timestamped line; do not log validation errors or normal request flow.
- Error handling uses HTTP status codes and JSON bodies rather than log entries (e.g., 405 for wrong method, 400 for validation failure, 500 for mail send failure). Errors are surfaced to the caller, not persisted to a log.
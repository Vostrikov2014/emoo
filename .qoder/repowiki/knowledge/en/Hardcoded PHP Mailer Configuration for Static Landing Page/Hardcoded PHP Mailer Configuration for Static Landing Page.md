---
kind: configuration_system
name: Hardcoded PHP Mailer Configuration for Static Landing Page
category: configuration_system
scope:
    - '**'
source_files:
    - send-brief.php
    - index.html
---

## What system/approach is used

This repository contains a static bilingual (RU/EN) marketing landing page with a single server-side component: `send-brief.php`, a PHP script that processes the brief form via PHP's built-in `mail()` function. There is no configuration framework, environment variable loader, config file parser, or feature flag system. All runtime settings are **hardcoded directly in source code**.

## Key files and packages

- `send-brief.php` — the only PHP file; holds all mail-related configuration inline.
- `index.html` — the frontend form posts to `send-brief.php` via AJAX (`XMLHttpRequest` POST to `send-brief.php`).
- No `.env`, `config/`, `*.yaml`, `*.toml`, `application.properties`, or any other configuration artifact exists in the repository.

## Architecture and conventions

Configuration is embedded as PHP variables at the top of the handler:

```php
$to_email   = 'emoo@emoo.ru';
$from_email = 'emoo@emoo.ru';
```

These two variables control the destination and sender addresses used by the `mail()` call on line 103:
```php
$sent = mail($to_email, $subject, $body, $headers, "-f{$from_email}");
```

The email subject, headers, Reply-To logic, and honeypot field name (`website_url`) are also hardcoded literals inside the same file. The form endpoint URL (`send-brief.php`) is hard-coded in both the HTML `<form action="send-brief.php">` (line 729) and the JavaScript AJAX call (`xhr.open('POST', 'send-brief.php', true)` at line 973).

There is no separation between deployment environments (dev/staging/prod); the same file is intended to be deployed as-is. Changing the recipient requires editing the PHP source.

## Conventions and constraints

- **No external configuration mechanism**: The project does not read from environment variables, config files, or any registry. All operational values are literal strings in `send-brief.php`.
- **Single responsibility for configuration**: Because there is only one backend entry point, all mail configuration lives in one place (`send-brief.php` lines 17–19), which simplifies changes but offers no abstraction layer.
- **Honeypot anti-spam field** is configured by convention: the hidden input named `website_url` in `index.html` must match the `$_POST['website_url']` check in `send-brief.php`; changing one without the other breaks the bot protection.
- **Form endpoint coupling**: The frontend expects the handler at the relative path `send-brief.php`. Moving or renaming the file requires updating both the HTML form `action` attribute and the JS `xhr.open` call.
- **PHP version constraint**: The script declares compatibility with PHP 7.4+ in its header comment; this acts as an implicit runtime requirement rather than a declared dependency manifest.
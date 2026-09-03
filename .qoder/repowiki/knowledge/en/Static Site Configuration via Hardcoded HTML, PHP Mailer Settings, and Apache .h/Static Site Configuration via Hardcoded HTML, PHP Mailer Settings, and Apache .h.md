---
kind: configuration_system
name: Static Site Configuration via Hardcoded HTML, PHP Mailer Settings, and Apache .htaccess
category: configuration_system
scope:
    - '**'
source_files:
    - index.html
    - send-brief.php
    - .htaccess
    - robots.txt
    - sitemap.xml
---

## What system/approach is used

This repository is a single-page marketing site with no application framework. There is no centralized configuration loader, no `.env` files, no YAML/JSON config, and no feature-flag system. All runtime behavior is controlled through three hard-coded layers:

1. **HTML content & UI state** — `index.html` contains all visible text (bilingual RU/EN), SEO metadata, structured data, CSS custom properties (`:root` color/font variables), and inline JavaScript that drives language switching, scroll animations, and form UX.
2. **PHP mailer settings** — `send-brief.php` holds the only server-side configuration: recipient email addresses, sender address, allowed area values, and validation rules.
3. **Apache webserver configuration** — `.htaccess` enforces HTTPS, security headers, caching, compression, and file access rules.

## Key files and packages

- `index.html` — page markup, embedded CSS/JS, bilingual content, SEO tags, Open Graph/Twitter Card, Schema.org JSON-LD, CSS custom property palette, default language selector (`setLang('ru')`).
- `send-brief.php` — brief form handler; recipients, sender, honeypot field name, input sanitization, allowed `area` enum, error messages, and `mail()` transport.
- `.htaccess` — rewrite rules, `mod_headers` security policy, `mod_expires` cache durations, `mod_deflate` compression whitelist, sensitive-file blocklist.
- `robots.txt`, `sitemap.xml` — search-engine configuration.
- `images/` — static assets referenced by relative paths throughout the page.

## Architecture and conventions

- **No abstraction layer**: Every piece of "configuration" is written directly into the file where it is consumed. There is no shared config module or environment variable lookup.
- **Bilingual content as data attributes**: Language selection is implemented by toggling `data-lang` on `<body>` and reading `data-en` / `data-ru` attributes on elements. The default language is set in JS via `setLang('ru')` (line 922) and can be changed to `'en'` there.
- **CSS theming via `:root` variables**: Colors, fonts, and spacing tokens are declared once at the top of the `<style>` block and reused throughout the stylesheet.
- **Form submission endpoint**: The `<form action="send-brief.php" method="POST">` posts to a sibling PHP script; there is no API gateway or external service integration beyond PHP's built-in `mail()`.
- **Honeypot anti-spam**: A hidden `website_url` field is ignored for real users but causes silent success responses if bots fill it — this is the only spam-protection mechanism.
- **Server-side defaults**: Recipients (`emoo@emoo.ru, tishkova.d@emoo.ru`), sender (`emoo@emoo.ru`), allowed area options, and validation thresholds are literal strings inside `send-brief.php`. Changing any of these requires editing the source file.
- **Webserver policy as configuration**: HTTPS enforcement, security headers (`X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection`, `Referrer-Policy`, `Permissions-Policy`), one-year cache for static assets, and gzip/brotli compression are all defined in `.htaccess`.

## Conventions and constraints

- **All user-facing text lives in `index.html`** — there is no template engine or i18n library; RU and EN copies are duplicated inline using `.ru` / `.en` spans and `data-*` attributes.
- **No secrets management**: Email addresses, phone numbers, and domain names are hardcoded in both `index.html` and `send-brief.php`; they are not read from environment variables or secret stores.
- **Validation is local + server-side**: Frontend uses `required` and native HTML5 types; backend re-validates with `strip_tags`, length checks, email/phone regex, and an allowlist for the `area` select value.
- **Caching strategy**: Static assets are cached for 1 year via `ExpiresByType` directives; the HTML page itself has no explicit cache-control header in `.htaccess`.
- **Security posture**: The site forces HTTPS, sets restrictive `Permissions-Policy` (no geolocation/microphone/camera), blocks frame embedding, and prevents direct browsing of `.php`, `.log`, `.ini`, `.bak` files.
- **Language toggle convention**: Buttons carry `data-lang="ru"|"en"`; clicking one calls `setLang()`, which updates `document.body.setAttribute('data-lang', l)`, `document.documentElement.lang`, and swaps placeholders/content based on `data-en` / `data-ru` attributes.
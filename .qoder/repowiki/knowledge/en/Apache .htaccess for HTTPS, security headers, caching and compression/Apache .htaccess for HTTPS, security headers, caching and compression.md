---
kind: external_dependency
name: Apache .htaccess for HTTPS, security headers, caching and compression
slug: apache-htaccess
category: external_dependency
category_hints:
    - framework_behavior
scope:
    - '**'
---

The site uses an Apache `.htaccess` file at the document root to enforce HTTPS (301 redirect from HTTP), set security response headers (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`), enable one-year browser cache for static assets via `mod_expires`, and compress HTML/CSS/JS/SVG/JSON with `mod_deflate`. It also blocks direct browsing of `.php`, `.log`, `.ini`, `.bak` files. This is the only server-side configuration in the repo; the actual hosting environment must have these Apache modules enabled.
---
kind: external_dependency
name: Google Fonts for Unbounded, Manrope, JetBrains Mono
slug: google-fonts
category: external_dependency
category_hints:
    - vendor_identity
scope:
    - '**'
---

The page loads three font families — Unbounded, Manrope, and JetBrains Mono — from `fonts.googleapis.com` with preconnect hints to `fonts.googleapis.com` and `fonts.gstatic.com`. These are external third-party resources that affect page load performance and privacy; they are referenced directly in the HTML head.
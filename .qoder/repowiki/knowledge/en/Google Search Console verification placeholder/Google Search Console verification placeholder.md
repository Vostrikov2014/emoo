---
kind: external_dependency
name: Google Search Console verification placeholder
slug: google-search-console
category: external_dependency
category_hints:
    - client_constraint
scope:
    - '**'
---

The page includes a commented-out `<meta name="google-site-verification">` tag intended for Google Search Console ownership verification. The project also declares hreflang alternates for `ru` and `en` plus a `x-default` fallback, which Google uses to understand the bilingual structure. Verification code must be inserted manually after registering the property in Google Search Console.
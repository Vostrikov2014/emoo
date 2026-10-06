---
kind: external_dependency
name: Yandex Webmaster verification placeholder
slug: yandex-webmaster
category: external_dependency
category_hints:
    - client_constraint
scope:
    - '**'
---

The page includes a commented-out `<meta name="yandex-verification">` tag intended for Yandex Webmaster ownership verification. Combined with the sitemap.xml and robots.txt pointing to it, this is the standard way to register the site with Yandex's indexing service. Verification code must be inserted manually after registering the property at webmaster.yandex.ru.
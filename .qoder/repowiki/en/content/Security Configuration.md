# Security Configuration

<cite>
**Referenced Files in This Document**
- [.htaccess](file://.htaccess)
- [send-brief.php](file://send-brief.php)
- [index.html](file://index.html)
- [README.md](file://README.md)
- [robots.txt](file://robots.txt)
</cite>

## Update Summary
**Changes Made**
- Updated .htaccess configuration section to reflect new proxy-aware HTTPS enforcement
- Enhanced security headers documentation with comprehensive header coverage
- Added detailed caching policies with one-year expiration for static assets
- Documented Gzip compression implementation for text-based content
- Updated sensitive file access restrictions documentation
- Enhanced troubleshooting guide with new security considerations

## Table of Contents
1. [Introduction](#introduction)
2. [Project Structure](#project-structure)
3. [Core Components](#core-components)
4. [Architecture Overview](#architecture-overview)
5. [Detailed Component Analysis](#detailed-component-analysis)
6. [Dependency Analysis](#dependency-analysis)
7. [Performance Considerations](#performance-considerations)
8. [Troubleshooting Guide](#troubleshooting-guide)
9. [Conclusion](#conclusion)
10. [Appendices](#appendices)

## Introduction
This document provides a comprehensive security overview for the EMOO website's multi-layered protection strategy. It covers server-level hardening via .htaccess (proxy-aware HTTPS enforcement, comprehensive security headers, caching policies, compression), backend input validation and sanitization in PHP, bot mitigation using honeypot fields, and guidance on monitoring, incident response, and best practices. It also clarifies how local email delivery within the same hosting environment improves reliability and reduces exposure.

## Project Structure
The site is minimal and focused:
- A static landing page with an embedded form that submits to a PHP endpoint.
- A PHP handler that validates, sanitizes, and sends emails locally.
- Server configuration (.htaccess) enforcing proxy-aware HTTPS, setting comprehensive security headers, enabling compression, and controlling caching.
- robots.txt to prevent indexing of sensitive endpoints.

```mermaid
graph TB
Client["Browser"] --> HTAccess[".htaccess<br/>Proxy-Aware HTTPS, Security Headers, Cache, Compression"]
HTAccess --> Index["index.html<br/>Form + JS"]
Index --> Handler["send-brief.php<br/>Validation, Sanitization, Local Mail"]
Handler --> Mail["Local MTA<br/>Same Hosting Environment"]
Index -.-> Robots["robots.txt<br/>Disallow sensitive paths"]
```

**Diagram sources**
- [.htaccess:6-52](file://.htaccess#L6-L52)
- [index.html:782-1067](file://index.html#L782-L1067)
- [send-brief.php:1-126](file://send-brief.php#L1-L126)
- [robots.txt:1-11](file://robots.txt#L1-L11)

**Section sources**
- [.htaccess:1-56](file://.htaccess#L1-L56)
- [index.html:782-1067](file://index.html#L782-L1067)
- [send-brief.php:1-126](file://send-brief.php#L1-L126)
- [robots.txt:1-11](file://robots.txt#L1-L11)

## Core Components
- Proxy-aware HTTPS enforcement and comprehensive security headers at the web server level.
- Input validation and sanitization in the PHP handler.
- Honeypot field to deter automated submissions.
- Local mail delivery to reduce interception risk and improve deliverability.
- robots.txt to hide sensitive endpoints from crawlers.

Key responsibilities:
- .htaccess: Enforce proxy-aware HTTPS, set comprehensive security headers, enable compression, cache static assets for one year, and restrict direct access patterns for sensitive file types.
- send-brief.php: Accept POST only, sanitize inputs, validate formats and lengths, build email content safely, log attempts, and return JSON responses.
- index.html: Provide a user-friendly form, client-side validation, and AJAX submission to the PHP handler.
- robots.txt: Disallow crawling of PHP endpoints and configuration files.

**Section sources**
- [.htaccess:6-52](file://.htaccess#L6-L52)
- [send-brief.php:1-126](file://send-brief.php#L1-L126)
- [index.html:782-1067](file://index.html#L782-L1067)
- [robots.txt:1-11](file://robots.txt#L1-L11)

## Architecture Overview
The request flow emphasizes secure transport, strict server policies, and safe processing on the backend.

```mermaid
sequenceDiagram
participant B as "Browser"
participant A as ".htaccess"
participant I as "index.html"
participant P as "send-brief.php"
participant M as "Local MTA"
B->>A : HTTP Request
A-->>B : 301 Redirect to HTTPS (Proxy-Aware)
B->>A : HTTPS Request
A-->>I : Serve index.html with Security Headers
B->>P : POST /send-brief.php (AJAX)
P->>P : Validate & Sanitize Inputs
P->>M : Send Email (local)
M-->>P : Delivery Result
P-->>B : JSON Response (success or errors)
```

**Diagram sources**
- [.htaccess:6-11](file://.htaccess#L6-L11)
- [index.html:1009-1067](file://index.html#L1009-L1067)
- [send-brief.php:9-126](file://send-brief.php#L9-L126)

## Detailed Component Analysis

### .htaccess: Proxy-Aware HTTPS, Comprehensive Security Headers, Caching, Compression, Access Control

**Updated** Enhanced configuration with proxy-aware HTTPS enforcement and comprehensive security headers

- **Proxy-Aware HTTPS Enforcement**: Forces all traffic to HTTPS with intelligent detection of proxied connections using X-Forwarded-Proto header to prevent redirect loops when SSL termination occurs at load balancers or reverse proxies.
- **Comprehensive Security Headers**:
  - X-Content-Type-Options: Prevents MIME-type sniffing attacks.
  - X-Frame-Options: Restricts framing to same origin to prevent clickjacking.
  - X-XSS-Protection: Enables legacy XSS filtering mode for older browsers.
  - Referrer-Policy: Limits referrer information leakage with strict-origin-when-cross-origin policy.
  - Permissions-Policy: Disables sensitive browser features (geolocation, microphone, camera) to minimize attack surface.
- **Static Asset Caching**: Implements one-year expiration for images (JPEG, PNG, SVG), CSS, JavaScript, and font files (WOFF2) to optimize performance while keeping dynamic content fresh.
- **Gzip Compression**: Enables DEFLATE compression for HTML, CSS, JavaScript, SVG, and JSON content to significantly reduce bandwidth usage and improve load times.
- **Sensitive File Handling**: Uses FilesMatch pattern to control access to PHP, logs, ini, and backup files; maintains accessibility for AJAX-endpoint scripts while blocking direct browsing to sensitive files.

Security benefits:
- Ensures encrypted transport for all requests with proxy compatibility.
- Mitigates common client-side attacks through restrictive security headers.
- Reduces attack surface by limiting exposure of sensitive file types.
- Optimizes performance through aggressive caching and compression.

**Section sources**
- [.htaccess:6-11](file://.htaccess#L6-L11)
- [.htaccess:17-24](file://.htaccess#L17-L24)
- [.htaccess:26-37](file://.htaccess#L26-L37)
- [.htaccess:39-46](file://.htaccess#L39-L46)
- [.htaccess:48-52](file://.htaccess#L48-L52)

### send-brief.php: Input Validation, Sanitization, Logging, Local Mail
- Method Restriction: Only accepts POST; returns 405 for other methods.
- Honeypot Field: Silently accepts submissions if a hidden field is filled, effectively blocking bots without friction for real users.
- Sanitization: Trims whitespace and strips tags from all inputs before use.
- Validation:
  - Name length constraints (2-100 characters).
  - Contact must be a valid email or phone number format.
  - Company name length limit (200 characters).
  - Area selection validated against a whitelist with normalization for Unicode variants.
  - Message length limit (2000 characters).
- Email Construction:
  - Subject and body built from sanitized inputs.
  - Reply-To header set based on whether contact is an email.
  - Content-Type set to plain text UTF-8.
  - Sender address explicitly set to a domain-matching email to improve trust and deliverability.
- Local Mail Delivery: Uses the host's local mail() function to send internally, reducing external exposure and leveraging trusted internal routing.
- Logging: Records successful submissions with timestamp, name, contact, and IP to aid monitoring and forensics.
- Responses: Returns JSON with success or error messages and appropriate HTTP status codes.

Security benefits:
- Strong input validation and sanitization reduce injection and XSS risks.
- Honeypot mitigates automated spam.
- Local mail delivery minimizes interception risk and improves deliverability within the same hosting environment.
- Structured logging supports monitoring and incident response.

**Section sources**
- [send-brief.php:9-28](file://send-brief.php#L9-L28)
- [send-brief.php:30-81](file://send-brief.php#L30-L81)
- [send-brief.php:83-113](file://send-brief.php#L83-L113)
- [send-brief.php:115-125](file://send-brief.php#L115-L125)

### index.html: Form UX, Client-Side Validation, AJAX Submission
- Form Fields: Includes a hidden honeypot field to detect bots.
- Client-Side Validation: Checks required fields and prevents submission if invalid.
- AJAX Submission: Sends form data to send-brief.php and handles success/error states, showing feedback to the user.
- No CSRF token implementation visible in this file; the README references a token-based mechanism, but it is not present in the current codebase.

Security considerations:
- Client-side validation improves usability but must not be relied upon for security; server-side checks are essential and implemented in the PHP handler.
- The absence of CSRF tokens in the frontend means additional protections should be considered if cross-site request forgery is a concern.

**Section sources**
- [index.html:782-818](file://index.html#L782-L818)
- [index.html:1009-1067](file://index.html#L1009-L1067)

### robots.txt: Crawling Restrictions
- Disallows crawling of send-brief.php, get-csrf-token.php, and .htaccess to prevent search engines from indexing sensitive endpoints.

Security benefit:
- Reduces exposure of administrative or functional endpoints to automated discovery by crawlers.

**Section sources**
- [robots.txt:4-8](file://robots.txt#L4-L8)

## Dependency Analysis
- Browser depends on .htaccess for proxy-aware HTTPS redirection and comprehensive security headers.
- index.html depends on send-brief.php for processing form submissions.
- send-brief.php depends on the hosting environment's local mail system.
- robots.txt influences crawler behavior but does not enforce access control.

```mermaid
graph LR
Browser["Browser"] --> HTAccess[".htaccess<br/>Proxy-Aware HTTPS & Security"]
HTAccess --> Index["index.html"]
Index --> Handler["send-brief.php"]
Handler --> LocalMTA["Local MTA"]
Crawler["Crawler"] --> Robots["robots.txt"]
```

**Diagram sources**
- [.htaccess:6-11](file://.htaccess#L6-L11)
- [index.html:1009-1067](file://index.html#L1009-L1067)
- [send-brief.php:113-125](file://send-brief.php#L113-L125)
- [robots.txt:4-8](file://robots.txt#L4-L8)

**Section sources**
- [.htaccess:6-11](file://.htaccess#L6-L11)
- [index.html:1009-1067](file://index.html#L1009-L1067)
- [send-brief.php:113-125](file://send-brief.php#L113-L125)
- [robots.txt:4-8](file://robots.txt#L4-L8)

## Performance Considerations
- **Aggressive Caching**: One-year expiration for static assets significantly reduces repeated downloads and improves load times across subsequent visits.
- **Compression**: DEFLATE compression reduces payload sizes for text-based resources, improving transfer speed and reducing bandwidth costs.
- **Local Mail**: Sending email via the local MTA avoids network overhead and leverages optimized local routing.
- **Proxy Compatibility**: Intelligent HTTPS detection prevents redirect loops in modern hosting environments with SSL termination at load balancers.

## Troubleshooting Guide
Common issues and resolutions:
- **405 Method Not Allowed**: Ensure the form uses POST; GET requests are rejected by the handler.
- **400 Bad Request**: Indicates validation errors; check input formats and lengths.
- **500 Internal Server Error**: Likely a mail delivery failure; verify local MTA configuration and permissions.
- **Redirect Loops**: If experiencing infinite redirects, ensure proper X-Forwarded-Proto header handling in proxy/load balancer configurations.
- **403 Forbidden**: Often caused by accessing the site over HTTP instead of HTTPS; ensure the redirect is active and SSL is configured.
- **Spam or Bot Submissions**: If honeypot is bypassed, consider adding rate limiting at the application or server level.

Monitoring and diagnostics:
- Review application logs written by the handler to track successful submissions and IPs.
- Inspect server error logs for PHP or mail-related failures.
- Use browser developer tools to inspect AJAX requests and responses during testing.
- Monitor cache hit ratios to validate one-year expiration effectiveness.

Recommended enhancements:
- Implement CSRF tokens on both frontend and backend to protect against cross-site request forgery.
- Add rate limiting per IP to prevent abuse and spam bursts.
- Enable Content-Security-Policy to further constrain resource loading and execution contexts.
- Rotate and securely store any secrets or credentials if added later.
- Consider implementing Brotli compression alongside DEFLATE for better performance.

**Section sources**
- [send-brief.php:9-15](file://send-brief.php#L9-L15)
- [send-brief.php:76-81](file://send-brief.php#L76-L81)
- [send-brief.php:115-125](file://send-brief.php#L115-L125)
- [README.md:65-72](file://README.md#L65-L72)

## Conclusion
The EMOO website employs a practical, layered security approach with enhanced server-level protections:
- **Proxy-aware HTTPS enforcement** ensures secure transport while maintaining compatibility with modern hosting architectures.
- **Comprehensive security headers** provide robust protection against common web vulnerabilities including XSS, clickjacking, and MIME-type sniffing.
- **Aggressive caching policies** with one-year expiration optimize performance while maintaining security.
- **Gzip compression** reduces bandwidth usage and improves load times.
- **Robust backend validation** and sanitization mitigate injection and XSS risks.
- **Honeypot-based bot mitigation** and structured logging enhance observability.
- **Local email delivery** reduces exposure and improves reliability within the same hosting environment.

To further strengthen security, consider implementing CSRF tokens, rate limiting, and a comprehensive Content-Security-Policy, along with ongoing log monitoring and incident response procedures.

## Appendices

### Security Log Monitoring Checklist
- Verify that successful submissions are logged with timestamps, names, contacts, and IPs.
- Set up alerts for spikes in failed submissions or unusual IP activity.
- Regularly review server error logs for PHP or mail subsystem issues.
- Monitor cache performance metrics to validate one-year expiration effectiveness.
- Track compression ratios to ensure optimal bandwidth usage.

**Section sources**
- [send-brief.php:115-117](file://send-brief.php#L115-L117)

### Best Practices for Secure Web Applications
- Always enforce proxy-aware HTTPS and set comprehensive security headers.
- Validate and sanitize all user inputs on the server side.
- Use anti-bot techniques such as honeypots and CAPTCHA where appropriate.
- Implement CSRF protection for state-changing operations.
- Apply rate limiting to sensitive endpoints.
- Monitor logs and set up alerting for anomalies.
- Keep dependencies and server software updated.
- Implement aggressive caching strategies for static assets.
- Enable compression for text-based content to optimize performance.
- Regularly audit security headers and permissions policies.

### Security Headers Reference
The following security headers are currently implemented:
- **X-Content-Type-Options: nosniff** - Prevents MIME-type sniffing
- **X-Frame-Options: SAMEORIGIN** - Restricts framing to same origin
- **X-XSS-Protection: 1; mode=block** - Enables XSS filtering
- **Referrer-Policy: strict-origin-when-cross-origin** - Controls referrer information
- **Permissions-Policy: geolocation=(), microphone=(), camera=()** - Disables sensitive features

[No sources needed since this section provides general guidance]
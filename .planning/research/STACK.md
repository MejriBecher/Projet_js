# Technology Stack

**Project:** Hotel Reservation System (PHP/MySQL)
**Researched:** 2026-05-11
**Overall confidence:** HIGH (all versions verified against official sources)

---

## Recommended Stack

### Core Framework

| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| PHP | **8.5.6** (latest); target **8.4+** for deployment | Backend language | PHP 8.5 is the current stable branch as of May 2026. PHP 8.4 remains in active support until Dec 2026 and is the safe minimum target for broader hosting compatibility. PHP 8.5 brings the pipe operator (`|>`), clone-with syntax, `#[NoDiscard]` attribute, `array_first()`/`array_last()` helpers, and improved fatal-error backtraces. PHP 8.4 introduced property hooks, asymmetric visibility, and driver-specific PDO classes — the latter is directly relevant to this project. |
| MySQL | **9.7.0 LTS** (latest); target **8.4 LTS** for deployment | Relational database | MySQL 8.0 reached EOL in April 2026. MySQL 9.7 LTS (released 2026-04-21) has 8-year support horizon (to Apr 2034). MySQL 8.4 LTS is the previous LTS branch, widely available on all hosting platforms, with support to ~2032. Use 8.4 LTS as the guaranteed deployment target; 9.7 LTS if the hosting environment supports it. Both use InnoDB with full transaction support — essential for preventing double-bookings. |
| Chart.js | **4.5.1** | Admin dashboard charts | The latest stable release (Oct 2025). Provides bar, pie, doughnut, line chart types needed for STAT-01 (occupancy rates, revenue breakdowns by service, reservation trends). Ships as ESM with a UMD bundle for CDN use. No build step required — drop in a `<script>` tag. |

### Infrastructure

| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| Apache or Nginx | Any recent stable | Web server | This vanilla PHP app uses `require_once` partials and no URL rewriting beyond basic routing. Either server works equally well. If using Apache, ensure `mod_rewrite` is available for clean URLs (optional). If using Nginx, ensure `try_files` is configured to route `.php` requests to PHP-FPM. |
| PHP-FPM | Ships with PHP 8.5 | FastCGI process manager | Required for Nginx; recommended for Apache (mod_proxy_fcgi) over mod_php for process isolation and security. Use `ondemand` process management for this app's traffic level. |

### Database Driver

| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| PHP PDO MySQL driver | Ships with PHP (`ext-pdo_mysql`) | Database access layer | The project spec mandates raw PDO, no ORM. PHP 8.4+ ships with driver-specific PDO classes (e.g., `Pdo\Mysql`) which provide access to MySQL-native methods like `lastInsertId()`. Use the standard `PDO` class for portability; the new driver-specific classes are optional. |

### Supporting Libraries

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| Chart.js (CDN) | 4.5.1 | Bar charts, pie charts, line charts | Admin dashboard statistics (STAT-01) |
| None (vanilla JS) | — | Form validation, price calculator | UI-02, UI-03 — no library needed |

---

## Alternatives Considered

| Category | Recommended | Alternative | Why Not |
|----------|-------------|-------------|---------|
| PHP version | 8.5 (dev) / 8.4 (target) | PHP 8.3 | 8.3 is security-fixes only since Dec 2025. No active bug fixes. Unnecessary risk for a new project. |
| PHP version | 8.5 (dev) / 8.4 (target) | PHP 7.x | Fully EOL. No security updates. Insecure by definition. |
| MySQL version | 9.7 LTS / 8.4 LTS | MySQL 8.0 | Reached EOL April 2026. No more official security patches. |
| MySQL version | 9.7 LTS / 8.4 LTS | MariaDB | The project spec says MySQL. MariaDB is a fork with diverging behavior (e.g., different `EXPLAIN` output, `SEQUENCE` engine, different `JSON` functions). Stick to MySQL for predictable PDO behavior. |
| Chart.js CDN | jsDelivr | CDNJS | CDNJS lags behind Chart.js releases (confirmed issue: CDNJS stuck at 4.4.1 while npm/GitHub have 4.5.1). jsDelivr is the CDN the official Chart.js docs reference. |
| Routing | `require_once` partials | Composer + router | Project spec prohibits Composer packages. Keep routing manual via include-based partials. |
| Templating | Raw PHP in partials | Twig / Blade | Spec says no packages. Raw PHP partials are sufficient for this scope. |

---

## Installation

### PHP Extensions Required

These ship with PHP 8.4+ but must be enabled in `php.ini`:

```ini
extension=pdo_mysql
extension=mbstring       ; for multibyte string handling
extension=gd             ; for image upload/resize (ROOM-03, SERV-01)
extension=fileinfo       ; for mime-type validation on uploads
```

Verify with `php -m` on the target environment.

### Chart.js CDN

```html
<!-- Production: pin exact version -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>

<!-- Development: auto-latest (use cautiously) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
```

**Why pin:** Auto-latest can break the admin dashboard on a minor release. Pin to 4.5.1 for reproducible behavior.

### MySQL Connection (PDO)

```php
<?php
$dsn = 'mysql:host=localhost;dbname=hotel_db;charset=utf8mb4';
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,   // use real prepared statements
];
$pdo = new PDO($dsn, $user, $pass, $options);
```

---

## Recommended PHP Settings (php.ini)

These are production-hardened settings tailored for this application. Sources: [OWASP PHP Configuration Cheat Sheet](https://github.com/OWASP/CheatSheetSeries/blob/master/cheatsheets/PHP_Configuration_Cheat_Sheet.md), [PHP Manual](https://www.php.net/manual/en/ini.core.php).

### Error Handling (PRODUCTION)

```ini
expose_php              = Off
error_reporting         = E_ALL & ~E_DEPRECATED & ~E_STRICT
display_errors          = Off
display_startup_errors  = Off
log_errors              = On
error_log               = /path/to/logs/php_error.log
ignore_repeated_errors  = Off
```

### Resource Limits

```ini
memory_limit            = 128M       ; adequate for CRUD hotel app with image uploads
max_execution_time      = 30         ; 30s is plenty for PDO queries; uploads need more
max_input_time          = 60         ; enough for form submission + image upload
post_max_size           = 12M        ; slightly above upload_max_filesize
upload_max_filesize     = 10M        ; room images (ROOM-03, SERV-01)
max_file_uploads        = 5          ; allow multiple room photos
```

### Session Security (AUTH-01, AUTH-02, AUTH-03)

```ini
session.auto_start      = Off        ; manual session_start() only on pages that need it
session.use_strict_mode = 1          ; reject uninitialized session IDs (CSRF mitigation)
session.use_cookies     = 1
session.use_only_cookies = 1         ; never accept session ID from URL
session.cookie_httponly = 1          ; JS cannot read session cookie
session.cookie_secure   = 1          ; only send over HTTPS
session.cookie_samesite = Lax        ; CSRF mitigation
session.cookie_lifetime = 0          ; expire on browser close
session.sid_length      = 256        ; 256-bit session IDs
session.sid_bits_per_character = 6   ; maximum entropy per character
session.gc_maxlifetime  = 14400      ; 4 hours — sensible for hotel booking session
session.name            = HOTEL_SESS ; rename from default PHPSESSID
session.save_path       = /path/to/sessions  ; outside document root!
```

### File Uploads (ROOM-03, SERV-01)

```ini
file_uploads            = On
upload_tmp_dir          = /path/to/php-uploads  ; outside document root
```

### Security Hardening

```ini
allow_url_fopen         = Off        ; prevent RFI (this app doesn't fetch remote files)
allow_url_include       = Off        ; prevent LFI-to-RFI escalation
disable_functions       = exec,passthru,shell_exec,system,proc_open,popen,phpinfo,show_source
```

### Performance

```ini
opcache.enable          = 1
opcache.memory_consumption = 64      ; adequate for this app's codebase size
opcache.max_accelerated_files = 4096
opcache.validate_timestamps = 1      ; set to 0 in production if deploying with a build step
opcache.revalidate_freq = 2          ; check for file changes every 2 seconds
realpath_cache_size     = 4M
realpath_cache_ttl      = 7200
```

### Date/Time

```ini
date.timezone           = UTC        ; or the hotel's local timezone
```

---

## PHP Version Support Timeline (for reference)

| PHP Version | Status (May 2026) | Bug Fixes Until | Security Fixes Until |
|-------------|-------------------|-----------------|---------------------|
| **8.5** | **Supported (latest)** | **2027-12-31** | **2029-12-31** |
| 8.4 | Supported | 2026-12-31 | 2028-12-31 |
| 8.3 | Security fixes only | 2025-12-31 (ended) | 2026-12-31 |
| 8.2 | Security fixes only | 2024-12-31 (ended) | 2025-12-31 (ended) |
| 8.1 | End of Life | 2023-12-31 | 2024-12-31 |

**Decision:** Target PHP **8.4** minimum for deployment (still in active support, most widely available). Develop against PHP **8.5** to use latest features and catch compatibility issues early.

Source: [php.net/supported-versions.php](https://www.php.net/supported-versions.php), confirmed via [PHP.Watch](https://php.watch/versions).

---

## MySQL Version Support Timeline

| MySQL Version | Status (May 2026) | Premier Support Until | Extended Support Until |
|---------------|-------------------|-----------------------|-----------------------|
| **9.7 LTS** | **Latest LTS** | **~2031** | **~2034** |
| **8.4 LTS** | **Supported LTS** | **~2029** | **~2032** |
| 8.0 | EOL (April 2026) | 2026-04 (ended) | N/A |
| 5.7 | EOL | 2023-10 (ended) | N/A |

**Decision:** Target MySQL **8.4 LTS** minimum (widely available on shared hosting, VPS, AWS RDS, etc.). Use **9.7 LTS** if hosting supports it. Both use InnoDB with transactions — the critical feature for this app.

Source: [Wikipedia MySQL](https://en.wikipedia.org/wiki/MySQL), [endoflife.date/mysql](https://endoflife.date/mysql), [Oracle MySQL docs](https://docs.oracle.com/en-us/iaas/mysql-database/doc/mysql-server-versions.html).

---

## Why Not ...

### Why not PHP 8.3 or earlier?
PHP 8.3 entered security-fix-only mode in December 2025. PHP 8.2's security support ended December 2025. Starting a new project on anything below 8.4 means accepting an unsupported runtime within the project's lifespan. PHP 8.5 is the correct branch for new work in May 2026.

### Why not MySQL 8.0?
MySQL 8.0 reached End of Life in April 2026. No more security patches. Using it for a new project means building on an unsupported foundation.

### Why not MariaDB?
The project spec says MySQL. While MariaDB is a drop-in replacement for many use cases, it has diverged in behavior around `EXPLAIN`, `SEQUENCE`, `JSON` functions, and some `INFORMATION_SCHEMA` tables. Using MySQL guarantees that PDO behavior matches the reference manual.

### Why not a framework (Laravel, Symfony)?
Project spec prohibits Composer packages. For this scope (CRUD rooms + reservations with session auth + Chart.js), vanilla PHP with PDO is appropriate and avoids framework overhead. The tradeoff is manual routing and no ORM — both acceptable tradeoffs for a sub-20-page application.

### Why not use CDNJS for Chart.js?
[Confirmed issue](https://github.com/chartjs/Chart.js/issues/11892): CDNJS lags behind Chart.js releases (stuck at 4.4.1 while latest is 4.5.1). jsDelivr is the CDN officially referenced in Chart.js documentation and stays current.

---

## Sources

- **PHP 8.5 download page**: https://www.php.net/downloads (confirmed "PHP 8.5.6 Released!" as of 2026-05-06)
- **PHP supported versions**: https://www.php.net/supported-versions.php
- **PHP.Watch version tracker**: https://php.watch/versions
- **MySQL 9.7 LTS announcement**: https://dev.mysql.com/doc/relnotes/mysql/9.7/en/
- **MySQL version support**: https://endoflife.date/mysql
- **Chart.js 4.5.1 release**: https://github.com/chartjs/Chart.js/releases/tag/v4.5.1
- **Chart.js installation docs**: https://www.chartjs.org/docs/latest/getting-started/installation.html
- **OWASP PHP Config Cheat Sheet**: https://github.com/OWASP/CheatSheetSeries/blob/master/cheatsheets/PHP_Configuration_Cheat_Sheet.md
- **PHP Manual - core php.ini directives**: https://www.php.net/manual/en/ini.core.php
- **PHP Manual - session security**: https://www.php.net/manual/en/session.security.ini.php
- **PHP Manual - PDO::beginTransaction**: https://www.php.net/manual/en/pdo.begintransaction.php

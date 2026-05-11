# Project Research Summary

**Project:** Hotel Reservation System (PHP/MySQL)
**Domain:** Hotel reservation web application — vanilla PHP, raw PDO, no framework
**Researched:** 2026-05-11
**Confidence:** HIGH (all four research files verified against official sources, industry PMS docs, and security advisories)

## Executive Summary

This is a **PHP/MySQL hotel reservation web application** with two user roles (guest, admin). Industry-standard Property Management Systems (PMS) like Cloudbeds, RoomRaccoon, and Oracle OPERA Cloud follow a three-phase booking flow: **Search & Evaluation** (browse + filter rooms), **Selection** (view details + calculate price), and **Checkout** (enter details + confirm). On the admin side, every PMS provides a dashboard with real-time KPIs, reservation management with status workflows, and basic reporting. The recommended approach uses **PHP 8.4+ (deploy target) / 8.5 (dev)**, **MySQL 8.4 LTS (minimum)**, **Chart.js 4.5.1** (CDN via jsDelivr), and a **feature-based folder structure** with `require_once` partials — no Composer, no ORM, no framework.

The **#1 risk** is double-booking from race conditions. Two concurrent users can both see "available" and both book — this requires a three-tier defense: (1) `SELECT ... FOR UPDATE` pessimistic locking inside a transaction, (2) the canonical overlap formula `check_in < :out AND check_out > :in`, and (3) defensive indexing on `(room_id, check_in, check_out, status)`. Additional critical risks include: **PDO emulated prepares bypass vulnerability** (fix: one-line `ATTR_EMULATE_PREPARES = false` in config), **XSS from unescaped output** (fix: a global `h()` helper using `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')` on every echo), **image upload RCE** (fix: `finfo` MIME validation + random filenames + `.htaccess` disabling PHP in uploads), and **session security on shared hosting** (fix: non-default session save path or DB-backed sessions). All are preventable with early design decisions — retrofitting these is painful.

## Key Findings

### Recommended Stack

The stack is straightforward and well-supported. **PHP 8.4** is the minimum deployable target (active security support through Dec 2028); **PHP 8.5.6** (latest stable as of May 2026) is recommended for development to catch compatibility issues early. **MySQL 8.4 LTS** is the guaranteed deployment target (widely available on all hosting, support through ~2032); **MySQL 9.7 LTS** (released April 2026) is the upgrade path with 8-year support. **Chart.js 4.5.1** via jsDelivr CDN handles all admin dashboard charts with `responsive: true` — no build step needed.

**Core technologies:**
- **PHP 8.4+ / 8.5**: Backend language — property hooks, driver-specific PDO classes, pipe operator, `array_first()`/`array_last()` helpers
- **MySQL 8.4 LTS / 9.7 LTS**: Relational DB — InnoDB with transactions essential for double-booking prevention; 8.4 LTS is the safe deployment target
- **Chart.js 4.5.1** (CDN via jsDelivr): Admin dashboard charts — bar (revenue), pie/doughnut (status distribution, room type occupancy), line (reservation trends)
- **PHP PDO MySQL** (ext-pdo_mysql): Database access layer — `PDO::ATTR_EMULATE_PREPARES = false` is mandatory (see pitfalls)
- **Extensions required**: `pdo_mysql`, `mbstring`, `gd`, `fileinfo` — all ship with PHP 8.4+

**CDN choice matters:** jsDelivr (not CDNJS) — CDNJS was confirmed stuck at Chart.js 4.4.1 while latest is 4.5.1. Pin exact version for reproducible behavior.

See [STACK.md](./STACK.md) for full version timelines, php.ini settings, and alternatives considered.

### Expected Features

The feature landscape is well-defined with clear industry patterns. **14 table stakes** form the MVP foundation, **14 differentiators** add competitive value, and **14 anti-features** are explicitly deferred to avoid scope creep.

**Must have (table stakes):**
- Room browse with cards (photo, price, capacity, description)
- Availability-based search with double-booking prevention — **high complexity, critical**
- JS filter by room type + price range (no libraries needed)
- 3-phase booking flow (dates → room + price → guest details + confirm)
- Live price calculator (JS: `nights × room_rate + extras`)
- User registration & login (session-based auth)
- User profile with booking history and status badges
- Admin: Room CRUD with image upload
- Admin: View all reservations with status change dropdown
- Admin: User management (view, deactivate)
- Inline form validation (JS)
- Consistent responsive UI
- Registration form validation (unique email, password confirmation)
- Admin: Services CRUD with image upload

**Should have (differentiators):**
- Chart.js admin statistics dashboard (bar + pie charts from real DB data) — **this is the capstone admin feature per PROJECT.md spec**
- Room type classification (Single, Double, Suite, Deluxe — book by type, not room number)
- Guest count-based pricing (base rate + extra guest charge)
- Services as booking addons (breakfast, spa, parking — live price updates on selection)
- Date-range filtering on admin reservations
- Admin booking notes (internal, admin-only visibility)
- Export reservations to CSV
- Cancel booking (user-initiated, before check-in)
- Confirmation page with booking reference number
- Password change from profile

**Defer to v2+:**
- Payment gateway — PCI compliance scope, huge security surface area
- Email notifications — SMTP config, deliverability issues
- Forgot password / reset flow — requires email
- Promo codes / discount coupons
- Review / rating system
- Multi-language support, mobile native app, OTA/channel manager, dynamic pricing, social login, real-time chat

See [FEATURES.md](./FEATURES.md) for the full feature dependency tree and edge-case analysis (half-open date intervals, anti-join availability pattern).

### Architecture Approach

The architecture follows a **feature-based folder grouping** design (files live in directories named after domain concepts: `rooms/`, `reservations/`, `auth/`, `admin/`), not by technical role (controllers/models/views). This mirrors the user journey and keeps related endpoints together. Each `.php` file is self-contained with both HTML output and POST handler logic (no micro-files). Shared dependencies flow via `require_once`: `config/database.php` → `includes/functions.php` → `partials/header.php` → page content → `partials/footer.php`. Admin pages add an admin role gate (`require_admin()` at top) and share `admin_header.php`/`admin_footer.php` with sidebar navigation.

**Major components:**
1. **config/** — PDO connection singleton, session start + security config, app constants. Every request loads these.
2. **auth/** — Registration, login, logout. Session-based auth with `password_hash()`/`password_verify()`. Two roles: guest, admin.
3. **rooms/** — Room detail page, AJAX filter endpoint. Foundation data for everything else.
4. **reservations/** — Booking form + POST handler, history, availability AJAX check. **Transactional heart of the system** — uses `SELECT ... FOR UPDATE` pessimistic locking.
5. **admin/** — Dashboard (Chart.js), room/services CRUD, reservation management with status dropdown, user management. Flat directory, all pages guarded by `require_admin()`.
6. **partials/** — HTML fragments (header, footer, admin_header, admin_footer). No logic, only `echo`.
7. **includes/** — PHP logic helpers (`functions.php`, `validation.php`, `image_upload.php`). No HTML.

**Database schema** uses 6 core tables: `users`, `rooms`, `services`, `room_services` (many-to-many room-service defaults), `reservations` (with status ENUM and compound index), `reservation_services` (price snapshot at booking time). The critical availability check uses `check_in < :check_out AND check_out > :check_in` — the canonical overlap pattern, not `BETWEEN`.

See [ARCHITECTURE.md](./ARCHITECTURE.md) for full schema SQL, Chart.js integration patterns (Approach A: same-page vs Approach B: AJAX), file upload handler, and the 7-phase build order.

### Critical Pitfalls

The pitfalls research identified **11 critical**, **4 moderate**, and **3 minor** pitfalls. The top 5 that demand attention from day one:

1. **Double-booking race condition (#1 system killer)** — Two concurrent users both pass the "available" check before either inserts. **Prevention:** Three-tier defense — `SELECT ... FOR UPDATE` pessimistic locking inside a transaction, canonical overlap SQL, and proper indexes on `(room_id, check_in, check_out, status)`. Must be built into RSRV phase from the start.

2. **PDO emulated prepares bypass vulnerability** — PDO defaults to `EMULATE_PREPARES = true`, recombining SQL on the client side. This has been exploited (CVE patterns in PHP PDO). **Prevention:** One-liner in `config/database.php`: `$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false)`. Address in the config/setup phase.

3. **Server-side XSS from unescaped output** — User data (name, notes, descriptions) rendered without `htmlspecialchars()` allows session hijacking. **Prevention:** Global `h()` helper: `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')`. Escape on output, not input. Apply in EVERY UI-rendering file from day one.

4. **Image upload leading to RCE** — PHP files disguised as images uploaded and executed. **Prevention:** `finfo` MIME validation (not `$_FILES['type']`), random UUID filenames (not user-supplied), `.htaccess` in `uploads/` disabling PHP execution (`php_flag engine off`). Address in ROOM-03 and SERV-01 phases.

5. **Session security on shared hosting** — Default `/tmp` session storage is world-readable. File-based sessions use `flock()` blocking for concurrent AJAX. **Prevention:** Move session save path outside document root, or use DB-backed sessions via `session_set_save_handler()`. Always `session_regenerate_id()` on login. Address in AUTH phase.

**Phase-specific warnings:** AUTH → password hashing (`password_hash`) + rate limiting; ROOM → image upload RCE; RSRV → double-booking + wrong overlap SQL; ADMIN → missing role gates + CSRF tokens; PROF → IDOR; STAT → SQL injection in dynamic queries; UI → XSS everywhere.

See [PITFALLS.md](./PITFALLS.md) for full details including suspicious file tests, overlap SQL edge cases, and the N+1 performance trap.

## Implications for Roadmap

The combined research reveals a clear dependency chain. All three research documents (FEATURES dependencies, ARCHITECTURE build order, PITFALLS phase warnings) converge on the same sequence. The roadmap should be structured into **6 phases**:

### Phase 1: Foundation & Configuration
**Rationale:** Nothing works without database connection, sessions, shared helpers, and page partials. This phase establishes the skeleton every page will use.
**Delivers:** Working database connection, session management, global helper functions (`h()`, `log_error()`, `redirect()`), header/footer partials, flash message system, `.htaccess` security rules, schema.sql with all 6 tables.
**Addresses:** Config infrastructure (implicit requirement for all features)
**Avoids:** PITFALL-2 (PDO emulated prepares — fix in config), PITFALL-10 (error info leaking), PITFALL-11 (credentials in source code), PITFALL-16 (strict SQL mode), PITFALL-18 (charset utf8mb4)
**Stack used:** PHP PDO configuration, MySQL schema
**Research flags:** **Well-documented, standard patterns** — skip deeper research

### Phase 2: Authentication System
**Rationale:** Auth blocks all user-facing features (RSRV requires login per spec). Must be done before any booking code. Also the phase where session security pitfalls are most salient.
**Delivers:** User registration (`auth/register.php`), login (`auth/login.php`), logout (`auth/logout.php`), session hardening (regenerate ID on login, idle timeout, secure cookie flags), rate limiting on login, `require_admin()` gate function for later admin phase.
**Addresses:** AUTH-01, AUTH-02, AUTH-03 (from PROJECT.md)
**Avoids:** PITFALL-5 (session security on shared hosting — move session path, regenerate ID), PITFALL-9 (MD5/SHA1 passwords — `password_hash`), PITFALL-15 (rate limiting on auth endpoints)
**Research flags:** **Well-documented, established patterns** — skip deeper research

### Phase 3: Room & Service Management
**Rationale:** Rooms are the data foundation. Without rooms, booking doesn't exist. Services are independent and can be parallel. Admin CRUD for both with image upload.
**Delivers:** Homepage room listing (`index.php`), room detail page (`rooms/details.php`), JS filter by type/price (`rooms/filter_ajax.php`), admin room CRUD (`admin/rooms.php`, create, edit, delete with image upload), admin services CRUD (`admin/services.php`, create, edit, delete with image upload), reusable `upload_image()` function, upload directory security (`.htaccess`), room images displayed on listing/detail pages.
**Addresses:** ROOM-01, ROOM-02, ROOM-03, SERV-01, UI-01 (responsive room cards)
**Avoids:** PITFALL-4 (image upload RCE — `finfo` validation, random filenames, `.htaccess`), PITFALL-12 (input validation), PITFALL-17 (forgetting to close statements in loops)
**Stack used:** PHP GD extension (image resize if needed), `fileinfo` extension
**Research flags:** **Well-documented** — but image upload security patterns should be cross-checked against server environment (Apache vs Nginx `.htaccess` equivalent). Low-risk.

### Phase 4: Reservation / Booking Flow
**Rationale:** This is the transactional heart of the system and the highest-risk phase. It depends on auth (Phase 2) and rooms (Phase 3). Must be built with `SELECT ... FOR UPDATE` from day one.
**Delivers:** Availability AJAX endpoint (`reservations/availability_check.php`), booking form + POST handler (`reservations/create.php`) with transactional pessimistic locking, user reservation history (`reservations/my.php`), user profile with booking history and status badges (`profile/index.php`), confirmation page with booking reference number, live price calculator (JS in `main.js`), inline form validation (JS).
**Addresses:** RSRV-01, PROF-01, UI-02, UI-03; differentiators D4 (guest count pricing), D10 (services as addons), D13 (user-initiated cancel), D14 (confirmation page)
**Avoids:** PITFALL-1 (double-booking — `SELECT ... FOR UPDATE` in transaction), PITFALL-7 (wrong overlap SQL — canonical formula), PITFALL-13 (check-out <= check-in validation)
**Stack used:** MySQL transactions, PDO prepared statements, vanilla JS
**Research flags:** **HIGH RISK — review concurrency test plan.** Consider writing a `test_concurrent_bookings.php` script (simulated parallel curl requests) to verify locking works. This is the one phase where additional validation during planning would be valuable.

### Phase 5: Admin Dashboard & Management
**Rationale:** Admin features depend on data from reservations and users existing. Admin reservation management (RSRV-02) needs RSRV-01 complete. Admin user management (CLNT-01) needs AUTH complete. Chart.js dashboard (STAT-01) is the capstone — it only makes sense once data exists.
**Delivers:** Admin dashboard (`admin/index.php`) with KPI stat cards (today's check-ins/outs, occupancy %, revenue MTD), full reservation list with inline status change dropdown (`admin/reservations.php` + `reservation_update.php`), user management with deactivate toggle (`admin/clients.php` + `client_toggle.php`), Chart.js statistics (`admin/stats_data.php` JSON endpoint + Chart.js rendering), reservation filtering by date/status/room type, admin booking notes, CSV export. Admin sidebar navigation with all sections.
**Addresses:** RSRV-02, CLNT-01, STAT-01; differentiators D1 (Chart.js dashboard), D6 (auto-status transitions), D7 (date range filtering), D8 (booking notes), D9 (CSV export), D12 (quick stats cards)
**Avoids:** PITFALL-6 (admin panel without role gates — `require_admin()` on every admin page), PITFALL-3 (XSS — `h()` helper on ALL output including reservation notes), baseline CSRF tokens on state-changing POST forms
**Stack used:** Chart.js 4.5.1 CDN (jsDelivr), PHP JSON endpoints
**Research flags:** Chart.js integration is well-documented. Admin role gating follows standard patterns. **Skip deeper research** — but review CSRF token implementation approach.

### Phase 6: Polish & Cross-Cutting
**Rationale:** Performance optimization, edge-case hardening, and UX polish make sense only after all features work. CSS responsive tweaks, caching strategy, session cleanup, cron job for status auto-transitions, `.htaccess` final hardening, and any remaining anti-feature verification (confirm nothing from the anti-features list leaked in).
**Delivers:** Responsive CSS refinements (mobile menu, breakpoints), OPcache tuning, query optimization (`EXPLAIN` on slow queries), simple file-based query cache for room listings, cron job (or page-load check) for auto-expiring pending reservations, final `.htaccess` security review, error log review.
**Avoids:** PITFALL-8 (N+1 queries — review all query patterns with `EXPLAIN`), PITFALL-14 (missing status lifecycle — auto-expire pending reservations)
**Research flags:** **Low priority** — can be addressed during any phase when performance issues are noticed.

### Phase Ordering Rationale

- **Dependency chain is strict**: Config → Auth → Rooms → Reservations → Admin → Polish. Every research file confirms this sequence. No meaningful reordering is possible.
- **Highest-risk work early**: PITFALL-1 (double-booking) is addressed in Phase 4, but Phases 1-3 establish the foundations that make it possible (session, rooms, partials, DB connection). The config phase (Phase 1) fixes PITFALL-2 (PDO emulated prepares) with a one-liner — cheap, massive impact.
- **Image upload security in Phase 3**: ROOM-03 and SERV-01 are where PITFALL-4 (RCE) must be prevented. The `upload_image()` function built here is reused everywhere.
- **Admin dashboard as capstone**: STAT-01 is listed last because charts need data from rooms, reservations, and users. It's the "reward" phase — makes the system feel complete.
- **Parallelization opportunity**: Admin CRUD for rooms (Phase 3) and services (Phase 3) can be built in parallel. Services are entirely independent of rooms.

### Research Flags

Phases likely needing deeper research during planning:
- **Phase 4 (Reservations):** **Recommend a `/gsd-research-phase` for concurrency testing plan.** The `SELECT ... FOR UPDATE` locking pattern is well-documented, but a concurrency simulation script should be designed before implementation to validate the locking works. This is the highest-risk feature. Testing approach: fire 10+ simultaneous requests for the same room/dates, verify only one succeeds.

Phases with standard patterns (skip research-phase):
- **Phase 1 (Foundation):** PDO connection, session config, schema.sql — every PHP project follows the same patterns.
- **Phase 2 (Auth):** Session-based auth with `password_hash` — well-trodden path.
- **Phase 3 (Rooms/Services):** CRUD with image upload — standard pattern replicated across every PHP hotel reference project.
- **Phase 5 (Admin):** Role-gated CRUD with Chart.js — well-documented in Chart.js official docs and multiple PHP hotel repos.
- **Phase 6 (Polish):** Performance tuning — address as needed, not a discrete research topic.

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | PHP 8.5.6, MySQL 9.7.0 LTS, Chart.js 4.5.1 — all versions verified against official release pages and support timelines. CDN choice (jsDelivr) confirmed via Chart.js official docs. |
| Features | HIGH | Feature landscape mapped against 10+ industry PMS sources (Cloudbeds, RoomRaccoon, Oracle OPERA Cloud, QloApps, Guesty, OnRes, HotelMinder, RoomStay). Table stakes vs differentiators clearly differentiated. Overlap detection pattern (anti-join, half-open intervals) from multiple authoritative sources. |
| Architecture | HIGH | Folder structure verified against 5+ reference PHP/MySQL hotel projects. DB schema follows normalized patterns with appropriate compromise (ENUM vs status table). File upload handler based on PHP.net official docs. Chart.js integration from official Chart.js docs. Build order derived from dependency chain analysis across all three research files. |
| Pitfalls | HIGH | Each pitfall sourced from specific security advisories (CVE patterns), OWASP cheat sheets, PHP manual, PortSwigger, Percona, and domain-specific research (race conditions in booking systems, 2026). Confidence varies per source but overall HIGH because multiple independent sources converge on the same mitigations. |

**Overall confidence:** HIGH

### Gaps to Address

- **Concurrency testing script**: The `SELECT ... FOR UPDATE` locking pattern is well-documented, but its effectiveness depends on correct implementation within the transaction scope. A test script should be written alongside the booking feature to validate under load. This is the single highest-risk item and should be validated during Phase 4 implementation, not deferred.
- **Hosting environment specifics**: The `.htaccess` security rules (upload directory PHP blocking, deny access to sensitive dirs) assume Apache. If deployment uses Nginx, equivalent `location` blocks must be configured. This should be confirmed when the deployment target is known — not a blocking issue but worth documenting in the deploy checklist.
- **Session storage strategy**: File-based sessions (with custom save path outside web root) vs DB-backed sessions. DB sessions are more secure and avoid `flock()` blocking but add a query per request. Decision should be made in Phase 2 based on expected concurrency. Recommendation: start with file-based sessions (non-default path), switch to DB sessions if concurrent AJAX performance is a problem.
- **Email notification workaround for v1**: Password reset is deferred because email is out of scope, but this means admin must manually handle password resets. This is acceptable but should be documented in the admin guide.

## Sources

### Primary (HIGH confidence)
- **PHP 8.5.6 download**: https://www.php.net/downloads — confirmed latest release
- **PHP supported versions**: https://www.php.net/supported-versions.php — version timeline
- **MySQL 9.7 LTS announcement**: https://dev.mysql.com/doc/relnotes/mysql/9.7/en/ — latest LTS
- **MySQL end-of-life dates**: https://endoflife.date/mysql — 8.0 EOL confirmation
- **Chart.js 4.5.1 release**: https://github.com/chartjs/Chart.js/releases/tag/v4.5.1 — latest stable
- **Chart.js CDN docs**: https://www.chartjs.org/docs/latest/getting-started/installation.html — jsDelivr reference
- **PHP Manual - PDO**: https://www.php.net/manual/en/pdo.begintransaction.php — transactions
- **PHP Manual - Sessions**: https://www.php.net/manual/en/session.security.php — session security
- **PHP Manual - File Uploads**: https://www.php.net/manual/en/features.file-upload.php — `move_uploaded_file`, `finfo`
- **OWASP XSS Prevention**: https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html — contextual escaping
- **PortSwigger - File Upload**: https://portswigger.net/web-security/file-upload — RCE via upload
- **Percona - Repeatable Read**: https://www.percona.com/blog/what-if-mysqls-repeatable-reads-cause-you-to-lose-money/ — MySQL isolation pitfalls
- **CampCodes Hotel Reservation** — reference PHP/MySQL hotel project structure
- **SourceCodester Hotel Reservation** — admin panel layout, CRUD patterns
- **DayPilot PHP Hotel Room Booking** — REST API, overlap prevention
- **Oracle OPERA Cloud docs** — industry gold standard booking flow
- **Cloudbeds / RoomRaccoon PMS** — feature set comparison (HotelTech Review 2026)

### Secondary (MEDIUM confidence)
- **slcyber.io - SQLi in PDO**: https://slcyber.io/research-center/a-novel-technique-for-sql-injection-in-pdos-prepared-statements — PDO emulated prepares bypass (July 2025)
- **HackerNoon - Race Conditions**: https://hackernoon.com/how-to-solve-race-conditions-in-a-booking-system — race condition patterns
- **amitavroy.com - Race Conditions in Hotels**: https://www.amitavroy.com/articles/race-conditions-in-hotel-booking-systems-why-your-technology-choice-matters-more-than-you-think — domain-specific (Feb 2026)
- **DEV Community - Secure PHP Uploads**: https://dev.to/einlinuus/how-to-upload-files-with-php-correctly-and-securely-1kng — file upload best practices
- **ssojet.com - HTML Escaping**: https://ssojet.com/escaping/html-escaping-in-php — common `htmlspecialchars` mistakes (Dec 2025)
- **Cybersecurity Dive - cPanel CVE-2026-41940**: https://www.cybersecuritydive.com/news/critical-vulnerability-cpanel-widespread-exploitation/819208/ — auth bypass in admin panels (May 2026)
- **QloApps Stats Management** — dated (2016) but patterns are standard
- **Anand Systems - Modern PMS Features** (2025) — feature validation

---
*Research completed: 2026-05-11*
*Ready for roadmap: yes*

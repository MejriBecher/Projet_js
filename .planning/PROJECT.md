# Hotel Reservation System

## What This Is

A PHP/MySQL hotel reservation web application that lets guests browse rooms, make reservations, and manage their bookings, while providing administrators with a full dashboard for managing rooms, services, reservations, clients, and viewing statistical reports. Built with vanilla PHP (PDO), MySQL, Chart.js, and no external frameworks.

## Core Value

Guests can find, book, and manage their hotel room reservations online; admins can manage the entire hotel operation from a single dashboard.

## Requirements

### Validated

(None yet — ship to validate)

### Active

- **AUTH-01**: User can register with name, email, password
- **AUTH-02**: User can log in with email/password — session managed
- **AUTH-03**: User can log out — session destroyed
- **ROOM-01**: Homepage shows room cards with details (price, capacity, image, description)
- **ROOM-02**: Room listing supports JS filter by type and price
- **ROOM-03**: Admin can create, edit, delete rooms with image upload
- **RSRV-01**: Logged-in user can book a room with date range
- **RSRV-02**: Admin can view all reservations and change status via dropdown
- **PROF-01**: User can view their profile and reservation history with status badges
- **SERV-01**: Admin can manage services (CRUD with image upload)
- **CLNT-01**: Admin can view all users and deactivate accounts
- **STAT-01**: Admin dashboard shows Chart.js bar and pie charts with real DB data
- **UI-01**: Consistent responsive UI across all pages
- **UI-02**: Live price calculator on reservation form
- **UI-03**: Inline form validation (JS)

### Out of Scope

- Payment gateway integration — defer to v2
- Email notifications — defer to v2
- Multi-language support — out of scope
- Mobile native app — web-first only
- ORM or Composer packages — raw PDO only per spec

## Context

Hotel reservation system with two user roles (guest, admin). PHP backend with MySQL database. No frameworks — raw PDO for database access, vanilla PHP for routing via `require_once` partials. Chart.js for admin statistics. Feature-based file grouping (user/, admin/, rooms/, reservations/).

## Constraints

- **Tech Stack**: PHP (latest stable), MySQL, Chart.js CDN, no Composer packages
- **Database**: Raw PDO only, no ORM
- **Architecture**: Feature-based file grouping, `require_once` for shared partials
- **Error Handling**: Native `error_log()` to `logs/app.log`, shared `log_error()` helper
- **Abstraction**: Only extract when logic is used in 3+ places
- **No micro-files**: Files must contain meaningful logic (except config/partials)

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| No ORM / no router | Keep stack minimal, raw PDO sufficient for scope | — Pending |
| Feature-based grouping | Group by domain not type, mirrors user journeys | — Pending |
| require_once partials | Shared header/footer/db only — KISS | — Pending |
| Session-based auth | Simple, sufficient for v1 | — Pending |
| Chart.js CDN | No build step, widely supported | — Pending |

## Evolution

This document evolves at phase transitions and milestone boundaries.

**After each phase transition** (via `/gsd-transition`):
1. Requirements invalidated? → Move to Out of Scope with reason
2. Requirements validated? → Move to Validated with phase reference
3. New requirements emerged? → Add to Active
4. Decisions to log? → Add to Key Decisions
5. "What This Is" still accurate? → Update if drifted

**After each milestone** (via `/gsd-complete-milestone`):
1. Full review of all sections
2. Core Value check — still the right priority?
3. Audit Out of Scope — reasons still valid?
4. Update Context with current state

---
*Last updated: 2026-05-11 after initialization*

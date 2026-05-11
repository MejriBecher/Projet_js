# PROJECT_MAP — Hotel Reservation System

## TECH_STACK
| Component | Version | Source |
|-----------|---------|--------|
| PHP | 8.5+ | Not installed locally — verify on target |
| MySQL | 8.4 LTS / 9.7 LTS | Not installed locally — verify on target |
| Chart.js | 4.5.1 | CDN: jsDelivr |
| Browser | Modern (ES6+) | |

## SYSTEM_FLOW

### User Journey
1. Register → Login → Browse Rooms → Filter by type/price → Select room → Book with date range → View reservation in profile → Logout

### Admin Journey
2. Login → Dashboard → Manage Rooms (CRUD + images) → Manage Services (CRUD + images) → Manage Reservations (change status) → Manage Clients (deactivate) → View Statistics (Chart.js)

## ARCHITECTURE

```
Projet_js/
├── .planning/
├── .gitignore
├── AGENTS.md
├── PROJECT_MAP.md          # Live state
├── README.md
├── index.php               # Homepage — room listing
├── schema.sql              # Full DB schema (6 tables)
├── seed.sql                # Sample rooms + services
├── setup.php               # Create admin user with bcrypt
├── app/
│   ├── config/
│   │   ├── database.php    # PDO connection (env-configurable)
│   │   └── helpers.php     # log_error, escape, redirect, require_login, require_admin, csrf
│   ├── partials/
│   │   ├── header.php      # Nav, session flash
│   │   └── footer.php      # Scripts, close tags
│   ├── auth/
│   │   ├── register.php    # Registration with validation
│   │   ├── login.php       # Login with password_verify
│   │   └── logout.php      # Session destroy + cookie clear
│   ├── rooms/              # (Phase 2)
│   ├── reservations/       # (Phase 3)
│   ├── user/               # (Phase 3)
│   └── admin/
│       ├── index.php       # Dashboard with stat cards + Chart.js
│       ├── login.php       # Redirects to /app/auth/login.php
│       ├── stats_data.php  # JSON endpoint for dashboard
│       ├── rooms.php       # (Phase 2)
│       ├── services.php    # (Phase 2)
│       ├── reservations.php# (Phase 4)
│       ├── clients.php     # (Phase 4)
│       └── partials/
│           ├── admin_header.php  # Sidebar nav + admin gate
│           └── sidebar.php  # (unused, merged into admin_header)
├── assets/
│   ├── css/
│   │   └── style.css       # Complete responsive stylesheet
│   └── js/
│       ├── main.js         # Flash auto-dismiss
│       ├── filter.js       # (Phase 2)
│       ├── booking.js      # (Phase 3)
│       └── admin-charts.js # (Phase 4)
├── uploads/
│   ├── .htaccess           # Deny PHP execution
│   ├── rooms/              # Room images
│   └── services/           # Service images
└── logs/
    └── app.log             # Error log
```

## PHASE STATUS

| # | Phase | Status | Milestone |
|---|-------|--------|-----------|
| 1 | Foundation & Authentication | ✅ COMPLETE | Register, Login, Logout, Admin gate, Helpers |
| 2 | Room & Service Management | ⬜ Pending | Room listing + filter, admin CRUD + upload |
| 3 | Reservations & User Profile | ⬜ Pending | Booking flow, double-booking guard, profile |
| 4 | Admin Dashboard & Management | ⬜ Pending | Reservation/user mgmt, Chart.js stats |
| 5 | Polish & Cross-cutting | ⬜ Pending | Responsive layout, edge cases |

## ORPHANS & PENDING

| Item | Type | Status |
|------|------|--------|
| PHP/MySQL not on local PATH | env | ⚠️ Verify on deployment |
| app/rooms/ directory | code | ⬜ Phase 2 |
| app/reservations/ directory | code | ⬜ Phase 3 |
| app/user/ directory | code | ⬜ Phase 3 |
| admin rooms.php | code | ⬜ Phase 2 |
| admin services.php | code | ⬜ Phase 2 |
| admin reservations.php | code | ⬜ Phase 4 |
| admin clients.php | code | ⬜ Phase 4 |
| filter.js | code | ⬜ Phase 2 |
| booking.js | code | ⬜ Phase 3 |
| admin-charts.js | code | ⬜ Phase 4 |

## Key Decisions
| Decision | Rationale | Outcome |
|----------|-----------|---------|
| No router/ORM | KISS, raw PDO | ✓ Good |
| Feature-based grouping | Mirrors user journeys | ✓ Good |
| require_once partials | Only header/footer/db shared | ✓ Good |
| Session-based auth | Simple, sufficient for v1 | ✓ Good |
| escape() wrapper | Centralizes XSS prevention | ✓ Good |
| setup.php for admin | Creates bcrypt hash at runtime | ✓ Good |

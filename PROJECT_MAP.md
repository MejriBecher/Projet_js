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
1. Register → Login → Browse Rooms → Filter by type/price → Select room → Book with date range → Choose payment method → View reservation in profile → Logout

### Admin Journey
2. Login → Dashboard → Manage Rooms (CRUD + images) → Manage Services (CRUD + images) → Manage Reservations (change status) → Manage Clients (deactivate) → View Statistics (Chart.js)

## Brand Identity

- **Name:** Azur Cove Hotel
- **Tagline:** *Where the Sea Meets Serenity*
- **Theme:** Coastal & fresh — navy, sky blue, sand, linen white
- **Login:** `admin@azurcove.com` / `admin123`

## Design System

- **CSS Variables:** `--navy`, `--ocean`, `--sky`, `--mist`, `--sand`, `--linen`, `--charcoal` in `assets/css/style.css`
- **Fonts:** Playfair Display (headings) + Inter (body) via Google Fonts CDN
- **Components:** Navy navbar, linen page bg, sand accent, radius variables, card shadow using navy rgba
- **Seed images:** Unsplash URLs for rooms (4/5) and services (3/5), external URLs stored in `image` column

## ARCHITECTURE

```
Projet_js/
├── .gitignore
├── PROJECT_MAP.md          # Project architecture & decisions
├── README.md
├── index.php               # Homepage — room listing (VIP filter + badge)
├── room.php                # Room detail page (id=X, VIP badge for vip rooms)
├── reserve.php             # Booking form → validates dates → stores in session → redirects to payment.php
├── payment.php             # Payment method selection → inserts reservation → redirects to booking_confirmation.php
├── booking_confirmation.php # Post-booking summary with session contract
├── services.php            # Services catalog (public)
├── about.php               # Static about/contact page
├── schema.sql              # Full DB schema (6+ tables, payment_method column)
├── seed.sql                # Sample rooms + services (all with images, 2 VIP rooms)
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

## PENDING

| Item | Type | Status |
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
| app/reservations/create.php | code | 🗑️ Orphan — replaced by reserve.php + payment.php (kept for backward compat) |
| Seed images changed from file-upload to external Unsplash URLs | seed | ✅ Updated in seed.sql |
| Admin login changed from admin@hotel.com to admin@azurcove.com | config | ✅ Updated in setup.php + seed.sql |
| VIP rooms (2) added with gold badge on cards + detail page | seed | ✅ VIP Ocean Suite + VIP Royal Penthouse |
| Image fallback: default Unsplash URL for any room/service without image | views | ✅ Applied in index.php, room.php, services.php |
| Image required on create for admin rooms/services forms | admin | ✅ required attribute + PHP error |
| Payment method page splits reservation flow into two steps | flow | ✅ reserve.php → payment.php → booking_confirmation.php |
| payment_method column added to reservations table | schema | ✅ ENUM('card','cash','transfer') DEFAULT 'cash' |
| Session contract: $_SESSION['pending_reservation'] for multi-step booking | flow | ✅ Documented in [ARCHITECTURE] |
| Card number formatting via main.js | assets | ✅ Auto-spaces every 4 digits on payment page |

## Reservation Flow (Updated)

```
room.php/index.php (Book This Room)
    ↓
reserve.php (form: room select, dates, optional services)
    ↓  POST → validate dates, check availability (FOR UPDATE), store in $_SESSION['pending_reservation']
payment.php (read-only summary from session, choose payment method)
    ↓  POST → INSERT into reservations + reservation_services, set $_SESSION['last_reservation_id']
booking_confirmation.php (summary from DB)
```

### Session Contract: `$_SESSION['pending_reservation']`

```php
[
    'room_id'     => int,
    'room_name'   => string,
    'room_type'   => string,
    'check_in'    => string (Y-m-d),
    'check_out'   => string (Y-m-d),
    'nights'      => int,
    'room_price'  => float,
    'total_price' => float,    // room_price * nights
    'services'    => [         // empty array if none
        ['id' => int, 'name' => string, 'price' => float],
        ...
    ],
]
```

This contract is set in `reserve.php` POST handler and consumed in `payment.php`. It is unset after successful DB insert.

## Key Decisions
| Decision | Rationale | Outcome |
|----------|-----------|---------|
| No router/ORM | KISS, raw PDO | ✓ Good |
| Feature-based grouping | Mirrors user journeys | ✓ Good |
| require_once partials | Only header/footer/db shared | ✓ Good |
| Session-based auth | Simple, sufficient for v1 | ✓ Good |
| escape() wrapper | Centralizes XSS prevention | ✓ Good |
| setup.php for admin | Creates bcrypt hash at runtime | ✓ Good |

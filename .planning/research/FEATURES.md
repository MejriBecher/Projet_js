# Feature Landscape: Hotel Reservation System

**Domain:** PHP/MySQL hotel reservation web application (vanilla stack, no framework)
**Researched:** 2026-05-11
**Overall confidence:** HIGH

## Executive Summary

This document maps the feature landscape for a hotel reservation system based on analysis of industry-standard Property Management Systems (PMS) including Cloudbeds, RoomRaccoon, QloApps, Oracle OPERA Cloud, and booking engines from OnRes, HotelMinder, RoomStay, and Guesty. Features are categorized into three buckets: **table stakes** (must have or users will leave), **differentiators** (competitive advantages for a vanilla-PHP system), and **anti-features** (things to deliberately avoid given the project scope and constraints).

The core booking flow follows a well-established three-phase pattern: **Search & Evaluation** (browse rooms + filter), **Selection** (view details + calculate price), **Checkout** (enter details + confirm). On the admin side, every PMS in the 2025-2026 market provides a dashboard with real-time KPIs (occupancy, revenue, arrivals/departures), reservation management with status workflows, and basic reporting. For a v1 vanilla-PHP system, the key architectural pitfall is **double-booking prevention** — this requires proper `SELECT ... FOR UPDATE` pessimistic locking at the database level, not just application-level checks.

---

## Table Stakes

Features users expect. Missing these = product feels incomplete or untrustworthy.

| # | Feature | Why Expected | Complexity | Notes |
|---|---------|--------------|------------|-------|
| 1 | **Room browse with cards** | Homepage shows rooms with photo, price, capacity, description. This is the entry point for all booking flows. | Low | Needs at minimum: name, photo, capacity (adults/children), price per night, short description |
| 2 | **Availability-based search** | Guest enters check-in date, check-out date, and optionally guest count. System returns only rooms available for that range. | **High** | **Critical: double-booking prevention.** Must use half-open date intervals `[check_in, check_out)` with overlap query + `SELECT ... FOR UPDATE` pessimistic locking on confirm. See PITFALLS.md. |
| 3 | **JS filter: room type + price range** | On listing page, client-side filtering by category and price without page reload. | Low-Medium | Standard JS, no libraries needed. Can enhance with simple range slider. |
| 4 | **Booking flow (3 phases)** | (1) Select dates → (2) Pick room + see live price → (3) Enter guest details + confirm. Each step must re-check availability. | Medium | Industry standard flow from Guesty/Oracle docs. Must re-verify availability at final confirmation step inside a DB transaction. |
| 5 | **Live price calculator** | On the booking form, total price updates in real-time as user changes dates or selects addons. | Low-Medium | `(nights) × (room_rate) + (service_extras)`. JS-only, no backend call needed for base calculation. |
| 6 | **User registration & login** | Name, email, password. Session-based auth. Guest cannot book without logging in. | Low-Medium | Per PROJECT.md: session-based auth, simple and sufficient for v1. |
| 7 | **User profile with booking history** | Logged-in user can see their personal info and past/future reservations with status badges (Confirmed, Checked-in, Cancelled, Completed). | Medium | Also serves as the guest's self-service reference for their bookings. |
| 8 | **Admin: Room CRUD** | Admin can create, edit, delete rooms. Includes image upload, price, capacity, description, amenities. | Medium | Image upload requires validation (type, size) and storage path management. |
| 9 | **Admin: View all reservations** | Single table listing all bookings with guest name, room, dates, status. Ability to change status via dropdown. | Low-Medium | Status workflow: Pending → Confirmed → Checked-in → Checked-out / Cancelled. |
| 10 | **Admin: User management** | Admin can view all registered users and deactivate accounts. | Low | Simple toggle. Helps with spam/bad actor management. |
| 11 | **Inline form validation (JS)** | Real-time validation on all forms: required fields, email format, date range validity (check-out > check-in), password strength. | Low | Enhances UX and reduces server-side error handling burden. |
| 12 | **Consistent responsive UI** | All pages render properly on mobile, tablet, and desktop. Navigation adapts to viewport. | Low-Medium | Per PROJECT.md: consistent responsive UI across all pages. |
| 13 | **Registration form validation** | Email must be unique, password confirmation, required fields checked. | Low | Server-side + client-side validation. |
| 14 | **Admin: Services CRUD** | Admin can create, edit, delete hotel services (e.g., breakfast, spa, parking) with image upload. | Medium | Per PROJECT.md spec. Services can be addons during booking. |

### Overlap Detection (Critical Detail for Table Stakes #2)

The industry-standard approach for checking room availability uses an **anti-join** pattern:

```sql
-- Available rooms of a type for requested [check_in, check_out) range
SELECT r.roomID
FROM Rooms AS r
LEFT JOIN Bookings AS b
  ON b.roomID = r.roomID
 AND NOT (b.check_out <= ? OR b.check_in >= ?)   -- overlap: ranges collide
WHERE r.room_type_id = ?
  AND b.bookingID IS NULL                          -- no overlapping bookings
```

The check-in/check-out dates should be treated as **half-open intervals** `[check_in, check_out)` where check-out is exclusive. Two ranges overlap when `NOT (existing.check_out <= requested.check_in OR existing.check_in >= requested.check_out)`.

---

## Differentiators

Features that add value beyond the baseline. Not expected by users but increase satisfaction / admin utility.

| # | Feature | Value Proposition | Complexity | Notes |
|---|---------|-------------------|------------|-------|
| D1 | **Chart.js admin statistics dashboard** | Visual occupancy bar charts, revenue pie charts, and booking trend line charts sourced from real DB data. | Medium | Per PROJECT.md spec. Must summarize rather than raw-table. 3-4 chart types is sufficient. |
| D2 | **Date-range availability grid** | Visual calendar showing which dates each room is booked/free (like a booking.com-style availability view). | Medium-High | Shows a month grid with available/booked highlighting. Great UX but more complex query logic. Defer if time-constrained. |
| D3 | **Room type classification** | Categorize rooms (Single, Double, Suite, Deluxe) rather than treating each room as independent. Book by type, not by specific room number. | Medium | More realistic than assigning specific room numbers. The system picks an available room within the type at check-in. |
| D4 | **Guest count-based pricing** | Price varies by number of guests (e.g., base rate for 2, extra charge per additional guest). | Low-Medium | Requires `base_price` and `extra_guest_price` fields in room table. |
| D5 | **Booking modification** | Logged-in user can modify their own booking dates (subject to availability) before check-in. | Medium | Complex: must re-check availability for modified dates while preserving original booking under a lock. |
| D6 | **Admin: Status auto-transitions** | System auto-transitions reservation status when dates pass (e.g., Pending → Confirmed on payment, Confirmed → Checked-in on arrival date, Checked-in → Checked-out on departure date). | Low-Medium | Can be done via cron job or on-page-load check. Reduces manual admin work. |
| D7 | **Admin: Date range filtering on reservations** | Filter reservation list by date range, status, room type, guest name. | Low | Simple SQL WHERE + ORDER BY. High usefulness. |
| D8 | **Admin: Booking notes** | Admin can add internal notes to any reservation (e.g., "Guest requested extra towels", "VIP — provide welcome basket"). | Low | Simple text field per reservation, admin-only visibility. |
| D9 | **Export reservations to CSV** | Download reservation list as CSV for external reporting. | Low | Add an "Export" button that generates CSV headers + rows. |
| D10 | **Services as booking addons** | During booking, guest can select optional services (breakfast, airport transfer) which are added to the total price. | Medium | Ties SERVICES-01 into the booking flow. Shows live price updates as services are selected. |
| D11 | **Password change from profile** | User can change their password from their profile page (not just on registration). | Low | Standard security hygiene. Missing this feels incomplete. |
| D12 | **Admin: Quick stats cards** | Dashboard shows KPI cards at top: Today's check-ins, Today's check-outs, Current occupancy %, Revenue MTD. | Medium | Per industry PMS dashboards (Hoteloni, Roomismo, Lodge Easy). High value for admin at-a-glance awareness. |
| D13 | **Cancel booking (user-initiated)** | User can cancel their own booking (with optional cancellation policy check) before check-in date. | Low-Medium | Status transitions to Cancelled. Must check cancellation policy window if implemented. |
| D14 | **Confirmation page + number** | After booking, show a confirmation page with a booking reference number. | Low | Simple UUID or auto-increment ID display. Builds trust. |

---

## Anti-Features

Features to deliberately NOT build in v1. These are outside the project scope, would add disproportionate complexity, or conflict with the tech constraints.

| # | Anti-Feature | Why Avoid | What to Do Instead |
|---|--------------|-----------|-------------------|
| AF1 | **Payment gateway integration** | Per PROJECT.md spec — deferred to v2. No PCI compliance scope needed. Adds huge security surface area. | Use "Book now, pay at hotel" model. Track payment status manually (Pending/Paid) in reservation status. |
| AF2 | **Email notifications** | Per PROJECT.md spec — deferred. Requires SMTP config, email templates, potential deliverability issues. | Show confirmation on-screen. Print-friendly booking summary page. Log notification attempts for later. |
| AF3 | **Multi-language support** | Per PROJECT.md — out of scope. Adds translation management overhead. | English only for v1. Use consistent language in DB seed data. |
| AF4 | **Mobile native app** | Per PROJECT.md — web-first only. Developing + maintaining iOS/Android apps is a full team effort. | Responsive web design covers all screen sizes. Add `manifest.json` for "Add to Home Screen" PWA behavior if desired. |
| AF5 | **ORM / Composer packages** | Per PROJECT.md spec — raw PDO only. No dependency management. | Raw PDO with shared `db.php` connection helper. Parameterized queries prevent injection. |
| AF6 | **Channel manager / OTA sync** | Connecting to Booking.com, Expedia, Airbnb APIs is a massive integration project. Each has different API semantics, rate limits, webhook handling. Each must be re-tested after any schema change. | Direct bookings only for v1. If OTA integration is needed later, add as a separate integration layer after the core is stable. |
| AF7 | **Dynamic pricing / RMS** | Revenue Management Systems that auto-adjust prices based on demand, competitor rates, seasonality. Requires historical data, ML or complex rules engine. | Manual price setting per room type. Admin can update prices directly. |
| AF8 | **AI-powered recommendations** | "Guests who booked this also booked…" style recommendations. Requires data collection, cross-guest analysis. Overkill for a vanilla PHP system. | Static curated "popular rooms" section on homepage. |
| AF9 | **Real-time chat / messaging** | WebSocket or polling-based customer support chat. Requires persistent connection handling not native to PHP's request-response model. | Display hotel contact info (phone, email) prominently. Contact form on site. |
| AF10 | **Multi-property / chain management** | Managing multiple hotels from one dashboard, cross-property availability search, consolidated reporting. | Single-property system. If multi-property is needed, it's a separate product. |
| AF11 | **Social login (OAuth)** | Login via Google, Facebook, etc. Requires OAuth client registration, callback handling, token management. | Standard email/password registration. Simple, no external dependencies. |
| AF12 | **Forgot password / reset flow** | Email-based password reset. Requires email sending (deferred per AF2). | For v1: admin can reset password for users. Self-service reset comes with email integration in v2. |
| AF13 | **Review / rating system** | Guest reviews on rooms after checkout. Requires moderation UI, spam prevention, and potential reputation management. | Gather feedback via contact form. Consider a simple star rating per booking in a later version. |
| AF14 | **Promo codes / discount coupons** | Percentage or fixed-amount discount codes with validity dates, usage limits, stacking rules. | Admin can manually adjust room price. Coupon module adds complexity disproportionate to value for v1. |

---

## Feature Dependencies

```
ROOM-01 (browse) ─────────────────────────────────────────────── (foundation)
    │
    ├── ROOM-02 (filters) ── requires ROOM-01
    │
    ├── ROOM-03 (admin CRUD) ── requires ROOM-01 (DB table)
    │
    └── RSRV-01 (book) ── requires ROOM-01 AND AUTH-01/AUTH-02
            │
            ├── PROF-01 (history) ── requires RSRV-01
            │
            ├── STAT-01 (charts) ── requires RSRV-01 AND ROOM-01 AND CLNT-01
            │
            └── RSRV-02 (admin manage) ── requires RSRV-01
                    │
                    └── D6 (auto-status) ── requires RSRV-02
                    
SERV-01 (admin services CRUD) ───────────────── (independent, can be parallel)
    │
    └── D10 (services as addons) ── requires SERV-01 AND RSRV-01
```

### Dependency Reasoning

- **ROOM-01** must come first: every other feature depends on rooms existing in the database.
- **AUTH-01/AUTH-02** must precede RSRV-01: authenticated users are required for booking per spec.
- **RSRV-01** is the transactional heart of the system and the highest-risk feature (double-booking). It must be built with proper pessimistic locking from day one — retrofitting concurrency protection is very difficult.
- **STAT-01 (charts)** depends on RSRV-01 and CLNT-01 because charts draw from both booking data and user demographic data.
- **SERV-01** is independent and can be built in parallel with ROOM-03.

---

## MVP Recommendation

### Phase 1: Foundation (All Table Stakes + D1)
Prioritize **table stakes 1–14** plus **D1 (Chart.js admin dashboard)** in this order:

1. **ROOM-01 + ROOM-03**: Room database schema, browse page, and admin CRUD with image upload. This is the data foundation. Without rooms, nothing else works.
2. **AUTH-01/02/03**: User registration, login, logout. Guests must be authenticated to book.
3. **SERV-01**: Admin services CRUD (can be parallel with AUTH).
4. **RSRV-01**: Core booking flow with double-booking prevention. **HIGHEST RISK** — build with `SELECT ... FOR UPDATE` from the start.
5. **ROOM-02**: JS filters on room listing page (can be done after browse page exists).
6. **RSRV-02**: Admin reservation management (view all, status change dropdown).
7. **CLNT-01**: Admin user management (view, deactivate).
8. **PROF-01**: User profile with booking history and status badges.
9. **STAT-01**: Chart.js admin dashboard (bar + pie charts from real data). This is the capstone admin feature.
10. **UI-01/02/03**: Responsive design, live price calculator, inline validation. These are cross-cutting and should be woven into all pages.

### Defer to v2
- Email notifications (AF2)
- Payment gateway (AF1)
- Forgot password (AF12)
- Promo codes (AF14)
- Reviews (AF13)

---

## Sources

- Cloudbeds PMS feature list (HotelTech Review, 2026) — HIGH confidence
- RoomRaccoon all-in-one PMS feature set (HotelTech Review, 2026) — HIGH confidence
- QloApps Stats Management documentation — MEDIUM confidence (dated 2016, but patterns are standard)
- Oracle OPERA Cloud reservation flow docs (2025-2026) — HIGH confidence (industry gold standard)
- Guesty Booking Engine API docs — HIGH confidence (booking flow patterns)
- HotelMinder "Top 10 Hotel Booking Engine Features" (2026) — HIGH confidence
- OnRes Software "Essential Features for Hotel Booking Engines" (2024) — MEDIUM confidence
- RoomStay "Hotel Booking Engine Explained" — HIGH confidence
- "Race Conditions in Hotel Booking Systems" (amitavroy.com, 2026) — HIGH confidence
- "Concurrent Room Booking" (Medium, 2025) — MEDIUM confidence
- Hotel PMS Systems comparison (Hospitality Net, 2025-2026) — HIGH confidence
- RevPARGenius "Hotel Tech Stack 2026" — HIGH confidence
- Anand Systems "Must have features of a modern PMS" (2025) — MEDIUM confidence
- Stack Overflow: PHP/MySQL availability check discussions — MEDIUM confidence (community knowledge)

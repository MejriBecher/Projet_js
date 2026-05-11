# Requirements: Hotel Reservation System

**Defined:** 2026-05-11
**Core Value:** Guests can find, book, and manage hotel room reservations; admins manage the entire operation from a dashboard.

## v1 Requirements

### Authentication

- [ ] **AUTH-01**: User can register with name, email, and password
- [ ] **AUTH-02**: User can log in — session persists across pages
- [ ] **AUTH-03**: User can log out — session destroyed
- [ ] **AUTH-04**: Admin can log in to admin dashboard

### Rooms (Public)

- [ ] **ROOM-01**: Homepage shows room cards with photo, price, capacity, description
- [ ] **ROOM-02**: Room listing supports JS filter by type and price range

### Reservations

- [ ] **RSRV-01**: Logged-in user can book a room with check-in/check-out dates
- [ ] **RSRV-02**: Double-booking prevention via PDO transaction + SELECT FOR UPDATE
- [ ] **RSRV-03**: Reservation status lifecycle: Pending → Confirmed → Checked-in → Checked-out / Cancelled

### User Account

- [ ] **PROF-01**: User can view their profile with reservation history and status badges

### Admin: Rooms

- [ ] **ADMN-ROOM-01**: Admin can create, edit, delete rooms
- [ ] **ADMN-ROOM-02**: Admin can upload room images with MIME validation + UUID renaming

### Admin: Services

- [ ] **ADMN-SERV-01**: Admin can create, edit, delete services
- [ ] **ADMN-SERV-02**: Admin can upload service images

### Admin: Reservations

- [ ] **ADMN-RSRV-01**: Admin can view all reservations
- [ ] **ADMN-RSRV-02**: Admin can change reservation status via dropdown

### Admin: Clients

- [ ] **ADMN-CLNT-01**: Admin can view all registered users
- [ ] **ADMN-CLNT-02**: Admin can deactivate a user account

### Admin: Statistics

- [ ] **ADMN-STAT-01**: Admin dashboard shows Chart.js bar chart (monthly revenue)
- [ ] **ADMN-STAT-02**: Admin dashboard shows Chart.js pie chart (reservation status distribution)
- [ ] **ADMN-STAT-03**: Admin dashboard shows Chart.js doughnut chart (room type occupancy)

### UI/UX

- [ ] **UI-01**: Consistent responsive layout across all pages
- [ ] **UI-02**: Live price calculator on reservation form (JS)
- [ ] **UI-03**: Inline form validation (JS)
- [ ] **UI-04**: All admin pages protected by `require_admin()` gate
- [ ] **UI-05**: All user-facing output escaped with `htmlspecialchars()`

## v2 Requirements

Deferred to future release:

- **NOTF-01**: Email notification on reservation confirmation
- **NOTF-02**: Email notification on status change
- **PMNT-01**: Payment gateway integration
- **AUTH-05**: OAuth login (Google, GitHub)
- **I18N-01**: Multi-language support

## Out of Scope

| Feature | Reason |
|---------|--------|
| ORM / Composer packages | Raw PDO only per spec |
| Router framework | require_once partials sufficient |
| Real-time chat | Not core to hotel reservations |
| Mobile native app | Web-first; responsive design covers mobile |
| Payment gateway | Defer to v2 |
| Email notifications | Defer to v2 |
| Multi-language | Out of scope |

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| AUTH-01 | Phase 1 | Pending |
| AUTH-02 | Phase 1 | Pending |
| AUTH-03 | Phase 1 | Pending |
| AUTH-04 | Phase 1 | Pending |
| ROOM-01 | Phase 2 | Pending |
| ROOM-02 | Phase 2 | Pending |
| RSRV-01 | Phase 3 | Pending |
| RSRV-02 | Phase 3 | Pending |
| RSRV-03 | Phase 3 | Pending |
| PROF-01 | Phase 3 | Pending |
| ADMN-ROOM-01 | Phase 2 | Pending |
| ADMN-ROOM-02 | Phase 2 | Pending |
| ADMN-SERV-01 | Phase 2 | Pending |
| ADMN-SERV-02 | Phase 2 | Pending |
| ADMN-RSRV-01 | Phase 4 | Pending |
| ADMN-RSRV-02 | Phase 4 | Pending |
| ADMN-CLNT-01 | Phase 4 | Pending |
| ADMN-CLNT-02 | Phase 4 | Pending |
| ADMN-STAT-01 | Phase 4 | Pending |
| ADMN-STAT-02 | Phase 4 | Pending |
| ADMN-STAT-03 | Phase 4 | Pending |
| UI-01 | Phase 5 | Pending |
| UI-02 | Phase 3 | Pending |
| UI-03 | Phase 3 | Pending |
| UI-04 | Phase 1 | Pending |
| UI-05 | Phase 1 | Pending |

**Coverage:**
- v1 requirements: 26 total
- Mapped to phases: 26
- Unmapped: 0 ✓

---
*Requirements defined: 2026-05-11*
*Last updated: 2026-05-11 after initial definition*

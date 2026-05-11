# Roadmap: Hotel Reservation System

## Overview

A five-phase build of a PHP/MySQL hotel reservation web application. Starting from database foundation and authentication, progressing through room/service management, the transactional booking core, the admin dashboard with Chart.js analytics, and finishing with responsive polish. Each phase builds on the previous, delivering a coherent, verifiable capability for either guests or administrators.

## Phases

- [ ] **Phase 1: Foundation & Authentication** - Database setup, shared helpers, user registration, login/logout, admin gate
- [ ] **Phase 2: Room & Service Management** - Homepage room cards, JS filtering, admin CRUD for rooms and services with image upload
- [ ] **Phase 3: Reservations & User Profile** - Booking flow with double-booking prevention, status lifecycle, user profile with reservation history
- [ ] **Phase 4: Admin Dashboard & Management** - Reservation management, user management, Chart.js statistics dashboard
- [ ] **Phase 5: Polish & Cross-cutting** - Responsive layout refinement, edge case hardening, final review

## Phase Details

### Phase 1: Foundation & Authentication
**Goal**: System is operational and users can securely access their accounts
**Mode**: mvp
**Depends on**: Nothing (first phase)
**Requirements**: AUTH-01, AUTH-02, AUTH-03, AUTH-04, UI-04, UI-05
**Success Criteria** (what must be TRUE):
  1. User can register with name, email, and password
  2. User can log in with email/password and session persists across pages
  3. User can log out and session is completely destroyed
  4. Admin user can access admin dashboard; non-admin users are blocked
  5. All user-facing output is safely escaped via a global helper function
**Plans**: TBD
**UI hint**: yes

### Phase 2: Room & Service Management
**Goal**: Guests can browse rooms and admins can manage rooms and services
**Mode**: mvp
**Depends on**: Phase 1
**Requirements**: ROOM-01, ROOM-02, ADMN-ROOM-01, ADMN-ROOM-02, ADMN-SERV-01, ADMN-SERV-02
**Success Criteria** (what must be TRUE):
  1. Homepage displays room cards with photo, price, capacity, and description
  2. Guest can filter rooms by type and price range using JS without page reload
  3. Admin can create, edit, and delete rooms with secure image upload (MIME validation + UUID renaming)
  4. Admin can create, edit, and delete services with secure image upload
**Plans**: TBD
**UI hint**: yes

### Phase 3: Reservations & User Profile
**Goal**: Logged-in users can book rooms and view their reservation history
**Mode**: mvp
**Depends on**: Phase 2
**Requirements**: RSRV-01, RSRV-02, RSRV-03, PROF-01, UI-02, UI-03
**Success Criteria** (what must be TRUE):
  1. Logged-in user can book a room with check-in/check-out date selection
  2. Double-booking is prevented — two concurrent users cannot reserve the same room for overlapping dates
  3. Reservation status follows lifecycle: Pending → Confirmed → Checked-in → Checked-out / Cancelled
  4. User can view their profile with reservation history and status badges
  5. Live price calculator updates total cost on the reservation form as selections change
  6. Inline form validation provides instant feedback on all forms
**Plans**: TBD
**UI hint**: yes

### Phase 4: Admin Dashboard & Management
**Goal**: Admins can manage reservations, users, and view statistical charts
**Mode**: mvp
**Depends on**: Phase 3
**Requirements**: ADMN-RSRV-01, ADMN-RSRV-02, ADMN-CLNT-01, ADMN-CLNT-02, ADMN-STAT-01, ADMN-STAT-02, ADMN-STAT-03
**Success Criteria** (what must be TRUE):
  1. Admin can view all reservations in a single management page
  2. Admin can change reservation status via dropdown
  3. Admin can view all registered users
  4. Admin can deactivate a user account
  5. Admin dashboard shows Chart.js bar chart (monthly revenue), pie chart (reservation status distribution), and doughnut chart (room type occupancy)
**Plans**: TBD
**UI hint**: yes

### Phase 5: Polish & Cross-cutting
**Goal**: The entire application has a polished, consistent, responsive user experience
**Mode**: mvp
**Depends on**: Phase 4
**Requirements**: UI-01
**Success Criteria** (what must be TRUE):
  1. All pages share a consistent, responsive layout that works on desktop and mobile
  2. Navigation is intuitive across all guest and admin pages
  3. Edge cases handled across the app (empty states, error messages, 404s)
**Plans**: TBD
**UI hint**: yes

## Progress

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Foundation & Authentication | 0/0 | Not started | - |
| 2. Room & Service Management | 0/0 | Not started | - |
| 3. Reservations & User Profile | 0/0 | Not started | - |
| 4. Admin Dashboard & Management | 0/0 | Not started | - |
| 5. Polish & Cross-cutting | 0/0 | Not started | - |

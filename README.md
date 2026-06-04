<<<<<<< HEAD
# Mapawa Baptist Youth Ministry — Website

## Stack
- **Frontend**: HTML5, CSS3, Vanilla JS
- **Backend**: PHP 8.0+
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Server**: Apache (with `mod_rewrite`)

---

## Quick Setup

### 1. Requirements
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.3+
- Apache with `mod_rewrite` enabled
- PHP extensions: `pdo_mysql`, `fileinfo`, `gd`

### 2. Database
```sql
-- In your MySQL client:
SOURCE /path/to/mapawa-baptist-youth/database.sql;
```
This creates the `mapawa_youth` database with all tables and seeds
the 4 sermon stubs and default events.

### 3. Configuration
Edit `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'mapawa_youth');
define('DB_USER', 'your_mysql_user');
define('DB_PASS', 'your_mysql_password');
define('SITE_URL', 'http://yourdomain.com/mapawa-baptist-youth');
```

### 4. Upload directory permissions
```bash
chmod -R 755 assets/uploads/
# Or create if missing:
mkdir -p assets/uploads/{audio,video,pdf,gallery,thumbnails,events}
```

### 5. Apache VirtualHost (optional)
```apache
<VirtualHost *:80>
  ServerName yourdomain.com
  DocumentRoot /var/www/mapawa-baptist-youth
  <Directory /var/www/mapawa-baptist-youth>
    AllowOverride All
    Require all granted
  </Directory>
</VirtualHost>
```

---

## Admin Login

```
URL:      http://yourdomain.com/mapawa-baptist-youth/admin/login.php
Username: admin
Password: MBC@Admin2025!
```

**⚠️ Change this password immediately after first login via Admin → Settings.**

---

## Admin Features

| Page | What it does |
|------|-------------|
| Dashboard | Stats overview, recent activity |
| Upload Sermon | Upload audio/video/PDF + metadata |
| Manage Sermons | Edit, publish, archive, delete sermons |
| Events | Create/edit/delete events |
| Registrations | View & export event sign-ups as CSV |
| Prayer Requests | Read, annotate, delete prayer submissions |
| Messages | Inbox for contact form, reply via email |
| Gallery | Upload, caption, hide/show photos |
| Settings | Change password, manage admin users, session log |

---

## Public API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `api/data.php?resource=sermons` | GET | Fetch published sermons |
| `api/data.php?resource=events` | GET | Fetch upcoming events |
| `api/data.php?resource=gallery` | GET | Fetch visible gallery photos |
| `api/submit-prayer.php` | POST | Submit a prayer request |
| `api/submit-contact.php` | POST | Submit a contact message |
| `api/register-event.php` | POST | Register for an event |

---

## File Structure

```
mapawa-baptist-youth/
├── .htaccess              ← Apache config (security, MIME, caching)
├── database.sql           ← Full DB schema + seed data
├── index.html             ← Home page
├── about.html
├── services.html          ← Sermon library
├── events.html
├── gallery.html
├── prayers.html           ← Prayer request form (posts to API)
├── contact.html           ← Contact form (posts to API)
│
├── services/              ← Individual sermon pages
│   ├── service-001-foundation-of-the-youth-ministry.html
│   ├── service-002.html
│   ├── service-003.html
│   └── service-004.html
│
├── admin/                 ← Protected admin panel
│   ├── login.php
│   ├── logout.php
│   ├── index.php          ← Dashboard
│   ├── upload-sermon.php
│   ├── sermons.php
│   ├── events.php
│   ├── registrations.php
│   ├── prayers.php
│   ├── messages.php
│   ├── gallery.php
│   └── settings.php
│
├── api/                   ← JSON API endpoints
│   ├── data.php
│   ├── submit-prayer.php
│   ├── submit-contact.php
│   └── register-event.php
│
├── includes/              ← PHP backend (not web-accessible)
│   ├── config.php
│   ├── db.php
│   ├── auth.php
│   ├── helpers.php
│   └── admin_layout.php
│
└── assets/
    ├── css/
    │   ├── main.css
    │   └── admin.css
    ├── js/
    │   ├── main.js
    │   └── admin.js
    ├── images/
    │   └── logo.svg
    └── uploads/           ← Auto-created on first upload
        ├── audio/
        ├── video/
        ├── pdf/
        ├── gallery/
        ├── thumbnails/
        └── events/
```

---

## Security Notes

- All admin pages check `is_admin_logged_in()` — redirect to login if not
- CSRF tokens on every form
- Passwords hashed with `bcrypt` (cost 12)
- Rate limiting on login (5/5min), prayer (5/hr), contact (3/hr)
- File uploads: extension whitelist + MIME type verification for images
- PHP execution blocked in `assets/uploads/` via `.htaccess`
- Sessions use `HttpOnly`, `SameSite=Strict` cookies
- Session timeout: 4 hours (configurable in `config.php`)

---

## Customisation

- **Admin password**: Admin → Settings → Change Password
- **Site URL**: `includes/config.php` → `SITE_URL`
- **Upload limits**: `includes/config.php` → `MAX_UPLOAD_MB`
- **Session timeout**: `includes/config.php` → `SESSION_TIMEOUT`
- **Colours / fonts**: `assets/css/main.css` CSS variables at `:root`
=======
<<<<<<< HEAD
# Mapawa Baptist Youth Ministry — Website

## Stack
- **Frontend**: HTML5, CSS3, Vanilla JS
- **Backend**: PHP 8.0+
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Server**: Apache (with `mod_rewrite`)

---

## Quick Setup

### 1. Requirements
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.3+
- Apache with `mod_rewrite` enabled
- PHP extensions: `pdo_mysql`, `fileinfo`, `gd`

### 2. Database
```sql
-- In your MySQL client:
SOURCE /path/to/mapawa-baptist-youth/database.sql;
```
This creates the `mapawa_youth` database with all tables and seeds
the 4 sermon stubs and default events.

### 3. Configuration
Edit `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'mapawa_youth');
define('DB_USER', 'your_mysql_user');
define('DB_PASS', 'your_mysql_password');
define('SITE_URL', 'http://yourdomain.com/mapawa-baptist-youth');
```

### 4. Upload directory permissions
```bash
chmod -R 755 assets/uploads/
# Or create if missing:
mkdir -p assets/uploads/{audio,video,pdf,gallery,thumbnails,events}
```

### 5. Apache VirtualHost (optional)
```apache
<VirtualHost *:80>
  ServerName yourdomain.com
  DocumentRoot /var/www/mapawa-baptist-youth
  <Directory /var/www/mapawa-baptist-youth>
    AllowOverride All
    Require all granted
  </Directory>
</VirtualHost>
```

---

## Admin Login

```
URL:      http://yourdomain.com/mapawa-baptist-youth/admin/login.php
Username: admin
Password: MBC@Admin2025!
```

**⚠️ Change this password immediately after first login via Admin → Settings.**

---

## Admin Features

| Page | What it does |
|------|-------------|
| Dashboard | Stats overview, recent activity |
| Upload Sermon | Upload audio/video/PDF + metadata |
| Manage Sermons | Edit, publish, archive, delete sermons |
| Events | Create/edit/delete events |
| Registrations | View & export event sign-ups as CSV |
| Prayer Requests | Read, annotate, delete prayer submissions |
| Messages | Inbox for contact form, reply via email |
| Gallery | Upload, caption, hide/show photos |
| Settings | Change password, manage admin users, session log |

---

## Public API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `api/data.php?resource=sermons` | GET | Fetch published sermons |
| `api/data.php?resource=events` | GET | Fetch upcoming events |
| `api/data.php?resource=gallery` | GET | Fetch visible gallery photos |
| `api/submit-prayer.php` | POST | Submit a prayer request |
| `api/submit-contact.php` | POST | Submit a contact message |
| `api/register-event.php` | POST | Register for an event |

---

## File Structure

```
mapawa-baptist-youth/
├── .htaccess              ← Apache config (security, MIME, caching)
├── database.sql           ← Full DB schema + seed data
├── index.html             ← Home page
├── about.html
├── services.html          ← Sermon library
├── events.html
├── gallery.html
├── prayers.html           ← Prayer request form (posts to API)
├── contact.html           ← Contact form (posts to API)
│
├── services/              ← Individual sermon pages
│   ├── service-001-foundation-of-the-youth-ministry.html
│   ├── service-002.html
│   ├── service-003.html
│   └── service-004.html
│
├── admin/                 ← Protected admin panel
│   ├── login.php
│   ├── logout.php
│   ├── index.php          ← Dashboard
│   ├── upload-sermon.php
│   ├── sermons.php
│   ├── events.php
│   ├── registrations.php
│   ├── prayers.php
│   ├── messages.php
│   ├── gallery.php
│   └── settings.php
│
├── api/                   ← JSON API endpoints
│   ├── data.php
│   ├── submit-prayer.php
│   ├── submit-contact.php
│   └── register-event.php
│
├── includes/              ← PHP backend (not web-accessible)
│   ├── config.php
│   ├── db.php
│   ├── auth.php
│   ├── helpers.php
│   └── admin_layout.php
│
└── assets/
    ├── css/
    │   ├── main.css
    │   └── admin.css
    ├── js/
    │   ├── main.js
    │   └── admin.js
    ├── images/
    │   └── logo.svg
    └── uploads/           ← Auto-created on first upload
        ├── audio/
        ├── video/
        ├── pdf/
        ├── gallery/
        ├── thumbnails/
        └── events/
```

---

## Security Notes

- All admin pages check `is_admin_logged_in()` — redirect to login if not
- CSRF tokens on every form
- Passwords hashed with `bcrypt` (cost 12)
- Rate limiting on login (5/5min), prayer (5/hr), contact (3/hr)
- File uploads: extension whitelist + MIME type verification for images
- PHP execution blocked in `assets/uploads/` via `.htaccess`
- Sessions use `HttpOnly`, `SameSite=Strict` cookies
- Session timeout: 4 hours (configurable in `config.php`)

---

## Customisation

- **Admin password**: Admin → Settings → Change Password
- **Site URL**: `includes/config.php` → `SITE_URL`
- **Upload limits**: `includes/config.php` → `MAX_UPLOAD_MB`
- **Session timeout**: `includes/config.php` → `SESSION_TIMEOUT`
- **Colours / fonts**: `assets/css/main.css` CSS variables at `:root`
=======
# Mapawa-Baptist-Youth
Mapawa Baptist Youth web app 
>>>>>>> f7e1b664b7cd8d9bfdd626d74a0f433c047743a4
>>>>>>> 310179d017a02a2e6efd72b43d0ae2cc00e11cd9

# 🌸 Raethingz Handicrafts — v2 Full-Stack Website

A professional, full-stack upgrade of the Raethingz Handicrafts website.
Built with **PHP 8+**, **MySQL**, **Vanilla JS**, and **CSS3**.

---

## 📁 Folder Structure

```
raethingz/
│
├── assets/
│   ├── css/
│   │   └── styles.css          ← All styles (CSS variables, responsive, animations)
│   ├── js/
│   │   └── script.js           ← All JavaScript (modular, per-page init functions)
│   └── images/                 ← Product images, logo
│
├── includes/
│   ├── config.php              ← App constants (DB credentials, paths)
│   ├── db.php                  ← Singleton PDO connection
│   ├── auth.php                ← Session management, CSRF, login helpers
│   ├── helpers.php             ← Sanitization, validation, jsonResponse()
│   ├── navbar.php              ← Shared navigation component
│   └── footer.php             ← Shared footer + cart sidebar + checkout modal
│
├── database/
│   └── schema.sql              ← Full DB schema + sample data (run once)
│
├── pages/
│   ├── products.php            ← Products listing page
│   ├── customization.php       ← Custom order request page
│   ├── login.php               ← Login page
│   ├── register.php            ← Registration page
│   ├── account.php             ← User dashboard (orders + custom requests)
│   ├── login_handler.php       ← POST: handles login (JSON API)
│   ├── register_handler.php    ← POST: handles registration (JSON API)
│   ├── fetch_products.php      ← GET: returns products JSON from DB
│   ├── place_order.php         ← POST: saves order + items to DB (transaction)
│   ├── submit_custom.php       ← POST: saves customization request to DB
│   └── logout.php              ← Destroys session, redirects to home
│
├── uploads/                    ← User-uploaded inspiration photos (gitignored)
│
├── index.php                   ← Home page
└── README.md                   ← This file
```

---

## ⚙️ Setup Instructions

### 1. Requirements
- PHP 8.0 or higher
- MySQL 8.0 or MariaDB 10.5+
- A local server: [XAMPP](https://www.apachefriends.org/), [Laragon](https://laragon.org/), or [Herd](https://herd.laravel.com/)

### 2. Database Setup
Open **phpMyAdmin** (or any MySQL client) and run:
```sql
source /path/to/raethingz/database/schema.sql
```
This creates the `raethingz` database with all tables and sample data.

### 3. Configure Credentials
Open `includes/config.php` and update:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'raethingz');
define('DB_USER', 'your_username');   // ← Change this
define('DB_PASS', 'your_password');   // ← Change this
define('APP_URL', 'http://localhost/raethingz');
```

### 4. Place Project
Copy the `raethingz/` folder into your web server's root:
- XAMPP: `C:/xampp/htdocs/raethingz/`
- Laragon: `C:/laragon/www/raethingz/`

### 5. Visit in Browser
```
http://localhost/raethingz/
```

### 6. Admin Login (default)
| Email | Password |
|---|---|
| admin@raethingz.com | Admin@1234 |

> ⚠️ **Change this password immediately in production!**

---

## 🔒 Security Features

| Feature | Implementation |
|---|---|
| SQL Injection Prevention | PDO prepared statements throughout |
| Password Security | `password_hash()` with BCRYPT cost 12 |
| CSRF Protection | Token in session, verified on every POST |
| Session Fixation | `session_regenerate_id(true)` on login |
| XSS Prevention | `htmlspecialchars()` on all output, `escapeHTML()` in JS |
| File Upload Safety | `finfo` MIME detection, random filenames |
| Secure Cookies | `httponly`, `samesite=Lax`, `secure` on HTTPS |
| Error Leaking | Debug mode toggle in `config.php` |
| Input Sanitization | `sanitizeString()`, `sanitizeEmail()`, `sanitizeInt()` in `helpers.php` |

---

## 🗄️ Database Tables

| Table | Purpose |
|---|---|
| `users` | Customer and admin accounts |
| `categories` | Product categories (Flower, Bouquet, Keychain, Hairclip) |
| `products` | Product listings with price, image, badge |
| `stock` | Inventory quantities per product |
| `orders` | Customer orders with status tracking |
| `order_items` | Line items with price-at-time snapshot |
| `custom_requests` | Custom order requests from the customization form |

---

## 🚀 Key Improvements Over v1

### HTML / Structure
- ❌ **Before:** Static `.html` files, navbar/footer copy-pasted into every file
- ✅ **After:** Dynamic `.php` pages, shared `navbar.php` and `footer.php` includes

### CSS
- ❌ **Before:** Hardcoded hex values, inconsistent spacing, no custom properties
- ✅ **After:** Full CSS variable system, mobile-first responsive, smooth animations

### JavaScript
- ❌ **Before:** Products hardcoded in JS array; passwords saved to `localStorage` (critical security issue)
- ✅ **After:** Products fetched from DB API; no sensitive data in storage; modular init functions

### Backend
- ❌ **Before:** No backend — purely static frontend
- ✅ **After:** Full PHP 8 backend with PDO, prepared statements, CSRF, session management

### Database
- ❌ **Before:** Existing SQL had no relationships or foreign keys
- ✅ **After:** Normalised schema with FK constraints, transactions, and stock tracking

---

## 📝 Adding Products

1. Open phpMyAdmin → `raethingz` database
2. Insert into `products` table with the correct `category_id`
3. Add a matching row in `stock` with the initial quantity
4. Place the product image in `assets/images/`

Or add an admin panel later using the existing `account.php` pattern.

---

## 🛠️ Production Checklist

- [ ] Set `DEBUG_MODE = false` in `config.php`
- [ ] Use environment variables for DB credentials (not hardcoded)
- [ ] Enable HTTPS (update `APP_URL` and cookie `secure` flag)
- [ ] Change default admin password
- [ ] Set `uploads/` folder permissions to `755`
- [ ] Add `.htaccess` to restrict direct access to `includes/` and `database/`
- [ ] Configure proper error logging (`error_log` path)

---

*Built with ❤️ for Raethingz Handicrafts, Valenzuela City, Philippines.*

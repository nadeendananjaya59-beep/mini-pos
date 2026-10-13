# Mini-POS & Inventory System

A small point-of-sale and inventory web application built with **Pure PHP (PDO)**, **MySQL**, **Bootstrap 5** and **Vanilla JavaScript**.

## Features

| Module | What it does |
|---|---|
| Authentication | Admin / Staff roles, `password_hash` (bcrypt), `session_regenerate_id`, 15-minute inactivity timeout, CSRF protection |
| Inventory (Admin) | Product CRUD, category CRUD, low-stock highlighting and alerts |
| Sales / POS | Live product search (AJAX), cart, real-time Subtotal / Discount / Net calculation, transactional checkout that deducts stock, printable receipt |
| Dashboard & Reports | Cards: Total Products, Low Stock Alerts, Today's Sales; date-range sales report (printable) |

## Requirements

- XAMPP or WAMP (PHP 8.0+ and MySQL / MariaDB)
- A web browser with internet access (Bootstrap is loaded from a CDN)

## Installation

1. **Copy the project** into your web root so the folder is named `mini-pos`:
   - XAMPP: `C:\xampp\htdocs\mini-pos`
   - WAMP: `C:\wamp64\www\mini-pos`
2. **Start Apache and MySQL** from the XAMPP / WAMP control panel.
3. **Import the database**:
   - Open `http://localhost/phpmyadmin`
   - Click the **Import** tab, choose `database/mini_pos.sql`, click **Go**.
   - The script creates the `mini_pos` database, all tables and sample data.
4. **Check the configuration** in `config/db.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'mini_pos');
   define('DB_USER', 'root');
   define('DB_PASS', '');        // XAMPP default: empty
   define('BASE_URL', '/mini-pos');   // change if you used another folder name
   ```
5. **Open the app**: `http://localhost/mini-pos/`

## Default Login Credentials

| Role  | Username | Password   |
|-------|----------|------------|
| Admin | `admin`  | `admin123` |
| Staff | `staff`  | `staff123` |

> Change these passwords before using the system for anything real.

## Role Permissions

| Page | Admin | Staff |
|---|:---:|:---:|
| Dashboard | ✔ | ✔ |
| Sales / POS | ✔ | ✔ |
| Sales Report | ✔ | ✔ |
| Products & Categories | ✔ | ✘ (blocked, redirected with an error) |

## Project Structure

```
mini-pos/
├── index.php              Login page
├── logout.php             Destroys the session
├── dashboard.php          Metric cards + low stock list
├── config/db.php          PDO connection and settings
├── includes/
│   ├── auth.php           Session, timeout, role checks, CSRF, helpers
│   ├── header.php         Navbar (changes by role)
│   └── footer.php
├── admin/
│   ├── products.php       Product CRUD + low stock alerts
│   └── categories.php     Category CRUD
├── pos/
│   ├── index.php          Sales screen
│   ├── search.php         AJAX product search (JSON)
│   ├── checkout.php       Transactional checkout (JSON)
│   └── receipt.php        Printable receipt
├── reports/sales_report.php
├── assets/
│   ├── css/style.css
│   └── js/pos.js          Cart + real-time calculations
└── database/mini_pos.sql  Schema + seed data
```

## Database Design

```
categories 1 ──< products 1 ──< sale_items >── 1 sales >── 1 users
```

- `users` (id, name, username UNIQUE, password hash, role)
- `categories` (id, name UNIQUE)
- `products` (id, category_id FK, name, sku UNIQUE, price, quantity, low_stock_threshold)
- `sales` (id, user_id FK, subtotal, discount, net_total, created_at)
- `sale_items` (id, sale_id FK, product_id FK, qty, unit_price, line_total)

`unit_price` is stored on each sale line, so old receipts stay correct even if a product price changes later.

## How Checkout Works (key logic)

1. The browser sends only product IDs, quantities and the discount to `pos/checkout.php`.
2. The server starts a **transaction** and locks the product rows (`SELECT ... FOR UPDATE`).
3. Prices and stock are read from the database (the browser's prices are never trusted).
4. The sale and its lines are inserted, then stock is reduced with
   `UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?`.
5. If anything fails (not enough stock, etc.) the transaction is **rolled back** and nothing is saved.

## Security Measures

- Passwords hashed with `password_hash()` and checked with `password_verify()`
- PDO prepared statements everywhere (SQL injection)
- Output escaped with `htmlspecialchars()` (XSS)
- CSRF token on all forms and on the checkout request
- Session ID regenerated on login, session timeout after 15 minutes of inactivity
- Server-side role checks on every admin page (not just hidden menu items)

## Suggested Demo Flow (5-10 minutes)

1. Log in as **admin** and show the dashboard cards.
2. Add / edit a product and show the **low stock** highlighting.
3. Log out, log in as **staff**, try opening `/admin/products.php` (access denied).
4. Make a sale: search, add items, change quantity, apply a discount, complete the sale and show the receipt.
5. Return to the product list to show the stock was deducted.
6. Open the dashboard (today's sales updated) and run the date-range report.
7. Briefly explain `pos/checkout.php` (transaction), `includes/auth.php` (roles / timeout) and the password hashing in `index.php`.

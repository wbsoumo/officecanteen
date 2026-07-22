# Office Food Ordering System (OfficeCanteen Console)

A production-quality corporate canteen food ordering platform. Employees can browse Indian menus, manage checkout details, submit cash-on-delivery orders, and track order stages (Received → Confirmed → Preparing → Ready → Out for Delivery → Delivered) on a timeline updating every 10 seconds. Admins and chefs can manage order queues, cook meals, control inventory, edit catalog CRUD details, and pull sales reports.

---

## 🚀 Features

### Employee Portal
* **Visual Dashboard**: Shows wallet balance, today's order stats, live tracking status cards, and last 3 order logs.
* **Menu Grid & Searching**: Wildcard instant searches, category filter chips (South Indian, Healthy choices, Snacks, Beverages), and Veg/Nonveg badges.
* **Detail Modals**: Displays calorie count, preparation times, ingredients check, and spice indicators.
* **Slide-over Cart**: Dynamic item note additions, instant subtotal calculation, and 5% GST calculations.
* **Timelines Tracking**: Live visual status indicators polling every 10 seconds via AJAX.
* **One-Click Reordering**: Duplicate historical orders directly back to checkout.
* **Invoice Receipts**: Printable HTML slip generator.

### Admin & Kitchen Console
* **Statistical charts**: Loads Chart.js to graph hourly ordering peaks and departmental volumes.
* **Orders Management**: Paginated datatable supporting status updates (Received → Confirmed → Preparing → Ready → Out For Delivery → Delivered), filters, and client-side CSV exports.
* **Kitchen Chef portal**: Displays large, touchscreen-friendly priority cards highlighting special instructions.
* **Stock control**: Adjust quantities and toggle menu item visibility (Available, Out of Stock, Off-Menu).
* **Catalog & Directory CRUD**: CRUD managers for food items, employee registry, and category icons.
* **Analytical summaries**: Tabular sales report details for departments and popular foods.

---

## 🛠️ Tech Stack

* **Frontend**: HTML5, Vanilla JavaScript, CSS, Tailwind Play CSS CDN, Font Awesome Icons.
* **Backend**: PHP 8.2 (Vanilla REST APIs with PDOprepared statements).
* **Database**: MySQL 8.0 / MariaDB.

---

## 📂 Folder Structure

```text
/assets
  ├── css/style.css        # Animations, skeletons, checks, dark mode variables
  └── js/app.js            # Toast handlers, global fetch, modal dialogues
/api
  ├── login.php            # Processes POST employee/admin credentials
  ├── logout.php           # Session destroyer
  ├── get-foods.php        # Search, pagination, filter queries
  ├── place-order.php      # Transactional order checker and inventory deduct
  ├── update-order-status.php # Chef transitions and cancellation refunds
  └── ...                  # profile, settings, categories details
/employee
  ├── login.php            # Login interface
  ├── dashboard.php        # Employee panel
  ├── order.php            # Active ordering interface
  ├── track.php            # Live polling timeline
  └── ...                  # history list, profile editor
/admin
  ├── login.php            # Chef/Admin console entry
  ├── dashboard.php        # Revenue graphs & stats
  ├── orders.php           # Order table & CSV export
  ├── chef.php             # Touchscreen kitchen panel
  └── ...                  # foods catalog, employees list, settings
/includes                  # Layout wrappers, headers, session auth libraries
/config                    # PDO connection configs
/sql                       # Complete SQL schemas and seeds
```

---

## 💾 Database Setup & Seed Data

1. Make sure your MySQL server is running (e.g., via XAMPP, MAMP, or local service).
2. Open your terminal or database management tool (like phpMyAdmin) and create the database:
   ```sql
   CREATE DATABASE office_canteen;
   ```
3. Import the SQL schema and demo seed data:
   ```bash
   mysql -u root -p office_canteen < sql/schema.sql
   ```
   *(If your database user is different, replace `root` with your username).*

---

## 💻 Running Locally

1. Duplicate the `.env.example` template into `.env`:
   ```bash
   cp .env.example .env
   ```
2. Open `.env` and verify database credentials:
   ```ini
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=office_canteen
   DB_USER=root
   DB_PASS=your_mysql_password
   ```
3. Start the PHP built-in webserver from the project root:
   ```bash
   php -S localhost:8000
   ```
4. Access the employee portal: [http://localhost:8000](http://localhost:8000)

---

## 🔑 Demo Access Credentials

All passwords default to **`password123`**.

| User Context | Username / Employee ID | Role | Key Access View |
| :--- | :--- | :--- | :--- |
| **Employee** | `EMP001` | Employee | Dashboard, Menu, Order Tracker, History |
| **Employee** | `EMP002` | Employee | Dashboard, Menu, Order Tracker, History |
| **Employee** | `EMP003` to `EMP030` | Employee | Dashboard, Menu, Order Tracker, History |
| **Admin** | `admin` | Manager Admin | Financial charts, CRUDs, CSV Export, Settings |
| **Chef** | `chef` | Head Chef | Kitchen view cards, Ready toggles |
| **Kitchen Staff**| `kitchen` | Staff | Kitchen cards, Dispatch status updates |

---

## 🔒 Security Measures

1. **SQL Injection Prevention**: Uses strict PHP PDO prepared statements for all operations.
2. **XSS Mitigation**: Applies escaping rules (`htmlspecialchars` / `ENT_QUOTES`) across forms.
3. **CSRF Protection**: Form check verification uses random byte hashes validated by session buffers.
4. **Password Protection**: Stored user passwords use secure `PASSWORD_BCRYPT` hashes.
5. **Transaction Integrity**: Placed orders and stock balances update within database transactional locks (`beginTransaction` & `commit`), rolling back completely on errors.

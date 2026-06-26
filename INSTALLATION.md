# FarmConnect Kenya — Installation Guide

Complete setup instructions for **local development** (Laragon / XAMPP) and **live hosting** (InfinityFree).

---

## Prerequisites

| Requirement | Minimum |
|-------------|---------|
| PHP | 8.0+ (8.1+ recommended) |
| MySQL / MariaDB | 5.7+ / 10.3+ |
| Web server | Apache (mod_rewrite optional) |
| PHP extensions | PDO MySQL, `fileinfo`, GD (recommended for image uploads) |

**Local:** [Laragon](https://laragon.org/) or [XAMPP](https://www.apachefriends.org/)  
**Live:** [InfinityFree](https://www.infinityfree.net/) or any PHP + MySQL host

---

## Which database file to use?

| File | Use? | Notes |
|------|------|-------|
| **`database/schema.sql`** | **Yes — use this** | Full current schema: Super Admin, orders, audit logs, customers |
| `database.sql` (project root) | **No** | Legacy coursework schema (`fullname`, `username`) — outdated |

---

## User roles

| Role | How they log in | Access |
|------|-----------------|--------|
| **Customer** | Public registration | Browse, order, inquiries |
| **Farmer** | Public registration | Products, orders, inquiries |
| **Admin** | Created by Super Admin | Farmers, customers, products, orders |
| **Super Admin** | Seeded admin account | Everything above + **Manage Admins** |

Admin staff log in at the same URL as everyone else (`login.php`). The system detects the account type automatically.

---

## Part 1 — Local installation (Laragon / XAMPP)

### Step 1: Place project files

**Laragon (Windows):**
```
C:\laragon\www\farmconnect\
```

**XAMPP (Windows):**
```
C:\xampp\htdocs\farmconnect\
```

**XAMPP (Mac):**
```
/Applications/XAMPP/htdocs/farmconnect/
```

### Step 2: Start Apache and MySQL

- **Laragon:** Click **Start All**
- **XAMPP:** Start **Apache** and **MySQL**

### Step 3: Configure environment

1. Copy the example config:
   ```
   config/env.local.php.example  →  config/env.local.php
   ```

2. Edit `config/env.local.php` for local use:

   ```php
   <?php
   declare(strict_types=1);

   return [
       'APP_ENV'       => 'local',
       'BASE_URL'      => 'http://localhost/farmconnect/',
       'AUTO_BASE_URL' => true,   // auto-detect URL (ngrok, mobile testing)
       'DB_HOST'       => 'localhost',
       'DB_NAME'       => 'farmconnect_kenya',
       'DB_USER'       => 'root',
       'DB_PASS'       => '',
   ];
   ```

   > `config/env.local.php` is gitignored — never commit it.

   > `AUTO_BASE_URL` lets CSS and images load correctly when using ngrok or a phone on the same network.

### Step 4: Create the database

1. Open **phpMyAdmin:** `http://localhost/phpmyadmin`
2. Click **Import**
3. Choose **`database/schema.sql`**
4. Click **Go**

This creates database **`farmconnect_kenya`** with all tables:

- `admins`, `farmers`, `customers`, `products`
- `orders`, `inquiries`, `notifications`, `audit_logs`

### Step 5: Seed the Super Admin account

From the project folder in a terminal:

```bash
cd C:\laragon\www\farmconnect
php tools/seed_admin.php
```

**Default Super Admin login:**

| Field | Value |
|-------|-------|
| URL | `http://localhost/farmconnect/login.php` |
| Email | `admin@farmconnect.co.ke` |
| Password | `superadmin123` |

Change this password after first login.

### Step 6: Verify uploads folders

Ensure these folders exist and are writable:

```
uploads/
uploads/profiles/
uploads/products/
```

On Windows (Laragon/XAMPP) the default permissions are usually fine. On Linux/Mac:

```bash
chmod -R 755 uploads/
```

### Step 7: Open the application

```
http://localhost/farmconnect/
```

You should see the FarmConnect Kenya homepage. Log in as Super Admin to access the full admin dashboard.

---

## Part 2 — Upgrading an old database

If you previously used the legacy `farmconnect_db` database (old `fullname` / `username` columns), run:

```bash
php tools/migrate_legacy_db.php
```

This script:

- Renames legacy columns to match the current schema
- Adds Super Admin `role` and `audit_logs`
- Normalizes table collations
- Seeds the Super Admin account

Then set `DB_NAME` in `config/env.local.php` to match your database name.

**Collation fix only** (if you see `Illegal mix of collations` errors):

```bash
php tools/fix_collations.php
```

**Super Admin migration only** (database already has correct columns):

Import `database/migrations/add_super_admin_role.sql` in phpMyAdmin.

---

## Part 3 — Live hosting on InfinityFree

### Before you upload

InfinityFree is suitable for **demos, coursework, and low-traffic sites**. Limits include ~50 MB per database and ~30,000 daily HTTP requests.

### Files to upload

Upload **production files only** to `htdocs` (via FTP or file manager):

```
admin/  assets/  config/  customer/  farmer/  includes/  uploads/
index.php  login.php  logout.php  products.php  product_details.php
place_order.php  contact_inquiry.php  register.php  register_choice.php
register_customer.php  register_farmer.php
```

**Do not upload:** `WEEK 1/` … `WEEK 9/` folders, `.git/`, or local-only tools (optional).

### Step 1: Create MySQL database in VistaPanel

1. Log in to [InfinityFree](https://www.infinityfree.net/) → **VistaPanel**
2. Create a MySQL database
3. Note these values:
   - **MySQL Hostname** (e.g. `sql305.infinityfree.com`)
   - **Database name** (e.g. `if0_12345678_farmconnect`)
   - **Username** and **Password**

### Step 2: Create `config/env.local.php` on the server

```php
<?php
declare(strict_types=1);

return [
    'APP_ENV'       => 'production',
    'BASE_URL'      => 'https://yoursite.infinityfreeapp.com/',
    'AUTO_BASE_URL' => true,
    'DB_HOST'       => 'sql305.infinityfree.com',   // from VistaPanel
    'DB_NAME'       => 'if0_12345678_farmconnect',   // from VistaPanel
    'DB_USER'       => 'if0_12345678',               // from VistaPanel
    'DB_PASS'       => 'your_database_password',
];
```

Replace values with your actual InfinityFree credentials. Use `https://` if SSL is enabled.

### Step 3: Import the database

1. Open **phpMyAdmin** from VistaPanel
2. Select **your** database (do not create a new one — InfinityFree assigns the name)
3. Go to **Import**
4. Open `database/schema.sql` in a text editor and **remove** these lines before importing:
   ```sql
   CREATE DATABASE IF NOT EXISTS farmconnect_kenya ...
   USE farmconnect_kenya;
   ```
5. Import the remaining SQL

### Step 4: Seed the Super Admin

InfinityFree free plans have **no SSH**, so run the seeder locally pointed at your live DB, **or** use phpMyAdmin:

**Option A — run locally** (if remote MySQL is allowed; InfinityFree blocks external DB access, so this usually does not work):

Use Option B instead.

**Option B — phpMyAdmin SQL tab:**

After uploading files, visit this URL **once** in your browser (create a temporary file if needed):

```
https://yoursite.infinityfreeapp.com/tools/seed_admin.php
```

Then **delete** `tools/seed_admin.php` from the server immediately after use.

**Option C — manual SQL in phpMyAdmin:**

Generate a password hash locally:

```bash
php -r "echo password_hash('superadmin123', PASSWORD_DEFAULT);"
```

Then run in phpMyAdmin:

```sql
INSERT INTO admins (full_name, email, password_hash, role, status)
VALUES (
    'System Administrator',
    'admin@farmconnect.co.ke',
    'PASTE_HASH_HERE',
    'super_admin',
    'active'
);
```

### Step 5: Enable SSL

In VistaPanel → **SSL Certificates** → enable free SSL for your domain.

### Step 6: Set uploads permissions

Create and make writable (via FTP / file manager):

```
uploads/profiles/
uploads/products/
```

### Step 7: Smoke test

1. Homepage loads with styling (green navbar, marketplace layout)
2. Login as Super Admin → dashboard shows **Super Admin** badge
3. Sidebar includes **Manage Admins**
4. Register a farmer → add a product → appears on marketplace
5. Register a customer → place an order

---

## First-time usage

### Register a farmer

1. Go to **Get Started** → **Register as Farmer**
2. Complete the form and log in
3. Add products from the farmer dashboard

### Register a customer

1. Go to **Get Started** → **Register as Customer**
2. Log in and browse the marketplace
3. Place an order on a product page

### Super Admin tasks

1. Log in at `/login.php`
2. Open **Manage Admins** → create Admin accounts
3. Use **Farmers** / **Customers** to suspend, activate, or delete accounts
4. View **Orders** for platform-wide order monitoring

---

## Troubleshooting

### Unknown database `farmconnect_db`

**Cause:** `config/env.local.php` points to the wrong database name.

**Fix:** Set `DB_NAME` to `farmconnect_kenya` (or your InfinityFree database name) and import `database/schema.sql`.

### Column not found (`full_name`, `role`, etc.)

**Cause:** Old legacy database schema.

**Fix:** Import `database/schema.sql` on a fresh database, or run `php tools/migrate_legacy_db.php`.

### CSS / images broken on mobile or ngrok

**Cause:** `BASE_URL` pointed to `localhost`.

**Fix:** Set `'AUTO_BASE_URL' => true` in `config/env.local.php`.

### Illegal mix of collations (UNION error)

**Fix:**

```bash
php tools/fix_collations.php
```

### Constant already defined warnings

**Cause:** Usually resolved in current code. Clear browser cache and ensure you are on the latest version.

### InfinityFree 500 error

- Check PHP version is **8.0+** in VistaPanel
- Verify `config/env.local.php` exists on the server with correct DB credentials
- Check error logs in VistaPanel → **Error Log**
- Ensure no single PHP file exceeds InfinityFree size limits

### Images not uploading

- Confirm `uploads/profiles/` and `uploads/products/` exist and are writable
- Max upload size is **2 MB** per image (configured in `config/app.php`)
- Only JPG, PNG, and WebP are allowed

### Login fails for Super Admin

```bash
php tools/seed_admin.php
```

Or run `php tools/fix_admin_account.php` if duplicate admin rows exist.

---

## Testing checklist

### Public

- [ ] Homepage loads with correct styling
- [ ] Marketplace search and pagination work
- [ ] Product detail page displays correctly

### Farmer

- [ ] Register and log in
- [ ] Add, edit, and delete a product
- [ ] Receive and respond to an order

### Customer

- [ ] Register and log in
- [ ] Place an order
- [ ] View order status updates

### Admin (staff)

- [ ] Log in as Admin (not Super Admin)
- [ ] Manage farmers, customers, products
- [ ] **Manage Admins** menu is hidden

### Super Admin

- [ ] Log in as Super Admin
- [ ] Dashboard shows extended stats
- [ ] Create, suspend, promote, and demote admin accounts
- [ ] Audit actions work without errors

---

## Security checklist (before going live)

- [ ] Change default Super Admin password (`superadmin123`)
- [ ] Enable HTTPS / SSL
- [ ] Confirm `config/env.local.php` is **not** publicly accessible
- [ ] Delete one-time setup scripts (`tools/seed_admin.php`) from the server after use
- [ ] Set up regular database backups via VistaPanel
- [ ] Keep `uploads/.htaccess` in place (blocks PHP execution in uploads)

---

## Useful commands (local only)

| Command | Purpose |
|---------|---------|
| `php tools/seed_admin.php` | Create / reset Super Admin account |
| `php tools/migrate_legacy_db.php` | Upgrade old `farmconnect_db` schema |
| `php tools/fix_collations.php` | Fix mixed collation errors |
| `php tools/fix_admin_account.php` | Remove duplicate admin rows |
| `php tools/migrate_phase6_orders.php` | Add orders table (partial upgrade only) |

For a **fresh install**, import `database/schema.sql` only — migrations are not needed.

---

## Related files

| File | Purpose |
|------|---------|
| `config/env.local.php.example` | Environment template |
| `database/schema.sql` | Full database schema |
| `database/migrations/add_super_admin_role.sql` | Super Admin upgrade for existing DBs |
| `DEPLOYMENT.md` | Short deployment checklist |
| `README.md` | Project overview |

---

**Happy farming!**

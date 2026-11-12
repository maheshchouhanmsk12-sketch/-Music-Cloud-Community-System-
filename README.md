# 🎵 Music Cloud Community System (MCCS)

Production-ready, Dockerized web application built with **PHP 8.2**, **Apache**, **Aiven Cloud MySQL**, and **AdminLTE 3**, optimized for high-performance deployment on **Render**.

---

## 🌐 Live URLs & Endpoints

| Portal | Endpoint URL | Description |
| :--- | :--- | :--- |
| **Public Website / Dashboard** | [Open Homepage](https://music-cloud-community.onrender.com/) | Music player, playlists, genre browsing, and media library |
| **Login Portal** | [Open Login Page](https://music-cloud-community.onrender.com/login.php) | User & Administrator sign-in portal |
| **1-Click DB Setup Tool** | [Open DB Setup Tool](https://music-cloud-community.onrender.com/db_setup.php) | Cloud MySQL Health Check & Auto-Migrator (Aiven SSL Ready) |

---

## 🔑 Default Credentials

### 1. System Administrator
- **Login URL:** `/login.php`
- **Email:** `admin@admin.com`
- **Password:** `admin123`
- **Role:** Administrator (Type 1) - Full access to all musics, genres, playlists, and user management

### 2. Sample Subscriber / User
- **Login URL:** `/login.php`
- **Email:** `jsmith@sample.com`
- **Password:** `jsmith123` *(or create a new account via the "Create New Account" button)*
- **Role:** Subscriber (Type 2) - Upload personal tracks, manage custom playlists

---

## ⚙️ Environment Variables (Render Dashboard)

Configure these in your Render Web Service dashboard under **Environment**:

| Variable | Recommended / Example Value | Notes |
| :--- | :--- | :--- |
| `DB_SERVER` | `mysql-xxxxxx.aivencloud.com` | Your Aiven MySQL service host |
| `DB_PORT` | `26284` | Aiven 5-digit custom MySQL port |
| `DB_USERNAME` | `avnadmin` | Default Aiven MySQL master user |
| `DB_PASSWORD` | `AVNS_xxxxxxxxxxxx` | Password from your Aiven Console |
| `DB_NAME` | `defaultdb` *(or `music_db`)* | Target database name |
| `DB_SSL` | `true` | **Required** for Aiven Cloud (`require_secure_transport=ON`) |
| `BASE_URL` | *(Leave empty for auto-detect)* | Optional override for app base URL |

---

## 🚀 How to Deploy on Render (Step-by-Step)

### Step 1: Connect GitHub Repository
1. Log in to your [Render Dashboard](https://dashboard.render.com/).
2. Click **New +** &rarr; Select **Web Service**.
3. Choose **Build and deploy from a Git repository**.
4. Select `maheshchouhanmsk12-sketch/-Music-Cloud-Community-System-`.

### Step 2: Configure Service Settings
- **Name:** `music-cloud-community` *(or any preferred name)*
- **Region:** Choose the region closest to your Aiven MySQL cluster (e.g., *Oregon (US West)* or *Frankfurt (EU Central)*)
- **Branch:** `main`
- **Environment:** `Docker`
- **Dockerfile Path:** `./Dockerfile`
- **Instance Type:** `Free`

### Step 3: Add Environment Variables
Under the **Environment** section, add the Aiven MySQL credentials listed in the table above:
- `DB_SERVER`
- `DB_PORT`
- `DB_USERNAME`
- `DB_PASSWORD`
- `DB_NAME`
- `DB_SSL` = `true`

Click **Deploy Web Service**.

### Step 4: 1-Click Database Setup
1. Once the deploy succeeds, open:
   ```text
   https://<your-service-name>.onrender.com/db_setup.php
   ```
2. The setup tool will verify your SSL connection to Aiven Cloud MySQL and check table status.
3. Click **"Import Database Now (1-Click)"** to automatically execute `database/music_db.sql`.
4. Click **"Go to Login Page"** and log in with `admin@admin.com` / `admin123`.

---

## 🛠️ Architecture & Production Fixes Applied

1. **PHP 8.2 Compatibility:**
   - Property initializations moved inside class constructors to eliminate fatal deprecation errors.
   - Guarded database queries with `if ($qry && $qry instanceof mysqli_result)` and safe null checks to prevent PHP 8.x crashes on empty tables.
2. **Aiven Cloud MySQL SSL Enforcement:**
   - Upgraded `db_connect.php` to initialize via `mysqli_init()` and activate the `MYSQLI_CLIENT_SSL` flag.
   - Added support for 5-digit custom cloud ports (e.g., 26284).
3. **Render Reverse-Proxy & Port 10000 Leak Prevention:**
   - Added `UseCanonicalName Off` and `UseCanonicalPhysicalPort Off` to Apache configuration.
   - Added smart `.htaccess` trailing-slash redirect rules that strip internal container ports (`:10000` or `:80`).
   - Dynamically mapped Apache to Render's assigned `$PORT` environment variable in `start.sh`.
4. **File Uploads & Media Storage:**
   - Pre-configured `assets/uploads/` write permissions (`www-data:www-data`, chmod 775) in Dockerfile and startup script.

---

## 📂 Project Structure

```text
├── assets/                 # AdminLTE UI plugins, CSS, JavaScript, and uploads
│   └── uploads/            # Track MP3s, album covers, and user avatars
├── database/
│   └── music_db.sql        # Complete MySQL database schema & sample seed data
├── .dockerignore           # Excludes local files and backups from Docker builds
├── .gitignore              # Git ignore rules (*.original, environment files)
├── .htaccess               # Apache reverse-proxy redirect rules
├── admin_class.php         # Application core business logic (PHP 8.2 compatible)
├── ajax.php                # AJAX endpoints router
├── db_connect.php          # Aiven Cloud MySQL connection handler with SSL
├── db_setup.php            # 1-Click web installer & diagnostic health check tool
├── Dockerfile              # Production Dockerfile (php:8.2-apache)
├── home.php                # System dashboard metrics
├── index.php               # Main authenticated portal controller
├── login.php               # Login & account creation portal
├── render.yaml             # Render Blueprint configuration
├── start.sh                # Container entrypoint script
└── test_aiven.php          # CLI script to test Aiven MySQL connection
```

---

*Music Cloud Community System &bull; Deployed on Render with Aiven Cloud MySQL*

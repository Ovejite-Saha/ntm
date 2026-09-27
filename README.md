# Tour Management System

A tour management website built with **PHP 7.4+**, **MySQL**, **Bootstrap 5** (offline), and **Font Awesome** (offline).

## Requirements
- PHP 7.4, 8.1, 8.2, 8.3, or 8.4
- MySQL 5.7+ or MariaDB 10.3+
- PHP GD extension (for image cropping/resizing)

## Installation

1. **Configure database credentials** in `config/db.php` (default: localhost, root, no password).

2. **Run the installer**: Open `install.php` in your browser:
   ```
   http://your-domain/install.php
   ```
   This creates all database tables and a default admin account:
   - **Username:** `admin`
   - **Password:** `admin123`

3. **Delete `install.php`** after installation for security.

4. **Download Bootstrap 5 & Font Awesome** (offline) and place them in:
   - `assets/css/bootstrap.min.css`
   - `assets/js/bootstrap.bundle.min.js`
   - `assets/css/all.min.css`
   - `assets/js/all.min.js`
   - `assets/webfonts/` (Font Awesome webfonts)

## Features

### Public (no login required)
- Homepage with image slideshow
- Past and upcoming tour sections
- Contact form (sends email automatically)

### Login
- Login appears as a popup modal on the homepage
- Separate login for Users and Admins
- 2-minute auto-logout on inactivity

### Admin Panel (`/admin/`)
- Dashboard with statistics
- Manage Users: create (with Full Name, Email, Mobile, DOB, Marital Status, Spouse Name, Profile Photo, Username, Password), edit, delete, reset password
- Manage Admins: create with username & password only (admin completes profile after login), edit, delete, change password
- Manage Tours: create, edit, delete, assign tours to users
- Tour Gallery: upload tour images (JPG/PNG/JPEG, max 3MB), toggle slideshow visibility

### User Panel (`/user/`)
- Dashboard: view assigned completed and upcoming tours with photo galleries
- Profile: edit own information, upload/replace profile photo (auto-cropped to square, max 1MB), change password

### Image Handling
- **Profile photos**: stored as `<username>.jpg` in `uploads/profile_photos/`, auto-cropped to 256x256 square, compressed under 1MB
- **Tour photos**: stored in `uploads/tour_photos/<tour_name_year>/`, auto-resized (max 1600px), compressed under 3MB
- Only JPG, PNG, JPEG formats accepted
- Replacing a profile photo deletes the previous one

## File Structure
```
tour-management/
├── assets/css/        (Bootstrap, Font Awesome, custom styles)
├── assets/js/         (Bootstrap, Font Awesome, session timeout, main)
├── assets/webfonts/   (Font Awesome webfonts)
├── uploads/profile_photos/   (user & admin profile photos)
├── uploads/tour_photos/      (tour images organized by tour_name_year)
├── config/            (db connection, functions, session handling)
├── includes/          (header, footer, login modal)
├── admin/             (admin dashboard, users, admins, tours, gallery)
├── user/              (user dashboard, profile)
├── index.php          (homepage)
├── login-process.php  (login handler)
├── login-redirect.php (login page for timeouts)
├── contact-process.php(contact form mail handler)
├── install.php        (database installer - delete after use)
└── database.sql       (database schema)
```

## Security Notes
- Passwords hashed with `password_hash()` (bcrypt)
- Session regeneration on login
- 2-minute inactivity timeout (both server-side and client-side)
- SQL injection prevention via prepared statements
- XSS prevention via `htmlspecialchars()` output escaping

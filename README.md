# Simple Archive Management System

A lightweight, secure, and user-friendly web application for managing digital document archives. Built with **Native PHP**, **MySQL**, and **Tailwind CSS**.

## Features
- 📂 **Dynamic Categories**: Organize documents with custom icons.
- 🔍 **Smart Search & Filters**: Search by title and filter by category, year, or month.
- 👁️ **File Preview**: Built-in preview for PDFs and Images.
- ✏️ **Edit & Replace**: update document details and replace files easily.
- 📦 **Bulk Actions**: Delete multiple documents at once.
- 🔐 **Secure Auth**: Session-based login and registration.

## Requirements
- **Web Server**: Apache or Nginx (e.g., via Laragon, XAMPP).
- **PHP**: Version 7.4 or higher.
- **MySQL**: Version 5.7 or higher.

## Installation Guide

### 1. Clone the Repository
Open your terminal in your web server's root directory (e.g., `d:\laragon\www\` or `htdocs`) and run:
```bash
git clone https://github.com/syiaril/archive-app.git
cd archive-app
```

### 2. Setup Database
1.  Open your database manager (phpMyAdmin, HeidiSQL, etc.).
2.  Create a new database named **`archive_db`**.
3.  Import the provided SQL file:
    -   File: `db_archive.sql` (located in the project root).
    -   This will create all permissions, tables, and default categories with icons.

### 3. Configure Connection
Open `config/database.php` and ensure the settings match your local environment:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Your MySQL Username
define('DB_PASS', '');     // Your MySQL Password
define('DB_NAME', 'archive_db');
```

### 4. Create Uploads Directory
Ensure the uploads folder exists and is writable.
If it doesn't exist, create it:
```bash
mkdir assets/uploads
```

### 5. Access the App
Open your browser and visit:
```
http://localhost/archive-app
```
(Or http://archive-app.test if using Laragon).

## Default Login
You can register a new user or use the demo account if you imported the seed data (optional):
- **Username**: `demo`
- **Password**: `password`

## Folder Structure
```
archive-app/
├── assets/
│   └── uploads/       # Document storage (Excluded from Git)
├── config/
│   └── database.php   # DB Configuration
├── dashboard.php      # Main User Interface
├── db_archive.sql     # Database Schema & Seed
├── index.php          # Login Page
└── ...
```

## Credits
Built with ❤️ using PHP and Tailwind CSS.

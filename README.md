# Nutrition Tracker System

NutriTrack is a nutrition and health tracking web application built with a hybrid PHP + Node.js stack. It includes user authentication, meal tracking, nutrition analysis, health goal monitoring, and community features.

## Features

- User registration, login, logout, and session management
- Meal tracker, meal history, and meal database
- Nutrition analysis and nutrient facts lookup
- Health goals, water tracking, and activity logging
- Reports, personal insights, community posts, announcements, and challenges
- Admin and user management interfaces
- Email-based account actions via SMTP

## Technology Stack

- PHP for legacy pages and server-side features
- Node.js + Express for API routes and session handling
- MySQL for application data
- Composer for PHP dependencies
- npm for Node.js dependencies

## Prerequisites

- Windows with XAMPP (Apache + MySQL)
- Node.js 24.x
- npm
- Composer

## Installation

1. Clone or copy the repository into your local web folder.

2. Install PHP dependencies:
   ```bash
   cd c:\xampp_new\htdocs\nutrition_tracker
   composer install
   ```

3. Install Node.js dependencies:
   ```bash
   npm install
   ```

4. Create your environment file:
   ```bash
   copy .env.example .env
   ```

5. Update `.env` with your database credentials, SMTP settings, and session secret.

6. Import the database schema:
   - Open phpMyAdmin or MySQL CLI
   - Import `nutrack_db.sql`

## Environment Variables

The application reads configuration from `.env`.

- `DB_HOST` - database host (default: `localhost`)
- `DB_USER` - database username
- `DB_PASS` - database password
- `DB_NAME` - database name (default: `nutrack_db`)
- `SESSION_SECRET` - Express session secret
- `SMTP_EMAIL` - email address used for outgoing mail
- `SMTP_PASSWORD` - SMTP password

## Running the App

### Start the Node.js server

```bash
npm start
```

By default, the app runs on `http://localhost:3000`.

### If using XAMPP PHP pages

- Start Apache and MySQL in XAMPP
- Serve the repo via Apache if you want to access the PHP routes directly

## Project Structure

- `server.js` - Express API server and static file serving
- `routes/auth.js` - authentication endpoints
- `config/database.js` - MySQL connection pool
- `utils/auth.js` - password hashing and validation helpers
- `codes/` - frontend assets, styles, scripts, and legacy PHP pages
- `php/` - PHP page handlers and utilities
- `vendor/` - Composer dependencies
- `nutrack_db.sql` - database schema and sample data

## Notes

- Make sure `.env` is not committed to source control.
- If you see login or session issues, verify `SESSION_SECRET` and database connection values.
- This repository includes both legacy PHP frontend pages and a Node.js API layer.

## License

This project does not include a license file by default.

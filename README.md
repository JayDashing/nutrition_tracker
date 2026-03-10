# Nutrition-Tracker-System-CST5
A nutrition tracker website.

## Setup Instructions

1. Run Apache and MySQL on XAMPP.
2. Set up the environment and install dependencies:
   ```bash
   copy .env.example .env
   composer install
   ```
3. Import nutrack_db.sql.

## Run the Server
   ```bash
   php -S localhost:8000 -t public
   ```

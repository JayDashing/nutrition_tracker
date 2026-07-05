# Node.js/Express Migration Guide for Vercel

## Overview
Your NutriTrack application has been converted from PHP to Node.js/Express for Vercel deployment.

## What's Been Set Up

### ✅ Core Infrastructure
- **server.js** - Main Express application
- **package.json** - Node.js dependencies configured
- **vercel.json** - Vercel deployment configuration
- **config/database.js** - MySQL connection pool (using mysql2)
- **utils/auth.js** - Authentication utilities (bcrypt, JWT, zxcvbn)
- **routes/auth.js** - Basic authentication endpoints

### 📦 Key Dependencies Added
- `express` - Web framework
- `mysql2` - Database driver
- `dotenv` - Environment variables
- `bcryptjs` - Password hashing
- `nodemailer` - Email sending
- `zxcvbn` - Password strength checker
- `jsonwebtoken` - JWT tokens
- `cors` - Cross-origin support
- `express-session` - Session management

## Environment Variables
Update your `.env` file with:
```
DB_HOST=your-database-host
DB_USER=your-database-user
DB_PASS=your-database-password
DB_NAME=nutrack_db
SMTP_EMAIL=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SESSION_SECRET=change-this-to-a-random-string
NODE_ENV=production
```

## Next Steps - Converting Remaining PHP Routes

### 1. Dashboard Route (`routes/dashboard.js`)
Convert from `codes/php/dashboard.php` - Handles:
- User dashboard data
- Activity logs
- Statistics queries

### 2. Meal Tracker Route (`routes/meals.js`)
Convert from `codes/php/meal_tracker.php` - Handles:
- Adding meals
- Meal history
- Meal database queries

### 3. User Profile Route (`routes/profile.js`)
Convert from `codes/php/profile.php`

### 4. Health Goals Route (`routes/goals.js`)
Convert from `codes/php/health_goals.php`

### 5. Additional Routes
- Community endpoints
- Reports generation
- Settings management
- Admin functions

## Local Development

### 1. Install Dependencies
```bash
npm install
```

### 2. Create `.env` file
Copy from `.env.example` and fill in your database credentials.

### 3. Run Locally
```bash
npm start
```
Server will run on http://localhost:3000

## Frontend Updates

Your HTML/CSS/JS files are still in `codes/` and will be served as static files. You may need to:
1. Update fetch URLs from PHP endpoints to `/api/*` endpoints
2. Update forms to POST to new Express routes
3. Adjust paths for CSS/JS imports

## Database Schema
No changes needed to your MySQL database - the schema remains the same. Just ensure:
1. Your database is accessible from Vercel's servers
2. You have a cloud MySQL instance (e.g., AWS RDS, Google Cloud SQL, or PlanetScale)
3. Environment variables point to the correct database

## Testing Before Deployment

1. Test locally with `npm start`
2. Test all authentication flows
3. Verify database connections
4. Test API endpoints with Postman or similar
5. Check session management

## Deploying to Vercel

### 1. Push to GitHub
```bash
git add .
git commit -m "Convert to Node.js/Express"
git push origin main
```

### 2. Connect to Vercel
- Go to https://vercel.com/dashboard
- Click "Add New Project"
- Import your GitHub repository
- Add environment variables in Vercel settings
- Deploy

### 3. Verify Deployment
- Check that the app loads
- Test authentication
- Monitor Vercel logs for errors

## Important Notes

⚠️ **Database Access**: Vercel is serverless. You CANNOT use a local MySQL database. You need:
- Cloud-hosted MySQL (AWS RDS, Google Cloud SQL, PlanetScale, etc.)
- Or use MongoDB (no schema changes required with mongoose)

⚠️ **File Uploads**: If using file uploads, configure cloud storage:
- AWS S3
- Google Cloud Storage
- Vercel Blob Storage

⚠️ **Email**: Gmail SMTP requires an "App Password" if 2FA is enabled

## Troubleshooting

### "Cannot find module" errors
- Run `npm install` again
- Check import paths are correct

### Database connection errors
- Verify `.env` variables
- Check database server is accessible
- Verify credentials

### Session issues
- Sessions don't persist across serverless functions
- Consider using JWT tokens instead
- Or use a session store (Redis, MongoDB)

## Additional Resources
- Express docs: https://expressjs.com
- Vercel docs: https://vercel.com/docs
- MySQL2 docs: https://github.com/sidorares/node-mysql2
- Nodemailer docs: https://nodemailer.com

## Next - Conversion Template

When converting each PHP route to Express, follow this pattern:

```javascript
// OLD: codes/php/some_feature.php
// NEW: routes/some_feature.js

import express from 'express';
import { query } from '../config/database.js';
import { requireAuth, verifyCsrfToken } from '../utils/auth.js';

const router = express.Router();

router.post('/endpoint', requireAuth, async (req, res) => {
  try {
    if (!verifyCsrfToken(req)) {
      return res.status(403).json({ error: 'CSRF token invalid' });
    }
    
    // Your logic here
    const result = await query('SELECT * FROM table WHERE id = ?', [req.body.id]);
    
    res.json({ success: true, data: result });
  } catch (error) {
    console.error('Error:', error);
    res.status(500).json({ error: 'Operation failed' });
  }
});

export default router;
```

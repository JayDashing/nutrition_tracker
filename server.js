import express from 'express';
import session from 'express-session';
import cors from 'cors';
import dotenv from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';
import crypto from 'crypto';
import authRoutes from './routes/auth.js';

// Load environment variables
dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();

// Middleware
app.use(cors({ origin: true, credentials: true }));
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Session configuration
app.use(session({
  secret: process.env.SESSION_SECRET || 'your-secret-key-change-in-production',
  resave: false,
  saveUninitialized: true,
  cookie: {
    secure: process.env.NODE_ENV === 'production',
    httpOnly: true,
    sameSite: 'lax',
    maxAge: 24 * 60 * 60 * 1000 // 24 hours
  }
}));

// Static files
app.use(express.static(path.join(__dirname, 'codes')));
app.use('/nutrition_tracker', express.static(__dirname));

// CSRF token middleware
app.use((req, res, next) => {
  if (!req.session.csrf_token) {
    req.session.csrf_token = crypto.randomBytes(32).toString('hex');
  }
  res.locals.csrf_token = req.session.csrf_token;
  next();
});

// CSRF token endpoint for static pages
app.get('/api/csrf', (req, res) => {
  res.json({ csrf_token: req.session.csrf_token });
});

// API Routes
app.use('/api/auth', authRoutes);

// Legacy PHP-style login/register route support
app.get(['/logreg.php', '/nutrition_tracker/logreg.php'], (req, res) => {
  res.sendFile(path.join(__dirname, 'codes', 'php', 'logreg.html'));
});
// Additional routes will be added here:
// import dashboardRoutes from './routes/dashboard.js';
// import mealRoutes from './routes/meals.js';
// app.use('/api/dashboard', dashboardRoutes);
// app.use('/api/meals', mealRoutes);

// Home route
app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'codes', 'php', 'index.html'));
});

// 404 handler
app.use((req, res) => {
  res.status(404).json({ error: 'Route not found' });
});

// Error handler
app.use((err, req, res, next) => {
  console.error(err);
  res.status(500).json({ error: 'Internal server error' });
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
});

export default app;

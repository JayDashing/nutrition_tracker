import express from 'express';
import { query } from '../config/database.js';
import { 
  hashPassword, 
  comparePassword, 
  checkPasswordStrength
} from '../utils/auth.js';
import nodemailer from 'nodemailer';

const router = express.Router();

// Configure email transporter
const transporter = nodemailer.createTransport({
  service: 'gmail',
  auth: {
    user: process.env.SMTP_EMAIL,
    pass: process.env.SMTP_PASSWORD
  }
});

// Register endpoint
router.post('/register', async (req, res) => {
  try {
    const { email, username, password } = req.body;
    const firstName = req.body.firstName || '';
    const lastName = req.body.lastName || '';

    // Validate input
    if (!email || !username || !password) {
      return res.status(400).json({ error: 'Missing required fields' });
    }

    // Check password strength
    const passwordStrength = checkPasswordStrength(password);
    if (!passwordStrength.isStrong) {
      return res.status(400).json({ 
        error: 'Password is too weak',
        feedback: passwordStrength.feedback 
      });
    }

    // Check if user exists
    const existingUser = await query('SELECT * FROM users WHERE email = ? OR username = ?', [email, username]);
    if (existingUser.length > 0) {
      return res.status(409).json({ error: 'Email or username already exists' });
    }

    // Hash password
    const hashedPassword = await hashPassword(password);

    // Create user
    await query(
      'INSERT INTO users (email, username, password, first_name, last_name, role, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
      [email, username, hashedPassword, firstName, lastName, 'user']
    );

    // Set session
    const user = await query('SELECT id FROM users WHERE username = ?', [username]);
    req.session.userId = user[0].id;
    req.session.username = username;
    req.session.role = 'user';

    res.json({ message: 'Registration successful', redirect: '/dashboard' });
  } catch (error) {
    console.error('Registration error:', error);
    res.status(500).json({ error: 'Registration failed' });
  }
});

// Login endpoint
router.post('/login', async (req, res) => {
  try {
    const { email, signin_identifier, password } = req.body;
    const identifier = email || signin_identifier;

    if (!identifier || !password) {
      return res.status(400).json({ error: 'Email or username and password required' });
    }

    const users = await query(
      'SELECT * FROM users WHERE email = ? OR username = ?',
      [identifier, identifier]
    );
    if (users.length === 0) {
      return res.status(401).json({ error: 'Invalid credentials' });
    }

    const user = users[0];
    const passwordMatch = await comparePassword(password, user.password);

    if (!passwordMatch) {
      return res.status(401).json({ error: 'Invalid credentials' });
    }

    // Update last login
    await query('UPDATE users SET last_login = NOW() WHERE id = ?', [user.id]);

    // Set session
    req.session.userId = user.id;
    req.session.username = user.username;
    req.session.role = user.role;

    res.json({ 
      message: 'Login successful',
      redirect: user.role === 'admin' ? '/admin/dashboard' : '/dashboard'
    });
  } catch (error) {
    console.error('Login error:', error);
    res.status(500).json({ error: 'Login failed' });
  }
});

// Forgot password endpoint (basic placeholder)
router.post('/forgot-password', async (req, res) => {
  try {
    const { email } = req.body;
    if (!email) {
      return res.status(400).json({ error: 'Email is required' });
    }

    const users = await query('SELECT id FROM users WHERE email = ?', [email]);
    if (users.length === 0) {
      return res.json({ message: 'If an account exists, a reset link has been sent.' });
    }

    // TODO: implement password reset email logic here
    return res.json({ message: 'If an account exists, a reset link has been sent.' });
  } catch (error) {
    console.error('Forgot password error:', error);
    res.status(500).json({ error: 'Unable to process request' });
  }
});

// Logout endpoint
router.post('/logout', (req, res) => {
  req.session.destroy((err) => {
    if (err) {
      return res.status(500).json({ error: 'Logout failed' });
    }
    res.json({ message: 'Logout successful' });
  });
});

// Check auth status
router.get('/status', (req, res) => {
  if (req.session.userId) {
    res.json({
      authenticated: true,
      username: req.session.username,
      role: req.session.role
    });
  } else {
    res.json({ authenticated: false });
  }
});

export default router;

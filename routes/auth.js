import express from 'express';
import { query } from '../config/database.js';
import { 
  hashPassword, 
  comparePassword, 
  checkPasswordStrength
} from '../utils/auth.js';
import nodemailer from 'nodemailer';

const router = express.Router();

console.log('routes/auth.js loaded');

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
    console.log('POST /api/auth/register', req.body);
    const {
      email,
      username,
      password,
      signup_email,
      signup_username,
      signup_password,
      firstName,
      lastName
    } = req.body;

    const regEmail = email || signup_email;
    const regUsername = username || signup_username;
    const regPassword = password || signup_password;
    const first_name = firstName || req.body.first_name || '';
    const last_name = lastName || req.body.last_name || '';

    // Validate input
    if (!regEmail || !regUsername || !regPassword) {
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
    const existingUser = await query('SELECT * FROM users WHERE email = ? OR username = ?', [regEmail, regUsername]);
    if (existingUser.length > 0) {
      return res.status(409).json({ error: 'Email or username already exists' });
    }

    // Hash password
    const hashedPassword = await hashPassword(regPassword);

    // Create user
    await query(
      'INSERT INTO users (email, username, password, first_name, last_name, role, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
      [regEmail, regUsername, hashedPassword, first_name, last_name, 'user']
    );

    // Set session
    const user = await query('SELECT id FROM users WHERE username = ?', [regUsername]);
    req.session.userId = user[0].id;
    req.session.username = regUsername;
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
    console.log('POST /api/auth/login', req.body);
    const {
      email,
      signin_identifier,
      signin_password,
      password,
      username,
      signin_username
    } = req.body;

    const identifier = email || signin_identifier || username || signin_username;
    const loginPassword = password || signin_password;

    console.log('identifier', identifier, 'passwordPresent', !!loginPassword);

    if (!identifier || !loginPassword) {
      return res.status(400).json({ error: 'Email or username and password required' });
    }

    const users = await query(
      'SELECT * FROM users WHERE email = ? OR username = ?',
      [identifier, identifier]
    );
    console.log('login users count', users.length, users.map(u => ({id:u.id,username:u.username,email:u.email,role:u.role}))); 
    if (users.length === 0) {
      return res.status(401).json({ error: 'Invalid credentials' });
    }

    const user = users[0];
    const passwordMatch = await comparePassword(loginPassword, user.password);

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

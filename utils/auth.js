import bcrypt from 'bcryptjs';
import jwt from 'jsonwebtoken';
import zxcvbn from 'zxcvbn';

export async function hashPassword(password) {
  const salt = await bcrypt.genSalt(10);
  return await bcrypt.hash(password, salt);
}

export async function comparePassword(password, hash) {
  return await bcrypt.compare(password, hash);
}

export function generateToken(userId, username) {
  return jwt.sign(
    { userId, username },
    process.env.JWT_SECRET || 'your-jwt-secret',
    { expiresIn: '24h' }
  );
}

export function verifyToken(token) {
  try {
    return jwt.verify(token, process.env.JWT_SECRET || 'your-jwt-secret');
  } catch (error) {
    return null;
  }
}

export function checkPasswordStrength(password) {
  const result = zxcvbn(password);
  return {
    score: result.score,
    feedback: result.feedback.suggestions,
    isStrong: result.score >= 3
  };
}

export function generateOTP() {
  return String(Math.floor(Math.random() * 100000)).padStart(5, '0');
}

// CSRF Token verification
export function verifyCsrfToken(req) {
  const token = req.body.csrf_token || req.headers['x-csrf-token'];
  return token && token === req.session.csrf_token;
}

// Middleware to check authentication
export function requireAuth(req, res, next) {
  if (!req.session.userId) {
    return res.status(401).json({ error: 'Authentication required' });
  }
  next();
}

// Middleware to check admin role
export function requireAdmin(req, res, next) {
  if (!req.session.userId || req.session.role !== 'admin') {
    return res.status(403).json({ error: 'Admin access required' });
  }
  next();
}

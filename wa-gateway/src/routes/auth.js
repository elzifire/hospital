import express from 'express';
import bcrypt from 'bcryptjs';
import jwt from 'jsonwebtoken';
import { query } from '../config/database.js';
import { authMiddleware } from '../middleware/auth.js';

const router = express.Router();

// POST /api/auth/login
router.post('/login', async (req, res) => {
  try {
    const { email, password } = req.body;

    if (!email || !password) {
      return res.status(400).json({
        success: false,
        message: 'Email dan password wajib diisi.',
      });
    }

    // Query user directly from Laravel users table
    const result = await query(
      'SELECT id, name, email, password FROM users WHERE email = $1 LIMIT 1',
      [email.trim().toLowerCase()]
    );

    if (result.rows.length === 0) {
      return res.status(401).json({
        success: false,
        message: 'Email atau password salah.',
      });
    }

    const user = result.rows[0];

    // Laravel uses bcrypt or argon2. Here we verify standard bcrypt hash ($2y$ or $2a$ or $2b$)
    let isMatch = false;
    try {
      // In PHP Laravel, $2y$ is often used; bcryptjs can handle it or convert $2y$ to $2a$
      const formattedHash = user.password.replace(/^\$2y\$/, '$2a$');
      isMatch = await bcrypt.compare(password, formattedHash);
    } catch (e) {
      console.error('Password compare error:', e);
      isMatch = false;
    }

    if (!isMatch) {
      return res.status(401).json({
        success: false,
        message: 'Email atau password salah.',
      });
    }

    const secret = process.env.JWT_SECRET || 'super_secret_jwt_key_wa_gateway_2026_change_me';
    const token = jwt.sign(
      {
        id: user.id,
        name: user.name,
        email: user.email,
      },
      secret,
      { expiresIn: '7d' }
    );

    return res.json({
      success: true,
      message: 'Login berhasil.',
      token,
      user: {
        id: user.id,
        name: user.name,
        email: user.email,
      },
    });
  } catch (error) {
    console.error('Login error:', error);
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server saat login.',
      error: error.message,
    });
  }
});

// GET /api/auth/me
router.get('/me', authMiddleware, async (req, res) => {
  try {
    const result = await query(
      'SELECT id, name, email, created_at FROM users WHERE id = $1 LIMIT 1',
      [req.user.id]
    );

    if (result.rows.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'User tidak ditemukan.',
      });
    }

    return res.json({
      success: true,
      user: result.rows[0],
    });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Gagal mengambil data user.',
      error: error.message,
    });
  }
});

export default router;

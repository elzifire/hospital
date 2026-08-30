import jwt from 'jsonwebtoken';

export const authMiddleware = (req, res, next) => {
  try {
    const authHeader = req.headers.authorization;
    let token = null;

    if (authHeader && authHeader.startsWith('Bearer ')) {
      token = authHeader.split(' ')[1];
    } else if (req.query && req.query.token) {
      token = req.query.token;
    }

    if (!token) {
      return res.status(401).json({
        success: false,
        message: 'Akses ditolak: Token autentikasi tidak ditemukan',
      });
    }

    const secret = process.env.JWT_SECRET || 'super_secret_jwt_key_wa_gateway_2026_change_me';
    const decoded = jwt.verify(token, secret);

    req.user = decoded;
    next();
  } catch (error) {
    return res.status(401).json({
      success: false,
      message: 'Akses ditolak: Token tidak valid atau sudah kedaluwarsa',
      error: error.message,
    });
  }
};

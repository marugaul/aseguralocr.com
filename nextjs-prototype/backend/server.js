require('dotenv').config();
const express = require('express');
const cors = require('cors');
const mysql = require('mysql2/promise');

const app = express();
const PORT = process.env.PORT || 3001;

// Middleware
app.use(cors({
  origin: process.env.CORS_ORIGIN || '*'
}));
app.use(express.json());

// Database connection pool
const pool = mysql.createPool({
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER,
  password: process.env.DB_PASS,
  database: process.env.DB_NAME,
  port: process.env.DB_PORT || 3306,
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
});

// Health check endpoint
app.get('/api/health', (req, res) => {
  res.json({
    status: 'ok',
    timestamp: new Date().toISOString(),
    service: 'AseguraloCR Backend API'
  });
});

// Get homepage stats
app.get('/api/stats', async (req, res) => {
  try {
    // In a real scenario, this would query the database
    // For prototype, return mock data
    const stats = {
      homes: 50000,
      satisfaction: 98,
      coverage24_7: true
    };

    res.json({
      success: true,
      data: stats
    });
  } catch (error) {
    console.error('Error fetching stats:', error);
    res.status(500).json({
      success: false,
      error: 'Error al obtener estadísticas'
    });
  }
});

// Get insurance types
app.get('/api/insurance-types', async (req, res) => {
  try {
    const types = [
      {
        id: 1,
        name: 'Seguro de Hogar',
        slug: 'hogar',
        icon: 'fa-home',
        color: 'purple',
        description: 'Protege tu casa y todo lo que hay en ella contra incendios, robos, terremotos y más.',
        features: ['Cobertura amplia', 'Primas accesibles'],
        available: true,
        link: '/hogar-comprensivo.php'
      },
      {
        id: 2,
        name: 'Seguro de Auto',
        slug: 'auto',
        icon: 'fa-car',
        color: 'blue',
        description: 'Circula tranquilo con la mejor cobertura para tu vehículo. Incluye responsabilidad civil.',
        features: ['Daños propios y terceros', 'Asistencia vial 24/7'],
        available: false,
        link: null
      },
      {
        id: 3,
        name: 'Seguro de Vida',
        slug: 'vida',
        icon: 'fa-heart',
        color: 'green',
        description: 'Asegura el futuro de tu familia con nuestros planes de vida flexibles y completos.',
        features: ['Protección familiar', 'Planes personalizados'],
        available: false,
        link: null
      }
    ];

    res.json({
      success: true,
      data: types
    });
  } catch (error) {
    console.error('Error fetching insurance types:', error);
    res.status(500).json({
      success: false,
      error: 'Error al obtener tipos de seguros'
    });
  }
});

// Get submissions count (example of real DB query)
app.get('/api/submissions/count', async (req, res) => {
  try {
    const [rows] = await pool.query(
      'SELECT COUNT(*) as total FROM submissions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)'
    );

    res.json({
      success: true,
      data: {
        total: rows[0].total,
        period: 'last_30_days'
      }
    });
  } catch (error) {
    console.error('Error fetching submissions count:', error);
    res.status(500).json({
      success: false,
      error: 'Error al obtener datos de solicitudes'
    });
  }
});

// Contact form submission (example POST endpoint)
app.post('/api/contact', async (req, res) => {
  try {
    const { nombre, email, telefono, mensaje } = req.body;

    // Validate required fields
    if (!nombre || !email || !mensaje) {
      return res.status(400).json({
        success: false,
        error: 'Campos requeridos: nombre, email, mensaje'
      });
    }

    // In production, this would insert to database
    // For prototype, just return success
    res.json({
      success: true,
      message: 'Mensaje recibido correctamente',
      data: {
        id: Math.floor(Math.random() * 10000),
        timestamp: new Date().toISOString()
      }
    });
  } catch (error) {
    console.error('Error processing contact form:', error);
    res.status(500).json({
      success: false,
      error: 'Error al procesar el formulario'
    });
  }
});

// 404 handler
app.use((req, res) => {
  res.status(404).json({
    success: false,
    error: 'Endpoint no encontrado',
    path: req.path
  });
});

// Error handler
app.use((err, req, res, next) => {
  console.error('Server error:', err);
  res.status(500).json({
    success: false,
    error: 'Error interno del servidor'
  });
});

// Start server
app.listen(PORT, () => {
  console.log(`
╔════════════════════════════════════════╗
║   AseguraloCR Backend API              ║
║   Node.js + Express                    ║
╠════════════════════════════════════════╣
║   🚀 Server running on port ${PORT}      ║
║   🌍 Environment: ${process.env.NODE_ENV || 'development'}         ║
║   📊 Database: ${process.env.DB_NAME || 'Not configured'}    ║
╚════════════════════════════════════════╝
  `);
});

// Graceful shutdown
process.on('SIGTERM', async () => {
  console.log('SIGTERM received, closing server...');
  await pool.end();
  process.exit(0);
});

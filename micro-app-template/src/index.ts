import express, { Request, Response, NextFunction } from 'express';
import cors from 'cors';
import axios from 'axios';
import jwt from 'jsonwebtoken';
import dotenv from 'dotenv';

dotenv.config();

const app = express();
const PORT = process.env.API_PORT || 3000;

// CORS Configuration - Allow WordPress to call this API
const corsOptions = {
  origin: (origin: string | undefined, callback: Function) => {
    const allowedOrigins = (process.env.ALLOWED_ORIGINS || '').split(',').map(o => o.trim());
    
    // Allow requests with no origin (like mobile apps or curl)
    if (!origin || allowedOrigins.includes(origin)) {
      callback(null, true);
    } else {
      callback(new Error(`Origin ${origin} not allowed by CORS`));
    }
  },
  credentials: true,
  methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
  allowedHeaders: ['Content-Type', 'Authorization', 'X-Requested-With']
};

app.use(cors(corsOptions));
app.use(express.json());

// Health check endpoint
app.get('/health', (req: Request, res: Response) => {
  res.json({
    status: 'healthy',
    timestamp: new Date().toISOString(),
    service: 'octane-micro-app',
    wordpress: {
      connected: !!process.env.WORDPRESS_API_URL,
      graphql: !!process.env.WORDPRESS_GRAPHQL_URL
    }
  });
});

// WordPress GraphQL proxy endpoint
app.post('/api/wordpress/graphql', async (req: Request, res: Response) => {
  try {
    const { query, variables } = req.body;
    
    const response = await axios.post(
      process.env.WORDPRESS_GRAPHQL_URL || 'http://headless-wp:80/graphql',
      { query, variables },
      {
        headers: {
          'Content-Type': 'application/json',
          // Forward auth token if present
          ...(req.headers.authorization && {
            Authorization: req.headers.authorization
          })
        }
      }
    );
    
    res.json(response.data);
  } catch (error: any) {
    console.error('WordPress GraphQL Error:', error.message);
    res.status(error.response?.status || 500).json({
      error: 'Failed to query WordPress',
      message: error.message
    });
  }
});

// Example: Scoped Transition API
// This endpoint demonstrates how to create a scoped API with JWT
app.post('/api/transition/:room', async (req: Request, res: Response) => {
  try {
    const { room } = req.params;
    const { userId, permissions } = req.body;
    
    // Verify the user has permission for this "room"
    // In production, validate against WordPress or SSO
    
    // Create a scoped JWT token for this transition
    const token = jwt.sign(
      {
        userId,
        room,
        permissions,
        exp: Math.floor(Date.now() / 1000) + (60 * 60) // 1 hour
      },
      process.env.JWT_SECRET || 'change-this-secret',
      { algorithm: 'HS256' }
    );
    
    res.json({
      success: true,
      room,
      token,
      expiresIn: 3600
    });
  } catch (error: any) {
    res.status(500).json({
      error: 'Transition failed',
      message: error.message
    });
  }
});

// JWT verification middleware
const verifyToken = (req: Request, res: Response, next: NextFunction) => {
  const token = req.headers.authorization?.replace('Bearer ', '');
  
  if (!token) {
    return res.status(401).json({ error: 'No token provided' });
  }
  
  try {
    const decoded = jwt.verify(token, process.env.JWT_SECRET || 'change-this-secret');
    (req as any).user = decoded;
    next();
  } catch (error) {
    return res.status(401).json({ error: 'Invalid token' });
  }
};

// Protected endpoint example
app.get('/api/protected/data', verifyToken, (req: Request, res: Response) => {
  const user = (req as any).user;
  
  res.json({
    message: 'This is protected data',
    user: user,
    data: {
      // Your scoped data based on user permissions
    }
  });
});

// Wasm app metadata endpoint
app.get('/api/apps', (req: Request, res: Response) => {
  res.json({
    apps: [
      {
        id: 'app-1',
        name: 'Example Wasm App',
        version: '1.0.0',
        wasmUrl: '/wasm/app1.wasm',
        entry: 'main',
        permissions: ['read', 'write']
      }
    ]
  });
});

// Error handling middleware
app.use((err: Error, req: Request, res: Response, next: NextFunction) => {
  console.error('Error:', err);
  res.status(500).json({
    error: 'Internal server error',
    message: err.message
  });
});

// Start server
app.listen(PORT, () => {
  console.log(`🚀 Octane Micro-App API running on port ${PORT}`);
  console.log(`📡 WordPress GraphQL: ${process.env.WORDPRESS_GRAPHQL_URL}`);
  console.log(`🔐 CORS enabled for: ${process.env.ALLOWED_ORIGINS}`);
});

export default app;

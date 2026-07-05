import mysql from 'mysql2/promise';
import dotenv from 'dotenv';

dotenv.config();

const pool = mysql.createPool({
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASS || '',
  database: process.env.DB_NAME || 'nutrack_db',
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0,
  enableKeepAlive: true
});

export async function getConnection() {
  return await pool.getConnection();
}

export async function query(sql, values) {
  let connection;
  try {
    connection = await pool.getConnection();
    const [results] = await connection.execute(sql, values);
    return results;
  } finally {
    if (connection) await connection.end();
  }
}

export default pool;

const http = require('http');
const mysql = require('mysql'); 

// Configure your database connection parameters
const dbConnection = mysql.createConnection({
    host: 'herogu.garageisep.com',
    user: 'yk3Ve7Rsfs_app_g7b',      // replace with your DB username
    password: 'TIEqEsHLHXvj8z2z',  // replace with your DB password
    database: 'C0fg5IDZ3Q_app_g7b'   // replace with your database name
  });
  
  // Connect to the database
  dbConnection.connect((err) => {
    if (err) {
      console.error('Error connecting to the database:', err);
      process.exit(1);
    }
    console.log('Connected to the database.');
  });
  
  // Create a simple HTTP server
  const server = http.createServer((req, res) => {
    // Only handle GET requests to the /api/housing endpoint
    if (req.url === '/api/housing' && req.method === 'GET') {
      // Query the housing table
      dbConnection.query('SELECT * FROM housing', (error, results) => {
        if (error) {
          res.writeHead(500, {'Content-Type': 'application/json'});
          res.end(JSON.stringify({ error: 'Database query error' }));
          return;
        }
        
        // Send results as JSON
        res.writeHead(200, {'Content-Type': 'application/json'});
        res.end(JSON.stringify(results));
      });
    } else {
      // Simple handling for other requests (like a static file or error response)
      res.writeHead(404, {'Content-Type': 'text/plain'});
      res.end('Not found');
    }
  });
  
  // Define the port and start the server
  const PORT = 3000;
  server.listen(PORT, () => {
    console.log(`Server is listening on port ${PORT}`);
  });
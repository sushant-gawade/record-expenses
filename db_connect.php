<?php
// Define database credentials
define('DB_SERVER', 'localhost'); // Usually 'localhost' for local development
define('DB_USERNAME', 'root');    // Your MySQL username
define('DB_PASSWORD', '');        // Your MySQL password (often blank for XAMPP/MAMP)
define('DB_NAME', 'account_manager_db');

/* Attempt to connect to MySQL database using the mysqli object-oriented style */
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($conn->connect_error){
    // If connection fails, stop script execution and display an error
    die("ERROR: Could not connect to the database. " . $conn->connect_error);
}

// Optional: Set character set for proper data handling
$conn->set_charset("utf8mb4");

// The variable $conn is now the active database connection object.
?>
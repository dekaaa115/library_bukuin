<?php
    /*
    * Database Configuration File
    *
    * This file contains the settings for connecting to the database.
    * Storing this information in a separate file is a good security practice.
    */

    // --- Database Credentials ---
    define('DB_SERVER', 'localhost');
    define('DB_USERNAME', 'root');
    define('DB_PASSWORD', ''); // Default XAMPP password is empty
    define('DB_NAME', 'library_bukuin');
    define('DB_PORT', 3306); // <-- PORT BARU ANDA

    // --- Attempt to connect to the MySQL database ---
    $link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT); // <-- PERUBAHAN DI SINI
    // --- Check the connection ---
    if($link === false){
        // If connection fails, stop the script and display an error message.
        die("ERROR: Could not connect. " . mysqli_connect_error());
    }
    ?>
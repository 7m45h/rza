<?php
include "cors.php";
cors();

$DB_URL = getenv("DBURI");

$conn = new PDO($DB_URL);
if (!$conn) {
    die("db connection error: " . pg_last_error());
}

$conn->exec(
    "CREATE TABLE IF NOT EXISTS users (uname TEXT UNIQUE NOT NULL, pass TEXT NOT NULL, token TEXT)"
);

$conn->exec(
    "CREATE TABLE IF NOT EXISTS items (id INT UNIQUE NOT NULL, title TEXT NOT NULL, category TEXT NOT NULL, status TEXT NOT NULL, note TEXT NOT NULL, owner TEXT NOT NULL)"
);

?>

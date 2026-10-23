<?php

$server_name = "localhost";
$user_name = "root";
$pass = "";
$db = "school_db";

$conn = mysqli_connect($server_name,$user_name,$pass,$db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

?>



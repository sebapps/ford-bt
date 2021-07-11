<?php

session_start();

$db_server = "{DBSERVER}";
$db_username = "{DBUSERNAME}";
$db_password = "{DBPASSWORD}";
$db_database = "{DBNAME}";

$mysql = mysqli_connect($db_server, $db_username, $db_password, $db_database);

// Hard code the user as well, for the competition
$username = "{user}";
$username_db = "{user}";

if(isset($_SESSION['username_database']))
    $username_db = $_SESSION['username_database'];
    
?>
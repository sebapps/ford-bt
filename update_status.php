<?php

include("functions.php");
$status = $_POST['status'];

$json = update_business_trip_status($status);
echo $json;

?>
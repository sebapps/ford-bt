<?php

include("functions.php");
$month = $_POST['month'];
$year = $_POST['year'];

$json = get_filtered_expenses($month, $year);
echo $json;

?>
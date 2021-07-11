<?php

include("functions.php");

$month = $_GET['m'];
$year = $_GET['y'];

// Headings and rows
$headings = array('Trip #','Start Date Time','Start Mile','Start Fuel','End Date Time','End Mile','End Fuel','Total Miles'.'Total Fuel');

// Get the data
$expenses_array = get_expenses_array($month, $year);

// Open the output stream
$fh = fopen('php://output', 'w');
        
// Start output buffering (to capture stream contents)
ob_start();
        
fputcsv($fh, $headings);
        
// Loop over the * to export
if (!empty($expenses_array)) {
	foreach ($expenses_array as $item) {
		fputcsv($fh, $item);
	}
}
        
// Get the contents of the output buffer
$string = ob_get_clean();
        
$filename = 'expenses_' . date('Ymd') .'_' . date('His');
        
// Output CSV-specific headers
header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private",false);
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=$filename.csv" );
header("Content-Transfer-Encoding: binary");

exit($string);
?>
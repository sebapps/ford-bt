<?php

include("db.php");

// Hard-code the timezone for EST
date_default_timezone_set("America/New_York");

// Returns the access token used to make calls to the API
function get_access_token() {
    
    global $mysql;
    global $username;
    
    // Do we have an access token?
    $code = "";
    $access_token = "";
    $access_token_expire = "";
    $refresh_token = "";
    $refresh_token_expire = "";
    $rightnow = date("Y-m-d H:i:s", strtotime("now"));
    
    $query = "SELECT code, access_token, access_token_expire, refresh_token, refresh_token_expire FROM users WHERE username = '$username'";
    $result = mysqli_query($mysql, $query);
    
    $num_rows = mysqli_num_rows($result);
    if($num_rows > 0) {
        $row = mysqli_fetch_array($result);
        $code = $row['code'];
        $access_token = $row['access_token'];
        $access_token_expire = $row['access_token_expire'];
        $refresh_token = $row['refresh_token'];
        $refresh_token_expire = $row['refresh_token_expire'];
    }
    
    // If we do NOT have an access token, OR the refresh token expired, get both tokens
    if($access_token == "" || ($refresh_token_expire != "" && $rightnow > $refresh_token_expire)) {
        
        // Access tokens expire in 20 minutes - set it to 16 minutes in future
        $access_token_expire = date("Y-m-d H:i:s", strtotime("+16 minutes"));
        
        // Refresh tokens expire in 90 days - set it to 85 days
        $refresh_token_expire = date("Y-m-d H:i:s", strtotime("+85 days"));
        
        $command = 'curl -d "grant_type=authorization_code&client_id={client_id}&client_secret={client_secret}&code='.$code.'" -H "Content-Type: application/x-www-form-urlencoded" -X POST https://dah2vb2cprod.b2clogin.com/914d88b1-3523-4bf6-9be4-1b96b4f6f919/oauth2/v2.0/token?p=B2C_1A_signup_signin_common';

        $response = shell_exec($command);
        $json = json_decode($response);

        if(isset($json->access_token))
            $access_token = $json->access_token;
        
        if(isset($json->refresh_token))
            $refresh_token = $json->refresh_token;
            
        $query = "UPDATE users SET access_token = '$access_token', access_token_expire = '$access_token_expire', refresh_token = '$refresh_token', refresh_token_expire = '$refresh_token_expire' WHERE username = '$username'";

        $result = mysqli_query($mysql, $query);
    }
    else {
        // If we have an access token, but it expired, get a refresh token
        if($access_token_expire != "" && $rightnow > $access_token_expire) {
        
            // Access tokens expire in 20 minutes - set it to 16 minutes in future
            $access_token_expire = date("Y-m-d H:i:s", strtotime("+16 minutes"));
        
            $command = 'curl -d "grant_type=refresh_token&refresh_token='.$refresh_token.'&client_id={client_id}&client_secret={client_secret}" -H "Content-Type: application/x-www-form-urlencoded" -X POST https://dah2vb2cprod.b2clogin.com/914d88b1-3523-4bf6-9be4-1b96b4f6f919/oauth2/v2.0/token?p=B2C_1A_signup_signin_common';
        
            $response = shell_exec($command);
            $json = json_decode($response);
        
            if(isset($json->access_token))
                $access_token = $json->access_token;
            
            $query = "UPDATE users SET access_token = '$access_token', access_token_expire = '$access_token_expire' WHERE username = '$username'";
            $result = mysqli_query($mysql, $query);
        }
    }
    return $access_token;
}

// Returns the vehicle ID belonging to the user
function get_vehicle_id() {
    
    global $mysql;
    global $username;
    global $username_db;
    
    $vehicle_id = "";
    $vehicle_image = "";
    $query = "SELECT ford_vehicle_id, vehicle_image FROM vehicles WHERE username = '$username_db'";
    $result = mysqli_query($mysql, $query);
    $num_rows = mysqli_num_rows($result);
    
    if($num_rows > 0) {
        $row = mysqli_fetch_array($result);
        $vehicle_id = $row['ford_vehicle_id'];
        $vehicle_image = $row['vehicle_image'];
    }
    
    // If there are no vehicles, look up the first one and add it
    if($vehicle_id == "") {
        
        $access_token = get_access_token();
        $command = 'curl -H "Accept: application/json" -H "Content-Type: application/json" -H "api-version: {api_version}" -H "Application-Id: {application_id}" -H "Authorization: Bearer '.$access_token.'" https://api.mps.ford.com/api/fordconnect/vehicles/v1';

        $response = shell_exec($command);
        $json = json_decode($response);

        if(isset($json->vehicles)) {
            $vehicles = array();
            foreach($json->vehicles as $vehicle)
                $vehicles[] = $vehicle->vehicleId;
                
            // For testing purposes, use the first one
            $vehicle_id = $vehicles[0];
            
            $query = "INSERT INTO vehicles (username, ford_vehicle_id, vehicle_image) VALUES ('$username_db', '$vehicle_id', 'https://mkpuertorico.com/ford/images/ford_thumb.png')";
            $result = mysqli_query($mysql, $query);
        }
    }
    /*if($vehicle_image == "") {
        $access_token = get_access_token();
        $command = 'curl -H "Accept: application/json" -H "Content-Type: application/json" -H "api-version: {api_version}" -H "Application-Id: {application_id}" -H "Authorization: Bearer '.$access_token.'" https://api.mps.ford.com/api/fordconnect/vehicles/v1/'.$vehicle_id.'/images/thumbnail?make=Ford&model=&year=2019';

        $response = shell_exec($command);
        echo $response; exit;
        $json = json_decode($response);
        print_r($json); exit;

        if(isset($json->vehicles)) {
            $vehicles = array();
            foreach($json->vehicles as $vehicle)
                $vehicles[] = $vehicle->vehicleId;
                
            // For testing purposes, use the first one
            $vehicle_id = $vehicles[0];
            
            $query = "INSERT INTO vehicles (username, ford_vehicle_id) VALUES ('$username_db', '$vehicle_id')";
            $result = mysqli_query($mysql, $query);
        }
    }*/
    return $vehicle_id;
}

// Returns the vehicle image
function get_vehicle_image() {
    global $mysql;
    global $username;
    global $username_db;
    
    $vehicle_image = "";
    $query = "SELECT vehicle_image FROM vehicles WHERE username = '$username_db'";
    $result = mysqli_query($mysql, $query);
    $num_rows = mysqli_num_rows($result);
    
    if($num_rows > 0) {
        $row = mysqli_fetch_array($result);
        $vehicle_image = $row['vehicle_image'];
    }
    else
        return "https://mkpuertorico.com/ford/images/ford_thumb.png";
        
    return $vehicle_image;
}

// Returns the vehicle status JSON object
function get_status() {
    
    // Get the vehicle ID
    $vehicle_id = get_vehicle_id();
    
    // Get the access token
    $access_token = get_access_token();
    
    $command = 'curl -H "Application-Id: {application_id}" -H "api-version: {api_version}" -H "Authorization: Bearer '.$access_token.'" https://api.mps.ford.com/api/fordconnect/vehicles/v1/'.$vehicle_id;
    
    $response = shell_exec($command);
    //$json = json_decode($response);
    
    return $response;
    
}

// Returns the vehicle location JSON object
function get_location() {
    
    // Get the vehicle ID
    $vehicle_id = get_vehicle_id();
    
    // Get the access token
    $access_token = get_access_token();
    
    $command = 'curl -H "Application-Id: {application_id}" -H "api-version: {api_version}" -H "Authorization: Bearer '.$access_token.'" https://api.mps.ford.com/api/fordconnect/vehicles/v1/'.$vehicle_id.'/location';
    
    $response = shell_exec($command);
    //$json = json_decode($response);
    
    return $response;
}

// Updates the vehicle location cache and then returns its location's JSON object
function refresh_location() {
    
    // Get the vehicle ID
    $vehicle_id = get_vehicle_id();
    
    // Get the access token
    $access_token = get_access_token();
    
    $command = 'curl -H "Application-Id: {application_id}" -H "api-version: {api_version}" -H "Authorization: Bearer '.$access_token.'" -X POST https://api.mps.ford.com/api/fordconnect/vehicles/v1/'.$vehicle_id.'/location';
    
    $response = shell_exec($command);
    $json = json_decode($response);
    
    // Now that we POSTED the call, we can get the updated status
    $json = get_location();
    return $json;
}

// Gets the current business trip status for the user
function get_current_business_trip_status() {
	global $mysql;
    global $username;
    global $username_db;
	
	$json = '{"status" : "<<STATUS>>", "trip_status" : "<<TRIP_STATUS>>"}';
	
	// Get the status from the database
	$query = "SELECT trip_status FROM trip_status WHERE username = '$username_db'";
	$result = mysqli_query($mysql, $query);
    $num_rows = mysqli_num_rows($result);
    
    if($num_rows > 0) {
        $row = mysqli_fetch_array($result);
        $trip_status = $row['trip_status']=="y"?"Active":"Not Active";
		$json = str_replace("<<STATUS>>", "SUCCESS", str_replace("<<TRIP_STATUS>>", $trip_status, $json));
    }
    else {
		$json = str_replace("<<STATUS>>", "SUCCESS", str_replace("<<TRIP_STATUS>>", "Not Active", $json));
	}
	return $json;
}

// Updates the business trip status and adds the information to the user's trips
function update_business_trip_status($status) {
	global $mysql;
    global $username;
    global $username_db;
	
	$json = '{"status" : "<<STATUS>>", "trip_status" : "<<TRIP_STATUS>>", "latitude" : "<<LAT>>", "longitude" : "<<LON>>"}';
	
	// Get the ID of the user's trip item
	$query = "SELECT trip_statusid FROM trip_status WHERE username = '$username_db'";
	$result = mysqli_query($mysql, $query);
    $num_rows = mysqli_num_rows($result);
    
    if($num_rows == 0) {
		// Add the row
        $query = "INSERT INTO trip_status(username, trip_status) VALUES ('$username_db', '$status')";
		$result = mysqli_query($mysql, $query);
    }
	else {
		// Update the row
		$row = mysqli_fetch_array($result);
		$trip_statusid = $row['trip_statusid'];
		$query = "UPDATE trip_status SET trip_status = '$status' WHERE username = '$username_db'";
		$result = mysqli_query($mysql, $query);
	}
	
	// For the actual trips, if this is an "end", close out any user trips that are open.
	// If it is a "start", add a new entry to the table.
	$vehicle_id = get_vehicle_id();
	$json_status = json_decode(get_status());
	$now = date("Y-m-d H:i:s");
	
	if($status == "y") {
		$query = "INSERT INTO trips (username, startdatetime, startfuellevel, startmileage, vehicle_id) VALUES ('$username_db', '$now', '".$json_status->vehicle->vehicleDetails->fuelLevel->value."', '".$json_status->vehicle->vehicleDetails->mileage."', '$vehicle_id')";
		$result = mysqli_query($mysql, $query);
	}
	else {
		// Close out any trips that are 'open'
		$query = "SELECT trip_id FROM trips WHERE trip_status = 'o' AND username = '$username_db' AND vehicle_id = '$vehicle_id'";
		$result = mysqli_query($mysql, $query);
		$num_rows = mysqli_num_rows($result);
		if($num_rows > 0) {
			while($row = mysqli_fetch_array($result)) {
				$trip_id = $row['trip_id'];
				$query = "UPDATE trips SET trip_status = 'c', enddatetime = '$now', endfuellevel = '".$json_status->vehicle->vehicleDetails->fuelLevel->value."', endmileage = '".$json_status->vehicle->vehicleDetails->mileage."' WHERE trip_id = '$trip_id'";
				mysqli_query($mysql, $query);
			}
		}
	}
	
	$trip_status = $status=="y"?"Active":"Not Active";
	$json = str_replace("<<STATUS>>", "SUCCESS", str_replace("<<TRIP_STATUS>>", $trip_status, $json));
	$json = str_replace("<<LAT>>", $json_status->vehicle->vehicleLocation->latitude, $json);
	$json = str_replace("<<LON>>", $json_status->vehicle->vehicleLocation->longitude, $json);
	return $json;
}

// Get the user's filtered expenses
function get_filtered_expenses($month, $year) {
	global $mysql;
    global $username;
    global $username_db;
	
	$json = '{"status" : "<<STATUS>>", "table_html" : "<<TABLE_HTML>>"}';
	$table_html = "<table id='expenses-table'><tr><th>Trip #</th><th>Start Date Time</th><th>Start Mile</th><th>Start Fuel</th><th>End Date Time</th><th>End Mile</th><th>End Fuel</th><th>Total Miles</th><th>Total Fuel</th></tr><<ITEMS>></table>";
	
	// Get the trips
	$vehicle_id = get_vehicle_id();
	if($month == 0 && $year == 0)
		$query = "SELECT * FROM trips WHERE username = '$username_db' AND vehicle_id = '$vehicle_id'";
	else {
		if($month == 0) {
			$start_date = $year."-01-01";
			$end_date = $year."-12-31";
		}
		else {
			$start_date = $year."-".$month."-01";
			$end_date = date("Y-m-t", strtotime($start_date));
		}
		$query = "SELECT * FROM trips WHERE username = '$username_db' AND vehicle_id = '$vehicle_id' AND startdatetime >= '$start_date' AND enddatetime <= '$end_date'";
	}
	$result = mysqli_query($mysql, $query);
	$num_rows = mysqli_num_rows($result);
	
	if($num_rows == 0) {
		$table_html = str_replace("<<ITEMS>>", "<tr><td style='text-align:center' colspan='9'>No trips match the criteria.</td><tr>", $table_html);
	}
	else {
		$table_items = "";
		$counter = 1;
		while($row = mysqli_fetch_array($result)) {
			$table_items.= "<tr><td>".$counter."</td>";
			$table_items.= "<td>".$row['startdatetime']."</td>";
			$table_items.= "<td>".$row['startmileage']."</td>";
			$table_items.= "<td>".$row['startfuellevel']."</td>";
			$table_items.= "<td>".$row['enddatetime']."</td>";
			$table_items.= "<td>".$row['endmileage']."</td>";
			$table_items.= "<td>".$row['endfuellevel']."</td>";
			$table_items.= "<td>".number_format(($row['endmileage'] - $row['startmileage']), 2)."</td>";
			$table_items.= "<td>".number_format(($row['startfuellevel'] - $row['endfuellevel']), 2)."</td><tr>";
			$counter++;
		}
		$table_html = str_replace("<<ITEMS>>", $table_items, $table_html);
	}
	
	$json = str_replace("<<STATUS>>", "SUCCESS", str_replace("<<TABLE_HTML>>", $table_html, $json));
	return $json;
}

function get_expenses_array($month, $year) {
	global $mysql;
    global $username;
    global $username_db;
	
	// Get the trips
	$trips = array();
	$vehicle_id = get_vehicle_id();
	if($month == 0 && $year == 0)
		$query = "SELECT * FROM trips WHERE username = '$username_db' AND vehicle_id = '$vehicle_id'";
	else {
		if($month == 0) {
			$start_date = $year."-01-01";
			$end_date = $year."-12-31";
		}
		else {
			$start_date = $year."-".$month."-01";
			$end_date = date("Y-m-t", strtotime($start_date));
		}
		$query = "SELECT * FROM trips WHERE username = '$username_db' AND vehicle_id = '$vehicle_id' AND startdatetime >= '$start_date' AND enddatetime <= '$end_date'";
	}
	$result = mysqli_query($mysql, $query);
	$num_rows = mysqli_num_rows($result);
	
	if($num_rows == 0) {
		$trips[] = array("No trips match the criteria.");
	}
	else {
		$counter = 1;
		while($row = mysqli_fetch_array($result)) {
			$trip = array();
			$trip[] = $counter;
			$trip[] = $row['startdatetime'];
			$trip[] = $row['startmileage'];
			$trip[] = $row['startfuellevel'];
			$trip[] = $row['enddatetime'];
			$trip[] = $row['endmileage'];
			$trip[] = $row['endfuellevel'];
			$trip[] = number_format(($row['endmileage'] - $row['startmileage']), 2);
			$trip[] = number_format(($row['startfuellevel'] - $row['endfuellevel']), 2);
			$trips[] = $trip;
			$counter++;
		}
	}
	
	return $trips;
}

?>
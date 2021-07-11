<?php
session_start();
if(!isset($_SESSION['username_database']))
    header("location: username.php");
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Ford BT | Ford Connect Hackathon</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
        <link rel="shortcut icon" type="image/x-icon" href="images/favicon.png" />
        <link rel="stylesheet" href="css/bootstrap/bootstrap.min.css">
        <link rel="stylesheet" href="css/themify-icons/themify-icons.css">
        <link rel="stylesheet" href="css/slick/slick.css">
        <link rel="stylesheet" href="css/slick/slick-theme.css">
        <link rel="stylesheet" href="css/fancybox/jquery.fancybox.min.css">
        <link rel="stylesheet" href="css/aos/aos.css">
        <link href="css/style.css?id=<?php echo date("YmdHis"); ?>" rel="stylesheet">
    </head>
    <body class="body-wrapper" data-spy="scroll" data-target=".privacy-nav">
      
        <nav class="navbar main-nav navbar-expand-lg px-2 px-sm-0 py-2 py-lg-0">
          <div class="container">
            <a class="navbar-brand" href="index.html"><img id="logo" src="images/ford_bt_logo.png" alt="logo"></a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"
              aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
              <span class="ti-menu"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
              <ul class="navbar-nav ml-auto">
                <li class="nav-item home">
                  <a class="nav-link" href="#" onclick="show_home_panel();">Home</a>
                </li>

                <li class="nav-item expenses">
                  <a class="nav-link" href="#" onclick="show_expenses_panel();">Expenses</a>
                </li>
                
                <li class="nav-item logout">
                  <a class="nav-link" href="logout.php">LOGOUT</a>
                </li>
                
              </ul>
            </div>
          </div>
        </nav>
        
        <div id="expenses-holder">
           <strong>Expenses</strong><br/>
		   <div id="filter">
				<span id="month-span">Search by Month: 
					<select id="month" onchange="filter_expenses();">
						<option value="0">All</option>
						<option value="01">January</option>
						<option value="02">February</option>
						<option value="03">March</option>
						<option value="04">April</option>
						<option value="05">May</option>
						<option value="06">June</option>
						<option value="07">July</option>
						<option value="08">August</option>
						<option value="09">September</option>
						<option value="10">October</option>
						<option value="11">November</option>
						<option value="12">December</option>
					</select>
				</span>
				<span id="year-span">Search by Year:
					<select id="year" onchange="filter_expenses();">
						<?php $y = date("Y"); for($i=$y;$i>=($y-20);$i--) echo '<option value="'.$i.'">'.$i.'</option>'.PHP_EOL;  ?>
					</select>
				</span>
				<button id="export-button" onclick="export_expenses();">Export</button>
		   </div>
		   <div id="expenses"></div>
        </div>
		
		<div id="infobar">
            <div id="infobar-left">Business Trip Status: <span id="expense_status"></span></div><div id="infobar-right" style="margin-left:10px"><span onclick="center_on_vehicle();" class="cursor">Center on Car</span></div>
        </div>
            
        <div id="map-holder">
            <!-- MAP -->
            <div id="map"></div>
			
			<!-- BUSINESS TRIP PANEL -->
			<div id="business-trip-panel">
				Click below to toggle your business trip status:<br/>
				<button id="business-button-green" onclick="activate_trip();">ACTIVATE</button>
				<button id="business-button-red" onclick="deactivate_trip();">DEACTIVATE</button>
			</div>
            
            <!-- NOTIFICATION -->
            <div id="notification">
			
                <span class="notification_message" id="geolocation-activating"><br/>Please wait while geolocation is activated...<br/>Remember to allow GPS positioning if prompted.
                </span>
                
				<span class="notification_message" id="vehicle-location-in-progress"><br/>Please wait while the vehicle is located....
                </span>
                
                <span class="notification_message" id="expenses-in-progress"><br/>Please wait while the expenses information is loaded....
                </span>
                
            </div>
        </div>
        
        <script src="js/jquery.min.js"></script>
        <script src="js/bootstrap.min.js"></script>
        <script src="js/slick.min.js"></script>
        <script src="js/jquery.fancybox.min.js"></script>
        <script src="js/jquery.syotimer.min.js"></script>
        <script src="js/aos.js"></script>
        <script src="js/script.js"></script>
        <script type="text/javascript">
		var home_shown = true;
        let map;
        let auto_marker;
        let auto_coords;
        var auto_image = "";
        $(document).ready(function(){
            // First step - geolocate vehicle
            geolocate_vehicle();
			// Next, get the current trip status
			get_current_business_trip_status();
        });
        function initMap() {
            map = new google.maps.Map(document.getElementById("map"), {
                center: { lat: 37.090, lng: -95.712 },
                zoom: 2,
            });
            map.addListener('click', function(e) {
                // Map click handler...
            });
        }
        // Geolocation
        var geolocation_options = {
            enableHighAccuracy: true,
            timeout: 5000,
            maximumAge: 0
        };
        function geolocate_vehicle() {
            $(".notification_message").hide();
            $("#vehicle-location-in-progress").show();
            $("#notification").css("opacity", "0.6");
            $("#notification").fadeIn(400);
            
            if(auto_image == "") {
                $.post("vehicle_image.php").done(function(data) {
                    auto_image = data;
                    // Find the current GPS of the vehicle
                    $.post("status.php").done(function(data){
                        if(data != "") {
                            var json = JSON.parse(data);
                            if(json.status == "SUCCESS") {
                                auto_coords = new Object();
                                auto_coords.latitude = json.vehicle.vehicleLocation.latitude;
                                auto_coords.longitude = json.vehicle.vehicleLocation.longitude;
                                var center = new google.maps.LatLng(auto_coords.latitude, auto_coords.longitude);
                                map.panTo(center);
                                map.setZoom(15);
                                // Drop a pin
								if(auto_marker)
									auto_marker.setMap(null);
                                auto_marker = new google.maps.Marker({
                                    position: center,
                                    map,
                                    title: "Current Vehicle Location",
                                    icon: auto_image
                                });
                                $("#notification").fadeOut(400);
								if(home_shown) {
									if(!$("#infobar").is(":visible"))
										$("#infobar").slideDown(400);
									if(!$("#business-trip-panel").is(":visible"))
										$("#business-trip-panel").fadeIn(400);
								}
                            }
                        }
                    });
                });
            }
            else {
                // Find the current GPS of the vehicle
                $.post("status.php").done(function(data){
                    if(data != "") {
                        var json = JSON.parse(data);
                        if(json.status == "SUCCESS") {
                            auto_coords = new Object();
                            auto_coords.latitude = json.vehicle.vehicleLocation.latitude;
                            auto_coords.longitude = json.vehicle.vehicleLocation.longitude;
                            var center = new google.maps.LatLng(auto_coords.latitude, auto_coords.longitude);
                            map.panTo(center);
                            map.setZoom(15);
                            // Drop a pin
							if(auto_marker)
								auto_marker.setMap(null);
                            auto_marker = new google.maps.Marker({
                                position: center,
                                map,
                                title: "Current Vehicle Location",
                                icon: auto_image
                            });
                            $("#notification").fadeOut(400);
							if(home_shown) {
								if(!$("#infobar").is(":visible"))
									$("#infobar").slideDown(400);
								if(!$("#business-trip-panel").is(":visible"))
									$("#business-trip-panel").fadeIn(400);
							}
                        }
                    }
                });
            }
        }
		
		// Recenter the map on the vehicle 
        function center_on_vehicle() {
            var center = new google.maps.LatLng(auto_coords.latitude, auto_coords.longitude);
            map.panTo(center);
            map.setZoom(15);
        }
		
		// Get the current business trip status for the user
		function get_current_business_trip_status() {
			$.post("current_business_trip_status.php").done(function(data){
				update_business_trip_UI(data);
			});
		}
		
		// Activates the trip
		function activate_trip() {
			$.post("update_status.php", {status: 'y'}).done(function(data){
				update_business_trip_UI(data);
			});
		}
		
		// Deactivates the trip
		function deactivate_trip() {
			$.post("update_status.php", {status: 'n'}).done(function(data){
				update_business_trip_UI(data);
			});
		}
		
		// Update the UI for the business trips
		function update_business_trip_UI(data) {
			if(data != "") {
				var json = JSON.parse(data);
				if(json.status == "SUCCESS") {
					if(json.trip_status == "Active") {
						$("#business-button-green").hide();
						$("#business-button-red").show();
						$("#expense_status").html('<span style="color:green">'+json.trip_status+'</span>');
					}
					else {
						$("#business-button-green").show();
						$("#business-button-red").hide();
						$("#expense_status").html('<span style="color:red">'+json.trip_status+'</span>');
					}
					auto_coords = new Object();
                    auto_coords.latitude = json.latitude;
                    auto_coords.longitude = json.longitude;
                    var center = new google.maps.LatLng(auto_coords.latitude, auto_coords.longitude);
                    map.panTo(center);
                    map.setZoom(15);
                    // Drop a pin
					if(auto_marker)
						auto_marker.setMap(null);
                    auto_marker = new google.maps.Marker({
                        position: center,
                        map,
                        title: "Current Vehicle Location",
                        icon: auto_image
                    });
				}
			}
		}
        
        // Slide the home panel into view
        function show_home_panel() {
            if(!home_shown) {
                home_shown = true;
                $("#expenses-holder").animate({"left" : "-100%"}, function(){
                    $("#expenses-holder").hide();
                    $("#infobar").show();
                    $("#map-holder").show();
                    $("#infobar").animate({"left" : "0%"});
                    $("#map-holder").animate({"left" : "0%"}, function(){
                    });
                });
            }
        }
        
        // Slide the expenses panel into view
        function show_expenses_panel() {
            if(home_shown) {
                home_shown = false;
                $(".notification_message").hide();
                $("#expenses-in-progress").fadeIn(400);
                $("#notification").fadeIn(400, function(){
					$.post("filter_expenses.php", {month: 0, year: 0}).done(function(data){
						var json = JSON.parse(data);
                        if(json.status == "SUCCESS") {
							$("#expenses").html(json.table_html);
							$("#infobar").animate({"left" : "-100%"});					
							$("#map-holder").animate({"left" : "-100%"}, function(){
								$("#infobar").hide();
								$("#map-holder").hide();
								$("#notification").fadeOut(400);
								$("#expenses-holder").show();
								$("#expenses-holder").animate({"left" : "0%"});
							});
						}
					});
                });
            }
        }
		
		// Show all or some of the expenses
		function filter_expenses() {
			var month = $("#month").val();
			var year = $("#year").val();
			$.post("filter_expenses.php", {month: month, year: year}).done(function(data){
				var json = JSON.parse(data);
                if(json.status == "SUCCESS") {
					$("#expenses").fadeOut(400, function(){
						$("#expenses").html(json.table_html);
						$("#expenses").fadeIn(400);
					});
				}
			});
		}
		
		// Export the current expenses as a CSV
		function export_expenses() {
			var month = $("#month").val();
			var year = $("#year").val();
			window.open("export.php?m="+month+"&y="+year);
		}
        </script>
        <script src="https://maps.googleapis.com/maps/api/js?key={GOOLGE_MAPS_API_KEY}&callback=initMap&libraries=&v=weekly" async></script>
    </body>
</html>
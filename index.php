<!DOCTYPE html>
<html>
<head>
	<title>ReGen Gaming</title>
	<link rel="icon" type="image/x-icon" href="favicon.ico">
	<link href="https://fonts.cdnfonts.com/css/ubuntu-mono-2" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
	<img src="banner.png" alt="Banner">
	
	<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
		<h1>RGG CONVERTER</h1>
		<span class="error" id="error-message"></span>
		<span class="converted" id="converted-message"></span><br>
		<input type="text" name="rggusername" placeholder="RGG Username" required><br>
		<input type="text" name="username" placeholder="NGG Username" required><br>
		<input type="password" name="key" placeholder="Password" required><br>
		<input type="submit" value="Submit">
		
	</form>

	<?php
	session_start();
	include 'dbconfig.php';
	// Create connection
	$conn = new mysqli($servername, $username, $password, $dbname);
	
	// Check connection
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}
	if (!isset($_SESSION['failed_attempts'])) {
		$_SESSION['failed_attempts'] = 0;
	}
	// Check if form has been submitted
	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		// Get the input values from the form
		$ip = $_SERVER['REMOTE_ADDR'];
		$dateTime = date('[Y-m-d H:i:s]');
		$input_username = $_POST["username"];
		$input_key = $_POST["key"];
		$requested_name= $_POST["rggusername"];
		// Check if username and key are correct
		if (!strpos($input_username, '_')) {
			$_SESSION['success_message'] = '<script>document.getElementById("error-message").innerHTML = "Error: NGG Username must contain an underscore.";</script>';
		}
		elseif (!preg_match('/^[a-zA-Z_]{4,}$/', $requested_name)) {
			$_SESSION['success_message'] = '<script>document.getElementById("error-message").innerHTML = "Error: Invalid RGG Username. (Format: Firstname_Lastname)";</script>';
		} else {
			$sql = "SELECT * FROM nggaccounts WHERE Username = '$input_username'";
			$result1 = $conn->query($sql);
			$sql = "SELECT 1 FROM accounts WHERE Username = '$requested_name'";
			$result2 = $conn->query($sql);
		  
			if ($result1->num_rows > 0) {
				// If username is correct, concatenate the key with the salt column and hash it using Whirlpool
				$row = $result1->fetch_assoc();
				$salt = $row["Salt"];
				$converted = $row["Converted"];
				$hashed_key = hash('whirlpool', $input_key . $salt);
				$hashed_key = strtoupper($hashed_key);
				// Check if the hashed key matches the stored hash in the users table
				if ($hashed_key == $row["Key"]) {
					// If the key is correct, insert data from another table
					if ($converted == 0) {
						if ($result2->num_rows == 0) {
							
							$sql = "INSERT INTO `accounts`(`RegiDate`, `Username`, `Key`, `Salt`, `Email`, `IP`, `Registered`, `ConnectedTime`, `Sex`, `BirthDate`, `Age`, 
							`Level`, `Respect`, `Money`, `Bank`, `pHealth`, `pArmor`, `pSHealth`, `Int`, `VirtualWorld`, `Model`, `SPos_x`, `SPos_y`, `SPos_z`, `SPos_r`, 
							`Radio`, `UpgradePoints`, `Crimes`, `Accent`, `Arrested`, `Phonebook`, `Job`, `Job2`, `Paycheck`, `Materials`, `Crack`,  `DetSkill`, `SexSkill`, 
							`BoxSkill`, `LawSkill`, `MechSkill`, `TruckSkill`, `DrugsSkill`, `ArmsSkill`, `SmugglerSkill`, `FishSkill`, `FightingStyle`, `PhoneNr`, 
							`CarLic`, `FlyLic`, `BoatLic`, `PayDay`, `PayDayHad`, `Pin`, `CDPlayer`, `Dice`, `Spraycan`, `Tutorial`, `Hospital`, `Insurance`, `TaxiLicense`, 
							`Screwdriver`, `Smslog`, `Wristwatch`, `GPS`, `Heroin`, `Syringe`, `Skins`, `Job3`) SELECT `RegiDate`, '$requested_name', `Key`, `Salt`, `Email`, 
							`IP`, `Registered`, `ConnectedTime`, `Sex`, `BirthDate`, `Age`, `Level`, `Respect`, LEAST(`Money`/10, 1000000), LEAST(`Bank`/10, 10000000), `pHealth`, 
							`pArmor`, `pSHealth`, `Int`, `VirtualWorld`, `Model`, `SPos_x`, `SPos_y`, `SPos_z`, `SPos_r`, `Radio`, `UpgradePoints`, `Crimes`, `Accent`, `Arrested`, 
							`Phonebook`, `Job`, `Job2`, `Paycheck`, LEAST(`Materials`/10, 500000), `Crack`,  `DetSkill`, `SexSkill`, `BoxSkill`, `LawSkill`, `MechSkill`, `TruckSkill`, 
							`DrugsSkill`, `ArmsSkill`, `SmugglerSkill`, `FishSkill`, `FightingStyle`, `PhoneNr`, `CarLic`, `FlyLic`, `BoatLic`, `PayDay`, `PayDayHad`, `Pin`,
							`CDPlayer`, `Dice`, `Spraycan`, `Tutorial`, `Hospital`, `Insurance`, `TaxiLicense`, `Screwdriver`, `Smslog`, `Wristwatch`, `GPS`, `Heroin`, `Syringe`, 
							`Skins`, `Job3` FROM nggaccounts WHERE `Username`  = '$input_username';";
							$sql .= "UPDATE `nggaccounts` SET `Converted` = '1'  WHERE `Username`  = '$input_username';";
							if ($conn->multi_query($sql) === TRUE) {
								$_SESSION['success_message'] = '<script>document.getElementById("converted-message").innerHTML = "Account converted successfully!";</script>';
							}
						} else {
							$_SESSION['success_message'] = '<script>document.getElementById("error-message").innerHTML = "Error: Username already exist in RGG.";</script>';
						}
					} else {
						$_SESSION['success_message'] = '<script>document.getElementById("error-message").innerHTML = "Error: Account has already been converted.";</script>';
					}
					$_SESSION['failed_attempts'] = 0;
				} else {
					$_SESSION['failed_attempts']++;
					$remaining_attempts = 5 - $_SESSION['failed_attempts'];
					if ($_SESSION['failed_attempts'] > 5) {
						// Block the user from submitting the form
						die('<script>document.getElementById("error-message").innerHTML = "You have exceeded the maximum number of failed attempts. Please try again later.";</script>');
					} else {
						// Display an error message
						$_SESSION['success_message'] = '<script>document.getElementById("error-message").innerHTML = "Error: Incorrect password. You have '.$remaining_attempts.' attempts remaining.";</script>';
					}
				}
			} else {
				$_SESSION['success_message'] = '<script>document.getElementById("error-message").innerHTML = "Error: Incorrect username.";</script>';
			}
		}
		header('Location: ' . $_SERVER['PHP_SELF'], true, 303);
		exit;
	}
	if (isset($_SESSION['success_message'])) {
		echo '<p>' . $_SESSION['success_message'] . '</p>';
		unset($_SESSION['success_message']);
	}
	
	?>
</body>
</html>

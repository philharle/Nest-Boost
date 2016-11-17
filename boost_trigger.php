<?php
//Redirect after 1 second to Boost Control
header("refresh:1;url=boost_control.php");

//The Nest-Boost config
require_once(__DIR__ . '/resources/config.php');

//Connect to the Database
$con = new mysqli($hostname, $username, $password, $dbname);
if ($con->connect_error) {
    trigger_error('Database connection failed: ' . $con->connect_error, E_USER_ERROR);
}

$totalMins = mysqli_real_escape_string($con, $_POST['boostTime']);

$sql = "INSERT INTO `boost` (`startTime`, `totalMins`, `complete`) VALUES (CURRENT_TIMESTAMP, '$totalMins','n')";
$result = $con->query($sql);

echo "<b>You have requested Boost for $totalMins mins.</b><br>Please wait while we send this instruction to the Nest...";
    
//Close database connection
$con->close();
?>
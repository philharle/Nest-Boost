<?php
// The Nest-Extended Configuration
require_once(__DIR__ . '/resources/config.php');

//Connect to the Database
$con = mysqli_connect($hostname, $username, $password, $dbname);
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

$totalMins = mysqli_real_escape_string($con, $_POST['boostTime']);

$sql = "INSERT INTO `boost` (`startTime`, `totalMins`, `complete`) VALUES (CURRENT_TIMESTAMP, '$totalMins','n')";
$result = $con->query($sql);

echo "Boost activated for ";
    echo $totalMins;
echo " mins<br><br><hr>";

//Close mySQL DB connection
$con->close();

//Run boost_control.php
include('boost_control.php');
?>
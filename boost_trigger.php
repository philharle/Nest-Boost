<head>
  <link rel="shortcut icon" type="image/png" href="favicon.png" />
  <title>Nest-Boost</title>
  <style>
    body {
      font-family: calibri;
    }
  </style>
</head>

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

//If we are specifying an actual boostTime then insert a boost trigger into the database
if ($totalMins > 0)
{
    $sql = "INSERT INTO `boost` (`startTime`, `totalMins`, `complete`) VALUES (CURRENT_TIMESTAMP, '$totalMins','n')";
    $result = $con->query($sql);
    
    echo "<h3>Boost Management:</h3>";
    echo "<b>You have requested Boost for $totalMins mins.</b><br>Please wait while we send this instruction to the Nest...";
    }
else //We are specifying a boostTime of 0, so cancel the current boost by caclulating a new boostMins value from the current time
{
    $sql = "SELECT TIMESTAMPDIFF(MINUTE,(select startTime from boost where complete = 'n'),NOW()) AS newBoostMins";
    $result = $con->query($sql);
    while ($row = $result->fetch_assoc()) {
        $newBoostMins = $row["newBoostMins"];
    }
    //Update the database with the reduced totalMins
    $sql = "UPDATE `boost` SET `totalMins` = '$newBoostMins' WHERE complete = 'n'";
    $result = $con->query($sql);
    
    echo "<h3>Boost Management:</h3>";
    echo "<b>You have requested the current boost programme to be cancelled.</b><br>Please wait while we send this instruction to the Nest...";
}

//Close database connection
$con->close();
?>
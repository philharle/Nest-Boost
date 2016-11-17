<head>
  <link rel="shortcut icon" type="image/png" href="favicon.png" />
  <title>Nest-Boost</title>
  <style>
    body {
      font-family: calibri;
    }
    
    table,
    th,
    td {
      border: 1px solid black;
    }
  </style>
</head>

<?php
//The Nest-Boost config
require_once(__DIR__ . '/resources/config.php');

//Connect to the Database
$con = new mysqli($hostname, $username, $password, $dbname);
if ($con->connect_error) {
    trigger_error('Database connection failed: ' . $con->connect_error, E_USER_ERROR);
}

echo "<h3>Boost Management:</h3>";

//Run query to check if Boost is in progress
$sql = "select startTargetTemp, startActualTemp, startTime, totalMins, complete from boost where complete = 'n'";
$result = $con->query($sql);

if ($result->num_rows > 0)
{
    //Boost already in progress
    echo "A Boost program is already in progress.";
    while ($row = $result->fetch_assoc()) {
        $dtEndDate = new DateTime($row["startTime"]);
        $dtEndDate->modify("+{$row["totalMins"]} minutes");
        $dtEndDate = $dtEndDate->format('Y-m-d H:i:s');
        echo "<b><br>Boost will complete at: </b> $dtEndDate";
        echo "<b><br>Boost duration: </b>";
        echo $row["totalMins"];
    }
}
else
{
    //No Boost in progress at the moment, so show the form
    echo "<form action=\"boost_trigger.php\"method=\"post\">Set boost for <select name=\"boostTime\"><option value=\"30\">30 mins</option><option value=\"60\">60 mins</option><option value=\"90\">90 mins</option><option value=\"120\">120 mins</option></select><br><input type=\"submit\" value=\"Submit\"></form>";
    }

//Output Boost History
echo "<br><hr><h3>Boost History:</h3>";
$result = mysqli_query($con, "SELECT startTime, totalMins FROM boost ORDER by startTime DESC");
echo "<table cellpadding=\"5\">";
echo "<tr><td><b>Start time</b></td><td align=\"right\"><b>Mins</b></td></tr>";
while ($row = mysqli_fetch_array($result)) {
    echo "<tr><td>" . $row['startTime'] . "</td><td align=\"right\"> " . $row['totalMins'] . "</td></tr>"; //these are the fields that you have stored in your database table employee
}
echo "</table>";

//Close database connection
$con->close();

?>
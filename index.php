<head>
  <link rel="shortcut icon" type="image/png" href="favicon.png" />
  <title>Nest-Boost</title>
  <style>
    body {
      font-family: calibri;
    }
    
    table {
      border: 1px solid black;
    }
    
    td,
    th {
      border: 1px solid lightgrey;
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

	//Round up End Time to the next 10 min cron interval
	$dtEndDatePlus10 = strtotime($dtEndDate);
	$dtEndDatePlus10 = date("Y-m-d H:i:s", ( $maketime+(60*10) ));

        echo "<b><br>Boost will complete at: </b> $dtEndDatePlus10";
        echo "<b><br>Boost duration: </b>";
        echo $row["totalMins"];
        //Provide a button to cancel the current boost...set boostTime to 0
        //TODO I don't like this method, it loses the boost history, should probably cacluate a new true boost value based on current time minus start time (minus 1 min)
        echo "<form action=\"boost_trigger.php\"method=\"post\"><br>Is your washing dry already?<input type=\"hidden\" name=\"boostTime\" value=\"0\"></select><br><br><input type=\"submit\" value=\"Cancel Boost\"></form>";
    }
}
else
{
    //No Boost in progress at the moment, so show the form
    echo "<form action=\"boost_trigger.php\"method=\"post\">Dry washing for <select name=\"boostTime\"><option value=\"30\">30 mins</option><option value=\"60\">60 mins</option><option value=\"90\">90 mins</option><option value=\"120\">120 mins</option></select><br><br><input type=\"submit\" value=\"Activate Boost\"></form>";
    }

//Output Boost History
echo "<hr><h3>Boost History:</h3>";
$result = mysqli_query($con, "SELECT startTime, totalMins FROM boost ORDER by startTime DESC");
echo "<table><tr><td><b>Start time</b></td><td align=\"right\"><b>Mins</b></td></tr>";
while ($row = mysqli_fetch_array($result)) {
    echo "<tr><td>" . $row['startTime'] . "</td><td align=\"right\"> " . $row['totalMins'] . "</td></tr>";
}
echo "</table>";

//Output sum of boostMins
$result = mysqli_query($con, "select sum(totalMins) as totalBoostMins from boost");
while ($row = mysqli_fetch_array($result)) {
    echo "<br>You've used the boost for a total of " . $row['totalBoostMins'] . " mins";
    }

//Close database connection
$con->close();

?>

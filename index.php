<head>
  <link rel="shortcut icon" type="image/png" href="favicon.png" />
  <title>Nest-Boost</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</head>

<?php
//The Nest-Boost config
require_once(__DIR__ . '/resources/config.php');

//Connect to the Database
$con = new mysqli($hostname, $username, $password, $dbname);
if ($con->connect_error) {
    trigger_error('Database connection failed: ' . $con->connect_error, E_USER_ERROR);
}

echo "<nav class=\"navbar navbar-default navbar-fixed-top\"><div class=\"container-fluid\"><div class=\"navbar-header\"><a class=\"navbar-brand\" href=\"/Nest-Boost/\"><span class=\"glyphicon glyphicon-record\"></span> Nest Boost</a></div></div></nav><div class=\"container\" style=\"margin-top:50px\">";

//Run query to check if Boost is in progress
$sql = "select startTargetTemp, startActualTemp, startTime, totalMins, complete from boost where complete = 'n'";
$result = $con->query($sql);

echo "<div class=\"col-sm-4\"><h1><small>Boost</small></h1>";

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
        echo "<form action=\"boost_trigger.php\"method=\"post\"><div class=\"form-group\"><br><label>Is your washing dry already?</label><input type=\"hidden\" name=\"boostTime\" value=\"0\"><br><input type=\"submit\" class=\"btn btn-danger\" value=\"Cancel Boost\"></div></form>";
    }
}
else
{
    //No Boost in progress at the moment, so show the form
    echo "<form action=\"boost_trigger.php\"method=\"post\"><div class=\"form-group\"><label>Dry washing for</label><select class=\"form-control\" name=\"boostTime\"><option value= \"30\">30 mins</option><option value= \"60\">60 mins</option><option value = \"90\">90 mins</option><option value = \"120\">120 mins</option></select><br><input type=\"submit\" class=\"btn btn-success\" value=\"Activate Boost\"></div></form>";
    }

    echo "</div>";

//Output Boost History
echo "<div class=\"col-sm-8\"><h1><small>History</small></h1>";
$result = mysqli_query($con, "SELECT startTime, totalMins FROM boost ORDER by startTime DESC");
echo "<table class=\"table table-striped\"><thead><tr><th>Start time</th><th>Mins</th></tr></thead>";
while ($row = mysqli_fetch_array($result)) {
    echo "<tr><td>" . $row['startTime'] . "</td><td> " . $row['totalMins'] . "</td></tr>";
}
echo "</table>";

//Output sum of boostMins
//TODO Filter on last 30 days
$result = mysqli_query($con, "select sum(totalMins) as totalBoostMins from boost");
while ($row = mysqli_fetch_array($result)) {
    echo "Over the last 30 days Boost has been active for " . $row['totalBoostMins'] . " mins</div>";
    }

echo "</div>";

//Close database connection
$con->close();

?>

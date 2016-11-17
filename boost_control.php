<?php
//The Nest-Boost config
require_once(__DIR__ . '/resources/config.php');
//The Nest API Class
require_once(__DIR__ . '/resources/nest.class.php');

//Connect to the Database
$con = new mysqli($hostname, $username, $password, $dbname);
if ($con->connect_error) {
    trigger_error('Database connection failed: ' . $con->connect_error, E_USER_ERROR);
}

echo date("Y/m/d H:i:s", time());
echo "<br>";

//If there is a boost recently triggered, then populate its values
$sql    = "select startTargetTemp, startActualTemp, startTime, totalMins, complete from boost where complete = 'n' AND startTargetTemp IS NULL and startActualTemp IS NULL";
$result = $con->query($sql);
if ($result->num_rows > 0)
{
    $nest  = new Nest();
    $infos = $nest->getDeviceInfo();
    
    //If the target temperature is an array, we need to deal with that.
    if (strpos($infos->current_state->mode, 'heat') !== false) {
        if (is_array($infos->target->temperature)) {
            $low_target_temp  = $infos->target->temperature[0];
            $high_target_temp = null;
        } else {
            $low_target_temp  = $infos->target->temperature;
            $high_target_temp = null;
        }
    } elseif (strpos($infos->current_state->mode, 'cool') !== false) {
        if (is_array($infos->target->temperature)) {
            $low_target_temp  = null;
            $high_target_temp = $infos->target->temperature[1];
        } else {
            $low_target_temp  = null;
            $high_target_temp = $infos->target->temperature;
        }
    } elseif (strpos($infos->current_state->mode, 'range') !== false) {
        $low_target_temp  = $infos->target->temperature[0];
        $high_target_temp = $infos->target->temperature[1];
    }
    
    echo ("<br>New boost trigger found, recording current state. Current target: $low_target_temp Current temp: $infos->current_state->temperature");
    
    $v1 = (string)$low_target_temp;
    $v2 = (string)$infos->current_state->temperature;
    
    $sql    = "UPDATE `boost` SET `startTargetTemp` = '$v1', startActualTemp = '$v2' WHERE `complete` = 'n' AND `startTargetTemp` IS NULL AND `startActualTemp` IS NULL";
    $result = $con->query($sql);
}

//Read from database. If there is more than 0 rows with boostcompleted then run the rest of this stuff to maintain the boost. If 0 or fewer, then we do nothing.
$sql    = "select startTargetTemp, startActualTemp, startTime, totalMins, complete from boost where complete = 'n'";
$result = $con->query($sql);

//Can't have more than one current boost. Crap out...
if ($result->num_rows > 1) {
    trigger_error("Error: More than one active boost. Please fix.");
}
//If boost is in progress, do stuff...
elseif ($result->num_rows > 0) {
    // output data of each row
    while ($row = $result->fetch_assoc()) {
        echo "Current boost programme:<br>startTargetTemp: " . $row["startTargetTemp"] . "<br>startActualTemp: " . $row["startActualTemp"] . "<br>startTime: " . $row["startTime"] . "<br>totalMins: " . $row["totalMins"] . "<br>complete: " . $row["complete"] . "<br>";
        
        $startTargetTemp = $row["startTargetTemp"];
        $startTime       = $row["startTime"];
        $totalMins       = $row["totalMins"];
        
        $nest  = new Nest();
        
        $infos = $nest->getDeviceInfo();
        //If the target temperature is an array, we need to deal with that.
        if (strpos($infos->current_state->mode, 'heat') !== false) {
            if (is_array($infos->target->temperature)) {
                $low_target_temp  = $infos->target->temperature[0];
                $high_target_temp = null;
            } else {
                $low_target_temp  = $infos->target->temperature;
                $high_target_temp = null;
            }
        } elseif (strpos($infos->current_state->mode, 'cool') !== false) {
            if (is_array($infos->target->temperature)) {
                $low_target_temp  = null;
                $high_target_temp = $infos->target->temperature[1];
            } else {
                $low_target_temp  = null;
                $high_target_temp = $infos->target->temperature;
            }
        } elseif (strpos($infos->current_state->mode, 'range') !== false) {
            $low_target_temp  = $infos->target->temperature[0];
            $high_target_temp = $infos->target->temperature[1];
        }
        
        echo ("<br>Current temp: $infos->current_state->temperature");
        echo ("<br>Target temp: $low_target_temp");
        echo ("<br>Time to target: $infos->target->time_to_target");
        
        //Calculate boost end time and store in variable
        $dtEndDate = new DateTime($startTime);
        $dtEndDate->modify("+{$totalMins} minutes");
        $dtEndDate = $dtEndDate->format('Y-m-d H:i:s');
        //Calculate current time and store in variables
        $dtCurrentDate = date('Y-m-d H:i:s');
        $dtCurrentHour = date('H');
        
        //Check if the active Boost should be terminated (has it gone beyond the intended run time)
        if ($dtCurrentDate > $dtEndDate) {
            echo ("<br><br>Boost completed. Restoring heating values.<br>Setting target temperature back to ";
            
            //Check if current time is between 10pm and 4am
            if( ($dtCurrentHour >= 23) || ($dtCurrentHour < 4) ){
                echo "12 degrees (the night time temp)";
                //Set temp to night time temperature (12 degrees)
                ////UNCOMMENT TO RUN LIVE
                ////$success = $nest->setTargetTemperature(12);
                ////var_dump($success);
                
                sleep(1);
            }
            else{
                echo ("$startTargetTemp degrees (the pre-boost target temp)";
                //Set temp to startTargetTemp
                $startTargetTemp = intval($startTargetTemp); //Set as integer first, it fails if we send a string through to the NEST API
                ////UNCOMMENT TO RUN LIVE
                ////$success = $nest->setTargetTemperature($startTargetTemp);
                ////var_dump($success);
                
                sleep(1);
            }
            
            sleep(1);
            
            //TODO - in the future, not essential
            //Compare the current target with the original one pre-Boost.
            //If it matches then the restore has been successful so we set boost to completed in DB
            //At the moment we're not doing a comparison, just making the (brave) assumption that the target_temp has been successfully restored
            $sql    = "UPDATE `boost` SET `complete` = 'y' WHERE `complete` = 'n'";
            $result = $con->query($sql);
            //$result should be 1 or more for a success
            
        } else {
            echo ("<br><br>Boost currently active...");
            
            $targetDiff = ($low_target_temp - $infos->current_state->temperature);
            
            //If current temp is less than target
            if ($low_target_temp < $infos->current_state->temperature) {
                echo ("<br>Target is $targetDiff degrees under current temp, increasing target.");
                
                //set target to current temp plus 1deg
                $newTargetTemp = ($infos->current_state->temperature + 1);
                echo "<br>Setting target temperature to $newTargetTemp";
                ////UNCOMMENT TO RUN LIVE
                ////$success = $nest->setTargetTemperature($newTargetTemp);
                ////var_dump($success);
                
                sleep(1);
            } else {
                echo ("<br>Target is $targetDiff degrees over current temp. Nothing to do.");
            }
        }
        
        //Close database connection
        $con->close();
        
        /* Helper functions */
        function json_format($json)
        {
            $tab          = "  ";
            $new_json     = "";
            $indent_level = 0;
            $in_string    = false;
            
            $json_obj = json_decode($json);
            
            if ($json_obj === false)
                return false;
            
            $json = json_encode($json_obj);
            $len  = strlen($json);
            
            for ($c = 0; $c < $len; $c++) {
                $char = $json[$c];
                switch ($char) {
                    case '{':
                        case '[':
                            if (!$in_string) {
                                $new_json .= $char . "\n" . str_repeat($tab, $indent_level + 1);
                                $indent_level++;
                        } else {
                            $new_json .= $char;
                        }
                        break;
            case '}':
                case ']':
                    if (!$in_string) {
                        $indent_level--;
                        $new_json .= "\n" . str_repeat($tab, $indent_level) . $char;
                } else {
                    $new_json .= $char;
                }
                break;
            case ',':
                if (!$in_string) {
                    $new_json .= ",\n" . str_repeat($tab, $indent_level);
            } else {
                $new_json .= $char;
            }
            break;
        case ':':
            if (!$in_string) {
                $new_json .= ": ";
        } else {
            $new_json .= $char;
        }
        break;
    case '"':
        if ($c > 0 && $json[$c - 1] != '\\') {
            $in_string = !$in_string;
    }
    default:
        $new_json .= $char;
        break;
}
}

return $new_json;
}

function jlog($json)
{
    if (!is_string($json)) {
        $json = json_encode($json);
    }
    echo json_format($json) . "\n";
}

}
} else {
    echo "<br><br>Boost is not currently active";
}
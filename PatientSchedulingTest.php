<head>
    <title>Scheduler Protoype</title>
</head>
<body>
    <?php include 'display_patients.php'; ?>
    <h2>Select patient times & whether they are new:</h2>
    <form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post">
        <?php
        // read patient list from text file for checkbox options
        $patientFile = fopen("patient_masterlist.txt", "r");
        if ($patientFile) {
            while (($line = fgets($patientFile)) !== false) {
                // line by line extract patient info
                $data = explode(",", $line);
                if (count($data) >= 1) {
                    $name = trim($data[0]); //patient name
                    echo "<div>";
                    echo "<label><input type='checkbox' name='selected_patients[]' value='$name'>$name</label>";
                    echo "<div>";
                    echo "<input type='checkbox' name='new_patients[]' value='$name'>  New Patient    ";
                    echo "<select name='appointment_times[$name]'>";
                    // dropdown options for appointment times from 9:00 to 6:00 at quarter-hour intervals
                    $startHour = strtotime("09:00");
                    $endHour = strtotime("18:00"); // 6:00
                    $interval = 15 * 60; // 15 minutes

                    while ($startHour <= $endHour) {
                        $timeString = date("h:i A", $startHour);
                        echo "<option value='$timeString'>$timeString</option>";
                        $startHour += $interval;
                    }
                    echo "</select>";
                    echo "</div>";
                } 
            }
            fclose($patientFile);
        }
        ?>
        <br>
        <hr>
        <input type="submit" value="Add to Schedule">
    </form>

    <?php

    // search for patient by name in file
    function searchPatientByName($name) {
        $patientFile = fopen("patient_masterlist.txt", "r");
        while (($line = fgets($patientFile)) !== false) {
            $data = explode(",", $line);
            if ($data[0] === $name) {
                fclose($patientFile);
                return ['Name' => $data[0], 'Address' => $data[1], 'Coordinates' => $data[2]];
            }
        }
        fclose($patientFile);
        return null; //patient not found
    }

    // estimated drive time
    function calculateDriveTime($startCoord, $endCoord) {
        // Convert letters to corresponding numbers
        $startX = ord(substr($startCoord, 0, 1)) - 65; // Convert letter to number (ASCII value - 65)
        $startY = intval(substr($startCoord, 1)); // Extract the number part
    
        $endX = ord(substr($endCoord, 0, 1)) - 65;
        $endY = intval(substr($endCoord, 1));
    
        // distance between two coordinates
        $distance = sqrt(pow($endX - $startX, 2) + pow($endY - $startY, 2));
    
        // each unit of distance represents 2 minutes of drive time
        return $distance * 2;
    }

    // draft schedule
    function createSchedule($selectedPatients, $newPatients, $startHour, $appointmentTimes) {
        // array schedule output
        $schedule = [];

        // Example map coordinates for demonstration
        $mapCoordinates = [
            // "Z5" => [0, 0],
            // "K2" => [0, 2],
            // "N9" => [2, 3],
            // Add more coordinates as needed
        ];

        // Generate schedule
        foreach ($selectedPatients as $index => $patient) {
            // get patient details from text file based on selected patient name
            $patientDetails = searchPatientByName($patient);
            if ($patientDetails !== null) {
                // determine if the patient is new based on checkbox
                $isNewPatient = in_array($patient, $newPatients);

                // calculate drive time from previous location (if exists)
                $driveTime = 0;
                // if ($prevCoord !== null) {
                //     $driveTime = calculateDriveTime($prevCoord, $mapCoordinates[$patientDetails['Coordinates']]);
                // }

                // if patient is new, visitDuration is 60 mins, 30 mins otherwise
                $visitDuration = $isNewPatient ? 60 : 30;

                // appointment time for the current patient
                $appointmentTime = isset($appointmentTimes[$patient]) ? $appointmentTimes[$patient] : '';

                // end time based on visit duration
                $appointmentEndTime = strtotime("+ $visitDuration minutes", strtotime("tomorrow $appointmentTime"));

                // drive time and patient appointment to schedule array
                $schedule[] = [
                    'name' => $patientDetails['Name'],
                    'address' => $patientDetails['Address'],
                    'appointment_time' => strtotime("tomorrow $appointmentTime"), // Customized appointment start time
                    'appointment_end_time' => $appointmentEndTime,
                    'drive_time' => $driveTime,
                    'visit_duration' => $visitDuration,
                    'is_new_patient' => $isNewPatient
                ];
            }
        }
        return $schedule;
    }

      // form is submitted for removing a row
    // if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_index'])) {
    //     $removeIndex = $_POST['remove_index'];
    //     // Remove the selected row from the schedule array
    //     unset($schedule[$removeIndex]);
    //     // Reindex the array to fill any gaps left by unset
    //     $schedule = array_values($schedule);
    // }
    
    // form is submitted for selecting patients
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['selected_patients'])) {
        // get selected patients and new patient from form
        $selectedPatients = $_POST['selected_patients'];
        $newPatients = isset($_POST['new_patients']) ? $_POST['new_patients'] : [];
        #$startTime = strtotime($_POST['appointment_time']);
        $appointmentTimes = isset($_POST['appointment_times']) ? $_POST['appointment_times'] : [];

        // create schedule
        $schedule = createSchedule($selectedPatients, $newPatients, $startHour, $appointmentTimes);
        
    
        // display schedule table
        echo "<h2>Draft Schedule:</h2>";
        echo "<table border=1 cellpadding=2 >";
        foreach ($schedule as $index => $item) {
            //$self = "PatientScheduling.php";
            echo "<tr>";
            $nameLabel = $item['is_new_patient'] ? $item['name'] . " *new*" : $item['name']; // if patient is marked as new, concatenate *new* to their name, just their name otherwise
            echo "<td>{$nameLabel}, {$item['address']} </td>";
            echo "<td>" . date('h:i A', $item['appointment_time']) . " - " . date('h:i A', $item['appointment_end_time']) . "</td>";
            echo "<td> drive {$item['drive_time']} minutes</td>";
            echo "<td>{$item['visit_duration']} minutes</td>";
            //echo "<td><form action='{$_SERVER["PHP_SELF"]}' method='post'><input type='hidden' name='remove_index' value='$index'><input type='submit' value='Remove'></form></td>";
            echo "</tr>";
        }
        echo "</table>";

    }
    ?>

</body>
</html>

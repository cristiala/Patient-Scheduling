<!-- Cristina Alarcon -->
<!-- This is the main file where the the information is displayed and formatted 
Sources regarding implemenatation are at the bottom-->

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
                $info = explode(",", $line);
                if (count($info) >= 1) { // checks if not empty
                    $name = $info[0]; //patient name
                    echo "<div>";
                    echo "<br><label><input type='checkbox' name='selected_patients[]' value='$name'> $name </label>";
                    echo "<div>";
                    echo "<input type='checkbox' name='new_patients[]' value='$name'>  New Patient    ";
                    echo "<select name='appointment_times[$name]'>";

                    // dropdown options for appointment times from 9:00 to 6:00 at quarter-hour intervals
                    $startHour = strtotime("09:00");
                    $endHour = strtotime("18:00"); // 6:00
                    $interval = 15 * 60; // 15 minutes

                    while ($startHour <= $endHour) {
                        $timeString = date("h:i A", $startHour); // hour:minute AM/PM
                        echo "<option value ='$timeString'> $timeString </option>";
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
        <input type = "submit" value = "Add to Schedule">
    </form>

    <?php

    // search for patient by name in file
    function searchPatient($name) {
        $patientFile = fopen("patient_masterlist.txt", "r"); // similar from beginning
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


    function letterToNumber($letter) {
        // ASCII value of 'A'
        $baseValue = ord('A') - 1;
        return ord($letter) - $baseValue;
    }

    // estimated drive time
    function calculateDriveTime($startCoord, $endCoord) {
        // seperate map coordinates into letter and number
        $startLetter = substr($startCoord, -2, 1);
        $startNumber = intval(substr($startCoord, -1)); 
        $endLetter = substr($endCoord, -2, 1);
        $endNumber = intval(substr($endCoord, -1));

        // letters to numbers
        $startLetterNum = letterToNumber($startLetter);
        $endLetterNum = letterToNumber($endLetter);

        $distance = sqrt(pow($endLetterNum - $startLetterNum, 2) + pow($endNumber - $startNumber, 2));
        $driveTime = $distance * 2;

        return $driveTime;
    }

    // draft schedule
    function createSchedule($selectedPatients, $newPatients, $startHour, $appointmentTimes) {
        // array schedule output
        $schedule = [];
        $mapCoordinates = [
            // "Z5" => [0, 0],
            // "K2" => [0, 2],
            // "N9" => [2, 3],
            // Add more coordinates as needed
        ];

        // iterate and create schedule
        foreach ($selectedPatients as $index => $patient) {
            // get patient details from text file based on selected patient name
            $patientDetails = searchPatient($patient);
            if ($patientDetails !== null) {
                // determine if the patient is in the new patient array new based on checkbox
                $isNewPatient = in_array($patient, $newPatients);

                // calculate drive time from previous location (if exists)
                $driveTime = 0;
                // if ($startCoord !== null) {
                //     $driveTime = calculateDriveTime($prevCoord, $mapCoordinates[$patientDetails['Coordinates']]);
                // }

                // if patient is new, visitDuration is 60 mins, 30 mins otherwise
                $visitDuration = $isNewPatient ? 60 : 30;

                // appointment time for the current patient
                $appointmentTime = isset($appointmentTimes[$patient]) ? $appointmentTimes[$patient] : '';
                // end time based on visit duration
                $appointmentEndTime = strtotime("+ $visitDuration minutes", strtotime("tomorrow $appointmentTime"));

                // populate to schedule array
                $schedule[] = [
                    'name' => $patientDetails['Name'],
                    'address' => $patientDetails['Address'],
                    'appointment_time' => strtotime("tomorrow $appointmentTime"), // appointment start time
                    'appointment_end_time' => $appointmentEndTime,
                    'drive_time' => $driveTime,
                    'visit_duration' => $visitDuration,
                    'is_new_patient' => $isNewPatient
                ];
            }
        }
        return $schedule;
    }
    
    // form is submitted for selecting patients
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['selected_patients'])) {
        // get selected patients and new patient from form
        $selectedPatients = $_POST['selected_patients'];
        $newPatients = isset($_POST['new_patients']) ? $_POST['new_patients'] : [];
        $appointmentTimes = isset($_POST['appointment_times']) ? $_POST['appointment_times'] : [];

        // create schedule
        $schedule = createSchedule($selectedPatients, $newPatients, $startHour, $appointmentTimes);
        
        // display schedule table
        echo "<h2>Draft Schedule:</h2>";
        echo "<table border=1 cellpadding=2 >";
        foreach ($schedule as $index => $item) {
            echo "<tr>";
            $nameLabel = $item['is_new_patient'] ? $item['name'] . " <font color='red'>*new*</font>" : $item['name']; // if patient is marked as new, concatenate *new* to their name, just their name otherwise
            echo "<td>{$nameLabel}, {$item['address']} </td>";
            echo "<td>" . date('h:i A', $item['appointment_time']) . " - " . date('h:i A', $item['appointment_end_time']) . "</td>";
            echo "<td> drive {$item['drive_time']} minutes</td>";
            echo "<td>{$item['visit_duration']} minute visit </td>";
            echo "</tr>";
        }
        echo "</table>";

    }
    ?>

</body>
</html>


<!-- Sources for ideas relating to built in PHP functions, and implementation
     inlcude statement - https://www.w3schools.com/php/php_includes.asp
     help to read a file - https://www.w3schools.com/php/php_file_open.asp
     Explode function - https://www.w3schools.com/php/func_string_explode.asp
     To create a drop down menu - https://www.quora.com/What-is-the-code-to-make-a-drop-down-menu-in-PHP#:~:text=To%20create%20a%20drop%2Ddown,select%20name%3D%22country%22%3E 
     To create a checkbox - https://www.w3schools.com/tags/att_input_type_checkbox.asp
     To determine if a value is in array - https://www.w3schools.com/php/func_array_in_array.asp
     strtotime() - https://www.w3schools.com/php/func_date_strtotime.asp
     how to formal time to keep track using date() - https://www.w3schools.com/php/func_date_date.asp

    -->
<?php
session_start();
require('firstimport.php');

if (!isset($_SESSION['name'])) {
    header("Location: login1.php");
    exit();
}

$tbl_name = "booking";
mysqli_select_db($conn, "$db_name") or die("Cannot select database");

$uname = $_SESSION['name'];
$num = $_POST['tno'] ?? null;
$seat = $_POST['selct'] ?? null; // Get seat from POST, and correct input name
$fromstn = $_POST['fromstn'] ?? null;
$tostn = $_POST['tostn'] ?? null;
$doj = $_POST['doj'] ?? null;
$dob = $_POST['dob'] ?? null;

if (!$num || !$seat || !$doj) {
    die("Error: Missing required parameters (tno, seat, doj). Please check your input.");
}

// Debugging: Log POST parameters
error_log("Received POST Parameters: " . print_r($_POST, true));

// Fetch seat availability
$sql1 = "SELECT `$seat` FROM seats_availability WHERE Train_No=? AND doj=?";
$stmt1 = $conn->prepare($sql1);
if (!$stmt1) {
    die("Error in SQL Query (seats_availability): " . $conn->error);
}
$stmt1->bind_param("ss", $num, $doj);
$stmt1->execute();
$result1 = $stmt1->get_result();
$row1 = $result1->fetch_assoc();
$value = $row1[$seat] ?? 0;
$stmt1->close();

// Passenger Booking Loop
$booking_count = 0;
for ($i = 1; $i <= 5; $i++) {
    $name = $_POST["name$i"] ?? null;
    $age = $_POST["age$i"] ?? null;
    $sex = $_POST["sex$i"] ?? null;

    if (!empty($name) && !empty($age)) {
        $booking_count++;
        $status = ($value > 0) ? "Confirmed" : "Waiting";

        $sql = "INSERT INTO $tbl_name (uname, Tnumber, class, doj, DOB, fromstn, tostn, Name, Age, sex, Status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Error in SQL Query (booking): " . $conn->error);
        }
        $stmt->bind_param("sssssssssss", $uname, $num, $seat, $doj, $dob, $fromstn, $tostn, $name, $age, $sex, $status);
        if (!$stmt->execute()) {
            echo "Error inserting passenger $name: " . $stmt->error;
        }
        $stmt->close();

        // Update seat availability if seat was confirmed
        if ($value > 0) {
            $value--;
            $sql2 = "UPDATE seats_availability SET `$seat`=? WHERE doj=? AND Train_No=?";
            $stmt2 = $conn->prepare($sql2);
            if (!$stmt2) {
                die("Error in SQL Query (seats_availability update): " . $conn->error);
            }
            $stmt2->bind_param("iss", $value, $doj, $num);
            $stmt2->execute();
            $stmt2->close();
        }
    }
}

// Set a success message in the session
$_SESSION['booking_success'] = true;
$_SESSION['passengers_booked'] = $booking_count;

// Redirect to display page after processing
header("location: display.php?tno=" . urlencode($num) . "&doj=" . urlencode($doj) . "&seat=" . urlencode($seat) . "&fromstn=" . urlencode($fromstn) . "&tostn=" . urlencode($tostn));
exit();
?>
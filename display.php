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

// Get parameters from URL
$num = $_GET['tno'] ?? null;
$seat = $_GET['seat'] ?? null;
$doj = $_GET['doj'] ?? null;
$fromstn = $_GET['fromstn'] ?? null;
$tostn = $_GET['tostn'] ?? null;

if (!$num || !$doj) {
    die("Error: Missing required parameters (tno, doj). Please check your input.");
}

// Fetch the booking details
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selected_class = isset($_POST['selected_class']) ? $_POST['selected_class'] : '';
    echo "Selected Class: " . $selected_class;
}

// Fetch train details including 1A column value
$train_sql = "SELECT * FROM train_list WHERE Number=?";
$train_stmt = $conn->prepare($train_sql);
if (!$train_stmt) {
    die("Error in SQL Query: " . $conn->error);
}
$train_stmt->bind_param("s", $num);
$train_stmt->execute();
$train_result = $train_stmt->get_result();
$train_info = $train_result->fetch_assoc();

// Extract 1A column value
$fare = $train_info['1A'] ?? 'Not Available';


// Order by passenger name
$sql = "SELECT * FROM $tbl_name WHERE uname=? AND Tnumber=? AND doj=? AND class=? ORDER BY Name";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error in SQL Query: " . $conn->error);
}
$stmt->bind_param("ssss", $uname, $num, $doj, $seat);
$stmt->execute();
$result = $stmt->get_result();

// Fetch train details
$train_sql = "SELECT * FROM train_list WHERE Number=?";
$train_stmt = $conn->prepare($train_sql);
if (!$train_stmt) {
    die("Error in SQL Query: " . $conn->error);
}
$train_stmt->bind_param("s", $num);
$train_stmt->execute();
$train_result = $train_stmt->get_result();
$train_info = $train_result->fetch_assoc();

// Retrieve amount from train_list table
$amount = $train_info['Amount'] ?? 'N/A';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Booking Details</title>
    <link rel="shortcut icon" href="images/favicon.png">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap.css" rel="stylesheet">
    <link href="css/Default.css" rel="stylesheet">
    <script type="text/javascript" src="js/jquery.js"></script>
    <script type="text/javascript" src="js/bootstrap.min.js"></script>
    <script type="text/javascript" src="js/bootstrap.js"></script>
    <script type="text/javascript" src="js/man.js"></script>
</head>
<body>
    <div class="wrap">
        <!-- Header -->
        <div class="header">
            <div style="float:left;width:150px;">
                <img src="images/logo.jpg"/>
            </div>
            <div>
                <div id="heading">
                    <a href="index.html">Indian Railways</a>
                </div>
            </div>
        </div>
        
        <!-- Navigation bar -->
        <div class="navbar navbar-inverse">
            <div class="navbar-inner">
                <div class="container">
                    <a class="brand" href="index.php">HOME</a>
                    <a class="brand" href="train.php">FIND TRAIN</a>
                    <a class="brand" href="reservation.php">RESERVATION</a>
                    <a class="brand" href="profile.php">PROFILE</a>
                    <a class="brand" href="booking.php">BOOKING HISTORY</a>
                </div>
            </div>
        </div>
        
        <div class="container">
            <?php if (isset($_SESSION['booking_success']) && $_SESSION['booking_success']): ?>
                <div class="alert alert-success">
                    <strong>Success!</strong> Your booking for <?php echo $_SESSION['passengers_booked']; ?> passenger(s) has been completed.
                </div>
                <?php 
                // Clear the success message so it doesn't show again on refresh
                unset($_SESSION['booking_success']);
                unset($_SESSION['passengers_booked']);
                ?>
            <?php endif; ?>

            <div class="page-header">
                <h1>Booking Details <small>Train No: <?php echo htmlspecialchars($num); ?></small></h1>
            </div>
            
            <?php if (isset($train_info) && $train_info): ?>
            <div class="well">
                <h3><?php echo htmlspecialchars($train_info['Name']); ?> (<?php echo htmlspecialchars($train_info['Number']); ?>)</h3>
                <p><strong>From:</strong> <?php echo htmlspecialchars($fromstn ?? 'N/A'); ?> 
                   <strong>To:</strong> <?php echo htmlspecialchars($tostn ?? 'N/A'); ?></p>
                <p><strong>Date of Journey:</strong> <?php echo htmlspecialchars($doj); ?> 
                   <strong>Class:</strong> <?php echo htmlspecialchars($seat); ?></p>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                <p>Train information is not available.</p>
            </div>
            <?php endif; ?>
            
            <?php if ($result && $result->num_rows > 0): ?>
                <h3>Passenger Details</h3>
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Age</th>
                            <th>Gender</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['Name']); ?></td>
                            <td><?php echo htmlspecialchars($row['Age']); ?></td>
                            <td><?php echo htmlspecialchars($row['sex']); ?></td>
                            <td>
                                <span class="label <?php echo ($row['Status'] == 'Confirmed') ? 'label-success' : 'label-warning'; ?>">
                                    <?php echo htmlspecialchars($row['Status']); ?>
                                </span>
                            </td>

                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                
                <div class="alert alert-info">
                    <p><strong>Note:</strong> Please take a screenshot or print this page for your reference.</p>
                    <p>If your status is "Waiting", please check your booking status before traveling.</p>
                </div>
				<div id="selectedClassDisplay">
    Fare: <strong><?php echo htmlspecialchars($fare); ?></strong>
</div>

				
                
                <div class="form-actions">
                    <a href="booking.php" class="btn btn-primary">View All Bookings</a>
                    <button class="btn" onclick="window.print()">Print</button>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <p>No booking records found. Please check if you've completed the booking process.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Footer -->
        <footer>
            <div style="width:100%;">
                <div style="float:left;">
                    <p class="text-right text-info">&copy; 2018 Copyright PVT Ltd.</p>
                </div>
                <div style="float:right;">
                    <p class="text-right text-info">Designed By: projectworlds</p>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
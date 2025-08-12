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

// Fetch all bookings for the user
$sql = "SELECT * FROM $tbl_name WHERE uname=? ORDER BY doj DESC";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error in SQL Query: " . $conn->error);
}
$stmt->bind_param("s", $uname);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Booking History</title>
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
            <div class="page-header">
                <h1>Your Booking History</h1>
            </div>
            
            <?php if ($result->num_rows > 0): ?>
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Train No</th>
                            <th>Journey Date</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Class</th>
                            <th>Passenger</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    $groupedBookings = [];
                    
                    // Group bookings by train/date/class
                    while ($row = $result->fetch_assoc()) {
                        $key = $row['Tnumber'] . $row['doj'] . $row['class'];
                        if (!isset($groupedBookings[$key])) {
                            $groupedBookings[$key] = [
                                'Tnumber' => $row['Tnumber'],
                                'doj' => $row['doj'],
                                'fromstn' => $row['fromstn'],
                                'tostn' => $row['tostn'],
                                'class' => $row['class'],
                                'passengers' => []
                            ];
                        }
                        $groupedBookings[$key]['passengers'][] = [
                            'name' => $row['Name'],
                            'status' => $row['Status']
                        ];
                    }
                    
                    foreach ($groupedBookings as $booking): 
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($booking['Tnumber']); ?></td>
                            <td><?php echo htmlspecialchars($booking['doj']); ?></td>
                            <td><?php echo htmlspecialchars($booking['fromstn']); ?></td>
                            <td><?php echo htmlspecialchars($booking['tostn']); ?></td>
                            <td><?php echo htmlspecialchars($booking['class']); ?></td>
                            <td>
                                <?php 
                                foreach ($booking['passengers'] as $i => $passenger) {
                                    if ($i > 0) echo ", ";
                                    echo htmlspecialchars($passenger['name']);
                                }
                                echo " (" . count($booking['passengers']) . ")";
                                ?>
                            </td>
                            <td>
                                <?php 
                                $confirmed = 0;
                                $waiting = 0;
                                foreach ($booking['passengers'] as $passenger) {
                                    if ($passenger['status'] == 'Confirmed') $confirmed++;
                                    else $waiting++;
                                }
                                
                                if ($waiting == 0) {
                                    echo '<span class="label label-success">All Confirmed</span>';
                                } elseif ($confirmed == 0) {
                                    echo '<span class="label label-warning">All Waiting</span>';
                                } else {
                                    echo '<span class="label label-info">Mixed</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <a href="display.php?tno=<?php echo urlencode($booking['Tnumber']); ?>&doj=<?php echo urlencode($booking['doj']); ?>&seat=<?php echo urlencode($booking['class']); ?>&fromstn=<?php echo urlencode($booking['fromstn']); ?>&tostn=<?php echo urlencode($booking['tostn']); ?>" class="btn btn-info btn-small">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">
                    <p>You don't have any bookings yet. <a href="train.php">Find a train</a> to make a reservation.</p>
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
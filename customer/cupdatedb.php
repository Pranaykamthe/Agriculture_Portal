<?php
session_start();
date_default_timezone_set("Asia/Calcutta");
$userlogin = $_SESSION['customer_login_user'];

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "agriculture_portal";

$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start a transaction
mysqli_begin_transaction($conn);

try {
    // Fetch cart items
    $query1 = "SELECT * FROM cart";
    $result1 = mysqli_query($conn, $query1);

    if (!$result1) {
        throw new Exception("Error fetching cart items: " . mysqli_error($conn));
    }

    $date = date('d/m/Y');

    while ($row1 = $result1->fetch_assoc()) {
        $quantity = $row1['quantity'];
        $cropname = $row1['cropname'];
        $price = $row1['price'];

        // Update production_approx table
        $query2 = "UPDATE production_approx SET quantity = quantity - '$quantity' WHERE crop = '$cropname'";
        if (!mysqli_query($conn, $query2)) {
            throw new Exception("Error updating production_approx: " . mysqli_error($conn));
        }

        // Process farmer_crops_trade table
        while ($quantity > 0) {
            $query3 = "SELECT * FROM farmer_crops_trade WHERE Trade_crop = '$cropname' LIMIT 1";
            $result3 = mysqli_query($conn, $query3);

            if (!$result3) {
                throw new Exception("Error fetching farmer_crops_trade: " . mysqli_error($conn));
            }

            $row3 = $result3->fetch_assoc();

            if (!$row3) {
                throw new Exception("No more crops available for $cropname");
            }

            $farmer_id = $row3['farmer_fkid'];
            $trade_id = $row3['trade_id'];
            $crop_quantity = $row3['Crop_quantity'];
            $msp = $row3['msp'];

            if ($crop_quantity == $quantity) {
                // Insert into farmer_history
                $query11 = "INSERT INTO farmer_history (farmer_id, farmer_crop, farmer_quantity, farmer_price, date)
                            VALUES ('$farmer_id', '$cropname', '$crop_quantity', '$price', '$date')";
                if (!mysqli_query($conn, $query11)) {
                    throw new Exception("Error inserting into farmer_history: " . mysqli_error($conn));
                }

                // Delete from farmer_crops_trade
                $query4 = "DELETE FROM farmer_crops_trade WHERE trade_id = '$trade_id'";
                if (!mysqli_query($conn, $query4)) {
                    throw new Exception("Error deleting from farmer_crops_trade: " . mysqli_error($conn));
                }

                $quantity = 0; // Exit the loop
            } elseif ($crop_quantity > $quantity) {
                // Insert into farmer_history
                $query12 = "INSERT INTO farmer_history (farmer_id, farmer_crop, farmer_quantity, farmer_price, date)
                             VALUES ('$farmer_id', '$cropname', '$quantity', '$quantity' * '$msp', '$date')";
                if (!mysqli_query($conn, $query12)) {
                    throw new Exception("Error inserting into farmer_history: " . mysqli_error($conn));
                }

                // Update farmer_crops_trade
                $query5 = "UPDATE farmer_crops_trade SET Crop_quantity = Crop_quantity - '$quantity' WHERE trade_id = '$trade_id'";
                if (!mysqli_query($conn, $query5)) {
                    throw new Exception("Error updating farmer_crops_trade: " . mysqli_error($conn));
                }

                $quantity = 0; // Exit the loop
            } else {
                // Insert into farmer_history
                $query13 = "INSERT INTO farmer_history (farmer_id, farmer_crop, farmer_quantity, farmer_price, date)
                            VALUES ('$farmer_id', '$cropname', '$crop_quantity', '$crop_quantity' * '$msp', '$date')";
                if (!mysqli_query($conn, $query13)) {
                    throw new Exception("Error inserting into farmer_history: " . mysqli_error($conn));
                }

                // Delete from farmer_crops_trade
                $query6 = "DELETE FROM farmer_crops_trade WHERE trade_id = '$trade_id'";
                if (!mysqli_query($conn, $query6)) {
                    throw new Exception("Error deleting from farmer_crops_trade: " . mysqli_error($conn));
                }

                $quantity -= $crop_quantity; // Reduce the remaining quantity
            }
        }

        // Update MSP for the crop
        $query7 = "UPDATE farmer_crops_trade SET msp = CEIL((SELECT AVG(costperkg) FROM farmer_crops_trade WHERE Trade_crop = '$cropname') * 1.5) WHERE Trade_crop = '$cropname'";
        if (!mysqli_query($conn, $query7)) {
            throw new Exception("Error updating MSP: " . mysqli_error($conn));
        }
    }

    // Commit the transaction
    mysqli_commit($conn);
    header("Location: cmoney_transfered.php");
    exit();
} catch (Exception $e) {
    // Rollback the transaction on error
    mysqli_rollback($conn);
    die("Error: " . $e->getMessage());
} finally {
    // Close the connection
    mysqli_close($conn);
}
?>
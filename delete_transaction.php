<?php
// Include the database connection file
require_once 'db_connect.php';

// Check if both transaction ID and profile ID are provided
if (!isset($_GET['trans_id']) || !isset($_GET['profile_id']) || empty($_GET['trans_id']) || empty($_GET['profile_id'])) {
    header("location: index.php");
    exit;
}

$transaction_id = intval($_GET['trans_id']);
$profile_id = intval($_GET['profile_id']);

// SQL to delete the specific transaction
$sql_delete = "DELETE FROM transactions WHERE id = ? AND profile_id = ?";

if ($stmt = $conn->prepare($sql_delete)) {
    // Bind transaction ID (i) and profile ID (i)
    $stmt->bind_param("ii", $transaction_id, $profile_id);
    
    if ($stmt->execute()) {
        // Success: Redirect back to the view profile page with a status
        header("location: view_profile.php?id=" . $profile_id . "&status=trans_deleted");
        exit;
    } else {
        // Error: Redirect back with an error status (you'll need to update view_profile.php to handle this)
        header("location: view_profile.php?id=" . $profile_id . "&status=trans_error&msg=" . urlencode($stmt->error));
        exit;
    }
    $stmt->close();
} else {
    // Preparation error
    header("location: view_profile.php?id=" . $profile_id . "&status=trans_error&msg=" . urlencode("SQL prepare failed."));
    exit;
}
?>
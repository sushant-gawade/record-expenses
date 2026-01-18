<?php
// =================================================================
// PHP LOGIC: TRANSACTION SUBMISSION HANDLER
// =================================================================

// Ensure this script is only processed if a POST submission occurred
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_transaction'])) {
    
    // Safety check: Ensure parent variables exist
    if (!isset($conn) || !isset($profile_id)) {
        echo "<p class='form-error-message'>SYSTEM ERROR: Database connection or Profile ID is missing.</p>";
        exit;
    }

    // 1. Collect and sanitize data
    $transaction_date = trim($_POST['transaction_date']);
    // CRITICAL: Capturing the value from the form
    $type = trim($_POST['type']); 
    $amount = trim($_POST['amount']);
    $payment_method = trim($_POST['payment_method']);
    $note = $conn->real_escape_string(trim($_POST['note']));

    // 2. Validation
    $error_found = false;
    // Check if any required field is empty, especially 'type'
    if (empty($transaction_date) || empty($type) || empty($payment_method)) {
        $error_found = true;
        $status_message = "<p class='form-error-message'>Validation Error: Please fill all required fields (Date, Type, Method). **Type submitted was: " . htmlspecialchars($type) . "**</p>";
    }
    if (!is_numeric($amount) || $amount <= 0) {
        $error_found = true;
        $status_message = "<p class='form-error-message'>Validation Error: Please enter a valid amount greater than zero.</p>";
    }

    // 3. Insert into transactions table if no errors
    if (!$error_found) {
        $sql_insert = "
            INSERT INTO transactions 
            (profile_id, transaction_date, type, amount, payment_method, note) 
            VALUES (?, ?, ?, ?, ?, ?)
        ";
        
        if ($stmt = $conn->prepare($sql_insert)) {
            // CRITICAL: Bind sequence must match column list: i(id), s(date), s(type), d(amount), s(method), s(note)
            if ($stmt->bind_param("isdsss", $profile_id, $transaction_date, $type, $amount, $payment_method, $note)) {
                
                if ($stmt->execute()) {
                    // Success - Redirect back to the profile view with status flag
                    header("location: view_profile.php?id=" . $profile_id . "&status=success");
                    exit;
                } else {
                    $status_message = "<p class='form-error-message'>Database Execution Error: " . htmlspecialchars($stmt->error) . ". Profile ID: " . $profile_id . "</p>";
                }
            } else {
                $status_message = "<p class='form-error-message'>Bind Error: Failed to bind parameters.</p>";
            }
            $stmt->close();
        } else {
             $status_message = "<p class='form-error-message'>Database Prepare Error: Failed to prepare statement: " . htmlspecialchars($conn->error) . "</p>";
        }
    }
    // Display the status message if an error prevented redirection
    echo (isset($status_message) ? $status_message : '');
}

// =================================================================
// HTML FORM STRUCTURE (CONFIRMING CORRECT INPUT NAMES)
// =================================================================
?>

<style>
    /* Styling specific to the form/error messages when included */
    .form-error-message { color: #721c24; background-color: #f8d7da; padding: 10px; border-radius: 5px; border: 1px solid #f5c6cb; margin-bottom: 15px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
    .form-group input, .form-group select, .form-group textarea { 
        width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 4px; box-sizing: border-box; 
    }
    .submit-btn { 
        background-color: #28a745; 
        color: white; 
        padding: 12px 20px; 
        border: none; 
        border-radius: 5px; 
        cursor: pointer; 
        font-weight: bold;
        width: 100%;
        transition: background-color 0.3s;
    }
    .submit-btn:hover { background-color: #218838; }
</style>

<form action="view_profile.php?id=<?php echo $profile_id; ?>" method="POST">

    <div class="form-group">
        <label for="date">Manual Date Choice:</label>
        <input type="date" id="date" name="transaction_date" required value="<?php echo date('Y-m-d'); ?>">
    </div>
    
    <div style="display: flex; gap: 20px;">
        <div class="form-group" style="flex: 1;">
            <label for="type">Transaction Type:</label>
            <select id="type" name="type" required>
                <option value="">-- Select --</option>
                <option value="credit">Credit (Money Received / User Owes You)</option>
                <option value="debit">Debit (Money Paid / You Owe User)</option>
            </select>
        </div>
        <div class="form-group" style="flex: 1;">
            <label for="amount">Amount (₹):</label>
            <input type="number" step="0.01" id="amount" name="amount" required min="0.01" placeholder="0.00">
        </div>
    </div>
    
    <div class="form-group">
        <label for="method">Payment Method:</label>
        <select id="method" name="payment_method" required>
            <option value="">-- Select Payment Method --</option>
            <option value="Cash">Cash</option>
            <option value="UPI">UPI</option>
            <option value="Online">Online Transfer</option>
            <option value="Credit Card">Credit Card</option>
            <option value="Debit Card">Debit Card</option>
            <option value="Cheque">Cheque</option> 
            <option value="Other">Other</option>
        </select>
    </div>

    <div class="form-group">
        <label for="note">Text Noted:</label>
        <textarea id="note" name="note" rows="3" placeholder="e.g., Paid full amount for March invoice. Received ₹5000 advance."></textarea>
    </div>
    
    <button type="submit" name="submit_transaction" class="submit-btn">Submit Record</button>
</form>
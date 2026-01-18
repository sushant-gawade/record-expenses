<?php
require_once 'db_connect.php';

// ========================
// FETCH EXISTING RECORD
// ========================
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: index.php");
    exit;
}

$transaction_id = intval($_GET['id']);
$transaction = null;
$profile_id = null;
$status_message = '';
$profile_name = "Unknown Profile";

// Fetch transaction
$sql_fetch = "SELECT * FROM transactions WHERE id = ?";
if ($stmt = $conn->prepare($sql_fetch)) {
    $stmt->bind_param("i", $transaction_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $transaction = $result->fetch_assoc();
        $profile_id = $transaction['profile_id'];
    } else {
        header("location: index.php");
        exit;
    }
    $stmt->close();
}

// Fetch profile name
$sql_name = "SELECT name FROM profiles WHERE id = ?";
if ($stmt = $conn->prepare($sql_name)) {
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $profile_name = $stmt->get_result()->fetch_assoc()['name'] ?? "Unknown Profile";
    $stmt->close();
}

// ========================
// UPDATE LOGIC
// ========================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_transaction'])) {

    $new_date   = trim($_POST['transaction_date']);
    $new_type   = trim($_POST['type']);
    $new_amount = trim($_POST['amount']);
    $new_method = trim($_POST['payment_method']);
    $new_note   = $conn->real_escape_string(trim($_POST['note']));

    // Validation
    if (empty($new_date) || empty($new_type) || empty($new_method) || !is_numeric($new_amount) || $new_amount <= 0) {
        $status_message = "<div class='status-error'>⚠️ Please fill all required fields correctly.</div>";
    } else {
        $sql_update = "
            UPDATE transactions SET 
                transaction_date = ?, 
                type = ?, 
                amount = ?, 
                payment_method = ?, 
                note = ?
            WHERE id = ? AND profile_id = ?
        ";
        if ($stmt = $conn->prepare($sql_update)) {
            $stmt->bind_param("ssdssii", $new_date, $new_type, $new_amount, $new_method, $new_note, $transaction_id, $profile_id);

            if ($stmt->execute()) {
                header("location: view_profile.php?id=" . $profile_id . "&status=trans_updated");
                exit;
            } else {
                $status_message = "<div class='status-error'>Database Error: " . $stmt->error . "</div>";
            }
            $stmt->close();
        }
    }

    // retain user input
    $transaction['transaction_date'] = $new_date;
    $transaction['type'] = $new_type;
    $transaction['amount'] = $new_amount;
    $transaction['payment_method'] = $new_method;
    $transaction['note'] = $new_note;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Transaction - <?php echo htmlspecialchars($profile_name); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 15px;
        }
        
        .container {
            background: #fff;
            width: 100%;
            max-width: 450px;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        
        h2 { 
            text-align: center; 
            color: #333; 
            margin-bottom: 10px;
            font-size: 1.8em;
        }
        
        p.subtitle { 
            text-align: center; 
            color: #666;
            margin-bottom: 25px;
        }
        
        label { 
            font-weight: 600; 
            display: block; 
            color: #555; 
            margin-bottom: 8px;
            font-size: 0.95em;
        }
        
        input, select, textarea {
            width: 100%; 
            padding: 14px; 
            border-radius: 8px; 
            border: 2px solid #e0e0e0;
            margin-bottom: 18px; 
            font-size: 16px; 
            font-family: inherit;
            transition: all 0.3s;
        }
        
        input:focus, select:focus, textarea:focus {
            border-color: #667eea; 
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        button, a.cancel-btn {
            display: inline-block; 
            width: 48%; 
            text-align: center; 
            padding: 14px;
            border: none; 
            border-radius: 8px; 
            color: #fff; 
            font-weight: 600;
            cursor: pointer; 
            transition: all 0.3s; 
            text-decoration: none;
            font-size: 1em;
        }
        
        button { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        button:hover { 
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        a.cancel-btn { 
            background: #6c757d;
        }
        
        a.cancel-btn:hover { 
            background: #5a6268; 
            transform: translateY(-2px);
        }
        
        .status-error {
            background-color: #f8d7da; 
            color: #721c24; 
            padding: 12px;
            border-radius: 8px; 
            border: 1px solid #f5c6cb; 
            margin-bottom: 20px;
            text-align: center;
        }
        
        .btn-group { 
            display: flex; 
            justify-content: space-between;
            gap: 10px;
        }
        
        /* Mobile optimization */
        @media (max-width: 480px) {
            .container {
                padding: 25px 20px;
            }
            
            h2 {
                font-size: 1.5em;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            button, a.cancel-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>✏️ Edit Transaction</h2>
    <p class="subtitle">For <b><?php echo htmlspecialchars($profile_name); ?></b></p>

    <?php echo $status_message; ?>

    <form id="editForm" method="POST" action="edit_transaction.php?id=<?php echo $transaction_id; ?>">
        <label for="transaction_date">Transaction Date: *</label>
        <input type="date" id="transaction_date" name="transaction_date" required
            value="<?php echo htmlspecialchars($transaction['transaction_date']); ?>">

        <label for="type">Transaction Type: *</label>
        <select name="type" id="type" required>
            <option value="">Select Type</option>
            <option value="credit" <?php echo ($transaction['type'] == 'credit') ? 'selected' : ''; ?>>💰 Credit (Money In)</option>
            <option value="debit" <?php echo ($transaction['type'] == 'debit') ? 'selected' : ''; ?>>💸 Debit (Money Out)</option>
        </select>

        <label for="amount">Amount (₹): *</label>
        <input type="number" step="0.01" id="amount" name="amount" required min="0.01"
            value="<?php echo htmlspecialchars($transaction['amount']); ?>">

        <label for="payment_method">Payment Method: *</label>
        <select id="payment_method" name="payment_method" required>
            <?php 
                $methods = ['Cash','UPI','Online','Credit Card','Debit Card'];
                foreach ($methods as $m) {
                    $selected = ($transaction['payment_method'] == $m) ? 'selected' : '';
                    echo "<option value='{$m}' {$selected}>{$m}</option>";
                }
            ?>
        </select>

        <label for="note">Note (optional):</label>
        <textarea id="note" name="note" rows="3"><?php echo htmlspecialchars($transaction['note']); ?></textarea>

        <div class="btn-group">
            <button type="submit" name="update_transaction">💾 Update</button>
            <a href="view_profile.php?id=<?php echo $profile_id; ?>" class="cancel-btn">❌ Cancel</a>
        </div>
    </form>
</div>

<script>
document.getElementById('editForm').addEventListener('submit', function(e) {
    const type = document.getElementById('type').value;
    const amount = document.getElementById('amount').value;
    const method = document.getElementById('payment_method').value;
    const date = document.getElementById('transaction_date').value;
    if (!type || !amount || !method || !date) {
        e.preventDefault();
        alert("Please fill all required fields.");
    }
});
</script>

</body>
</html>

<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $profile_id = mysqli_real_escape_string($conn, $_POST['profile_id']);
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $note = mysqli_real_escape_string($conn, $_POST['note']);
    $transaction_date = mysqli_real_escape_string($conn, $_POST['transaction_date']);
    $type = isset($_POST['type']) ? mysqli_real_escape_string($conn, $_POST['type']) : '';

    // Validation
    if (empty($profile_id) || empty($type) || empty($amount) || empty($payment_method) || empty($transaction_date)) {
        echo "<script>alert('Please fill all required fields.'); window.history.back();</script>";
        exit;
    }

    $query = "INSERT INTO transactions (profile_id, type, amount, payment_method, note, transaction_date)
              VALUES ('$profile_id', '$type', '$amount', '$payment_method', '$note', '$transaction_date')";

    if (mysqli_query($conn, $query)) {
        echo "<script>alert('✅ Transaction added successfully!'); window.location.href='view_profile.php?id=$profile_id';</script>";
    } else {
        echo "<script>alert('Error adding transaction: " . mysqli_error($conn) . "'); window.history.back();</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Transaction</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #74ebd5 0%, #9face6 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 15px;
        }
        
        .container {
            background: #fff;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
        }
        
        h2 {
            color: #333;
            margin-bottom: 25px;
            text-align: center;
            font-size: 1.8em;
        }
        
        form label {
            display: block;
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
            font-size: 0.95em;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 14px;
            margin-bottom: 18px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
            transition: all 0.3s;
        }
        
        input:focus, select:focus, textarea:focus {
            border-color: #74ebd5;
            outline: none;
            box-shadow: 0 0 0 3px rgba(116, 235, 213, 0.1);
        }
        
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        button {
            width: 100%;
            background: linear-gradient(135deg, #74ebd5 0%, #9face6 100%);
            color: #fff;
            font-weight: bold;
            border: none;
            padding: 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1em;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(116, 235, 213, 0.3);
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(116, 235, 213, 0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #74ebd5;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: #9face6;
        }
        
        /* Mobile optimization */
        @media (max-width: 480px) {
            .container {
                padding: 25px 20px;
            }
            
            h2 {
                font-size: 1.5em;
            }
            
            input, select, textarea {
                padding: 12px;
            }
            
            button {
                padding: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>➕ Add Transaction</h2>
        <form id="transactionForm" method="POST" action="">
            <input type="hidden" name="profile_id" value="<?php echo $_GET['id']; ?>">

            <label for="type">Transaction Type: *</label>
            <select name="type" id="type" required>
                <option value="">Select Type</option>
                <option value="credit">💰 Credit (Money In)</option>
                <option value="debit">💸 Debit (Money Out)</option>
            </select>

            <label for="amount">Amount (₹): *</label>
            <input type="number" step="0.01" name="amount" id="amount" required placeholder="0.00" min="0.01">

            <label for="payment_method">Payment Method: *</label>
            <select name="payment_method" id="payment_method" required>
                <option value="">Select Method</option>
                <option value="Cash">💵 Cash</option>
                <option value="UPI">📱 UPI</option>
                <option value="Online">🌐 Online Transfer</option>
                <option value="Credit Card">💳 Credit Card</option>
                <option value="Debit Card">💳 Debit Card</option>
            </select>

            <label for="transaction_date">Transaction Date: *</label>
            <input type="date" name="transaction_date" id="transaction_date" required value="<?php echo date('Y-m-d'); ?>">

            <label for="note">Note (optional):</label>
            <textarea name="note" id="note" rows="3" placeholder="Add any additional details..."></textarea>

            <button type="submit">Add Transaction</button>
        </form>
        
        <a href="view_profile.php?id=<?php echo $_GET['id']; ?>" class="back-link">← Back to Profile</a>
    </div>

    <script>
        // Simple front-end validation
        document.getElementById('transactionForm').addEventListener('submit', function(e) {
            const type = document.getElementById('type').value;
            const amount = document.getElementById('amount').value;
            const paymentMethod = document.getElementById('payment_method').value;
            const date = document.getElementById('transaction_date').value;

            if (!type || !amount || !paymentMethod || !date) {
                e.preventDefault();
                alert('Please fill all required fields correctly.');
            }
        });
    </script>
</body>
</html>

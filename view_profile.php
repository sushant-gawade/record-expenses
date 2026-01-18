<?php
// =================================================================
// PHP LOGIC: DATABASE CONNECTION, DATA FETCHING, AND CALCULATIONS
// =================================================================

require_once 'db_connect.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: index.php");
    exit;
}

$profile_id = intval($_GET['id']);
$profile = null;
$transactions = [];
$status_message = '';

// --- 1. Handle Delete Profile Action ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_profile'])) {
    $sql_delete = "DELETE FROM profiles WHERE id = ?";
    if ($stmt = $conn->prepare($sql_delete)) {
        $stmt->bind_param("i", $profile_id);
        if ($stmt->execute()) {
            header("location: index.php?status=deleted");
            exit;
        } else {
            $status_message = "<div class='status-error'>Error deleting profile: " . htmlspecialchars($conn->error) . "</div>";
        }
        $stmt->close();
    }
}

// --- 2. Fetch Profile Details ---
$sql_profile = "SELECT id, name, mobile FROM profiles WHERE id = ?";
if ($stmt = $conn->prepare($sql_profile)) {
    $stmt->bind_param("i", $profile_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $profile = $result->fetch_assoc();
        } else {
            header("location: index.php");
            exit;
        }
    }
    $stmt->close();
}

// --- 3. Calculate Financial Summary ---
$sql_summary = "
    SELECT 
        COALESCE(SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END), 0) AS total_credit,
        COALESCE(SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END), 0) AS total_debit
    FROM transactions WHERE profile_id = ?
";
$total_credit = $total_debit = $balance = 0;

if ($stmt = $conn->prepare($sql_summary)) {
    $stmt->bind_param("i", $profile_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $total_credit = $row['total_credit'];
            $total_debit = $row['total_debit'];
            $balance = $total_credit - $total_debit;
        }
    }
    $stmt->close();
}

// --- 4. Fetch Transaction History ---
$sql_history = "SELECT id, transaction_date, type, amount, payment_method, note FROM transactions WHERE profile_id = ? ORDER BY transaction_date DESC, created_at DESC";
if ($stmt = $conn->prepare($sql_history)) {
    $stmt->bind_param("i", $profile_id);
    if ($stmt->execute()) {
        $transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();
}

// Check for status messages
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        $status_message = "<div class='status-success'>✅ Transaction recorded successfully!</div>";
    }
    if ($_GET['status'] == 'trans_deleted') {
        $status_message = "<div class='status-success'>✅ Transaction deleted successfully!</div>";
    }
    if ($_GET['status'] == 'trans_updated') {
        $status_message = "<div class='status-success'>✅ Transaction updated successfully!</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile: <?php echo htmlspecialchars($profile['name']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: #f0f2f5; 
            color: #333;
            padding: 15px;
        }
        
        .container { 
            max-width: 1000px; 
            margin: 0 auto; 
            background-color: white; 
            padding: 20px; 
            border-radius: 12px; 
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .status-success, .status-error { 
            padding: 12px; 
            border-radius: 8px; 
            margin-bottom: 20px;
            text-align: center;
        }
        
        .status-success { 
            background-color: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb;
        }
        
        .status-error { 
            background-color: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb;
        }

        .back-link {
            display: inline-block;
            color: #007bff;
            text-decoration: none;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .profile-header { 
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 25px; 
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .profile-info h2 { 
            margin: 0; 
            font-size: 1.6em; 
            color: #007bff;
        }
        
        .profile-info p { 
            color: #6c757d; 
            margin: 5px 0 0 0;
        }
        
        .delete-btn { 
            background-color: #dc3545; 
            color: white; 
            padding: 10px 15px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
        }
        
        .delete-btn:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }
        
        /* Summary Cards */
        .summary-cards { 
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .card { 
            padding: 15px; 
            border-radius: 10px; 
            color: white; 
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .card-credit { background: linear-gradient(135deg, #17a2b8, #138496); } 
        .card-debit { background: linear-gradient(135deg, #ffc107, #e0a800); color: #333; } 
        .card-balance-pos { background: linear-gradient(135deg, #28a745, #218838); }
        .card-balance-neg { background: linear-gradient(135deg, #dc3545, #c82333); }
        
        .card h3 { 
            margin: 0 0 8px 0; 
            font-size: 0.9em; 
            opacity: 0.95;
        }
        
        .card p { 
            font-size: 1.5em; 
            font-weight: bold; 
            margin: 0;
        }
        
        .card small {
            display: block;
            margin-top: 5px;
            font-size: 0.8em;
        }
        
        /* Action Buttons */
        .action-buttons { 
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 25px;
        }
        
        .payment-btn { 
            background-color: #007bff; 
            color: white; 
            padding: 14px 20px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-weight: bold;
            text-decoration: none;
            display: block;
            text-align: center;
            transition: all 0.3s;
        }
        
        .payment-btn:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }
        
        .backup-btn-profile {
            background-color: #6c757d;
        }
        
        .backup-btn-profile:hover {
            background-color: #5a6268;
        }
        
        /* Transaction History Section */
        h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        /* Mobile Card View for Transactions */
        .transaction-cards {
            display: block;
        }
        
        .transaction-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 12px;
        }
        
        .trans-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        
        .trans-type {
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .trans-credit { color: #28a745; }
        .trans-debit { color: #dc3545; }
        
        .trans-amount {
            font-size: 1.2em;
            font-weight: bold;
        }
        
        .trans-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 10px;
            font-size: 0.9em;
        }
        
        .trans-detail-item {
            color: #6c757d;
        }
        
        .trans-detail-item strong {
            color: #333;
            display: block;
        }
        
        .trans-note {
            background: white;
            padding: 10px;
            border-radius: 6px;
            font-size: 0.9em;
            color: #555;
            margin-bottom: 10px;
        }
        
        .trans-actions {
            display: flex;
            gap: 8px;
        }
        
        .edit-btn, .delete-trans-btn { 
            flex: 1;
            padding: 8px 12px; 
            border-radius: 6px; 
            border: none; 
            cursor: pointer;
            font-size: 0.9em; 
            text-decoration: none;
            text-align: center;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .edit-btn { 
            background-color: #ffc107; 
            color: #333;
        }
        
        .edit-btn:hover {
            background-color: #e0a800;
        }
        
        .delete-trans-btn { 
            background-color: #dc3545; 
            color: white;
        }
        
        .delete-trans-btn:hover {
            background-color: #c82333;
        }
        
        /* Desktop Table View (Hidden on Mobile) */
        .history-table-wrapper {
            display: none;
            overflow-x: auto;
        }
        
        .history-table { 
            width: 100%; 
            border-collapse: collapse;
        }
        
        .history-table th, .history-table td { 
            padding: 12px 15px; 
            border-bottom: 1px solid #e9ecef; 
            text-align: left;
        }
        
        .history-table th { 
            background-color: #f8f9fa; 
            font-weight: 600; 
            color: #555;
        }
        
        .history-table tbody tr:hover { 
            background-color: #f5f5f5;
        }
        
        .no-transactions {
            text-align: center;
            color: #6c757d;
            padding: 40px 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        /* Tablet and Desktop */
        @media (min-width: 768px) {
            .container {
                padding: 30px;
            }
            
            .profile-header {
                flex-direction: row;
                justify-content: space-between;
                align-items: flex-start;
            }
            
            .profile-info h2 {
                font-size: 2em;
            }
            
            .delete-btn {
                width: auto;
            }
            
            .card p {
                font-size: 1.8em;
            }
            
            .action-buttons {
                flex-direction: row;
            }
            
            .payment-btn {
                flex: 0 1 auto;
            }
            
            /* Show table on desktop */
            .history-table-wrapper {
                display: block;
            }
            
            /* Hide cards on desktop */
            .transaction-cards {
                display: none;
            }
        }
        
        @media (min-width: 480px) and (max-width: 767px) {
            .summary-cards {
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← Back to Dashboard</a>
        
        <?php echo $status_message; ?>
        
        <div class="profile-header">
            <div class="profile-info">
                <h2><?php echo htmlspecialchars($profile['name']); ?></h2>
                <p>📱 <?php echo htmlspecialchars($profile['mobile']); ?></p>
            </div>
            <form method="POST" onsubmit="return confirm('⚠️ WARNING: Are you sure you want to delete this profile and ALL associated transactions? This cannot be undone.')">
                <input type="hidden" name="profile_id" value="<?php echo $profile_id; ?>">
                <button type="submit" name="delete_profile" class="delete-btn">❌ Delete Profile</button>
            </form>
        </div>

        <div class="summary-cards">
            <div class="card card-credit">
                <h3>Credit (In)</h3>
                <p>₹ <?php echo number_format($total_credit, 2); ?></p>
            </div>
            <div class="card card-debit">
                <h3>Debit (Out)</h3>
                <p>₹ <?php echo number_format($total_debit, 2); ?></p>
            </div>
            <div class="card <?php echo ($balance >= 0) ? 'card-balance-pos' : 'card-balance-neg'; ?>">
                <h3>Net Balance</h3>
                <p>₹ <?php echo number_format(abs($balance), 2); ?></p>
                <small>
                    <?php 
                        if ($balance >= 0) {
                            echo 'Receivable';
                        } else {
                            echo 'Payable';
                        }
                    ?>
                </small>
            </div>
        </div>

        <div class="action-buttons">
            <a href="add_transaction.php?id=<?php echo $profile_id; ?>" class="payment-btn">
                ➕ Add New Transaction
            </a>
            <a href="backup_transaction_history.php?id=<?php echo $profile_id; ?>" class="payment-btn backup-btn-profile">
                ⬇️ Backup History (PDF)
            </a>
        </div>
        
        <h3>📋 Transaction History</h3>
        
        <?php if (!empty($transactions)): ?>
            
            <!-- Mobile Card View -->
            <div class="transaction-cards">
                <?php foreach ($transactions as $t): ?>
                    <div class="transaction-card">
                        <div class="trans-header">
                            <div>
                                <span class="trans-type <?php echo ($t['type'] == 'credit') ? 'trans-credit' : 'trans-debit'; ?>">
                                    <?php echo ucfirst(htmlspecialchars($t['type'])); ?>
                                </span>
                            </div>
                            <span class="trans-amount <?php echo ($t['type'] == 'credit') ? 'trans-credit' : 'trans-debit'; ?>">
                                ₹ <?php echo number_format($t['amount'], 2); ?>
                            </span>
                        </div>
                        
                        <div class="trans-details">
                            <div class="trans-detail-item">
                                <strong>Date</strong>
                                <?php echo date('d M Y', strtotime($t['transaction_date'])); ?>
                            </div>
                            <div class="trans-detail-item">
                                <strong>Method</strong>
                                <?php echo htmlspecialchars($t['payment_method']); ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($t['note'])): ?>
                            <div class="trans-note">
                                <strong>Note:</strong> <?php echo htmlspecialchars($t['note']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="trans-actions">
                            <a href="edit_transaction.php?id=<?php echo $t['id']; ?>" class="edit-btn">✏️ Edit</a>
                            <button class="delete-trans-btn" onclick="deleteTransaction(<?php echo $t['id']; ?>, <?php echo $profile_id; ?>)">🗑️ Delete</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Desktop Table View -->
            <div class="history-table-wrapper">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th style="width: 12%;">Date</th>
                            <th style="width: 8%;">Type</th>
                            <th style="width: 15%;">Amount (₹)</th>
                            <th style="width: 15%;">Payment Method</th>
                            <th style="width: 30%;">Note</th>
                            <th style="width: 20%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><?php echo date('Y-m-d', strtotime($t['transaction_date'])); ?></td>
                                <td class="<?php echo ($t['type'] == 'credit') ? 'trans-credit' : 'trans-debit'; ?>">
                                    <?php echo ucfirst(htmlspecialchars($t['type'])); ?>
                                </td>
                                <td>₹ <?php echo number_format($t['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($t['payment_method']); ?></td>
                                <td><?php echo htmlspecialchars(substr($t['note'], 0, 50)) . (strlen($t['note']) > 50 ? '...' : ''); ?></td>
                                <td>
                                    <a href="edit_transaction.php?id=<?php echo $t['id']; ?>" class="edit-btn">Edit</a>
                                    <button class="delete-trans-btn" onclick="deleteTransaction(<?php echo $t['id']; ?>, <?php echo $profile_id; ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
        <?php else: ?>
            <div class="no-transactions">
                No transactions recorded for this profile.
            </div>
        <?php endif; ?>
    </div>

    <script>
        function deleteTransaction(transactionId, profileId) {
            if (confirm('Are you sure you want to delete this specific transaction?')) {
                window.location.href = `delete_transaction.php?trans_id=${transactionId}&profile_id=${profileId}`;
            }
        }
    </script>
</body>
</html>

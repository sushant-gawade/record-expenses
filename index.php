<?php
// =================================================================
// PHP LOGIC: DATABASE CONNECTION AND FINANCIAL CALCULATIONS
// =================================================================

// Include the database connection file
require_once 'db_connect.php';

// Initialize variables
$profiles = [];
$overall_collected = 0; // Sum of all positive balances (Money owed TO YOU)
$overall_pending = 0;   // Sum of all negative balances (Money owed BY YOU / Advance)
$error_message = "";

// SQL Query to fetch ALL profiles and calculate their summary Credit/Debit/Balance
$sql = "
    SELECT 
        p.id, 
        p.name, 
        p.mobile,
        COALESCE(SUM(CASE WHEN t.type = 'credit' THEN t.amount ELSE 0 END), 0) AS total_credit,
        COALESCE(SUM(CASE WHEN t.type = 'debit' THEN t.amount ELSE 0 END), 0) AS total_debit
    FROM 
        profiles p
    LEFT JOIN 
        transactions t ON p.id = t.profile_id
    GROUP BY 
        p.id, p.name, p.mobile
    ORDER BY 
        p.name ASC
";

if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        // Calculate the net balance for the profile
        $row['balance'] = $row['total_credit'] - $row['total_debit'];
        
        // Calculate the overall system totals
        if ($row['balance'] > 0) {
            $overall_collected += $row['balance'];
        } elseif ($row['balance'] < 0) {
            $overall_pending += abs($row['balance']);
        }
        
        $profiles[] = $row;
    }
    $result->free();
} else {
    $error_message = "Error fetching profiles: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Main Dashboard - Account Management System</title>
    
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
            padding: 10px;
        }
        
        .dashboard { 
            max-width: 1200px; 
            margin: 0 auto; 
        }
        
        h1 { 
            color: #007bff; 
            margin-bottom: 20px; 
            border-bottom: 3px solid #007bff; 
            padding-bottom: 10px;
            font-size: 1.8em;
        }
        
        /* Summary Cards (Responsive Grid) */
        .summary-cards { 
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .card { 
            padding: 20px; 
            border-radius: 12px; 
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); 
            color: white; 
            transition: transform 0.2s;
        }
        
        .card:hover { 
            transform: translateY(-3px); 
        }
        
        .collected { background: linear-gradient(135deg, #28a745, #20c997); }
        .pending { background: linear-gradient(135deg, #dc3545, #fd7e14); }
        
        .card h2 { 
            margin-top: 0; 
            font-size: 1em; 
            opacity: 0.95; 
            margin-bottom: 10px;
        }
        
        .card p { 
            font-size: 2em; 
            font-weight: bold; 
            margin: 0;
        }
        
        /* Header & Actions */
        .action-header { 
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        #search-bar { 
            padding: 12px; 
            border: 2px solid #ccc; 
            border-radius: 8px; 
            width: 100%;
            font-size: 16px; 
            transition: border-color 0.3s;
        }
        
        #search-bar:focus { 
            border-color: #007bff; 
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }
        
        .action-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .action-group a { 
            text-decoration: none; 
            flex: 1;
            min-width: 150px;
        }
        
        .create-btn, .backup-btn { 
            padding: 12px 20px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-weight: bold; 
            display: block;
            text-align: center;
            transition: all 0.3s;
            width: 100%;
        }
        
        .create-btn { 
            background-color: #007bff; 
            color: white; 
        }
        
        .create-btn:hover { 
            background-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .backup-btn { 
            background-color: #6c757d; 
            color: white; 
        }
        
        .backup-btn:hover { 
            background-color: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        /* Profile List */
        .profile-list-container { 
            background-color: white; 
            padding: 15px; 
            border-radius: 12px; 
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }
        
        .profile-list-container h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        /* Mobile-First: Card Layout */
        .profile-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .profile-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .profile-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        
        .profile-info strong {
            font-size: 1.1em;
            color: #007bff;
            display: block;
            margin-bottom: 4px;
        }
        
        .profile-info small {
            color: #6c757d;
        }
        
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 12px;
        }
        
        .stat-item {
            background: #f8f9fa;
            padding: 8px;
            border-radius: 6px;
            text-align: center;
        }
        
        .stat-label {
            font-size: 0.85em;
            color: #6c757d;
            display: block;
            margin-bottom: 4px;
        }
        
        .stat-value {
            font-weight: bold;
            font-size: 1em;
        }
        
        .balance-pos { color: #28a745; }
        .balance-neg { color: #dc3545; }
        
        .view-btn { 
            background-color: #17a2b8; 
            color: white; 
            padding: 10px 15px; 
            border-radius: 6px; 
            text-decoration: none; 
            font-size: 0.95em; 
            transition: background-color 0.3s;
            display: inline-block;
            width: 100%;
            text-align: center;
        }
        
        .view-btn:hover { 
            background-color: #138496; 
        }
        
        /* Desktop Table (Hidden on Mobile) */
        .profile-table-wrapper {
            display: none;
            overflow-x: auto;
        }
        
        .profile-table { 
            width: 100%; 
            border-collapse: separate; 
            border-spacing: 0;
        }
        
        .profile-table th, .profile-table td { 
            padding: 12px 15px; 
            border-bottom: 1px solid #e9ecef; 
            text-align: left; 
        }
        
        .profile-table th { 
            background-color: #f8f9fa; 
            font-weight: 600; 
            color: #555; 
            border-top: 1px solid #e9ecef;
            position: sticky;
            top: 0;
        }
        
        .profile-table tbody tr:hover { 
            background-color: #f5f5f5; 
        }
        
        .error-message {
            padding: 15px;
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .no-profiles {
            text-align: center;
            color: #6c757d;
            padding: 40px 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        /* Tablet and Desktop */
        @media (min-width: 768px) {
            body {
                padding: 20px;
            }
            
            h1 {
                font-size: 2.2em;
            }
            
            .action-header {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
            
            #search-bar {
                width: 350px;
            }
            
            .action-group a {
                flex: 0;
            }
            
            .card p {
                font-size: 2.5em;
            }
            
            .profile-list-container {
                padding: 20px;
            }
            
            /* Show table on desktop */
            .profile-table-wrapper {
                display: block;
            }
            
            /* Hide cards on desktop */
            .profile-cards-wrapper {
                display: none;
            }
        }
        
        @media (max-width: 767px) {
            .card p {
                font-size: 1.8em;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <h1>📊 Account Management</h1>

        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <strong>Error:</strong> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="summary-cards">
            <div class="card collected">
                <h2>💰 Overall Collected</h2>
                <p>₹ <?php echo number_format($overall_collected, 2); ?></p>
            </div>
            <div class="card pending">
                <h2>⏳ Overall Pending</h2>
                <p>₹ <?php echo number_format($overall_pending, 2); ?></p>
            </div>
        </div>

        <div class="action-header">
            <input type="text" id="search-bar" placeholder="🔍 Search profile by name or mobile...">
            <div class="action-group">
                <a href="backup_all_profiles.php" class="backup-btn">⬇️ Backup Data</a> 
                <a href="create_profile.php" class="create-btn">➕ Create Profile</a>
            </div>
        </div>
        
        <div class="profile-list-container">
            <h3>Profile Ledger List</h3>
            
            <?php if (!empty($profiles)): ?>
                
                <!-- Mobile Card View -->
                <div class="profile-cards-wrapper" id="profile-cards">
                    <?php foreach ($profiles as $profile): ?>
                        <div class="profile-card" data-name="<?php echo strtolower(htmlspecialchars($profile['name'] . ' ' . $profile['mobile'])); ?>">
                            <div class="profile-card-header">
                                <div class="profile-info">
                                    <strong><?php echo htmlspecialchars($profile['name']); ?></strong>
                                    <small><?php echo htmlspecialchars($profile['mobile']); ?></small>
                                </div>
                            </div>
                            
                            <div class="profile-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Credit</span>
                                    <span class="stat-value">₹ <?php echo number_format($profile['total_credit'], 2); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Debit</span>
                                    <span class="stat-value">₹ <?php echo number_format($profile['total_debit'], 2); ?></span>
                                </div>
                            </div>
                            
                            <div class="stat-item" style="margin-bottom: 12px;">
                                <span class="stat-label">Balance</span>
                                <span class="stat-value <?php echo ($profile['balance'] >= 0) ? 'balance-pos' : 'balance-neg'; ?>">
                                    ₹ <?php echo number_format($profile['balance'], 2); ?>
                                </span>
                            </div>
                            
                            <a href="view_profile.php?id=<?php echo $profile['id']; ?>" class="view-btn">View Profile</a>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Desktop Table View -->
                <div class="profile-table-wrapper">
                    <table class="profile-table" id="profile-table">
                        <thead>
                            <tr>
                                <th style="width: 30%;">Profile Name (Mobile)</th>
                                <th style="width: 20%;">Credit (In)</th>
                                <th style="width: 20%;">Debit (Out)</th>
                                <th style="width: 15%;">Balance</th>
                                <th style="width: 15%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($profiles as $profile): ?>
                                <tr data-name="<?php echo strtolower(htmlspecialchars($profile['name'] . ' ' . $profile['mobile'])); ?>">
                                    <td>
                                        <strong><?php echo htmlspecialchars($profile['name']); ?></strong><br>
                                        <small style="color:#6c757d;"><?php echo htmlspecialchars($profile['mobile']); ?></small>
                                    </td>
                                    <td>₹ <?php echo number_format($profile['total_credit'], 2); ?></td>
                                    <td>₹ <?php echo number_format($profile['total_debit'], 2); ?></td>
                                    <td class="<?php echo ($profile['balance'] >= 0) ? 'balance-pos' : 'balance-neg'; ?>">
                                        ₹ <?php echo number_format($profile['balance'], 2); ?>
                                    </td>
                                    <td>
                                        <a href="view_profile.php?id=<?php echo $profile['id']; ?>" class="view-btn">View Profile</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
            <?php else: ?>
                <div class="no-profiles">
                    No profiles found. Click 'Create Profile' to begin tracking accounts.
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        document.getElementById('search-bar').addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            
            // Search in cards (mobile)
            const cards = document.querySelectorAll('.profile-card');
            cards.forEach(card => {
                const cardData = card.getAttribute('data-name');
                if (cardData && cardData.indexOf(searchText) > -1) {
                    card.style.display = "";
                } else {
                    card.style.display = "none";
                }
            });
            
            // Search in table (desktop)
            const tableBody = document.querySelector('#profile-table tbody');
            if (tableBody) {
                const rows = tableBody.getElementsByTagName('tr');
                for (let i = 0; i < rows.length; i++) {
                    const rowData = rows[i].getAttribute('data-name');
                    if (rowData && rowData.indexOf(searchText) > -1) {
                        rows[i].style.display = "";
                    } else {
                        rows[i].style.display = "none";
                    }
                }
            }
        });
    </script>
</body>
</html>

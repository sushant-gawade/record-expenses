<?php
// =================================================================
// PHP LOGIC: DATABASE CONNECTION AND FORM SUBMISSION HANDLING
// =================================================================

// 1. Include the database connection file
require_once 'db_connect.php';

// Initialize variables for error handling and data storage
$name_err = $mobile_err = "";
$name = $mobile = "";

// 2. Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_profile'])) {

    // --- Input Validation and Sanitization ---

    // Check name
    if (empty(trim($_POST["name"]))) {
        $name_err = "Please enter a name.";
    } else {
        // Sanitize name input
        $name = trim($_POST["name"]);
    }

    // Check mobile number
    if (empty(trim($_POST["mobile"]))) {
        $mobile_err = "Please enter a mobile number.";
    } else {
        $mobile = trim($_POST["mobile"]);
        // Server-side check for 10-15 digits
        if (!preg_match('/^[0-9]{10,15}$/', $mobile)) {
             $mobile_err = "Mobile number must be 10-15 digits and contain only numbers.";
        }
    }

    // 3. Check for Mobile Number Duplication
    if (empty($name_err) && empty($mobile_err)) {
        $sql = "SELECT id FROM profiles WHERE mobile = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_mobile);
            $param_mobile = $mobile;
            
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows == 1) {
                    $mobile_err = "This mobile number is already registered. Please use the dashboard to search for the existing profile.";
                }
            } else {
                // Database execution error
                $mobile_err = "Oops! Something went wrong during profile check. Please try again later.";
            }
            $stmt->close();
        }
    }
    
    // 4. Insert Profile into Database if no errors
    if (empty($name_err) && empty($mobile_err)) {
        $sql = "INSERT INTO profiles (name, mobile) VALUES (?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ss", $param_name, $param_mobile);
            
            // Set parameters
            $param_name = $name;
            $param_mobile = $mobile;
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Profile successfully created, redirect to the View Profile page
                $new_id = $conn->insert_id;
                header("location: view_profile.php?id=" . $new_id);
                exit;
            } else {
                echo "<p class='error'>Database Error: Could not create profile. " . $stmt->error . "</p>";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Profile - Account Manager</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            padding: 15px;
        }
        
        .container { 
            background-color: #ffffff; 
            padding: 30px; 
            border-radius: 16px; 
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2); 
            width: 100%; 
            max-width: 450px;
        }
        
        h2 { 
            text-align: center; 
            color: #333; 
            margin-bottom: 25px; 
            font-size: 1.8em;
        }
        
        label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600; 
            color: #555; 
            font-size: 0.95em;
        }
        
        input[type="text"], input[type="tel"] { 
            width: 100%; 
            padding: 14px; 
            margin-bottom: 20px; 
            border: 2px solid #e0e0e0; 
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        input[type="text"]:focus, input[type="tel"]:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        button { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; 
            padding: 16px 20px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            width: 100%; 
            font-size: 1.1em; 
            font-weight: bold;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        button:hover { 
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .error { 
            color: #721c24;
            background-color: #f8d7da;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #f5c6cb;
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.95em;
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-size: 0.95em;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: #764ba2;
        }
        
        /* Mobile optimization */
        @media (max-width: 480px) {
            .container {
                padding: 25px 20px;
            }
            
            h2 {
                font-size: 1.5em;
            }
            
            button {
                padding: 14px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>👤 Create New Profile</h2>
        
        <?php 
            if (!empty($name_err) || !empty($mobile_err)) {
                echo '<p class="error">' . trim($name_err . ' ' . $mobile_err) . '</p>';
            }
        ?>

        <form action="create_profile.php" method="POST" onsubmit="return validateForm()">
            <label for="name">Full Name:</label>
            <input type="text" id="name" name="name" required placeholder="Enter full name" value="<?php echo htmlspecialchars($name); ?>" autocomplete="name">
            
            <label for="mobile">Mobile Number:</label>
            <input type="tel" id="mobile" name="mobile" required placeholder="e.g., 9876543210" pattern="[0-9]{10,15}" title="Mobile number must be 10 to 15 digits" value="<?php echo htmlspecialchars($mobile); ?>" autocomplete="tel">
            
            <button type="submit" name="submit_profile">Create Profile</button>
        </form>

        <a href="index.php" class="back-link">← Back to Dashboard</a>
    </div>

    <script>
        function validateForm() {
            const mobileInput = document.getElementById('mobile');
            if (!mobileInput.checkValidity()) {
                alert("Please enter a valid mobile number (10-15 digits, numbers only).");
                return false;
            }
            return true;
        }
    </script>
</body>
</html>

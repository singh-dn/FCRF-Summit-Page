<?php
// Production Settings
error_reporting(0);
ini_set('display_errors', 0);

$message = "";
$messageType = "";
$showSuccessModal = false;
$successName = "";

// ================= CONFIGURATION ================= //
$db_host = "localhost";
$db_user = "u545411682_summit"; // Replace with actual DB user
$db_pass = "Summit2026";          // Replace with actual DB password
$db_name = "u545411682_summit"; // Replace with actual DB name
$table_name = "fcrf_hackathon_2026"; 

// 🔴 RECAPTCHA KEYS (Updated)
$recaptcha_site_key = "6LfkXYwsAAAAAO8Vwrhg7KdnocQzL-yQwl8zgTt4";
$recaptcha_secret = "6LfkXYwsAAAAAOg_C4CYVgNlQOyG9X1RU4Pl576h";

// --- SECURITY: Input Sanitization Function to prevent XSS ---
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    try {
        // 1. Verify reCAPTCHA
        if (empty($_POST['g-recaptcha-response'])) {
            throw new Exception("Please check the 'I am not a robot' box.");
        }

        $verify_url = "https://www.google.com/recaptcha/api/siteverify?secret={$recaptcha_secret}&response=" . $_POST['g-recaptcha-response'];
        $verify_response = file_get_contents($verify_url);
        $response_data = json_decode($verify_response);

        if (!$response_data->success) {
            throw new Exception("Robot verification failed. Please try again.");
        }

        // 2. Connect to DB
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($conn->connect_error) {
            throw new Exception("Database Connection Failed.");
        }
        $conn->set_charset("utf8mb4");

        // 3. Process & Validate Inputs Securely
        $full_name             = sanitize_input($_POST['full_name'] ?? '');
        $email                 = sanitize_input($_POST['email'] ?? '');
        $mobile                = sanitize_input($_POST['mobile'] ?? '');
        $city                  = sanitize_input($_POST['city'] ?? '');
        $state                 = sanitize_input($_POST['state'] ?? '');
        $current_status        = sanitize_input($_POST['current_status'] ?? '');
        $highest_qualification = sanitize_input($_POST['highest_qualification'] ?? '');
        $organization_name     = sanitize_input($_POST['organization_name'] ?? '');
        $current_role          = sanitize_input($_POST['current_role'] ?? '');
        $linkedin_url          = sanitize_input($_POST['linkedin_url'] ?? '');
        
        $dec1 = isset($_POST['dec1']);
        $dec2 = isset($_POST['dec2']);
        $dec3 = isset($_POST['dec3']);
        $dec4 = isset($_POST['dec4']);

        // --- STRICT VALIDATION LOGIC ---
        if (empty($full_name) || empty($email) || empty($mobile) || empty($city) || empty($state) || empty($current_status) || empty($highest_qualification) || empty($organization_name) || empty($current_role)) {
            throw new Exception("All fields marked with * are mandatory.");
        }

        if (!$dec1 || !$dec2 || !$dec3 || !$dec4) {
            throw new Exception("Please agree to all declarations to proceed.");
        }

        if (!preg_match("/^[a-zA-Z\s\.]+$/", $full_name)) {
            throw new Exception("Name cannot contain numbers or special characters.");
        }

        if (!preg_match("/^[0-9\+\-\s]+$/", $mobile) || strlen(preg_replace('/[^0-9]/', '', $mobile)) < 10) {
            throw new Exception("Please enter a valid mobile number.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        if (!empty($linkedin_url) && !filter_var($linkedin_url, FILTER_VALIDATE_URL)) {
            throw new Exception("Please provide a valid LinkedIn URL.");
        }

        // 4. File Upload Logic (Optional CV)
        $target_dir = "uploads/hackathon_cvs/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0755, true); }
        
        $cvPath = "";
        if (!empty($_FILES["cv"]["name"])) {
            $cvName = time() . "_cv_" . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES["cv"]["name"]));
            $cvPath = $target_dir . $cvName;
            
            $allowed_cv_mimes = [
                'application/pdf', 
                'application/msword', 
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES["cv"]["tmp_name"]);
            finfo_close($finfo);
            
            $cvType = strtolower(pathinfo($cvPath, PATHINFO_EXTENSION));
            if (!in_array($cvType, ['pdf', 'doc', 'docx']) || !in_array($mime, $allowed_cv_mimes)) { 
                throw new Exception("Invalid CV. Only PDF, DOC, or DOCX documents are allowed."); 
            }
            if ($_FILES["cv"]["size"] > 5000000) { throw new Exception("CV size must be less than 5MB."); }
            if (!move_uploaded_file($_FILES["cv"]["tmp_name"], $cvPath)) { throw new Exception("Failed to upload CV."); }
        }

        // 5. Insert into Database Securely (Prepared Statements prevent SQLi)
        $sql = "INSERT INTO $table_name (full_name, email, mobile, city, state, current_status, highest_qualification, organization_name, current_role, linkedin_url, cv_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) { throw new Exception("Database Error: " . $conn->error); }

        $stmt->bind_param("sssssssssss", $full_name, $email, $mobile, $city, $state, $current_status, $highest_qualification, $organization_name, $current_role, $linkedin_url, $cvPath);
        
        if ($stmt->execute()) {
            $showSuccessModal = true;
            $successName = $full_name;
            $_POST = array(); // Clear form values on success
        } else {
            throw new Exception("Failed to submit registration.");
        }
        
        $stmt->close();
        $conn->close();

    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- ================== Basic Meta ================== -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <title>Register for FCRF Hackathon 2026</title>
    <link rel="shortcut icon" href="assets/img/logo/favs.jpeg">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    
    <!-- Unified Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #0ea5e9; /* Cyber Cyan */
            --secondary: #10b981; /* Hacker Emerald */
            --gradient: linear-gradient(135deg, #0ea5e9, #10b981);
            --bg-dark: #0f172a;
            --bg-card: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --error: #ef4444;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(at 0% 0%, rgba(14, 165, 233, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.15) 0px, transparent 50%);
            background-attachment: fixed;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 3rem 1rem;
        }

        .form-container {
            background: var(--bg-card);
            width: 100%;
            max-width: 950px;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            overflow: hidden;
            position: relative;
        }

        /* Top Banner */
        .banner {
            background: var(--bg-dark);
            padding: 4rem 3rem;
            position: relative;
            overflow: hidden;
            border-bottom: 4px solid var(--primary);
        }
        
        .banner::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background-image: linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px);
            background-size: 30px 30px; opacity: 0.5;
        }

        .banner-content { position: relative; z-index: 1; }

        .banner-logo {
            max-width: 280px;
            height: auto;
            margin-bottom: 1.5rem;
            display: block;
        }

        .banner h1 {
            color: white; font-size: 2.5rem; font-weight: 800; line-height: 1.2;
            margin-bottom: 1rem; font-family: 'JetBrains Mono', monospace;
        }
        .banner h1 span { color: var(--primary); }
        .banner p { color: #94a3b8; font-size: 1.1rem; line-height: 1.6; max-width: 800px; }

        /* Form Body */
        .form-body { padding: 3rem; }

        .intro-text {
            background: #f8fafc;
            border-left: 4px solid var(--primary);
            padding: 1.5rem;
            border-radius: 0 12px 12px 0;
            margin-bottom: 2.5rem;
            color: #475569;
            font-size: 0.95rem;
            line-height: 1.6;
        }
        .intro-text p { margin-bottom: 10px; }
        .intro-text p:last-child { margin-bottom: 0; }

        .section-title {
            display: flex; align-items: center; gap: 10px; font-size: 1.25rem; font-weight: 700;
            color: var(--text-main); margin: 2.5rem 0 1.5rem; padding-bottom: 0.75rem; border-bottom: 2px solid #f1f5f9;
        }
        .section-title i { color: var(--primary); }
        .section-title:first-child { margin-top: 0; }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }

        label { display: block; font-weight: 600; font-size: 0.95rem; margin-bottom: 0.5rem; color: #334155; }
        .optional-tag { font-weight: 400; color: #94a3b8; font-size: 0.8rem; display: block; margin-top: 4px; }
        .inline-optional { font-weight: 400; color: #94a3b8; font-size: 0.8rem; }

        /* Input Wrapper for Icons */
        .input-wrapper { position: relative; display: flex; align-items: center; }
        .input-wrapper i { position: absolute; left: 14px; color: #94a3b8; transition: color 0.3s ease; pointer-events: none; }

        input[type="text"], input[type="email"], input[type="tel"], input[type="number"], input[type="url"], select, textarea {
            width: 100%; padding: 14px 16px 14px 44px; border-radius: 12px; border: 1px solid var(--border-color);
            background: #f8fafc; font-family: inherit; font-size: 1rem; color: var(--text-main); transition: all 0.3s ease;
        }
        select, textarea { padding-left: 16px; } 
        .input-wrapper select { padding-left: 44px; }
        
        textarea { resize: vertical; min-height: 120px; }

        input:focus, select:focus, textarea:focus {
            outline: none; border-color: var(--primary); background: white;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
        }
        .input-wrapper input:focus + i, .input-wrapper select:focus + i { color: var(--primary); }

        /* Radio & Checkbox Groups */
        .radio-label { display: flex; align-items: flex-start; gap: 12px; font-size: 0.95rem; color: #475569; cursor: pointer; padding: 10px 12px; border-radius: 8px; transition: background 0.2s; line-height: 1.4; }
        .radio-label:hover { background: white; }
        .radio-label input[type="radio"], .radio-label input[type="checkbox"] { accent-color: var(--primary); width: 1.2rem; height: 1.2rem; cursor: pointer; margin-top: 2px; }

        /* File Upload */
        .upload-grid { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
        .upload-area { border: 2px dashed var(--border-color); border-radius: 16px; padding: 2rem; text-align: center; background: #f8fafc; cursor: pointer; transition: all 0.3s; position: relative; display: block; height: 100%; }
        .upload-area:hover, .upload-area.dragover { border-color: var(--primary); background: #f0f9ff; }
        .upload-area i { color: var(--text-muted); margin-bottom: 10px; transition: color 0.3s; }
        .upload-area:hover i { color: var(--primary); }
        .upload-area.has-error { border-color: var(--error); background: #fef2f2; }
        .file-name { margin-top: 10px; font-size: 0.9rem; color: var(--secondary); font-weight: 600; display: none; word-break: break-all; }

        /* Error & Buttons */
        .error-box { background: #fee2e2; border-left: 4px solid var(--error); color: #b91c1c; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; font-weight: 500; display: flex; align-items: center; gap: 8px; }

        /* --- VALIDATION STYLES --- */
        .form-group.has-error input,
        .form-group.has-error select,
        .form-group.has-error textarea {
            border-color: var(--error);
            background-color: #fef2f2;
        }
        .form-group.has-error .input-wrapper i {
            color: var(--error);
        }
        .form-group.has-error::after {
            content: "⚠️ This field is required or invalid.";
            display: block;
            color: var(--error);
            font-size: 0.8rem;
            margin-top: 0.4rem;
            font-weight: 600;
            animation: popIn 0.3s ease;
        }
        .upload-area.has-error {
            border-color: var(--error);
            background-color: #fef2f2;
        }
        .upload-area.has-error i, .upload-area.has-error .upload-label {
            color: var(--error);
        }
        .upload-area.has-error::after {
            content: "⚠️ Required file missing";
            display: block;
            color: var(--error);
            font-size: 0.8rem;
            margin-top: 0.5rem;
            font-weight: 600;
        }
        .confirmation-card { background: #f8fafc; padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 0.5rem; }
        .confirmation-card.has-error {
            border-color: var(--error);
            background-color: #fef2f2;
        }
        .confirmation-card.has-error .radio-label { color: var(--error); }
        .confirmation-card.has-error::after {
            content: "⚠️ You must agree to all declarations.";
            display: block;
            color: var(--error);
            font-size: 0.8rem;
            margin-top: 0.5rem;
            font-weight: 600;
            animation: popIn 0.3s ease;
        }

        .submit-btn {
            width: 100%; background: var(--gradient); color: white; border: none; padding: 1.2rem; border-radius: 12px;
            font-size: 1.1rem; font-weight: 700; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; margin-top: 2rem; display: flex; justify-content: center; align-items: center; gap: 10px;
        }
        .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(14, 165, 233, 0.3); }

        .captcha-wrap { display: flex; justify-content: center; margin-top: 2rem; }

        .footer-note { text-align: center; margin-top: 1.5rem; font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; }

        /* --- IMMERSIVE SUCCESS MODAL --- */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.85); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal-content { background: white; padding: 3rem; border-radius: 24px; text-align: center; max-width: 450px; width: 90%; box-shadow: 0 0 0 1px rgba(255,255,255,0.1), 0 25px 50px -12px rgba(0,0,0,0.5); animation: scaleIn 0.4s cubic-bezier(0.16, 1, 0.3, 1); position: relative; }
        .icon-success { width: 80px; height: 80px; background: #ecfdf5; color: var(--secondary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; position: relative; }
        .icon-success::before { content: ''; position: absolute; inset: -10px; border-radius: 50%; border: 2px solid var(--secondary); animation: pulse 2s infinite; opacity: 0.5; }
        .countdown-text { font-size: 0.9rem; color: #94a3b8; margin-top: 1rem; font-weight: 500; }
        .countdown-text span { color: var(--primary); font-weight: 700; }

        @keyframes scaleIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
        @keyframes popIn { 0% { transform: scale(0.95); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
        @keyframes pulse { 0% { transform: scale(1); opacity: 0.8; } 100% { transform: scale(1.3); opacity: 0; } }

        /* Form Responsive Constraints */
        @media (max-width: 768px) {
            .banner { padding: 3rem 1.5rem; }
            .banner-logo { max-width: 200px; margin-bottom: 1rem; }
            .banner h1 { font-size: 2rem; }
            .form-body { padding: 2rem 1.5rem; }
            .grid-2, .grid-3, .upload-grid { grid-template-columns: 1fr; gap: 0; }
        }
    </style>
</head>
<body>

    <!-- SUCCESS MODAL (With Countdown & Redirection) -->
    <div class="modal-overlay <?php echo $showSuccessModal ? 'active' : ''; ?>" id="success-modal">
        <div class="modal-content">
            <div class="icon-success"><i data-lucide="check" size="40"></i></div>
            <h2 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 10px; color: var(--text-main);">Registration Complete!</h2>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem; line-height: 1.5;">
                Thank you, <strong style="color:var(--text-main);"><?php echo htmlspecialchars($successName); ?></strong>. Your registration for the FCRF Hackathon 2026 is confirmed.
            </p>
            <button onclick="forceRedirect()" class="submit-btn" style="margin-top:0; padding:1rem; font-size:1rem;">Continue</button>
            <div class="countdown-text">Auto-redirecting in <span id="timer">4</span> seconds...</div>
        </div>
    </div>

    <!-- THE MAIN FORM CONTAINER -->
    <div class="form-container">
        
        <div class="banner">
            <div class="banner-content">
                <img src="assets/img/logo/FCRF Hackathon.png" alt="FCRF Logo" class="banner-logo">
                <h1>Register for the FCRF <span>Hackathon 2026</span></h1>
                <p>Take on real-world challenges in cybercrime, digital forensics and emerging threats.</p>
            </div>
        </div>

        <div class="form-body">
            
            <div class="intro-text">
                <p>Complete the form below to register as an individual participant for the FCRF Hackathon 2026. Registered participants will begin receiving official communications, instructions and problem statements from <strong>12 July 2026</strong>.</p>
                <p>All eligible participants who complete the hackathon will receive an official <strong>Certificate of Participation</strong>. The top five performers will receive separate winner or merit certificates, complimentary passes to Future Crime Summit 2026, and formal recognition at the summit.</p>
            </div>

            <?php if (!empty($message) && $messageType == "error"): ?>
                <div class="error-box">
                    <i data-lucide="alert-triangle" size="20"></i> 
                    <span><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data" id="regForm" novalidate>
                
                <!-- Section 1: Identity -->
                <div class="section-title"><i data-lucide="user"></i> 1. Participant Details</div>
                
                <div class="form-group">
                    <label>Full Name *</label>
                    <span class="optional-tag mb-2">Enter your name exactly as it should appear on your certificate.</span>
                    <div class="input-wrapper">
                        <input type="text" name="full_name" required placeholder="e.g. John Doe"
                               pattern="[a-zA-Z\s\.]+" oninput="this.value = this.value.replace(/[^a-zA-Z\s\.]/g, '')"
                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                        <i data-lucide="user-circle" size="18"></i>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Email Address *</label>
                        <span class="optional-tag mb-2">Use an active email address for all communications.</span>
                        <div class="input-wrapper">
                            <input type="email" name="email" required placeholder="john@example.com"
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            <i data-lucide="mail" size="18"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Mobile Number *</label>
                        <span class="optional-tag mb-2">Include the country code (e.g. +91).</span>
                        <div class="input-wrapper">
                            <input type="tel" name="mobile" required placeholder="+91 98765 43210"
                                   pattern="[0-9\+\-\s]+" oninput="this.value = this.value.replace(/[^0-9\+\-\s]/g, '')"
                                   value="<?php echo isset($_POST['mobile']) ? htmlspecialchars($_POST['mobile']) : ''; ?>">
                            <i data-lucide="phone" size="18"></i>
                        </div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>City *</label>
                        <div class="input-wrapper">
                            <input type="text" name="city" required placeholder="e.g. Mumbai"
                                   value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>">
                            <i data-lucide="map-pin" size="18"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>State / Union Territory *</label>
                        <div class="input-wrapper">
                            <input type="text" name="state" required placeholder="e.g. Maharashtra"
                                   value="<?php echo isset($_POST['state']) ? htmlspecialchars($_POST['state']) : ''; ?>">
                            <i data-lucide="map" size="18"></i>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Professional Details -->
                <div class="section-title"><i data-lucide="briefcase"></i> 2. Academic / Professional Details</div>
                
                <div class="grid-2">
                    <div class="form-group">
                        <label>Current Status *</label>
                        <div class="input-wrapper">
                            <select name="current_status" required>
                                <option value="" disabled selected>Select Status</option>
                                <?php 
                                    $statuses = [
                                        "Undergraduate", 
                                        "Postgraduate", 
                                        "Research Scholar", 
                                        "Working Professional", 
                                        "Government/Law Enforcement Professional", 
                                        "Faculty/Academic Professional", 
                                        "Independent Professional", 
                                        "Other"
                                    ];
                                    $selected_s = isset($_POST['current_status']) ? $_POST['current_status'] : '';
                                    foreach($statuses as $s) {
                                        $sel = ($s == $selected_s) ? 'selected' : '';
                                        echo "<option value='$s' $sel>$s</option>";
                                    }
                                ?>
                            </select>
                            <i data-lucide="user-check" size="18"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Highest Educational Qualification *</label>
                        <div class="input-wrapper">
                            <select name="highest_qualification" required>
                                <option value="" disabled selected>Select Qualification</option>
                                <?php 
                                    $quals = [
                                        "Senior Secondary", 
                                        "Diploma", 
                                        "Undergraduate Degree", 
                                        "Postgraduate Degree", 
                                        "Doctorate", 
                                        "Professional Qualification", 
                                        "Other"
                                    ];
                                    $selected_q = isset($_POST['highest_qualification']) ? $_POST['highest_qualification'] : '';
                                    foreach($quals as $q) {
                                        $sel = ($q == $selected_q) ? 'selected' : '';
                                        echo "<option value='$q' $sel>$q</option>";
                                    }
                                ?>
                            </select>
                            <i data-lucide="graduation-cap" size="18"></i>
                        </div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>College, University or Organisation Name *</label>
                        <div class="input-wrapper">
                            <input type="text" name="organization_name" required placeholder="e.g. ABC University or Tech Corp"
                                   value="<?php echo isset($_POST['organization_name']) ? htmlspecialchars($_POST['organization_name']) : ''; ?>">
                            <i data-lucide="building-2" size="18"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Course, Designation or Current Role *</label>
                        <div class="input-wrapper">
                            <input type="text" name="current_role" required placeholder="e.g. B.Tech CS or Security Analyst"
                                   value="<?php echo isset($_POST['current_role']) ? htmlspecialchars($_POST['current_role']) : ''; ?>">
                            <i data-lucide="briefcase" size="18"></i>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Links & Attachments -->
                <div class="section-title"><i data-lucide="link"></i> 3. Links & Attachments</div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>LinkedIn Profile <span class="inline-optional">(Optional)</span></label>
                        <div class="input-wrapper">
                            <input type="url" name="linkedin_url" placeholder="https://linkedin.com/in/..."
                                   value="<?php echo isset($_POST['linkedin_url']) ? htmlspecialchars($_POST['linkedin_url']) : ''; ?>">
                            <i data-lucide="linkedin" size="18"></i>
                        </div>
                    </div>
                </div>

                <div class="upload-grid">
                    <div class="form-group">
                        <label>Upload CV <span class="inline-optional">(Optional - PDF/DOC up to 5MB)</span></label>
                        <label class="upload-area" for="cv-input" id="drop-area-cv">
                            <i data-lucide="file-up" size="32"></i>
                            <div style="font-weight: 600; color: #1e293b; font-size:14px;">Browse or drag CV here</div>
                            <input type="file" name="cv" id="cv-input" style="display:none;" accept=".pdf,.doc,.docx">
                            <div id="cv-file-name" class="file-name"></div>
                        </label>
                    </div>
                </div>

                <!-- Section 4: Declaration -->
                <div class="section-title"><i data-lucide="check-square"></i> 4. Declaration</div>
                
                <div class="confirmation-card">
                    <label class="radio-label">
                        <input type="checkbox" name="dec1" required <?php if(isset($_POST['dec1'])) echo 'checked'; ?>>
                        <span>I confirm that the information provided is accurate. *</span>
                    </label>
                    <label class="radio-label">
                        <input type="checkbox" name="dec2" required <?php if(isset($_POST['dec2'])) echo 'checked'; ?>>
                        <span>I understand that participation is strictly individual and team participation is not permitted. *</span>
                    </label>
                    <label class="radio-label">
                        <input type="checkbox" name="dec3" required <?php if(isset($_POST['dec3'])) echo 'checked'; ?>>
                        <span>I agree to follow the rules, timelines and code of conduct of the FCRF Hackathon 2026. *</span>
                    </label>
                    <label class="radio-label">
                        <input type="checkbox" name="dec4" required <?php if(isset($_POST['dec4'])) echo 'checked'; ?>>
                        <span>I consent to receiving hackathon instructions, problem statements and related communications from FCRF. *</span>
                    </label>
                </div>

                <!-- Recaptcha -->
                <div class="captcha-wrap">
                    <div class="g-recaptcha" data-sitekey="<?php echo $recaptcha_site_key; ?>"></div>
                </div>

                <button type="submit" class="submit-btn">
                    Complete Registration <i data-lucide="send" size="18"></i>
                </button>

                <div class="footer-note">
                    After submitting the form, please check your registered email address. Detailed communications will begin from <strong>7 July 2026</strong>.
                </div>

            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- SCRIPTS                                    -->
    <!-- ========================================== -->
    <script>
        // Initialize Icons
        lucide.createIcons();

        // --- File Upload Visual Feedback & Client-Side Validation ---
        function setupFileUpload(inputId, fileNameId, dropAreaId) {
            const fileInput = document.getElementById(inputId);
            const fileNameDiv = document.getElementById(fileNameId);
            const dropArea = document.getElementById(dropAreaId);

            const maxSize = 5 * 1024 * 1024; // 5MB limit
            const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            const allowedExts = ['pdf', 'doc', 'docx'];

            function handleFileValidation(file) {
                dropArea.classList.remove('has-error');
                if (!file) return;

                const ext = file.name.split('.').pop().toLowerCase();

                if (!allowedExts.includes(ext) && !allowedTypes.includes(file.type)) {
                    alert("Invalid file type. Please upload a PDF or Word document only.");
                    fileInput.value = ''; 
                    fileNameDiv.style.display = 'none';
                    dropArea.classList.add('has-error'); 
                    return;
                }

                if (file.size > maxSize) {
                    alert(`File is too large. Maximum size allowed is ${maxSize / (1024 * 1024)}MB.`);
                    fileInput.value = ''; 
                    fileNameDiv.style.display = 'none';
                    dropArea.classList.add('has-error'); 
                    return;
                }

                fileNameDiv.textContent = '✓ Selected: ' + file.name;
                fileNameDiv.style.display = 'block'; 
                dropArea.style.borderColor = "var(--secondary)";
            }

            fileInput.addEventListener('change', function(e) {
                if (e.target.files.length > 0) { 
                    handleFileValidation(e.target.files[0]); 
                } else { 
                    fileNameDiv.style.display = 'none'; 
                    dropArea.style.borderColor = "var(--border-color)"; 
                    dropArea.classList.remove('has-error'); 
                }
            });

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, e => { e.preventDefault(); e.stopPropagation(); }, false);
            });
            ['dragenter', 'dragover'].forEach(eventName => dropArea.addEventListener(eventName, () => dropArea.classList.add('dragover'), false));
            ['dragleave', 'drop'].forEach(eventName => dropArea.addEventListener(eventName, () => dropArea.classList.remove('dragover'), false));
            
            dropArea.addEventListener('drop', (e) => {
                if(e.dataTransfer.files.length > 0) {
                    fileInput.files = e.dataTransfer.files;
                    handleFileValidation(e.dataTransfer.files[0]);
                }
            });
        }

        // Initialize for Optional CV
        setupFileUpload('cv-input', 'cv-file-name', 'drop-area-cv');


        // --- Redirection Logic with Countdown ---
        function forceRedirect() {
            window.location.href = "https://summit.futurecrime.org";
        }

        <?php if ($showSuccessModal): ?>
        let timeLeft = 4;
        const timerElement = document.getElementById('timer');
        const countdown = setInterval(function() {
            timeLeft--;
            if (timerElement) timerElement.textContent = timeLeft;
            if (timeLeft <= 0) {
                clearInterval(countdown);
                forceRedirect();
            }
        }, 1000);
        <?php endif; ?>

        // --- SMART FORM VALIDATION ENGINE ---
        const form = document.getElementById('regForm');
        
        form.addEventListener('submit', function(event) {
            let isValid = true;
            let firstInvalid = null;

            // Remove existing error classes before re-checking
            document.querySelectorAll('.has-error').forEach(el => el.classList.remove('has-error'));

            // Check all inputs, selects, and textareas inside the form
            const elements = form.querySelectorAll('input, select, textarea');
            elements.forEach(el => {
                if (!el.checkValidity()) {
                    isValid = false;
                    if (!firstInvalid) firstInvalid = el;
                    
                    // Add error class to the specific parent wrapper
                    if (el.type === 'file') {
                        el.closest('.upload-area').classList.add('has-error');
                    } else if (el.type === 'checkbox') {
                        el.closest('.confirmation-card').classList.add('has-error');
                    } else {
                        el.closest('.form-group').classList.add('has-error');
                    }
                }
            });

            if (!isValid) {
                event.preventDefault(); // Stop form submission
                
                // Smooth scroll to the first missed field
                if (firstInvalid) {
                    const container = firstInvalid.closest('.form-group') || firstInvalid.closest('.upload-area') || firstInvalid.closest('.confirmation-card');
                    if(container) {
                        container.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    firstInvalid.focus({ preventScroll: true });
                }
            }
        });

        // Real-time error clearing when user starts typing/selecting
        form.addEventListener('input', function(e) {
            if (e.target.checkValidity()) {
                if (e.target.type === 'file') {
                    e.target.closest('.upload-area')?.classList.remove('has-error');
                } else if (e.target.type === 'checkbox') {
                    // Check if ALL checkboxes in the group are valid
                    const card = e.target.closest('.confirmation-card');
                    if (card) {
                        const allChecks = card.querySelectorAll('input[type="checkbox"]');
                        let allValid = true;
                        allChecks.forEach(chk => { if (!chk.checkValidity()) allValid = false; });
                        if (allValid) card.classList.remove('has-error');
                    }
                } else {
                    e.target.closest('.form-group')?.classList.remove('has-error');
                }
            }
        });
    </script>
</body>
</html>
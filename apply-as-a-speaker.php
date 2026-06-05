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
$table_name = "fcrf_professionals"; 

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
        $first_name    = sanitize_input($_POST['firstName'] ?? '');
        $last_name     = sanitize_input($_POST['lastName'] ?? '');
        $phone         = sanitize_input($_POST['phone'] ?? '');
        $email         = sanitize_input($_POST['email'] ?? '');
        $qualification = sanitize_input($_POST['qualification'] ?? '');
        $experience    = sanitize_input($_POST['experience'] ?? '');
        $designation   = sanitize_input($_POST['designation'] ?? '');
        $organization  = sanitize_input($_POST['organization'] ?? '');
        $websiteUrl    = sanitize_input($_POST['websiteUrl'] ?? '');
        $district      = sanitize_input($_POST['district'] ?? '');
        $state         = sanitize_input($_POST['state'] ?? '');
        $country       = sanitize_input($_POST['country'] ?? '');
        $social        = sanitize_input($_POST['social'] ?? '');
        $brief         = sanitize_input($_POST['brief'] ?? '');

        // --- STRICT VALIDATION LOGIC ---
        if (empty($first_name) || empty($last_name) || empty($phone) || empty($email) || empty($qualification) || empty($experience) || empty($designation) || empty($organization) || empty($district) || empty($state) || empty($country) || empty($brief)) {
            throw new Exception("All fields marked with * are mandatory.");
        }

        if (!preg_match("/^[a-zA-Z\s\.]+$/", $first_name) || !preg_match("/^[a-zA-Z\s\.]+$/", $last_name)) {
            throw new Exception("Name cannot contain numbers or special characters.");
        }

        if (!ctype_digit($phone) || strlen($phone) < 10) {
            throw new Exception("Please enter a valid phone number (digits only).");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        if (!preg_match("/^[0-9]+$/", $experience)) {
            throw new Exception("Years of experience must be a valid number.");
        }

        if (!empty($websiteUrl) && !filter_var($websiteUrl, FILTER_VALIDATE_URL)) {
            throw new Exception("Please provide a valid Website URL.");
        }

        if (!empty($social) && !filter_var($social, FILTER_VALIDATE_URL)) {
            throw new Exception("Please provide a valid Social/LinkedIn URL.");
        }

        // 4. File Upload Logic
        $target_dir = "uploads/professionals/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0755, true); }
        
        // Handle Photo (Secure MIME validation)
        $photoPath = "";
        if (!empty($_FILES["photo"]["name"])) {
            $photoName = time() . "_photo_" . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES["photo"]["name"]));
            $photoPath = $target_dir . $photoName;
            
            $allowed_photo_mimes = ['image/jpeg', 'image/png', 'image/jpg'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES["photo"]["tmp_name"]);
            finfo_close($finfo);
            
            $photoType = strtolower(pathinfo($photoPath, PATHINFO_EXTENSION));
            if (!in_array($photoType, ['jpg', 'jpeg', 'png']) || !in_array($mime, $allowed_photo_mimes)) { 
                throw new Exception("Invalid Photo. Only JPG or PNG formats are allowed."); 
            }
            if ($_FILES["photo"]["size"] > 5000000) { throw new Exception("Photo size must be less than 5MB."); }
            if (!move_uploaded_file($_FILES["photo"]["tmp_name"], $photoPath)) { throw new Exception("Failed to upload photo."); }
        } else {
            throw new Exception("Profile photo is required.");
        }

        // Handle CV (Secure MIME validation for PDF/DOC)
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
        } else {
            throw new Exception("CV document is required.");
        }

        // 5. Insert into Database Securely (Prepared Statements prevent SQLi)
        $sql = "INSERT INTO $table_name (first_name, last_name, phone, email, qualification, experience, designation, organization, website_url, district, state, country, social_url, photo_path, cv_path, brief) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) { throw new Exception("Database Error: " . $conn->error); }

        $stmt->bind_param("ssssssssssssssss", $first_name, $last_name, $phone, $email, $qualification, $experience, $designation, $organization, $websiteUrl, $district, $state, $country, $social, $photoPath, $cvPath, $brief);
        
        if ($stmt->execute()) {
            $showSuccessModal = true;
            $successName = $first_name;
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
    
    <title>Professional Registration Portal | FCRF Academy</title>
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
        .radio-label { display: flex; align-items: center; gap: 12px; font-size: 0.95rem; color: #475569; cursor: pointer; padding: 8px; border-radius: 8px; transition: background 0.2s; }
        .radio-label:hover { background: white; }
        .radio-label input[type="radio"], .radio-label input[type="checkbox"] { accent-color: var(--primary); width: 1.2rem; height: 1.2rem; cursor: pointer; }

        /* File Upload */
        .upload-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .upload-area { border: 2px dashed var(--border-color); border-radius: 16px; padding: 2rem; text-align: center; background: #f8fafc; cursor: pointer; transition: all 0.3s; position: relative; display: block; height: 100%; }
        .upload-area:hover, .upload-area.dragover { border-color: var(--primary); background: #f0f9ff; }
        .upload-area i { color: var(--text-muted); margin-bottom: 10px; transition: color 0.3s; }
        .upload-area:hover i { color: var(--primary); }
        .upload-area.has-error { border-color: var(--error); background: #fef2f2; }
        .file-name { margin-top: 10px; font-size: 0.9rem; color: var(--secondary); font-weight: 600; display: none; word-break: break-all; }

        /* Error & Buttons */
        .error-box { background: #fee2e2; border-left: 4px solid var(--error); color: #b91c1c; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; font-weight: 500; display: flex; align-items: center; gap: 8px; }

        /* --- NEW VALIDATION STYLES --- */
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
        .confirmation-card.has-error {
            border-color: var(--error);
            background-color: #fef2f2;
        }
        .confirmation-card.has-error label {
            color: var(--error);
        }

        .submit-btn {
            width: 100%; background: var(--gradient); color: white; border: none; padding: 1.2rem; border-radius: 12px;
            font-size: 1.1rem; font-weight: 700; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; margin-top: 2rem; display: flex; justify-content: center; align-items: center; gap: 10px;
        }
        .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(14, 165, 233, 0.3); }

        .captcha-wrap { display: flex; justify-content: center; margin-top: 2rem; }

        /* --- IMMERSIVE SUCCESS MODAL --- */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.85); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal-content { background: white; padding: 3rem; border-radius: 24px; text-align: center; max-width: 450px; width: 90%; box-shadow: 0 0 0 1px rgba(255,255,255,0.1), 0 25px 50px -12px rgba(0,0,0,0.5); animation: scaleIn 0.4s cubic-bezier(0.16, 1, 0.3, 1); position: relative; }
        .icon-success { width: 80px; height: 80px; background: #ecfdf5; color: var(--secondary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; position: relative; }
        .icon-success::before { content: ''; position: absolute; inset: -10px; border-radius: 50%; border: 2px solid var(--secondary); animation: pulse 2s infinite; opacity: 0.5; }
        .countdown-text { font-size: 0.9rem; color: #94a3b8; margin-top: 1rem; font-weight: 500; }
        .countdown-text span { color: var(--primary); font-weight: 700; }

        @keyframes scaleIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
        @keyframes pulse { 0% { transform: scale(1); opacity: 0.8; } 100% { transform: scale(1.3); opacity: 0; } }

        /* Form Responsive Constraints */
        @media (max-width: 768px) {
            .banner { padding: 3rem 1.5rem; }
            .banner-logo { max-width: 200px; margin-bottom: 1rem; }
            .banner h1 { font-size: 2rem; }
            .form-body { padding: 2rem 1.5rem; }
            .grid-2, .grid-3, .upload-grid { grid-template-columns: 1fr; gap: 0; }
            .upload-grid { gap: 1.5rem; }
        }
    </style>
</head>
<body>

    <!-- SUCCESS MODAL (With Countdown & Redirection) -->
    <div class="modal-overlay <?php echo $showSuccessModal ? 'active' : ''; ?>" id="success-modal">
        <div class="modal-content">
            <div class="icon-success"><i data-lucide="shield-check" size="40"></i></div>
            <h2 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 10px; color: var(--text-main);">Registration Successful!</h2>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem; line-height: 1.5;">
                Hello <strong style="color:var(--text-main);"><?php echo htmlspecialchars($successName); ?></strong>, your profile has been successfully saved.
            </p>
            <button onclick="forceRedirect()" class="submit-btn" style="margin-top:0; padding:1rem; font-size:1rem;">Continue to Summit</button>
            <div class="countdown-text">Auto-redirecting in <span id="timer">4</span> seconds...</div>
        </div>
    </div>

    <!-- THE MAIN FORM CONTAINER -->
    <div class="form-container">
        
        <div class="banner">
            <div class="banner-content">
                <h1>Take the Stage at<span> FutureCrime Summit 2026</span></h1>
                <p>Join a national platform where experts speak on cybercrime, AI threats, digital forensics, fraud, cyber law, data privacy, and the future of technology-enabled crime.</p>
            </div>
        </div>

        <div class="form-body">
            
            <?php if (!empty($message) && $messageType == "error"): ?>
                <div class="error-box">
                    <i data-lucide="alert-triangle" size="20"></i> 
                    <span><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data" id="regForm" novalidate>
                
                <!-- Section 1: Identity -->
                <div class="section-title"><i data-lucide="user"></i> 1. Identity</div>
                
                <div class="grid-2">
                    <div class="form-group">
                        <label>First Name *</label>
                        <div class="input-wrapper">
                            <input type="text" name="firstName" required placeholder="John"
                                   pattern="[a-zA-Z\s\.]+" oninput="this.value = this.value.replace(/[^a-zA-Z\s\.]/g, '')"
                                   value="<?php echo isset($_POST['firstName']) ? htmlspecialchars($_POST['firstName']) : ''; ?>">
                            <i data-lucide="user-circle" size="18"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <div class="input-wrapper">
                            <input type="text" name="lastName" required placeholder="Doe"
                                   pattern="[a-zA-Z\s\.]+" oninput="this.value = this.value.replace(/[^a-zA-Z\s\.]/g, '')"
                                   value="<?php echo isset($_POST['lastName']) ? htmlspecialchars($_POST['lastName']) : ''; ?>">
                            <i data-lucide="user-circle" size="18"></i>
                        </div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Phone / Mobile *</label>
                        <div class="input-wrapper">
                            <input type="tel" name="phone" required placeholder="Contact Number"
                                   pattern="[0-9]+" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                            <i data-lucide="phone" size="18"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email Address *</label>
                        <div class="input-wrapper">
                            <input type="email" name="email" required placeholder="john@example.com"
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            <i data-lucide="mail" size="18"></i>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Professional Details -->
                <div class="section-title"><i data-lucide="briefcase"></i> 2. Professional Details</div>
                
                <div class="grid-2">
                    <div class="form-group">
                        <label>Educational Qualification *</label>
                        <div class="input-wrapper">
                            <input type="text" name="qualification" required placeholder="e.g. MCA Student"
                                   value="<?php echo isset($_POST['qualification']) ? htmlspecialchars($_POST['qualification']) : ''; ?>">
                            <i data-lucide="graduation-cap" size="18"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Years of Experience *</label>
                        <div class="input-wrapper">
                            <input type="number" name="experience" required min="0" placeholder="e.g. 2"
                                   pattern="[0-9]+" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                   value="<?php echo isset($_POST['experience']) ? htmlspecialchars($_POST['experience']) : ''; ?>">
                            <i data-lucide="clock" size="18"></i>
                        </div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Designation *</label>
                        <div class="input-wrapper">
                            <input type="text" name="designation" required placeholder="e.g. Lead Developer"
                                   value="<?php echo isset($_POST['designation']) ? htmlspecialchars($_POST['designation']) : ''; ?>">
                            <i data-lucide="briefcase" size="18"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Name of Organization *</label>
                        <div class="input-wrapper">
                            <input type="text" name="organization" required placeholder="e.g. Tech Solutions Inc."
                                   value="<?php echo isset($_POST['organization']) ? htmlspecialchars($_POST['organization']) : ''; ?>">
                            <i data-lucide="building-2" size="18"></i>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Presence & Location -->
                <div class="section-title"><i data-lucide="globe"></i> 3. Presence & Location</div>

                <div class="form-group">
                    <label>Website URL <span class="inline-optional">(Optional)</span></label>
                    <div class="input-wrapper">
                        <input type="url" name="websiteUrl" placeholder="https://yourproduct.com"
                               value="<?php echo isset($_POST['websiteUrl']) ? htmlspecialchars($_POST['websiteUrl']) : ''; ?>">
                        <i data-lucide="globe" size="18"></i>
                    </div>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label>District *</label>
                        <div class="input-wrapper">
                            <input type="text" name="district" required placeholder="e.g. Ulhasnagar"
                                   value="<?php echo isset($_POST['district']) ? htmlspecialchars($_POST['district']) : ''; ?>">
                            <i data-lucide="map-pin" size="18"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>State *</label>
                        <div class="input-wrapper">
                            <input type="text" name="state" required placeholder="e.g. Maharashtra"
                                   value="<?php echo isset($_POST['state']) ? htmlspecialchars($_POST['state']) : ''; ?>">
                            <i data-lucide="map" size="18"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Country *</label>
                        <div class="input-wrapper">
                            <select name="country" required>
                                <option value="" disabled selected>Select Country</option>
                                <?php 
                                    $countries = ["India", "USA", "UK", "Canada", "Australia", "Other"];
                                    $selected_c = isset($_POST['country']) ? $_POST['country'] : '';
                                    foreach($countries as $c) {
                                        $sel = ($c == $selected_c) ? 'selected' : '';
                                        echo "<option value='$c' $sel>$c</option>";
                                    }
                                ?>
                            </select>
                            <i data-lucide="globe" size="18"></i>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>LinkedIn / Social Media <span class="inline-optional">(if any)</span></label>
                    <div class="input-wrapper">
                        <input type="url" name="social" placeholder="LinkedIn or Portfolio URL"
                               value="<?php echo isset($_POST['social']) ? htmlspecialchars($_POST['social']) : ''; ?>">
                        <i data-lucide="link" size="18"></i>
                    </div>
                </div>

                <!-- Section 4: Attachments -->
                <div class="section-title"><i data-lucide="paperclip"></i> 4. Attachments</div>

                <div class="upload-grid">
                    <div class="form-group">
                        <label>Upload your latest photo * <span class="inline-optional">(JPG/PNG up to 5MB)</span></label>
                        <label class="upload-area" for="photo-input" id="drop-area-photo">
                            <i data-lucide="camera" size="32"></i>
                            <div style="font-weight: 600; color: #1e293b; font-size:14px;">Browse or drag Photo here</div>
                            <input type="file" name="photo" id="photo-input" style="display:none;" accept=".jpg,.jpeg,.png" required>
                            <div id="photo-file-name" class="file-name"></div>
                        </label>
                    </div>

                    <div class="form-group">
                        <label>Upload your CV / Bio * <span class="inline-optional">(PDF/DOC up to 5MB)</span></label>
                        <label class="upload-area" for="cv-input" id="drop-area-cv">
                            <i data-lucide="file-up" size="32"></i>
                            <div style="font-weight: 600; color: #1e293b; font-size:14px;">Browse or drag CV here</div>
                            <input type="file" name="cv" id="cv-input" style="display:none;" accept=".pdf,.doc,.docx" required>
                            <div id="cv-file-name" class="file-name"></div>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Area of Expertise / Brief of Work Experience *</label>
                    <textarea name="brief" required placeholder="Tell us about your professional background..."><?php echo isset($_POST['brief']) ? htmlspecialchars($_POST['brief']) : ''; ?></textarea>
                </div>

                <!-- Section 5: Declaration -->
                <div class="section-title"><i data-lucide="check-square"></i> 5. Declaration</div>
                
                <div class="form-group">
                    <label class="radio-label" style="align-items: flex-start; background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color);">
                        <input type="checkbox" name="confirm" required <?php if(isset($_POST['confirm'])) echo 'checked'; ?>>
                        <span style="font-size: 0.9rem; line-height: 1.5;">I confirm that the information provided is accurate and true to my knowledge. *</span>
                    </label>
                </div>

                <!-- Recaptcha -->
                <div class="captcha-wrap">
                    <div class="g-recaptcha" data-sitekey="<?php echo $recaptcha_site_key; ?>"></div>
                </div>

                <button type="submit" class="submit-btn">
                    Securely Submit Registration <i data-lucide="lock" size="18"></i>
                </button>

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
        function setupFileUpload(inputId, fileNameId, dropAreaId, isPhoto) {
            const fileInput = document.getElementById(inputId);
            const fileNameDiv = document.getElementById(fileNameId);
            const dropArea = document.getElementById(dropAreaId);

            const maxSize = 5 * 1024 * 1024; // 5MB limit
            const allowedTypes = isPhoto 
                ? ['image/jpeg', 'image/png'] 
                : ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            const allowedExts = isPhoto ? ['jpg', 'jpeg', 'png'] : ['pdf', 'doc', 'docx'];

            function handleFileValidation(file) {
                dropArea.classList.remove('has-error');
                if (!file) return;

                const ext = file.name.split('.').pop().toLowerCase();

                if (!allowedExts.includes(ext) && !allowedTypes.includes(file.type)) {
                    alert(isPhoto ? "Invalid file type. Please upload a JPG or PNG." : "Invalid file type. Please upload a PDF or Word document.");
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

        setupFileUpload('photo-input', 'photo-file-name', 'drop-area-photo', true);
        setupFileUpload('cv-input', 'cv-file-name', 'drop-area-cv', false);


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
                    e.target.closest('.confirmation-card')?.classList.remove('has-error');
                } else {
                    e.target.closest('.form-group')?.classList.remove('has-error');
                }
            }
        });
    </script>
</body>
</html>
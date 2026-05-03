<?php
error_reporting(0);
ini_set('display_errors', 0);

$message = "";
$messageType = "";
$showSuccessModal = false;
$successName = "";

// ================= CONFIGURATION ================= //
$db_host = "localhost";
$db_user = "u318207836_waitlist"; 
$db_pass = "FCRFdev820";          
$db_name = "u318207836_waitlist"; 
$table_name = "fcrf_ethical_hackers"; 

// 🔴 RECAPTCHA KEYS (Updated)
$recaptcha_site_key = "6LfkXYwsAAAAAO8Vwrhg7KdnocQzL-yQwl8zgTt4";
$recaptcha_secret = "6LfkXYwsAAAAAOg_C4CYVgNlQOyG9X1RU4Pl576h"; 

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

        // 3. Process & Validate Inputs
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $organization = trim($_POST['organization']);
        $designation = trim($_POST['designation']);
        $city_country = trim($_POST['city_country']);
        $experience = trim($_POST['experience']);
        $linkedin_url = trim($_POST['linkedin_url']);

        // Handle Checkboxes (Teaching Areas)
        $teaching_arr = isset($_POST['teaching_areas']) ? $_POST['teaching_areas'] : [];
        if (empty($teaching_arr)) {
            throw new Exception("Please select at least one teaching area.");
        }
        $teaching_areas = implode(" | ", $teaching_arr);

        // --- STRICT VALIDATION LOGIC ---
        if (empty($full_name) || empty($email) || empty($phone) || empty($organization) || empty($designation) || empty($city_country) || empty($experience)) {
            throw new Exception("All fields marked with * are mandatory.");
        }

        if (!preg_match("/^[a-zA-Z\s\.]+$/", $full_name)) {
            throw new Exception("Name cannot contain numbers or special characters.");
        }

        if (!ctype_digit($phone) || strlen($phone) < 10) {
            throw new Exception("Please enter a valid phone number (digits only).");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        // 4. File Upload Logic (Advanced Security for CV)
        $target_dir = "uploads/hackers_cv/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0755, true); }
        
        $cvPath = "";
        if (!empty($_FILES["cv"]["name"])) {
            $cvName = time() . "_cv_" . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES["cv"]["name"]));
            $cvPath = $target_dir . $cvName;
            
            // Basic extension check
            $cvType = strtolower(pathinfo($cvPath, PATHINFO_EXTENSION));
            if (!in_array($cvType, ['pdf', 'doc', 'docx'])) { 
                throw new Exception("CV must be a PDF, DOC, or DOCX file."); 
            }
            
            // Advanced MIME Type check to prevent malicious file spoofing
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES["cv"]["tmp_name"]);
            finfo_close($finfo);
            $allowed_mimes = [
                'application/pdf', 
                'application/msword', 
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];
            if (!in_array($mime, $allowed_mimes)) { 
                throw new Exception("Invalid file content. Upload genuinely formatted PDF or Word documents only."); 
            }

            if ($_FILES["cv"]["size"] > 5000000) { throw new Exception("CV file size must be less than 5MB."); }
            if (!move_uploaded_file($_FILES["cv"]["tmp_name"], $cvPath)) { throw new Exception("Failed to upload CV."); }
        }

        // 5. Insert into Database securely using Prepared Statements
        $sql = "INSERT INTO $table_name (full_name, email, phone, organization, designation, city_country, experience, teaching_areas, linkedin_url, cv_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) { throw new Exception("Database Error: " . $conn->error); }

        $stmt->bind_param("ssssssssss", $full_name, $email, $phone, $organization, $designation, $city_country, $experience, $teaching_areas, $linkedin_url, $cvPath);
        
        if ($stmt->execute()) {
            $showSuccessModal = true;
            $successName = $full_name;
            $_POST = array(); // Clear form values on success
        } else {
            throw new Exception("Failed to submit application.");
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
<meta name="author" content="FCRF Academy">

<!-- ================== SEO Meta ================== -->
<title>Ethical hacking professional instructor enrollment | FCRF Academy</title>

<meta name="description" content="Join FCRF Academy as an Ethical Hacking Instructor. We are inviting experienced ethical hackers, VAPT professionals, and cybersecurity experts with strong practical knowledge and teaching ability to lead upcoming training programs." />

<meta name="robots" content="index, follow">
<meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">

<link rel="canonical" href="https://fcrf.academy/ethical-hacking-instructor-enrollment" />

<link rel="icon" href="/favicon.ico" type="image/x-icon">

<!-- ================== Open Graph (LinkedIn, WhatsApp, Facebook) ================== -->
<meta property="og:type" content="website">
<meta property="og:title" content="Ethical hacking professional instructor enrollment | FCRF Academy">
<meta property="og:description" content="FCRF Academy is inviting ethical hacking experts, VAPT professionals, and cybersecurity practitioners to join as instructors and contribute to advanced training initiatives." />
<meta property="og:url" content="https://fcrf.academy/ethical-hacking-instructor-enrollment">
<meta property="og:image" content="https://fcrf.academy/assets/img/lms/CEH-course.jpeg">
<meta property="og:site_name" content="FCRF Academy">

<!-- ================== Twitter ================== -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Ethical hacking professional instructor enrollment | FCRF Academy">
<meta name="twitter:description" content="Apply to become an Ethical Hacking Instructor at FCRF Academy. Looking for experienced cybersecurity and VAPT professionals with strong practical and teaching expertise.">
<meta name="twitter:image" content="https://fcrf.academy/assets/img/lms/CEH-course.jpeg">

<link rel="shortcut icon" href="assets/img/logo/fcrf-logo.webp">    
<script src="https://unpkg.com/lucide@latest"></script>    
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<!-- Consolidated Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;700&family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@1,500;1,600&display=swap" rel="stylesheet">
    
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
            flex-direction: column; /* Allows top-to-bottom stacking */
            align-items: center;
            padding: 0 1rem 4rem 1rem;
        }

        /* ========================================== */
        /* ISOLATED PROGRAM MODULE CSS                */
        /* ========================================== */
        .isolated-program-module {
            --bg-card-dark: #121214;
            --text-white: #ffffff;
            --gray-200: #e5e7eb;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-800: #1f2937;
            --custom-gradient: linear-gradient(93deg, #5135FF 10.65%, #FF5455 89.35%);
            
            font-family: 'Inter', sans-serif;
            color: var(--text-white);
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
        }

        .isolated-program-module .font-serif-italic {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-weight: 500;
        }

        .isolated-program-module .nav-wrapper {
            width: 100%;
            padding: 2rem 1.5rem 0;
            display: flex;
            justify-content: center;
        }

        .isolated-program-module .navbar {
            width: 100%;
            max-width: 950px;
            background-color: transparent;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 2.5rem;
        }

        .isolated-program-module .nav-links { display: flex; gap: 1.5rem; }

        .isolated-program-module .nav-link {
            color: var(--text-white);
            font-weight: 500;
            font-size: 0.95rem;
            padding: 0.5rem 1.5rem;
            border: none;
            border-radius: 9999px;
            background: var(--custom-gradient);
            transition: all 0.3s ease;
            cursor: pointer;
            opacity: 0.5; /* Inactive state */
        }

        .isolated-program-module .nav-link.active {
            opacity: 1;
            box-shadow: 0 4px 15px rgba(255, 84, 85, 0.4);
            transform: translateY(-1px);
        }

        .isolated-program-module .nav-link:hover { opacity: 0.9; }

        /* Carousel CSS */
        .isolated-program-module .stalwarts-section {
            width: 100%;
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .isolated-program-module .stalwarts-container {
            width: 100%;
            max-width: 1400px;
            padding: 3rem 1.5rem;
            overflow: hidden;
        }

        .isolated-program-module .jury-headline {
            font-size: 3rem;
            font-weight: 400;
            text-align: center;
            margin-bottom: 3rem;
            letter-spacing: -0.02em;
        }

        .isolated-program-module .jury-headline strong {
            background: var(--custom-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }

        .isolated-program-module .carousel-wrapper { position: relative; width: 100%; overflow: hidden; }
        .isolated-program-module .carousel-container {
            display: flex; gap: 1.5rem; overflow-x: auto; width: 100%; padding: 1rem 0; cursor: grab; user-select: none;
            scrollbar-width: none;
        }
        .isolated-program-module .carousel-container::-webkit-scrollbar { display: none; }
        .isolated-program-module .carousel-container:active { cursor: grabbing; }

        .isolated-program-module .card {
            flex-shrink: 0; width: 280px; border-radius: 1rem; overflow: hidden; display: flex; flex-direction: column;
            background-color: var(--bg-card-dark); border: 1px solid rgba(31, 41, 55, 0.5); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        }
        .isolated-program-module .card-img-container { height: 12rem; width: 100%; }
        .isolated-program-module .card-img-container img { width: 100%; height: 100%; object-fit: cover; pointer-events: none; }
        .isolated-program-module .card-info { padding: 1.25rem; display: flex; flex-direction: column; flex-grow: 1; }
        .isolated-program-module .card-name { font-weight: 700; color: var(--text-white); font-size: 1.125rem; }
        .isolated-program-module .card-role { font-size: 12px; color: var(--gray-400); margin-top: 4px; }
        .isolated-program-module .card-company { margin-top: 1rem; margin-bottom: 0.5rem; font-weight: 900; color: var(--gray-200); font-size: 14px; text-transform: uppercase; letter-spacing: 0.1em; }
        .isolated-program-module .card-footer { margin-top: auto; padding-top: 1rem; border-top: 1px solid var(--gray-800); }
        .isolated-program-module .card-topic { font-size: 12px; color: var(--gray-500); font-style: italic; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

        /* ========================================== */
        /* FORMS CSS                                  */
        /* ========================================== */
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

        .banner h1 {
            color: white; font-size: 2.5rem; font-weight: 800; line-height: 1.2;
            margin-bottom: 1rem; font-family: 'JetBrains Mono', monospace;
        }
        .banner h1 span { color: var(--primary); }
        .banner-logo { max-width: 280px; height: auto; margin-bottom: 1.5rem; display: block; }
        .banner p { color: #94a3b8; font-size: 1.1rem; line-height: 1.6; max-width: 800px; }

        /* Form Body */
        .form-body { padding: 3rem; }
        .section-title { display: flex; align-items: center; gap: 10px; font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin: 2.5rem 0 1.5rem; padding-bottom: 0.75rem; border-bottom: 2px solid #f1f5f9; }
        .section-title i { color: var(--primary); }
        .section-title:first-child { margin-top: 0; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; font-weight: 600; font-size: 0.95rem; margin-bottom: 0.5rem; color: #334155; }
        .optional-tag { font-weight: 400; color: #94a3b8; font-size: 0.8rem; }

        /* Input Wrapper for Icons */
        .input-wrapper { position: relative; display: flex; align-items: center; }
        .input-wrapper i { position: absolute; left: 14px; color: #94a3b8; transition: color 0.3s ease; pointer-events: none; }
        input[type="text"], input[type="email"], input[type="tel"], input[type="number"], input[type="url"] {
            width: 100%; padding: 14px 16px 14px 44px; border-radius: 12px; border: 1px solid var(--border-color);
            background: #f8fafc; font-family: inherit; font-size: 1rem; color: var(--text-main); transition: all 0.3s ease;
        }
        .input-wrapper input:focus { outline: none; border-color: var(--primary); background: white; box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1); }
        .input-wrapper input:focus + i { color: var(--primary); }

        /* Custom Checkbox Grid */
        .checkbox-grid { display: grid; grid-template-columns: 1fr; gap: 12px; background: #f8fafc; padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border-color); }
        @media (min-width: 768px) { .checkbox-grid { grid-template-columns: 1fr 1fr; } }
        .checkbox-label { display: flex; align-items: flex-start; gap: 12px; font-size: 0.95rem; line-height: 1.4; color: #475569; cursor: pointer; padding: 12px; border-radius: 10px; transition: background 0.2s; }
        .checkbox-label:hover { background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .checkbox-label input { margin-top: 3px; accent-color: var(--primary); width: 1.2rem; height: 1.2rem; flex-shrink: 0; cursor: pointer; }

        /* File Upload */
        .upload-area { border: 2px dashed var(--border-color); border-radius: 16px; padding: 2rem; text-align: center; background: #f8fafc; cursor: pointer; transition: all 0.3s; position: relative; }
        .upload-area:hover, .upload-area.dragover { border-color: var(--primary); background: #f0f9ff; }
        .upload-area i { color: var(--text-muted); margin-bottom: 10px; transition: color 0.3s; }
        .upload-area:hover i { color: var(--primary); }
        .file-name { margin-top: 10px; font-size: 0.9rem; color: var(--secondary); font-weight: 600; display: none; }

        /* Error & Buttons */
        .error-box { background: #fee2e2; border-left: 4px solid var(--error); color: #b91c1c; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; font-weight: 500; display: flex; align-items: center; gap: 8px; }
        .submit-btn { width: 100%; background: var(--gradient); color: white; border: none; padding: 1.2rem; border-radius: 12px; font-size: 1.1rem; font-weight: 700; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; margin-top: 2rem; display: flex; justify-content: center; align-items: center; gap: 10px; }
        .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(14, 165, 233, 0.3); }
        .captcha-wrap { display: flex; justify-content: center; margin-top: 2rem; }

        /* Modal */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.85); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal-content { background: white; padding: 3rem; border-radius: 24px; text-align: center; max-width: 450px; width: 90%; box-shadow: 0 0 0 1px rgba(255,255,255,0.1), 0 25px 50px -12px rgba(0,0,0,0.5); animation: scaleIn 0.4s cubic-bezier(0.16, 1, 0.3, 1); position: relative; }
        .icon-success { width: 80px; height: 80px; background: #ecfdf5; color: var(--secondary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; position: relative; }
        .icon-success::before { content: ''; position: absolute; inset: -10px; border-radius: 50%; border: 2px solid var(--secondary); animation: pulse 2s infinite; opacity: 0.5; }
        .countdown-text { font-size: 0.9rem; color: #94a3b8; margin-top: 1rem; font-weight: 500; }
        .countdown-text span { color: var(--primary); font-weight: 700; }

        @keyframes scaleIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
        @keyframes pulse { 0% { transform: scale(1); opacity: 0.8; } 100% { transform: scale(1.3); opacity: 0; } }

        /* Responsive Media Queries */
        @media (max-width: 768px) {
            .isolated-program-module .navbar { padding: 0.75rem 1rem; }
            .isolated-program-module .stalwarts-container { padding: 4rem 1.5rem; }
            .isolated-program-module .jury-headline { font-size: 2.5rem; margin-bottom: 2rem; }
            .banner { padding: 3rem 1.5rem; }
            .banner-logo { max-width: 200px; margin-bottom: 1rem; }
            .banner h1 { font-size: 2rem; }
            .form-body { padding: 2rem 1.5rem; }
            .grid-2 { grid-template-columns: 1fr; gap: 0; }
        }
        @media (max-width: 640px) {
            .isolated-program-module .jury-headline { font-size: 2rem; }
            .isolated-program-module .card { width: 240px; }
            .isolated-program-module .card-img-container { height: 10rem; }
            .isolated-program-module .card-name { font-size: 1rem; }
            .isolated-program-module .card-role, .isolated-program-module .card-topic { font-size: 11px; }
            .isolated-program-module .card-company { font-size: 12px; }
        }
    </style>
</head>
<body>

    <!-- SUCCESS MODAL -->
    <div class="modal-overlay <?php echo $showSuccessModal ? 'active' : ''; ?>" id="success-modal">
        <div class="modal-content">
            <div class="icon-success"><i data-lucide="shield-check" size="40"></i></div>
            <h2 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 10px; color: var(--text-main);">Application Received!</h2>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem; line-height: 1.5;">
                Thank you, <strong style="color:var(--text-main);"><?php echo htmlspecialchars($successName); ?></strong>. We will securely review your expertise and contact you soon.
            </p>
            <button onclick="forceRedirect()" class="submit-btn" style="margin-top:0; padding:1rem; font-size:1rem;">Continue to Summit Now</button>
            <div class="countdown-text">Auto-redirecting in <span id="timer">4</span> seconds...</div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 1. NAVIGATION & STALWARTS MODULE           -->
    <!-- ========================================== -->
    <div class="isolated-program-module">
        
        <div class="nav-wrapper">
            <nav class="navbar">
                <div class="nav-links">
                    <button class="nav-link active" onclick="switchForm('form-container-1', this)">Ethical Hacking Expert</button>
                    <button class="nav-link" onclick="switchForm('form-container-2', this)">Cybersecurity Professional</button>
                </div>
            </nav>
        </div>

        <div class="stalwarts-section">
            <section class="stalwarts-container">
                <h2 class="jury-headline">
                    List of <strong class="font-serif-italic">Jury</strong>
                </h2>
                <div class="carousel-wrapper" id="carouselWrapper">
                    <div id="carousel" class="carousel-container">
                        <!-- Cards injected by JS -->
                    </div>
                </div>
            </section>
        </div>
        
    </div>

    <!-- ========================================== -->
    <!-- 2. TOGGLEABLE FORMS SECTION                -->
    <!-- ========================================== -->
    
    <!-- FORM 1: Ethical Hacking Expert -->
    <div class="form-container" id="form-container-1">
        <div class="banner">
            <div class="banner-content">
                <img src="assets/img/logo/FCRF Academy.png" alt="FCRF Academy Logo" class="banner-logo">
                <h1>Are You an <span>Ethical Hacking</span> Expert?</h1>
                <p>FCRF Academy is inviting experienced ethical hackers, VAPT professionals, and cybersecurity practitioners to teach in upcoming training initiatives. If you have strong practical expertise and teaching ability, we would love to hear from you.</p>
            </div>
        </div>

        <div class="form-body">
            <?php if (!empty($message) && $messageType == "error"): ?>
                <div class="error-box"><i data-lucide="alert-triangle" size="20"></i> <span><?php echo $message; ?></span></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                
                <div class="section-title"><i data-lucide="user"></i> Personal Details</div>
                
                <div class="form-group">
                    <label>Full Name *</label>
                    <div class="input-wrapper">
                        <input type="text" name="full_name" required placeholder="John Doe"
                               pattern="[a-zA-Z\s\.]+" oninput="this.value = this.value.replace(/[^a-zA-Z\s\.]/g, '')"
                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                        <i data-lucide="user-circle" size="18"></i>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Email Address *</label>
                        <div class="input-wrapper">
                            <input type="email" name="email" required placeholder="john@example.com"
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            <i data-lucide="mail" size="18"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Phone Number *</label>
                        <div class="input-wrapper">
                            <input type="tel" name="phone" required placeholder="+91 98765 43210"
                                   pattern="[0-9]+" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                            <i data-lucide="phone" size="18"></i>
                        </div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Current Organization *</label>
                        <div class="input-wrapper">
                            <input type="text" name="organization" required placeholder="Tech Security Inc."
                                   value="<?php echo isset($_POST['organization']) ? htmlspecialchars($_POST['organization']) : ''; ?>">
                            <i data-lucide="building-2" size="18"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Official Designation *</label>
                        <div class="input-wrapper">
                            <input type="text" name="designation" required placeholder="Lead Penetration Tester"
                                   value="<?php echo isset($_POST['designation']) ? htmlspecialchars($_POST['designation']) : ''; ?>">
                            <i data-lucide="briefcase" size="18"></i>
                        </div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>City / Country *</label>
                        <div class="input-wrapper">
                            <input type="text" name="city_country" required placeholder="Mumbai, India"
                                   value="<?php echo isset($_POST['city_country']) ? htmlspecialchars($_POST['city_country']) : ''; ?>">
                            <i data-lucide="map-pin" size="18"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Years of Experience * <span class="optional-tag">(Cybersecurity / VAPT)</span></label>
                        <div class="input-wrapper">
                            <input type="number" name="experience" required min="0" placeholder="5"
                                   value="<?php echo isset($_POST['experience']) ? htmlspecialchars($_POST['experience']) : ''; ?>">
                            <i data-lucide="clock" size="18"></i>
                        </div>
                    </div>
                </div>

                <div class="section-title"><i data-lucide="terminal"></i> Technical Expertise</div>
                
                <div class="form-group">
                    <label>Which of the following areas can you teach confidently? * <span class="optional-tag">(Select all that apply)</span></label>
                    <div class="checkbox-grid">
                        <?php 
                            $topics = [
                                "Ethical Hacking Foundations, Scope, and Legal/Ethical Boundaries",
                                "Networking, Linux, Windows, Reconnaissance, and OSINT",
                                "Scanning, Vulnerability Assessment, and Validation",
                                "Web Application Security Testing",
                                "API Security Testing",
                                "Linux, Windows, Active Directory, and Internal Security Assessment",
                                "Network, Wireless, VPN, and Edge Security",
                                "Cloud Security, Containers, DevSecOps, and IaC Security",
                                "Mobile Application Security Testing",
                                "AI-Assisted Security Testing, AI Application Security, Reporting, and Re-Testing"
                            ];
                            $selected_topics = isset($_POST['teaching_areas']) ? $_POST['teaching_areas'] : [];
                            foreach($topics as $topic) {
                                $checked = in_array($topic, $selected_topics) ? 'checked' : '';
                                echo "<label class='checkbox-label'>
                                        <input type='checkbox' name='teaching_areas[]' value='$topic' $checked>
                                        <span>$topic</span>
                                      </label>";
                            }
                        ?>
                    </div>
                </div>

                <div class="section-title"><i data-lucide="link"></i> Links & Attachments</div>

                <div class="form-group">
                    <label>LinkedIn Profile <span class="optional-tag">(Optional)</span></label>
                    <div class="input-wrapper">
                        <input type="url" name="linkedin_url" placeholder="https://linkedin.com/in/yourprofile"
                               value="<?php echo isset($_POST['linkedin_url']) ? htmlspecialchars($_POST['linkedin_url']) : ''; ?>">
                        <i data-lucide="linkedin" size="18"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Upload Your CV <span class="optional-tag">(Optional - PDF/DOC up to 5MB)</span></label>
                    <label class="upload-area" for="cv-input-1" id="drop-area-1">
                        <i data-lucide="file-up" size="32"></i>
                        <div style="font-weight: 600; color: #1e293b;">Click to browse or drag file here</div>
                        <input type="file" name="cv" id="cv-input-1" style="display:none;" accept=".pdf,.doc,.docx">
                        <div id="cv-file-name-1" class="file-name"></div>
                    </label>
                </div>

                <div class="captcha-wrap">
                    <div class="g-recaptcha" data-sitekey="<?php echo $recaptcha_site_key; ?>"></div>
                </div>

                <button type="submit" class="submit-btn">
                    Securely Submit Application <i data-lucide="lock" size="18"></i>
                </button>
            </form>
        </div>
    </div>


    <!-- FORM 2: Cybersecurity Professional -->
    <div class="form-container" id="form-container-2" style="display:none;">
        <div class="banner">
            <div class="banner-content">
                <img src="assets/img/logo/FCRF Academy.png" alt="FCRF Academy Logo" class="banner-logo">
                <h1>Are You a <span>Cybersecurity</span> Expert?</h1>
                <p>FCRF Academy is inviting experienced ethical hackers, VAPT professionals, and cybersecurity practitioners to teach in upcoming training initiatives. If you have strong practical expertise and teaching ability, we would love to hear from you.</p>
            </div>
        </div>

        <div class="form-body">
            <?php if (!empty($message) && $messageType == "error"): ?>
                <div class="error-box"><i data-lucide="alert-triangle" size="20"></i> <span><?php echo $message; ?></span></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                
                <div class="section-title"><i data-lucide="user"></i> Personal Details</div>
                
                <div class="form-group">
                    <label>Full Name *</label>
                    <div class="input-wrapper">
                        <input type="text" name="full_name" required placeholder="John Doe"
                               pattern="[a-zA-Z\s\.]+" oninput="this.value = this.value.replace(/[^a-zA-Z\s\.]/g, '')"
                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                        <i data-lucide="user-circle" size="18"></i>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Email Address *</label>
                        <div class="input-wrapper">
                            <input type="email" name="email" required placeholder="john@example.com"
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            <i data-lucide="mail" size="18"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Phone Number *</label>
                        <div class="input-wrapper">
                            <input type="tel" name="phone" required placeholder="+91 98765 43210"
                                   pattern="[0-9]+" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                            <i data-lucide="phone" size="18"></i>
                        </div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Current Organization *</label>
                        <div class="input-wrapper">
                            <input type="text" name="organization" required placeholder="Tech Security Inc."
                                   value="<?php echo isset($_POST['organization']) ? htmlspecialchars($_POST['organization']) : ''; ?>">
                            <i data-lucide="building-2" size="18"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Official Designation *</label>
                        <div class="input-wrapper">
                            <input type="text" name="designation" required placeholder="Lead Penetration Tester"
                                   value="<?php echo isset($_POST['designation']) ? htmlspecialchars($_POST['designation']) : ''; ?>">
                            <i data-lucide="briefcase" size="18"></i>
                        </div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>City / Country *</label>
                        <div class="input-wrapper">
                            <input type="text" name="city_country" required placeholder="Mumbai, India"
                                   value="<?php echo isset($_POST['city_country']) ? htmlspecialchars($_POST['city_country']) : ''; ?>">
                            <i data-lucide="map-pin" size="18"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Years of Experience * <span class="optional-tag">(Cybersecurity / VAPT)</span></label>
                        <div class="input-wrapper">
                            <input type="number" name="experience" required min="0" placeholder="5"
                                   value="<?php echo isset($_POST['experience']) ? htmlspecialchars($_POST['experience']) : ''; ?>">
                            <i data-lucide="clock" size="18"></i>
                        </div>
                    </div>
                </div>

                <div class="section-title"><i data-lucide="terminal"></i> Technical Expertise</div>
                
                <div class="form-group">
                    <label>Which of the following areas can you teach confidently? * <span class="optional-tag">(Select all that apply)</span></label>
                    <div class="checkbox-grid">
                        <?php 
                            $topics = [
                                "Ethical Hacking Foundations, Scope, and Legal/Ethical Boundaries",
                                "Networking, Linux, Windows, Reconnaissance, and OSINT",
                                "Scanning, Vulnerability Assessment, and Validation",
                                "Web Application Security Testing",
                                "API Security Testing",
                                "Linux, Windows, Active Directory, and Internal Security Assessment",
                                "Network, Wireless, VPN, and Edge Security",
                                "Cloud Security, Containers, DevSecOps, and IaC Security",
                                "Mobile Application Security Testing",
                                "AI-Assisted Security Testing, AI Application Security, Reporting, and Re-Testing"
                            ];
                            $selected_topics = isset($_POST['teaching_areas']) ? $_POST['teaching_areas'] : [];
                            foreach($topics as $topic) {
                                $checked = in_array($topic, $selected_topics) ? 'checked' : '';
                                echo "<label class='checkbox-label'>
                                        <input type='checkbox' name='teaching_areas[]' value='$topic' $checked>
                                        <span>$topic</span>
                                      </label>";
                            }
                        ?>
                    </div>
                </div>

                <div class="section-title"><i data-lucide="link"></i> Links & Attachments</div>

                <div class="form-group">
                    <label>LinkedIn Profile <span class="optional-tag">(Optional)</span></label>
                    <div class="input-wrapper">
                        <input type="url" name="linkedin_url" placeholder="https://linkedin.com/in/yourprofile"
                               value="<?php echo isset($_POST['linkedin_url']) ? htmlspecialchars($_POST['linkedin_url']) : ''; ?>">
                        <i data-lucide="linkedin" size="18"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Upload Your CV <span class="optional-tag">(Optional - PDF/DOC up to 5MB)</span></label>
                    <label class="upload-area" for="cv-input-2" id="drop-area-2">
                        <i data-lucide="file-up" size="32"></i>
                        <div style="font-weight: 600; color: #1e293b;">Click to browse or drag file here</div>
                        <input type="file" name="cv" id="cv-input-2" style="display:none;" accept=".pdf,.doc,.docx">
                        <div id="cv-file-name-2" class="file-name"></div>
                    </label>
                </div>

                <div class="captcha-wrap">
                    <div class="g-recaptcha" data-sitekey="<?php echo $recaptcha_site_key; ?>"></div>
                </div>

                <button type="submit" class="submit-btn">
                    Securely Submit Application <i data-lucide="lock" size="18"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- SCRIPTS                                    -->
    <!-- ========================================== -->
    <script>
        lucide.createIcons();

        // --- NAVIGATION TOGGLE LOGIC ---
        function switchForm(formId, btnElement) {
            // Hide all forms
            document.getElementById('form-container-1').style.display = 'none';
            document.getElementById('form-container-2').style.display = 'none';
            
            // Show targeted form
            document.getElementById(formId).style.display = 'block';

            // Update Active Tab Styling
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            btnElement.classList.add('active');
        }

        // --- STALWARTS CAROUSEL LOGIC ---
        const stalwartsData = [
            { name: "Gopal Iyer", role: "Former Associate Director", company: "EY", topic: "How to work effectively in teams?", imgUrl: "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80" },
            { name: "Divij Bajaj", role: "Data & Applied Scientist", company: "Microsoft", topic: "Build your own AI Chatbot with ChatGPT", imgUrl: "https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80" },
            { name: "Jishnu Changkakoti", role: "Former Director, Marketing", company: "SAMSUNG", topic: "Competitive & Pricing Dynamics", imgUrl: "https://images.unsplash.com/photo-1556157382-97eda2d62296?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80" },
            { name: "Malthi S S.", role: "Former Director Product Management", company: "Product Strategy Co.", topic: "Advanced Product Strategy", imgUrl: "https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80" },
            { name: "Saurabh Sengupta", role: "Former SVP, Sales", company: "Zomato", topic: "Creating a Winner Sales Funnel", imgUrl: "https://images.unsplash.com/photo-1560250097-0b93528c311a?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80" },
            { name: "Akshay Gurnani", role: "Co-founder and CEO", company: "Schbang", topic: "Building a digital agency", imgUrl: "https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80" }
        ];

        const carousel = document.getElementById('carousel');

        function createCard(data) {
            const div = document.createElement('div');
            div.className = "card";
            div.innerHTML = `
                <div class="card-img-container">
                    <img src="${data.imgUrl}" alt="${data.name}">
                </div>
                <div class="card-info">
                    <h3 class="card-name">${data.name}</h3>
                    <p class="card-role">${data.role}</p>
                    <div class="card-company">${data.company}</div>
                    <div class="card-footer">
                        <p class="card-topic">"${data.topic}"</p>
                    </div>
                </div>
            `;
            return div;
        }

        function renderCards() {
            stalwartsData.forEach(item => carousel.appendChild(createCard(item)));
            stalwartsData.forEach(item => carousel.appendChild(createCard(item))); // Duplicate for loop
        }
        renderCards();

        // Momentum Scrolling Logic
        let isDown = false;
        let startX, scrollLeft, floatScroll = 0, velocity = 0, prevX = 0, momentumID;
        let isAutoScrolling = true;
        const autoScrollSpeed = 0.3; 
        const friction = 0.96;

        function autoScroll() {
            if (isAutoScrolling) {
                floatScroll += autoScrollSpeed;
                if (floatScroll >= carousel.scrollWidth / 2) floatScroll -= carousel.scrollWidth / 2;
                carousel.scrollLeft = floatScroll;
            }
            requestAnimationFrame(autoScroll);
        }
        requestAnimationFrame(autoScroll);

        function applyMomentum() {
            isAutoScrolling = false;
            function step() {
                if (Math.abs(velocity) > 0.2) {
                    carousel.scrollLeft -= velocity;
                    velocity *= friction;
                    if (carousel.scrollLeft <= 0) carousel.scrollLeft += carousel.scrollWidth / 2;
                    else if (carousel.scrollLeft >= carousel.scrollWidth / 2) carousel.scrollLeft -= carousel.scrollWidth / 2;
                    floatScroll = carousel.scrollLeft;
                    momentumID = requestAnimationFrame(step);
                } else {
                    isAutoScrolling = true;
                    floatScroll = carousel.scrollLeft;
                }
            }
            momentumID = requestAnimationFrame(step);
        }

        const handleDown = (e) => {
            isDown = true; isAutoScrolling = false; cancelAnimationFrame(momentumID);
            startX = (e.pageX || e.touches[0].pageX) - carousel.offsetLeft;
            scrollLeft = carousel.scrollLeft; floatScroll = carousel.scrollLeft;
            prevX = (e.pageX || e.touches[0].pageX); velocity = 0;
        };

        const handleUpOrLeave = () => { if (isDown) { isDown = false; applyMomentum(); } };

        const handleMove = (e) => {
            if (!isDown) return;
            const currentX = (e.pageX || e.touches[0].pageX);
            const walk = (currentX - carousel.offsetLeft - startX);
            carousel.scrollLeft = scrollLeft - walk;
            velocity = (currentX - prevX);
            prevX = currentX;
            floatScroll = carousel.scrollLeft;
        };

        carousel.addEventListener('mousedown', handleDown);
        carousel.addEventListener('touchstart', handleDown);
        carousel.addEventListener('mouseleave', handleUpOrLeave);
        carousel.addEventListener('mouseup', handleUpOrLeave);
        carousel.addEventListener('touchend', handleUpOrLeave);
        carousel.addEventListener('mousemove', (e) => { e.preventDefault(); handleMove(e); });
        carousel.addEventListener('touchmove', handleMove);

        // --- FILE UPLOAD VISUAL FEEDBACK (For Both Forms) ---
        function setupFileUpload(inputId, fileNameId, dropAreaId) {
            const fileInput = document.getElementById(inputId);
            const fileNameDiv = document.getElementById(fileNameId);
            const dropArea = document.getElementById(dropAreaId);

            fileInput.addEventListener('change', function(e) {
                if (e.target.files.length > 0) {
                    fileNameDiv.textContent = '✓ Selected: ' + e.target.files[0].name;
                    fileNameDiv.style.display = 'block';
                    dropArea.style.borderColor = "var(--secondary)";
                } else {
                    fileNameDiv.style.display = 'none';
                    dropArea.style.borderColor = "var(--border-color)";
                }
            });

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, e => { e.preventDefault(); e.stopPropagation(); }, false);
            });
            ['dragenter', 'dragover'].forEach(eventName => dropArea.addEventListener(eventName, () => dropArea.classList.add('dragover'), false));
            ['dragleave', 'drop'].forEach(eventName => dropArea.addEventListener(eventName, () => dropArea.classList.remove('dragover'), false));
            
            dropArea.addEventListener('drop', (e) => {
                fileInput.files = e.dataTransfer.files;
                fileInput.dispatchEvent(new Event('change'));
            });
        }

        setupFileUpload('cv-input-1', 'cv-file-name-1', 'drop-area-1');
        setupFileUpload('cv-input-2', 'cv-file-name-2', 'drop-area-2');

        // --- MODAL REDIRECTION ---
        function forceRedirect() { window.location.href = "https://summit.futurecrime.org"; }

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
    </script>
</body>
</html>
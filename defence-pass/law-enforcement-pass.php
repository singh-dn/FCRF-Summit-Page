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
$db_user = "u545411682_summit";
$db_pass = "Summit2026";
$db_name = "u545411682_summit";
$table_name = "fcrf_defence_passes";

// 🔴 RECAPTCHA KEYS
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
        $full_name         = sanitize_input($_POST['full_name'] ?? '');
        $email             = sanitize_input($_POST['email'] ?? '');
        $phone             = sanitize_input($_POST['phone'] ?? '');
        $designation       = sanitize_input($_POST['designation'] ?? '');
        $organization_name = sanitize_input($_POST['organization_name'] ?? '');

        // --- STRICT VALIDATION LOGIC ---
        if (empty($full_name) || empty($email) || empty($phone) || empty($designation) || empty($organization_name)) {
            throw new Exception("All fields marked with * are mandatory.");
        }

        if (!preg_match("/^[a-zA-Z\s\.]+$/", $full_name)) {
            throw new Exception("Name cannot contain numbers or special characters.");
        }

        if (!preg_match("/^[0-9\+\-\s]+$/", $phone) || strlen(preg_replace('/[^0-9]/', '', $phone)) < 10) {
            throw new Exception("Please enter a valid phone number.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        // 4. Block duplicate registrations on the same official email
        $check = $conn->prepare("SELECT id FROM `$table_name` WHERE `email` = ? LIMIT 1");
        if ($check) {
            $check->bind_param("s", $email);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                $check->close();
                $conn->close();
                throw new Exception("This email address is already registered. Your pass will be sent once your details are reviewed.");
            }
            $check->close();
        }

        // 5. Insert into Database Securely (Prepared Statements prevent SQLi)
        $sql = "INSERT INTO `$table_name` (`full_name`, `email`, `phone`, `designation`, `organization_name`) VALUES (?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) { throw new Exception("Database Error: " . $conn->error); }

        $stmt->bind_param("sssss", $full_name, $email, $phone, $designation, $organization_name);

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

    <title>Complimentary Pass — Law Enforcement &amp; Defence | FutureCrime Summit</title>
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
            .grid-2, .grid-3 { grid-template-columns: 1fr; gap: 0; }
        }
    </style>
</head>
<body>

    <!-- SUCCESS MODAL (With Countdown & Redirection) -->
    <div class="modal-overlay <?php echo $showSuccessModal ? 'active' : ''; ?>" id="success-modal">
        <div class="modal-content">
            <div class="icon-success"><i data-lucide="check" size="40"></i></div>
            <h2 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 10px; color: var(--text-main);">Registration Received!</h2>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem; line-height: 1.5;">
                Thank you, <strong style="color:var(--text-main);"><?php echo htmlspecialchars($successName); ?></strong>. Once your details have been reviewed, your complimentary summit pass will be sent to your registered email address.
            </p>
            <button onclick="forceRedirect()" class="submit-btn" style="margin-top:0; padding:1rem; font-size:1rem;">Continue</button>
            <div class="countdown-text">Auto-redirecting in <span id="timer">4</span> seconds...</div>
        </div>
    </div>

    <!-- THE MAIN FORM CONTAINER -->
    <div class="form-container">

        <div class="banner">
            <div class="banner-content">
                <img src="https://summit.futurecrime.org/assets/img/watch/transparent-png.png" alt="FCRF Logo" class="banner-logo">
                <h1>Complimentary Pass for <span>Law Enforcement &amp; Defence</span></h1>
                <p>Serving law enforcement and defence personnel can register here to receive a complimentary pass to the FutureCrime Summit.</p>
            </div>
        </div>

        <div class="form-body">

            <div class="intro-text">
                <p>The FutureCrime Summit is pleased to invite serving officers and personnel from police departments, state police forces, central armed police forces, the Indian Army, Navy, Air Force, and other defence and armed forces.</p>
                <p>Complete the registration form below with your official details. Once your information has been reviewed, your complimentary summit pass will be sent to your registered email address.</p>
            </div>

            <?php if (!empty($message) && $messageType == "error"): ?>
                <div class="error-box">
                    <i data-lucide="alert-triangle" size="20"></i>
                    <span><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST" id="regForm" novalidate>

                <!-- Registration Details -->
                <div class="section-title"><i data-lucide="shield-check"></i> Registration Details</div>

                <div class="form-group">
                    <label>Full Name *</label>
                    <span class="optional-tag mb-2">Enter your full name.</span>
                    <div class="input-wrapper">
                        <input type="text" name="full_name" required placeholder="e.g. Rajesh Kumar"
                               pattern="[a-zA-Z\s\.]+" oninput="this.value = this.value.replace(/[^a-zA-Z\s\.]/g, '')"
                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                        <i data-lucide="user-circle" size="18"></i>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Official Email Address *</label>
                        <span class="optional-tag mb-2">Enter the email address on which you would like to receive your pass.</span>
                        <div class="input-wrapper">
                            <input type="email" name="email" required placeholder="officer@department.gov.in"
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            <i data-lucide="mail" size="18"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Phone Number *</label>
                        <span class="optional-tag mb-2">Enter your active contact number.</span>
                        <div class="input-wrapper">
                            <input type="tel" name="phone" required placeholder="+91 98765 43210"
                                   pattern="[0-9\+\-\s]+" oninput="this.value = this.value.replace(/[^0-9\+\-\s]/g, '')"
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                            <i data-lucide="phone" size="18"></i>
                        </div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Designation / Rank *</label>
                        <span class="optional-tag mb-2">Enter your current designation or rank.</span>
                        <div class="input-wrapper">
                            <input type="text" name="designation" required placeholder="e.g. Deputy Superintendent of Police"
                                   value="<?php echo isset($_POST['designation']) ? htmlspecialchars($_POST['designation']) : ''; ?>">
                            <i data-lucide="badge-check" size="18"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Organisation / Force *</label>
                        <span class="optional-tag mb-2">Enter the name of the police department, armed force, defence organisation, or government agency you represent.</span>
                        <div class="input-wrapper">
                            <input type="text" name="organization_name" required placeholder="e.g. Maharashtra Police / Indian Army"
                                   value="<?php echo isset($_POST['organization_name']) ? htmlspecialchars($_POST['organization_name']) : ''; ?>">
                            <i data-lucide="building-2" size="18"></i>
                        </div>
                    </div>
                </div>

                <!-- Recaptcha -->
                <div class="captcha-wrap">
                    <div class="g-recaptcha" data-sitekey="<?php echo $recaptcha_site_key; ?>"></div>
                </div>

                <button type="submit" class="submit-btn">
                    Register for Your Complimentary Pass <i data-lucide="send" size="18"></i>
                </button>

                <div class="footer-note">
                    Passes are issued to serving personnel only. Please use your official email address wherever possible, as it helps us verify your registration faster.
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
                    if (el.type === 'checkbox') {
                        el.closest('.confirmation-card')?.classList.add('has-error');
                    } else {
                        el.closest('.form-group')?.classList.add('has-error');
                    }
                }
            });

            if (!isValid) {
                event.preventDefault(); // Stop form submission

                // Smooth scroll to the first missed field
                if (firstInvalid) {
                    const container = firstInvalid.closest('.form-group') || firstInvalid.closest('.confirmation-card');
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
                if (e.target.type === 'checkbox') {
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
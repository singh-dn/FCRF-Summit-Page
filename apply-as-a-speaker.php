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
        $first_name = trim($_POST['firstName']);
        $last_name = trim($_POST['lastName']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $qualification = trim($_POST['qualification']);
        $experience = trim($_POST['experience']);
        $designation = trim($_POST['designation']);
        $organization = trim($_POST['organization']);
        $websiteUrl = trim($_POST['websiteUrl']);
        $district = trim($_POST['district']);
        $state = trim($_POST['state']);
        $country = trim($_POST['country']);
        $social = trim($_POST['social']);
        $brief = trim($_POST['brief']);

        // --- STRICT VALIDATION LOGIC ---
        if (empty($first_name) || empty($last_name) || empty($phone) || empty($email)) {
            throw new Exception("First Name, Last Name, Phone, and Email are mandatory.");
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

        // 4. File Upload Logic
        $target_dir = "uploads/professionals/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0755, true); }
        
        // Handle Photo (Optional)
        $photoPath = "";
        if (!empty($_FILES["photo"]["name"])) {
            $photoName = time() . "_photo_" . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES["photo"]["name"]));
            $photoPath = $target_dir . $photoName;
            $photoType = strtolower(pathinfo($photoPath, PATHINFO_EXTENSION));
            if (!in_array($photoType, ['jpg', 'jpeg', 'png'])) { throw new Exception("Photo must be JPG, JPEG, or PNG."); }
            if (!move_uploaded_file($_FILES["photo"]["tmp_name"], $photoPath)) { throw new Exception("Failed to upload photo."); }
        }

        // Handle CV (Optional)
        $cvPath = "";
        if (!empty($_FILES["cv"]["name"])) {
            $cvName = time() . "_cv_" . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES["cv"]["name"]));
            $cvPath = $target_dir . $cvName;
            $cvType = strtolower(pathinfo($cvPath, PATHINFO_EXTENSION));
            if (!in_array($cvType, ['pdf', 'doc', 'docx'])) { throw new Exception("CV must be PDF, DOC, or DOCX."); }
            if (!move_uploaded_file($_FILES["cv"]["tmp_name"], $cvPath)) { throw new Exception("Failed to upload CV."); }
        }


        // 5. Insert into Database
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Registration Portal</title>
    <link rel="shortcut icon" href="assets/img/logo/favs.jpeg">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        /* Scoped Root */
        #professional-form-section {
            --if-font: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            --if-primary: #5b2dd8;
            --if-secondary: #db2777;
            --if-gradient: linear-gradient(135deg, #5b2dd8, #db2777);
            --if-bg: #f3f4f6;
            --if-window-bg: #ffffff;
            --if-border: #e5e7eb;
            --if-text-main: #1f2937;
            --if-text-muted: #6b7280;
            
            width: 100%;
            min-height: 100vh;
            padding: 2rem 1rem;
            background: var(--if-bg);
            font-family: var(--if-font);
            color: var(--if-text-main);
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        #professional-form-section *, 
        #professional-form-section *::before, 
        #professional-form-section *::after {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: #f3f4f6;
        }

        /* 3D Window Container */
        #professional-form-section .if-window {
            background: var(--if-window-bg);
            width: 100%;
            max-width: 900px;
            border-radius: 1.5rem;
            box-shadow: 
                0 0 0 1px rgba(0,0,0,0.03), 
                0 25px 50px -12px rgba(0, 0, 0, 0.15), 
                0 0 0 8px rgba(255, 255, 255, 0.4); 
            overflow: hidden;
            display: flex;
            flex-direction: column;
            position: relative;
            transform-style: preserve-3d;
            margin-top: 1rem;
        }

        /* Window Title Bar */
        #professional-form-section .if-titlebar {
            background: #f9fafb;
            border-bottom: 1px solid var(--if-border);
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        #professional-form-section .if-dot { width: 12px; height: 12px; border-radius: 50%; }
        #professional-form-section .if-dot.red { background: #ef4444; }
        #professional-form-section .if-dot.yellow { background: #f59e0b; }
        #professional-form-section .if-dot.green { background: #22c55e; }

        #professional-form-section .if-address-bar {
            background: #e5e7eb;
            height: 8px;
            border-radius: 4px;
            width: 200px;
            margin-left: 1rem;
            opacity: 0.5;
        }

        /* Main Content Layout */
        #professional-form-section .if-content {
            display: flex;
            flex-direction: column-reverse;
        }

        /* IMAGE PANEL (Banner) */
        #professional-form-section .if-image-panel {
            width: 100%;
            height: 250px;
            background: #1e1b4b; 
            position: relative;
            overflow: hidden;
        }

        #professional-form-section .if-bg-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.8;
            mix-blend-mode: overlay;
        }
        
        #professional-form-section .if-overlay-text {
            position: absolute;
            bottom: 2rem;
            left: 2rem;
            right: 2rem;
            color: white;
            z-index: 2;
        }

        /* FORM PANEL (Body) */
        #professional-form-section .if-form-panel {
            width: 100%;
            padding: 3rem 2.5rem;
        }

        /* Typography */
        #professional-form-section h2 {
            font-size: 2.2rem;
            font-weight: 800;
            margin: 0 0 0.5rem 0;
            background: var(--if-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        #professional-form-section h3 {
            font-size: 1.1rem;
            font-weight: 700;
            margin: 2.5rem 0 1rem 0;
            color: var(--if-text-main);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 0.5rem;
        }

        #professional-form-section h3 i { color: var(--if-primary); width: 20px; }

        /* Form Inputs */
        #professional-form-section .if-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        #professional-form-section .if-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        #professional-form-section .if-group { margin-bottom: 1.5rem; }

        #professional-form-section label {
            display: block;
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #374151;
        }

        #professional-form-section input[type="text"],
        #professional-form-section input[type="email"],
        #professional-form-section input[type="tel"],
        #professional-form-section input[type="number"],
        #professional-form-section input[type="url"],
        #professional-form-section select,
        #professional-form-section textarea {
            width: 100%;
            padding: 0.85rem 1rem;
            border-radius: 0.75rem;
            border: 1px solid var(--if-border);
            background: #f9fafb;
            font-family: inherit;
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        #professional-form-section input:focus,
        #professional-form-section textarea:focus,
        #professional-form-section select:focus {
            outline: none;
            border-color: var(--if-primary);
            box-shadow: 0 0 0 4px rgba(91, 45, 216, 0.1);
            background: white;
        }

        /* Upload Boxes */
        #professional-form-section .upload-grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 1.5rem; 
            margin-bottom: 1.5rem; 
        }
        #professional-form-section .upload-box { 
            border: 2px dashed var(--if-border); 
            border-radius: 1rem; 
            padding: 2rem 1rem; 
            text-align: center; 
            cursor: pointer; 
            background: #f9fafb; 
            transition: all 0.2s ease; 
            display: block; 
        }
        #professional-form-section .upload-box:hover { 
            background: #ffffff; 
            border-color: var(--if-primary); 
        }
        #professional-form-section .upload-box i { 
            color: var(--if-text-muted); 
            margin-bottom: 0.5rem; 
            transition: color 0.3s;
        }
        #professional-form-section .upload-box:hover i { 
            color: var(--if-primary); 
        }
        #professional-form-section .upload-label { 
            font-weight: 600; 
            font-size: 0.9rem; 
            color: #1e293b; 
        }
        #professional-form-section .file-indicator { 
            font-size: 0.8rem; 
            color: #10b981; 
            margin-top: 0.5rem; 
            display: none; 
            font-weight: 700; 
        }

        /* Confirmation */
        #professional-form-section .confirmation-card { 
            background: #f9fafb; 
            padding: 1.25rem; 
            border-radius: 0.75rem; 
            display: flex; 
            gap: 0.75rem; 
            align-items: flex-start; 
            border: 1px solid var(--if-border); 
            margin-top: 2rem;
        }
        #professional-form-section .confirmation-card input[type="checkbox"] { 
            width: 1.1rem; 
            height: 1.1rem; 
            cursor: pointer; 
            accent-color: var(--if-primary); 
            margin-top: 0.1rem; 
        }
        #professional-form-section .confirmation-card label { 
            margin: 0; 
            font-size: 0.9rem; 
            line-height: 1.5; 
            cursor: pointer; 
            color: #4b5563; 
        }

        /* Submit Button */
        #professional-form-section .if-btn-submit {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
            padding: 1.25rem;
            margin-top: 2rem;
            background: var(--if-gradient);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        #professional-form-section .if-btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(91, 45, 216, 0.3);
        }

        .server-error {
            background: #fee2e2; color: #b91c1c; padding: 1rem;
            border-radius: 0.75rem; margin-bottom: 2rem;
            border: 1px solid #fca5a5; font-size: 0.95rem;
            display: flex; align-items: center; gap: 0.5rem;
        }

        /* Modal Styles */
        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(15, 23, 42, 0.7);
            z-index: 2000; justify-content: center; align-items: center;
            backdrop-filter: blur(4px);
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            text-align: center; padding: 40px;
            background: white; border-radius: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid var(--if-border);
            width: 90%; max-width: 400px;
            animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .success-icon { font-size: 60px; color: #10b981; margin-bottom: 15px; display: block; }
        
        @keyframes slideUp { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }

        /* Mobile Fixes */
        @media (max-width: 768px) {
            #professional-form-section { padding: 1rem; }
            #professional-form-section .if-form-panel { padding: 2rem 1.5rem; }
            #professional-form-section .if-grid-2,
            #professional-form-section .if-grid-3,
            #professional-form-section .upload-grid { grid-template-columns: 1fr; }
            #professional-form-section .if-overlay-text h2 { font-size: 1.8rem; }
            #professional-form-section .if-image-panel { height: 200px; }
        }
</style>
</head>
<body>

    <!-- SUCCESS MODAL -->
    <div class="modal-overlay <?php echo $showSuccessModal ? 'active' : ''; ?>" id="success-modal">
        <div class="modal-box">
            <span class="success-icon"><i data-lucide="check-circle" size="64"></i></span>
            <h2 style="margin:0 0 10px 0; color:#1f2937; font-size: 1.8rem; font-weight: 800;">Success!</h2>
            <p style="color:#6b7280; margin-bottom:25px; line-height: 1.5;">
                Hello <?php echo htmlspecialchars($successName ?? ''); ?>, your registration has been successfully saved.
            </p>
            <button onclick="closeModal()" style="width: 100%; padding: 14px; border:none; background: #0f172a; color:white; border-radius:12px; cursor:pointer; font-weight: 600; font-size: 1rem; transition: background 0.2s;">Continue to Summit</button>
        </div>
    </div>

    <section id="professional-form-section">
        <div class="if-window">
            
            <div class="if-titlebar">
                <div class="if-dot red"></div>
                <div class="if-dot yellow"></div>
                <div class="if-dot green"></div>
                <div class="if-address-bar"></div>
            </div>

            <div class="if-content">
                
                <div class="if-form-panel">
                    <h2>Registration Portal</h2>
                    <p style="color: #6b7280; margin-top: 0;">Enter your professional details. All fields with * are required.</p>

                    <?php if (!empty($message) && $messageType == "error"): ?>
                        <div class="server-error">
                            <i data-lucide="alert-circle" size="20"></i>
                            <span><?php echo $message; ?></span>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" enctype="multipart/form-data">
                        
                        <!-- Identity Section -->
                        <h3><i data-lucide="user"></i> Identity</h3>
                        
                        <div class="if-grid-2">
                            <div class="if-group">
                                <label>First Name *</label>
                                <input type="text" name="firstName" required placeholder="John"
                                       pattern="[a-zA-Z\s\.]+" oninput="this.value = this.value.replace(/[^a-zA-Z\s\.]/g, '')"
                                       value="<?php echo isset($_POST['firstName']) ? htmlspecialchars($_POST['firstName']) : ''; ?>">
                            </div>
                            <div class="if-group">
                                <label>Last Name *</label>
                                <input type="text" name="lastName" required placeholder="Doe"
                                       pattern="[a-zA-Z\s\.]+" oninput="this.value = this.value.replace(/[^a-zA-Z\s\.]/g, '')"
                                       value="<?php echo isset($_POST['lastName']) ? htmlspecialchars($_POST['lastName']) : ''; ?>">
                            </div>
                        </div>

                        <div class="if-grid-2">
                            <div class="if-group">
                                <label>Phone/Mobile *</label>
                                <input type="tel" name="phone" required placeholder="10-digit number"
                                       pattern="[0-9]+" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                            </div>
                            <div class="if-group">
                                <label>Email *</label>
                                <input type="email" name="email" required placeholder="john@example.com"
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                        </div>

                        <!-- Professional Details Section -->
                        <h3><i data-lucide="briefcase"></i> Professional Details *</h3>
                        
                        <div class="if-grid-2">
                            <div class="if-group">
                                <label>Educational Qualification *</label>
                                <input type="text" name="qualification" placeholder="e.g. MCA Student" required
                                       value="<?php echo isset($_POST['qualification']) ? htmlspecialchars($_POST['qualification']) : ''; ?>">
                            </div>
                            <div class="if-group">
                                <label>Years of Experience *</label>
                                <input type="number" name="experience" min="0" placeholder="e.g. 2" required
                                       value="<?php echo isset($_POST['experience']) ? htmlspecialchars($_POST['experience']) : ''; ?>">
                            </div>
                        </div>

                        <div class="if-grid-2">
                            <div class="if-group">
                                <label>Designation *</label>
                                <input type="text" name="designation" placeholder="e.g. Lead Developer" required
                                       value="<?php echo isset($_POST['designation']) ? htmlspecialchars($_POST['designation']) : ''; ?>">
                            </div>
                            <div class="if-group">
                                <label>Name of Organization *</label>
                                <input type="text" name="organization" placeholder="e.g. Tech Solutions Inc." required
                                       value="<?php echo isset($_POST['organization']) ? htmlspecialchars($_POST['organization']) : ''; ?>">
                            </div>
                        </div>

                        <!-- Presence & Location Section -->
                        <h3><i data-lucide="globe"></i> Presence & Location *</h3>
                        
                        <div class="if-group">
                            <label>Website URL</label>
                            <input type="url" name="websiteUrl" placeholder="https://yourproduct.com"
                                   value="<?php echo isset($_POST['websiteUrl']) ? htmlspecialchars($_POST['websiteUrl']) : ''; ?>">
                        </div>

                        <div class="if-grid-3">
                            <div class="if-group">
                                <label>District *</label>
                                <input type="text" name="district" placeholder="e.g. Ulhasnagar" required
                                       value="<?php echo isset($_POST['district']) ? htmlspecialchars($_POST['district']) : ''; ?>">
                            </div>
                            <div class="if-group">
                                <label>State *</label>
                                <input type="text" name="state" placeholder="e.g. Maharashtra" required
                                       value="<?php echo isset($_POST['state']) ? htmlspecialchars($_POST['state']) : ''; ?>">
                            </div>
                            <div class="if-group">
                                <label>Country *</label>
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
                            </div>
                        </div>

                        <div class="if-group">
                            <label>LinkedIn / Social Media / Website (if any)</label>
                            <input type="url" name="social" placeholder="LinkedIn or Portfolio URL"
                                   value="<?php echo isset($_POST['social']) ? htmlspecialchars($_POST['social']) : ''; ?>">
                        </div>

                        <!-- Attachments Section -->
                        <h3><i data-lucide="paperclip"></i> Attachments</h3>
                        
                        <div class="upload-grid">
                            <label class="upload-box" for="photo-input">
                                <i data-lucide="camera" size="32"></i>
                                <div class="upload-label">Upload your latest photo *</div>
                                <input type="file" name="photo" class="hidden" accept="image/jpeg, image/png, image/jpg" id="photo-input" style="display:none;" required>
                                <div id="photo-file" class="file-indicator"></div>
                            </label>
                            <label class="upload-box" for="cv-input">
                                <i data-lucide="file-up" size="32"></i>
                                <div class="upload-label">Upload your CV/ Bio<br>(PDF, Doc) *</div>
                                <input type="file" name="cv" class="hidden" accept=".pdf,.doc,.docx" id="cv-input" style="display:none;" required>
                                <div id="cv-file" class="file-indicator"></div>
                            </label>
                        </div>

                        <div class="if-group">
                            <label>Area of Expertise / Brief of Work Experience *</label>
                            <textarea name="brief" rows="4" required placeholder="Tell us about your background..."><?php echo isset($_POST['brief']) ? htmlspecialchars($_POST['brief']) : ''; ?></textarea>
                        </div>

                        <!-- Confirmation -->
                        <div class="confirmation-card">
                            <input type="checkbox" id="confirm" required>
                            <label for="confirm">I confirm that the information provided is accurate and true to my knowledge.</label>
                        </div>

                        <!-- Recaptcha -->
                        <div style="margin-top:20px; display:flex; justify-content:center;">
                            <!-- Ensure your variables $recaptcha_site_key are defined in PHP logic -->
                            <div class="g-recaptcha" data-sitekey="<?php echo isset($recaptcha_site_key) ? $recaptcha_site_key : ''; ?>"></div>
                        </div>

                        <button type="submit" class="if-btn-submit" id="submit-btn">
                            <span>Submit Registration</span> <i data-lucide="send" size="18"></i>
                        </button>

                    </form>
                </div>

                <!-- Image Banner (Top of Form) -->
                <div class="if-image-panel">
                    <img src="https://images.unsplash.com/photo-1556761175-5973dc0f32e7?q=80&w=2070&auto=format&fit=crop" class="if-bg-image" alt="Professional Networking">
                    <div class="if-overlay-text">
                        <h2 style="color: white; font-size: 2.5rem; line-height: 1.1;">Join the Network.</h2>
                        <p style="color: rgba(255,255,255,0.8); margin-top: 1rem; font-size: 1.1rem;">Register as a professional and connect with global leaders.</p>
                    </div>
                </div>

            </div>
        </div>
    </section>

  <!-- Scripts -->
<script>
     lucide.createIcons();

        // File Selection Feedback
        document.getElementById('photo-input').onchange = (e) => {
            const display = document.getElementById('photo-file');
            if (e.target.files.length > 0) {
                display.textContent = '✓ ' + e.target.files[0].name;
                display.style.display = 'block';
            } else {
                display.style.display = 'none';
            }
        };

        document.getElementById('cv-input').onchange = (e) => {
            const display = document.getElementById('cv-file');
            if (e.target.files.length > 0) {
                display.textContent = '✓ ' + e.target.files[0].name;
                display.style.display = 'block';
            } else {
                display.style.display = 'none';
            }
        };

        function closeModal() {
            document.getElementById('success-modal').classList.remove('active');
            window.location.href = "https://summit.futurecrime.org"; // Redirect on click
        }
        
        // Auto-redirect after 4 seconds
        <?php if ($showSuccessModal): ?>
        setTimeout(function() {
            window.location.href = "https://summit.futurecrime.org";
        }, 4000);
        <?php endif; ?>
    </script>
</body>
</html>
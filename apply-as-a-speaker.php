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

        // 4. File Upload Logic
        $target_dir = "uploads/professionals/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0755, true); }
        
        // Handle Photo
        if (empty($_FILES["photo"]["name"])) { throw new Exception("Please upload your photo."); }
        $photoName = time() . "_photo_" . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES["photo"]["name"]));
        $photoPath = $target_dir . $photoName;
        $photoType = strtolower(pathinfo($photoPath, PATHINFO_EXTENSION));
        if (!in_array($photoType, ['jpg', 'jpeg', 'png'])) { throw new Exception("Photo must be JPG, JPEG, or PNG."); }
        if (!move_uploaded_file($_FILES["photo"]["tmp_name"], $photoPath)) { throw new Exception("Failed to upload photo."); }

        // Handle CV
        if (empty($_FILES["cv"]["name"])) { throw new Exception("Please upload your CV/Bio."); }
        $cvName = time() . "_cv_" . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES["cv"]["name"]));
        $cvPath = $target_dir . $cvName;
        $cvType = strtolower(pathinfo($cvPath, PATHINFO_EXTENSION));
        if (!in_array($cvType, ['pdf', 'doc', 'docx'])) { throw new Exception("CV must be PDF, DOC, or DOCX."); }
        if (!move_uploaded_file($_FILES["cv"]["tmp_name"], $cvPath)) { throw new Exception("Failed to upload CV."); }


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
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        :root {
            --primary-gradient: linear-gradient(93deg, #5135FF 10.65%, #FF5455 89.35%);
            --primary-solid: #5135FF;
            --bg-page: #f0f2f5;
            --bg-ipad: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --ipad-bezel: #1a1a1a;
            --error-color: #ef4444;
            --success-color: #10b981;
            --font-main: 'Inter', sans-serif;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body { 
            font-family: var(--font-main); 
            background-color: var(--bg-page); 
            color: var(--text-main); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 0; 
        }

        /* Responsive iPad Pro Frame */
        .ipad-container { 
            position: relative; 
            width: 100%; 
            max-width: 1024px; 
            height: 90vh; 
            max-height: 900px; 
            background-color: #000; 
            border-radius: 50px; 
            border: 4px solid #334155; 
            box-shadow: 0 0 0 12px var(--ipad-bezel), 0 30px 60px -15px rgba(0,0,0,0.3); 
            display: flex; 
            flex-direction: column; 
            overflow: hidden; 
            transition: all 0.3s ease;
        }

        /* Mobile Adjustments */
        @media (max-width: 1024px) {
            body { background-color: var(--bg-ipad); }
            .ipad-container {
                height: 100vh;
                max-height: none;
                border-radius: 0;
                border: none;
                box-shadow: none;
            }
            .bezel-sensor { display: none !important; }
        }

        /* Status Bar */
        .status-bar { 
            height: 40px; 
            padding: 0 25px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            background: rgba(255, 255, 255, 0.9); 
            backdrop-filter: blur(10px); 
            border-bottom: 1px solid var(--border-color); 
            z-index: 20; 
            font-size: 0.85rem; 
            font-weight: 700; 
            color: #000; 
        }
        .status-right { display: flex; align-items: center; gap: 8px; }

        /* Content Area */
        .main-viewport { flex: 1; background-color: var(--bg-ipad); overflow-y: auto; position: relative; }
        .main-viewport::-webkit-scrollbar { width: 6px; }
        .main-viewport::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, 0.1); border-radius: 10px; }

        /* Header Branding */
        .header-branding { background: var(--primary-gradient); padding: 50px 30px; text-align: center; color: white; position: relative; overflow: hidden; }
        .header-branding::before { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-image: linear-gradient(rgba(255,255,255,0.1) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.1) 1px, transparent 1px); background-size: 30px 30px; }
        .header-branding h1 { font-size: 2rem; font-weight: 800; margin-bottom: 10px; position: relative; letter-spacing: -0.02em; }
        .header-branding p { font-size: 0.9rem; opacity: 0.9; max-width: 500px; margin: 0 auto; position: relative; font-weight: 400; }

        /* Form Layout */
        .form-content { padding: 30px; max-width: 900px; margin: 0 auto; }
        section { margin-bottom: 40px; }
        .section-header { display: flex; gap: 12px; margin-bottom: 20px; align-items: center; }
        .icon-box { background: #f1f5f9; color: var(--primary-solid); padding: 10px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
        .section-header h2 { font-size: 1.2rem; font-weight: 700; color: #0f172a; }

        .grid-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        @media (max-width: 640px) { .grid-row { grid-template-columns: 1fr; } }

        .form-group { margin-bottom: 15px; }
        label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #334155; }
        input, select, textarea { width: 100%; padding: 12px 15px; border-radius: 12px; border: 1px solid var(--border-color); background-color: #fff; color: var(--text-main); font-size: 0.9rem; transition: all 0.2s ease; outline: none; }
        input:focus, select:focus, textarea:focus { border-color: var(--primary-solid); box-shadow: 0 0 0 4px rgba(81, 53, 255, 0.1); }

        /* Server Error Message */
        .server-error { background: #fee2e2; border-left: 4px solid #ef4444; color: #b91c1c; padding: 15px; border-radius: 8px; margin-bottom: 25px; font-weight: 500; font-size: 0.9rem; }

        /* Upload Boxes */
        .upload-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .upload-box { border: 2px dashed var(--border-color); border-radius: 15px; padding: 25px 15px; text-align: center; cursor: pointer; background: #fafafa; transition: all 0.2s ease; display: block; }
        .upload-box:hover { background: #fff; border-color: var(--primary-solid); }
        .upload-box i { font-size: 24px; color: var(--text-muted); margin-bottom: 8px; }
        .upload-box .upload-label { font-weight: 700; font-size: 13px; color: #1e293b; }
        .file-indicator { font-size: 10px; color: var(--success-color); margin-top: 4px; display: none; font-weight: 700; }

        /* Confirmation & Button */
        .confirmation-card { background: #f8fafc; padding: 18px; border-radius: 15px; display: flex; gap: 12px; align-items: flex-start; border: 1px solid var(--border-color); }
        .confirmation-card input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary-solid); margin-top: 2px; }
        .confirmation-card label { margin: 0; font-size: 0.8rem; line-height: 1.4; cursor: pointer; color: #475569; }

        .captcha-wrapper { display: flex; justify-content: center; margin-top: 20px; }

        .submit-btn { width: 100%; background: var(--primary-gradient); color: white; border: none; padding: 16px; border-radius: 14px; font-size: 1rem; font-weight: 800; margin-top: 25px; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 8px 20px -5px rgba(81, 53, 255, 0.3); }
        .submit-btn:hover { transform: translateY(-2px); filter: brightness(1.1); }
        
        /* POPUP MODAL STYLES */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(5px); display: flex; align-items: center; justify-content: center; z-index: 1000; opacity: 0; visibility: hidden; transition: all 0.3s ease; }
        .modal-overlay.active { opacity: 1; visibility: visible; }
        .modal-content { background: white; padding: 40px; border-radius: 30px; width: 90%; max-width: 450px; text-align: center; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); transform: scale(0.9); transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .modal-overlay.active .modal-content { transform: scale(1); }
        .success-icon-popup { width: 80px; height: 80px; background: var(--primary-gradient); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; box-shadow: 0 10px 20px rgba(81, 53, 255, 0.2); }

        /* Bezel Elements */
        .bezel-sensor { position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 160px; height: 32px; display: flex; align-items: center; justify-content: center; gap: 12px; z-index: 30; }
        .sensor-dot { width: 4px; height: 4px; background: #333; border-radius: 50%; }
        .sensor-lens { width: 8px; height: 8px; background: #111; border: 1px solid #333; border-radius: 50%; }
        .home-indicator { position: sticky; bottom: 8px; left: 50%; width: 120px; height: 5px; background: rgba(0,0,0,0.1); border-radius: 10px; margin: 0 auto; }
    </style>
</head>
<body>

    <div class="ipad-container">
        <div class="bezel-sensor">
            <div class="sensor-dot"></div>
            <div class="sensor-lens"></div>
        </div>

        <div class="status-bar">
            <div id="clock">9:41 AM</div>
            <div class="status-right">
                <i data-lucide="signal" size="14"></i>
                <i data-lucide="wifi" size="14"></i>
                <i data-lucide="battery" size="18"></i>
            </div>
        </div>

        <div class="main-viewport" id="viewport">
            <div id="form-container">
                <header class="header-branding">
                    <h1>Registration Portal</h1>
                    <p>Enter your professional details. All fields with * are required.</p>
                </header>

                <div class="form-content">
                    
                    <?php if (!empty($message) && $messageType == "error"): ?>
                        <div class="server-error">
                            <i data-lucide="alert-circle" size="18" style="display:inline; vertical-align:middle; margin-right:5px;"></i>
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" enctype="multipart/form-data">
                        
                        <!-- Identity Section -->
                        <section>
                            <div class="section-header">
                                <div class="icon-box"><i data-lucide="user" size="20"></i></div>
                                <h2>Identity</h2>
                            </div>
                            <div class="grid-row">
                                <div class="form-group">
                                    <label>First Name *</label>
                                    <input type="text" name="firstName" required placeholder="John"
                                           pattern="[a-zA-Z\s\.]+" oninput="this.value = this.value.replace(/[^a-zA-Z\s\.]/g, '')"
                                           value="<?php echo isset($_POST['firstName']) ? htmlspecialchars($_POST['firstName']) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Last Name *</label>
                                    <input type="text" name="lastName" required placeholder="Doe"
                                           pattern="[a-zA-Z\s\.]+" oninput="this.value = this.value.replace(/[^a-zA-Z\s\.]/g, '')"
                                           value="<?php echo isset($_POST['lastName']) ? htmlspecialchars($_POST['lastName']) : ''; ?>">
                                </div>
                            </div>
                            <div class="grid-row">
                                <div class="form-group">
                                    <label>Phone/Mobile *</label>
                                    <input type="tel" name="phone" required placeholder="10-digit number"
                                           pattern="[0-9]+" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Email *</label>
                                    <input type="email" name="email" required placeholder="john@example.com"
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                </div>
                            </div>
                        </section>

                        <!-- Professional Section -->
                        <section>
                            <div class="section-header">
                                <div class="icon-box"><i data-lucide="briefcase" size="20"></i></div>
                                <h2>Professional Details</h2>
                            </div>
                            <div class="grid-row">
                                <div class="form-group">
                                    <label>Educational Qualification *</label>
                                    <input type="text" name="qualification" required placeholder="e.g. MBA"
                                           value="<?php echo isset($_POST['qualification']) ? htmlspecialchars($_POST['qualification']) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Years of Experience *</label>
                                    <input type="number" name="experience" required min="0" placeholder="e.g. 2"
                                           value="<?php echo isset($_POST['experience']) ? htmlspecialchars($_POST['experience']) : ''; ?>">
                                </div>
                            </div>
                            <div class="grid-row">
                                <div class="form-group">
                                    <label>Designation *</label>
                                    <input type="text" name="designation" required placeholder="e.g. Lead Developer"
                                           value="<?php echo isset($_POST['designation']) ? htmlspecialchars($_POST['designation']) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Name of Organization *</label>
                                    <input type="text" name="organization" required placeholder="e.g. Tech Solutions Inc."
                                           value="<?php echo isset($_POST['organization']) ? htmlspecialchars($_POST['organization']) : ''; ?>">
                                </div>
                            </div>
                        </section>

                        <!-- Online & Location Section -->
                        <section>
                            <div class="section-header">
                                <div class="icon-box"><i data-lucide="globe" size="20"></i></div>
                                <h2>Presence & Location</h2>
                            </div>
                            <div class="form-group">
                                <label>Website URL (if any)</label>
                                <input type="url" name="websiteUrl" placeholder="https://yourproduct.com"
                                       value="<?php echo isset($_POST['websiteUrl']) ? htmlspecialchars($_POST['websiteUrl']) : ''; ?>">
                            </div>
                            <div class="grid-row" style="grid-template-columns: repeat(3, 1fr);">
                                <div class="form-group">
                                    <label>District *</label>
                                    <input type="text" name="district" required placeholder="e.g. Ulhasnagar"
                                           value="<?php echo isset($_POST['district']) ? htmlspecialchars($_POST['district']) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label>State *</label>
                                    <input type="text" name="state" required placeholder="e.g. Maharashtra"
                                           value="<?php echo isset($_POST['state']) ? htmlspecialchars($_POST['state']) : ''; ?>">
                                </div>
                                <div class="form-group">
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
                            <div class="form-group">
                                <label>LinkedIn / Social Media / Website (if any)</label>
                                <input type="url" name="social" placeholder="LinkedIn or Portfolio URL"
                                       value="<?php echo isset($_POST['social']) ? htmlspecialchars($_POST['social']) : ''; ?>">
                            </div>
                        </section>

                        <!-- Files Section -->
                        <section>
                            <div class="section-header">
                                <div class="icon-box"><i data-lucide="paperclip" size="20"></i></div>
                                <h2>Attachments</h2>
                            </div>
                            <div class="upload-grid">
                                <label class="upload-box">
                                    <i data-lucide="camera"></i>
                                    <div class="upload-label">Upload your latest photo *</div>
                                    <input type="file" name="photo" required class="hidden" accept="image/jpeg, image/png, image/jpg" id="photo-input" style="display:none;">
                                    <div id="photo-file" class="file-indicator">✓ Photo Selected</div>
                                </label>
                                <label class="upload-box">
                                    <i data-lucide="file-up"></i>
                                    <div class="upload-label">Upload your CV/ Bio *<br>(PDF, Doc)</div>
                                    <input type="file" name="cv" required class="hidden" accept=".pdf,.doc,.docx" id="cv-input" style="display:none;">
                                    <div id="cv-file" class="file-indicator">✓ CV Selected</div>
                                </label>
                            </div>
                            <div class="form-group">
                                <label>Area of Expertise / Brief of Work Experience *</label>
                                <textarea name="brief" required rows="4" placeholder="Tell us about your background..."><?php echo isset($_POST['brief']) ? htmlspecialchars($_POST['brief']) : ''; ?></textarea>
                            </div>
                        </section>

                        <div class="confirmation-card">
                            <input type="checkbox" id="confirm" required>
                            <label for="confirm">I confirm that the information provided is accurate and true to my knowledge.</label>
                        </div>

                        <!-- Recaptcha -->
                        <div class="captcha-wrapper">
                            <div class="g-recaptcha" data-sitekey="<?php echo $recaptcha_site_key; ?>"></div>
                        </div>

                        <button type="submit" class="submit-btn" id="submit-btn">
                            <span id="btn-text">Submit Registration</span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="home-indicator"></div>
        </div>
    </div>

    <!-- SUCCESS POPUP MODAL -->
    <div class="modal-overlay <?php echo $showSuccessModal ? 'active' : ''; ?>" id="success-modal">
        <div class="modal-content">
            <div class="success-icon-popup"><i data-lucide="check" size="40"></i></div>
            <h2 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 10px; color:#1e293b;">Success!</h2>
            <p style="color: var(--text-muted); margin-bottom: 30px; line-height: 1.5; font-size: 0.95rem;">
                Hello <?php echo htmlspecialchars($successName); ?>, your registration has been successfully saved.
            </p>
            <button onclick="closeModal()" class="submit-btn" style="margin-top: 0; background: #0f172a; box-shadow: none;">Back to Portal</button>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // Live Clock
        function updateClock() {
            const now = new Date();
            let h = now.getHours(); let m = now.getMinutes();
            const ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12; m = m < 10 ? '0' + m : m;
            document.getElementById('clock').textContent = h + ':' + m + ' ' + ampm;
        }
        setInterval(updateClock, 1000); updateClock();

        // File Selection Feedback
        document.getElementById('photo-input').onchange = (e) => {
            if (e.target.files.length > 0) document.getElementById('photo-file').style.display = 'block';
        };
        document.getElementById('cv-input').onchange = (e) => {
            if (e.target.files.length > 0) document.getElementById('cv-file').style.display = 'block';
        };

        function closeModal() {
            document.getElementById('success-modal').classList.remove('active');
            window.location.href = window.location.href; // Refresh page to clear
        }
    </script>
</body>
</html>
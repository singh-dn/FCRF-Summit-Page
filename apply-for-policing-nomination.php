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
$table_name = "fcrf_policing_awards"; 

// 🔴 RECAPTCHA KEYS 
$recaptcha_site_key = "6LfkXYwsAAAAAO8Vwrhg7KdnocQzL-yQwl8zgTt4";
$recaptcha_secret = "6LfkXYwsAAAAAOg_C4CYVgNlQOyG9X1RU4Pl576h"; 

// --- SECURITY: Input Sanitization Function to prevent XSS ---
function sanitize_input($data) {
    if (is_array($data)) { return array_map('sanitize_input', $data); }
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
        if ($conn->connect_error) { throw new Exception("Database Connection Failed."); }
        $conn->set_charset("utf8mb4");

        // 3. Process & Validate Inputs Securely
        $nomination_type = sanitize_input($_POST['nomination_type'] ?? '');
        $nominee_name    = sanitize_input($_POST['nominee_name'] ?? '');
        $email           = sanitize_input($_POST['email'] ?? '');
        $phone           = sanitize_input($_POST['phone'] ?? '');
        $department      = sanitize_input($_POST['department'] ?? '');
        $designation     = sanitize_input($_POST['designation'] ?? '');
        $police_station  = sanitize_input($_POST['police_station'] ?? '');
        $city            = sanitize_input($_POST['city'] ?? '');
        $state           = sanitize_input($_POST['state'] ?? '');
        $country         = sanitize_input($_POST['country'] ?? '');
        
        // Representative details
        $rep_name        = sanitize_input($_POST['rep_name'] ?? '');
        $rep_designation = sanitize_input($_POST['rep_designation'] ?? '');
        $rep_email       = sanitize_input($_POST['rep_email'] ?? '');
        $rep_phone       = sanitize_input($_POST['rep_phone'] ?? '');

        // Award Categories (Multi-Select)
        $award_category_arr = $_POST['award_category'] ?? [];
        if (empty($award_category_arr)) { throw new Exception("Please select at least one award category."); }
        $award_category = implode(" | ", sanitize_input($award_category_arr));
        
        $other_category   = sanitize_input($_POST['other_category'] ?? '');
        
        // Service Profile
        $experience_years = sanitize_input($_POST['experience_years'] ?? '');
        $current_work_area= sanitize_input($_POST['current_work_area'] ?? '');
        $jurisdiction     = sanitize_input($_POST['jurisdiction'] ?? '');
        
        // Summaries & Highlights
        $summary          = sanitize_input($_POST['summary'] ?? '');
        
        // Links
        $linkedin_url     = sanitize_input($_POST['linkedin_url'] ?? '');
        $website_url      = sanitize_input($_POST['website_url'] ?? '');
        $supporting_links = sanitize_input($_POST['supporting_links'] ?? '');
        
        $declaration = isset($_POST['declaration']) ? 'Yes' : '';

        // --- STRICT VALIDATION LOGIC ---
        if (empty($nomination_type) || empty($nominee_name) || empty($email) || empty($phone) || empty($department) || empty($designation) || empty($city) || empty($state) || empty($country) || empty($experience_years) || empty($current_work_area) || empty($jurisdiction) || empty($summary) || empty($declaration)) {
            throw new Exception("All fields marked with * are mandatory.");
        }

        if ($nomination_type === 'Nomination of a Police Unit / Cyber Cell / Police Station / Department') {
            if(empty($rep_name) || empty($rep_designation) || empty($rep_email) || empty($rep_phone)) {
                throw new Exception("Nodal Officer details are mandatory for Unit/Department nominations.");
            }
            if (!filter_var($rep_email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid nodal officer email format.");
            }
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { throw new Exception("Invalid nominee email format."); }
        if (!empty($linkedin_url) && !filter_var($linkedin_url, FILTER_VALIDATE_URL)) { throw new Exception("Please provide a valid LinkedIn URL."); }
        if (!empty($website_url) && !filter_var($website_url, FILTER_VALIDATE_URL)) { throw new Exception("Please provide a valid Website URL."); }

        // 4. File Upload Logic
        $target_dir_cv = "uploads/nominations_cv/";
        $target_dir_docs = "uploads/nominations_docs/";
        if (!file_exists($target_dir_cv)) { mkdir($target_dir_cv, 0755, true); }
        if (!file_exists($target_dir_docs)) { mkdir($target_dir_docs, 0755, true); }
        
        // Allowed MIME Types
        $allowed_mimes_cv = [
            'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        $allowed_mimes_docs = array_merge($allowed_mimes_cv, [
            'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'
        ]);

        // Handle CV
        $cvPath = "";
        if (!empty($_FILES["cv"]["name"])) {
            $cvName = time() . "_cv_" . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES["cv"]["name"]));
            $cvPath = $target_dir_cv . $cvName;
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES["cv"]["tmp_name"]);
            finfo_close($finfo);
            
            if (!in_array($mime, $allowed_mimes_cv)) { throw new Exception("Invalid CV file. Only PDF or Word docs allowed."); }
            if ($_FILES["cv"]["size"] > 5000000) { throw new Exception("CV file size must be less than 5MB."); }
            if (!move_uploaded_file($_FILES["cv"]["tmp_name"], $cvPath)) { throw new Exception("Failed to upload CV."); }
        }

        // Handle Supporting Document
        $supportDocPath = "";
        if (!empty($_FILES["support_doc"]["name"])) {
            $docName = time() . "_doc_" . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES["support_doc"]["name"]));
            $supportDocPath = $target_dir_docs . $docName;
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES["support_doc"]["tmp_name"]);
            finfo_close($finfo);
            
            if (!in_array($mime, $allowed_mimes_docs)) { throw new Exception("Invalid supporting document. Only PDF, Word, or PPT allowed."); }
            if ($_FILES["support_doc"]["size"] > 10000000) { throw new Exception("Supporting document size must be less than 10MB."); }
            if (!move_uploaded_file($_FILES["support_doc"]["tmp_name"], $supportDocPath)) { throw new Exception("Failed to upload Supporting Document."); }
        }

        // 5. Insert into Database securely (25 Parameters)
        $sql = "INSERT INTO $table_name (nomination_type, nominee_name, email, phone, department, designation, police_station, city, state, country, rep_name, rep_designation, rep_email, rep_phone, award_category, other_category, experience_years, current_work_area, jurisdiction, summary, linkedin_url, website_url, supporting_links, cv_path, support_doc_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) { throw new Exception("Database Error: " . $conn->error); }

        $stmt->bind_param("sssssssssssssssssssssssss", $nomination_type, $nominee_name, $email, $phone, $department, $designation, $police_station, $city, $state, $country, $rep_name, $rep_designation, $rep_email, $rep_phone, $award_category, $other_category, $experience_years, $current_work_area, $jurisdiction, $summary, $linkedin_url, $website_url, $supporting_links, $cvPath, $supportDocPath);
        
        if ($stmt->execute()) {
            $showSuccessModal = true;
            $successName = $nominee_name;
            $_POST = array(); // Clear form values
        } else {
            throw new Exception("Failed to submit nomination.");
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
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="author" content="FCRF Academy">

<title>Cyber Policing & Law Enforcement Awards | FCRF Academy</title>

<meta name="description" content="Apply for Cyber Policing & Law Enforcement Awards. Nominate yourself, another officer, a police unit, or a law enforcement organisation for the FCRF Excellence Awards 2026." />

<link rel="icon" href="/favicon.ico" type="image/x-icon">
<link rel="shortcut icon" href="assets/img/logo/favs.jpeg">   
<script src="https://unpkg.com/lucide@latest"></script>    
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@1,500;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    
    <style>
        /* ========================================== */
        /* GLOBAL VARIABLES & BODY SETTINGS           */
        /* ========================================== */
        :root {
            --primary: #0ea5e9; 
            --secondary: #10b981; 
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
            background-color: #ffffff;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0 0 4rem 0;
        }

        /* ========================================== */
        /* ISOLATED PROGRAM & STALWARTS MODULE        */
        /* ========================================== */
        .isolated-program-module {
            --bg-dark: #0a0a0a;
            --bg-card-dark: #121214;
            --text-white: #ffffff;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --module-gradient: linear-gradient(93deg, #5135FF 10.65%, #FF5455 89.35%);

            font-family: 'Inter', sans-serif;
            background-color: #ffffff;
            color: var(--gray-900);
            display: flex; flex-direction: column; align-items: center;
            width: 100%; margin-bottom: 3rem;
        }

        .isolated-program-module a { text-decoration: none; }
        .isolated-program-module .font-serif-italic { font-family: 'Playfair Display', serif; font-style: italic; font-weight: 500; }

        .isolated-program-module .main-header { width: 100%; max-width: 1400px; padding: 2.5rem 1.5rem 0; display: flex; justify-content: center; align-items: center; }
        .isolated-program-module .logo-link { display: inline-block; transition: opacity 0.3s ease; }
        .isolated-program-module .logo-link:hover { opacity: 0.8; }
        .isolated-program-module .brand-logo { height: auto; max-height: 80px; width: auto; max-width: 100%; object-fit: contain; display: block; }

        .isolated-program-module .awards-info-section { width: 100%; max-width: 1200px; padding: 4rem 1.5rem 3rem; display: flex; flex-direction: column; align-items: center; text-align: center; margin: 0 auto; }
        .isolated-program-module .awards-badge { display: inline-block; padding: 0.5rem 1.25rem; background: rgba(0, 0, 0, 0.04); border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 9999px; font-size: 0.85rem; font-weight: 600; color: var(--gray-700); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 2rem; }
        .isolated-program-module .awards-title-block { display: flex; flex-direction: column; align-items: center; margin-bottom: 2.5rem; width: 100%; }
        
        .isolated-program-module .awards-headline { font-size: 4rem; font-weight: 600; line-height: 1.1; margin-bottom: 1.5rem; letter-spacing: -0.025em; text-align: center; color: var(--gray-900); }
        .isolated-program-module .awards-headline strong { background: var(--module-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 700; }
        .isolated-program-module .awards-subheadline { font-size: 1.35rem; font-weight: 400; color: var(--gray-700); line-height: 1.5; margin: 0 auto; max-width: 800px; }
        .isolated-program-module .awards-desc-block { display: flex; flex-direction: column; justify-content: center; align-items: center; width: 100%; margin-top: 2rem; }
        .isolated-program-module .awards-intro-text { font-size: 1.15rem; color: var(--gray-600); line-height: 1.8; max-width: 900px; margin-bottom: 3rem; }
        
        .isolated-program-module .awards-features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; width: 100%; }
        .isolated-program-module .feature-card { background-color: #ffffff; border: 1px solid rgba(0, 0, 0, 0.06); border-radius: 1.25rem; padding: 2.5rem 2rem; text-align: left; display: flex; flex-direction: column; gap: 1.25rem; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05); transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .isolated-program-module .feature-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1); }
        .isolated-program-module .feature-icon-wrapper { width: 54px; height: 54px; border-radius: 1rem; background: rgba(81, 53, 255, 0.08); display: flex; align-items: center; justify-content: center; }
        .isolated-program-module .feature-icon-wrapper svg { width: 28px; height: 28px; color: #5135FF; }
        .isolated-program-module .feature-title { font-size: 1.25rem; font-weight: 700; color: var(--gray-900); letter-spacing: -0.01em; }
        .isolated-program-module .feature-text { font-size: 0.95rem; color: var(--gray-600); line-height: 1.7; }
        .isolated-program-module .text-gradient { background: var(--module-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 700; }

        .isolated-program-module .stalwarts-section { width: 100%; background-color: transparent; color: var(--gray-900); display: flex; justify-content: center; }
        .isolated-program-module .stalwarts-container { width: 100%; max-width: 1400px; padding: 4rem 1.5rem; overflow: hidden; }
        .isolated-program-module .jury-headline { font-size: 3rem; font-weight: 600; text-align: center; margin-bottom: 3rem; letter-spacing: -0.02em; color: var(--gray-900); }
        .isolated-program-module .carousel-wrapper { position: relative; width: 100%; overflow: hidden; }
        .isolated-program-module .carousel-container { display: flex; gap: 1.5rem; overflow-x: auto; width: 100%; padding: 1rem 0; cursor: grab; user-select: none; scrollbar-width: none; }
        .isolated-program-module .carousel-container::-webkit-scrollbar { display: none; }
        
        .isolated-program-module .card { flex-shrink: 0; width: 280px; border-radius: 1rem; overflow: hidden; display: flex; flex-direction: column; background-color: #ffffff; border: 1px solid rgba(0, 0, 0, 0.08); box-shadow: 0 10px 30px -10px rgba(0,0,0,0.08); }
        .isolated-program-module .card-img-container { height: 12rem; width: 100%; }
        .isolated-program-module .card-img-container img { width: 100%; height: 100%; object-fit: cover; pointer-events: none; }
        .isolated-program-module .card-info { padding: 1.25rem; display: flex; flex-direction: column; flex-grow: 1; }
        .isolated-program-module .card-name { font-weight: 700; color: var(--gray-900); font-size: 1.125rem; }
        .isolated-program-module .card-role { font-size: 12px; color: var(--gray-600); margin-top: 4px; }
        .isolated-program-module .card-company { margin-top: 1rem; margin-bottom: 0.5rem; font-weight: 900; color: var(--gray-800); font-size: 14px; text-transform: uppercase; letter-spacing: 0.1em; }
        .isolated-program-module .card-footer { margin-top: auto; padding-top: 1rem; border-top: 1px solid var(--gray-200); }
        .isolated-program-module .card-topic { font-size: 12px; color: var(--gray-600); font-style: italic; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

        @media (max-width: 1024px) { .isolated-program-module .awards-features-grid { gap: 1.5rem; } }
        @media (max-width: 768px) {
            .isolated-program-module .main-header { padding: 1.5rem 1.5rem 0; }
            .isolated-program-module .brand-logo { max-height: 55px; }
            .isolated-program-module .awards-info-section { padding: 3rem 1.5rem 2rem; }
            .isolated-program-module .awards-headline { font-size: 2.75rem; }
            .isolated-program-module .awards-subheadline { font-size: 1.15rem; }
            .isolated-program-module .awards-features-grid { gap: 1rem; }
            .isolated-program-module .feature-card { padding: 1.5rem 1rem; gap: 1rem; }
            .isolated-program-module .feature-icon-wrapper { width: 44px; height: 44px; }
            .isolated-program-module .stalwarts-container { padding: 3rem 1.5rem; }
            .isolated-program-module .jury-headline { font-size: 2.5rem; margin-bottom: 2rem; }
        }
        @media (max-width: 640px) {
            .isolated-program-module .awards-headline { font-size: 2.25rem; }
            .isolated-program-module .awards-intro-text { font-size: 1rem; }
            .isolated-program-module .awards-features-grid { grid-template-columns: 1fr; gap: 0.5rem; }
            .isolated-program-module .feature-card { padding: 1rem 0.5rem; gap: 0.75rem; border-radius: 1rem; }
            .isolated-program-module .feature-icon-wrapper { width: 32px; height: 32px; border-radius: 0.75rem; }
            .isolated-program-module .feature-title { font-size: 0.85rem; }
            .isolated-program-module .feature-text { font-size: 0.7rem; line-height: 1.4; }
            .isolated-program-module .card { width: 240px; }
        }

        /* ========================================== */
        /* ORIGINAL FORM STYLES (PRESERVED)           */
        /* ========================================== */
        .form-container {
            background: var(--bg-card); width: 100%; max-width: 950px; border-radius: 24px;
            box-shadow: 0 15px 40px -10px rgba(0,0,0,0.15); overflow: hidden; position: relative; margin: 0 1rem;
        }

        .banner { background: var(--bg-dark); padding: 4rem 3rem; position: relative; overflow: hidden; border-bottom: 4px solid var(--primary); }
        .banner::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background-image: linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px);
            background-size: 30px 30px; opacity: 0.5;
        }
        .banner-content { position: relative; z-index: 1; }
        .banner-logo { max-width: 280px; height: auto; margin-bottom: 1.5rem; display: block; }
        .banner h1 { color: white; font-size: 2.5rem; font-weight: 800; line-height: 1.2; margin-bottom: 1rem; font-family: 'JetBrains Mono', monospace; }
        .banner h1 span { color: var(--primary); }
        .banner p { color: #94a3b8; font-size: 1.1rem; line-height: 1.6; max-width: 800px; }

        .form-body { padding: 3rem; }
        .section-title { display: flex; align-items: center; gap: 10px; font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin: 2.5rem 0 1.5rem; padding-bottom: 0.75rem; border-bottom: 2px solid #f1f5f9; }
        .section-title i { color: var(--primary); }
        .section-title:first-child { margin-top: 0; }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; font-weight: 600; font-size: 0.95rem; margin-bottom: 0.5rem; color: #334155; }
        .optional-tag { font-weight: 400; color: #94a3b8; font-size: 0.8rem; display: block; margin-top: 4px; }
        .inline-optional { font-weight: 400; color: #94a3b8; font-size: 0.8rem; }

        .input-wrapper { position: relative; display: flex; align-items: center; }
        .input-wrapper i { position: absolute; left: 14px; color: #94a3b8; transition: color 0.3s ease; pointer-events: none; }

        input[type="text"], input[type="email"], input[type="tel"], input[type="number"], input[type="url"], select, textarea {
            width: 100%; padding: 14px 16px 14px 44px; border-radius: 12px; border: 1px solid var(--border-color);
            background: #f8fafc; font-family: inherit; font-size: 1rem; color: var(--text-main); transition: all 0.3s ease;
        }
        select, textarea { padding-left: 16px; } 
        .input-wrapper select { padding-left: 44px; }
        textarea { resize: vertical; min-height: 120px; }

        input:focus, select:focus, textarea:focus { outline: none; border-color: var(--primary); background: white; box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1); }
        .input-wrapper input:focus + i, .input-wrapper select:focus + i { color: var(--primary); }

        .radio-group { display: flex; flex-direction: column; gap: 12px; background: #f8fafc; padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border-color); }
        .radio-label { display: flex; align-items: center; gap: 12px; font-size: 0.95rem; color: #475569; cursor: pointer; padding: 8px; border-radius: 8px; transition: background 0.2s; }
        .radio-label:hover { background: white; }
        .radio-label input[type="radio"], .radio-label input[type="checkbox"] { accent-color: var(--primary); width: 1.2rem; height: 1.2rem; cursor: pointer; }

        .checkbox-grid { display: grid; grid-template-columns: 1fr; gap: 12px; background: #f8fafc; padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border-color); }
        .checkbox-label { display: flex; align-items: flex-start; gap: 12px; font-size: 0.95rem; line-height: 1.4; color: #475569; cursor: pointer; padding: 12px; border-radius: 10px; transition: background 0.2s; }
        .checkbox-label:hover { background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .checkbox-label input[type="checkbox"] { margin-top: 3px; accent-color: var(--primary); width: 1.2rem; height: 1.2rem; flex-shrink: 0; cursor: pointer; }

        .rep-details-section { display: none; background: #f1f5f9; padding: 1.5rem; border-radius: 16px; margin-top: 1.5rem; border-left: 4px solid var(--primary); animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        .upload-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .upload-area { border: 2px dashed var(--border-color); border-radius: 16px; padding: 2rem; text-align: center; background: #f8fafc; cursor: pointer; transition: all 0.3s; position: relative; display: block; height: 100%; }
        .upload-area:hover, .upload-area.dragover { border-color: var(--primary); background: #f0f9ff; }
        .upload-area i { color: var(--text-muted); margin-bottom: 10px; transition: color 0.3s; }
        .upload-area:hover i { color: var(--primary); }
        .upload-area.has-error { border-color: var(--error); background: #fef2f2; }
        .file-name { margin-top: 10px; font-size: 0.9rem; color: var(--secondary); font-weight: 600; display: none; word-break: break-all; }

        .error-box { background: #fee2e2; border-left: 4px solid var(--error); color: #b91c1c; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; font-weight: 500; display: flex; align-items: center; gap: 8px; }
        .submit-btn { width: 100%; background: var(--gradient); color: white; border: none; padding: 1.2rem; border-radius: 12px; font-size: 1.1rem; font-weight: 700; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; margin-top: 2rem; display: flex; justify-content: center; align-items: center; gap: 10px; }
        .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(14, 165, 233, 0.3); }
        .captcha-wrap { display: flex; justify-content: center; margin-top: 2rem; }
        .footer-note { text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border-color); font-size: 0.85rem; color: var(--text-muted); line-height: 1.6; }

        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.85); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal-content { background: white; padding: 3rem; border-radius: 24px; text-align: center; max-width: 450px; width: 90%; box-shadow: 0 0 0 1px rgba(255,255,255,0.1), 0 25px 50px -12px rgba(0,0,0,0.5); animation: scaleIn 0.4s cubic-bezier(0.16, 1, 0.3, 1); position: relative; }
        .icon-success { width: 80px; height: 80px; background: #ecfdf5; color: var(--secondary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; position: relative; }
        .icon-success::before { content: ''; position: absolute; inset: -10px; border-radius: 50%; border: 2px solid var(--secondary); animation: pulse 2s infinite; opacity: 0.5; }
        .countdown-text { font-size: 0.9rem; color: #94a3b8; margin-top: 1rem; font-weight: 500; }
        .countdown-text span { color: var(--primary); font-weight: 700; }

        @keyframes scaleIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
        @keyframes pulse { 0% { transform: scale(1); opacity: 0.8; } 100% { transform: scale(1.3); opacity: 0; } }

        @media (max-width: 768px) {
            .banner { padding: 3rem 1.5rem; }
            .banner-logo { max-width: 200px; margin-bottom: 1rem; }
            .banner h1 { font-size: 2rem; }
            .form-body { padding: 2rem 1.5rem; }
            .grid-2, .upload-grid { grid-template-columns: 1fr; gap: 0; }
            .upload-grid { gap: 1.5rem; }
        }
    </style>
</head>
<body>

    <div class="modal-overlay <?php echo $showSuccessModal ? 'active' : ''; ?>" id="success-modal">
        <div class="modal-content">
            <div class="icon-success"><i data-lucide="award" size="40"></i></div>
            <h2 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 10px; color: var(--text-main);">Nomination Received!</h2>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem; line-height: 1.5;">
                Thank you for your nomination, <strong style="color:var(--text-main);"><?php echo htmlspecialchars($successName); ?></strong>. We will review this submission shortly.
            </p>
            <button onclick="forceRedirect()" class="submit-btn" style="margin-top:0; padding:1rem; font-size:1rem;">Continue to Summit</button>
            <div class="countdown-text">Auto-redirecting in <span id="timer">4</span> seconds...</div>
        </div>
    </div>

    <div class="isolated-program-module">
        <header class="main-header">
            <a href="https://fcrf.academy" class="logo-link">
                <img src="assets/img/logo/FCRF Excellence.webp" alt="FCRF Academy" class="brand-logo">
            </a>
        </header>

        <section class="awards-info-section">
            <div class="awards-title-block">
                <div class="awards-badge"><span class="text-gradient">FCRF Excellence Awards 2026</span></div>
                <h1 class="awards-headline">
                    Apply for <span class="text-gradient">Cyber Policing &<br>Law Enforcement Awards</span>
                </h1>
                <h3 class="awards-subheadline">
                    Nominate yourself, another officer, a police unit, or a law enforcement organisation.
                </h3>
            </div>
            
            <div class="awards-desc-block">
                <p class="awards-intro-text">
                    The Cyber Policing & Law Enforcement Awards under the <span class="text-gradient">FCRF Excellence Awards 2026</span> recognise outstanding contributions in cyber policing, cybercrime investigation, cyber fraud response, digital evidence handling, cyber forensics, citizen cyber safety, cyber training and law enforcement innovation.
                </p>

                <div class="awards-features-grid">
                    <div class="feature-card">
                        <div class="feature-icon-wrapper">
                            <i data-lucide="users" size="28" style="color: #5135FF;"></i>
                        </div>
                        <h4 class="feature-title">Who Can Apply</h4>
                        <p class="feature-text">You may submit a self-nomination, nominate another officer, or nominate a police station, unit, cyber cell, cybercrime wing, or department.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon-wrapper">
                            <i data-lucide="file-check" size="28" style="color: #5135FF;"></i>
                        </div>
                        <h4 class="feature-title">The Process</h4>
                        <p class="feature-text">All nominations will be reviewed and shortlisted through an evaluation process. Shortlisted nominees may be contacted for additional information.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon-wrapper">
                            <i data-lucide="award" size="28" style="color: #5135FF;"></i>
                        </div>
                        <h4 class="feature-title">The Grand Finale</h4>
                        <p class="feature-text">The awards will be presented at the <span class="text-gradient">Future Crime Summit 2026</span>, on 6th–7th August 2026, at Dr. Ambedkar International Centre, New Delhi.</p>
                    </div>
                </div>
            </div>
        </section>

        <div class="stalwarts-section">
            <section class="stalwarts-container">
                <h2 class="jury-headline">
                    <span class="text-gradient">Esteemed Jury</span>
                </h2>
                <div class="carousel-wrapper" id="carouselWrapper">
                    <div id="carousel" class="carousel-container">
                        </div>
                </div>
            </section>
        </div>
    </div>


    <div class="form-container">
        
        <div class="banner">
            <div class="banner-content">
                <img src="assets/img/logo/FCRF Excellence(landscape).webp" alt="FCRF Academy Logo" class="banner-logo">
                <h1>Cyber Policing & Law Enforcement <span>Awards</span></h1>
                <p>Recognising excellence in cyber policing, investigations, forensics, and capacity building. Submit your nomination details below.</p>
            </div>
        </div>

        <div class="form-body">
            
            <?php if (!empty($message) && $messageType == "error"): ?>
                <div class="error-box">
                    <i data-lucide="alert-triangle" size="20"></i> 
                    <span><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data" id="nominationForm">
                
                <div class="section-title"><i data-lucide="bookmark"></i> 1. Nomination Type</div>
                
                <div class="form-group">
                    <label>Who are you nominating? *</label>
                    <div class="radio-group">
                        <?php $nomType = $_POST['nomination_type'] ?? ''; ?>
                        <label class="radio-label">
                            <input type="radio" name="nomination_type" value="Self-Nomination" required onchange="toggleRepSection()" <?php if($nomType == 'Self-Nomination') echo 'checked'; ?>>
                            <span>Self-Nomination</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="nomination_type" value="Nomination of Another Officer" required onchange="toggleRepSection()" <?php if($nomType == 'Nomination of Another Officer') echo 'checked'; ?>>
                            <span>Nomination of Another Officer</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="nomination_type" value="Nomination of a Police Unit / Cyber Cell / Police Station / Department" required onchange="toggleRepSection()" <?php if($nomType == 'Nomination of a Police Unit / Cyber Cell / Police Station / Department') echo 'checked'; ?>>
                            <span>Nomination of a Police Unit / Cyber Cell / Police Station / Department</span>
                        </label>
                    </div>
                </div>

                <div class="section-title"><i data-lucide="user"></i> 2. Nominee Details</div>
                
                <div class="form-group">
                    <label>Full Name / Unit Name *</label>
                    <div class="input-wrapper">
                        <input type="text" name="nominee_name" required placeholder="Enter the nominee or unit name"
                               value="<?php echo isset($_POST['nominee_name']) ? htmlspecialchars($_POST['nominee_name']) : ''; ?>">
                        <i data-lucide="user-circle" size="18"></i>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Email Address *</label>
                        <div class="input-wrapper">
                            <input type="email" name="email" required placeholder="nominee@example.com"
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            <i data-lucide="mail" size="18"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Phone Number *</label>
                        <div class="input-wrapper">
                            <input type="tel" name="phone" required placeholder="Contact Number"
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                            <i data-lucide="phone" size="18"></i>
                        </div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Department / Organisation *</label>
                        <div class="input-wrapper">
                            <input type="text" name="department" required placeholder="e.g. State Police"
                                   value="<?php echo isset($_POST['department']) ? htmlspecialchars($_POST['department']) : ''; ?>">
                            <i data-lucide="building-2" size="18"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Official Designation / Rank *</label>
                        <div class="input-wrapper">
                            <input type="text" name="designation" required placeholder="e.g. Inspector, DSP"
                                   value="<?php echo isset($_POST['designation']) ? htmlspecialchars($_POST['designation']) : ''; ?>">
                            <i data-lucide="shield" size="18"></i>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Police Station / Unit / Wing Name <span class="inline-optional">(if applicable)</span></label>
                    <div class="input-wrapper">
                        <input type="text" name="police_station" placeholder="e.g. Cyber Crime Cell, District X"
                               value="<?php echo isset($_POST['police_station']) ? htmlspecialchars($_POST['police_station']) : ''; ?>">
                        <i data-lucide="map-pin" size="18"></i>
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
                        <label>State *</label>
                        <div class="input-wrapper">
                            <input type="text" name="state" required placeholder="e.g. Maharashtra"
                                   value="<?php echo isset($_POST['state']) ? htmlspecialchars($_POST['state']) : ''; ?>">
                            <i data-lucide="map" size="18"></i>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Country *</label>
                    <div class="input-wrapper">
                        <input type="text" name="country" required placeholder="e.g. India"
                               value="<?php echo isset($_POST['country']) ? htmlspecialchars($_POST['country']) : ''; ?>">
                        <i data-lucide="globe" size="18"></i>
                    </div>
                </div>

                <div class="rep-details-section" id="repDetailsBlock">
                    <h3 style="font-size: 1rem; color: var(--primary); margin-bottom: 1rem; font-weight: 700;">Nodal Officer / Contact Person Details</h3>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Nodal Officer Name *</label>
                            <input type="text" name="rep_name" id="rep_name" placeholder="Name of point of contact"
                                   value="<?php echo isset($_POST['rep_name']) ? htmlspecialchars($_POST['rep_name']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Designation / Rank *</label>
                            <input type="text" name="rep_designation" id="rep_designation" placeholder="Rank / Title"
                                   value="<?php echo isset($_POST['rep_designation']) ? htmlspecialchars($_POST['rep_designation']) : ''; ?>">
                        </div>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Contact Email *</label>
                            <input type="email" name="rep_email" id="rep_email" placeholder="Email Address"
                                   value="<?php echo isset($_POST['rep_email']) ? htmlspecialchars($_POST['rep_email']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Contact Number *</label>
                            <input type="tel" name="rep_phone" id="rep_phone" placeholder="Phone Number"
                                   value="<?php echo isset($_POST['rep_phone']) ? htmlspecialchars($_POST['rep_phone']) : ''; ?>">
                        </div>
                    </div>
                </div>

                <div class="section-title"><i data-lucide="award"></i> 3. Award Category Selection</div>
                
                <div class="form-group">
                    <label>Select the award category * <span class="optional-tag">(Select all that apply)</span></label>
                    <div class="checkbox-grid">
                        <?php 
                            $categories = [
                                "FCRF Excellence Award in Cyber Policing",
                                "FCRF Excellence Award in Cyber Crime Investigation",
                                "FCRF Excellence Award in State Cybercrime Response",
                                "FCRF Excellence Award in Cyber Forensics",
                                "FCRF Excellence Award in Cyber Intelligence Operations",
                                "FCRF Excellence Award in Social Media Crime Investigation",
                                "FCRF Excellence Award in Cyber Patrol and Monitoring",
                                "FCRF Excellence Award in Digital Policing Innovation",
                                "FCRF Excellence Award in Cyber Helpline Operations",
                                "FCRF Excellence Award in Cyber Lab Development"
                            ];
                            $selected_cat = isset($_POST['award_category']) ? (array)$_POST['award_category'] : [];
                            foreach($categories as $cat) {
                                $checked = in_array($cat, $selected_cat) ? 'checked' : '';
                                echo "<label class='checkbox-label'>
                                        <input type='checkbox' name='award_category[]' value=\"$cat\" $checked>
                                        <span>$cat</span>
                                      </label>";
                            }
                        ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>If nominating for another category, please specify <span class="inline-optional">(Optional)</span></label>
                    <div class="input-wrapper">
                        <input type="text" name="other_category" placeholder="Specify category here..."
                               value="<?php echo isset($_POST['other_category']) ? htmlspecialchars($_POST['other_category']) : ''; ?>">
                        <i data-lucide="help-circle" size="18"></i>
                    </div>
                </div>

                <div class="section-title"><i data-lucide="badge-check"></i> 4. Service / Functional Profile</div>
                
                <div class="grid-2">
                    <div class="form-group">
                        <label>Total Years of Service *</label>
                        <div class="input-wrapper">
                            <input type="number" name="experience_years" required min="0" placeholder="e.g. 10"
                                   value="<?php echo isset($_POST['experience_years']) ? htmlspecialchars($_POST['experience_years']) : ''; ?>">
                            <i data-lucide="clock" size="18"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Current Area of Work *</label>
                        <div class="input-wrapper">
                            <select name="current_work_area" required>
                                <option value="" disabled selected>Select Area of Work</option>
                                <?php 
                                    $work_areas = [
                                        "Cybercrime Investigation",
                                        "Cyber Fraud Response",
                                        "Digital Evidence Handling",
                                        "Cyber Forensics",
                                        "OSINT / Intelligence",
                                        "Citizen Awareness / Cyber Safety",
                                        "Cyber Training",
                                        "Cyber Police Station Operations",
                                        "State Cybercrime Coordination",
                                        "Technology-led Policing Innovation",
                                        "Other"
                                    ];
                                    $selected_wa = $_POST['current_work_area'] ?? '';
                                    foreach($work_areas as $wa) {
                                        $sel = ($wa == $selected_wa) ? 'selected' : '';
                                        echo "<option value=\"$wa\" $sel>$wa</option>";
                                    }
                                ?>
                            </select>
                            <i data-lucide="briefcase" size="18"></i>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>State / Unit / Jurisdiction *</label>
                    <div class="input-wrapper">
                        <input type="text" name="jurisdiction" required placeholder="e.g. State Cyber Cell, XYZ State"
                               value="<?php echo isset($_POST['jurisdiction']) ? htmlspecialchars($_POST['jurisdiction']) : ''; ?>">
                        <i data-lucide="shield" size="18"></i>
                    </div>
                </div>

                <div class="section-title"><i data-lucide="file-edit"></i> 5. Nomination Summary</div>

                <div class="form-group">
                    <label>Summary of Work / Contribution *</label>
                    <span class="optional-tag mb-2">In 1,000–1,500 words, describe the nominee’s contribution, initiatives, cybercrime cases handled, public impact, innovations, response systems, training efforts, victim support mechanisms, or institutional achievements relevant to the selected award category.</span>
                    <textarea name="summary" required placeholder="Please provide detailed justification for this nomination..."><?php echo isset($_POST['summary']) ? htmlspecialchars($_POST['summary']) : ''; ?></textarea>
                </div>

                <div class="section-title"><i data-lucide="paperclip"></i> 6. Links & Attachments</div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>LinkedIn Profile <span class="inline-optional">(Optional)</span></label>
                        <div class="input-wrapper">
                            <input type="url" name="linkedin_url" placeholder="https://linkedin.com/in/nominee"
                                   value="<?php echo isset($_POST['linkedin_url']) ? htmlspecialchars($_POST['linkedin_url']) : ''; ?>">
                            <i data-lucide="linkedin" size="18"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Official Department / Unit Website <span class="inline-optional">(Optional)</span></label>
                        <div class="input-wrapper">
                            <input type="url" name="website_url" placeholder="https://police.gov.in/..."
                                   value="<?php echo isset($_POST['website_url']) ? htmlspecialchars($_POST['website_url']) : ''; ?>">
                            <i data-lucide="globe" size="18"></i>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Supporting Links <span class="inline-optional">(Optional)</span></label>
                    <div class="input-wrapper">
                        <input type="text" name="supporting_links" placeholder="News articles, press releases, etc."
                               value="<?php echo isset($_POST['supporting_links']) ? htmlspecialchars($_POST['supporting_links']) : ''; ?>">
                        <i data-lucide="link" size="18"></i>
                    </div>
                </div>

                <div class="upload-grid">
                    <div class="form-group">
                        <label>Upload CV / Profile / Service Note <span class="inline-optional">(Optional - PDF/DOC up to 5MB)</span></label>
                        <label class="upload-area" for="cv-input" id="drop-area-cv">
                            <i data-lucide="file-up" size="32"></i>
                            <div style="font-weight: 600; color: #1e293b; font-size:14px;">Browse or drag Document here</div>
                            <input type="file" name="cv" id="cv-input" style="display:none;" accept=".pdf,.doc,.docx">
                            <div id="cv-file-name" class="file-name"></div>
                        </label>
                    </div>

                    <div class="form-group">
                        <label>Upload Note on Work Completed <span class="inline-optional">(Optional - PDF/DOC/PPT up to 10MB)</span></label>
                        <span class="optional-tag mb-2" style="font-size:0.75rem;">This may include an achievement note, citation, case summary, department profile, or capacity-building note.</span>
                        <label class="upload-area" for="doc-input" id="drop-area-doc">
                            <i data-lucide="folder-up" size="32"></i>
                            <div style="font-weight: 600; color: #1e293b; font-size:14px;">Browse or drag Supporting Docs</div>
                            <input type="file" name="support_doc" id="doc-input" style="display:none;" accept=".pdf,.doc,.docx,.ppt,.pptx">
                            <div id="doc-file-name" class="file-name"></div>
                        </label>
                    </div>
                </div>

                <div class="section-title"><i data-lucide="check-square"></i> 7. Declaration</div>
                
                <div class="form-group">
                    <label class="radio-label" style="align-items: flex-start; background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color);">
                        <input type="checkbox" name="declaration" required <?php if(isset($_POST['declaration'])) echo 'checked'; ?>>
                        <span style="font-size: 0.9rem; line-height: 1.5;">I confirm that the information submitted in this nomination is true and accurate to the best of my knowledge. I understand that all nominations will be reviewed through a shortlisting and evaluation process, and shortlisted nominees may be contacted for additional details. *</span>
                    </label>
                </div>

                <div class="captcha-wrap">
                    <div class="g-recaptcha" data-sitekey="<?php echo $recaptcha_site_key; ?>"></div>
                </div>

                <button type="submit" class="submit-btn">
                    Submit Nomination Securely <i data-lucide="lock" size="18"></i>
                </button>
                
                <div class="footer-note">
                    <strong>Shortlisting Note:</strong> All nominations will be reviewed by the FCRF Excellence Awards evaluation team and jury. Shortlisted nominees may be contacted for further details, clarifications, or supporting documents.<br><br>
                    <strong>Event Note:</strong> Selected awardees will be honoured at the Future Crime Summit 2026, taking place on 6th–7th August 2026 at Dr. Ambedkar International Centre, New Delhi.
                </div>

            </form>
        </div>
    </div>

    <script>
        // Initialize Icons
        lucide.createIcons();

        // --- Dynamic Representative Section Logic ---
        function toggleRepSection() {
            const orgRadio = document.querySelector('input[name="nomination_type"][value="Nomination of a Police Unit / Cyber Cell / Police Station / Department"]');
            const repBlock = document.getElementById('repDetailsBlock');
            const repInputs = repBlock.querySelectorAll('input');

            if (orgRadio && orgRadio.checked) {
                repBlock.style.display = 'block';
                repInputs.forEach(input => input.setAttribute('required', 'true'));
            } else {
                repBlock.style.display = 'none';
                repInputs.forEach(input => input.removeAttribute('required'));
            }
        }
        document.addEventListener('DOMContentLoaded', toggleRepSection);

        // --- STALWARTS CAROUSEL LOGIC ---
        const juryData = [
            { name: "Maj Gen Sandeep Sharma", role: "(Retd.)", company: "", topic: "", imgUrl: "assets/img/jury/Maj Gen Sandeep  Sharma (Retd.).webp" },
            { name: "AVM (Dr.) Devesh Vatsa", role: "Advisor", company: "Data Security Council of India", topic: "", imgUrl: "assets/img/jury/Devesh vatsa.webp" },
            { name: "Dr. Vikram Singh", role: "Former DGP, UP & Chancellor", company: "Noida International University", topic: "", imgUrl: "assets/img/jury/Vikram singh.webp" },
            { name: "Arun Kumar", role: "Former DG", company: "Railway Protection Force (RPF)", topic: "", imgUrl: "assets/img/jury/Arun kumar.webp" },
            { name: "Dr. Gulshan Rai", role: "Former DG", company: "CERT-In", topic: "", imgUrl: "assets/img/jury/Gulshan rai.webp" },
            { name: "Dr. Pavan Duggal", role: "Advocate", company: "Supreme Court of India", topic: "", imgUrl: "assets/img/jury/Pavan Duggal.webp" }
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
                </div>
            `;
            return div;
        }

        function renderCards() {
            juryData.forEach(item => carousel.appendChild(createCard(item)));
            juryData.forEach(item => carousel.appendChild(createCard(item))); 
        }
        renderCards();

        // Momentum Scrolling Logic
        let isDown = false, startX, scrollLeft, floatScroll = 0, velocity = 0, prevX = 0, momentumID, isAutoScrolling = true;
        const autoScrollSpeed = 0.3, friction = 0.96;

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
                    isAutoScrolling = true; floatScroll = carousel.scrollLeft;
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
            prevX = currentX; floatScroll = carousel.scrollLeft;
        };

        carousel.addEventListener('mousedown', handleDown);
        carousel.addEventListener('touchstart', handleDown);
        carousel.addEventListener('mouseleave', handleUpOrLeave);
        carousel.addEventListener('mouseup', handleUpOrLeave);
        carousel.addEventListener('touchend', handleUpOrLeave);
        carousel.addEventListener('mousemove', (e) => { e.preventDefault(); handleMove(e); });
        carousel.addEventListener('touchmove', handleMove);


        // --- File Upload Visual Feedback & Client-Side Validation ---
        function setupFileUpload(inputId, fileNameId, dropAreaId) {
            const fileInput = document.getElementById(inputId);
            const fileNameDiv = document.getElementById(fileNameId);
            const dropArea = document.getElementById(dropAreaId);

            const maxSize = inputId === 'cv-input' ? 5 * 1024 * 1024 : 10 * 1024 * 1024;
            const allowedTypes = [
                'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'
            ];
            const allowedExts = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];

            function handleFileValidation(file) {
                dropArea.classList.remove('has-error');
                if (!file) return;

                const ext = file.name.split('.').pop().toLowerCase();

                if (!allowedExts.includes(ext) && !allowedTypes.includes(file.type)) {
                    alert("Invalid file type. Please upload a PDF, Word, or PPT document.");
                    fileInput.value = ''; fileNameDiv.style.display = 'none';
                    dropArea.classList.add('has-error'); return;
                }

                if (file.size > maxSize) {
                    alert(`File is too large. Maximum size allowed is ${maxSize / (1024 * 1024)}MB.`);
                    fileInput.value = ''; fileNameDiv.style.display = 'none';
                    dropArea.classList.add('has-error'); return;
                }

                fileNameDiv.textContent = '✓ Selected: ' + file.name;
                fileNameDiv.style.display = 'block'; dropArea.style.borderColor = "var(--secondary)";
            }

            fileInput.addEventListener('change', function(e) {
                if (e.target.files.length > 0) { handleFileValidation(e.target.files[0]); } 
                else { fileNameDiv.style.display = 'none'; dropArea.style.borderColor = "var(--border-color)"; dropArea.classList.remove('has-error'); }
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

        setupFileUpload('cv-input', 'cv-file-name', 'drop-area-cv');
        setupFileUpload('doc-input', 'doc-file-name', 'drop-area-doc');

        // --- Redirection Logic with Countdown ---
        function forceRedirect() { window.location.href = "https://summit.futurecrime.org"; }

        <?php if ($showSuccessModal): ?>
        let timeLeft = 4;
        const timerElement = document.getElementById('timer');
        const countdown = setInterval(function() {
            timeLeft--;
            if (timerElement) timerElement.textContent = timeLeft;
            if (timeLeft <= 0) { clearInterval(countdown); forceRedirect(); }
        }, 1000);
        <?php endif; ?>
    </script>
</body>
</html>
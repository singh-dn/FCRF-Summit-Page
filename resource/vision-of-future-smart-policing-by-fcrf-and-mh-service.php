<?php
session_start();

// Production Settings
error_reporting(0);
ini_set('display_errors', 0);

$message = "";
$messageType = "";

// ================= CONFIGURATION ================= //
$db_host    = "localhost";
$db_user    = "u545411682_summit";
$db_pass    = "Summit2026";
$db_name    = "u545411682_summit";
$table_name = "fcrf_document_downloads";

// The document now lives in the database (uploaded via admin-upload.php)
$DOC_SLUG      = 'white-paper-v1';
$DOWNLOAD_NAME = 'WHITE_PAPER_FCRF_V1.pdf'; // clean name the user receives
$LINK_LIFETIME = 300; // seconds the one-time download link stays valid
$RESET_SECONDS = 8;   // seconds on the success screen before the form resets

// ============================================================
// 1. DOWNLOAD HANDLER — must run before ANY output is sent
// ============================================================
if (isset($_GET['download'])) {

    $token = isset($_GET['token']) ? $_GET['token'] : '';

    $tokenOk = !empty($_SESSION['dl_token'])
            && is_string($token)
            && hash_equals($_SESSION['dl_token'], $token)
            && isset($_SESSION['dl_expires'])
            && time() < $_SESSION['dl_expires'];

    if (!$tokenOk) {
        http_response_code(403);
        exit('This download link has expired. Please fill the form again.');
    }

    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        http_response_code(503);
        exit('The document service is unavailable. Please try again shortly.');
    }

    $stmt = $conn->prepare("SELECT mime_type, file_data FROM fcrf_documents WHERE slug = ? LIMIT 1");
    $stmt->bind_param('s', $DOC_SLUG);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $stmt->close();
        $conn->close();
        http_response_code(404);
        exit('The document has not been published yet. Please contact the summit team.');
    }

    $stmt->bind_result($mimeType, $fileData);
    $stmt->fetch();
    $stmt->close();
    $conn->close();

    // Clear any buffers so nothing corrupts the PDF bytes
    while (ob_get_level() > 0) { ob_end_clean(); }

    header('Content-Description: File Transfer');
    header('Content-Type: ' . ($mimeType ?: 'application/pdf'));
    header('Content-Disposition: attachment; filename="' . $DOWNLOAD_NAME . '"');
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . strlen($fileData));
    header('Accept-Ranges: none');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    header('X-Content-Type-Options: nosniff');

    echo $fileData;
    exit;
}

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

// Current page URL without any query string (used for redirects)
$selfUrl = strtok($_SERVER['REQUEST_URI'], '?');

// ============================================================
// 2. FORM SUBMISSION — Post / Redirect / Get
// ============================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    try {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($conn->connect_error) {
            throw new Exception("Database connection failed. Please try again in a moment.");
        }
        $conn->set_charset("utf8mb4");

        $full_name   = sanitize_input($_POST['full_name']   ?? '');
        $email       = sanitize_input($_POST['email']       ?? '');
        $mobile      = sanitize_input($_POST['mobile']      ?? '');
        $city        = sanitize_input($_POST['city']        ?? '');
        $org         = sanitize_input($_POST['org']         ?? '');
        $designation = sanitize_input($_POST['designation'] ?? '');

        // --- STRICT VALIDATION LOGIC ---
        if (empty($full_name) || empty($email) || empty($mobile) || empty($city) || empty($org) || empty($designation)) {
            throw new Exception("All fields are mandatory to download the document.");
        }

        if (!preg_match("/^[a-zA-Z\s\.]+$/", $full_name)) {
            throw new Exception("Name can only contain letters and spaces.");
        }

        if (!preg_match("/^[0-9\+\-\s]+$/", $mobile) || strlen(preg_replace('/[^0-9]/', '', $mobile)) < 10) {
            throw new Exception("Enter a valid mobile number with at least 10 digits.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Enter a valid email address.");
        }

        // Insert into Database Securely
        $sql  = "INSERT INTO $table_name (full_name, email, mobile, city, organization, designation) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) { throw new Exception("Database error. Please try again."); }

        $stmt->bind_param("ssssss", $full_name, $email, $mobile, $city, $org, $designation);

        if (!$stmt->execute()) {
            throw new Exception("We couldn't process your request. Please try again.");
        }

        $stmt->close();
        $conn->close();

        // Issue a short-lived download token, then redirect (stops duplicate
        // rows on refresh and gives a clean URL for the success screen)
        $_SESSION['dl_token']   = bin2hex(random_bytes(16));
        $_SESSION['dl_expires'] = time() + $LINK_LIFETIME;

        header('Location: ' . $selfUrl . '?status=ready');
        exit;

    } catch (Exception $e) {
        $message     = $e->getMessage();
        $messageType = "error";
    }
}

// ============================================================
// 3. SUCCESS SCREEN STATE
// ============================================================
$showSuccess = isset($_GET['status'])
            && $_GET['status'] === 'ready'
            && !empty($_SESSION['dl_token'])
            && isset($_SESSION['dl_expires'])
            && time() < $_SESSION['dl_expires'];

$downloadUrl = $showSuccess
    ? htmlspecialchars($selfUrl . '?download=1&token=' . $_SESSION['dl_token'], ENT_QUOTES, 'UTF-8')
    : '';

// Is a document actually published? (only checked for the success screen copy)
$docMissing = false;
if ($showSuccess) {
    $checkConn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($checkConn->connect_error) {
        $docMissing = true;
    } else {
        $checkStmt = $checkConn->prepare("SELECT id FROM fcrf_documents WHERE slug = ? LIMIT 1");
        $checkStmt->bind_param('s', $DOC_SLUG);
        $checkStmt->execute();
        $checkStmt->store_result();
        $docMissing = ($checkStmt->num_rows === 0);
        $checkStmt->close();
        $checkConn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>VISION OF FUTURE SMART POLICING · FutureCrime Summit 2026</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Fira+Code:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="shortcut icon" href="https://summit.futurecrime.org/assets/img/logo/favs.jpeg">
<style>
:root {
  --bg-base: #f8fafc;
  --text-main: #0f172a;
  --text-muted: #64748b;
  --border-light: rgba(0, 0, 0, 0.1);
  --accent-cyan: #0ea5e9;
  --accent-cyan-dim: rgba(14, 165, 233, 0.2);
  --accent-purple: #5135FF;
  --accent-magenta: #FF5455;
  --grad-brand: linear-gradient(93deg, var(--accent-purple) 10.65%, var(--accent-magenta) 89.35%);
  --sans: 'Outfit', system-ui, -apple-system, sans-serif;
}

*, *::before, *::after { box-sizing: border-box; }

body {
  margin: 0;
  background-color: #a2c2e8;
  color: var(--text-main);
  font-family: var(--sans);
  font-size: 16px;
  line-height: 1.6;
  -webkit-font-smoothing: antialiased;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
}

.ambient-bg {
  position: fixed;
  inset: 0;
  z-index: -2;
  pointer-events: none;
  background-color: #7db9e8;
  background-image:
    radial-gradient(circle at 10% 95%, #0d47a1 0%, transparent 50%),
    radial-gradient(circle at 90% 90%, #64b5f6 0%, transparent 60%),
    radial-gradient(circle at 0% 0%, #ffffff 0%, transparent 60%),
    radial-gradient(circle at 90% 10%, #e3f2fd 0%, transparent 60%);
}

.form-container {
  background: #ffffff;
  width: 100%;
  max-width: 680px;
  border-radius: 32px;
  box-shadow:
    0 40px 100px -20px rgba(0, 50, 150, 0.25),
    0 1px 3px rgba(0, 0, 0, 0.05),
    inset 0 1px 1px rgba(255, 255, 255, 0.8);
  overflow: hidden;
  position: relative;
  z-index: 1;
}

.branding { padding: 32px 40px 20px; background: #ffffff; }

.logo-space { display: block; max-width: 200px; height: auto; margin-bottom: 24px; }

.logo-placeholder {
  font-size: 24px;
  font-weight: 800;
  letter-spacing: -0.03em;
  color: var(--text-main);
  display: flex;
  align-items: center;
  gap: 4px;
}
.logo-placeholder .red { color: var(--accent-magenta); }

.banner-space {
  width: 100%;
  height: auto;
  border-radius: 16px;
  display: block;
  object-fit: cover;
  background: #f1f5f9;
}

.form-body { padding: 32px 40px 48px; }

.form-title {
  margin: 0 0 8px 0;
  font-size: 28px;
  font-weight: 700;
  letter-spacing: -0.02em;
}
.form-desc { margin: 0 0 32px 0; color: var(--text-muted); font-size: 15px; }

.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media (max-width: 600px) { .grid-2 { grid-template-columns: 1fr; } }

.field { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
.field label { font-size: 14px; font-weight: 600; color: var(--text-main); }
.field label span { color: var(--accent-magenta); margin-left: 2px; }

.field input {
  padding: 14px 16px;
  border-radius: 12px;
  border: 1px solid var(--border-light);
  background: #f8fafc;
  font-family: var(--sans);
  font-size: 15px;
  color: var(--text-main);
  transition: all .2s;
  width: 100%;
}
.field input:focus {
  outline: none;
  border-color: var(--accent-cyan);
  background: #ffffff;
  box-shadow: 0 0 0 3px var(--accent-cyan-dim);
}
.field input::placeholder { color: #94a3b8; }

.error-msg {
  background-color: #fef2f2;
  border: 1px solid #fca5a5;
  color: #b91c1c;
  padding: 14px 16px;
  border-radius: 12px;
  margin-bottom: 24px;
  font-size: 14px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 10px;
  text-align: left;
}
.error-msg svg { width: 20px; height: 20px; flex-shrink: 0; }

.btn-submit {
  width: 100%;
  padding: 16px;
  margin-top: 12px;
  border-radius: 14px;
  border: none;
  background: var(--grad-brand);
  color: #ffffff;
  font-family: var(--sans);
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  transition: transform 0.2s, box-shadow 0.2s, opacity 0.2s;
  box-shadow: 0 10px 20px -10px var(--accent-purple);
  text-decoration: none;
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 15px 25px -10px var(--accent-purple);
}

.btn-submit:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
  filter: grayscale(100%);
}

.btn-submit svg { width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

.success-msg { text-align: center; padding: 40px 20px; animation: fadeIn 0.4s ease; }

.success-icon {
  width: 64px; height: 64px;
  background: rgba(16, 172, 132, 0.1);
  color: #10AC84;
  border-radius: 50%;
  display: inline-grid; place-items: center;
  margin-bottom: 20px;
}
.success-icon svg { width: 32px; height: 32px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

.reset-note { margin-top: 20px; font-size: 13px; color: var(--text-muted); }
.manual-link { color: var(--accent-purple); font-weight: 600; text-decoration: none; border-bottom: 1px solid currentColor; }

.hidden-frame { display: none; }

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}
@media (prefers-reduced-motion: reduce) {
  * { animation: none !important; transition: none !important; }
}
</style>
</head>
<body>

<div class="ambient-bg" aria-hidden="true"></div>

<main class="form-container">

  <!-- Branding Header -->
  <div class="branding">
    <img src="../agenda/assets/summit.png" alt="FutureCrime Summit" class="logo-space" onerror="this.outerHTML='<div class=\'logo-placeholder\'>FUTURE<span class=\'red\'>CRIME</span></div>'">
    <img src="../agenda/assets/1600x520.webp" alt="Summit Venue Banner" class="banner-space" onerror="this.src='https://placehold.co/1200x400/f1f5f9/64748b?text=Venue+Banner+Image'">
  </div>

  <div class="form-body">

  <?php if ($showSuccess): ?>

    <!-- ================= SUCCESS SCREEN ================= -->
    <div class="success-msg" id="success-msg">
      <div class="success-icon">
        <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
      </div>

      <?php if ($docMissing): ?>
        <h2 class="form-title">Details received</h2>
        <p class="form-desc">The document isn't published yet. Write to the summit team and we'll send it across.</p>
        <a class="btn-submit" style="background:var(--bg-base); color:var(--text-main); box-shadow:none; border:1px solid var(--border-light);"
           href="<?php echo htmlspecialchars($selfUrl, ENT_QUOTES, 'UTF-8'); ?>">Back to form</a>
      <?php else: ?>
        <h2 class="form-title">Download started</h2>
        <p class="form-desc">
          Your copy of the white paper is on its way to your downloads folder.<br>
          If nothing happened, <a class="manual-link" href="<?php echo $downloadUrl; ?>">download it here</a>.
        </p>

        <a class="btn-submit" style="background:var(--bg-base); color:var(--text-main); box-shadow:none; border:1px solid var(--border-light);"
           href="<?php echo htmlspecialchars($selfUrl, ENT_QUOTES, 'UTF-8'); ?>">
          Register another person
        </a>

        <p class="reset-note">Form resets in <span id="countdown"><?php echo (int)$RESET_SECONDS; ?></span>s for the next entry.</p>

        <!-- The download is fetched in a hidden frame so the page stays put -->
        <iframe class="hidden-frame" id="dl-frame" title="Download" src="about:blank"></iframe>
      <?php endif; ?>
    </div>

    <script>
    (function () {
      var frame = document.getElementById('dl-frame');
      if (!frame) return;

      var downloadUrl = <?php echo json_encode($selfUrl . '?download=1&token=' . $_SESSION['dl_token']); ?>;
      var resetUrl    = <?php echo json_encode($selfUrl); ?>;
      var seconds     = <?php echo (int)$RESET_SECONDS; ?>;

      // Kick the download off after a short beat so the success state is seen
      setTimeout(function () { frame.src = downloadUrl; }, 800);

      var label = document.getElementById('countdown');
      var timer = setInterval(function () {
        seconds -= 1;
        if (label) label.textContent = seconds > 0 ? seconds : 0;
        if (seconds <= 0) {
          clearInterval(timer);
          // replace() keeps the success page out of the back-button history
          window.location.replace(resetUrl);
        }
      }, 1000);
    })();
    </script>

  <?php else: ?>

    <!-- ================= FORM SCREEN ================= -->
    <h1 class="form-title">Download the White Paper</h1>
    <p class="form-desc">Fill in your details below to download “Vision of Future Smart Policing in the Era of Advanced Technology,” a joint white paper by the Future Crime Research Foundation (FCRF) and MH Service.</p>

    <?php if (!empty($message) && $messageType === "error"): ?>
      <div class="error-msg">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <?php echo $message; ?>
      </div>
    <?php endif; ?>

    <form id="download-form" action="<?php echo htmlspecialchars($selfUrl, ENT_QUOTES, 'UTF-8'); ?>" method="POST" autocomplete="on">
      <div class="grid-2">
        <div class="field">
          <label for="name">Full name <span>*</span></label>
          <input type="text" id="name" name="full_name" required placeholder="e.g. Aman Bandvi"
                 value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name'], ENT_QUOTES, 'UTF-8') : ''; ?>">
        </div>
        <div class="field">
          <label for="email">Mail ID <span>*</span></label>
          <input type="email" id="email" name="email" required placeholder="name@organization.com"
                 value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : ''; ?>">
        </div>
      </div>

      <div class="grid-2">
        <div class="field">
          <label for="mobile">Mobile number <span>*</span></label>
          <input type="tel" id="mobile" name="mobile" required placeholder="+91 98765 43210"
                 pattern="[0-9\+\-\s]+" oninput="this.value = this.value.replace(/[^0-9\+\-\s]/g, '')"
                 value="<?php echo isset($_POST['mobile']) ? htmlspecialchars($_POST['mobile'], ENT_QUOTES, 'UTF-8') : ''; ?>">
        </div>
        <div class="field">
          <label for="city">City <span>*</span></label>
          <input type="text" id="city" name="city" required placeholder="e.g. New Delhi"
                 value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city'], ENT_QUOTES, 'UTF-8') : ''; ?>">
        </div>
      </div>

      <div class="grid-2">
        <div class="field">
          <label for="org">Organisation <span>*</span></label>
          <input type="text" id="org" name="org" required placeholder="Company or agency name"
                 value="<?php echo isset($_POST['org']) ? htmlspecialchars($_POST['org'], ENT_QUOTES, 'UTF-8') : ''; ?>">
        </div>
        <div class="field">
          <label for="designation">Designation <span>*</span></label>
          <input type="text" id="designation" name="designation" required placeholder="Your job title"
                 value="<?php echo isset($_POST['designation']) ? htmlspecialchars($_POST['designation'], ENT_QUOTES, 'UTF-8') : ''; ?>">
        </div>
      </div>

      <button type="submit" class="btn-submit" id="submit-btn" disabled>
        <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
        Unlock &amp; download
      </button>
    </form>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
      var form = document.getElementById('download-form');
      var btn  = document.getElementById('submit-btn');
      if (!form || !btn) return;
      var inputs = form.querySelectorAll('input[required]');

      function checkValidity() {
        var ok = true;
        inputs.forEach(function (input) {
          if (!input.value.trim()) ok = false;
        });
        btn.disabled = !ok;
      }

      checkValidity();
      inputs.forEach(function (input) {
        input.addEventListener('input', checkValidity);
        input.addEventListener('change', checkValidity);
      });

      form.addEventListener('submit', function () {
        btn.disabled = true;
        btn.lastChild.textContent = ' Processing…';
      });
    });
    </script>

  <?php endif; ?>

  </div>
</main>

</body>
</html>

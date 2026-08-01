<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

// ================= CONFIGURATION ================= //
$db_host = "localhost";
$db_user = "u545411682_summit";
$db_pass = "Summit2026";
$db_name = "u545411682_summit";

// CHANGE THIS before uploading the file to the server.
$ADMIN_PASSWORD = 'ChangeMe@FCRF2026';

// Which document slot this uploader manages
$DOC_SLUG  = 'white-paper-v1';
$DOC_TITLE = 'FCRF White Paper V1';

$notice = '';
$noticeType = '';

// --- Login handling ---
if (isset($_POST['admin_pass'])) {
    if (hash_equals($ADMIN_PASSWORD, $_POST['admin_pass'])) {
        $_SESSION['doc_admin'] = true;
    } else {
        $notice = 'Wrong password.';
        $noticeType = 'error';
    }
}
if (isset($_GET['logout'])) {
    unset($_SESSION['doc_admin']);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}
$loggedIn = !empty($_SESSION['doc_admin']);

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) { die('Database connection failed.'); }
$conn->set_charset('utf8mb4');

// --- Upload handling ---
if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    try {
        $f = $_FILES['document'];

        if ($f['error'] !== UPLOAD_ERR_OK) {
            $reasons = [
                UPLOAD_ERR_INI_SIZE   => 'File is larger than upload_max_filesize in PHP.',
                UPLOAD_ERR_FORM_SIZE  => 'File is larger than the form limit.',
                UPLOAD_ERR_PARTIAL    => 'Upload was interrupted. Try again.',
                UPLOAD_ERR_NO_FILE    => 'Choose a PDF first.',
                UPLOAD_ERR_NO_TMP_DIR => 'Server has no temp folder configured.',
                UPLOAD_ERR_CANT_WRITE => 'Server could not write the temp file.',
            ];
            throw new Exception($reasons[$f['error']] ?? 'Upload failed.');
        }

        if (!is_uploaded_file($f['tmp_name'])) {
            throw new Exception('Invalid upload.');
        }

        // Verify it really is a PDF, not just named .pdf
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($f['tmp_name']);
        if ($mime !== 'application/pdf') {
            throw new Exception('Only PDF files are accepted (detected: ' . htmlspecialchars($mime) . ').');
        }

        $size = (int)$f['size'];
        if ($size <= 0) { throw new Exception('The file is empty.'); }

        // Warn early if the blob will exceed what MySQL accepts in one statement
        $res = $conn->query("SHOW VARIABLES LIKE 'max_allowed_packet'");
        $row = $res ? $res->fetch_assoc() : null;
        $maxPacket = $row ? (int)$row['Value'] : 0;
        if ($maxPacket > 0 && $size > ($maxPacket - 65536)) {
            throw new Exception('The PDF (' . round($size / 1048576, 2) . ' MB) is bigger than the database packet limit ('
                . round($maxPacket / 1048576, 2) . ' MB). Compress the PDF or raise max_allowed_packet.');
        }

        // Keep a clean download name
        $origName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($f['name']));
        if (strtolower(substr($origName, -4)) !== '.pdf') { $origName .= '.pdf'; }

        $sql = "INSERT INTO fcrf_documents (slug, title, file_name, mime_type, file_size, file_data)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                  title = VALUES(title), file_name = VALUES(file_name),
                  mime_type = VALUES(mime_type), file_size = VALUES(file_size),
                  file_data = VALUES(file_data)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) { throw new Exception('Database error: ' . $conn->error); }

        $null = null;
        $stmt->bind_param('ssssib', $DOC_SLUG, $DOC_TITLE, $origName, $mime, $size, $null);

        // Stream the file to MySQL in chunks instead of loading it all into memory
        $fh = fopen($f['tmp_name'], 'rb');
        while (!feof($fh)) {
            $stmt->send_long_data(5, fread($fh, 262144)); // 256 KB chunks
        }
        fclose($fh);

        if (!$stmt->execute()) { throw new Exception('Save failed: ' . $stmt->error); }
        $stmt->close();

        $notice = 'Stored in the database (' . round($size / 1048576, 2) . ' MB). The download page will now serve this file.';
        $noticeType = 'ok';

    } catch (Exception $e) {
        $notice = $e->getMessage();
        $noticeType = 'error';
    }
}

// --- Current stored document ---
$current = null;
if ($loggedIn) {
    $stmt = $conn->prepare("SELECT title, file_name, file_size, mime_type, uploaded_at FROM fcrf_documents WHERE slug = ?");
    $stmt->bind_param('s', $DOC_SLUG);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Document manager · FutureCrime Summit</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root { --purple:#5135FF; --magenta:#FF5455; --muted:#64748b; --line:rgba(0,0,0,.1); }
  *,*::before,*::after{box-sizing:border-box}
  body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px 20px;
       font-family:'Outfit',system-ui,sans-serif;color:#0f172a;background:#0f172a}
  .card{background:#fff;width:100%;max-width:560px;border-radius:24px;padding:36px;box-shadow:0 30px 80px -20px rgba(0,0,0,.5)}
  h1{margin:0 0 6px;font-size:24px;font-weight:700;letter-spacing:-.02em}
  p.sub{margin:0 0 28px;color:var(--muted);font-size:14px}
  label{display:block;font-size:14px;font-weight:600;margin-bottom:8px}
  input[type=password],input[type=file]{width:100%;padding:13px 14px;border:1px solid var(--line);border-radius:12px;
       background:#f8fafc;font-family:inherit;font-size:15px}
  button{width:100%;margin-top:18px;padding:15px;border:none;border-radius:13px;cursor:pointer;
       background:linear-gradient(93deg,var(--purple) 10%,var(--magenta) 90%);color:#fff;font-family:inherit;font-size:16px;font-weight:700}
  .note{padding:13px 15px;border-radius:12px;font-size:14px;font-weight:500;margin-bottom:22px}
  .note.error{background:#fef2f2;border:1px solid #fca5a5;color:#b91c1c}
  .note.ok{background:#ecfdf5;border:1px solid #6ee7b7;color:#047857}
  .meta{background:#f8fafc;border:1px solid var(--line);border-radius:14px;padding:16px;font-size:14px;margin-bottom:24px}
  .meta div{display:flex;justify-content:space-between;gap:16px;padding:4px 0}
  .meta span{color:var(--muted)}
  .foot{margin-top:22px;font-size:13px;color:var(--muted);text-align:center}
  .foot a{color:var(--purple);font-weight:600}
</style>
</head>
<body>
<main class="card">

<?php if (!$loggedIn): ?>
  <h1>Document manager</h1>
  <p class="sub">Enter the admin password to continue.</p>
  <?php if ($notice): ?><div class="note <?php echo $noticeType; ?>"><?php echo htmlspecialchars($notice); ?></div><?php endif; ?>
  <form method="POST">
    <label for="pw">Password</label>
    <input type="password" id="pw" name="admin_pass" required autofocus>
    <button type="submit">Sign in</button>
  </form>

<?php else: ?>
  <h1>White paper</h1>
  <p class="sub">Upload the PDF here. It is stored in the database and served by the download form.</p>

  <?php if ($notice): ?><div class="note <?php echo $noticeType; ?>"><?php echo htmlspecialchars($notice); ?></div><?php endif; ?>

  <div class="meta">
    <?php if ($current): ?>
      <div><span>File</span><strong><?php echo htmlspecialchars($current['file_name']); ?></strong></div>
      <div><span>Size</span><strong><?php echo round($current['file_size'] / 1048576, 2); ?> MB</strong></div>
      <div><span>Updated</span><strong><?php echo htmlspecialchars($current['uploaded_at']); ?></strong></div>
    <?php else: ?>
      <div><span>Status</span><strong>No document stored yet</strong></div>
    <?php endif; ?>
  </div>

  <form method="POST" enctype="multipart/form-data">
    <label for="doc">Choose PDF <?php echo $current ? '(replaces the current one)' : ''; ?></label>
    <input type="file" id="doc" name="document" accept="application/pdf" required>
    <button type="submit">Upload to database</button>
  </form>

  <p class="foot"><a href="?logout=1">Sign out</a></p>
<?php endif; ?>

</main>
</body>
</html>

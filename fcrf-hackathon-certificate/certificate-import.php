<?php
/**
 * Load the attendee whitelist into `allowed_attendees`.
 *
 * Upload your sheet as CSV next to this file (attendees.csv). It uses the
 * column headed "email" if present, otherwise the first column. Duplicates
 * and invalid addresses are skipped.
 *
 *   Browser: https://your-site/certificate-import.php?token=YOUR_IMPORT_TOKEN
 *   SSH:     php certificate-import.php your-export.csv
 *
 * SECURITY: your export also contains password hashes and phone numbers.
 * Delete this file AND the CSV as soon as the import succeeds, and never
 * leave the CSV reachable in the web root.
 */

require_once __DIR__ . '/certificate-engine/db.php';

$isCli = (php_sapi_name() === 'cli');
if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
    if (($_GET['token'] ?? '') !== IMPORT_TOKEN || IMPORT_TOKEN === 'change-this-long-random-string') {
        http_response_code(403);
        exit("Forbidden. Set a real IMPORT_TOKEN in certificate-engine/config.php and pass ?token=...\n");
    }
}

$path = $isCli ? ($argv[1] ?? __DIR__ . '/attendees.csv') : __DIR__ . '/attendees.csv';
if (!is_file($path)) exit("CSV not found: $path\n");

$fh = fopen($path, 'r');
if (!$fh) exit("Could not open: $path\n");

$emailCol = 0; $hasHeader = false;
$first = fgetcsv($fh);
if ($first !== false) {
    foreach ($first as $i => $cell) {
        if (strtolower(trim((string)$cell)) === 'email') { $emailCol = $i; $hasHeader = true; }
    }
    if (!$hasHeader) rewind($fh);
}

$pdo = db();
$ins = $pdo->prepare('INSERT IGNORE INTO allowed_attendees (email) VALUES (?)');

$added = $skipped = $seen = 0; $dedupe = [];
while (($row = fgetcsv($fh)) !== false) {
    if (!isset($row[$emailCol])) { $skipped++; continue; }
    $email = strtolower(trim((string)$row[$emailCol]));
    if ($email === '' || isset($dedupe[$email])) continue;
    $dedupe[$email] = true; $seen++;
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $skipped++; continue; }
    $ins->execute([$email]);
    $added += $ins->rowCount();
}
fclose($fh);

$total = (int) $pdo->query('SELECT COUNT(*) FROM allowed_attendees')->fetchColumn();
echo "Processed unique emails : $seen\n";
echo "Newly added             : $added\n";
echo "Skipped (invalid/empty) : $skipped\n";
echo "Total in whitelist now  : $total\n";
echo "\nDone. Now DELETE certificate-import.php and the CSV.\n";

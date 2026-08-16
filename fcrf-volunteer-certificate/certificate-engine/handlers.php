<?php
/**
 * Verify + download handlers for the hackathon certificate.
 * Called from fcrf-hackathon-certificate.php based on ?action=.
 * Assumes the caller has already require'd config.php and started the session.
 */

require_once __DIR__ . '/db.php';

function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function clean_name(string $raw): string {
    $n = trim($raw);
    $n = preg_replace('/[\x00-\x1F\x7F]/u', '', $n);
    $n = preg_replace('/\s+/u', ' ', $n);
    return function_exists('mb_substr') ? mb_substr($n, 0, NAME_MAX_LEN) : substr($n, 0, NAME_MAX_LEN);
}

function name_is_valid(string $n): bool {
    if ($n === '') return false;
    return (bool) preg_match("/^[\p{L}\p{M}][\p{L}\p{M}\.\'\- ]*$/u", $n);
}

function too_many_attempts(): bool {
    $now = time();
    if ($now - ($_SESSION['verify_win'] ?? 0) > 60) {
        $_SESSION['verify_win'] = $now;
        $_SESSION['verify_cnt'] = 0;
    }
    $_SESSION['verify_cnt'] = ($_SESSION['verify_cnt'] ?? 0) + 1;
    return $_SESSION['verify_cnt'] > VERIFY_MAX_ATTEMPTS;
}

/* ------------------------------------------------------------------ */

function handle_verify(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'reason' => 'method'], 405);
    if (too_many_attempts())                   json_out(['ok' => false, 'reason' => 'rate'], 429);

    $body = json_decode(file_get_contents('php://input'), true);
    if (!is_array($body)) $body = $_POST;

    $email = strtolower(trim((string)($body['email'] ?? '')));
    $name  = clean_name((string)($body['name'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_out(['ok' => false, 'reason' => 'email_format']);
    if (!name_is_valid($name))                      json_out(['ok' => false, 'reason' => 'name_invalid']);

    $pdo = db();

    $st = $pdo->prepare('SELECT 1 FROM volunteer_allowed WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    if (!$st->fetchColumn()) json_out(['ok' => false, 'reason' => 'not_registered']);

    $st = $pdo->prepare('SELECT name FROM volunteer_claims WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    $existing = $st->fetchColumn();

    if ($existing !== false) {
        $changed = (mb_strtolower($existing) !== mb_strtolower($name));
        $_SESSION['cert'] = ['email' => $email, 'name' => $existing, 'at' => time()];
        json_out(['ok' => true, 'name' => $existing, 'locked' => true, 'changed' => $changed]);
    }

    try {
        $ins = $pdo->prepare('INSERT INTO volunteer_claims (email, name) VALUES (?, ?)');
        $ins->execute([$email, $name]);
        $_SESSION['cert'] = ['email' => $email, 'name' => $name, 'at' => time()];
        json_out(['ok' => true, 'name' => $name, 'locked' => false, 'changed' => false]);
    } catch (PDOException $e) {
        $st = $pdo->prepare('SELECT name FROM volunteer_claims WHERE email = ? LIMIT 1');
        $st->execute([$email]);
        $locked = $st->fetchColumn() ?: $name;
        $_SESSION['cert'] = ['email' => $email, 'name' => $locked, 'at' => time()];
        json_out(['ok' => true, 'name' => $locked, 'locked' => true, 'changed' => false]);
    }
}

/* ------------------------------------------------------------------ */

function handle_download(): void {
    $cert = $_SESSION['cert'] ?? null;
    $grid = (CERT_DEBUG && isset($_GET['grid']));

    if (!$grid && !$cert) { http_response_code(403); echo 'Please verify your email first.'; return; }
    if (!is_file(CERT_TEMPLATE)) { http_response_code(500); echo 'Certificate template missing on the server.'; return; }

    $name = $grid ? 'Sample Attendee Name' : $cert['name'];

    if (!$grid) {
        try {
            db()->prepare('UPDATE volunteer_claims SET download_count = download_count + 1, last_download_at = NOW() WHERE email = ?')
                ->execute([$cert['email']]);
        } catch (PDOException $e) { /* non-fatal */ }
    }

    render_certificate($name, $grid);
}

function to_pdf_text(string $s): string {
    $out = @iconv('UTF-8', 'windows-1252//TRANSLIT', $s);
    return $out !== false ? $out : $s;
}

function hex_rgb(string $hex): array {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    return [hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2))];
}

function render_certificate(string $name, bool $grid): void {
    require_once __DIR__ . '/lib/pdf.php';

    $pdf = new \setasign\Fpdi\Fpdi();
    $pdf->setSourceFile(CERT_TEMPLATE);
    $tpl  = $pdf->importPage(1);
    $size = $pdf->getTemplateSize($tpl);
    $pdf->AddPage($size['width'] > $size['height'] ? 'L' : 'P', [$size['width'], $size['height']]);
    $pdf->useTemplate($tpl);

    if ($grid) draw_grid($pdf, $size['width'], $size['height']);

    [$r, $g, $b] = hex_rgb(CERT_NAME_COLOR);
    $pdf->SetTextColor($r, $g, $b);

    $text = to_pdf_text($name);
    $pt = (float) CERT_NAME_SIZE;
    $pdf->SetFont(CERT_NAME_FONT, CERT_NAME_STYLE, $pt);
    while ($pt > 8 && $pdf->GetStringWidth($text) > (float) CERT_NAME_MAX_WIDTH) {
        $pt -= 0.5;
        $pdf->SetFont(CERT_NAME_FONT, CERT_NAME_STYLE, $pt);
    }

    $w = $pdf->GetStringWidth($text);
    if (CERT_NAME_ALIGN === 'left') {
        $x = (float) CERT_NAME_LEFT_X;
    } else {
        $cx = (CERT_NAME_CENTER_X === null) ? ($size['width'] / 2) : (float) CERT_NAME_CENTER_X;
        $x = $cx - $w / 2;
    }
    $pdf->Text($x, (float) CERT_NAME_Y, $text);

    $pdf->Output($grid ? 'I' : 'D', CERT_DOWNLOAD_NAME);
}

function draw_grid(\setasign\Fpdi\Fpdi $pdf, float $w, float $h): void {
    $pdf->SetFont('Arial', '', 6);
    for ($x = 0; $x <= $w; $x += 10) {
        $pdf->SetDrawColor($x % 50 == 0 ? 255 : 200, 80, 80);
        $pdf->Line($x, 0, $x, $h);
        if ($x % 20 == 0) { $pdf->SetTextColor(200,0,0); $pdf->Text($x + 0.5, 4, (string)$x); }
    }
    for ($y = 0; $y <= $h; $y += 10) {
        $pdf->SetDrawColor(80, 80, $y % 50 == 0 ? 255 : 200);
        $pdf->Line(0, $y, $w, $y);
        if ($y % 20 == 0) { $pdf->SetTextColor(0,0,200); $pdf->Text(1, $y - 0.5, (string)$y); }
    }
    $pdf->SetDrawColor(0,0,0);
}

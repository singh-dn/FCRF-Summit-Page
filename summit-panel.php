<?php
session_start();

// ================= CONFIGURATION ================= //
// 🔒 ADMIN CREDENTIALS
$admin_username = "harsh";
$admin_password = "harsh100@futurecrime.org";

// 🗄️ DATABASE CONNECTIONS ROUTER
$db_configs = [
    'newfuturecrime' => [
        'host' => 'localhost',
        'user' => 'u545411682_newfuturecrime',
        'pass' => 'FCRFdev820',
        'db'   => 'u545411682_newfuturecrime'
    ],
    'summit' => [
        'host' => 'localhost',
        'user' => 'u545411682_summit',
        'pass' => 'Summit2026',
        'db'   => 'u545411682_summit'
    ]
];

// 🗂️ TABLE ARCHITECTURE
$tables = [
    'professionals' => ['db_key' => 'summit', 'table' => 'fcrf_professionals', 'date_col' => 'created_at', 'name' => 'Summit Professionals', 'icon' => 'user-check'],
    'awards'        => ['db_key' => 'summit', 'table' => 'fcrf_award_nominations', 'date_col' => 'created_at', 'name' => 'Excellence Awards', 'icon' => 'award'],
    'policing'      => ['db_key' => 'summit', 'table' => 'fcrf_policing_awards', 'date_col' => 'created_at', 'name' => 'Policing Awards', 'icon' => 'shield'],
    'hackathon'     => ['db_key' => 'summit', 'table' => 'fcrf_hackathon_2026', 'date_col' => 'created_at', 'name' => 'Hackathon Registration', 'icon' => 'laptop'],
    'applications'  => ['db_key' => 'newfuturecrime', 'table' => 'applications_table', 'date_col' => 'created_at', 'name' => 'Job Applications', 'icon' => 'briefcase'],
    'careers'       => ['db_key' => 'newfuturecrime', 'table' => 'fcrf_careers', 'date_col' => 'applied_at', 'name' => 'Career Form', 'icon' => 'rocket'],
    'contact'       => ['db_key' => 'newfuturecrime', 'table' => 'contact_messages', 'date_col' => 'created_at', 'name' => 'Contact Messages', 'icon' => 'mail']
];

// Determine Active Tab
$active_tab = isset($_GET['tab']) && array_key_exists($_GET['tab'], $tables) ? $_GET['tab'] : 'professionals';
$current_table_info = $tables[$active_tab];

// Search term (searches every column of the active table)
$search_term = isset($_GET['q']) ? trim($_GET['q']) : '';

// ================= LOGIC ================= //

// 1. Handle Logout
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// 2. Handle Login
$login_error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    if ($_POST['username'] === $admin_username && $_POST['password'] === $admin_password) {
        $_SESSION['is_master_admin'] = true;
        session_regenerate_id(true); // Security fix
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $login_error = "Invalid Username or Password";
    }
}

// 3. Helper: Database Connection
function get_db_connection($db_key) {
    global $db_configs;
    $config = $db_configs[$db_key];
    $conn = new mysqli($config['host'], $config['user'], $config['pass'], $config['db']);
    if ($conn->connect_error) { die("Connection failed to DB: $db_key"); }
    $conn->set_charset("utf8mb4");
    return $conn;
}

// 4. Helper: Read the column list of a table (used to search every field)
function get_table_columns($conn, $table_name) {
    $cols = [];
    $res = $conn->query("SHOW COLUMNS FROM `" . $table_name . "`");
    if ($res) {
        while ($c = $res->fetch_assoc()) { $cols[] = $c['Field']; }
        $res->free();
    }
    return $cols;
}

// 5. Helper: Build Query with Datetime + Universal Search
function build_query($conn, $table_name, $date_col) {
    global $search_term;
    $where_clauses = [];

    if (!empty($_GET['start_datetime'])) {
        $start = $conn->real_escape_string(str_replace('T', ' ', $_GET['start_datetime']));
        $where_clauses[] = "$date_col >= '$start'";
    }
    if (!empty($_GET['end_datetime'])) {
        $end = $conn->real_escape_string(str_replace('T', ' ', $_GET['end_datetime']));
        $where_clauses[] = "$date_col <= '$end'";
    }

    // Universal search: name, user code, email, phone — any column in the table
    if ($search_term !== '') {
        $safe = $conn->real_escape_string($search_term);
        $safe = str_replace(['%', '_'], ['\%', '\_'], $safe); // treat wildcards as literals
        $columns = get_table_columns($conn, $table_name);
        $search_parts = [];
        foreach ($columns as $col) {
            $search_parts[] = "`" . str_replace('`', '', $col) . "` LIKE '%$safe%'";
        }
        if (!empty($search_parts)) {
            $where_clauses[] = '(' . implode(' OR ', $search_parts) . ')';
        }
    }

    $sql = "SELECT * FROM $table_name";
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(' AND ', $where_clauses);
    }
    $sql .= " ORDER BY id DESC";

    return $conn->query($sql);
}

// 6. Handle CSV Export (Downloads ALL Columns dynamically)
if (isset($_GET['action']) && $_GET['action'] == 'export' && isset($_SESSION['is_master_admin'])) {
    $conn = get_db_connection($current_table_info['db_key']);
    $result = build_query($conn, $current_table_info['table'], $current_table_info['date_col']);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $active_tab . '_data_' . date('Y-m-d_H-i') . '.csv');
    $output = fopen('php://output', 'w');

    if ($result && $result->num_rows > 0) {
        $first_row = $result->fetch_assoc();
        fputcsv($output, array_keys($first_row));
        fputcsv($output, array_values($first_row));
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, array_values($row));
        }
    } else {
        fputcsv($output, ['No data found for the selected filters.']);
    }

    fclose($output);
    $conn->close();
    exit();
}

// 7. Fetch Data for Active Tab Display
$table_data = [];
$total_records = 0;

if (isset($_SESSION['is_master_admin']) && $_SESSION['is_master_admin'] === true) {
    $conn = get_db_connection($current_table_info['db_key']);
    $result = build_query($conn, $current_table_info['table'], $current_table_info['date_col']);

    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $table_data[] = $row;
        }
        $total_records = count($table_data);
    }
    $conn->close();
}

// 8. Display helpers (presentation only)
function pick_first(array $row, array $keys) {
    foreach ($keys as $k) {
        if (isset($row[$k]) && trim((string)$row[$k]) !== '') return trim((string)$row[$k]);
    }
    return '';
}

function initials_of($name) {
    $name = trim(preg_replace('/\s+/', ' ', (string)$name));
    if ($name === '' || strtolower($name) === 'n/a') return '?';
    $parts = explode(' ', $name);
    $first = mb_substr($parts[0], 0, 1);
    $last  = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : '';
    return mb_strtoupper($first . $last);
}

function label_of($key) { return ucwords(str_replace('_', ' ', $key)); }

function relative_time($timestamp) {
    $diff = time() - $timestamp;
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('d M Y', $timestamp);
}

// 9. Analytics — derived from the rows already fetched, no extra queries
$stat_today = 0; $stat_week = 0; $stat_month = 0;
$unique_emails = [];
$daily_counts = [];
$hour_counts = array_fill(0, 24, 0);

$today_key = date('Y-m-d');
$week_cut  = strtotime('-6 days 00:00:00');
$month_cut = strtotime('-29 days 00:00:00');

// seed the last 30 days so gaps render as zero, not as missing points
for ($i = 29; $i >= 0; $i--) { $daily_counts[date('Y-m-d', strtotime("-$i days"))] = 0; }

$breakdown_candidates = ['award_category','category','course_name','interested_courses','designation',
                         'current_role','position','department','organization','organisation','company',
                         'state','city','experience','subject','status','gender','how_did_you_hear'];
$breakdown_col = '';
$breakdown = [];

foreach ($table_data as $row) {
    $ts = strtotime($row[$current_table_info['date_col']] ?? '');
    if ($ts) {
        $dk = date('Y-m-d', $ts);
        if (isset($daily_counts[$dk])) $daily_counts[$dk]++;
        if ($dk === $today_key) $stat_today++;
        if ($ts >= $week_cut)   $stat_week++;
        if ($ts >= $month_cut)  $stat_month++;
        $hour_counts[(int)date('G', $ts)]++;
    }
    if (!empty($row['email'])) $unique_emails[strtolower(trim($row['email']))] = true;
}

// pick the most useful categorical column for the breakdown chart
if (!empty($table_data)) {
    foreach ($breakdown_candidates as $cand) {
        if (!array_key_exists($cand, $table_data[0])) continue;
        $vals = [];
        foreach ($table_data as $row) {
            $v = trim((string)($row[$cand] ?? ''));
            if ($v === '' || mb_strlen($v) > 46) continue;
            $vals[$v] = ($vals[$v] ?? 0) + 1;
        }
        if (count($vals) > 1) { $breakdown_col = $cand; $breakdown = $vals; break; }
    }
}
arsort($breakdown);
$breakdown = array_slice($breakdown, 0, 6, true);
$breakdown_max = $breakdown ? max($breakdown) : 1;

$peak_hour = array_search(max($hour_counts), $hour_counts);
$chart_labels = array_keys($daily_counts);
$chart_values = array_values($daily_counts);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="color-scheme" content="light">
    <title>Registry — FCRF Master Admin</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        /* ============ DESIGN TOKENS ============
           Ink chrome, porcelain work surface, violet primary + amber signal.
           Mono is structural here: IDs, codes, timestamps and column labels are
           ledger data and are set in mono so the eye can scan columns. */
        :root {
            --ink-900:  #0a0f1c;
            --ink-800:  #111827;
            --ink-700:  #1c2536;
            --porcelain:#eef1f6;
            --paper:    #ffffff;
            --line:     #e2e7f0;
            --line-soft:#eef1f6;
            --text:     #16202e;
            --muted:    #64748b;
            --violet:   #5b5bd6;
            --violet-dk:#4338ca;
            --amber:    #f59e0b;
            --teal:     #0d9488;
            --rose:     #e11d48;
        }

        /* ---- Cross-OS typography ---- */
        html { -webkit-text-size-adjust: 100%; text-size-adjust: 100%; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            font-synthesis-weight: none;
            background: var(--porcelain);
            color: var(--text);
        }
        .display { font-family: 'Space Grotesk', 'Inter', sans-serif; letter-spacing: -0.02em; }
        .mono {
            font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Consolas, monospace;
            font-variant-ligatures: none;
            font-feature-settings: 'tnum' 1;
        }

        /* ============ SCROLL ARCHITECTURE ============
           The old build nested a horizontal scroller inside a vertical one, and
           flex children default to min-height:auto so nothing could shrink —
           that is why the list would not scroll. Every flex column in the chain
           now carries min-height:0, and the table has ONE scroll box that owns
           both axes, so the sticky header has something to stick to. */
        .app-shell { height: 100vh; height: 100dvh; }
        .col-fix   { min-height: 0; min-width: 0; }
        .table-scroll {
            overflow: auto;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
            scroll-behavior: smooth;
        }

        /* ---- Scrollbars: macOS hides them by default ---- */
        * { scrollbar-width: thin; scrollbar-color: #c2cadb transparent; }
        ::-webkit-scrollbar { width: 12px; height: 12px; -webkit-appearance: none; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb {
            background: #c7cfdf; border: 3.5px solid transparent;
            background-clip: padding-box; border-radius: 999px;
        }
        ::-webkit-scrollbar-thumb:hover { background: #9aa6bd; background-clip: padding-box; }
        ::-webkit-scrollbar-corner { background: transparent; }

        /* ---- Safari form control resets ---- */
        input, select, textarea, button { font-family: inherit; font-size: 100%; -webkit-appearance: none; appearance: none; border-radius: 0; }
        input[type="datetime-local"] { min-height: 40px; line-height: 1.2; }
        input[type="datetime-local"]::-webkit-calendar-picker-indicator { cursor: pointer; opacity: .5; }
        input[type="search"]::-webkit-search-decoration,
        input[type="search"]::-webkit-search-cancel-button { -webkit-appearance: none; appearance: none; }
        button { cursor: pointer; }

        /* ---- Icons (size="" is not a valid SVG attribute; sized via CSS) ---- */
        .lucide, [data-lucide] { width: 17px; height: 17px; stroke-width: 2; flex-shrink: 0; }
        .ico-xs { width: 12px; height: 12px; }
        .ico-sm { width: 14px; height: 14px; }
        .ico-lg { width: 22px; height: 22px; }
        .ico-xl { width: 30px; height: 30px; }

        /* ============ CHROME ============ */
        .rail { background: var(--ink-900); }
        .rail-item { position: relative; transition: background-color .16s ease, color .16s ease; }
        .rail-item::before {
            content: ""; position: absolute; left: 0; top: 50%; transform: translateY(-50%);
            width: 2px; height: 0; border-radius: 999px; background: var(--amber);
            transition: height .24s cubic-bezier(.16,1,.3,1);
        }
        .rail-item.is-active::before { height: 22px; }

        .card {
            background: var(--paper); border: 1px solid var(--line); border-radius: 14px;
        }
        .card-lift { transition: transform .18s cubic-bezier(.16,1,.3,1), box-shadow .18s ease; }
        .card-lift:hover { transform: translateY(-2px); box-shadow: 0 14px 30px -20px rgba(16,24,40,.45); }

        /* ============ SIGNATURE: the activity spine ============ */
        #spine { display: block; width: 100%; height: 108px; }
        #spine path.area { transition: opacity .3s ease; }
        #spine path.line { vector-effect: non-scaling-stroke; }
        .spine-draw { stroke-dasharray: var(--len); stroke-dashoffset: var(--len); animation: draw 1.1s cubic-bezier(.16,1,.3,1) forwards; }
        @keyframes draw { to { stroke-dashoffset: 0; } }
        .spine-hit { fill: transparent; cursor: crosshair; }
        #spineTip {
            position: absolute; pointer-events: none; opacity: 0; z-index: 20;
            transition: opacity .12s ease;
            transform: translate(-50%, -120%);
        }

        /* breakdown bars */
        .bar-track { background: var(--line-soft); border-radius: 999px; height: 7px; overflow: hidden; }
        .bar-fill  { height: 100%; border-radius: 999px; width: 0; animation: grow .9s cubic-bezier(.16,1,.3,1) forwards; }
        @keyframes grow { to { width: var(--w); } }

        /* ============ RECORD LEDGER ============ */
        .ledger { border-collapse: separate; border-spacing: 0; width: 100%; min-width: 1040px; }
        .ledger thead th {
            position: sticky; top: 0; z-index: 5;
            background: #f7f9fc;
            border-bottom: 1px solid var(--line);
            font-size: 10px; letter-spacing: .14em; text-transform: uppercase;
            color: #7c8aa3; font-weight: 600; text-align: left;
            padding: 11px 18px; white-space: nowrap;
        }
        .ledger tbody td { border-bottom: 1px solid var(--line-soft); padding: 14px 18px; vertical-align: top; }
        .ledger tbody tr { transition: background-color .14s ease; }
        .ledger tbody tr:hover { background: #f9fbff; }
        .ledger tbody tr:hover .gutter::after { transform: scaleY(1); }
        .ledger tbody tr:hover .row-open { opacity: 1; transform: none; }

        /* left ID gutter with the accent stripe that lights on hover */
        .gutter { position: relative; }
        .gutter::after {
            content: ""; position: absolute; left: 0; top: 8px; bottom: 8px; width: 2px;
            background: var(--violet); border-radius: 999px;
            transform: scaleY(0); transform-origin: center;
            transition: transform .22s cubic-bezier(.16,1,.3,1);
        }
        .row-open { opacity: 0; transform: translateX(4px); transition: opacity .16s ease, transform .16s ease; }
        @media (hover: none) { .row-open { opacity: 1; transform: none; } }

        /* density toggle */
        body.dense .ledger tbody td { padding: 8px 18px; }
        body.dense .avatar { width: 30px; height: 30px; font-size: 11px; border-radius: 9px; }
        body.dense .chip-row { display: none; }

        .avatar {
            width: 38px; height: 38px; border-radius: 11px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 12px; color: #fff;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.22);
            transition: width .16s ease, height .16s ease;
        }
        .chip {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 2.5px 8px; border-radius: 6px;
            font-size: 11px; font-weight: 500; line-height: 1.55;
            border: 1px solid var(--line); background: #f8fafc; color: #52627a;
            max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .chip-key { color: #9aa7bd; font-weight: 600; }

        /* ============ MODAL ============ */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(10,15,28,.6);
            -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px);
            z-index: 60; align-items: center; justify-content: center; padding: 20px;
        }
        .modal-overlay.active { display: flex; animation: fade .16s ease both; }
        .modal-content { animation: rise .34s cubic-bezier(.16,1,.3,1); max-height: 88vh; max-height: 88dvh; display: flex; flex-direction: column; }
        @keyframes rise { from { transform: scale(.97) translateY(10px); opacity: 0; } to { transform: none; opacity: 1; } }
        @keyframes fade { from { opacity: 0; } to { opacity: 1; } }

        .badge-blue   { background: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe; }
        .badge-purple { background: #faf5ff; color: #a21caf; border: 1px solid #f0abfc; }
        .badge-orange { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }

        #toast {
            position: fixed; left: 50%; bottom: 26px; transform: translate(-50%, 14px);
            opacity: 0; pointer-events: none; z-index: 90;
            transition: opacity .18s ease, transform .2s cubic-bezier(.16,1,.3,1);
        }
        #toast.show { opacity: 1; transform: translate(-50%, 0); }

        :focus-visible { outline: 2px solid var(--violet); outline-offset: 2px; border-radius: 8px; }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .001ms !important; animation-iteration-count: 1 !important; transition-duration: .001ms !important; }
        }

        /* ---- Responsive ---- */
        @media (max-width: 1100px) {
            .rail { position: fixed; inset: 0 auto 0 0; z-index: 50; transform: translateX(-100%); transition: transform .28s cubic-bezier(.16,1,.3,1); }
            .rail.open { transform: none; }
            .scrim { display: none; position: fixed; inset: 0; background: rgba(10,15,28,.55); z-index: 45; }
            .scrim.open { display: block; }
        }
        @media (min-width: 1101px) { .menu-btn { display: none; } }
        @media (max-width: 860px) { .metrics { display: none; } }
    </style>
</head>
<body class="app-shell overflow-hidden flex">

<?php if (!isset($_SESSION['is_master_admin']) || $_SESSION['is_master_admin'] !== true): ?>

    <!-- === LOGIN === -->
    <div class="w-full h-full flex items-center justify-center p-5 relative overflow-hidden" style="background: var(--ink-900);">
        <!-- ambient grid, sits behind the card -->
        <div class="absolute inset-0 opacity-[.35]" style="background-image: linear-gradient(rgba(91,91,214,.16) 1px, transparent 1px), linear-gradient(90deg, rgba(91,91,214,.16) 1px, transparent 1px); background-size: 46px 46px; mask-image: radial-gradient(circle at 50% 45%, #000 0%, transparent 72%); -webkit-mask-image: radial-gradient(circle at 50% 45%, #000 0%, transparent 72%);"></div>

        <div class="relative w-full max-w-[400px] rounded-2xl p-9 border" style="background: rgba(255,255,255,.035); border-color: rgba(255,255,255,.1); -webkit-backdrop-filter: blur(18px); backdrop-filter: blur(18px);">
            <div class="mb-8">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl text-white mb-5" style="background: linear-gradient(140deg, var(--violet), var(--violet-dk));">
                    <i data-lucide="shield-check" class="ico-lg"></i>
                </div>
                <h2 class="display text-[26px] font-bold text-white">Master control</h2>
                <p class="text-slate-400 text-sm mt-1.5">Sign in to open the registry</p>
            </div>

            <?php if ($login_error): ?>
                <div class="p-3 rounded-xl text-sm font-medium mb-5 flex items-center gap-2.5 border" style="background: rgba(225,29,72,.1); border-color: rgba(225,29,72,.3); color: #fda4af;">
                    <i data-lucide="alert-circle" class="ico-sm"></i> <?php echo $login_error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="mono block text-[10px] font-semibold text-slate-500 uppercase tracking-[.16em] mb-2">Username</label>
                    <input type="text" name="username" required autocomplete="username" placeholder="Admin ID"
                           class="w-full px-4 py-3 rounded-xl text-white placeholder:text-slate-600 outline-none transition focus:ring-4"
                           style="background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.12); --tw-ring-color: rgba(91,91,214,.3);">
                </div>
                <div>
                    <label class="mono block text-[10px] font-semibold text-slate-500 uppercase tracking-[.16em] mb-2">Password</label>
                    <input type="password" name="password" required autocomplete="current-password" placeholder="••••••••"
                           class="w-full px-4 py-3 rounded-xl text-white placeholder:text-slate-600 outline-none transition focus:ring-4"
                           style="background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.12); --tw-ring-color: rgba(91,91,214,.3);">
                </div>
                <button type="submit" name="login"
                        class="w-full text-white font-semibold py-3.5 rounded-xl transition flex justify-center items-center gap-2 active:scale-[.99] hover:brightness-110"
                        style="background: linear-gradient(140deg, var(--violet), var(--violet-dk));">
                    <i data-lucide="arrow-right" class="ico-sm"></i> Sign in
                </button>
            </form>

            <p class="mono text-center text-[10px] text-slate-600 mt-8 tracking-[.18em]">FCRF · REGISTRY v2</p>
        </div>
    </div>

<?php else: ?>

    <div class="scrim" id="scrim" onclick="toggleRail(false)"></div>

    <!-- === RAIL === -->
    <aside class="rail w-[248px] flex flex-col h-full shrink-0 col-fix" id="rail">
        <div class="px-5 py-5 flex items-center justify-between border-b" style="border-color: rgba(255,255,255,.07);">
            <div class="flex items-center gap-3">
                <span class="w-9 h-9 rounded-xl flex items-center justify-center text-white text-sm font-bold display" style="background: linear-gradient(140deg, var(--violet), var(--violet-dk));">F</span>
                <div>
                    <p class="display text-white text-[15px] font-bold leading-none">Registry</p>
                    <p class="mono text-[9px] text-slate-500 tracking-[.18em] uppercase mt-1.5">FCRF Data Hub</p>
                </div>
            </div>
            <button class="menu-btn text-slate-500 hover:text-white" onclick="toggleRail(false)" aria-label="Close menu"><i data-lucide="x"></i></button>
        </div>

        <nav class="flex-1 overflow-y-auto col-fix py-4 px-3">
            <p class="mono text-[9px] font-semibold text-slate-600 uppercase tracking-[.18em] px-3 pb-3">Collections</p>
            <?php foreach ($tables as $key => $info): ?>
                <a href="?tab=<?php echo $key; ?>"
                   class="rail-item flex items-center gap-3 pl-4 pr-3 py-2.5 rounded-lg text-[13px] mb-0.5 <?php echo $active_tab == $key ? 'is-active text-white font-semibold' : 'text-slate-400 hover:text-white'; ?>"
                   style="<?php echo $active_tab == $key ? 'background: rgba(255,255,255,.07);' : ''; ?>">
                    <i data-lucide="<?php echo $info['icon']; ?>" class="ico-sm <?php echo $active_tab == $key ? 'text-amber-400' : 'text-slate-600'; ?>"></i>
                    <span class="truncate"><?php echo $info['name']; ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="p-3 border-t" style="border-color: rgba(255,255,255,.07);">
            <div class="flex items-center gap-3 px-3 py-2.5 mb-1 rounded-lg" style="background: rgba(255,255,255,.04);">
                <div class="avatar" style="background: linear-gradient(140deg, var(--violet), var(--teal)); width: 30px; height: 30px; font-size: 11px;"><?php echo htmlspecialchars(initials_of($admin_username)); ?></div>
                <div class="min-w-0">
                    <p class="text-white text-[13px] font-semibold truncate leading-tight"><?php echo htmlspecialchars($admin_username); ?></p>
                    <p class="mono text-[9px] text-slate-500 uppercase tracking-[.14em]">Master admin</p>
                </div>
            </div>
            <a href="?action=logout" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-[13px] font-medium transition-colors" style="color: #fb7185;" onmouseover="this.style.background='rgba(225,29,72,.1)'" onmouseout="this.style.background='transparent'">
                <i data-lucide="log-out" class="ico-sm"></i> Sign out
            </a>
        </div>
    </aside>

    <!-- === WORKSPACE (flex column, every level min-height:0 so the ledger can scroll) === -->
    <main class="flex-1 flex flex-col h-full col-fix overflow-hidden">

        <!-- Top bar -->
        <header class="shrink-0 bg-white border-b flex items-center gap-4 px-5 lg:px-7 py-3.5" style="border-color: var(--line);">
            <button class="menu-btn p-2 -ml-2 rounded-lg text-slate-500 hover:bg-slate-100" onclick="toggleRail(true)" aria-label="Open menu"><i data-lucide="menu"></i></button>

            <div class="min-w-0">
                <h1 class="display text-[19px] font-bold truncate leading-tight"><?php echo $current_table_info['name']; ?></h1>
                <p class="mono text-[10px] text-slate-400 mt-0.5 truncate">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500 align-middle mr-1.5"></span><?php echo $current_table_info['db_key']; ?> / <?php echo $current_table_info['table']; ?>
                </p>
            </div>

            <!-- command-style search lives in the top bar now -->
            <form method="GET" class="ml-auto flex items-center gap-2.5 flex-1 max-w-[560px]" id="searchForm">
                <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">
                <input type="hidden" name="start_datetime" value="<?php echo isset($_GET['start_datetime']) ? htmlspecialchars($_GET['start_datetime']) : ''; ?>">
                <input type="hidden" name="end_datetime" value="<?php echo isset($_GET['end_datetime']) ? htmlspecialchars($_GET['end_datetime']) : ''; ?>">
                <div class="relative flex-1">
                    <i data-lucide="search" class="ico-sm absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="search" name="q" id="searchBox" value="<?php echo htmlspecialchars($search_term, ENT_QUOTES); ?>"
                           placeholder="Search name, user code, email, phone, any answer…"
                           class="w-full pl-10 pr-16 py-2.5 rounded-xl border text-[13px] outline-none transition focus:ring-4 focus:bg-white"
                           style="border-color: var(--line); background: #f6f8fc; --tw-ring-color: rgba(91,91,214,.12);">
                    <kbd class="mono hidden sm:block absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 border rounded px-1.5 py-0.5 bg-white" style="border-color: var(--line);">/</kbd>
                </div>
                <button type="submit" class="px-4 py-2.5 rounded-xl text-white text-[13px] font-semibold shrink-0 transition active:scale-[.98] hover:brightness-110" style="background: var(--ink-800);">Search</button>
            </form>
        </header>

        <!-- ============ METRICS + CHARTS ============ -->
        <section class="metrics shrink-0 px-5 lg:px-7 pt-5 pb-1">
            <div class="grid grid-cols-12 gap-4">

                <!-- Stat tiles -->
                <div class="col-span-12 xl:col-span-3 grid grid-cols-2 xl:grid-cols-1 gap-4">
                    <div class="card card-lift p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <i data-lucide="database" class="ico-sm" style="color: var(--violet);"></i>
                            <p class="mono text-[9px] font-semibold uppercase tracking-[.16em] text-slate-400">Records shown</p>
                        </div>
                        <p class="display text-[28px] font-bold leading-none mono"><?php echo number_format($total_records); ?></p>
                        <p class="text-[11px] text-slate-400 mt-1.5"><?php echo number_format(count($unique_emails)); ?> unique emails</p>
                    </div>
                    <div class="card card-lift p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <i data-lucide="sunrise" class="ico-sm" style="color: var(--amber);"></i>
                            <p class="mono text-[9px] font-semibold uppercase tracking-[.16em] text-slate-400">Today</p>
                        </div>
                        <p class="display text-[28px] font-bold leading-none mono"><?php echo number_format($stat_today); ?></p>
                        <p class="text-[11px] text-slate-400 mt-1.5"><?php echo number_format($stat_week); ?> in the last 7 days</p>
                    </div>
                </div>

                <!-- SIGNATURE: the activity spine -->
                <div class="col-span-12 xl:col-span-6 card p-4 relative col-fix">
                    <div class="flex items-start justify-between mb-1">
                        <div>
                            <p class="mono text-[9px] font-semibold uppercase tracking-[.16em] text-slate-400">Submissions · last 30 days</p>
                            <p class="display text-[20px] font-bold leading-tight mt-1 mono"><?php echo number_format($stat_month); ?></p>
                        </div>
                        <div class="text-right">
                            <p class="mono text-[9px] font-semibold uppercase tracking-[.16em] text-slate-400">Busiest hour</p>
                            <p class="mono text-[13px] font-semibold mt-1.5"><?php echo str_pad($peak_hour, 2, '0', STR_PAD_LEFT); ?>:00</p>
                        </div>
                    </div>
                    <div class="relative" id="spineWrap">
                        <svg id="spine" viewBox="0 0 600 108" preserveAspectRatio="none" role="img" aria-label="Daily submissions over the last 30 days"></svg>
                        <div id="spineTip" class="mono text-[11px] px-2.5 py-1.5 rounded-lg text-white shadow-lg" style="background: var(--ink-800);"></div>
                    </div>
                    <div class="flex justify-between mono text-[9px] text-slate-400 mt-1">
                        <span><?php echo date('d M', strtotime($chart_labels[0])); ?></span>
                        <span><?php echo date('d M', strtotime($chart_labels[count($chart_labels)-1])); ?></span>
                    </div>
                </div>

                <!-- Breakdown -->
                <div class="col-span-12 xl:col-span-3 card p-4 col-fix">
                    <p class="mono text-[9px] font-semibold uppercase tracking-[.16em] text-slate-400 mb-3 truncate">
                        <?php echo $breakdown_col ? htmlspecialchars(label_of($breakdown_col)) : 'Breakdown'; ?>
                    </p>
                    <?php if ($breakdown): ?>
                        <div class="space-y-2.5">
                            <?php $bi = 0; $bar_colors = ['var(--violet)','var(--teal)','var(--amber)','#8b5cf6','#0ea5e9','var(--rose)'];
                            foreach ($breakdown as $bval => $bcount): $pct = round(($bcount / $breakdown_max) * 100); ?>
                                <div>
                                    <div class="flex justify-between items-baseline gap-2 mb-1">
                                        <span class="text-[11.5px] font-medium truncate" title="<?php echo htmlspecialchars($bval); ?>"><?php echo htmlspecialchars($bval); ?></span>
                                        <span class="mono text-[11px] font-semibold text-slate-500 shrink-0"><?php echo $bcount; ?></span>
                                    </div>
                                    <div class="bar-track">
                                        <div class="bar-fill" style="--w: <?php echo $pct; ?>%; background: <?php echo $bar_colors[$bi % count($bar_colors)]; ?>; animation-delay: <?php echo $bi * 70; ?>ms;"></div>
                                    </div>
                                </div>
                            <?php $bi++; endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-[12px] text-slate-400 mt-6 text-center">No grouped field in this table.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- ============ FILTER BAR ============ -->
        <div class="shrink-0 px-5 lg:px-7 pt-4 pb-3 flex flex-wrap items-center gap-2.5">
            <form method="GET" class="flex flex-wrap items-center gap-2.5">
                <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">
                <input type="hidden" name="q" value="<?php echo htmlspecialchars($search_term, ENT_QUOTES); ?>">

                <div class="flex items-center gap-2 card px-3 py-1.5">
                    <i data-lucide="calendar-range" class="ico-sm text-slate-400"></i>
                    <input type="datetime-local" name="start_datetime" value="<?php echo isset($_GET['start_datetime']) ? htmlspecialchars($_GET['start_datetime']) : ''; ?>"
                           class="bg-transparent text-[12px] text-slate-700 outline-none mono" style="min-height: 30px;">
                    <span class="text-slate-300">→</span>
                    <input type="datetime-local" name="end_datetime" value="<?php echo isset($_GET['end_datetime']) ? htmlspecialchars($_GET['end_datetime']) : ''; ?>"
                           class="bg-transparent text-[12px] text-slate-700 outline-none mono" style="min-height: 30px;">
                </div>

                <button type="submit" class="px-3.5 py-2 rounded-xl text-[12.5px] font-semibold text-white transition active:scale-[.98] hover:brightness-110" style="background: var(--violet);">Apply</button>
                <a href="?tab=<?php echo $active_tab; ?>" class="px-3.5 py-2 rounded-xl text-[12.5px] font-semibold text-slate-600 card hover:bg-slate-50 transition-colors">Reset all</a>
                <button type="submit" name="action" value="export" class="px-3.5 py-2 rounded-xl text-[12.5px] font-semibold text-slate-700 card hover:bg-slate-50 transition-colors flex items-center gap-1.5">
                    <i data-lucide="download" class="ico-sm" style="color: var(--teal);"></i> Export CSV
                </button>
            </form>

            <div class="ml-auto flex items-center gap-2.5">
                <?php if ($search_term !== ''): ?>
                    <span class="chip" style="background:#eef2ff;border-color:#c7d2fe;color:#4338ca;">
                        <i data-lucide="search" class="ico-xs"></i> <?php echo htmlspecialchars($search_term); ?>
                        <a href="?tab=<?php echo $active_tab; ?>" class="ml-1 opacity-60 hover:opacity-100"><i data-lucide="x" class="ico-xs"></i></a>
                    </span>
                <?php endif; ?>
                <button onclick="toggleDensity()" class="px-3 py-2 rounded-xl text-[12.5px] font-semibold text-slate-600 card hover:bg-slate-50 transition-colors flex items-center gap-1.5" title="Toggle row height">
                    <i data-lucide="rows-3" class="ico-sm"></i> <span id="densityLabel">Comfortable</span>
                </button>
            </div>
        </div>

        <!-- ============ THE LEDGER — one scroll box, both axes ============ -->
        <div class="flex-1 col-fix px-5 lg:px-7 pb-5 flex flex-col">
            <div class="card flex-1 col-fix flex flex-col overflow-hidden">
                <div class="table-scroll flex-1 col-fix">
                    <table class="ledger">
                        <thead>
                            <tr>
                                <th style="width: 74px;">ID</th>
                                <th>Person</th>
                                <th>Contact</th>
                                <th>Submitted details</th>
                                <th style="width: 150px;">Received</th>
                                <th style="width: 90px; text-align: right;">Record</th>
                            </tr>
                        </thead>
                        <tbody class="text-[13px]">
                        <?php if ($total_records > 0): ?>
                            <?php foreach ($table_data as $row):
                                $id = $row['id'];

                                $name = 'N/A';
                                if (!empty($row['nominee_name'])) $name = $row['nominee_name'];
                                elseif (!empty($row['full_name'])) $name = $row['full_name'];
                                elseif (!empty($row['first_name'])) $name = $row['first_name'] . ' ' . ($row['last_name'] ?? '');
                                elseif (!empty($row['name'])) $name = $row['name'];

                                $sub_info = '';
                                if (!empty($row['award_category'])) $sub_info = $row['award_category'];
                                elseif (!empty($row['designation'])) $sub_info = $row['designation'];
                                elseif (!empty($row['current_role'])) $sub_info = $row['current_role'];
                                elseif (!empty($row['position'])) $sub_info = $row['position'];
                                elseif (!empty($row['course_name'])) $sub_info = $row['course_name'];
                                elseif (!empty($row['subject'])) $sub_info = $row['subject'];

                                $email = $row['email'] ?? 'N/A';
                                $phone = $row['phone'] ?? $row['mobile'] ?? '';
                                $ts    = strtotime($row[$current_table_info['date_col']]);
                                $date  = date("d M Y · H:i", $ts);

                                $cv_link  = $row['cv_path'] ?? $row['resume_path'] ?? '';
                                $doc_link = $row['support_doc_path'] ?? '';

                                $user_code = pick_first($row, ['user_code','usercode','code','reference_id','reference_no','registration_id','registration_no','reg_no','application_id','ticket_id','uid','member_id']);
                                $org       = pick_first($row, ['organization','organisation','company','institution','college','department','employer','org_name']);
                                $place     = pick_first($row, ['city','location','state','district','country']);

                                $skip_keys = ['id','password','created_at','updated_at','applied_at','joined_at','submitted_at',
                                              'nominee_name','full_name','first_name','last_name','name','email','phone','mobile',
                                              'cv_path','resume_path','support_doc_path','ip','ip_address','user_agent'];
                                $extra_fields = [];
                                foreach ($row as $k => $v) {
                                    if (in_array($k, $skip_keys, true)) continue;
                                    $v = trim((string)$v);
                                    if ($v === '' || mb_strlen($v) > 40) continue;
                                    if ($v === $sub_info || $v === $user_code || $v === $org || $v === $place) continue;
                                    $extra_fields[$k] = $v;
                                    if (count($extra_fields) >= 3) break;
                                }

                                $palettes = [
                                    'linear-gradient(140deg,#5b5bd6,#8b5cf6)',
                                    'linear-gradient(140deg,#0ea5e9,#0d9488)',
                                    'linear-gradient(140deg,#f59e0b,#f97316)',
                                    'linear-gradient(140deg,#10b981,#0d9488)',
                                    'linear-gradient(140deg,#e11d48,#f43f5e)',
                                    'linear-gradient(140deg,#8b5cf6,#d946ef)',
                                ];
                                $avatar_bg = $palettes[$id % count($palettes)];
                            ?>
                            <tr>
                                <td class="gutter mono text-[11px] text-slate-400 font-medium">#<?php echo $id; ?></td>

                                <td>
                                    <div class="flex items-start gap-3">
                                        <div class="avatar" style="background: <?php echo $avatar_bg; ?>;"><?php echo htmlspecialchars(initials_of($name)); ?></div>
                                        <div class="min-w-0">
                                            <div class="font-semibold text-[13.5px] leading-snug"><?php echo htmlspecialchars($name); ?></div>
                                            <?php if ($sub_info): ?>
                                                <div class="text-[11.5px] text-slate-500 mt-0.5 truncate max-w-[220px]"><?php echo htmlspecialchars($sub_info); ?></div>
                                            <?php endif; ?>
                                            <div class="chip-row flex flex-wrap gap-1.5 mt-2">
                                                <?php if ($user_code): ?>
                                                    <span class="chip mono" style="background:#eef2ff;border-color:#c7d2fe;color:#4338ca;"><i data-lucide="hash" class="ico-xs"></i><?php echo htmlspecialchars($user_code); ?></span>
                                                <?php endif; ?>
                                                <?php if ($cv_link): ?>
                                                    <a href="<?php echo htmlspecialchars($cv_link); ?>" target="_blank" rel="noopener" class="chip hover:brightness-95" style="background:#ecfdf5;border-color:#a7f3d0;color:#047857;"><i data-lucide="file-text" class="ico-xs"></i> CV</a>
                                                <?php endif; ?>
                                                <?php if ($doc_link): ?>
                                                    <a href="<?php echo htmlspecialchars($doc_link); ?>" target="_blank" rel="noopener" class="chip hover:brightness-95" style="background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8;"><i data-lucide="folder-open" class="ico-xs"></i> Docs</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <?php if ($email && $email !== 'N/A'): ?>
                                        <button type="button" onclick="copyValue('<?php echo htmlspecialchars($email, ENT_QUOTES); ?>')" class="group flex items-center gap-2 font-medium text-[12.5px] max-w-[220px] transition-colors" style="color: var(--violet-dk);" title="Copy email">
                                            <i data-lucide="mail" class="ico-sm"></i><span class="truncate"><?php echo htmlspecialchars($email); ?></span>
                                            <i data-lucide="copy" class="ico-xs opacity-0 group-hover:opacity-50"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-slate-300 text-[11.5px] italic">No email</span>
                                    <?php endif; ?>
                                    <?php if ($phone): ?>
                                        <button type="button" onclick="copyValue('<?php echo htmlspecialchars($phone, ENT_QUOTES); ?>')" class="group flex items-center gap-2 text-slate-500 hover:text-slate-800 mt-1.5 text-[11.5px] mono" title="Copy phone">
                                            <i data-lucide="phone" class="ico-xs"></i><?php echo htmlspecialchars($phone); ?>
                                            <i data-lucide="copy" class="ico-xs opacity-0 group-hover:opacity-50"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="flex flex-col gap-1.5 items-start">
                                        <?php if ($org): ?><span class="chip"><i data-lucide="building-2" class="ico-xs text-slate-400"></i><?php echo htmlspecialchars($org); ?></span><?php endif; ?>
                                        <?php if ($place): ?><span class="chip"><i data-lucide="map-pin" class="ico-xs text-slate-400"></i><?php echo htmlspecialchars($place); ?></span><?php endif; ?>
                                        <?php foreach ($extra_fields as $k => $v): ?>
                                            <span class="chip"><span class="chip-key"><?php echo htmlspecialchars(label_of($k)); ?>:</span> <?php echo htmlspecialchars($v); ?></span>
                                        <?php endforeach; ?>
                                        <?php if (!$org && !$place && empty($extra_fields)): ?><span class="text-slate-300 text-[11.5px]">—</span><?php endif; ?>
                                    </div>
                                </td>

                                <td>
                                    <div class="text-[12.5px] font-medium text-slate-700"><?php echo relative_time($ts); ?></div>
                                    <div class="mono text-[10.5px] text-slate-400 mt-0.5 whitespace-nowrap"><?php echo $date; ?></div>
                                </td>

                                <td style="text-align: right;">
                                    <button onclick='viewFullData(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                            class="row-open inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[11.5px] font-semibold border transition-colors hover:text-white"
                                            style="border-color: var(--line); color: #475569;"
                                            onmouseover="this.style.background='var(--ink-800)'; this.style.borderColor='var(--ink-800)';"
                                            onmouseout="this.style.background='transparent'; this.style.borderColor='var(--line)';">
                                        <i data-lucide="maximize-2" class="ico-xs"></i> Open
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="padding: 80px 24px; text-align: center;">
                                    <div class="flex flex-col items-center">
                                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-4" style="background: var(--porcelain);">
                                            <i data-lucide="<?php echo $search_term !== '' ? 'search-x' : 'inbox'; ?>" class="ico-lg text-slate-400"></i>
                                        </div>
                                        <?php if ($search_term !== ''): ?>
                                            <p class="display text-[17px] font-bold">Nothing matches that search</p>
                                            <p class="text-[13px] text-slate-500 mt-1">Try part of a name, a user code, or an email domain.</p>
                                            <a href="?tab=<?php echo $active_tab; ?>" class="mt-4 px-4 py-2 rounded-xl text-white text-[13px] font-semibold" style="background: var(--ink-800);">Clear search</a>
                                        <?php else: ?>
                                            <p class="display text-[17px] font-bold">No records yet</p>
                                            <p class="text-[13px] text-slate-500 mt-1">Widen the date range, or pick another collection.</p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="shrink-0 px-5 py-2.5 border-t flex items-center justify-between" style="border-color: var(--line); background: #fbfcfe;">
                    <p class="mono text-[10.5px] text-slate-400">
                        <?php echo number_format($total_records); ?> record<?php echo $total_records === 1 ? '' : 's'; ?><?php echo $search_term !== '' ? ' · filtered' : ''; ?>
                    </p>
                    <p class="mono text-[10.5px] text-slate-400 hidden sm:block">/ search · ⌘K jump · Esc close</p>
                </div>
            </div>
        </div>
    </main>

    <!-- === RECORD MODAL === -->
    <div id="dataModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-content bg-white w-full max-w-3xl rounded-2xl overflow-hidden border" style="border-color: var(--line);">
            <div class="px-6 py-4 flex items-center justify-between gap-4 border-b shrink-0" style="border-color: var(--line);">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white shrink-0" style="background: linear-gradient(140deg, var(--violet), var(--violet-dk));">
                        <i data-lucide="file-text" class="ico-sm"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 id="modalTitle" class="display text-[16px] font-bold truncate">Complete record</h3>
                        <p class="mono text-[10px] text-slate-400 truncate"><?php echo $current_table_info['table']; ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                    <button onclick="copyRecord()" class="px-3 py-2 text-[12px] font-semibold text-slate-600 hover:bg-slate-100 rounded-lg transition-colors flex items-center gap-1.5">
                        <i data-lucide="clipboard-copy" class="ico-sm"></i> Copy all
                    </button>
                    <button onclick="closeModal()" class="text-slate-400 hover:text-rose-500 hover:bg-rose-50 p-2 rounded-lg transition-colors" aria-label="Close"><i data-lucide="x"></i></button>
                </div>
            </div>

            <div class="px-6 pt-4 shrink-0">
                <div class="relative">
                    <i data-lucide="search" class="ico-sm absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="search" id="modalFilter" oninput="filterModalFields(this.value)" placeholder="Filter fields in this record"
                           class="w-full pl-9 pr-3 py-2 rounded-lg border text-[13px] outline-none transition focus:ring-4"
                           style="border-color: var(--line); background: #f6f8fc; --tw-ring-color: rgba(91,91,214,.1);">
                </div>
            </div>

            <div class="p-6 overflow-y-auto col-fix" id="modalDataContainer" style="overscroll-behavior: contain;"></div>

            <div class="px-6 py-3.5 flex justify-between items-center border-t shrink-0" style="border-color: var(--line); background: #fbfcfe;">
                <span class="mono text-[10.5px] text-slate-400">Esc to close</span>
                <button onclick="closeModal()" class="px-5 py-2 text-white text-[13px] font-semibold rounded-lg transition hover:brightness-110" style="background: var(--ink-800);">Close</button>
            </div>
        </div>
    </div>

    <div id="toast">
        <div class="text-white text-[13px] font-medium px-4 py-2.5 rounded-xl shadow-2xl flex items-center gap-2" style="background: var(--ink-800);">
            <i data-lucide="check" class="ico-sm text-emerald-400"></i> <span id="toastText">Copied</span>
        </div>
    </div>

    <script>
        lucide.createIcons();

        /* ============ ACTIVITY SPINE ============
           Hand-drawn SVG so it stays sharp and needs no chart library.
           Catmull-Rom control points give the smooth curve. */
        const spineData   = <?php echo json_encode($chart_values); ?>;
        const spineLabels = <?php echo json_encode($chart_labels); ?>;

        function drawSpine() {
            const svg = document.getElementById('spine');
            if (!svg || !spineData.length) return;
            const W = 600, H = 108, PAD = 8;
            const max = Math.max(...spineData, 1);
            const stepX = W / (spineData.length - 1 || 1);
            const pts = spineData.map((v, i) => [i * stepX, H - PAD - (v / max) * (H - PAD * 2 - 10)]);

            let d = `M ${pts[0][0]} ${pts[0][1]}`;
            for (let i = 0; i < pts.length - 1; i++) {
                const p0 = pts[i - 1] || pts[i], p1 = pts[i], p2 = pts[i + 1], p3 = pts[i + 2] || p2;
                const c1x = p1[0] + (p2[0] - p0[0]) / 6, c1y = p1[1] + (p2[1] - p0[1]) / 6;
                const c2x = p2[0] - (p3[0] - p1[0]) / 6, c2y = p2[1] - (p3[1] - p1[1]) / 6;
                d += ` C ${c1x} ${c1y}, ${c2x} ${c2y}, ${p2[0]} ${p2[1]}`;
            }
            const area = d + ` L ${W} ${H} L 0 ${H} Z`;

            svg.innerHTML = `
                <defs>
                    <linearGradient id="spineFill" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#5b5bd6" stop-opacity=".26"/>
                        <stop offset="100%" stop-color="#5b5bd6" stop-opacity="0"/>
                    </linearGradient>
                </defs>
                <path class="area" d="${area}" fill="url(#spineFill)"/>
                <path class="line" d="${d}" fill="none" stroke="#5b5bd6" stroke-width="2"
                      stroke-linecap="round" stroke-linejoin="round"/>
                <line id="spineCross" x1="0" y1="0" x2="0" y2="${H}" stroke="#5b5bd6" stroke-width="1" opacity="0"/>
                <circle id="spineDot" r="3.5" fill="#fff" stroke="#5b5bd6" stroke-width="2" opacity="0"/>
                <rect class="spine-hit" x="0" y="0" width="${W}" height="${H}"/>
            `;

            const line = svg.querySelector('.line');
            if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                const len = line.getTotalLength();
                line.style.setProperty('--len', len);
                line.classList.add('spine-draw');
            }

            // hover readout
            const wrap = document.getElementById('spineWrap');
            const tip = document.getElementById('spineTip');
            const cross = svg.querySelector('#spineCross');
            const dot = svg.querySelector('#spineDot');

            svg.addEventListener('mousemove', (e) => {
                const r = svg.getBoundingClientRect();
                const i = Math.round(((e.clientX - r.left) / r.width) * (spineData.length - 1));
                const idx = Math.min(Math.max(i, 0), spineData.length - 1);
                const [px, py] = pts[idx];
                cross.setAttribute('x1', px); cross.setAttribute('x2', px);
                cross.setAttribute('opacity', '.25');
                dot.setAttribute('cx', px); dot.setAttribute('cy', py); dot.setAttribute('opacity', '1');
                const dateTxt = new Date(spineLabels[idx] + 'T00:00:00')
                    .toLocaleDateString(undefined, { day: '2-digit', month: 'short' });
                tip.textContent = `${dateTxt} · ${spineData[idx]}`;
                tip.style.left = ((px / 600) * r.width) + 'px';
                tip.style.top  = ((py / 108) * r.height) + 'px';
                tip.style.opacity = '1';
            });
            svg.addEventListener('mouseleave', () => {
                tip.style.opacity = '0';
                cross.setAttribute('opacity', '0');
                dot.setAttribute('opacity', '0');
            });
        }
        drawSpine();

        /* ============ MODAL ============ */
        const modal = document.getElementById('dataModal');
        const container = document.getElementById('modalDataContainer');
        let currentRecord = null;

        function escapeHtml(str) {
            return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        function viewFullData(rowData) {
            currentRecord = rowData;
            document.getElementById('modalFilter').value = '';
            let html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">';

            for (const [key, value] of Object.entries(rowData)) {
                const formattedKey = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                const searchable = escapeHtml((key + ' ' + (value ?? '')).toLowerCase());

                let displayValue = value === null ? '' : escapeHtml(value);
                if (value === null || value === '') {
                    displayValue = '<span class="text-slate-300 italic">Not provided</span>';
                } else if (key.includes('path') || key.includes('url') || key.includes('link')) {
                    displayValue = `<a href="${escapeHtml(value)}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 font-semibold px-3 py-1.5 rounded-lg border transition-colors mt-1" style="color:#4338ca;background:#eef2ff;border-color:#c7d2fe;"><i data-lucide="external-link" class="ico-xs"></i> Open file / link</a>`;
                } else if (String(value).length > 60) {
                    html += `<div class="field-block col-span-1 md:col-span-2 p-4 rounded-xl border" style="background:#f8fafc;border-color:var(--line-soft);" data-search="${searchable}">
                                <div class="mono text-[9px] font-semibold text-slate-400 uppercase tracking-[.16em] mb-2">${formattedKey}</div>
                                <div class="text-[13px] whitespace-pre-wrap leading-relaxed">${displayValue}</div>
                             </div>`;
                    continue;
                } else {
                    if (key === 'overall_rating') displayValue = `<span class="badge-orange px-2 py-1 rounded text-[11px] font-semibold">${displayValue}</span>`;
                    else if (key === 'award_category' || key === 'interested_courses' || key === 'course_name') displayValue = `<span class="badge-purple px-2 py-1 rounded text-[11px] font-semibold">${displayValue}</span>`;
                    else if (key.includes('email')) displayValue = `<span class="badge-blue px-2 py-1 rounded text-[11px] font-medium">${displayValue}</span>`;
                }

                html += `
                    <div class="field-block pb-3 border-b" style="border-color: var(--line-soft);" data-search="${searchable}">
                        <div class="mono text-[9px] font-semibold text-slate-400 uppercase tracking-[.16em] mb-1">${formattedKey}</div>
                        <div class="text-[13px] font-medium break-words">${displayValue}</div>
                    </div>`;
            }

            html += '</div>';
            container.innerHTML = html;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            lucide.createIcons();
        }

        function filterModalFields(term) {
            const t = term.trim().toLowerCase();
            container.querySelectorAll('.field-block').forEach(el => {
                el.style.display = (!t || el.dataset.search.includes(t)) ? '' : 'none';
            });
        }

        function closeModal() {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
        modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

        /* ============ UTIL ============ */
        let toastTimer;
        function showToast(msg) {
            document.getElementById('toastText').textContent = msg;
            const t = document.getElementById('toast');
            t.classList.add('show');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => t.classList.remove('show'), 1600);
        }

        function copyValue(text) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => showToast('Copied ' + text));
            } else {
                const ta = document.createElement('textarea');
                ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
                document.body.appendChild(ta); ta.select();
                try { document.execCommand('copy'); showToast('Copied ' + text); } catch (e) {}
                document.body.removeChild(ta);
            }
        }

        function copyRecord() {
            if (!currentRecord) return;
            copyValue(Object.entries(currentRecord).map(([k, v]) => k.replace(/_/g, ' ') + ': ' + (v ?? '')).join('\n'));
            showToast('Record copied');
        }

        function toggleRail(open) {
            document.getElementById('rail').classList.toggle('open', open);
            document.getElementById('scrim').classList.toggle('open', open);
        }

        function toggleDensity() {
            const dense = document.body.classList.toggle('dense');
            document.getElementById('densityLabel').textContent = dense ? 'Compact' : 'Comfortable';
            try { localStorage.setItem('fcrf_density', dense ? '1' : '0'); } catch (e) {}
        }
        try {
            if (localStorage.getItem('fcrf_density') === '1') {
                document.body.classList.add('dense');
                document.getElementById('densityLabel').textContent = 'Compact';
            }
        } catch (e) {}

        document.addEventListener('keydown', (e) => {
            const typing = ['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName);
            if (e.key === 'Escape') { closeModal(); toggleRail(false); }
            if (e.key === '/' && !typing) { e.preventDefault(); document.getElementById('searchBox')?.focus(); }
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); document.getElementById('searchBox')?.focus(); }
        });
    </script>

<?php endif; ?>
</body>
</html>

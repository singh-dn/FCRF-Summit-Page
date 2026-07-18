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
        // Dynamically write headers based on table columns
        fputcsv($output, array_keys($first_row));
        // Write first row data
        fputcsv($output, array_values($first_row));
        // Write remaining rows
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
    $name = trim(preg_replace('/\s+/', ' ', $name));
    if ($name === '' || strtolower($name) === 'n/a') return '?';
    $parts = explode(' ', $name);
    $first = mb_substr($parts[0], 0, 1);
    $last  = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : '';
    return mb_strtoupper($first . $last);
}

function label_of($key) {
    return ucwords(str_replace('_', ' ', $key));
}

function relative_time($timestamp) {
    $diff = time() - $timestamp;
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('d M Y', $timestamp);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="color-scheme" content="light">
    <title>Master Admin — FCRF</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        :root {
            --ink:        #0b1220;
            --ink-soft:   #1e293b;
            --line:       #e6eaf2;
            --canvas:     #f6f7fb;
            --brand:      #4f46e5;
            --brand-deep: #3730a3;
            --accent:     #06b6d4;
        }

        /* ---- Cross-OS typography: identical weight on macOS, Windows and Linux ---- */
        html {
            -webkit-text-size-adjust: 100%;   /* stops Safari/iOS inflating text */
            text-size-adjust: 100%;
        }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI',
                         'Helvetica Neue', Roboto, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;      /* macOS renders lighter, matches Windows */
            -moz-osx-font-smoothing: grayscale;
            font-synthesis-weight: none;              /* no faux-bold on macOS */
            background-color: var(--canvas);
            text-rendering: optimizeLegibility;
        }
        .mono {
            font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, 'SF Mono',
                         Menlo, Consolas, 'Liberation Mono', monospace;
            font-variant-ligatures: none;
            font-feature-settings: 'tnum' 1;
        }

        /* ---- Full-height that survives mobile Safari's collapsing toolbar ---- */
        .app-shell { height: 100vh; height: 100dvh; }

        /* ---- Scrollbars: macOS hides them by default, so make them explicit everywhere ---- */
        * { scrollbar-width: thin; scrollbar-color: #c3cad9 transparent; }
        ::-webkit-scrollbar { width: 10px; height: 10px; -webkit-appearance: none; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb {
            background: #c9d0de;
            border: 3px solid transparent;
            background-clip: padding-box;
            border-radius: 999px;
        }
        ::-webkit-scrollbar-thumb:hover { background: #a4aec2; background-clip: padding-box; }
        .scroll-area { overscroll-behavior: contain; -webkit-overflow-scrolling: touch; }

        /* ---- Form controls: Safari styles these itself unless we reset ---- */
        input, select, textarea, button {
            font-family: inherit;
            font-size: 100%;
            -webkit-appearance: none;
            appearance: none;
            border-radius: 0;
        }
        input[type="datetime-local"] {
            min-height: 42px;              /* Safari renders these shorter than Chrome */
            line-height: 1.2;
        }
        input[type="datetime-local"]::-webkit-calendar-picker-indicator { cursor: pointer; opacity: .55; }
        input[type="datetime-local"]::-webkit-datetime-edit { padding: 0; }
        input[type="search"]::-webkit-search-decoration,
        input[type="search"]::-webkit-search-cancel-button { -webkit-appearance: none; appearance: none; }
        button { cursor: pointer; }

        /* ---- Icon sizing (the old size="" attribute is not valid SVG and was ignored) ---- */
        .lucide, [data-lucide] { width: 18px; height: 18px; stroke-width: 2; flex-shrink: 0; }
        .ico-sm  { width: 14px; height: 14px; }
        .ico-xs  { width: 12px; height: 12px; }
        .ico-lg  { width: 22px; height: 22px; }
        .ico-xl  { width: 30px; height: 30px; }

        /* ---- Surfaces ---- */
        .glass-panel {
            background: rgba(255, 255, 255, 0.94);
            -webkit-backdrop-filter: saturate(160%) blur(14px);   /* Safari needs the prefix */
            backdrop-filter: saturate(160%) blur(14px);
        }
        .sheet {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 16px;
            box-shadow: 0 1px 2px rgba(16, 24, 40, .04), 0 12px 32px -18px rgba(16, 24, 40, .28);
        }

        /* Sidebar rail marker for the active section */
        .nav-item { position: relative; }
        .nav-item::before {
            content: ""; position: absolute; left: -12px; top: 50%;
            width: 3px; height: 0; border-radius: 999px;
            background: linear-gradient(180deg, var(--accent), var(--brand));
            transform: translateY(-50%);
            transition: height .22s cubic-bezier(.16,1,.3,1);
        }
        .nav-item.is-active::before { height: 26px; }

        /* Table */
        .data-table thead th {
            position: sticky; top: 0; z-index: 5;
            background: #fbfcfe;
            box-shadow: inset 0 -1px 0 var(--line);
        }
        .data-row { transition: background-color .15s ease, box-shadow .15s ease; }
        .data-row:hover { background: #f8faff; }
        .data-row:hover .row-actions { opacity: 1; transform: none; }
        .row-actions { opacity: .55; transform: translateX(3px); transition: opacity .15s ease, transform .15s ease; }
        @media (hover: none) { .row-actions { opacity: 1; transform: none; } }

        .avatar {
            width: 40px; height: 40px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 13px; letter-spacing: .02em; color: #fff;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.25);
        }

        .chip {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 3px 8px; border-radius: 7px;
            font-size: 11px; font-weight: 600; line-height: 1.5;
            border: 1px solid var(--line); background: #f8fafc; color: #475569;
            max-width: 210px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .chip-key { color: #94a3b8; font-weight: 600; }

        /* Modals */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(11, 18, 32, .62);
            -webkit-backdrop-filter: blur(6px);
            backdrop-filter: blur(6px);
            z-index: 50; align-items: center; justify-content: center; padding: 20px;
        }
        .modal-overlay.active { display: flex; animation: fadeIn .18s ease both; }
        .modal-content { animation: scaleUp .34s cubic-bezier(.16,1,.3,1); max-height: 88vh; max-height: 88dvh; display: flex; flex-direction: column; }
        @keyframes scaleUp { from { transform: scale(.96) translateY(8px); opacity: 0; } to { transform: none; opacity: 1; } }
        @keyframes fadeIn  { from { opacity: 0; } to { opacity: 1; } }

        /* Dynamic Badges */
        .badge-blue   { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .badge-green  { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-purple { background: #faf5ff; color: #a21caf; border: 1px solid #f5d0fe; }
        .badge-orange { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }

        /* Toast */
        #toast {
            position: fixed; left: 50%; bottom: 28px; transform: translate(-50%, 16px);
            opacity: 0; pointer-events: none; z-index: 80;
            transition: opacity .2s ease, transform .2s cubic-bezier(.16,1,.3,1);
        }
        #toast.show { opacity: 1; transform: translate(-50%, 0); }

        /* Accessibility floor */
        :focus-visible { outline: 2px solid var(--brand); outline-offset: 2px; border-radius: 8px; }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .001ms !important; transition-duration: .001ms !important; }
        }

        /* Responsive sidebar */
        @media (max-width: 1024px) {
            .sidebar {
                position: fixed; inset: 0 auto 0 0; z-index: 40;
                transform: translateX(-100%); transition: transform .28s cubic-bezier(.16,1,.3,1);
            }
            .sidebar.open { transform: translateX(0); }
            .sidebar-scrim { display: none; position: fixed; inset: 0; background: rgba(11,18,32,.5); z-index: 30; }
            .sidebar-scrim.open { display: block; }
        }
        @media (min-width: 1025px) { .menu-btn { display: none; } }
    </style>
</head>
<body class="text-slate-800 app-shell overflow-hidden flex">

<?php if (!isset($_SESSION['is_master_admin']) || $_SESSION['is_master_admin'] !== true): ?>

    <!-- === SECURE LOGIN PAGE === -->
    <div class="w-full h-full flex items-center justify-center p-5 relative overflow-hidden"
         style="background: radial-gradient(1100px 600px at 15% -10%, #312e81 0%, transparent 55%), radial-gradient(900px 500px at 110% 110%, #0e7490 0%, transparent 50%), #070b18;">

        <div class="glass-panel p-9 sm:p-10 rounded-3xl shadow-2xl w-full max-w-md border border-white/25 relative">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl text-white mb-5 shadow-lg"
                     style="background: linear-gradient(135deg, var(--brand), var(--accent)); box-shadow: 0 12px 30px -12px rgba(79,70,229,.7);">
                    <i data-lucide="shield-check" class="ico-xl"></i>
                </div>
                <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Master Control</h2>
                <p class="text-slate-500 text-sm mt-1.5">Sign in to reach the data hub</p>
            </div>

            <?php if ($login_error): ?>
                <div class="bg-red-50 text-red-700 p-3.5 rounded-xl text-sm font-medium mb-6 flex items-center gap-2.5 border border-red-200">
                    <i data-lucide="alert-circle" class="ico-sm"></i> <?php echo $login_error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Username</label>
                    <div class="relative">
                        <i data-lucide="user" class="ico-sm absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" name="username" required autocomplete="username"
                               class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-300 bg-white/80 focus:ring-4 focus:ring-indigo-500/15 focus:border-indigo-500 outline-none transition"
                               placeholder="Admin ID">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Password</label>
                    <div class="relative">
                        <i data-lucide="lock" class="ico-sm absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="password" name="password" required autocomplete="current-password"
                               class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-300 bg-white/80 focus:ring-4 focus:ring-indigo-500/15 focus:border-indigo-500 outline-none transition"
                               placeholder="••••••••">
                    </div>
                </div>
                <button type="submit" name="login"
                        class="w-full text-white font-bold py-3.5 px-4 rounded-xl transition duration-200 flex justify-center items-center gap-2 active:scale-[.99]"
                        style="background: linear-gradient(135deg, var(--brand), var(--brand-deep)); box-shadow: 0 14px 30px -14px rgba(79,70,229,.9);">
                    <i data-lucide="log-in" class="ico-sm"></i> Sign in
                </button>
            </form>

            <p class="text-center text-[11px] text-slate-400 mt-7 mono">FCRF · MASTER ADMIN</p>
        </div>
    </div>

<?php else: ?>

    <!-- === SIDEBAR NAVIGATION === -->
    <div class="sidebar-scrim" id="sidebarScrim" onclick="toggleSidebar(false)"></div>

    <aside class="sidebar w-72 flex flex-col h-full shrink-0 z-40" id="sidebar"
           style="background: linear-gradient(180deg, #0d1426 0%, #0a1020 100%); box-shadow: 1px 0 0 rgba(255,255,255,.06), 12px 0 40px -30px rgba(0,0,0,.9);">

        <div class="px-6 py-6 border-b border-white/[.07] flex items-center justify-between">
            <div>
                <h1 class="text-[17px] font-extrabold text-white flex items-center gap-3 tracking-tight">
                    <span class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-black"
                          style="background: linear-gradient(135deg, var(--brand), var(--accent)); box-shadow: 0 8px 22px -10px rgba(6,182,212,.9);">F</span>
                    FCRF System
                </h1>
                <p class="text-[10px] text-slate-500 mt-2.5 tracking-[.18em] uppercase mono">Data Management Hub</p>
            </div>
            <button class="menu-btn text-slate-400 hover:text-white p-1" onclick="toggleSidebar(false)" aria-label="Close menu">
                <i data-lucide="x"></i>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto scroll-area py-4 px-4 space-y-1">
            <p class="text-[10px] font-bold text-slate-600 uppercase tracking-[.14em] px-3 pb-2">Collections</p>
            <?php foreach ($tables as $key => $info): ?>
                <a href="?tab=<?php echo $key; ?>"
                   class="nav-item flex items-center gap-3 px-3.5 py-3 rounded-xl text-sm transition-all duration-200 group <?php echo $active_tab == $key ? 'is-active bg-white/[.09] text-white font-semibold' : 'text-slate-400 hover:bg-white/[.05] hover:text-white'; ?>">
                    <i data-lucide="<?php echo $info['icon']; ?>" class="ico-sm <?php echo $active_tab == $key ? 'text-cyan-300' : 'text-slate-500 group-hover:text-cyan-300'; ?>"></i>
                    <span class="truncate"><?php echo $info['name']; ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="p-4 border-t border-white/[.07]">
            <div class="flex items-center gap-3 px-3 py-3 mb-2 rounded-xl bg-white/[.04]">
                <div class="avatar" style="background: linear-gradient(135deg,#6366f1,#06b6d4); width:34px; height:34px; font-size:12px;">
                    <?php echo htmlspecialchars(initials_of($admin_username)); ?>
                </div>
                <div class="min-w-0">
                    <p class="text-white text-sm font-semibold truncate"><?php echo htmlspecialchars($admin_username); ?></p>
                    <p class="text-[10px] text-slate-500 mono uppercase tracking-wider">Master admin</p>
                </div>
            </div>
            <a href="?action=logout" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm text-red-400 hover:bg-red-500/10 hover:text-red-300 transition-colors font-semibold">
                <i data-lucide="log-out" class="ico-sm"></i> Sign out
            </a>
        </div>
    </aside>

    <!-- === MAIN CONTENT AREA === -->
    <main class="flex-1 flex flex-col h-full relative overflow-hidden" style="background: var(--canvas);">

        <!-- Header -->
        <header class="bg-white/85 glass-panel border-b border-slate-200 px-5 sm:px-8 py-4 flex items-center justify-between gap-4 shrink-0 z-10">
            <div class="flex items-center gap-3 min-w-0">
                <button class="menu-btn p-2 -ml-1 rounded-lg text-slate-500 hover:bg-slate-100" onclick="toggleSidebar(true)" aria-label="Open menu">
                    <i data-lucide="menu"></i>
                </button>
                <div class="min-w-0">
                    <h2 class="text-xl sm:text-[22px] font-extrabold text-slate-900 tracking-tight truncate"><?php echo $current_table_info['name']; ?></h2>
                    <div class="flex items-center gap-2 text-[11px] text-slate-500 mt-1 mono truncate">
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                        <?php echo $current_table_info['db_key']; ?> · <?php echo $current_table_info['table']; ?>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 shrink-0">
                <div class="hidden sm:flex items-center gap-3 px-4 py-2 rounded-xl border border-indigo-100 bg-indigo-50/70">
                    <div class="p-2 bg-white rounded-lg text-indigo-600 shadow-sm"><i data-lucide="database" class="ico-sm"></i></div>
                    <div>
                        <p class="text-[10px] text-indigo-500 font-bold uppercase tracking-[.12em]">Records shown</p>
                        <p class="text-lg font-extrabold text-indigo-900 leading-none mono"><?php echo number_format($total_records); ?></p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Search, Filter & Export Toolbar -->
        <div class="px-5 sm:px-8 py-4 bg-white border-b border-slate-200 shrink-0">
            <form method="GET" class="flex flex-wrap items-end gap-3" id="filterForm">
                <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">

                <!-- Universal search -->
                <div class="flex-1 min-w-[240px]">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-[.12em] mb-1.5" for="searchBox">Search records</label>
                    <div class="relative">
                        <i data-lucide="search" class="ico-sm absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="search" name="q" id="searchBox"
                               value="<?php echo htmlspecialchars($search_term, ENT_QUOTES); ?>"
                               placeholder="Name, user code, email, phone, city — any detail they entered"
                               class="w-full pl-10 pr-24 py-2.5 rounded-xl border border-slate-300 bg-slate-50 focus:bg-white text-sm text-slate-800 placeholder:text-slate-400 focus:ring-4 focus:ring-indigo-500/12 focus:border-indigo-500 outline-none transition">
                        <?php if ($search_term !== ''): ?>
                            <a href="?tab=<?php echo $active_tab; ?>" class="absolute right-14 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 p-1" title="Clear search">
                                <i data-lucide="x" class="ico-sm"></i>
                            </a>
                        <?php endif; ?>
                        <kbd class="hidden sm:block absolute right-3 top-1/2 -translate-y-1/2 text-[10px] mono text-slate-400 border border-slate-200 rounded px-1.5 py-0.5 bg-white">/</kbd>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-[.12em] mb-1.5">From</label>
                    <input type="datetime-local" name="start_datetime" value="<?php echo isset($_GET['start_datetime']) ? htmlspecialchars($_GET['start_datetime']) : ''; ?>"
                           class="px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-4 focus:ring-indigo-500/12 focus:border-indigo-500 outline-none text-slate-700 bg-slate-50 focus:bg-white transition">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-[.12em] mb-1.5">To</label>
                    <input type="datetime-local" name="end_datetime" value="<?php echo isset($_GET['end_datetime']) ? htmlspecialchars($_GET['end_datetime']) : ''; ?>"
                           class="px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-4 focus:ring-indigo-500/12 focus:border-indigo-500 outline-none text-slate-700 bg-slate-50 focus:bg-white transition">
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold rounded-xl flex items-center gap-2 transition-colors active:scale-[.98]">
                        <i data-lucide="filter" class="ico-sm"></i> Apply
                    </button>
                    <a href="?tab=<?php echo $active_tab; ?>" class="px-4 py-2.5 bg-white hover:bg-slate-50 text-slate-600 border border-slate-300 text-sm font-semibold rounded-xl transition-colors flex items-center">
                        Reset
                    </a>
                </div>

                <div class="ml-auto">
                    <button type="submit" name="action" value="export"
                            class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl flex items-center gap-2 transition-all hover:-translate-y-0.5 active:translate-y-0"
                            style="box-shadow: 0 12px 24px -14px rgba(5,150,105,.9);">
                        <i data-lucide="file-spreadsheet" class="ico-sm"></i> Download CSV
                    </button>
                </div>
            </form>

            <?php if ($search_term !== ''): ?>
                <div class="mt-3 flex items-center gap-2 text-xs text-slate-500">
                    <i data-lucide="corner-down-right" class="ico-xs text-slate-400"></i>
                    Matching <span class="mono font-semibold text-slate-800">"<?php echo htmlspecialchars($search_term); ?>"</span>
                    across every field in this table — <span class="font-semibold text-slate-800"><?php echo number_format($total_records); ?></span> found.
                </div>
            <?php endif; ?>
        </div>

        <!-- Dynamic Table Area -->
        <div class="flex-1 overflow-auto scroll-area p-5 sm:p-8">
            <div class="sheet overflow-hidden">
                <div class="overflow-x-auto scroll-area">
                <table class="w-full text-left border-collapse data-table" style="min-width: 980px;">
                    <thead>
                        <tr class="text-[10px] uppercase tracking-[.12em] text-slate-500 font-bold">
                            <th class="px-5 py-3.5 w-16">ID</th>
                            <th class="px-5 py-3.5">Person</th>
                            <th class="px-5 py-3.5">Contact</th>
                            <th class="px-5 py-3.5">Details</th>
                            <th class="px-5 py-3.5">Submitted</th>
                            <th class="px-5 py-3.5 text-right">Record</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                        <?php if ($total_records > 0): ?>
                            <?php foreach ($table_data as $row):
                                // Smart logic to extract primary name and contact info universally
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
                                $date  = date("d M Y, h:i A", $ts);

                                // Check for attached documents (CV / Supporting Docs)
                                $cv_link = $row['cv_path'] ?? $row['resume_path'] ?? '';
                                $doc_link = $row['support_doc_path'] ?? '';

                                // --- extra display detail (presentation only) ---
                                $user_code = pick_first($row, ['user_code','usercode','code','reference_id','reference_no','registration_id','registration_no','reg_no','application_id','ticket_id','uid','member_id']);
                                $org       = pick_first($row, ['organization','organisation','company','institution','college','department','employer','org_name']);
                                $place     = pick_first($row, ['city','location','state','district','country','address']);

                                // any remaining short fields, so each row shows more of what they entered
                                $skip_keys = ['id','password','created_at','updated_at','applied_at','joined_at','submitted_at',
                                              'nominee_name','full_name','first_name','last_name','name','email','phone','mobile',
                                              'cv_path','resume_path','support_doc_path','ip','ip_address','user_agent'];
                                $extra_fields = [];
                                foreach ($row as $k => $v) {
                                    if (in_array($k, $skip_keys, true)) continue;
                                    $v = trim((string)$v);
                                    if ($v === '' || mb_strlen($v) > 42) continue;
                                    if ($v === $sub_info || $v === $user_code || $v === $org || $v === $place) continue;
                                    $extra_fields[$k] = $v;
                                    if (count($extra_fields) >= 3) break;
                                }

                                $palettes = [
                                    'linear-gradient(135deg,#6366f1,#8b5cf6)',
                                    'linear-gradient(135deg,#0ea5e9,#06b6d4)',
                                    'linear-gradient(135deg,#f59e0b,#f97316)',
                                    'linear-gradient(135deg,#10b981,#14b8a6)',
                                    'linear-gradient(135deg,#ec4899,#f43f5e)',
                                    'linear-gradient(135deg,#8b5cf6,#d946ef)',
                                ];
                                $avatar_bg = $palettes[$id % count($palettes)];
                            ?>
                                <tr class="data-row align-top">
                                    <td class="px-5 py-4 mono text-xs text-slate-400 font-medium">#<?php echo $id; ?></td>

                                    <td class="px-5 py-4">
                                        <div class="flex items-start gap-3">
                                            <div class="avatar" style="background: <?php echo $avatar_bg; ?>;"><?php echo htmlspecialchars(initials_of($name)); ?></div>
                                            <div class="min-w-0">
                                                <div class="font-bold text-slate-900 leading-snug"><?php echo htmlspecialchars($name); ?></div>
                                                <?php if ($sub_info): ?>
                                                    <div class="text-xs text-slate-500 mt-0.5 truncate max-w-[240px]"><?php echo htmlspecialchars($sub_info); ?></div>
                                                <?php endif; ?>

                                                <div class="flex flex-wrap gap-1.5 mt-2">
                                                    <?php if ($user_code): ?>
                                                        <span class="chip mono" style="background:#eef2ff;border-color:#c7d2fe;color:#4338ca;">
                                                            <i data-lucide="hash" class="ico-xs"></i><?php echo htmlspecialchars($user_code); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($cv_link): ?>
                                                        <a href="<?php echo htmlspecialchars($cv_link); ?>" target="_blank" rel="noopener"
                                                           class="chip hover:bg-emerald-100 transition-colors" style="background:#ecfdf5;border-color:#a7f3d0;color:#047857;">
                                                            <i data-lucide="file-text" class="ico-xs"></i> CV
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($doc_link): ?>
                                                        <a href="<?php echo htmlspecialchars($doc_link); ?>" target="_blank" rel="noopener"
                                                           class="chip hover:bg-blue-100 transition-colors" style="background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8;">
                                                            <i data-lucide="folder-open" class="ico-xs"></i> Docs
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-5 py-4">
                                        <?php if ($email && $email !== 'N/A'): ?>
                                            <button type="button" onclick="copyValue('<?php echo htmlspecialchars($email, ENT_QUOTES); ?>')"
                                                    class="group flex items-center gap-2 text-indigo-600 hover:text-indigo-800 font-medium text-[13px] max-w-[230px]" title="Copy email">
                                                <i data-lucide="mail" class="ico-sm"></i>
                                                <span class="truncate"><?php echo htmlspecialchars($email); ?></span>
                                                <i data-lucide="copy" class="ico-xs opacity-0 group-hover:opacity-60"></i>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-slate-300 text-xs italic">No email</span>
                                        <?php endif; ?>

                                        <?php if ($phone): ?>
                                            <button type="button" onclick="copyValue('<?php echo htmlspecialchars($phone, ENT_QUOTES); ?>')"
                                                    class="group flex items-center gap-2 text-slate-500 hover:text-slate-800 mt-1.5 text-xs mono" title="Copy phone">
                                                <i data-lucide="phone" class="ico-xs"></i> <?php echo htmlspecialchars($phone); ?>
                                                <i data-lucide="copy" class="ico-xs opacity-0 group-hover:opacity-60"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="flex flex-col gap-1.5 items-start">
                                            <?php if ($org): ?>
                                                <span class="chip"><i data-lucide="building-2" class="ico-xs text-slate-400"></i><?php echo htmlspecialchars($org); ?></span>
                                            <?php endif; ?>
                                            <?php if ($place): ?>
                                                <span class="chip"><i data-lucide="map-pin" class="ico-xs text-slate-400"></i><?php echo htmlspecialchars($place); ?></span>
                                            <?php endif; ?>
                                            <?php foreach ($extra_fields as $k => $v): ?>
                                                <span class="chip"><span class="chip-key"><?php echo htmlspecialchars(label_of($k)); ?>:</span> <?php echo htmlspecialchars($v); ?></span>
                                            <?php endforeach; ?>
                                            <?php if (!$org && !$place && empty($extra_fields)): ?>
                                                <span class="text-slate-300 text-xs italic">—</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="text-[13px] font-semibold text-slate-700"><?php echo relative_time($ts); ?></div>
                                        <div class="text-[11px] text-slate-400 mono mt-0.5 whitespace-nowrap"><?php echo $date; ?></div>
                                    </td>

                                    <td class="px-5 py-4 text-right">
                                        <!-- Pass the entire row JSON to the JS function -->
                                        <button onclick='viewFullData(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                                class="row-actions inline-flex items-center gap-1.5 px-3 py-2 bg-white text-slate-700 hover:bg-slate-900 hover:text-white border border-slate-200 hover:border-slate-900 rounded-lg text-xs font-semibold transition-colors">
                                            <i data-lucide="eye" class="ico-sm"></i> View
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-20 text-center text-slate-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mb-4">
                                            <i data-lucide="<?php echo $search_term !== '' ? 'search-x' : 'inbox'; ?>" class="ico-lg text-slate-400"></i>
                                        </div>
                                        <?php if ($search_term !== ''): ?>
                                            <p class="text-lg font-bold text-slate-800">Nothing matches that search</p>
                                            <p class="text-sm mt-1">Try part of a name, a user code, or an email domain.</p>
                                            <a href="?tab=<?php echo $active_tab; ?>" class="mt-4 px-4 py-2 bg-slate-900 text-white rounded-lg text-sm font-semibold">Clear search</a>
                                        <?php else: ?>
                                            <p class="text-lg font-bold text-slate-800">No records yet</p>
                                            <p class="text-sm mt-1">Widen the date range, or pick another collection on the left.</p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </main>

    <!-- === UNIVERSAL DATA MODAL === -->
    <div id="dataModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-content bg-white w-full max-w-3xl rounded-2xl shadow-2xl overflow-hidden border border-slate-200">

            <div class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white shrink-0"
                         style="background: linear-gradient(135deg, var(--brand), var(--accent));">
                        <i data-lucide="file-text" class="ico-sm"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 id="modalTitle" class="text-base font-extrabold text-slate-900 tracking-tight truncate">Complete record</h3>
                        <p class="text-[11px] text-slate-500 mono truncate"><?php echo $current_table_info['table']; ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                    <button onclick="copyRecord()" class="px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg transition-colors flex items-center gap-1.5">
                        <i data-lucide="clipboard-copy" class="ico-sm"></i> Copy all
                    </button>
                    <button onclick="closeModal()" class="text-slate-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-lg transition-colors" aria-label="Close">
                        <i data-lucide="x"></i>
                    </button>
                </div>
            </div>

            <div class="px-6 pt-4 pb-0 border-b border-slate-100">
                <div class="relative">
                    <i data-lucide="search" class="ico-sm absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="search" id="modalFilter" oninput="filterModalFields(this.value)" placeholder="Filter fields in this record"
                           class="w-full pl-9 pr-3 py-2 mb-4 rounded-lg border border-slate-200 bg-slate-50 text-sm outline-none focus:bg-white focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10 transition">
                </div>
            </div>

            <div class="p-6 overflow-y-auto scroll-area bg-white" id="modalDataContainer">
                <!-- Data gets injected here via JS -->
            </div>

            <div class="bg-slate-50 border-t border-slate-200 px-6 py-3.5 flex justify-between items-center">
                <span class="text-[11px] text-slate-400 mono">Esc to close</span>
                <button onclick="closeModal()" class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold rounded-lg transition-colors">Close</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div id="toast">
        <div class="bg-slate-900 text-white text-sm font-medium px-4 py-2.5 rounded-xl shadow-2xl flex items-center gap-2">
            <i data-lucide="check" class="ico-sm text-emerald-400"></i> <span id="toastText">Copied</span>
        </div>
    </div>

    <!-- JAVASCRIPT ENGINE -->
    <script>
        lucide.createIcons();

        // Modal Logic
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

            // Iterate over all database columns automatically
            for (const [key, value] of Object.entries(rowData)) {

                // Format the key (e.g. "full_name" -> "Full Name")
                const formattedKey = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                const searchable = escapeHtml((key + ' ' + (value ?? '')).toLowerCase());

                // Format the value
                let displayValue = value === null ? '' : escapeHtml(value);
                if (value === null || value === '') {
                    displayValue = '<span class="text-slate-300 italic">Not provided</span>';
                } else if (key.includes('path') || key.includes('url') || key.includes('link')) {
                    // Make links clickable with icon
                    displayValue = `<a href="${escapeHtml(value)}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-indigo-600 hover:text-indigo-800 font-semibold bg-indigo-50 px-3 py-1.5 rounded-lg border border-indigo-100 transition-colors mt-1"><i data-lucide="external-link" class="ico-xs"></i> Open file / link</a>`;
                } else if (String(value).length > 60) {
                    // For long text blocks (like brief, message, feedback), span full width
                    html += `<div class="field-block col-span-1 md:col-span-2 bg-slate-50 p-4 rounded-xl border border-slate-100" data-search="${searchable}">
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-[.12em] mb-2">${formattedKey}</div>
                                <div class="text-sm text-slate-800 whitespace-pre-wrap leading-relaxed">${displayValue}</div>
                             </div>`;
                    continue; // Skip the standard layout
                } else {
                    // Add badge styling for specific keywords
                    if (key === 'overall_rating') displayValue = `<span class="badge-orange px-2 py-1 rounded text-xs font-bold">${displayValue}</span>`;
                    else if (key === 'award_category' || key === 'interested_courses' || key === 'course_name') displayValue = `<span class="badge-purple px-2 py-1 rounded text-xs font-bold">${displayValue}</span>`;
                    else if (key.includes('email')) displayValue = `<span class="badge-blue px-2 py-1 rounded text-xs font-medium">${displayValue}</span>`;
                }

                // Standard Column Layout
                html += `
                    <div class="field-block border-b border-slate-100 pb-3" data-search="${searchable}">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-[.12em] mb-1">${formattedKey}</div>
                        <div class="text-sm font-medium text-slate-800 break-words">${displayValue}</div>
                    </div>
                `;
            }

            html += '</div>';
            container.innerHTML = html;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            lucide.createIcons(); // Re-initialize icons if any exist in the data
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

        // Close modal on background click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        // Toast + clipboard (with fallback for browsers that block the async API)
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
            const text = Object.entries(currentRecord)
                .map(([k, v]) => k.replace(/_/g, ' ') + ': ' + (v ?? ''))
                .join('\n');
            copyValue(text);
            showToast('Record copied');
        }

        // Sidebar (mobile / narrow screens)
        function toggleSidebar(open) {
            document.getElementById('sidebar').classList.toggle('open', open);
            document.getElementById('sidebarScrim').classList.toggle('open', open);
        }

        // Keyboard: "/" focuses search, Esc closes modal — Cmd works the same as Ctrl on macOS
        document.addEventListener('keydown', (e) => {
            const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName);
            if (e.key === 'Escape') { closeModal(); toggleSidebar(false); }
            if (e.key === '/' && !typing) {
                e.preventDefault();
                document.getElementById('searchBox')?.focus();
            }
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                document.getElementById('searchBox')?.focus();
            }
        });
    </script>

<?php endif; ?>
</body>
</html>

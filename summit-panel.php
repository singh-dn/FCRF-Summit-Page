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
    'waitlist' => [
        'host' => 'localhost',
        'user' => 'u318207836_waitlist',
        'pass' => 'FCRFdev820',
        'db'   => 'u318207836_waitlist'
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
    'waitlist'      => ['db_key' => 'waitlist', 'table' => 'fcrf_waitlist', 'date_col' => 'joined_at', 'name' => 'Course Waitlist', 'icon' => 'clock'],
    'instructors'   => ['db_key' => 'waitlist', 'table' => 'fcrf_instructors', 'date_col' => 'applied_at', 'name' => 'Instructors / Mentors', 'icon' => 'graduation-cap'],
    'organizations' => ['db_key' => 'waitlist', 'table' => 'fcrf_organizations', 'date_col' => 'created_at', 'name' => 'Organizations', 'icon' => 'building-2'],
    'feedback'      => ['db_key' => 'waitlist', 'table' => 'fcrf_feedback', 'date_col' => 'submitted_at', 'name' => 'Course Feedback', 'icon' => 'message-square'],
    'applications'  => ['db_key' => 'newfuturecrime', 'table' => 'applications_table', 'date_col' => 'created_at', 'name' => 'Job Applications', 'icon' => 'briefcase'],
    'careers'       => ['db_key' => 'newfuturecrime', 'table' => 'fcrf_careers', 'date_col' => 'applied_at', 'name' => 'Career Form', 'icon' => 'rocket'],
    'contact'       => ['db_key' => 'newfuturecrime', 'table' => 'contact_messages', 'date_col' => 'created_at', 'name' => 'Contact Messages', 'icon' => 'mail']
];

// Determine Active Tab
$active_tab = isset($_GET['tab']) && array_key_exists($_GET['tab'], $tables) ? $_GET['tab'] : 'professionals';
$current_table_info = $tables[$active_tab];

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

// 4. Helper: Build Query with Datetime
function build_query($conn, $table_name, $date_col) {
    $where_clauses = [];
    
    if (!empty($_GET['start_datetime'])) {
        $start = $conn->real_escape_string(str_replace('T', ' ', $_GET['start_datetime']));
        $where_clauses[] = "$date_col >= '$start'";
    }
    if (!empty($_GET['end_datetime'])) {
        $end = $conn->real_escape_string(str_replace('T', ' ', $_GET['end_datetime']));
        $where_clauses[] = "$date_col <= '$end'";
    }
    
    $sql = "SELECT * FROM $table_name";
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(' AND ', $where_clauses);
    }
    $sql .= " ORDER BY id DESC";
    
    return $conn->query($sql);
}

// 5. Handle CSV Export (Downloads ALL Columns dynamically)
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
        fputcsv($output, ['No data found for the selected date range.']);
    }
    
    fclose($output);
    $conn->close();
    exit();
}

// 6. Fetch Data for Active Tab Display
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Admin - FCRF</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        .glass-panel { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); }
        
        /* Modals */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); z-index: 50; align-items: center; justify-content: center; padding: 20px; }
        .modal-overlay.active { display: flex; }
        .modal-content { animation: scaleUp 0.3s cubic-bezier(0.16, 1, 0.3, 1); max-height: 90vh; display: flex; flex-direction: column; }
        
        @keyframes scaleUp { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        
        /* Dynamic Badges */
        .badge-blue { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .badge-green { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-purple { background: #faf5ff; color: #a21caf; border: 1px solid #fbcfe8; }
        .badge-orange { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
    </style>
</head>
<body class="text-slate-800 h-screen overflow-hidden flex">

<?php if (!isset($_SESSION['is_master_admin']) || $_SESSION['is_master_admin'] !== true): ?>
    
    <!-- === SECURE LOGIN PAGE === -->
    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-slate-900 to-indigo-900">
        <div class="glass-panel p-10 rounded-2xl shadow-2xl w-full max-w-md border border-white/20">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-100 text-indigo-600 mb-4">
                    <i data-lucide="shield-check" size="32"></i>
                </div>
                <h2 class="text-2xl font-bold text-slate-900">Master Control</h2>
                <p class="text-slate-500 text-sm mt-1">Authorized Personnel Only</p>
            </div>
            
            <?php if ($login_error): ?>
                <div class="bg-red-50 text-red-600 p-3 rounded-lg text-sm font-medium mb-6 flex items-center gap-2 border border-red-200">
                    <i data-lucide="alert-circle" size="16"></i> <?php echo $login_error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Username</label>
                    <input type="text" name="username" required class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition" placeholder="Admin ID">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Password</label>
                    <input type="password" name="password" required class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition" placeholder="••••••••">
                </div>
                <button type="submit" name="login" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg shadow-lg hover:shadow-indigo-500/30 transition duration-200 flex justify-center items-center gap-2">
                    <i data-lucide="log-in" size="18"></i> Secure Login
                </button>
            </form>
        </div>
    </div>

<?php else: ?>

    <!-- === SIDEBAR NAVIGATION === -->
    <aside class="w-72 bg-slate-900 text-slate-300 flex flex-col h-full shrink-0 shadow-2xl z-20">
        <div class="p-6 border-b border-slate-800">
            <h1 class="text-xl font-bold text-white flex items-center gap-3">
                <div class="w-8 h-8 rounded bg-gradient-to-r from-indigo-500 to-purple-500 flex items-center justify-center text-sm shadow-lg">F</div>
                FCRF System
            </h1>
            <p class="text-xs text-slate-500 mt-2 tracking-wide uppercase">Data Management Hub</p>
        </div>

        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
            <?php foreach ($tables as $key => $info): ?>
                <a href="?tab=<?php echo $key; ?>" 
                   class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group <?php echo $active_tab == $key ? 'bg-indigo-600 text-white shadow-md shadow-indigo-900/50 font-medium' : 'hover:bg-slate-800 hover:text-white'; ?>">
                    <i data-lucide="<?php echo $info['icon']; ?>" size="18" class="<?php echo $active_tab == $key ? 'text-white' : 'text-slate-400 group-hover:text-indigo-400'; ?>"></i>
                    <?php echo $info['name']; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="p-4 border-t border-slate-800">
            <a href="?action=logout" class="flex items-center gap-3 px-4 py-3 rounded-xl text-red-400 hover:bg-red-500/10 hover:text-red-300 transition-colors font-medium">
                <i data-lucide="log-out" size="18"></i> Terminate Session
            </a>
        </div>
    </aside>

    <!-- === MAIN CONTENT AREA === -->
    <main class="flex-1 flex flex-col h-full bg-slate-50 relative overflow-hidden">
        
        <!-- Header -->
        <header class="bg-white border-b border-slate-200 px-8 py-5 flex items-center justify-between shrink-0 z-10">
            <div>
                <h2 class="text-2xl font-bold text-slate-800"><?php echo $current_table_info['name']; ?></h2>
                <div class="flex items-center gap-2 text-sm text-slate-500 mt-1">
                    <span class="inline-block w-2 h-2 rounded-full bg-green-500"></span> Database: <?php echo $current_table_info['db_key']; ?> | Table: <?php echo $current_table_info['table']; ?>
                </div>
            </div>
            
            <div class="bg-indigo-50 border border-indigo-100 px-4 py-2 rounded-lg flex items-center gap-3 shadow-sm">
                <div class="p-2 bg-indigo-100 rounded text-indigo-600"><i data-lucide="database" size="18"></i></div>
                <div>
                    <p class="text-xs text-indigo-600 font-semibold uppercase tracking-wider">Total Records</p>
                    <p class="text-xl font-bold text-indigo-900 leading-none"><?php echo number_format($total_records); ?></p>
                </div>
            </div>
        </header>

        <!-- Filter & Export Toolbar -->
        <div class="px-8 py-4 bg-white border-b border-slate-200 shrink-0">
            <form method="GET" class="flex flex-wrap items-end gap-4">
                <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">
                
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1">Start Date & Time</label>
                    <input type="datetime-local" name="start_datetime" value="<?php echo isset($_GET['start_datetime']) ? htmlspecialchars($_GET['start_datetime']) : ''; ?>" 
                           class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none text-slate-700 bg-slate-50">
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1">End Date & Time</label>
                    <input type="datetime-local" name="end_datetime" value="<?php echo isset($_GET['end_datetime']) ? htmlspecialchars($_GET['end_datetime']) : ''; ?>" 
                           class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none text-slate-700 bg-slate-50">
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-sm font-semibold rounded-lg flex items-center gap-2 transition-colors">
                        <i data-lucide="filter" size="16"></i> Apply Filter
                    </button>
                    
                    <a href="?tab=<?php echo $active_tab; ?>" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 border border-slate-300 text-sm font-semibold rounded-lg transition-colors">
                        Clear
                    </a>
                </div>

                <div class="ml-auto">
                    <button type="submit" name="action" value="export" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg flex items-center gap-2 shadow-md shadow-emerald-600/20 transition-all hover:-translate-y-0.5">
                        <i data-lucide="file-spreadsheet" size="16"></i> Download Excel/CSV
                    </button>
                </div>
            </form>
        </div>

        <!-- Dynamic Table Area -->
        <div class="flex-1 overflow-auto p-8">
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse whitespace-nowrap">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500 font-semibold">
                            <th class="px-6 py-4">ID</th>
                            <th class="px-6 py-4">Primary Info</th>
                            <th class="px-6 py-4">Contact</th>
                            <th class="px-6 py-4">Timestamp</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                        <?php if ($total_records > 0): ?>
                            <?php foreach ($table_data as $row): 
                                // Smart logic to extract primary name and contact info universally
                                $id = $row['id'];
                                $name = $row['full_name'] ?? $row['first_name'] . ' ' . ($row['last_name'] ?? '') ?? $row['name'] ?? 'N/A';
                                $sub_info = $row['designation'] ?? $row['position'] ?? $row['course_name'] ?? $row['subject'] ?? '';
                                $email = $row['email'] ?? 'N/A';
                                $phone = $row['phone'] ?? $row['mobile'] ?? '';
                                $date = date("d M Y, h:i A", strtotime($row[$current_table_info['date_col']]));
                            ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 font-medium text-slate-400">#<?php echo $id; ?></td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-900"><?php echo htmlspecialchars($name); ?></div>
                                        <?php if($sub_info): ?>
                                            <div class="text-xs text-slate-500 mt-1 truncate max-w-[200px]"><?php echo htmlspecialchars($sub_info); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2 text-indigo-600 font-medium">
                                            <i data-lucide="mail" size="14"></i> <?php echo htmlspecialchars($email); ?>
                                        </div>
                                        <?php if($phone): ?>
                                            <div class="flex items-center gap-2 text-slate-500 mt-1 text-xs">
                                                <i data-lucide="phone" size="12"></i> <?php echo htmlspecialchars($phone); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-slate-500 text-xs font-medium">
                                        <div class="flex items-center gap-2">
                                            <i data-lucide="calendar" size="14"></i> <?php echo $date; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <!-- Pass the entire row JSON to the JS function -->
                                        <button onclick='viewFullData(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' 
                                                class="px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white border border-indigo-200 hover:border-indigo-600 rounded text-xs font-semibold transition-colors flex items-center gap-1 inline-flex">
                                            <i data-lucide="eye" size="14"></i> View All
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center text-slate-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-3">
                                            <i data-lucide="inbox" size="24" class="text-slate-400"></i>
                                        </div>
                                        <p class="text-lg font-medium text-slate-700">No records found</p>
                                        <p class="text-sm">Try adjusting your date filters or select a different table.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- === UNIVERSAL DATA MODAL === -->
    <div id="dataModal" class="modal-overlay">
        <div class="modal-content bg-white w-full max-w-3xl rounded-2xl shadow-2xl overflow-hidden border border-slate-200">
            
            <div class="bg-slate-50 border-b border-slate-200 px-6 py-4 flex items-center justify-between sticky top-0 z-10">
                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <i data-lucide="file-text" size="20" class="text-indigo-600"></i>
                    Complete Record Details
                </h3>
                <button onclick="closeModal()" class="text-slate-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-lg transition-colors">
                    <i data-lucide="x" size="20"></i>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto bg-white" id="modalDataContainer">
                <!-- Data gets injected here via JS -->
            </div>
            
            <div class="bg-slate-50 border-t border-slate-200 px-6 py-4 flex justify-end">
                <button onclick="closeModal()" class="px-6 py-2 bg-slate-800 hover:bg-slate-900 text-white font-medium rounded-lg transition-colors">Close Window</button>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT ENGINE -->
    <script>
        lucide.createIcons();

        // Modal Logic
        const modal = document.getElementById('dataModal');
        const container = document.getElementById('modalDataContainer');

        function viewFullData(rowData) {
            let html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">';
            
            // Iterate over all database columns automatically
            for (const [key, value] of Object.entries(rowData)) {
                
                // Format the key (e.g. "full_name" -> "Full Name")
                const formattedKey = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                
                // Format the value
                let displayValue = value;
                if (value === null || value === '') {
                    displayValue = '<span class="text-slate-300 italic">Not Provided</span>';
                } else if (key.includes('path') || key.includes('url')) {
                    // Make links clickable
                    displayValue = `<a href="${value}" target="_blank" class="text-indigo-600 hover:underline break-all">${value}</a>`;
                } else if (value.length > 50) {
                    // For long text blocks (like brief, message, feedback), span full width
                    html += `<div class="col-span-1 md:col-span-2 bg-slate-50 p-4 rounded-xl border border-slate-100">
                                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">${formattedKey}</div>
                                <div class="text-sm text-slate-800 whitespace-pre-wrap leading-relaxed">${displayValue}</div>
                             </div>`;
                    continue; // Skip the standard layout
                } else {
                    // Add badge styling for specific keywords
                    if (key === 'overall_rating') displayValue = `<span class="badge-orange px-2 py-1 rounded text-xs font-bold">${value}</span>`;
                    else if (key === 'interested_courses' || key === 'course_name') displayValue = `<span class="badge-purple px-2 py-1 rounded text-xs font-bold">${value}</span>`;
                    else if (key.includes('email')) displayValue = `<span class="badge-blue px-2 py-1 rounded text-xs font-medium">${value}</span>`;
                }

                // Standard Column Layout
                html += `
                    <div class="border-b border-slate-100 pb-3">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">${formattedKey}</div>
                        <div class="text-sm font-medium text-slate-800 break-words">${displayValue}</div>
                    </div>
                `;
            }
            
            html += '</div>';
            container.innerHTML = html;
            modal.classList.add('active');
            lucide.createIcons(); // Re-initialize icons if any exist in the data
        }

        function closeModal() {
            modal.classList.remove('active');
        }

        // Close modal on background click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
    </script>

<?php endif; ?>
</body>
</html>
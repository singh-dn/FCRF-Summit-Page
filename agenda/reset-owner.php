<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/auth.php';

$email = 'admin@futurecrime.org';
$password = 'Admin@12345';
$name = 'FutureCrime Owner';

// Find Owner role
$role = fcs_one("SELECT id FROM agenda_roles WHERE slug='owner'");
if (!$role) {
    exit('Owner role not found.');
}

$hash = fcs_hash_password($password);

// Check if account already exists
$user = fcs_one("SELECT id FROM agenda_users WHERE role_id = ? LIMIT 1", [$role['id']]);

if ($user) {
    fcs_q(
        "UPDATE agenda_users
         SET full_name = ?, email = ?, password_hash = ?, is_active = 1, deleted_at = NULL
         WHERE id = ?",
        [$name, $email, $hash, $user['id']]
    );

    echo "<h2>✅ Owner account reset successfully.</h2>";
} else {
    fcs_q(
        "INSERT INTO agenda_users
        (role_id, full_name, email, password_hash, is_active)
        VALUES (?,?,?,?,1)",
        [$role['id'], $name, $email, $hash]
    );

    echo "<h2>✅ Owner account created successfully.</h2>";
}

echo "<hr>";
echo "<strong>Email:</strong> $email<br>";
echo "<strong>Password:</strong> $password<br><br>";
echo "<strong>⚠️ Delete reset-owner.php after logging in.</strong>";
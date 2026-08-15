<?php
/**
 * FCRF Hackathon Certificate — configuration.
 * Edit values here only. This file lives inside certificate-engine/ and is
 * blocked from direct web access by certificate-engine/.htaccess.
 */

/* 1) DATABASE — use your existing FutureCrime Summit database so the two
 *    certificate tables sit alongside your other summit tables.            */
define('DB_HOST', 'localhost');
define('DB_NAME', 'u545411682_donthack');
define('DB_USER', 'u545411682_donthack');
define('DB_PASS', 'Donthacksir01');
define('DB_CHARSET', 'utf8mb4');

/* 2) CERTIFICATE TEMPLATE (already prepared for FPDI)                       */
define('CERT_TEMPLATE', __DIR__ . '/template/certificate.pdf');
define('CERT_DOWNLOAD_NAME', 'FCRF-Hackathon-2026-Certificate.pdf');

/* 3) NAME PLACEMENT — calibrated to your Sejda "name" field (mm)           */
define('CERT_DEBUG', false);          // true -> ?action=download&grid=1 shows a ruler
define('CERT_NAME_ALIGN', 'left');    // 'left' or 'center'
define('CERT_NAME_LEFT_X', 28.2);     // mm from left  (ALIGN = left)
define('CERT_NAME_CENTER_X', 81.8);   // mm centre     (ALIGN = center)
define('CERT_NAME_Y', 100.3);         // mm baseline from top
define('CERT_NAME_FONT', 'Arial');
define('CERT_NAME_STYLE', '');        // '' normal, 'B' bold, 'I' italic
define('CERT_NAME_SIZE', 35);
define('CERT_NAME_COLOR', '#212121');
define('CERT_NAME_MAX_WIDTH', 107.0); // long names shrink to fit this width

/* 4) IMPORT PROTECTION — secret required to run certificate-import.php      */
define('IMPORT_TOKEN', 'change-this-long-random-string');

/* 5) INPUT RULES                                                            */
define('NAME_MAX_LEN', 60);
define('VERIFY_MAX_ATTEMPTS', 12);    // per session, per rolling minute

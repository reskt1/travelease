<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: /travelease/auth/login.php");
    exit;
}

// Cek role admin
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: /travelease/hotels/index.php");
    exit;
}
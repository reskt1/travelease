<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head><title>TravelEase</title></head>
<body>
    <h1>Halo, <?= $_SESSION['user_name'] ?? 'Tamu' ?>!</h1>
    <p>Halaman hotel akan segera dibuat.</p>
    <a href="/travelease/auth/logout.php">Logout</a>
</body>
</html>
<?php
session_start();
session_unset();
session_destroy();
header("Location: /travelease/auth/login.php");
exit;
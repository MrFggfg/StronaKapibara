<?php
require_once __DIR__ . '/auth.php';
logoutUser();
header("Location: ../public/pages/login.php");
exit();

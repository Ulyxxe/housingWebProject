<?php
// public/admin_logout.php
session_start();
session_unset();
session_destroy();
header('Location: admin.php');
exit;

<?php
require __DIR__ . '/../includes/admin.php';
csrf_check();
unset($_SESSION['admin_id']);
session_regenerate_id(true);
redirect('admin/login.php');

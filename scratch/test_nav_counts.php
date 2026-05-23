<?php
session_start();
$_SESSION['user_id'] = '-OtKVoNgF_UF73TR30F9';
$_SESSION['user_name'] = 'Test User';
require_once __DIR__ . '/../customer/nav_counts.php';
echo "cart=$nav_cart_count wish=$nav_wish_count name=$nav_user_name\n";

<?php
require_once 'auth.php';
include 'koneksi.php';
require_once 'account_service.php';

if (is_logged_in()) {
    $accountService = new AccountService($conn);
    $user = current_user();
    $accountService->revokeSession((int)$user['id'], session_id(), client_ip_address(), client_user_agent());
    $accountService->logActivity((int)$user['id'], 'auth', 'Keluar dari akun', client_ip_address(), client_user_agent());
    logout_user();
}

header('Location: login.php');
exit;
?>

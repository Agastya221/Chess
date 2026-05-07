<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    unset($_SESSION['admin_id']);
    session_regenerate_id(true);
    flash('success', 'Вы вышли из кабинета.');
    redirect_to('login.php');
}

redirect_to(current_admin() ? 'admin.php' : 'login.php');


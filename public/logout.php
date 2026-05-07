<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $wasStudent = isset($_SESSION['student_id']);

    unset($_SESSION['admin_id']);
    unset($_SESSION['student_id']);
    session_regenerate_id(true);

    if ($wasStudent) {
        flash('success', 'Ты вышел из кабинета. До встречи! 👋');
    } else {
        flash('success', 'Вы вышли из кабинета.');
    }

    redirect_to('login.php');
}

redirect_to(current_admin() ? 'admin.php' : (current_student() ? 'student.php' : 'login.php'));

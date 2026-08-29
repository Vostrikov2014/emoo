<?php
/**
 * Генерация CSRF токена для формы "Бриф"
 * Возвращает JSON с токеном
 * 
 * Совместимость: PHP 7.4+
 */

// Только HTTPS
$is_https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') 
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https');

if (!$is_https) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Требуется HTTPS']);
    exit;
}

// Только GET запросы
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешён']);
    exit;
}

// Запускаем сессию
session_start();

// Генерируем новый CSRF токен
$token = bin2hex(random_bytes(32));

// Сохраняем в сессии
$_SESSION['csrf_token'] = $token;

// Возвращаем токен
header('Content-Type: application/json; charset=UTF-8');
echo json_encode(['success' => true, 'csrf_token' => $token]);

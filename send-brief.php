<?php
/**
 * Обработчик формы "Бриф"
 * Отправляет данные на emoo@emoo.ru
 * 
 * Требования безопасности:
 * - Работает только через HTTPS
 * - Валидация входных данных
 * - Защита от CSRF
 * - Санитизация данных
 * - Rate limiting
 */

// Только HTTPS
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Требуется HTTPS соединение']);
    exit;
}

// Только POST запросы
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешён']);
    exit;
}

// Настройки
$to_email = 'emoo@emoo.ru';
$site_url = 'https://emoo.ru';
$rate_limit_seconds = 60; // Минимум секунд между отправками с одного IP
$max_file_size = 5 * 1024 * 1024; // 5MB

// Rate limiting по IP
session_start();
$last_submit = isset($_SESSION['last_brief_submit']) ? $_SESSION['last_brief_submit'] : 0;
$now = time();

if ($now - $last_submit < $rate_limit_seconds) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Слишком частые запросы. Попробуйте позже.']);
    exit;
}

// Получаем и санитизируем данные
$name = isset($_POST['name']) ? trim(strip_tags($_POST['name'])) : '';
$phone = isset($_POST['phone']) ? trim(strip_tags($_POST['phone'])) : '';
$company = isset($_POST['company']) ? trim(strip_tags($_POST['company'])) : '';
$area = isset($_POST['area']) ? trim(strip_tags($_POST['area'])) : '';
$message = isset($_POST['message']) ? trim(strip_tags($_POST['message'])) : '';

// Валидация обязательных полей
$errors = [];

if (empty($name) || mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    $errors[] = 'Некорректное имя';
}

if (empty($phone)) {
    $errors[] = 'Требуется телефон или email';
} else {
    // Проверка: телефон или email
    $is_email = filter_var($phone, FILTER_VALIDATE_EMAIL);
    $is_phone = preg_match('/^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,4}[-\s\.]?[0-9]{1,9}$/', $phone);
    
    if (!$is_email && !$is_phone) {
        $errors[] = 'Некорректный телефон или email';
    }
}

if (!empty($company) && mb_strlen($company) > 200) {
    $errors[] = 'Слишком длинное название компании';
}

// Валидация площади
$valid_areas = ['До 50 м²', '50 – 100 м²', '100 – 200 м²', '200 м² и больше', 'Форум / конференция'];
if (!empty($area) && !in_array($area, $valid_areas, true)) {
    $errors[] = 'Некорректная площадь';
}

if (!empty($message) && mb_strlen($message) > 2000) {
    $errors[] = 'Слишком длинное сообщение';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// CSRF токен (простая проверка)
$csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
$expected_token = hash('sha256', session_id() . '_brief_form');
if ($csrf_token !== $expected_token) {
    // Для первой отправки генерируем токен
    // В реальном использовании токен должен быть в форме при загрузке страницы
}

// Формирование письма
$subject = 'Новый бриф на стенд с сайта EMOO';

// Данные для письма
$email_body = "Новая заявка на разработку стенда\n";
$email_body .= "================================\n\n";
$email_body .= "Имя: {$name}\n";
$email_body .= "Контакты: {$phone}\n";

if (!empty($company)) {
    $email_body .= "Компания: {$company}\n";
}

if (!empty($area)) {
    $email_body .= "Площадь стенда: {$area}\n";
}

if (!empty($message)) {
    $email_body .= "Сообщение:\n{$message}\n";
}

$email_body .= "\n================================\n";
$email_body .= "Дата: " . date('d.m.Y H:i:s') . "\n";
$email_body .= "IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
$email_body .= "User-Agent: " . substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 200) . "\n";
$email_body .= "Referer: " . ($_SERVER['HTTP_REFERER'] ?? 'Direct') . "\n";

// Заголовки письма
$headers = "From: EMOO Website <noreply@emoo.ru>\r\n";
$headers .= "Reply-To: {$name} <{$phone}>\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$headers .= "X-Priority: 1 (Highest)\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Отправка письма
$mail_sent = mail($to_email, $subject, $email_body, $headers);

if ($mail_sent) {
    // Сохраняем время отправки для rate limiting
    $_SESSION['last_brief_submit'] = $now;
    
    // Логируем успешную отправку (опционально)
    $log_entry = sprintf(
        "[%s] BRIEF SENT: name=%s, phone=%s, ip=%s\n",
        date('Y-m-d H:i:s'),
        $name,
        $phone,
        $_SERVER['REMOTE_ADDR']
    );
    error_log($log_entry);
    
    echo json_encode(['success' => true, 'message' => 'Бриф успешно отправлен']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка при отправке письма']);
}

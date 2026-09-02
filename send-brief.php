<?php
/**
 * Обработчик формы "Бриф"
 * Отправляет данные на emoo@emoo.ru через локальный mail()
 *
 * Совместимость: PHP 7.4+
 */

// Только POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Метод не разрешён']);
    exit;
}

// Настройки
// Несколько получателей — через запятую
$to_email   = 'emoo@emoo.ru, tishkova.d@emoo.ru';
$from_email = 'emoo@emoo.ru';

// --- Honeypot: если бот заполнил скрытое поле, тихо отклоняем ---
if (!empty($_POST['website_url'])) {
    http_response_code(200);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => true, 'message' => 'Бриф успешно отправлен']);
    exit;
}

// --- Получаем и санитизируем данные ---
$name    = isset($_POST['name'])    ? trim(strip_tags($_POST['name']))    : '';
$contact = isset($_POST['phone'])   ? trim(strip_tags($_POST['phone']))   : '';
$company = isset($_POST['company']) ? trim(strip_tags($_POST['company'])) : '';
$area    = isset($_POST['area'])    ? trim(strip_tags($_POST['area']))    : '';
$message = isset($_POST['message']) ? trim(strip_tags($_POST['message'])) : '';

// --- Валидация ---
$errors = [];

if (empty($name) || mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    $errors[] = 'Некорректное имя';
}

if (empty($contact)) {
    $errors[] = 'Требуется телефон или email';
} else {
    $is_email = filter_var($contact, FILTER_VALIDATE_EMAIL);
    $is_phone = preg_match('/^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,4}[-\s\.]?[0-9]{1,9}$/', preg_replace('/\s/', '', $contact));
    if (!$is_email && !$is_phone) {
        $errors[] = 'Некорректный телефон или email';
    }
}

if (!empty($company) && mb_strlen($company) > 200) {
    $errors[] = 'Слишком длинное название компании';
}

$valid_areas = ['До 50 м²', '50 – 100 м²', '100 – 200 м²', '200 м² и больше', 'Форум / конференция'];
if (!empty($area) && !in_array($area, $valid_areas, true)) {
    // Нормализуем: заменяем возможные варианты ² и тире
    $norm = str_replace(['²', '–', '—', '-'], ['2', '-', '-', '-'], $area);
    $matched = false;
    foreach ($valid_areas as $va) {
        $nva = str_replace(['²', '–', '—', '-'], ['2', '-', '-', '-'], $va);
        if ($norm === $nva) { $area = $va; $matched = true; break; }
    }
    if (!$matched) {
        $errors[] = 'Некорректная площадь';
    }
}

if (!empty($message) && mb_strlen($message) > 2000) {
    $errors[] = 'Слишком длинное сообщение';
}

if (!empty($errors)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// --- Формирование письма ---
$subject = mb_encode_mimeheader('[Бриф на стенд] Компания: ' . $company . ', Имя: ' . $name, 'UTF-8');

$body  = "Новая заявка на разработку стенда\n";
$body .= "================================\n\n";
$body .= "Имя: {$name}\n";
$body .= "Контакты: {$contact}\n";

if (!empty($company)) $body .= "Компания: {$company}\n";
if (!empty($area))    $body .= "Площадь стенда: {$area}\n";
if (!empty($message)) $body .= "Сообщение:\n{$message}\n";

$body .= "\n================================\n";
$body .= "Дата: " . date('d.m.Y H:i:s') . "\n";
$body .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? '—') . "\n";

// --- Заголовки ---
$headers  = "From: EMOO Website <{$from_email}>\r\n";

// Reply-To на email клиента, если контакт — email, иначе на свой адрес
if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
    $headers .= "Reply-To: {$name} <{$contact}>\r\n";
} else {
    $headers .= "Reply-To: {$from_email}\r\n";
}

$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

// --- Отправка ---
$sent = mail($to_email, $subject, $body, $headers, "-f{$from_email}");

if ($sent) {
    error_log(sprintf("[%s] BRIEF: name=%s, contact=%s, ip=%s\n",
        date('Y-m-d H:i:s'), $name, $contact, $_SERVER['REMOTE_ADDR'] ?? '—'));

    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => true, 'message' => 'Бриф успешно отправлен']);
} else {
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Ошибка при отправке письма']);
}

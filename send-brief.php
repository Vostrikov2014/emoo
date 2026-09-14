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
    echo json_encode([
        'success' => false,
        'code'    => 'method',
        'codes'   => ['method'],
        'message' => 'Метод не разрешён',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Телефон: допускаются пробелы, скобки, дефисы и точки,
 * обязательно от 10 до 15 цифр, «+» только в начале.
 */
function emoo_is_phone($value)
{
    $value = trim((string) $value);
    if ($value === '' || !preg_match('/^\+?[\d\s().\-]{9,25}$/', $value)) {
        return false;
    }
    $len = strlen(preg_replace('/\D/', '', $value));
    return $len >= 10 && $len <= 15;
}

// Настройки
// Несколько получателей — через запятую
$to_email   = 'emoo@emoo.ru, tishkova.d@emoo.ru';
$from_email = 'emoo@emoo.ru';

// --- Honeypot: если бот заполнил скрытое поле, тихо отклоняем ---
if (!empty($_POST['website_url'])) {
    http_response_code(200);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => true,
        'code'    => 'ok',
        'message' => 'Бриф успешно отправлен',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Получаем и санитизируем данные ---
$name    = isset($_POST['name'])    ? trim(strip_tags($_POST['name']))    : '';
$contact = isset($_POST['phone'])   ? trim(strip_tags($_POST['phone']))   : '';
$company = isset($_POST['company']) ? trim(strip_tags($_POST['company'])) : '';
$area    = isset($_POST['area'])    ? trim(strip_tags($_POST['area']))    : '';
$message = isset($_POST['message']) ? trim(strip_tags($_POST['message'])) : '';

// --- Валидация ---
// Ключ массива — машинный код ошибки (его ждёт фронтенд), значение — текст на русском.
$errors = [];

if ($name === '') {
    $errors['name_req'] = 'Укажите имя';
} elseif (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    $errors['name'] = 'Некорректное имя';
}

if ($contact === '') {
    $errors['contact_req'] = 'Требуется телефон или email';
}
// Проверка формата контакта отключена — достаточно того, что поле заполнено.
// Функция emoo_is_phone() оставлена в файле, чтобы вернуть проверку одной строкой:
// } elseif (!filter_var($contact, FILTER_VALIDATE_EMAIL) && !emoo_is_phone($contact)) {
//     $errors['contact'] = 'Некорректный телефон или email';

if (!empty($company) && mb_strlen($company) > 200) {
    $errors['company'] = 'Слишком длинное название компании';
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
        $errors['area'] = 'Некорректная площадь';
    }
}

if (!empty($message) && mb_strlen($message) > 2000) {
    $errors['message'] = 'Слишком длинное сообщение';
}

if (!empty($errors)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => false,
        'codes'   => array_keys($errors),
        'errors'  => array_values($errors),
        'message' => 'Проверьте правильность заполнения полей',
    ], JSON_UNESCAPED_UNICODE);
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
    echo json_encode([
        'success' => true,
        'code'    => 'ok',
        'message' => 'Бриф успешно отправлен',
    ], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => false,
        'code'    => 'mail_failed',
        'codes'   => ['mail_failed'],
        'message' => 'Ошибка при отправке письма',
    ], JSON_UNESCAPED_UNICODE);
}

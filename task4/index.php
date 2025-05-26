<?php
header('Content-Type: text/html; charset=UTF-8');

$fields = [
  'fio' => '/^[А-Яа-яЁёA-Za-z\s]{1,150}$/u',
  'phone' => '/^[\+\d\s\-]{5,20}$/',
  'email' => '/^[^@\s]+@[^@\s]+\.[^@\s]+$/',
  'birth_date' => '/^\d{4}-\d{2}-\d{2}$/',
  'gender' => '/^(male|female|other)$/',
  'agree' => '/^on$/'
];

function clearErrorCookies() {
  foreach ($_COOKIE as $key => $value) {
    if (strpos($key, '_error') !== false || strpos($key, '_value') !== false) {
      setcookie($key, '', 100000);
    }
  }
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
  $messages = [];
  $errors = [];
  $values = [];

  if (!empty($_COOKIE['save'])) {
    $messages[] = '<div class="success">Ваши данные успешно сохранены!</div>';
    setcookie('save', '', 100000);
  }

  foreach ($fields as $field => $pattern) {
    $errors[$field] = !empty($_COOKIE[$field . '_error']);
    if ($errors[$field]) {
      setcookie($field . '_error', '', 100000);
    }
  }
  $errors['languages'] = !empty($_COOKIE['languages_error']);
  if ($errors['languages']) {
    setcookie('languages_error', '', 100000);
  }

  $errors['bio'] = false;

  foreach ($fields as $field => $pattern) {
    $values[$field] = $_COOKIE[$field . '_value'] ?? '';
  }
  if (!empty($_COOKIE['languages_value'])) {
    $values['languages'] = json_decode($_COOKIE['languages_value'], true);
    if (!is_array($values['languages'])) {
      $values['languages'] = [];
    }
  } else {
    $values['languages'] = [];
  }

  $values['bio'] = $_COOKIE['bio_value'] ?? '';

  include('form.php');
  exit();
}


$errors = false;

foreach ($fields as $field => $pattern) {
  if (empty($_POST[$field])) {
    setcookie($field . '_error', '1', 0); 
    $errors = true;
  } else {
    if ($pattern && !preg_match($pattern, $_POST[$field])) {
      setcookie($field . '_error', '1', 0);
      $errors = true;
    }
  }

  setcookie($field . '_value', $_POST[$field] ?? '', time() + 30 * 24 * 60 * 60);
}

if (empty($_POST['languages']) || !is_array($_POST['languages']) || count($_POST['languages']) == 0) {
  setcookie('languages_error', '1', 0);
  $errors = true;
} else {
  setcookie('languages_value', json_encode($_POST['languages']), time() + 30 * 24 * 60 * 60);
}

setcookie('bio_value', $_POST['bio'] ?? '', time() + 30 * 24 * 60 * 60);

if ($errors) {
  header('Location: index.php');
  exit();
}

clearErrorCookies();

foreach ($fields as $field => $pattern) {
  setcookie($field . '_value', $_POST[$field], time() + 365 * 24 * 60 * 60);
}
setcookie('languages_value', json_encode($_POST['languages']), time() + 365 * 24 * 60 * 60);
setcookie('bio_value', $_POST['bio'] ?? '', time() + 365 * 24 * 60 * 60);

$user = 'u68797';
$pass = '6204726';
try {
  $db = new PDO('mysql:host=localhost;dbname=u68797', $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
  ]);

  $stmt = $db->prepare("
    INSERT INTO application (fio, phone, email, birth_date, gender, bio, agree)
    VALUES (:fio, :phone, :email, :birth_date, :gender, :bio, 1)
  ");
  $stmt->execute([
    'fio' => $_POST['fio'],
    'phone' => $_POST['phone'],
    'email' => $_POST['email'],
    'birth_date' => $_POST['birth_date'],
    'gender' => $_POST['gender'],
    'bio' => $_POST['bio'] ?? ''
  ]);

  $app_id = $db->lastInsertId();

  $stmt = $db->prepare("INSERT INTO application_languages (app_id, lang_id) VALUES (?, ?)");
  foreach ($_POST['languages'] as $lang_id) {
    $stmt->execute([$app_id, $lang_id]);
  }

} catch (PDOException $e) {
  echo '<div class="error">Ошибка базы данных: ' . htmlspecialchars($e->getMessage()) . '</div>';
  exit();
}

setcookie('save', '1', 0);

header('Location: index.php');
exit();
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Анкета разработчика</title>
  <style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f5f7fa;
        margin: 0;
        padding: 20px;
        color: #333;
    }

    .container {
        max-width: 600px;
        margin: 0 auto;
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    h1 {
        text-align: center;
        color: #2c3e50;
        margin-bottom: 20px;
    }
    label {
        display: block;
        margin: 15px 0 5px;
        font-weight: bold;
        color: #34495e;
    }

    input[type="text"],
    input[type="tel"],
    input[type="email"],
    input[type="date"],
    select,
    textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
        box-sizing: border-box;
    }

    select[multiple] {
        height: 120px;
    }
    
    .radio-group, .checkbox-group {
        margin: 10px 0;
    }

    .radio-group label, .checkbox-group label {
        display: inline-block;
        margin-right: 15px;
        font-weight: normal;
    }

    button {
        background: #3498db;
        color: white;
        border: none;
        padding: 12px 20px;
        font-size: 16px;
        border-radius: 5px;
        cursor: pointer;
        width: 100%;
        margin-top: 20px;
        transition: background 0.3s;
    }

    button:hover {
        background: #2980b9;
    }

    .error-message {
        color: #e74c3c;
        margin: 5px 0 15px 0;
        font-size: 14px;
    }

    .error-field {
        border-color: #e74c3c !important;
        background-color: #fceae9;
    }

    .success {
        color: #27ae60;
        text-align: center;
        margin: 20px 0;
        font-weight: bold;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Анкета разработчика</h1>

    <?php
    if (!empty($messages)) {
      foreach ($messages as $msg) {
        echo $msg;
      }
    }

    // Функция для вывода ошибки для поля
    function showError($field) {
      global $errors;
      if (!empty($errors[$field])) {
        echo '<div class="error-message">Поле заполнено некорректно или содержит недопустимые символы.</div>';
      }
    }

    // Функция для добавления класса ошибки к полю
    function errorClass($field) {
      global $errors;
      return !empty($errors[$field]) ? 'error-field' : '';
    }
    ?>

    <form action="index.php" method="POST" novalidate>
      <label>ФИО*:</label>
      <input type="text" name="fio" maxlength="150"
        value="<?php echo htmlspecialchars($values['fio'] ?? ''); ?>"
        class="<?php echo errorClass('fio'); ?>"
        pattern="[А-Яа-яЁёA-Za-z\s]+"
        required>
      <?php showError('fio'); ?>

      <label>Телефон*:</label>
      <input type="tel" name="phone"
        value="<?php echo htmlspecialchars($values['phone'] ?? ''); ?>"
        class="<?php echo errorClass('phone'); ?>"
        pattern="[\+\d\s\-]+"
        required>
      <?php showError('phone'); ?>

      <label>Email*:</label>
      <input type="email" name="email"
        value="<?php echo htmlspecialchars($values['email'] ?? ''); ?>"
        class="<?php echo errorClass('email'); ?>"
        required>
      <?php showError('email'); ?>

      <label>Дата рождения*:</label>
      <input type="date" name="birth_date"
        value="<?php echo htmlspecialchars($values['birth_date'] ?? ''); ?>"
        class="<?php echo errorClass('birth_date'); ?>"
        required>
      <?php showError('birth_date'); ?>

      <label>Пол*:</label>
      <div class="radio-group <?php echo errorClass('gender'); ?>">
        <label><input type="radio" name="gender" value="male"
          <?php if (($values['gender'] ?? '') === 'male') echo 'checked'; ?> required> Мужской</label>
        <label><input type="radio" name="gender" value="female"
          <?php if (($values['gender'] ?? '') === 'female') echo 'checked'; ?>> Женский</label>
        <label><input type="radio" name="gender" value="other"
          <?php if (($values['gender'] ?? '') === 'other') echo 'checked'; ?>> Другой</label>
      </div>
      <?php showError('gender'); ?>

      <label>Любимый язык программирования* (выберите один или несколько):</label>
      <select name="languages[]" multiple class="<?php echo errorClass('languages'); ?>" required>
        <?php
        $langs = [
          '1' => 'Pascal',
          '2' => 'C',
          '3' => 'C++',
          '4' => 'JavaScript',
          '5' => 'PHP',
          '6' => 'Python',
          '7' => 'Java',
          '8' => 'Haskell',
          '9' => 'Clojure',
          '10' => 'Prolog',
          '11' => 'Scala',
          '12' => 'Go'
        ];
        $selectedLangs = $values['languages'] ?? [];
        foreach ($langs as $id => $name) {
          $selected = in_array($id, $selectedLangs) ? 'selected' : '';
          echo "<option value=\"$id\" $selected>$name</option>";
        }
        ?>
      </select>
      <?php showError('languages'); ?>

      <label>Биография:</label>
      <textarea name="bio" rows="5"><?php echo htmlspecialchars($values['bio'] ?? ''); ?></textarea>

      <label class="checkbox-group <?php echo errorClass('agree'); ?>">
        <input type="checkbox" name="agree" <?php if (!empty($values['agree'])) echo 'checked'; ?> required>
        С контрактом ознакомлен(а)*
      </label>
      <?php showError('agree'); ?>

      <button type="submit">Сохранить</button>
    </form>
  </div>
</body>
</html>
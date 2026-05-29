<?php
session_start();

// Если пользователь уже авторизован, перенаправляем
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['admin']) && $_SESSION['admin']) {
        header('Location: admin.php');
    } else {
        header('Location: create.php');
    }
    exit;
}

$error = false;
$error_message = '';
$success = false;
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login']);
    $password = $_POST['password'];
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    
    $form_data = compact('login', 'fullname', 'phone', 'email');
    
    // Валидация
    $errors = [];
    
    if (empty($login)) {
        $errors[] = 'Логин обязателен для заполнения';
    } elseif (!preg_match('/^[a-zA-Z0-9]{6,}$/', $login)) {
        $errors[] = 'Логин должен содержать только латиницу и цифры, минимум 6 символов';
    }
    
    if (empty($password)) {
        $errors[] = 'Пароль обязателен для заполнения';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Пароль должен содержать минимум 8 символов';
    }
    
    if (empty($fullname)) {
        $errors[] = 'ФИО обязательно для заполнения';
    } elseif (strlen($fullname) < 5) {
        $errors[] = 'Введите полное ФИО';
    }
    
    if (empty($phone)) {
        $errors[] = 'Телефон обязателен для заполнения';
    } elseif (!preg_match('/^\+7\(\d{3}\)\d{3}-\d{2}-\d{2}$/', $phone)) {
        $errors[] = 'Телефон должен быть в формате +7(XXX)XXX-XX-XX';
    }
    
    if (empty($email)) {
        $errors[] = 'Email обязателен для заполнения';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Введите корректный email';
    }
    
    if (empty($errors)) {
        include('db.php');
        
        // Проверяем, существует ли соединение
        if (!$con) {
            $error = true;
            $error_message = 'Ошибка подключения к базе данных';
        } else {
            // Проверка на существование логина
            $stmt = $con->prepare("SELECT id FROM users WHERE login = ?");
            if ($stmt) {
                $stmt->bind_param("s", $login);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $error = true;
                    $error_message = 'Пользователь с таким логином уже существует';
                }
                $stmt->close();
            } else {
                $error = true;
                $error_message = 'Ошибка подготовки запроса: ' . $con->error;
            }
            
            if (!$error) {
                // Проверка на существование email
                $stmt = $con->prepare("SELECT id FROM users WHERE email = ?");
                if ($stmt) {
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows > 0) {
                        $error = true;
                        $error_message = 'Пользователь с таким email уже существует';
                    }
                    $stmt->close();
                } else {
                    $error = true;
                    $error_message = 'Ошибка подготовки запроса: ' . $con->error;
                }
            }
            
            if (!$error) {
                // Для безопасности рекомендуется хешировать пароль
                // $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                // Но для совместимости с существующими данными (пароли в открытом виде) оставим как есть
                $hashed_password = $password;
                
                $sql = "INSERT INTO users (login, password, fullname, phone, email) VALUES (?, ?, ?, ?, ?)";
                $stmt = $con->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("sssss", $login, $hashed_password, $fullname, $phone, $email);
                    if ($stmt->execute()) {
                        $success = true;
                        header('refresh:2;url=login.php');
                    } else {
                        $error = true;
                        $error_message = 'Ошибка при регистрации: ' . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error = true;
                    $error_message = 'Ошибка подготовки запроса: ' . $con->error;
                }
            }
        }
    } else {
        $error = true;
        $error_message = implode('<br>', $errors);
    }
}

$is_admin = isset($_SESSION['admin']) && $_SESSION['admin'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация – Учусь.РФ</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --blue-base: #007bff;
            --blue-dark: #0d47a1;
            --silver: #c0c0c0;
            --silver-light: #e0e0e0;
            --white: #ffffff;
            --text-dark: #212529;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--blue-base) 0%, var(--blue-dark) 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .site-header {
            background: rgba(13, 71, 161, 0.95);
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .logo {
            color: var(--silver);
            font-size: 24px;
            font-weight: 700;
            text-decoration: none;
            text-shadow: 0 0 10px rgba(192, 192, 192, 0.5);
            transition: all 0.3s ease;
        }

        .logo:hover {
            color: var(--silver-light);
            text-shadow: 0 0 15px rgba(192, 192, 192, 0.8);
        }

        .nav-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .nav-buttons a {
            padding: 10px 20px;
            border: 2px solid var(--silver);
            border-radius: 25px;
            color: var(--silver);
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-buttons a:hover {
            background-color: var(--silver);
            color: var(--blue-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .container {
            max-width: 550px;
            margin: 40px auto;
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 35px;
            flex: 1;
        }

        h1 {
            text-align: center;
            color: var(--blue-dark);
            margin-bottom: 10px;
            font-size: 28px;
            font-weight: 700;
        }

        .form-subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .error-message, .success-message {
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 500;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--silver-light);
            border-radius: 12px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--blue-base);
            box-shadow: 0 0 0 3px rgba(0,123,255,0.2);
        }
        .hint {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
            display: block;
        }

        .btn-register {
            width: 100%;
            padding: 14px;
            background: var(--blue-base);
            color: var(--white);
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-size: 18px;
            font-weight: 700;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        .btn-register:hover {
            background: var(--blue-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.4);
        }
        .btn-register:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .form-footer {
            margin-top: 25px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid var(--silver-light);
        }
        .form-footer p {
            color: #666;
            margin-bottom: 10px;
        }
        .login-link {
            color: var(--blue-base);
            text-decoration: none;
            font-weight: 600;
        }
        .login-link:hover {
            text-decoration: underline;
        }
        .back-home {
            display: inline-block;
            margin-top: 10px;
            color: #888;
            text-decoration: none;
            font-size: 14px;
        }
        .back-home:hover {
            color: var(--blue-base);
        }

        .site-footer {
            background: rgba(13, 71, 161, 0.95);
            color: var(--white);
            text-align: center;
            padding: 20px 0;
            margin-top: 40px;
            font-size: 14px;
            border-top: 1px solid var(--silver-light);
        }

        @media (max-width: 768px) {
            .nav {
                flex-direction: column;
                gap: 15px;
            }
            .nav-buttons {
                justify-content: center;
                width: 100%;
            }
            .nav-buttons a {
                margin-left: 0;
                text-align: center;
                flex: 1 1 auto;
            }
            .container {
                margin: 20px 15px;
                padding: 25px;
            }
            h1 {
                font-size: 24px;
            }
            .site-footer {
                font-size: 12px;
                padding: 15px 0;
            }
        }
    </style>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>
<body>

<header class="site-header">
    <div class="nav">
        <a href="index.php" class="logo">Учусь.РФ</a>
        <div class="nav-buttons">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="login.php">Войти</a>
                <a href="register.php" class="active">Регистрация</a>
            <?php elseif ($is_admin): ?>
                <a href="admin.php">Панель администратора</a>
                <a href="?logout=1">Выход</a>
            <?php else: ?>
                <a href="history.php">Мои заявки</a>
                <a href="create.php">Новая заявка</a>
                <a href="?logout=1">Выход</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<div class="container">
    <h1>Создание аккаунта</h1>
    <div class="form-subtitle">Заполните форму для регистрации</div>

    <?php if ($error): ?>
        <div class="error-message">⚠️ <?php echo $error_message; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success-message">
            Регистрация успешно завершена!<br>
            <small>Перенаправление на страницу входа...</small>
        </div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST" action="" id="registerForm">
        <div class="form-group">
            <label for="fullname">ФИО</label>
            <input type="text" id="fullname" name="fullname" 
                   value="<?php echo htmlspecialchars($form_data['fullname'] ?? ''); ?>"
                   placeholder="Иванов Иван Иванович" required>
            <span class="hint">Ваше полное имя</span>
        </div>

        <div class="form-group">
            <label for="phone">Телефон</label>
            <input type="tel" id="phone" name="phone" 
                   value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>"
                   placeholder="+7(XXX)XXX-XX-XX" required
                   inputmode="numeric">
            <span class="hint">Формат: +7(XXX)XXX-XX-XX</span>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" 
                   value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>"
                   placeholder="example@mail.com" required>
            <span class="hint">На этот адрес будут приходить уведомления</span>
        </div>

        <div class="form-group">
            <label for="login">Логин</label>
            <input type="text" id="login" name="login" 
                   value="<?php echo htmlspecialchars($form_data['login'] ?? ''); ?>"
                   placeholder="ivan123" required>
            <span class="hint">Только латиница и цифры, минимум 6 символов</span>
        </div>

        <div class="form-group">
            <label for="password">Пароль</label>
            <input type="password" id="password" name="password" 
                   placeholder="Минимум 8 символов" required>
            <span class="hint" id="passwordHint">Минимум 8 символов</span>
        </div>

        <div class="form-group">
            <label for="confirm_password">Подтверждение пароля</label>
            <input type="password" id="confirm_password" name="confirm_password" 
                   placeholder="Повторите пароль" required>
            <span class="hint" id="confirmHint"></span>
        </div>

        <button type="submit" class="btn-register" id="submitBtn">Зарегистрироваться</button>
    </form>
    <?php endif; ?>

    <div class="form-footer">
        <p>Уже есть аккаунт? <a href="login.php" class="login-link">Войти →</a></p>
        <a href="index.php" class="back-home">← Вернуться на главную</a>
    </div>
</div>

<footer class="site-footer">
    <p>© 2026 Учусь.РФ - дистанционные курсы повышения квалификации</p>
</footer>

<script>
    // МАСКА ТЕЛЕФОНА
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        if (!phoneInput.value) {
            phoneInput.value = '+7';
        }

        phoneInput.addEventListener('input', function(e) {
            let cursorPos = e.target.selectionStart;
            let value = this.value;
            
            let numbers = value.replace(/[^\d+]/g, '');
            if (!numbers.startsWith('+7')) {
                numbers = '+7' + numbers.replace(/[^0-9]/g, '');
            }
            let digits = numbers.replace(/\D/g, '').slice(1);
            if (digits.length > 10) digits = digits.slice(0, 10);
            
            let formatted = '+7';
            if (digits.length > 0) {
                formatted += '(' + digits.slice(0, 3);
            }
            if (digits.length >= 4) {
                formatted += ')' + digits.slice(3, 6);
            }
            if (digits.length >= 7) {
                formatted += '-' + digits.slice(6, 8);
            }
            if (digits.length >= 9) {
                formatted += '-' + digits.slice(8, 10);
            }
            if (digits.length > 3 && !formatted.includes(')')) {
                formatted = formatted + ')';
            }
            
            let oldLength = this.value.length;
            this.value = formatted;
            let newLength = this.value.length;
            cursorPos += newLength - oldLength;
            if (cursorPos < 0) cursorPos = 0;
            if (cursorPos > newLength) cursorPos = newLength;
            this.setSelectionRange(cursorPos, cursorPos);
        });

        phoneInput.addEventListener('paste', function(e) {
            setTimeout(() => {
                let event = new Event('input', { bubbles: true });
                this.dispatchEvent(event);
            }, 0);
        });
    }

    // Валидация пароля
    const form = document.getElementById('registerForm');
    if (form) {
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const passwordHint = document.getElementById('passwordHint');
        const confirmHint = document.getElementById('confirmHint');
        const submitBtn = document.getElementById('submitBtn');

        if (password) {
            password.addEventListener('input', function() {
                if (this.value.length >= 8) {
                    passwordHint.innerHTML = 'Пароль надёжный';
                    passwordHint.style.color = '#28a745';
                } else {
                    passwordHint.innerHTML = 'Минимум 8 символов';
                    passwordHint.style.color = '#dc3545';
                }
                if (confirmPassword.value) checkPasswordsMatch();
            });
        }

        function checkPasswordsMatch() {
            if (password.value === confirmPassword.value && password.value.length >= 8) {
                confirmHint.innerHTML = 'Пароли совпадают';
                confirmHint.style.color = '#28a745';
                return true;
            } else if (confirmPassword.value.length > 0) {
                confirmHint.innerHTML = 'Пароли не совпадают';
                confirmHint.style.color = '#dc3545';
                return false;
            }
            return false;
        }

        if (confirmPassword) {
            confirmPassword.addEventListener('input', checkPasswordsMatch);
        }

        form.addEventListener('submit', function(e) {
            if (password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Пароли не совпадают');
                confirmPassword.style.borderColor = '#dc3545';
                return false;
            }
            if (password.value.length < 8) {
                e.preventDefault();
                alert('Пароль должен содержать минимум 8 символов');
                password.style.borderColor = '#dc3545';
                return false;
            }
            const phonePattern = /^\+7\(\d{3}\)\d{3}-\d{2}-\d{2}$/;
            if (!phonePattern.test(phoneInput.value)) {
                e.preventDefault();
                alert('Введите телефон в формате +7(XXX)XXX-XX-XX');
                phoneInput.style.borderColor = '#dc3545';
                return false;
            }
            const login = document.getElementById('login');
            const loginPattern = /^[a-zA-Z0-9]{6,}$/;
            if (!loginPattern.test(login.value)) {
                e.preventDefault();
                alert('Логин должен содержать только латиницу и цифры, минимум 6 символов');
                login.style.borderColor = '#dc3545';
                return false;
            }
            submitBtn.disabled = true;
            submitBtn.textContent = 'Регистрация...';
        });
    }
</script>
</body>
</html>
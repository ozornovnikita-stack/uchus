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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login']);
    $password = $_POST['password'];
    
    if (empty($login) || empty($password)) {
        $error = true;
        $error_message = 'Пожалуйста, заполните все поля';
    } else {
        include('db.php');
        
        $stmt = $con->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->bind_param("s", $login);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = true;
            $error_message = 'Неверный логин или пароль';
        } else {
            $user = $result->fetch_assoc();
            
            if ($password !== $user['password']) {
                $error = true;
                $error_message = 'Неверный логин или пароль';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_login'] = $user['login'];
                $_SESSION['user_fullname'] = $user['fullname'];
                
                if ($user['login'] == 'Admin26') {
                    $_SESSION['admin'] = true;
                    header('Location: admin.php');
                } else {
                    header('Location: create.php');
                }
                exit;
            }
        }
        $stmt->close();
    }
}

$is_admin = isset($_SESSION['admin']) && $_SESSION['admin'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход – Учусь.РФ</title>
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

        /* Шапка */
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

        /* Контейнер формы */
        .container {
            max-width: 500px;
            width: 90%;
            margin: 40px auto 0 auto; /* верхний отступ, нижний 0 */
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 35px;
            flex: 0 0 auto; /* не растягивается, размер по содержимому */
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

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 500;
            border: 1px solid #f5c6cb;
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

        .btn-login {
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

        .btn-login:hover {
            background: var(--blue-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.4);
        }

        .btn-login:disabled {
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

        .register-link {
            color: var(--blue-base);
            text-decoration: none;
            font-weight: 600;
        }

        .register-link:hover {
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

        /* Футер */
        .site-footer {
            background: rgba(13, 71, 161, 0.95);
            color: var(--white);
            text-align: center;
            padding: 20px 0;
            margin-top: auto; /* прижимаем футер к низу, если контента мало */
            font-size: 14px;
            border-top: 1px solid var(--silver-light);
        }

        /* Адаптивность */
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
                margin: 20px auto 0 auto;
                padding: 25px;
                width: 90%;
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
</head>
<body>

<header class="site-header">
    <div class="nav">
        <a href="index.php" class="logo">Учусь.РФ</a>
        <div class="nav-buttons">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="login.php" class="active">Войти</a>
                <a href="register.php">Регистрация</a>
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
    <h1>Вход в аккаунт</h1>
    <div class="form-subtitle">Добро пожаловать!</div>

    <?php if ($error): ?>
        <div class="error-message">⚠️ <?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form method="POST" action="" id="loginForm">
        <div class="form-group">
            <label for="login">Логин</label>
            <input type="text" id="login" name="login" 
                   value="<?php echo isset($_POST['login']) ? htmlspecialchars($_POST['login']) : ''; ?>"
                   placeholder="Введите ваш логин" required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Пароль</label>
            <input type="password" id="password" name="password" 
                   placeholder="Введите пароль" required>
        </div>

        <button type="submit" class="btn-login" id="submitBtn">Войти</button>
    </form>

    <div class="form-footer">
        <p>Нет аккаунта? <a href="register.php" class="register-link">Зарегистрироваться →</a></p>
        <a href="index.php" class="back-home">← Вернуться на главную</a>
    </div>
</div>

<footer class="site-footer">
    <p>© 2026 Учусь.РФ - дистанционные курсы повышения квалификации</p>
</footer>

<script>
    const form = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            const login = document.getElementById('login').value.trim();
            const password = document.getElementById('password').value;
            
            if (!login || !password) {
                e.preventDefault();
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Вход...';
        });
    }
</script>
</body>
</html>
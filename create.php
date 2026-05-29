<?php
session_start();

// Обработка выхода (добавлено)
if (isset($_GET['logout'])) {
    $_SESSION = array();
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    header('Location: index.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    die('Чтобы оставить заявку, надо войти в аккаунт.');
}

$success = false;
$error = false;
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $review = $_POST['review'] ?? '';
    $date   = $_POST['date'] ?? '';
    $curses = $_POST['curses'] ?? '';
    $payment= $_POST['payment'] ?? '';
    $status = 'Новая';

    include('db.php');

    $user_id = (int)$_SESSION['user_id'];
    $review  = $con->real_escape_string($review);
    $curses  = $con->real_escape_string($curses);
    $payment = $con->real_escape_string($payment);

    $query = $con->query("INSERT INTO request (review, date, curses, payment, user_id, status) 
                          VALUES ('$review', '$date', '$curses', '$payment', '$user_id', '$status')");

    if (!$query) {
        $error = true;
        $error_msg = 'Ошибка: ' . $con->error;
    } else {
        $success = true;
    }
}

$is_admin = isset($_SESSION['admin']) && $_SESSION['admin'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание заявки – Учусь.РФ</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --blue-base: #007bff;
            --blue-dark: #0d47a1;
            --silver: #c0c0c0;
            --silver-light: #e0e0e0;
            --white: #ffffff;
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
            font-weight: bold;
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
            max-width: 700px;
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
            margin-bottom: 25px;
            font-size: 28px;
            font-weight: 700;
        }

        form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #212529;
        }

        form input,
        form select,
        form textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 2px solid var(--silver-light);
            border-radius: 12px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            background-color: var(--white);
        }

        form input:focus,
        form select:focus,
        form textarea:focus {
            outline: none;
            border-color: var(--blue-base);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
        }

        form textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn-submit {
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
        }

        .btn-submit:hover {
            background: var(--blue-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4);
        }

        .success-message, .error-message {
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 500;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .success-message a, .error-message a {
            color: inherit;
            font-weight: bold;
            text-decoration: underline;
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
            <?php if ($is_admin): ?>
                <a href="admin.php">Панель администратора</a>
                <a href="?logout=1">Выход</a>
            <?php else: ?>
                <a href="history.php">Мои заявки</a>
                <a href="create.php" class="active">Новая заявка</a>
                <a href="?logout=1">Выход</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<div class="container">
    <h1>Создание заявки</h1>

    <?php if ($success): ?>
        <div class="success-message">
            Заявка успешно отправлена!<br><br>
            <a href="history.php">Перейти к истории моих заявок</a><br><br>
            Спасибо, что выбрали нас!
        </div>
    <?php elseif ($error): ?>
        <div class="error-message">
            Ошибка при отправке заявки: <?php echo htmlspecialchars($error_msg); ?><br>
            <a href="javascript:history.back()">Попробовать снова</a>
        </div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST" action="">
        <label for="curses">Название курса</label>
        <select id="curses" name="curses" required>
            <option value="Курсы повышения квалификации">Курсы повышения квалификации</option>
            <option value="Курсы переподготовки">Курсы переподготовки</option>
            <option value="Курсы по охране труда">Курсы по охране труда</option>
        </select>

        <label for="date">Когда желаете начать обучение?</label>
        <input type="datetime-local" id="date" name="date" required>

        <label for="payment">Способ оплаты</label>
        <select id="payment" name="payment" required>
            <option value="наличные">Наличные</option>
            <option value="перевод">Переводом по номеру</option>
            <option value="карта">Банковской картой</option>
        </select>

        <label for="review">Дополнительная информация</label>
        <textarea id="review" name="review" placeholder="Опишите ваши пожелания или комментарий..."></textarea>

        <button type="submit" class="btn-submit">Отправить заявку</button>
    </form>
    <?php endif; ?>
</div>

<footer class="site-footer">
    <p>© 2026 Учусь.РФ - дистанционные курсы повышения квалификации</p>
</footer>

<script>
    const form = document.querySelector('form');
    const submitBtn = document.querySelector('.btn-submit');
    if (form) {
        form.addEventListener('submit', function() {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Отправка...';
            }
        });
    }
</script>
</body>
</html>
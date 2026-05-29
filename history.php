<?php
session_start();

// Обработка выхода
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    die('Чтобы посмотреть историю заявок, надо войти в аккаунт.');
}
include('db.php');

// Обработка отправки отзыва (обновляет таблицу request)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['review']) && isset($_POST['request_id'])) {
    $review = $con->real_escape_string($_POST['review']);
    $request_id = (int)$_POST['request_id'];
    $user_id = (int)$_SESSION['user_id'];
    
    $con->query("UPDATE request SET review='$review' WHERE id='$request_id' AND user_id='$user_id'");
    $review_success = true;
}

// Получение заявок пользователя
$user_id = (int)$_SESSION['user_id'];
$query = $con->query("SELECT * FROM request WHERE user_id='$user_id' ORDER BY date DESC");
if (!$query) die('Query error: ' . $con->error);

$is_admin = isset($_SESSION['admin']) && $_SESSION['admin'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Мои заявки – Учусь.РФ</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Глобальное применение шрифта Inter */
        * {
            font-family: 'Inter', sans-serif;
        }

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
            max-width: 1100px;
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
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 700;
        }

        .request-card {
            background: var(--white);
            border: 1px solid var(--silver-light);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .request-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .request-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--blue-dark);
            margin-bottom: 12px;
            border-left: 4px solid var(--blue-base);
            padding-left: 12px;
        }

        .request-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 15px;
        }

        .info-item {
            font-size: 14px;
            color: var(--text-dark);
        }

        .info-label {
            font-weight: 600;
            color: #555;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 40px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-new {
            background: #fff3cd;
            color: #856404;
        }

        .status-in-progress {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .review-form {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed var(--silver-light);
        }

        .review-form form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .review-form input {
            flex: 1;
            padding: 10px 15px;
            border: 2px solid var(--silver-light);
            border-radius: 40px;
            font-size: 14px;
        }

        .review-form input:focus {
            outline: none;
            border-color: var(--blue-base);
            box-shadow: 0 0 0 3px rgba(0,123,255,0.2);
        }

        .btn-review {
            background: var(--blue-base);
            color: var(--white);
            border: none;
            padding: 10px 24px;
            border-radius: 40px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .btn-review:hover {
            background: var(--blue-dark);
            transform: translateY(-2px);
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 500;
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            background: var(--silver-light);
            border-radius: 16px;
            color: #666;
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
            .review-form form {
                flex-direction: column;
                gap: 10px;
            }
            .review-form input {
                width: 100%;
                flex: none;
            }
            .btn-review {
                width: 100%;
                white-space: normal;
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
            <?php if ($is_admin): ?>
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
    <h1>История заявок</h1>

    <?php if (isset($review_success) && $review_success): ?>
        <div class="alert-success">
            Отзыв успешно оставлен! Спасибо за обратную связь.
        </div>
    <?php endif; ?>

    <?php if ($query->num_rows == 0): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>У вас пока нет заявок</h3>
            <p>Перейдите на главную страницу, чтобы создать новую заявку.</p>
            <a href="create.php" class="btn-create" style="display: inline-block; margin-top: 15px; background: var(--blue-base); color: white; padding: 10px 25px; border-radius: 40px; text-decoration: none;">Создать заявку</a>
        </div>
    <?php else: ?>
        <?php 
        $counter = 1;
        while ($request = $query->fetch_assoc()):
            $status_class = match($request['status']) {
                'Новая' => 'status-new',
                'Идет обучение' => 'status-in-progress',
                'Обучение завершено' => 'status-completed',
                default => 'status-new'
            };
        ?>
            <div class="request-card">
                <div class="request-title">Заявка #<?= $counter++ ?></div>
                <div class="request-info">
                    <div class="info-item"><span class="info-label">Дата подачи:</span> <?= htmlspecialchars($request['date']) ?></div>
                    <div class="info-item"><span class="info-label">Курс:</span> <?= htmlspecialchars($request['curses']) ?></div>
                    <div class="info-item"><span class="info-label">Оплата:</span> <?= htmlspecialchars($request['payment']) ?></div>
                    <div class="info-item">
                        <span class="info-label">Статус:</span>
                        <span class="status-badge <?= $status_class ?>"><?= htmlspecialchars($request['status']) ?></span>
                    </div>
                </div>

                <?php if ($request['status'] === 'Обучение завершено'): ?>
                    <div class="review-form">
                        <form method="POST">
                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                            <input type="text" name="review" placeholder="Оставьте отзыв об обучении..." value="<?= htmlspecialchars($request['review'] ?? '') ?>">
                            <button type="submit" class="btn-review">Оставить отзыв</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>

<footer class="site-footer">
    <p>© 2026 Учись.РФ - дистанционные курсы повышения квалификации</p>
</footer>

</body>
</html>
<?php
include('db.php');
session_start();

// Проверка авторизации администратора
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php');
    exit;
}

// Обработка выхода
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// Допустимые статусы
$valid_statuses = ['Новая', 'Идет обучение', 'Обучение завершено'];

// Показывать ли уведомление? (проверяем параметр updated)
$status_updated = isset($_GET['updated']) && $_GET['updated'] == 1;

// ========== ФИЛЬТРАЦИЯ ПО СТАТУСУ ==========
$status_filter = $_GET['status_filter'] ?? '';
$page          = (int)($_GET['page'] ?? 1);
$limit         = 10;
$offset        = ($page - 1) * $limit;

// ========== СОРТИРОВКА (ID, дата, логин, ФИО) ==========
$sort_field = $_GET['sort'] ?? 'date';
$sort_order = $_GET['order'] ?? 'DESC';
$allowed_sort = ['id', 'date', 'login', 'fullname'];
if (!in_array($sort_field, $allowed_sort, true)) $sort_field = 'date';
$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

// Построение WHERE (только статус)
$where = [];
$params = [];
$types = '';

if ($status_filter && in_array($status_filter, $valid_statuses, true)) {
    $where[] = "request.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Подсчёт общего количества (для пагинации)
$count_sql = "SELECT COUNT(*) as total FROM request INNER JOIN users ON request.user_id = users.id $where_sql";
$count_stmt = $con->prepare($count_sql);
if ($params) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_rows = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Сортировка: соответствие полей
$sort_map = [
    'id'       => 'request.id',
    'date'     => 'request.date',
    'login'    => 'users.login',
    'fullname' => 'users.fullname',
];
$order_by = $sort_map[$sort_field] . ' ' . $sort_order;

// Основной запрос с сортировкой
$sql = "
    SELECT request.*, users.login, users.fullname
    FROM request
    INNER JOIN users ON request.user_id = users.id
    $where_sql
    ORDER BY $order_by
    LIMIT ? OFFSET ?
";
$stmt = $con->prepare($sql);
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$query = $stmt->get_result();

// Обработка изменения статуса
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_id'])) {
    $request_id = (int)$_POST['request_id'];
    $status = $_POST['status'] ?? '';

    if (!in_array($status, $valid_statuses, true)) {
        die('Недопустимый статус заявки');
    }

    $stmt = $con->prepare("UPDATE request SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $request_id);
    if ($stmt->execute()) {
        $get_params = $_GET;
        unset($get_params['page']);
        $get_params['updated'] = 1;
        header('Location: ?' . http_build_query($get_params));
        exit;
    } else {
        die('Ошибка обновления: ' . $con->error);
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Панель администратора — Учусь.РФ</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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

        /* ШАПКА (как на других страницах) */
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
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .nav-buttons a:hover {
            background-color: var(--silver);
            color: var(--blue-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        /* ОСНОВНОЙ КОНТЕЙНЕР */
        .container {
            max-width: 1200px;
            margin: 20px auto;
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            overflow: hidden;
            flex: 1;
        }

        /* ФИЛЬТРЫ */
        .filters-bar {
            padding: 20px 30px;
            background: var(--white);
            border-bottom: 1px solid var(--silver-light);
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        .filter-group {
            flex: 1;
            min-width: 160px;
        }
        .filter-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--text-dark);
        }
        .filter-group select {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid var(--silver-light);
            border-radius: 30px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
        }
        .btn-filter, .btn-reset {
            padding: 10px 20px;
            border-radius: 30px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
        }
        .btn-filter { background: var(--blue-base); color: var(--white); }
        .btn-reset { background: #6c757d; color: var(--white); text-decoration: none; display: inline-block; }

        /* СОРТИРОВКА */
        .sort-panel {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
            padding: 12px 30px;
            background: var(--silver-light);
            border-bottom: 1px solid #eee;
        }
        .sort-label {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 14px;
        }
        .sort-link {
            background: var(--white);
            padding: 6px 14px;
            border-radius: 30px;
            text-decoration: none;
            color: var(--blue-base);
            border: 1px solid var(--silver);
            font-size: 13px;
            transition: 0.2s;
        }
        .sort-link.active {
            background: var(--blue-base);
            color: var(--white);
            border-color: var(--blue-base);
        }
        .sort-link:hover {
            background: var(--blue-base);
            color: var(--white);
        }

        /* КАРТОЧКИ ЗАЯВОК */
        .requests-container {
            padding: 0 30px 30px;
        }
        .request-item {
            background: var(--white);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border-left: 4px solid;
        }
        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .user-info h3 {
            color: var(--blue-dark);
            margin-bottom: 5px;
        }
        .request-id {
            background: var(--silver-light);
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            color: #666;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-new {
            background: var(--silver-light);
            color: var(--text-dark);
        }
        .status-in-progress {
            background: var(--blue-base);
            color: var(--white);
        }
        .status-completed {
            background: var(--silver-light);
            color: var(--blue-dark);
        }

        .request-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .detail-item {
            padding: 10px;
            background: var(--silver-light);
            border-radius: 12px;
        }
        .detail-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
        }
        .detail-value {
            font-size: 16px;
            color: var(--text-dark);
            word-break: break-word;
        }

        /* ФОРМА ИЗМЕНЕНИЯ СТАТУСА */
        .status-form {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px dashed var(--silver-light);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
        }
        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--silver-light);
            border-radius: 12px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
        }
        .btn-save {
            width: 100%;
            padding: 12px;
            background: var(--blue-base);
            color: var(--white);
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .btn-save:hover {
            background: var(--blue-dark);
            transform: translateY(-2px);
        }

        /* ПАГИНАЦИЯ */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
            padding-bottom: 30px;
        }
        .page-link {
            padding: 8px 16px;
            border: 1px solid var(--silver-light);
            border-radius: 30px;
            text-decoration: none;
            color: var(--blue-base);
            background: var(--white);
            transition: all 0.3s ease;
        }
        .page-link:hover,
        .page-link.active {
            background: var(--blue-base);
            color: var(--white);
            border-color: var(--blue-base);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--silver-light);
            border-radius: 16px;
        }
        .empty-state i {
            font-size: 48px;
            color: #999;
            margin-bottom: 15px;
        }
        .empty-state h3 {
            color: var(--blue-dark);
            margin-bottom: 10px;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            background: var(--blue-base);
            color: var(--white);
            border-radius: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            z-index: 1000;
            animation: slideInRight 0.5s ease-out, fadeOut 0.5s ease-out 2.5s forwards;
            font-weight: bold;
        }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(100px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes fadeOut {
            to { opacity: 0; visibility: hidden; }
        }

        .site-footer {
            background: rgba(13, 71, 161, 0.95);
            color: var(--white);
            text-align: center;
            padding: 20px 0;
            margin-top: 20px;
            font-size: 14px;
            border-top: 1px solid var(--silver-light);
        }

        @media (max-width: 768px) {
            body { padding: 0; }
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
            .container { margin: 10px; border-radius: 12px; }
            .filters-bar { flex-direction: column; align-items: stretch; padding: 15px 20px; }
            .sort-panel { flex-direction: column; align-items: stretch; padding: 12px 20px; }
            .sort-link { text-align: center; }
            .requests-container { padding: 0 20px 20px; }
            .request-item { padding: 16px; }
            .request-header { flex-direction: column; align-items: flex-start; gap: 10px; }
            .request-details { grid-template-columns: 1fr; }
            .btn-save { font-size: 14px; }
            .site-footer { font-size: 12px; padding: 15px 0; }
            .notification { top: 10px; right: 10px; left: 10px; text-align: center; border-radius: 30px; font-size: 14px; }
        }
    </style>
</head>
<body>

<header class="site-header">
    <div class="nav">
        <a href="index.php" class="logo">Учусь.РФ</a>
        <div class="nav-buttons">
            <a href="index.php"><i class="fas fa-home"></i> Главная</a>
            <a href="?logout=1" onclick="return confirm('Выйти из аккаунта?')"><i class="fas fa-sign-out-alt"></i> Выход</a>
        </div>
    </div>
</header>

<div class="container">
    <form method="GET" class="filters-bar">
        <div class="filter-group">
            <label><i class="fas fa-filter"></i> Статус</label>
            <select name="status_filter">
                <option value="">Все статусы</option>
                <option value="Новая" <?= $status_filter === 'Новая' ? 'selected' : '' ?>>Новая</option>
                <option value="Идет обучение" <?= $status_filter === 'Идет обучение' ? 'selected' : '' ?>>Идёт обучение</option>
                <option value="Обучение завершено" <?= $status_filter === 'Обучение завершено' ? 'selected' : '' ?>>Обучение завершено</option>
            </select>
        </div>
        <div class="filter-group">
            <label>&nbsp;</label>
            <button type="submit" class="btn-filter"><i class="fas fa-sliders-h"></i> Применить</button>
            <a href="?" class="btn-reset"><i class="fas fa-undo-alt"></i> Сбросить</a>
        </div>
        <input type="hidden" name="sort" value="<?= htmlspecialchars($sort_field) ?>">
        <input type="hidden" name="order" value="<?= htmlspecialchars($sort_order) ?>">
    </form>

    <div class="sort-panel">
        <span class="sort-label"><i class="fas fa-sort"></i> Сортировать по:</span>
        <?php
        $current_get = $_GET;
        unset($current_get['page']);
        $sort_fields = ['id' => 'Порядку', 'date' => 'Дате', 'login' => 'Логину', 'fullname' => 'ФИО'];
        foreach ($sort_fields as $field => $title) {
            $new_order = ($sort_field === $field && $sort_order === 'ASC') ? 'DESC' : 'ASC';
            $get_copy = $current_get;
            $get_copy['sort'] = $field;
            $get_copy['order'] = $new_order;
            $active = ($sort_field === $field) ? 'active' : '';
            echo '<a href="?' . http_build_query($get_copy) . '" class="sort-link ' . $active . '">' . $title;
            if ($sort_field === $field) echo ' ' . ($sort_order === 'ASC' ? '↑' : '↓');
            echo '</a>';
        }
        ?>
    </div>

    <div class="requests-container">
        <?php if ($query->num_rows === 0): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>Заявок не найдено</h3>
                <p>Попробуйте изменить параметры фильтрации</p>
            </div>
        <?php else: while ($request = $query->fetch_assoc()):
            $status_class = match($request['status']) {
                'Новая' => 'status-new',
                'Идет обучение' => 'status-in-progress',
                'Обучение завершено' => 'status-completed',
                default => 'status-new'
            };
        ?>
            <div class="request-item">
                <div class="request-header">
                    <div class="user-info">
                        <h3><?= htmlspecialchars($request['login']) ?></h3>
                        <p><?= htmlspecialchars($request['fullname']) ?></p>
                    </div>
                    <div>
                        <span class="request-id">Заявка №<?= htmlspecialchars($request['id']) ?></span>
                        <span class="status-badge <?= $status_class ?>"><?= htmlspecialchars($request['status']) ?></span>
                    </div>
                </div>
                <div class="request-details">
                    <div class="detail-item"><div class="detail-label">Дата подачи</div><div class="detail-value"><?= htmlspecialchars($request['date']) ?></div></div>
                    <div class="detail-item"><div class="detail-label">Услуга</div><div class="detail-value"><?= htmlspecialchars($request['curses'] ?? '—') ?></div></div>
                    <div class="detail-item"><div class="detail-label">Оплата</div><div class="detail-value"><?= htmlspecialchars($request['payment'] ?? '—') ?></div></div>
                    <div class="detail-item"><div class="detail-label">Комментарий</div><div class="detail-value"><?= htmlspecialchars($request['review'] ?? '—') ?></div></div>
                </div>
                <div class="status-form">
                    <form method="POST" class="status-update-form">
                        <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                        <?php foreach ($_GET as $key => $val): if (is_array($val)) continue; ?>
                            <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($val) ?>">
                        <?php endforeach; ?>
                        <div class="form-group">
                            <label class="form-label" for="status_<?= $request['id'] ?>"><i class="fas fa-tag"></i> Изменить статус:</label>
                            <select name="status" id="status_<?= $request['id'] ?>" class="form-select">
                                <option value="Новая" <?= $request['status'] == 'Новая' ? 'selected' : '' ?>>Новая</option>
                                <option value="Идет обучение" <?= $request['status'] == 'Идет обучение' ? 'selected' : '' ?>>Идёт обучение</option>
                                <option value="Обучение завершено" <?= $request['status'] == 'Обучение завершено' ? 'selected' : '' ?>>Обучение завершено</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-save"><i class="fas fa-save"></i> Сохранить изменения</button>
                    </form>
                </div>
            </div>
        <?php endwhile; endif; ?>
    </div>

    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php
            $query_params = $_GET;
            unset($query_params['page']);
            for ($i = 1; $i <= $total_pages; $i++):
                $query_params['page'] = $i;
                $url = '?' . http_build_query($query_params);
            ?>
                <a href="<?= $url ?>" class="page-link <?= $page === $i ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<footer class="site-footer">
    <p>© 2026 Учусь.РФ - дистанционные курсы повышения квалификации</p>
</footer>

<?php if ($status_updated): ?>
    <div class="notification">✅ Статус заявки успешно обновлён!</div>
<?php endif; ?>

<script>
    document.querySelectorAll('.status-update-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const btn = this.querySelector('.btn-save');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сохранение...';
            }
        });
    });
    const notification = document.querySelector('.notification');
    if (notification) {
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 500);
        }, 3000);
    }
    document.querySelectorAll('.page-link').forEach(link => {
        if (link.getAttribute('href') === window.location.pathname + window.location.search) {
            link.classList.add('active');
        }
    });
</script>
</body>
</html>
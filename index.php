<?php
session_start();

// Выход из системы
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Проверяем, установлен ли ключ admin в сессии
$is_admin = isset($_SESSION['admin']) && $_SESSION['admin'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Учусь.РФ - дополнительное образование</title>
  <!-- Подключаем шрифт Inter -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Основные цвета: синий и серебристый (как на странице регистрации) */
    :root {
      --blue-dark: #007bff;
      --blue-medium: #0d47a1;
      --blue-light: #4a7bcb;
      --silver: #c0c0c0;
      --silver-light: #e0e0e0;
      --white: #ffffff;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, var(--blue-dark) 0%, var(--blue-medium) 100%);
      margin: 0;
      padding: 0;
      color: var(--white);
      min-height: 100vh;
    }

    /* Шапка сайта */
    .header {
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
      margin-left: 0;
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

    /* Слайдер */
    .slideshow-container {
      max-width: 1000px;
      position: relative;
      margin: 40px auto;
      overflow: hidden;
      border-radius: 10px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
    }

    .mySlides {
      display: none;
    }

    .fade {
      animation: fadeIn 1.5s ease-in-out;
    }

    @keyframes fadeIn {
      from { opacity: 0.4; }
      to { opacity: 1; }
    }

    .mySlides img {
      width: 100%;
      height: 500px;
      object-fit: cover;
    }

    .text {
      position: absolute;
      bottom: 20px;
      left: 20px;
      background: rgba(26, 58, 95, 0.8);
      padding: 10px 20px;
      border-radius: 5px;
      font-size: 20px;
      font-weight: bold;
      color: var(--silver);
    }

    .prev, .next {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background-color: rgba(26, 58, 95, 0.8);
      color: var(--silver);
      border: none;
      cursor: pointer;
      padding: 15px 20px;
      font-size: 18px;
      border-radius: 50%;
      transition: all 0.3s ease;
    }

    .prev { left: 10px; }
    .next { right: 10px; }

    .prev:hover, .next:hover {
      background-color: var(--silver);
      color: var(--blue-dark);
      transform: translateY(-50%) scale(1.1);
    }

    .dot-container {
      text-align: center;
      padding: 20px 0;
    }

    .dot {
      cursor: pointer;
      height: 15px;
      width: 15px;
      margin: 0 5px;
      background-color: #bbb;
      border-radius: 50%;
      display: inline-block;
      transition: background-color 0.3s ease;
    }

    .dot.active, .dot:hover {
      background-color: var(--silver);
    }

    /* Блок преимуществ */
    .benefits {
      max-width: 1200px;
      margin: 40px auto;
      padding: 0 20px;
    }

    .benefits h2 {
      text-align: center;
      color: var(--silver);
      margin-bottom: 30px;
    }

    .benefits-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 30px;
    }

    .benefit-card {
      background: rgba(26, 58, 95, 0.8);
      padding: 25px;
      border-radius: 10px;
      text-align: center;
    }

    .benefit-card h3 {
      color: var(--silver-light);
      margin-bottom: 10px;
    }

    .benefit-card p {
      color: var(--silver);
      line-height: 1.5;
    }

    /* Футер */
    .site-footer {
      background: rgba(13, 71, 161, 0.95);
      color: var(--white);
      text-align: center;
      padding: 20px 0;
      margin-top: 40px;
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
      .mySlides img {
        height: 300px;
      }
      .text {
        font-size: 14px;
        bottom: 10px;
        left: 10px;
      }
      .prev, .next {
        padding: 8px 12px;
        font-size: 14px;
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

<!-- Шапка сайта -->
<header class="header">
  <div class="nav">
    <a href="index.php" class="logo">Учусь.РФ</a>
    <div class="nav-buttons">
      <?php if (!isset($_SESSION['user_id'])): ?>
        <a href="login.php" class="btn-login">Войти</a>
        <a href="register.php" class="btn-register">Регистрация</a>
      <?php elseif ($is_admin): ?>
        <a href="admin.php" class="btn-admin">Панель администратора</a>
        <a href="?logout=1" class="btn-exit">Выход</a>
      <?php elseif (isset($_SESSION['user_id'])): ?>
        <a href="history.php" class="btn-lk">Мои заявки</a>
        <a href="create.php" class="btn-create">Новая заявка</a>
        <a href="?logout=1" class="btn-exit">Выход</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<!-- Слайдер с картинками (4 слайда) -->
<div class="slideshow-container">
  <div class="mySlides fade">
    <img src="slide18-l-1.jpg" alt="Слайд 1">
    <div class="text">Курсы повышения квалификации</div>
  </div>
  <div class="mySlides fade">
    <img src="88-gHQq11R0.jpg" alt="Слайд 2">
    <div class="text">Курсы переподготовки</div>
  </div>
  <div class="mySlides fade">
    <img src="2.png" alt="Слайд 3">
    <div class="text">Курсы по охране труда</div>
  </div>
  <!-- Четвёртый слайд -->
  <div class="mySlides fade">
    <img src="hero.png" alt="Слайд 4">
    <div class="text">Индивидуальный подход</div>
  </div>
  <a class="prev" onclick="plusSlides(-1)">❮</a>
  <a class="next" onclick="plusSlides(1)">❯</a>
</div>

<!-- Точки навигации (4 точки) -->
<div class="dot-container">
  <span class="dot" onclick="currentSlide(1)"></span>
  <span class="dot" onclick="currentSlide(2)"></span>
  <span class="dot" onclick="currentSlide(3)"></span>
  <span class="dot" onclick="currentSlide(4)"></span>
</div>

<!-- Блок преимуществ -->
<section class="benefits">
  <h2>Почему выбирают нас?</h2>
  <div class="benefits-grid">
    <div class="benefit-card">
      <h3>Опытные преподаватели</h3>
      <p>Все наши преподаватели имеют высшую категорию.</p>
    </div>
    <div class="benefit-card">
      <h3>Современное оборудование</h3>
      <p>Мы используем только новые программы для обучения.</p>
    </div>
    <div class="benefit-card">
      <h3>Гибкий график</h3>
      <p>Подберём удобное время для занятий под ваш график.</p>
    </div>
  </div>
</section>

<!-- Футер -->
<footer class="site-footer">
  <p>© 2026 Учусь.РФ - дистанционные курсы повышения квалификации</p>
</footer>

<script>
let slideIndex = 1;
showSlides(slideIndex);

function plusSlides(n) {
  showSlides(slideIndex += n);
}

function currentSlide(n) {
  showSlides(slideIndex = n);
}

function showSlides(n) {
  let i;
  let slides = document.getElementsByClassName("mySlides");
  let dots = document.getElementsByClassName("dot");

  if (n > slides.length) { slideIndex = 1 }
  if (n < 1) { slideIndex = slides.length }

  for (i = 0; i < slides.length; i++) {
    slides[i].style.display = "none";
  }
  for (i = 0; i < dots.length; i++) {
    dots[i].className = dots[i].className.replace(" active", "");
  }

  slides[slideIndex-1].style.display = "block";
  dots[slideIndex-1].className += " active";
}

let slideInterval = setInterval(function() {
  plusSlides(1);
}, 3000);

const slideshowContainer = document.querySelector('.slideshow-container');
if (slideshowContainer) {
  slideshowContainer.addEventListener('mouseenter', function() {
    clearInterval(slideInterval);
  });
  slideshowContainer.addEventListener('mouseleave', function() {
    slideInterval = setInterval(function() {
      plusSlides(1);
    }, 3000);
  });
}
</script>
</body>
</html>
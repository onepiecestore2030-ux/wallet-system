<?php
session_start();

// حفظ اسم المستخدم قبل تدمير الجلسة
$username = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'عزيزي العميل';

// تدمير جميع بيانات الجلسة
session_destroy();

// تحديد وقت التأخير (5 ثواني)
$delay = 5;
$redirect = 'index.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>وداعاً</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: white;
            text-align: center;
            overflow: hidden;
        }
        .logout-container {
            padding: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
            max-width: 500px;
        }
        .logout-container h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            opacity: 0.9;
        }
        .logout-container p {
            font-size: 1.2rem;
            margin-bottom: 30px;
        }
        .username {
            font-weight: bold;
            color: #fff;
            font-size: 1.4rem;
        }
        .countdown {
            font-size: 2rem;
            font-weight: bold;
            color: #ffeb3b;
        }
        .icon {
            font-size: 4rem;
            margin-bottom: 20px;
            animation: wave 2s infinite;
        }
        @keyframes wave {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(10deg); }
            75% { transform: rotate(-10deg); }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="icon">
            <i class="fas fa-sign-out-alt"></i>
        </div>
        <h1>وداعاً</h1>
        <p>
            شكرًا لك على استخدامك نظام المحفظة الرقمية.<br>
            نتمنى لك يوماً سعيداً، <span class="username"><?= htmlspecialchars($username) ?></span> 💙
        </p>
        <div class="countdown" id="countdown"><?= $delay ?></div>
        <p>سيتم توجيهك تلقائيًا إلى صفحة تسجيل الدخول...</p>
    </div>

    <script>
        // عد تنازلي من 5 إلى 0
        let timeLeft = <?= $delay ?>;
        const countdownElement = document.getElementById('countdown');

        const timer = setInterval(() => {
            timeLeft--;
            countdownElement.textContent = timeLeft;

            if (timeLeft <= 0) {
                clearInterval(timer);
                window.location.href = '<?= $redirect ?>';
            }
        }, 1000);
    </script>
</body>
</html>
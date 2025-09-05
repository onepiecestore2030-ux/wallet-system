<?php
session_start();

// التحقق من الجلسة
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// التحقق من وجود عملية شراء
if (!isset($_SESSION['last_purchase'])) {
    header("Location: packages.php");
    exit;
}

$purchase = $_SESSION['last_purchase'];
$phone_error = '';

// -------------------------------
// معالجة إرسال الرسالة عبر واتساب
// -------------------------------
if (isset($_POST['send_whatsapp'])) {
    $country_code = $_POST['country_code'];
    $phone_local = preg_replace('/\D/', '', $_POST['phone_local']);
    $full_phone = $country_code . $phone_local;

    // التحقق من الرقم
    if ($country_code === '+967' && !preg_match('/^7[0-9]{8}$/', $phone_local)) {
        $phone_error = 'الرقم اليمني غير صحيح. يجب أن يبدأ بـ 7 ويكون 9 أرقام.';
    } elseif ($country_code === '+966' && !preg_match('/^5[0-9]{8}$/', $phone_local)) {
        $phone_error = 'الرقم السعودي غير صحيح. يجب أن يبدأ بـ 5 ويكون 9 أرقام.';
    } else {
        // إعدادات Ultramsg
        $token = 'liho0pkzrtylk9j2'; // ⚠️ غيّرها
        $instance_id = 'instance131957'; // ⚠️ غيّرها

        $url = "https://api.ultramsg.com/$instance_id/messages/chat?token=$token";

        $message = "🎉 تم شراء باقة الإنترنت بنجاح!\n\n";
        $message .= "👤 اسم المستخدم: {$purchase['username']}\n";
        $message .= "📦 الباقة: {$purchase['package']}\n";
        $message .= "💰 السعر: {$purchase['price']} ر.س\n";
        $message .= "📅 التاريخ: {$purchase['date']}\n\n";
        $message .= "💡 ملاحظة: عند الاتصال بالواي فاي، اترك حقل كلمة المرور فارغًا.";

        $data = [
            'to' => $full_phone,
            'body' => $message
        ];

        // إعداد cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        // تنفيذ الطلب
        $response = curl_exec($ch);

        // جمع الأخطاء
        $curl_error = curl_error($ch);
        $curl_info = curl_getinfo($ch);
        curl_close($ch);

        // تحليل النتيجة
        if ($curl_error) {
            $phone_error = "<strong>❌ خطأ في الاتصال:</strong><br>" . htmlspecialchars($curl_error);
        } else {
            if ($response === false) {
                $phone_error = "❌ لم يتم استلام رد من السيرفر. تحقق من اتصال الإنترنت.";
            } else {
                $result = json_decode($response, true);
                if (isset($result['error'])) {
                    $error_msg = is_array($result['error']) ? implode(', ', $result['error']) : $result['error'];
                    $phone_error = "<strong>❌ خطأ من Ultramsg:</strong><br>" . htmlspecialchars($error_msg);
                } elseif (isset($result['sent']) && in_array(strtolower($result['sent']), ['ok', 'true', 1, '1'])) {
                    $phone_error = "<span style='color:green'><strong>✅ تم الإرسال بنجاح إلى $full_phone</strong></span>";
                } else {
                    $phone_error = "<strong>⚠️ لم تُرسل الرسالة.</strong><br>
                                    <small><strong>الرد:</strong> " . htmlspecialchars($response) . "</small>";
                }
            }
        }
    }
}

// إزالة بيانات الشراء من الجلسة (لمنع إعادة العرض)
unset($_SESSION['last_purchase']);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تم الشراء بنجاح</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .success-container {
            max-width: 700px;
            margin: 60px auto;
            padding: 40px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
        }
        .success-icon {
            font-size: 5rem;
            color: #28a745;
            margin-bottom: 20px;
        }
        .info-table {
            margin: 30px auto;
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td, .info-table th {
            padding: 12px;
            border: 1px solid #dee2e6;
            text-align: center;
        }
        .info-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .note {
            background: #fff3cd;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            font-size: 0.95rem;
            color: #856404;
        }
        .whatsapp-form {
            margin: 30px 0;
            padding: 20px;
            border: 1px dashed #0d6efd;
            border-radius: 10px;
            background: #f8f9ff;
        }
        .btn-action {
            background: #0d6efd;
            color: white;
            padding: 10px 25px;
            border-radius: 10px;
            margin: 5px;
        }
        .alert-custom {
            margin: 10px 0;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.9rem;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h3 class="text-success mb-4">تم الشراء بنجاح!</h3>

        <p>تم إنشاء حسابك على شبكة الواي فاي. استخدم البيانات أدناه للاتصال:</p>

        <!-- جدول المعلومات -->
        <table class="info-table">
            <tr>
                <th>اسم المستخدم</th>
                <th>الباقة</th>
                <th>السعر</th>
                <th>التاريخ</th>
            </tr>
            <tr>
                <td><code><?= htmlspecialchars($purchase['username']) ?></code></td>
                <td><?= htmlspecialchars($purchase['package']) ?></td>
                <td><?= number_format($purchase['price'], 2) ?> ر.س</td>
                <td><?= htmlspecialchars($purchase['date']) ?></td>
            </tr>
        </table>

        <div class="note">
            <strong>ملاحظة:</strong> عند الاتصال بالواي فاي، أدخل اسم المستخدم فقط، ولا تكتب أي شيء في حقل كلمة المرور.
        </div>

        <!-- نموذج إرسال عبر واتساب -->
        <div class="whatsapp-form">
            <h5><i class="fab fa-whatsapp"></i> أرسل البيانات إلى رقمك</h5>
            <form method="POST">
                <div class="row g-2 justify-content-center">
                    <div class="col-md-4">
                        <select name="country_code" class="form-select" required>
                            <option value="+967">+967 🇾🇪 (اليمن)</option>
                            <option value="+966">+966 🇸🇦 (السعودية)</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <input type="text" name="phone_local" class="form-control" placeholder="770123456" pattern="[0-9]{9}" title="أدخل 9 أرقام" required>
                    </div>
                </div>
                <button type="submit" name="send_whatsapp" class="btn-action mt-3">
                    <i class="fab fa-whatsapp"></i> إرسال عبر واتساب
                </button>
            </form>
            <?php if ($phone_error): ?>
                <div class="alert-custom alert alert-danger mt-3">
                    <?= $phone_error ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- زر العودة -->
        <a href="packages.php" class="btn-action">
            <i class="fas fa-arrow-right"></i> العودة إلى الباقات
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
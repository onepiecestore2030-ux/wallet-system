<?php
require_once 'includes/RouterOSAPI.php';

$API = new RouterOSAPI();

// استخدم بياناتك
if ($API->connect('192.168.4.1', 8729, 'admin', 'wal_712334731')) {
    echo "<h3 style='color:green'>✅ اتصال ناجح بالميكروتيك!</h3>";
    
    // جرب جلب الباقات
    $API->write('/ip/hotspot/user/profile/print');
    $profiles = $API->read();
    
    echo "<h4>الباقات:</h4><ul>";
    foreach ($profiles as $p) {
        echo "<li>" . $p['name'] . " - " . ($p['validity'] ?? 'لا يوجد') . "</li>";
    }
    echo "</ul>";
    
    $API->disconnect();
} else {
    echo "<h3 style='color:red'>❌ فشل الاتصال بالميكروتيك</h3>";
    echo "<p>تحقق من:</p>";
    echo "<ul>
        <li>IP: 192.168.4.1</li>
        <li>المنفذ: 8729 أو 8728</li>
        <li>اسم المستخدم وكلمة المرور</li>
        <li>تمكين API في الميكروتيك</li>
        <li>اتصال الشبكة</li>
    </ul>";
}
?>
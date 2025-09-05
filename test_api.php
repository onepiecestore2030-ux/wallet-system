<?php
require('includes/RouterosAPI.php');  // تأكد من المسار الصحيح لملف المكتبة

$API = new RouterosAPI();

$API->debug = false;

if ($API->connect('192.168.4.1', 'leedo', 'Wal_712334731')) {

    // جلب بروفايلات الهوتسبوت (الباقات)
    $profiles = $API->comm("/ip/hotspot/user/profile/print");

    echo "<h2>باقات الهوتسبوت</h2>";
    echo "<table border='1' cellpadding='8' cellspacing='0'>";
    echo "<tr><th>الاسم</th><th>المدة</th><th>السرعة</th></tr>";

    foreach ($profiles as $p) {
        $name = isset($p['name']) ? $p['name'] : '';
        $rate_limit = isset($p['rate-limit']) ? $p['rate-limit'] : 'غير محدد';
        $validity = isset($p['on-login']) ? $p['on-login'] : '—';

        echo "<tr>
                <td>{$name}</td>
                <td>{$validity}</td>
                <td>{$rate_limit}</td>
              </tr>";
    }

    echo "</table>";

    $API->disconnect();

} else {
    echo "فشل الاتصال بالراوتر!";
}
?>

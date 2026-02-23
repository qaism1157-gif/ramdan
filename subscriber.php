<?php
/**
 * 🌐 <?php echo COMPANY_NAME; ?> - بوابة المشتركين
 * صفحة المشتركين الاحترافية
 */
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

define('DB_FILE', __DIR__ . '/onllin_net.db');
define('COMPANY_NAME', 'Onlline Net');
define('CONTACT_PHONE', '0938386346');

function getDB() {
    try {
        $db = new PDO('sqlite:' . DB_FILE);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        die('خطأ في قاعدة البيانات');
    }
}

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subscriber_login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND username != 'admin'");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['subscriber_id'] = $user['id'];
        $_SESSION['subscriber_username'] = $user['username'];
        $_SESSION['subscriber_name'] = $user['full_name'];
        $_SESSION['subscriber_logged_in'] = true;
        header('Location: subscriber.php');
        exit;
    } else {
        $login_error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: subscriber.php');
    exit;
}

$isLoggedIn = isset($_SESSION['subscriber_logged_in']) && $_SESSION['subscriber_logged_in'] === true;

$subscriberData = null;
$subscriptions = [];
$payments = [];
$daysRemaining = 0;
$statusColor = '#10b981';
$statusText = 'نشط';

if ($isLoggedIn) {
    $subscriberId = $_SESSION['subscriber_id'];
    
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$subscriberId]);
    $subscriberData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("SELECT s.*, p.name as package_name, p.speed, p.price, p.data_limit 
        FROM subscriptions s 
        JOIN packages p ON s.package_id = p.id 
        WHERE s.user_id = ? AND s.status = 'active'
        ORDER BY s.end_date DESC");
    $stmt->execute([$subscriberId]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($subscriptions) > 0) {
        $endDate = new DateTime($subscriptions[0]['end_date']);
        $today = new DateTime();
        $diff = $today->diff($endDate);
        $daysRemaining = $diff->days;
        
        if ($today > $endDate) {
            $statusColor = '#ef4444';
            $statusText = 'منتهي';
        } elseif ($daysRemaining <= 7) {
            $statusColor = '#f59e0b';
            $statusText = 'ينتهي قريباً';
        }
    }
    
    $stmt = $db->prepare("SELECT p.*, s.id as sub_id 
        FROM payments p 
        JOIN subscriptions s ON p.subscription_id = s.id 
        WHERE s.user_id = ? 
        ORDER BY p.payment_date DESC 
        LIMIT 10");
    $stmt->execute([$subscriberId]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo COMPANY_NAME; ?> - بوابة المشتركين</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* نفس التنسيقات مع تعديلات بسيطة للبوابة */
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #8b5cf6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --gradient: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Tajawal', sans-serif;
            background: var(--gradient);
            min-height: 100vh;
        }
        
        /* نفس تنسيقات صفحة الإدارة مع تعديلات البوابة */
        .login-page {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-container {
            background: rgba(255,255,255,0.98);
            padding: 50px;
            border-radius: 30px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 25px 80px rgba(0,0,0,0.25);
            text-align: center;
        }
        
        .logo-box {
            width: 100px;
            height: 100px;
            background: var(--gradient);
            border-radius: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            box-shadow: 0 10px 40px rgba(99,102,241,0.4);
        }
        
        .logo-box i { font-size: 50px; color: white; }
        
        .company-name {
            font-size: 32px;
            font-weight: 900;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }
        
        .company-tagline { color: #64748b; margin-bottom: 35px; }
        
        .form-group {
            margin-bottom: 22px;
            text-align: right;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .form-group input {
            width: 100%;
            padding: 16px 55px 16px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            font-size: 15px;
            font-family: 'Tajawal', sans-serif;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99,102,241,0.1);
        }
        
        .login-btn {
            width: 100%;
            padding: 18px;
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 17px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Tajawal', sans-serif;
        }
        
        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(99,102,241,0.5);
        }
        
        .error-msg {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #dc2626;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .info-msg {
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            color: #1e40af;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: right;
        }
        
        .contact-buttons {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 2px dashed #e2e8f0;
        }
        
        .contact-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            margin-bottom: 10px;
            transition: all 0.3s;
        }
        
        .contact-btn.phone {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .contact-btn.chat {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
        }
        
        .contact-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .admin-link {
            display: block;
            margin-top: 20px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
        }
        
        /* بوابة المشتركين */
        .portal { display: none; min-height: 100vh; background: #f1f5f9; }
        .portal.active { display: block; }
        
        .portal-header {
            background: white;
            padding: 20px 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .portal-logo {
            font-size: 28px;
            font-weight: 900;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            background: var(--gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .welcome-card {
            background: var(--gradient);
            color: white;
            padding: 40px;
            border-radius: 25px;
            margin-bottom: 30px;
            box-shadow: 0 15px 50px rgba(99,102,241,0.3);
        }
        
        .welcome-card h1 {
            font-size: 32px;
            margin-bottom: 10px;
            font-weight: 900;
        }
        
        .status-banner {
            background: white;
            padding: 35px;
            border-radius: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            text-align: center;
        }
        
        .status-circle {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 10px solid;
        }
        
        .status-circle span {
            font-size: 42px;
            font-weight: 900;
        }
        
        .status-circle small {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .status-label {
            font-size: 26px;
            font-weight: 800;
            margin-top: 15px;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .card-icon {
            width: 55px;
            height: 55px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .card-icon.blue { background: linear-gradient(135deg, #eff6ff, #dbeafe); color: var(--primary); }
        .card-icon.green { background: linear-gradient(135deg, #ecfdf5, #d1fae5); color: var(--success); }
        .card-icon.orange { background: linear-gradient(135deg, #fffbeb, #fef3c7); color: var(--warning); }
        
        .card-header h3 {
            color: var(--dark);
            font-size: 20px;
            font-weight: 800;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 14px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #64748b; font-weight: 600; }
        .info-value { font-weight: 700; color: var(--dark); }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 18px;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            text-decoration: none;
            color: var(--dark);
            font-weight: 700;
            transition: all 0.3s;
        }
        
        .action-btn:hover {
            border-color: var(--primary);
            background: var(--gradient);
            color: white;
            transform: translateY(-5px);
        }
        
        .footer {
            text-align: center;
            padding: 30px;
            color: rgba(255,255,255,0.9);
            font-size: 14px;
        }
        
        /* زر الدردشة */
        .chat-button {
            position: fixed;
            bottom: 30px;
            left: 30px;
            width: 65px;
            height: 65px;
            background: var(--gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 10px 40px rgba(99,102,241,0.4);
            z-index: 999;
            transition: all 0.3s;
        }
        
        .chat-button i { font-size: 28px; color: white; }
        
        .chat-window {
            position: fixed;
            bottom: 110px;
            left: 30px;
            width: 380px;
            height: 500px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.2);
            z-index: 999;
            display: none;
            flex-direction: column;
        }
        
        .chat-window.active { display: flex; }
        
        @media (max-width: 768px) {
            .login-container { padding: 35px 25px; }
            .grid { grid-template-columns: 1fr; }
            .chat-window { width: calc(100% - 40px); left: 20px; }
        }
    </style>
</head>
<body>
    <?php if (!$isLoggedIn): ?>
    <!-- بوابة تسجيل الدخول -->
    <div class="login-page">
        <div class="login-container">
            <div class="logo-box">
                <i class="fas fa-wifi"></i>
            </div>
            <h1 class="company-name"><?php echo COMPANY_NAME; ?></h1>
            <p class="company-tagline">بوابة المشتركين الإلكترونية</p>
            
            <?php if (isset($login_error)): ?>
            <div class="error-msg">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $login_error; ?>
            </div>
            <?php endif; ?>
            
            <div class="info-msg">
                <i class="fas fa-info-circle"></i>
                <strong>ملاحظة:</strong> إذا لم يكن لديك حساب، تواصل مع الإدارة لإنشاء حساب لك.
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label>اسم المستخدم</label>
                    <input type="text" name="username" required placeholder="اسم المستخدم الخاص بك">
                </div>
                <div class="form-group">
                    <label>كلمة المرور</label>
                    <input type="password" name="password" required placeholder="كلمة المرور">
                </div>
                <button type="submit" name="subscriber_login" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    دخول
                </button>
            </form>
            
            <div class="contact-buttons">
                <p style="color: #64748b; margin-bottom: 15px; font-weight: 600;">هل تحتاج مساعدة؟</p>
                <a href="tel:<?php echo CONTACT_PHONE; ?>" class="contact-btn phone">
                    <i class="fas fa-phone"></i>
                    اتصل بالدعم: <?php echo CONTACT_PHONE; ?>
                </a>
                <a href="#" class="contact-btn chat" onclick="toggleChat(); return false;">
                    <i class="fas fa-comments"></i>
                    دردشة مباشرة
                </a>
            </div>
            
            <a href="admin.php" class="admin-link">
                <i class="fas fa-lock"></i>
                دخول الإدارة
            </a>
        </div>
    </div>
    <?php else: ?>
    
    <!-- بوابة المشتركين -->
    <div class="portal active">
        <header class="portal-header">
            <div class="portal-logo">
                <i class="fas fa-wifi"></i>
                <?php echo COMPANY_NAME; ?>
            </div>
            <div class="user-menu">
                <div style="text-align: left;">
                    <strong><?php echo htmlspecialchars($_SESSION['subscriber_name']); ?></strong><br>
                    <small style="color: #64748b;"><?php echo htmlspecialchars($_SESSION['subscriber_username']); ?></small>
                </div>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['subscriber_name'], 0, 1)); ?>
                </div>
                <a href="?logout=1" class="login-btn" style="padding: 12px 20px; font-size: 14px;">
                    <i class="fas fa-sign-out-alt"></i>
                    خروج
                </a>
            </div>
        </header>
        
        <div class="container">
            <div class="welcome-card">
                <h1>مرحباً، <?php echo htmlspecialchars($_SESSION['subscriber_name']); ?>! 👋</h1>
                <p>يمكنك من هنا متابعة اشتراكك والدفعات والحالة الفنية</p>
            </div>
            
            <div class="status-banner">
                <div class="status-circle" style="border-color: <?php echo $statusColor; ?>;">
                    <span style="color: <?php echo $statusColor; ?>;"><?php echo $daysRemaining; ?></span>
                    <small style="color: <?php echo $statusColor; ?>;">يوم متبقي</small>
                </div>
                <div class="status-label" style="color: <?php echo $statusColor; ?>;">
                    <?php echo $statusText; ?>
                </div>
                <?php if ($daysRemaining <= 7 && $daysRemaining > 0): ?>
                <div style="margin-top: 25px;">
                    <a href="#" class="login-btn" style="background: linear-gradient(135deg, #f59e0b, #d97706);" onclick="toggleChat(); return false;">
                        <i class="fas fa-sync-alt"></i>
                        تجديد الاشتراك الآن
                    </a>
                </div>
                <?php elseif ($daysRemaining == 0 || $statusText == 'منتهي'): ?>
                <div style="margin-top: 25px;">
                    <a href="#" class="login-btn" style="background: linear-gradient(135deg, #ef4444, #dc2626);" onclick="toggleChat(); return false;">
                        <i class="fas fa-exclamation-triangle"></i>
                        اشتراكك منتهي - تواصل للتجديد
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="grid">
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon blue"><i class="fas fa-file-contract"></i></div>
                        <h3>تفاصيل الاشتراك</h3>
                    </div>
                    <?php if (count($subscriptions) > 0): ?>
                    <div class="info-row">
                        <span class="info-label">اسم الباقة</span>
                        <span class="info-value"><?php echo htmlspecialchars($subscriptions[0]['package_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">السرعة</span>
                        <span class="info-value"><?php echo htmlspecialchars($subscriptions[0]['speed']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">حد البيانات</span>
                        <span class="info-value"><?php echo htmlspecialchars($subscriptions[0]['data_limit']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">تاريخ الانتهاء</span>
                        <span class="info-value"><?php echo $subscriptions[0]['end_date']; ?></span>
                    </div>
                    <?php else: ?>
                    <p style="text-align: center; color: #64748b; padding: 30px;">
                        ⚠️ لا يوجد اشتراكات نشطة<br>
                        <small>تواصل مع الإدارة لتفعيل اشتراكك</small>
                    </p>
                    <?php endif; ?>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon green"><i class="fas fa-user"></i></div>
                        <h3>معلوماتي</h3>
                    </div>
                    <div class="info-row">
                        <span class="info-label">الاسم الكامل</span>
                        <span class="info-value"><?php echo htmlspecialchars($subscriberData['full_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">اسم المستخدم</span>
                        <span class="info-value"><?php echo htmlspecialchars($subscriberData['username']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">رقم الهاتف</span>
                        <span class="info-value"><?php echo htmlspecialchars($subscriberData['phone'] ?? 'غير محدد'); ?></span>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon orange"><i class="fas fa-receipt"></i></div>
                        <h3>آخر الدفعات</h3>
                    </div>
                    <?php if (count($payments) > 0): ?>
                    <table style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="text-align: right; padding: 10px; background: #f8fafc;">التاريخ</th>
                                <th style="text-align: right; padding: 10px; background: #f8fafc;">المبلغ</th>
                                <th style="text-align: right; padding: 10px; background: #f8fafc;">الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;"><?php echo $payment['payment_date']; ?></td>
                                <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;"><?php echo number_format($payment['amount']); ?> ل.س</td>
                                <td style="padding: 10px; border-bottom: 1px solid #f1f5f9;">
                                    <span class="badge badge-success">✓</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p style="text-align: center; color: #64748b; padding: 30px;">لا توجد دفعات مسجلة</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <div class="card-icon blue"><i class="fas fa-bolt"></i></div>
                    <h3>إجراءات سريعة</h3>
                </div>
                <div class="quick-actions">
                    <a href="#" class="action-btn" onclick="toggleChat(); return false;">
                        <i class="fas fa-sync-alt"></i>
                        <span>تجديد الاشتراك</span>
                    </a>
                    <a href="#" class="action-btn" onclick="toggleChat(); return false;">
                        <i class="fas fa-headset"></i>
                        <span>دعم فني</span>
                    </a>
                    <a href="#" class="action-btn" onclick="toggleChat(); return false;">
                        <i class="fas fa-box"></i>
                        <span>تغيير الباقة</span>
                    </a>
                    <a href="tel:<?php echo CONTACT_PHONE; ?>" class="action-btn">
                        <i class="fas fa-phone"></i>
                        <span>اتصال مباشر</span>
                    </a>
                </div>
            </div>
        </div>
        
        <footer class="footer">
            <p>© <?php echo date('Y'); ?> <?php echo COMPANY_NAME; ?> - جميع الحقوق محفوظة</p>
            <p style="margin-top: 10px;">
                📞 للدعم الفني: <?php echo CONTACT_PHONE; ?>
            </p>
        </footer>
    </div>
    
    <!-- زر الدردشة -->
    <div class="chat-button" onclick="toggleChat()" style="position: fixed; bottom: 30px; left: 30px; width: 65px; height: 65px; background: var(--gradient); border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 10px 40px rgba(99,102,241,0.4); z-index: 999;">
        <i class="fas fa-comments" style="font-size: 28px; color: white;"></i>
    </div>
    
    <div class="chat-window" id="chatWindow" style="position: fixed; bottom: 110px; left: 30px; width: 380px; height: 500px; background: white; border-radius: 20px; box-shadow: 0 15px 50px rgba(0,0,0,0.2); z-index: 999; display: none; flex-direction: column; overflow: hidden;">
        <div class="chat-header" style="background: var(--gradient); padding: 20px; color: white; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 45px; height: 45px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-headset" style="font-size: 22px;"></i>
                </div>
                <div>
                    <strong style="display: block; font-size: 16px;">الدعم الفني</strong>
                    <small style="opacity: 0.9; font-size: 13px;">متصل الآن</small>
                </div>
            </div>
            <button onclick="toggleChat()" style="background: rgba(255,255,255,0.2); border: none; width: 35px; height: 35px; border-radius: 10px; cursor: pointer; color: white; font-size: 18px;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="chat-messages" id="chatMessages" style="flex: 1; padding: 20px; overflow-y: auto; background: #f8fafc;">
            <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                <div style="width: 35px; height: 35px; background: var(--gradient); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-robot" style="color: white; font-size: 16px;"></i>
                </div>
                <div style="background: white; padding: 12px 16px; border-radius: 15px; max-width: 75%; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                    مرحباً! كيف يمكنني مساعدتك اليوم؟ 🌟
                </div>
            </div>
        </div>
        <div style="padding: 15px; background: white; border-top: 1px solid #e2e8f0; display: flex; gap: 10px;">
            <input type="text" id="chatInput" placeholder="اكتب رسالتك..." style="flex: 1; padding: 12px 18px; border: 2px solid #e2e8f0; border-radius: 25px; font-family: 'Tajawal', sans-serif; font-size: 14px;">
            <button onclick="sendChat()" style="width: 45px; height: 45px; background: var(--gradient); border: none; border-radius: 50%; cursor: pointer; color: white; font-size: 18px;">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
        function toggleChat() {
            const chat = document.getElementById('chatWindow');
            chat.style.display = chat.style.display === 'flex' ? 'none' : 'flex';
        }
        
        function sendChat() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            if (!message) return;
            
            const messagesDiv = document.getElementById('chatMessages');
            messagesDiv.innerHTML += `
                <div style="display: flex; gap: 10px; margin-bottom: 15px; flex-direction: row-reverse;">
                    <div style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; padding: 12px 16px; border-radius: 15px; max-width: 75%;">
                        ${message}
                    </div>
                </div>
            `;
            
            input.value = '';
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
            
            setTimeout(() => {
                messagesDiv.innerHTML += `
                    <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                        <div style="width: 35px; height: 35px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-robot" style="color: white; font-size: 16px;"></i>
                        </div>
                        <div style="background: white; padding: 12px 16px; border-radius: 15px; max-width: 75%; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                            شكراً لتواصلك! سيقوم فريق الدعم بالرد عليك قريباً 🌟
                        </div>
                    </div>
                `;
                messagesDiv.scrollTop = messagesDiv.scrollHeight;
            }, 1000);
        }
    </script>
</body>
</html>
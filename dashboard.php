<?php
/**
 * SalamiPay - Dashboard (Redesigned - Mobile-First, Professional)
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$user = getLoggedInUser($pdo);
if (!$user) {
    session_destroy();
    redirect('/login');
}

$isSuspended = (isset($user['is_suspended']) && $user['is_suspended'] == 1);

if ($isSuspended) {
    echo '<!DOCTYPE html><html lang="bn"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>অ্যাকাউন্ট স্থগিত | SalamiPay</title><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"><style>
        body { background: #0b0f1e; color: #f1f5f9; display: flex; align-items: center; justify-content: center; height: 100vh; font-family: "Sora", sans-serif; margin: 0; }
        .box { text-align: center; padding: 48px 32px; background: #111627; border-radius: 28px; border: 1px solid rgba(239, 68, 68, 0.3); max-width: 400px; width: 90%; box-shadow: 0 20px 50px rgba(0,0,0,0.6); position: relative; overflow: hidden; }
        .box::before { content: ""; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(239, 68, 68, 0.05) 0%, transparent 70%); pointer-events: none; }
        .icon-wrap { width: 80px; height: 80px; background: rgba(239, 68, 68, 0.1); border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; color: #ef4444; font-size: 2.5rem; }
        h1 { font-size: 1.75rem; font-weight: 800; margin-bottom: 12px; color: #fff; }
        p { color: #94a3b8; line-height: 1.6; margin-bottom: 32px; font-size: 1rem; }
        .btn { 
            display: inline-flex; align-items: center; justify-content: center; gap: 10px; 
            padding: 14px 28px; background: linear-gradient(135deg, #ef4444 0%, #d82b2b 100%); 
            color: #fff; text-decoration: none; border-radius: 14px; font-weight: 700; 
            font-size: 0.95rem; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3); border: 1px solid rgba(255,255,255,0.1);
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 12px 25px rgba(239, 68, 68, 0.4); filter: brightness(1.1); }
        .btn:active { transform: translateY(0); }
    </style></head><body>';
    echo '<div class="box"><div class="icon-wrap"><i class="fa-solid fa-user-lock"></i></div><h1>🚫 অ্যাকাউন্ট স্থগিত</h1><p>অতিরিক্ত বালপাকনামী করার জন্য আপনার হোগায় লাত্থি মারা হয়েছে।</p><a href="logout.php" class="btn"><i class="fa-solid fa-power-off"></i> লগআউট করুন</a></div>';
    echo '</body></html>';
    exit;
}

// Handle MFS Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($isSuspended) {
        setFlash('error', 'অতিরিক্ত বালপাকনামী করার জন্য আপনার হোগায় লাত্থি মারা হয়েছে।');
        redirect('/dashboard');
    }
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        setFlash('error', 'CSRF ভেরিফিকেশন ব্যর্থ হয়েছে।');
        redirect('/dashboard');
    }
    

    // AJAX: Delete salami log
    if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'delete_log') {
        header('Content-Type: application/json');
        $logId = intval($_POST['log_id'] ?? 0);
        if ($logId <= 0) { echo json_encode(['success' => false, 'message' => 'অবৈধ লগ।']); exit; }
        $chk = $pdo->prepare("SELECT id FROM salami_logs WHERE id = ? AND receiver_id = ?");
        $chk->execute([$logId, $user['id']]);
        if (!$chk->fetch()) { echo json_encode(['success' => false, 'message' => 'লগটি পাওয়া যায়নি।']); exit; }
        $pdo->prepare("DELETE FROM salami_logs WHERE id = ? AND receiver_id = ?")->execute([$logId, $user['id']]);
        echo json_encode(['success' => true, 'message' => 'লগটি মুছে ফেলা হয়েছে।']);
        exit;
    }

    // AJAX: Block sender by log ID (hides IP from client)
    if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'block_log_id') {
        header('Content-Type: application/json');
        $logId = intval($_POST['log_id'] ?? 0);
        
        // Find the log and its IP
        $stmt = $pdo->prepare("SELECT ip_address FROM salami_logs WHERE id = ? AND receiver_id = ? LIMIT 1");
        $stmt->execute([$logId, $user['id']]);
        $log = $stmt->fetch();
        
        if (!$log || empty($log['ip_address'])) {
            echo json_encode(['success' => false, 'message' => 'লগটি খুঁজে পাওয়া যায়নি।']);
            exit;
        }
        
        $ip = $log['ip_address'];
        $pdo->prepare("INSERT IGNORE INTO blocked_ips (ip_address, note) VALUES (?, ?)")->execute([$ip, "Blocked by user #{$user['id']} (Log #{$logId})"]);
        echo json_encode(['success' => true, 'message' => "প্রেরককে ব্লক করা হয়েছে।"]);
        exit;
    }

    $action = $_POST['mfs_action'] ?? '';

    if ($action === 'add') {
        $mfsType   = $_POST['mfs_type'] ?? '';
        $mfsNumber = trim($_POST['mfs_number'] ?? '');
        $mfsLabel  = trim($_POST['mfs_label'] ?? '');

        $validTypes = ['বিকাশ', 'নগদ', 'রকেট', 'উপায়', 'অন্যান্য'];
        if (!in_array($mfsType, $validTypes)) {
            setFlash('error', 'সঠিক MFS ধরন নির্বাচন করুন।');
        } elseif (empty($mfsNumber)) {
            setFlash('error', 'MFS নম্বর দিন।');
        } else {
            try {
                addMfsAccount($pdo, $user['id'], $mfsType, $mfsNumber, !empty($mfsLabel) ? $mfsLabel : null);
                setFlash('success', getMfsName($mfsType, $mfsLabel) . ' অ্যাকাউন্ট যোগ হয়েছে! 🎉');
            } catch (PDOException $e) {
                setFlash('error', 'MFS যোগ করতে সমস্যা হয়েছে।');
            }
        }
        redirect('/dashboard/mfs');
    }

    if ($action === 'delete') {
        $mfsId = intval($_POST['mfs_id'] ?? 0);
        if ($mfsId > 0) {
            deleteMfsAccount($pdo, $mfsId, $user['id']);
            setFlash('success', 'MFS অ্যাকাউন্ট সরানো হয়েছে।');
        }
        redirect('/dashboard/mfs');
    }

    if ($action === 'set_primary') {
        $mfsId = intval($_POST['mfs_id'] ?? 0);
        if ($mfsId > 0) {
            setMfsPrimary($pdo, $mfsId, $user['id']);
            setFlash('success', 'প্রাথমিক MFS হিসেবে সেট হয়েছে। ✅');
        }
        redirect('/dashboard/mfs');
    }

    if ($action === 'profile_update') {
        $description = trim($_POST['description'] ?? '');
        $profile_image = $user['profile_image'];

        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_image'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $maxSize = 2 * 1024 * 1024;

            if (!in_array($file['type'], $allowedTypes)) {
                setFlash('error', 'শুধুমাত্র JPG, PNG বা WEBP ছবি আপলোড করুন।');
            } elseif ($file['size'] > $maxSize) {
                setFlash('error', 'ছবির সাইজ ২ মেগাবাইটের কম হতে হবে।');
            } else {
                $uploadDir = __DIR__ . '/uploads/profiles/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                $newName = 'user_' . $user['id'] . '_' . time() . '.webp';
                $uploadPath = $uploadDir . $newName;

                // Load image based on type
                $img = null;
                $sourceType = $file['type'];
                
                if ($sourceType === 'image/jpeg') {
                    $img = @imagecreatefromjpeg($file['tmp_name']);
                } elseif ($sourceType === 'image/png') {
                    $img = @imagecreatefrompng($file['tmp_name']);
                    if ($img) {
                        imagepalettetotruecolor($img);
                        imagealphablending($img, true);
                        imagesavealpha($img, true);
                    }
                } elseif ($sourceType === 'image/webp') {
                    $img = @imagecreatefromwebp($file['tmp_name']);
                }

                if ($img) {
                    // Compress and save as WebP (80% quality)
                    if (imagewebp($img, $uploadPath, 80)) {
                        imagedestroy($img);
                        
                        // Delete OLD image if exists
                        if (!empty($user['profile_image'])) {
                            $oldFile = $uploadDir . $user['profile_image'];
                            if (file_exists($oldFile)) {
                                @unlink($oldFile);
                            }
                        }
                        
                        $profile_image = $newName;
                    } else {
                        setFlash('error', 'ছবি সেভ করতে সমস্যা হয়েছে।');
                    }
                } else {
                    setFlash('error', 'ছবি প্রসেস করা সম্ভব হয়নি।');
                }
            }
        }

        try {
            $stmt = $pdo->prepare("UPDATE users SET description = ?, profile_image = ? WHERE id = ?");
            $stmt->execute([$description, $profile_image, $user['id']]);
            setFlash('success', 'প্রোফাইল আপডেট হয়েছে! ✨');
        } catch (PDOException $e) {
            setFlash('error', 'আপডেট করতে সমস্যা হয়েছে।');
        }
        redirect('/dashboard/settings');
    }

    if ($action === 'password_update') {
        $old_password = $_POST['old_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
            setFlash('error', 'সবগুলো ফিল্ড পূরণ করুন।');
        } elseif (!password_verify($old_password, $user['password'])) {
            setFlash('error', 'পুরনো পাসওয়ার্ডটি সঠিক নয়।');
        } elseif ($new_password !== $confirm_password) {
            setFlash('error', 'নতুন পাসওয়ার্ড এবং কনফার্ম পাসওয়ার্ড মিলছে না।');
        } elseif (strlen($new_password) < 6) {
            setFlash('error', 'নতুন পাসওয়ার্ড অন্তত ৬ অক্ষরের হতে হবে।');
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed, $user['id']])) {
                setFlash('success', 'পাসওয়ার্ড সফলভাবে পরিবর্তন করা হয়েছে! 🔒');
            } else {
                setFlash('error', 'পাসওয়ার্ড পরিবর্তন করতে সমস্যা হয়েছে।');
            }
        }
        redirect('/dashboard/settings');
    }
}

// Consolidated Stats Optimization
$stats          = getUserStats($pdo, $user['id']);
$totalAmount    = $stats['total_collected'];
$salamiCount    = $stats['salami_count'];
$mfsAccounts    = getUserMfsAccounts($pdo, $user['id']);
$mfsCount       = (int)$stats['mfs_count'];
$verifiedAmount = $stats['verified_amount'];
$pendingCount   = (int)$stats['pending_count'];
$profileViews   = (int)$stats['views'];

$salamilogs     = getSalamiLogs($pdo, $user['id']);

// Fetch salami logs from blocked IPs for this user
$blockedLogsStmt = $pdo->prepare("
    SELECT sl.*, ma.mfs_type, ma.label as mfs_label, bi.note as block_note
    FROM salami_logs sl
    INNER JOIN blocked_ips bi ON sl.ip_address = bi.ip_address
    LEFT JOIN mfs_accounts ma ON sl.mfs_account_id = ma.id
    WHERE sl.receiver_id = ?
    ORDER BY sl.created_at DESC
");
$blockedLogsStmt->execute([$user['id']]);
$blockedLogs = $blockedLogsStmt->fetchAll();
$blockedCount = count($blockedLogs);

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$shareUrl = $protocol . '://সালামির.পাতা.বাংলা/' . $user['username']. '/';

$nameParts = explode(' ', $user['full_name']);
$initials = mb_substr($nameParts[0], 0, 1);
if (count($nameParts) > 1) $initials .= mb_substr(end($nameParts), 0, 1);

$activeTab = $_GET['tab'] ?? 'overview';
$validTabs = ['overview', 'salami', 'mfs', 'share', 'settings', 'blocked'];
if (!in_array($activeTab, $validTabs)) $activeTab = 'overview';

$pageTitle = 'ড্যাশবোর্ড';
?>
<!DOCTYPE html>
<html lang="bn" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= sanitize($pageTitle) ?> | <?= SITE_NAME ?></title>
    <link rel="icon" type="image/png" href="<?= SITE_FAVICON ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=Noto+Sans+Bengali:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:          #0b0f1e;
            --surface:     #111627;
            --surface-2:   #161d30;
            --border:      rgba(255,255,255,0.07);
            --border-glow: rgba(100,120,255,0.25);

            --p:           #5b6af0;
            --p-light:     #818cf8;
            --p-glow:      rgba(91,106,240,0.35);

            --green:       #22c55e;
            --green-bg:    rgba(34,197,94,0.12);
            --amber:       #f59e0b;
            --amber-bg:    rgba(245,158,11,0.12);
            --red:         #ef4444;
            --red-bg:      rgba(239,68,68,0.12);
            --pink:        #ec4899;
            --pink-bg:     rgba(236,72,153,0.12);

            --t1: #f1f5f9;
            --t2: #94a3b8;
            --t3: #475569;

            --r-sm: 10px;
            --r-md: 16px;
            --r-lg: 22px;
            --r-xl: 28px;

            --sidebar-w: 260px;
            --topbar-h: 64px;
            --bottom-nav-h: 68px;

            --font-en: 'Sora', sans-serif;
            --font-bn: 'Noto Sans Bengali', 'Sora', sans-serif;

            --shadow-card: 0 4px 24px rgba(0,0,0,0.35);
            --shadow-glow: 0 0 30px rgba(91,106,240,0.15);
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: var(--font-bn);
            background: var(--bg);
            color: var(--t1);
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* ── SCROLLBAR ── */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; }

        /* ── FLASH ── */
        .flash-wrap {
            position: fixed; top: 16px; left: 50%; transform: translateX(-50%);
            z-index: 9999; width: min(92vw, 440px);
        }
        .flash-alert {
            display: flex; align-items: center; gap: 10px;
            padding: 14px 18px; border-radius: var(--r-md);
            font-size: 0.875rem; font-weight: 500;
            backdrop-filter: blur(16px);
            animation: slideDown 0.4s cubic-bezier(.22,.68,0,1.2) both;
        }
        .flash-alert.success { background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.3); color: #86efac; }
        .flash-alert.error   { background: rgba(239,68,68,0.15);  border: 1px solid rgba(239,68,68,0.3);  color: #fca5a5; }
        .flash-alert .close-btn { margin-left: auto; cursor: pointer; opacity: 0.6; transition: opacity .2s; background:none; border:none; color:inherit; font-size:1rem; }
        .flash-alert .close-btn:hover { opacity: 1; }

        @keyframes slideDown { from { opacity:0; transform:translateY(-12px); } to { opacity:1; transform:translateY(0); } }

        /* ── LAYOUT ── */
        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            position: fixed; left: 0; top: 0; bottom: 0;
            width: var(--sidebar-w);
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column;
            z-index: 900;
            transition: transform 0.3s cubic-bezier(.4,0,.2,1);
        }

        .sidebar-logo {
            padding: 24px 20px;
            display: flex; align-items: center; gap: 10px;
            border-bottom: 1px solid var(--border);
        }
        .logo-icon {
            width: 38px; height: 38px;
            background: var(--p);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; color: #fff;
            box-shadow: 0 4px 14px var(--p-glow);
        }
        .logo-text { font-family: var(--font-en); font-weight: 800; font-size: 1.1rem; letter-spacing: -.5px; color: var(--t1); }
        .logo-text span { color: var(--p-light); }

        .sidebar-profile {
            padding: 16px 14px;
            margin: 12px 12px 0;
            background: var(--surface-2);
            border-radius: var(--r-md);
            display: flex; align-items: center; gap: 10px;
            position: relative;
        }
        .avatar {
            flex-shrink: 0;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-family: var(--font-en); text-transform: uppercase;
        }
        .avatar-sm { width: 36px; height: 36px; font-size: .8rem; }
        .avatar-md { width: 48px; height: 48px; font-size: 1rem; }
        .avatar-lg { width: 80px; height: 80px; font-size: 1.6rem; }
        .avatar-img { object-fit: cover; border: 2px solid var(--p); }
        .avatar-default { background: linear-gradient(135deg, var(--p) 0%, #8b5cf6 100%); color: #fff; }

        .sidebar-profile-name { font-size: .85rem; font-weight: 700; color: var(--t1); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sidebar-profile-user { font-size: .73rem; color: var(--t3); margin-top: 2px; }

        .sidebar-nav {
            flex: 1; overflow-y: auto; padding: 10px 12px 10px;
            margin-top: 8px;
        }
        .nav-label {
            font-size: .68rem; font-weight: 700; letter-spacing: 1.2px;
            text-transform: uppercase; color: var(--t3);
            padding: 12px 8px 6px;
        }
        .nav-link {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px; border-radius: var(--r-sm);
            color: var(--t2); font-size: .875rem; font-weight: 500;
            text-decoration: none; cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 2px; border: none; background: none; width: 100%;
            position: relative;
        }
        .nav-link:hover { background: rgba(255,255,255,0.05); color: var(--t1); }
        .nav-link.active {
            background: rgba(91,106,240,0.12);
            color: var(--p-light);
            font-weight: 600;
        }
        .nav-link.active::before {
            content: '';
            position: absolute; left: 0; top: 20%; bottom: 20%;
            width: 3px; border-radius: 0 3px 3px 0;
            background: var(--p);
        }
        .nav-link .icon { width: 18px; text-align: center; font-size: .9rem; }
        .nav-badge {
            margin-left: auto; background: var(--red);
            color: #fff; font-size: .65rem; font-weight: 700;
            padding: 2px 7px; border-radius: 20px; font-family: var(--font-en);
        }

        .sidebar-footer {
            padding: 12px;
            border-top: 1px solid var(--border);
        }
        .logout-btn {
            display: flex; align-items: center; gap: 10px;
            width: 100%; padding: 10px 12px; border-radius: var(--r-sm);
            background: none; border: 1px solid rgba(239,68,68,0.2);
            color: var(--red); font-size: .85rem; font-weight: 600;
            cursor: pointer; transition: all .2s; font-family: var(--font-bn);
        }
        .logout-btn:hover { background: var(--red-bg); }

        /* Overlay */
        .overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
            z-index: 800;
        }
        .overlay.show { display: block; }

        /* ── MAIN ── */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1; min-width: 0;
            display: flex; flex-direction: column;
            min-height: 100vh;
        }

        /* ── TOPBAR ── */
        .topbar {
            height: var(--topbar-h);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 24px;
            background: rgba(11,15,30,0.8);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 700;
        }
        .topbar-left { display: flex; align-items: center; gap: 12px; }
        .hamburger {
            display: none; width: 38px; height: 38px;
            background: var(--surface-2); border: 1px solid var(--border);
            border-radius: var(--r-sm); align-items: center; justify-content: center;
            cursor: pointer; color: var(--t1); font-size: 1rem;
            transition: all .2s;
        }
        .hamburger:hover { background: var(--surface); border-color: var(--border-glow); }
        .topbar-greeting h1 { font-size: 1rem; font-weight: 700; color: var(--t1); }
        .topbar-greeting p { font-size: .72rem; color: var(--t3); margin-top: 1px; }

        /* ── CONTENT ── */
        .content {
            flex: 1; padding: 24px;
            padding-bottom: calc(24px + env(safe-area-inset-bottom));
        }

        /* ── TAB PANELS ── */
        .tab-panel { display: none; opacity: 0; transform: translateY(10px); transition: opacity .3s ease, transform .3s ease; }
        .tab-panel.active { display: block; }
        .tab-panel.visible { opacity: 1; transform: translateY(0); }

        /* ── CARDS ── */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            overflow: hidden;
        }
        .card-header {
            padding: 18px 20px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .card-title { font-size: .95rem; font-weight: 700; color: var(--t1); display: flex; align-items: center; gap: 8px; }
        .card-title i { color: var(--p-light); }
        .card-body { padding: 20px; }
        .card-link { font-size: .78rem; font-weight: 600; color: var(--p-light); text-decoration: none; }
        .card-link:hover { color: var(--t1); }

        /* ── STAT CARDS ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            padding: 18px;
            display: flex; flex-direction: column; gap: 10px;
            transition: border-color .2s, transform .2s;
            position: relative; overflow: hidden;
        }
        .stat-card::after {
            content: '';
            position: absolute; inset: 0;
            background: radial-gradient(ellipse at top left, var(--card-glow, transparent) 0%, transparent 70%);
            pointer-events: none;
        }
        .stat-card:hover { transform: translateY(-2px); border-color: var(--border-glow); }

        .stat-card.blue  { --card-glow: rgba(91,106,240,0.08); }
        .stat-card.green { --card-glow: rgba(34,197,94,0.06); }
        .stat-card.pink  { --card-glow: rgba(236,72,153,0.06); }
        .stat-card.amber { --card-glow: rgba(245,158,11,0.06); }

        .stat-icon {
            width: 40px; height: 40px; border-radius: var(--r-sm);
            display: flex; align-items: center; justify-content: center; font-size: .95rem;
        }
        .stat-icon.blue  { background: rgba(91,106,240,0.12); color: var(--p-light); }
        .stat-icon.green { background: var(--green-bg); color: var(--green); }
        .stat-icon.pink  { background: var(--pink-bg); color: var(--pink); }
        .stat-icon.amber { background: var(--amber-bg); color: var(--amber); }

        .stat-val { font-size: 1.55rem; font-weight: 800; letter-spacing: -1px; line-height: 1; color: var(--t1); font-family: var(--font-en); }
        .stat-val.bn { font-family: var(--font-bn); font-size: 1.4rem; }
        .stat-lbl { font-size: .75rem; color: var(--t3); font-weight: 500; }

        /* ── INFO BANNER ── */
        .info-banner {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 16px; border-radius: var(--r-md);
            background: rgba(91,106,240,0.1); border: 1px solid rgba(91,106,240,0.25);
            font-size: .85rem; color: #a5b4fc; margin-bottom: 20px;
        }
        .info-banner a { color: var(--p-light); font-weight: 600; text-decoration: underline; }

        /* ── GRID ROWS ── */
        .two-col { display: grid; grid-template-columns: 1fr 360px; gap: 16px; }
        .side-stack { display: flex; flex-direction: column; gap: 16px; }

        /* ── TABLE ── */
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table thead tr { border-bottom: 1px solid var(--border); }
        .data-table th { padding: 10px 16px; font-size: .72rem; font-weight: 600; color: var(--t3); text-transform: uppercase; letter-spacing: .8px; text-align: left; white-space: nowrap; }
        .data-table td { padding: 14px 16px; font-size: .85rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
        .data-table tbody tr:last-child td { border-bottom: none; }
        .data-table tbody tr { transition: background .15s; }
        .data-table tbody tr:hover { background: rgba(255,255,255,0.025); }

        .sender-cell { display: flex; align-items: center; gap: 10px; }
        .sender-name { font-weight: 600; font-size: .875rem; color: var(--t1); }
        .sender-time { font-size: .72rem; color: var(--t3); margin-top: 2px; }

        .amount-text { font-weight: 800; color: var(--green); font-family: var(--font-en); white-space: nowrap; }
        .trx-id { font-family: monospace; font-size: .75rem; color: var(--t3); }
        .gateway-span { white-space: nowrap; display: inline-flex; align-items: center; gap: 4px; }

        /* ── STATUS BADGE ── */
        .badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 20px; font-size: .72rem; font-weight: 700; }
        .badge-verified { background: var(--green-bg); color: var(--green); }
        .badge-pending  { background: var(--amber-bg); color: var(--amber); }
        .badge-dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }

        .empty-state {
            padding: 48px 20px; text-align: center;
            display: flex; flex-direction: column; align-items: center; gap: 8px;
        }
        .empty-icon { width: 60px; height: 60px; border-radius: 50%; background: var(--surface-2); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: var(--t3); margin-bottom: 4px; }
        .empty-title { font-size: .95rem; font-weight: 700; color: var(--t1); }
        .empty-sub { font-size: .8rem; color: var(--t3); }

        /* ── SHARE BOX ── */
        .share-input-wrap {
            display: flex; align-items: center;
            background: var(--surface-2); border: 1px solid var(--border);
            border-radius: var(--r-md); overflow: hidden;
            transition: border-color .2s;
        }
        .share-input-wrap:focus-within { border-color: var(--border-glow); }
        .share-input-wrap input {
            flex: 1; background: none; border: none; outline: none;
            padding: 12px 14px; font-size: .82rem; color: var(--t2);
            font-family: var(--font-bn);
        }
        .copy-btn {
            padding: 10px 16px; background: var(--p); border: none;
            color: #fff; font-size: .78rem; font-weight: 700; cursor: pointer;
            font-family: var(--font-bn); transition: background .2s;
            white-space: nowrap;
        }
        .copy-btn:hover { background: #4a59d9; }
        .copy-btn.copied { background: var(--green); }

        .share-btns { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
        .share-btn {
            display: flex; align-items: center; gap: 7px;
            padding: 9px 16px; border-radius: var(--r-sm); font-size: .82rem;
            font-weight: 600; text-decoration: none; transition: all .2s;
            border: 1px solid transparent;
        }
        .share-btn.fb  { background: rgba(59,89,152,0.15); color: #748ffc; border-color: rgba(59,89,152,0.25); }
        .share-btn.wa  { background: rgba(37,211,102,0.12); color: #69db7c; border-color: rgba(37,211,102,0.25); }
        .share-btn.tg  { background: rgba(0,136,204,0.12); color: #74c0fc; border-color: rgba(0,136,204,0.25); }
        .share-btn:hover { opacity: .8; transform: translateY(-1px); }

        /* ── MFS CARDS ── */
        .mfs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 12px; }
        .mfs-card {
            background: var(--surface-2); border: 1px solid var(--border);
            border-radius: var(--r-md); padding: 16px;
            display: flex; align-items: center; gap: 12px;
            transition: border-color .2s;
        }
        .mfs-card:hover { border-color: var(--border-glow); }
        .mfs-ico {
            width: 44px; height: 44px; border-radius: var(--r-sm);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; flex-shrink: 0;
        }
        .mfs-info { flex: 1; min-width: 0; }
        .mfs-name { font-size: .875rem; font-weight: 700; color: var(--t1); display: flex; align-items: center; gap: 6px; }
        .mfs-num  { font-size: .78rem; color: var(--t3); margin-top: 2px; font-family: monospace; }
        .primary-star { color: var(--amber); font-size: .75rem; }
        .primary-tag {
            font-size: .62rem; font-weight: 700; padding: 2px 7px; border-radius: 20px;
            background: var(--amber-bg); color: var(--amber); margin-left: 2px;
        }
        .mfs-actions { display: flex; gap: 6px; }
        .icon-btn {
            width: 32px; height: 32px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            border: 1px solid var(--border); background: none;
            color: var(--t3); cursor: pointer; font-size: .8rem;
            transition: all .2s;
        }
        .icon-btn:hover { background: var(--surface); color: var(--t1); border-color: var(--border-glow); }
        .icon-btn.danger:hover { background: var(--red-bg); color: var(--red); border-color: rgba(239,68,68,0.3); }
        .icon-btn.star:hover { background: var(--amber-bg); color: var(--amber); border-color: rgba(245,158,11,0.3); }

        /* ── ACTION BTN ── */
        .btn-primary {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 9px 18px; border-radius: var(--r-sm);
            background: var(--p); color: #fff;
            font-size: .85rem; font-weight: 700; border: none; cursor: pointer;
            font-family: var(--font-bn); text-decoration: none;
            transition: background .2s, transform .15s;
        }
        .btn-primary:hover { background: #4a59d9; transform: translateY(-1px); color: #fff; }
        .btn-ghost {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 9px 18px; border-radius: var(--r-sm);
            background: none; color: var(--t2);
            font-size: .85rem; font-weight: 600;
            border: 1px solid var(--border); cursor: pointer;
            font-family: var(--font-bn); text-decoration: none;
            transition: all .2s;
        }
        .btn-ghost:hover { background: var(--surface-2); color: var(--t1); border-color: var(--border-glow); }

        /* ── FORM CONTROLS ── */
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: .8rem; font-weight: 600; color: var(--t2); margin-bottom: 6px; }
        .form-control {
            width: 100%; padding: 11px 14px;
            background: var(--surface-2); border: 1px solid var(--border);
            border-radius: var(--r-sm); color: var(--t1); font-size: .88rem;
            font-family: var(--font-bn); outline: none; transition: border-color .2s;
            -webkit-appearance: none;
        }
        .form-control:focus { border-color: var(--border-glow); box-shadow: 0 0 0 3px rgba(91,106,240,0.1); }
        .form-control::placeholder { color: var(--t3); }
        textarea.form-control { resize: vertical; min-height: 110px; }
        select.form-control { cursor: pointer; }

        /* ── SETTINGS PROFILE ── */
        .settings-profile { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; }
        .profile-photo-wrap { position: relative; flex-shrink: 0; }
        .profile-photo-wrap img,
        .profile-photo-wrap .avatar-lg { border: 3px solid var(--border); }
        .photo-upload-trigger {
            position: absolute; bottom: 0; right: 0;
            width: 28px; height: 28px; border-radius: 50%;
            background: var(--p); border: 2px solid var(--bg);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: .7rem; color: #fff; transition: background .2s;
        }
        .photo-upload-trigger:hover { background: #4a59d9; }
        .settings-name { font-size: 1.05rem; font-weight: 800; color: var(--t1); }
        .settings-user { font-size: .78rem; color: var(--t3); margin-top: 3px; }
        .help-text { font-size: .75rem; color: var(--t3); margin-top: 5px; }

        /* ── MINI MFS IN OVERVIEW ── */
        .mfs-mini-list { display: flex; flex-direction: column; gap: 8px; }
        .mfs-mini-row {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px; border-radius: var(--r-sm);
            background: var(--surface-2); border: 1px solid var(--border);
        }
        .mfs-mini-name { font-size: .82rem; font-weight: 700; color: var(--t1); }
        .mfs-mini-num  { font-size: .72rem; color: var(--t3); font-family: monospace; }

        /* ── TOGGLE STATUS BTN ── */
        .toggle-status-btn {
            width: 30px; height: 30px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            background: none; border: 1px solid var(--border);
            color: var(--t3); cursor: pointer; font-size: .8rem;
            transition: all .2s;
        }
        .toggle-status-btn.verify:hover { background: var(--green-bg); color: var(--green); border-color: rgba(34,197,94,0.3); }
        .toggle-status-btn.revert:hover { background: var(--amber-bg); color: var(--amber); border-color: rgba(245,158,11,0.3); }

        .view-msg-btn {
            width: 26px; height: 26px; border-radius: 6px;
            display: inline-flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,0.03); border: 1px solid var(--border);
            color: var(--t3); cursor: pointer; font-size: .75rem;
            transition: all .2s;
        }
        .view-msg-btn:hover { background: rgba(91,106,240,0.1); color: var(--p-light); border-color: var(--p-light); }

        /* ── MODAL ── */
        .modal-backdrop {
            display: none; position: fixed; inset: 0; z-index: 1000;
            background: rgba(0,0,0,.7); backdrop-filter: blur(6px);
            align-items: center; justify-content: center;
            padding: 20px;
        }
        .modal-backdrop.open { display: flex; }
        .modal-box {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: var(--r-xl);
            width: 100%; max-width: 440px;
            animation: modalIn .4s cubic-bezier(.34,1.56,.64,1) both;
            max-height: 90vh; overflow-y: auto;
            position: relative;
            box-shadow: 0 24px 48px rgba(0,0,0,0.5);
        }
        @keyframes modalIn { from { transform: scale(0.9) translateY(20px); opacity:0; } to { transform: scale(1) translateY(0); opacity:1; } }
        .modal-header {
            padding: 24px 24px 16px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .modal-title { font-size: .95rem; font-weight: 700; }
        .modal-close { background: none; border: none; color: var(--t3); font-size: 1.1rem; cursor: pointer; padding: 2px; transition: color .2s; }
        .modal-close:hover { color: var(--t1); }
        .modal-body { padding: 0 24px 24px; }
        .modal-footer { padding: 16px 24px 24px; display: flex; justify-content: flex-end; gap: 12px; }

        /* ── BOTTOM NAV (MOBILE) ── */
        .bottom-nav {
            display: none;
            position: fixed; bottom: 0; left: 0; right: 0;
            height: var(--bottom-nav-h);
            background: var(--surface);
            border-top: 1px solid var(--border);
            z-index: 600;
            padding-bottom: env(safe-area-inset-bottom);
            backdrop-filter: blur(16px);
        }
        .bottom-nav-inner {
            height: calc(var(--bottom-nav-h) - env(safe-area-inset-bottom));
            display: flex; align-items: stretch;
        }
        .bnav-item {
            flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 4px; font-size: .65rem; font-weight: 600; color: var(--t3);
            background: none; border: none; cursor: pointer; padding: 6px 4px;
            transition: color .2s; font-family: var(--font-bn); text-decoration: none;
            position: relative;
        }
        .bnav-item i { font-size: 1.1rem; transition: transform .2s; }
        .bnav-item.active { color: var(--p-light); }
        .bnav-item.active i { transform: translateY(-2px); }
        .bnav-item.active::after {
            content: '';
            position: absolute; bottom: -1px; left: 20%; right: 20%;
            height: 2px; background: var(--p); border-radius: 2px;
        }
        .bnav-badge {
            position: absolute; top: 6px; right: calc(50% - 20px);
            background: var(--red); color: #fff; font-size: .55rem; font-weight: 800;
            width: 20px; height: 20px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-family: var(--font-en); border: 2px solid var(--surface);
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 1100px) {
            .two-col { grid-template-columns: 1fr; }
            .side-stack { display: grid; grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-100%);
                box-shadow: 4px 0 24px rgba(0,0,0,.5);
            }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; }
            .hamburger { display: flex; }
            .bottom-nav { display: flex; flex-direction: column; }
            .content {
                padding: 16px;
                padding-bottom: calc(var(--bottom-nav-h) + 16px + env(safe-area-inset-bottom));
            }
        }

        @media (max-width: 767px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .side-stack { grid-template-columns: 1fr; }
            .data-table .hide-mobile { display: none; }
            .mfs-grid { grid-template-columns: 1fr; }
            .topbar { padding: 0 16px; }
            .topbar-greeting h1 { font-size: .9rem; }
            .card-header { padding: 14px 16px; }
            .card-body { padding: 16px; }
            .modal-box { max-height: 85vh; }

            /* Table Mobile Fixes */
            .data-table th, .data-table td { padding: 14px 10px; }
            .data-table th:first-child, .data-table td:first-child { padding-left: 16px; }
            .data-table th:last-child, .data-table td:last-child { padding-right: 16px; }
            .sender-cell { gap: 8px; }
            .avatar-sm { width: 32px; height: 32px; font-size: 0.72rem; }
            .sender-name { font-size: 0.85rem; }
            .sender-time { font-size: 0.68rem; margin-top: 5px; opacity: 0.8; }
            .amount-text { font-size: 0.85rem; }
            .badge { padding: 5px 8px; }
            .badge span:not(.badge-dot) { font-size: 0.68rem; }
        }

        @media (max-width: 480px) {
            .stats-grid { gap: 10px; }
            .stat-card { padding: 14px; }
            .data-table th, .data-table td { padding: 18px 10px; }
            .data-table th:first-child, .data-table td:first-child { padding-left: 14px; }
            .data-table th:last-child, .data-table td:last-child { padding-right: 14px; }
            .avatar-sm { width: 34px; height: 34px; font-size: 0.75rem; }
            .sender-name { font-size: 0.88rem; }
            .sender-time { font-size: 0.7rem; margin-top: 4px; opacity: 0.75; }
            .data-table { min-width: 480px; }
            .badge { width: auto; height: auto; border-radius: 20px; padding: 5px 8px; }
            .badge span:not(.badge-dot) { display: none; }
            .badge-dot { width: 7px; height: 7px; margin-right: 0; }
            .toggle-status-btn { width: 30px; height: 30px; font-size: 0.8rem; }
            .amount-text { font-size: 0.82rem; }
        }

        /* ── UTILITIES ── */
        .text-truncate { overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
        .gap-8 { gap: 8px; }
        .mt-4 { margin-top: 4px; }
        .mt-8 { margin-top: 8px; }
        .mt-16 { margin-top: 16px; }
        .d-flex { display: flex; }
        .align-center { align-items: center; }
        .fw-700 { font-weight: 700; }
        .f-en { font-family: var(--font-en); }
    </style>
</head>
<body>

<?php $flash = getFlash(); if ($flash): ?>
<div class="flash-wrap" id="flashWrap">
    <div class="flash-alert <?= $flash['type'] ?>">
        <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
        <span><?= sanitize($flash['message']) ?></span>
        <button class="close-btn" onclick="document.getElementById('flashWrap').remove()"><i class="fa-solid fa-xmark"></i></button>
    </div>
</div>
<script>setTimeout(() => { const f = document.getElementById('flashWrap'); if(f) f.remove(); }, 4500);</script>
<?php endif; ?>

<div class="overlay" id="overlay"></div>

<div class="layout">
    <!-- ═══════════════ SIDEBAR ═══════════════ -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon"><i class="fa-solid fa-hand-holding-heart"></i></div>
            <span class="logo-text"><?= SITE_NAME ?></span>
        </div>

        <div class="sidebar-profile">
            <?php 
            $profilePic = (!empty($user['profile_image']) && file_exists(__DIR__ . '/uploads/profiles/' . $user['profile_image'])) 
                ? BASE_URL . '/uploads/profiles/' . $user['profile_image'] 
                : BASE_URL . '/assets/img/default-avatar.png';
            ?>
            <img src="<?= $profilePic ?>" class="avatar avatar-sm avatar-img" style="border-radius:50%;">
            <div style="min-width:0;">
                <div class="sidebar-profile-name text-truncate"><?= sanitize($user['full_name']) ?></div>
                <div class="sidebar-profile-user">@<?= sanitize($user['username']) ?></div>
            </div>
            <span style="position:absolute; top:8px; right:8px; font-size:0.6rem; background:rgba(255,255,255,0.03); padding:2px 6px; border-radius:8px; display:inline-flex; align-items:center; gap:4px; color:#fff; border:1px solid rgba(255,255,255,0.03);" title="প্রোফাইল ভিউ">
                <i class="fa-regular fa-eye"></i> <?= bn_number($profileViews) ?>
            </span>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-label">মেনু</div>
            <button class="nav-link <?= $activeTab==='overview'?'active':'' ?>" data-tab="overview">
                <i class="icon fa-solid fa-house"></i><span>ওভারভিউ</span>
            </button>
            <button class="nav-link <?= $activeTab==='salami'?'active':'' ?>" data-tab="salami">
                <i class="icon fa-solid fa-receipt"></i><span>সালামি তালিকা</span>
                <?php if ($pendingCount > 0): ?><span class="nav-badge"><?= bn_number($pendingCount) ?></span><?php endif; ?>
            </button>
            <button class="nav-link <?= $activeTab==='mfs'?'active':'' ?>" data-tab="mfs">
                <i class="icon fa-solid fa-wallet"></i><span>পেমেন্ট মেথড</span>
            </button>
            <button class="nav-link <?= $activeTab==='share'?'active':'' ?>" data-tab="share">
                <i class="icon fa-solid fa-share-nodes"></i><span>শেয়ার করুন</span>
            </button>
            <button class="nav-link <?= $activeTab==='settings'?'active':'' ?>" data-tab="settings">
                <i class="icon fa-solid fa-gear"></i><span>সেটিংস</span>
            </button>
            <button class="nav-link <?= $activeTab==='blocked'?'active':'' ?>" data-tab="blocked" style="color:var(--red);">
                <i class="icon fa-solid fa-ban"></i><span>ব্লক্ড</span>
                <?php if ($blockedCount > 0): ?><span class="nav-badge"><?= bn_number($blockedCount) ?></span><?php endif; ?>
            </button>

            <div class="nav-label" style="margin-top:8px;">পাবলিক</div>
            <a href="<?= BASE_URL ?>/<?= $user['username'] ?>" class="nav-link" target="_blank">
                <i class="icon fa-solid fa-arrow-up-right-from-square"></i><span>প্রোফাইল দেখুন</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="<?= BASE_URL ?>/logout" class="logout-btn">
                <i class="fa-solid fa-right-from-bracket"></i> লগআউট
            </a>
        </div>
    </aside>

    <!-- ═══════════════ MAIN ═══════════════ -->
    <main class="main">

<?php if ($isSuspended): ?>
<div class="suspended-overlay">
    <div class="suspended-card">
        <i class="fa-solid fa-user-lock"></i>
        <h1>অ্যাকাউন্ট স্থগিত!</h1>
        <p>অতিরিক্ত বালপাকনামী করার জন্য আপনার হোগায় লাত্থি মারা হয়েছে।</p>
        <a href="logout.php" class="btn-primary" style="background:var(--red);"><i class="fa-solid fa-power-off"></i> লগআউট করুন</a>
    </div>
</div>
<?php endif; ?>

        <!-- TOPBAR -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="hamburger" id="hamburger" aria-label="Toggle menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="topbar-greeting">
                    <h1>আস্সালামু আলাইকুম, <?= sanitize(explode(' ', $user['full_name'])[0]) ?> 👋</h1>
                    <p><?= date('l, d F Y') ?></p>
                </div>
            </div>
            <a href="<?= BASE_URL ?>/logout" class="btn-ghost" style="padding:7px 14px; font-size:.78rem; display:none;" id="topLogout">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </header>

        <!-- CONTENT -->
        <div class="content">

            <?php if ($mfsCount === 0): ?>
            <div class="info-banner">
                <i class="fa-solid fa-circle-info"></i>
                <span><strong>প্রথমে MFS যোগ করুন!</strong> <a href="#" onclick="switchTab('mfs');return false;">এখানে ক্লিক করুন</a> বিকাশ/নগদ নম্বর যোগ করতে।</span>
            </div>
            <?php endif; ?>

            <!-- ── OVERVIEW ── -->
            <div class="tab-panel <?= $activeTab==='overview'?'active visible':'' ?>" id="panel-overview">

                <div class="stats-grid">
                    <div class="stat-card blue">
                        <div class="stat-icon blue"><i class="fa-solid fa-bangladeshi-taka-sign"></i></div>
                        <div>
                            <div class="stat-val"><?= formatAmount($totalAmount) ?></div>
                            <div class="stat-lbl">মোট সালামি</div>
                        </div>
                    </div>
                    <div class="stat-card green">
                        <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
                        <div>
                            <div class="stat-val"><?= formatAmount($verifiedAmount) ?></div>
                            <div class="stat-lbl">যাচাইকৃত</div>
                        </div>
                    </div>
                    <div class="stat-card pink">
                        <div class="stat-icon pink"><i class="fa-solid fa-heart"></i></div>
                        <div>
                            <div class="stat-val bn"><?= bn_number($salamiCount) ?></div>
                            <div class="stat-lbl">সালামি</div>
                        </div>
                    </div>
                    <div class="stat-card amber">
                        <div class="stat-icon amber"><i class="fa-solid fa-clock"></i></div>
                        <div>
                            <div class="stat-val bn"><?= bn_number($pendingCount) ?></div>
                            <div class="stat-lbl">অপেক্ষমান</div>
                        </div>
                    </div>
                </div>

                <div class="two-col">
                    <!-- Recent table -->
                    <div class="card">
                        <div class="card-header">
                            <span class="card-title"><i class="fa-solid fa-clock-rotate-left"></i> সাম্প্রতিক প্রাপ্তি</span>
                            <a href="#" class="card-link" onclick="switchTab('salami');return false;">সব দেখুন <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                        <div style="overflow-x:auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>প্রেরক</th>
                                        <th>গেটওয়ে</th>
                                        <th>পরিমাণ</th>
                                        <th class="hide-mobile">সময়</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($salamilogs)): ?>
                                        <tr><td colspan="4"><div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-inbox"></i></div><div class="empty-title">কোনো সালামি নেই</div><div class="empty-sub">এখনো কোনো সালামি আসেনি।</div></div></td></tr>
                                    <?php else: foreach (array_slice($salamilogs,0,5) as $log): ?>
                                        <tr>
                                            <td>
                                                <div class="sender-cell">
                                                    <div class="avatar avatar-sm avatar-default" style="font-size:.7rem;"><?= mb_substr($log['sender_name'],0,1) ?></div>
                                                    <div class="sender-name"><?= sanitize($log['sender_name']) ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="gateway-span" style="font-size:.8rem; font-weight:600; color:<?= getMfsColor($log['mfs_type']) ?>;">
                                                    <i class="<?= getMfsIcon($log['mfs_type']) ?>"></i><?= getMfsName($log['mfs_type'], $log['mfs_label']) ?>
                                                </span>
                                            </td>
                                            <td><span class="amount-text"><?= formatAmount($log['amount']) ?></span></td>
                                            <td class="hide-mobile" style="color:var(--t3); font-size:.78rem;"><?= timeAgo($log['created_at']) ?></td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="side-stack">
                        <!-- Share quick -->
                        <div class="card card-body" style="padding:20px;">
                            <div class="card-title" style="margin-bottom:14px;"><i class="fa-solid fa-share-nodes"></i> লিংক শেয়ার</div>
                            <div class="share-input-wrap">
                                <input type="text" value="<?= sanitize($shareUrl) ?>" readonly id="overviewLink">
                                <button class="copy-btn" data-target="overviewLink">কপি</button>
                            </div>
                            <div class="share-btns">
                                <a href="https://wa.me/?text=<?= urlencode('আমার সালামি পেজ: '.$shareUrl) ?>" target="_blank" class="share-btn wa"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($shareUrl) ?>" target="_blank" class="share-btn fb"><i class="fa-brands fa-facebook-f"></i> Facebook</a>
                            </div>
                        </div>

                        <!-- MFS preview -->
                        <div class="card card-body" style="padding:20px;">
                            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                                <div class="card-title"><i class="fa-solid fa-wallet"></i> পেমেন্ট মেথড</div>
                                <a href="#" class="card-link" onclick="switchTab('mfs');return false;"><i class="fa-solid fa-plus"></i></a>
                            </div>
                            <div class="mfs-mini-list">
                                <?php if (empty($mfsAccounts)): ?>
                                    <div style="text-align:center; padding:20px; color:var(--t3); font-size:.82rem;">কোনো মেথড যোগ নেই।</div>
                                <?php else: foreach (array_slice($mfsAccounts,0,3) as $mfs): ?>
                                    <div class="mfs-mini-row">
                                        <div class="mfs-ico" style="width:36px;height:36px; background:<?= getMfsColor($mfs['mfs_type']) ?>22; color:<?= getMfsColor($mfs['mfs_type']) ?>; font-size:.85rem;">
                                            <i class="<?= getMfsIcon($mfs['mfs_type']) ?>"></i>
                                        </div>
                                        <div>
                                            <div class="mfs-mini-name"><?= getMfsName($mfs['mfs_type'], $mfs['label']) ?></div>
                                            <div class="mfs-mini-num"><?= sanitize($mfs['mfs_number']) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── SALAMI LIST ── -->
            <div class="tab-panel <?= $activeTab==='salami'?'active visible':'' ?>" id="panel-salami">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title"><i class="fa-solid fa-receipt"></i> সালামি সংগ্রহশালা</div>
                            <div style="font-size:.75rem; color:var(--t3); margin-top:3px;">সকল প্রাপ্ত ডিজিটাল সালামির বিবরণ</div>
                        </div>
                        <span class="badge badge-verified"><?= bn_number($salamiCount) ?>টি মোট</span>
                    </div>
                    <div style="overflow-x:auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>প্রেরক</th>
                                    <th>গেটওয়ে</th>
                                    <th>পরিমাণ</th>
                                    <th style="text-align:center;">TrxID / বার্তা</th>
                                    <th>স্ট্যাটাস</th>
                                    <th>অ্যাকশন</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($salamilogs)): ?>
                                    <tr><td colspan="6"><div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-inbox"></i></div><div class="empty-title">কোনো সালামি নেই</div><div class="empty-sub">শেয়ার লিংকটি ছড়িয়ে দিন!</div></div></td></tr>
                                <?php else: foreach ($salamilogs as $log): ?>
                                    <tr>
                                        <td>
                                            <div class="sender-cell">
                                                <div class="avatar avatar-sm avatar-default" style="font-size:.7rem;"><?= mb_substr($log['sender_name'],0,1) ?></div>
                                                <div>
                                                    <div class="sender-name"><?= sanitize($log['sender_name']) ?></div>
                                                    <div class="sender-time"><?= timeAgo($log['created_at']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="gateway-span" style="font-size:.8rem; font-weight:600; color:<?= getMfsColor($log['mfs_type']) ?>;">
                                                <i class="<?= getMfsIcon($log['mfs_type']) ?>"></i><?= getMfsName($log['mfs_type'], $log['mfs_label']) ?>
                                            </span>
                                        </td>
                                        <td><span class="amount-text"><?= formatAmount($log['amount']) ?></span></td>
                                        <td style="text-align:center;">
                                            <div style="display:flex; align-items:center; justify-content:center;">
                                                <?php if ($log['trx_id'] || $log['message']): ?>
                                                    <button class="view-msg-btn btn-view-message"
                                                            data-sender="<?= sanitize($log['sender_name']) ?>"
                                                            data-message="<?= sanitize($log['message']) ?>"
                                                            data-trx="<?= sanitize($log['trx_id']) ?>"
                                                            title="বিস্তারিত দেখুন">
                                                        <i class="fa-solid fa-eye"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <span style="color:var(--t3); height:26px; display:flex; align-items:center;">—</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($log['status'] === 'verified'): ?>
                                                <span class="badge badge-verified"><span class="badge-dot"></span>যাচাইকৃত</span>
                                            <?php else: ?>
                                                <span class="badge badge-pending"><span class="badge-dot"></span>অপেক্ষায়</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="display:flex;gap:6px;align-items:center;">
                                                <button class="toggle-status-btn <?= $log['status']==='pending'?'verify':'revert' ?> btn-toggle-status"
                                                        data-id="<?= $log['id'] ?>" data-status="<?= $log['status'] ?>"
                                                        title="<?= $log['status']==='pending'?'যাচাই করুন':'পেন্ডিং করুন' ?>">
                                                    <i class="fa-solid <?= $log['status']==='pending'?'fa-check':'fa-rotate-left' ?>"></i>
                                                </button>
                                                <?php if (!empty($log['ip_address'])): ?>
                                                <button class="toggle-status-btn btn-block-ip" data-id="<?= $log['id'] ?>"
                                                        title="প্রেরককে ব্লক করুন" style="background:rgba(239,68,68,0.05); border-color:rgba(239,68,68,0.2); color:var(--red);">
                                                    <i class="fa-solid fa-ban"></i>
                                                </button>
                                                <?php endif; ?>
                                                <button class="toggle-status-btn btn-delete-log" data-id="<?= $log['id'] ?>"
                                                        title="লগ মুছুন" style="background:rgba(239,68,68,0.1);border-color:rgba(239,68,68,0.3);color:var(--red);">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ── MFS ── -->
            <div class="tab-panel <?= $activeTab==='mfs'?'active visible':'' ?>" id="panel-mfs">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fa-solid fa-wallet"></i> MFS অ্যাকাউন্ট</div>
                        <button class="btn-primary" onclick="document.getElementById('addMfsModal').classList.add('open')">
                            <i class="fa-solid fa-plus"></i> নতুন যোগ
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($mfsAccounts)): ?>
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fa-solid fa-mobile-screen"></i></div>
                                <div class="empty-title">কোনো MFS নেই</div>
                                <div class="empty-sub">বিকাশ, নগদ বা অন্য MFS যোগ করুন।</div>
                                <button class="btn-primary" style="margin-top:12px;" onclick="document.getElementById('addMfsModal').classList.add('open')">
                                    <i class="fa-solid fa-plus"></i> এখনই যোগ করুন
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="mfs-grid">
                                <?php foreach ($mfsAccounts as $mfs): ?>
                                <div class="mfs-card">
                                    <div class="mfs-ico" style="background:<?= getMfsColor($mfs['mfs_type']) ?>20; color:<?= getMfsColor($mfs['mfs_type']) ?>;">
                                        <i class="<?= getMfsIcon($mfs['mfs_type']) ?>"></i>
                                    </div>
                                    <div class="mfs-info">
                                        <div class="mfs-name">
                                            <?= getMfsName($mfs['mfs_type'], $mfs['label']) ?>
                                            <?php if ($mfs['is_primary']): ?>
                                                <i class="fa-solid fa-star primary-star"></i>
                                                <span class="primary-tag">প্রাথমিক</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mfs-num"><?= sanitize($mfs['mfs_number']) ?></div>
                                    </div>
                                    <div class="mfs-actions">
                                        <?php if (!$mfs['is_primary']): ?>
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                            <input type="hidden" name="mfs_action" value="set_primary">
                                            <input type="hidden" name="mfs_id" value="<?= $mfs['id'] ?>">
                                            <button type="submit" class="icon-btn star" title="প্রাথমিক করুন"><i class="fa-solid fa-star"></i></button>
                                        </form>
                                        <?php endif; ?>
                                        <form method="POST" onsubmit="return confirm('এই MFS মুছে ফেলতে চান?')">
                                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                            <input type="hidden" name="mfs_action" value="delete">
                                            <input type="hidden" name="mfs_id" value="<?= $mfs['id'] ?>">
                                            <button type="submit" class="icon-btn danger" title="মুছুন"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ── SHARE ── -->
            <div class="tab-panel <?= $activeTab==='share'?'active visible':'' ?>" id="panel-share">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fa-solid fa-share-nodes"></i> লিংক শেয়ার করুন</div>
                    </div>
                    <div class="card-body">
                        <p style="color:var(--t2); font-size:.875rem; line-height:1.7; margin-bottom:16px;">
                            নিচের লিংকটি কপি করে Facebook, WhatsApp বা যেকোনো জায়গায় শেয়ার করুন। যে কেউ ক্লিক করলে আপনার সালামি পেজ দেখতে পাবে।
                        </p>
                        <div class="share-input-wrap">
                            <input type="text" value="<?= sanitize($shareUrl) ?>" readonly id="shareLink" style="font-size:.82rem;">
                            <button class="copy-btn" data-target="shareLink">কপি করুন</button>
                        </div>
                        <div class="share-btns" style="margin-top:16px;">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($shareUrl) ?>" target="_blank" class="share-btn fb"><i class="fa-brands fa-facebook-f"></i> Facebook</a>
                            <a href="https://wa.me/?text=<?= urlencode('আমার সালামি পেজ: '.$shareUrl) ?>" target="_blank" class="share-btn wa"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
                            <a href="https://t.me/share/url?url=<?= urlencode($shareUrl) ?>&text=<?= urlencode('আমার সালামি পেজ') ?>" target="_blank" class="share-btn tg"><i class="fa-brands fa-telegram"></i> Telegram</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── SETTINGS ── -->
            <div class="tab-panel <?= $activeTab==='settings'?'active visible':'' ?>" id="panel-settings">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fa-solid fa-gear"></i> প্রোফাইল সেটিংস</div>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="mfs_action" value="profile_update">

                            <div class="settings-profile">
                                <div class="profile-photo-wrap">
                                    <?php 
                                    $settingsPic = (!empty($user['profile_image']) && file_exists(__DIR__ . '/uploads/profiles/' . $user['profile_image'])) 
                                        ? BASE_URL . '/uploads/profiles/' . $user['profile_image'] 
                                        : null;
                                    ?>
                                    <?php if ($settingsPic): ?>
                                        <img src="<?= $settingsPic ?>" id="profilePreview" class="avatar avatar-lg avatar-img" style="border-radius:var(--r-md);">
                                    <?php else: ?>
                                        <div id="profilePreview" class="avatar avatar-lg avatar-default" style="border-radius:var(--r-md);"><?= mb_strtoupper($initials) ?></div>
                                    <?php endif; ?>
                                    <label for="profile_image" class="photo-upload-trigger">
                                        <i class="fa-solid fa-camera"></i>
                                        <input type="file" id="profile_image" name="profile_image" accept="image/*" style="display:none;" onchange="previewImage(this)">
                                    </label>
                                </div>
                                <div>
                                    <div class="settings-name"><?= sanitize($user['full_name']) ?></div>
                                    <div class="settings-user">@<?= sanitize($user['username']) ?></div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="description">প্রোফাইল বিবরণ</label>
                                <textarea class="form-control" id="description" name="description" placeholder="আপনি কে বা সালামি কেন চাচ্ছেন?"><?= sanitize($user['description'] ?? '') ?></textarea>
                                <div class="help-text"><i class="fa-solid fa-circle-info" style="margin-right:4px;"></i>এটি আপনার পাবলিক সালামি পেজে দেখা যাবে।</div>
                            </div>

                            <button type="submit" class="btn-primary">
                                <i class="fa-solid fa-floppy-disk"></i> সেভ করুন
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card" style="margin-top:24px;">
                    <div class="card-header">
                        <div class="card-title"><i class="fa-solid fa-lock"></i> পাসওয়ার্ড পরিবর্তন</div>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="mfs_action" value="password_update">
                            
                            <div class="form-group">
                                <label class="form-label" for="old_password">বর্তমান পাসওয়ার্ড <span style="color:var(--red);">*</span></label>
                                <input type="password" class="form-control" id="old_password" name="old_password" placeholder="আপনার বর্তমান পাসওয়ার্ড দিন" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="new_password">নতুন পাসওয়ার্ড <span style="color:var(--red);">*</span></label>
                                <input type="password" class="form-control" id="new_password" name="new_password" placeholder="কমপক্ষে ৬ অক্ষরের নতুন পাসওয়ার্ড" required minlength="6">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="confirm_password">নতুন পাসওয়ার্ড আবার লিখুন <span style="color:var(--red);">*</span></label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="পুনরায় নতুন পাসওয়ার্ডটি দিন" required minlength="6">
                            </div>
                            
                            <button type="submit" class="btn-primary" style="background:#475569;">
                                <i class="fa-solid fa-key"></i> পাসওয়ার্ড আপডেট করুন
                            </button>
                        </form>
                    </div>
                </div>
            </div>


            <!-- ── BLOCKED IPs LOG ── -->
            <div class="tab-panel <?= $activeTab==='blocked'?'active visible':'' ?>" id="panel-blocked">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title" style="color:var(--red);"><i class="fa-solid fa-ban"></i> ব্লক্ড সালামি লগ</div>
                            <div style="font-size:.75rem; color:var(--t3); margin-top:3px;">ব্লক করা প্রেরক থেকে আসা সকল সালামির তালিকা</div>
                        </div>
                        <span class="badge" style="background:rgba(239,68,68,0.12);color:var(--red);border:1px solid rgba(239,68,68,0.25);"><?= bn_number($blockedCount) ?>টি</span>
                    </div>
                    <div style="overflow-x:auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>প্রেরক</th>
                                    <th>গেটওয়ে</th>
                                    <th>পরিমাণ</th>
                                    <th style="text-align:center;">TrxID / বার্তা</th>
                                    <th>সময়</th>
                                    <th>অ্যাকশন</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($blockedLogs)): ?>
                                    <tr><td colspan="6"><div class="empty-state">
                                        <div class="empty-icon" style="color:var(--red);"><i class="fa-solid fa-shield-check"></i></div>
                                        <div class="empty-title">কোনো ব্লক্ড লগ নেই</div>
                                        <div class="empty-sub">কোনো প্রেরককে ব্লক করা হয়নি।</div>
                                    </div></td></tr>
                                <?php else: foreach ($blockedLogs as $log): ?>
                                    <tr style="border-left:3px solid var(--red); background:rgba(239,68,68,0.03);">
                                        <td>
                                            <div class="sender-cell">
                                                <div class="avatar avatar-sm" style="background:rgba(239,68,68,0.15);color:var(--red);font-size:.7rem;"><?= mb_substr($log['sender_name'],0,1) ?></div>
                                                <div>
                                                    <div class="sender-name" style="color:var(--red);"><?= sanitize($log['sender_name']) ?></div>
                                                    <div class="sender-time"><?= timeAgo($log['created_at']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="gateway-span" style="font-size:.8rem; font-weight:600; color:<?= getMfsColor($log['mfs_type']) ?>;">
                                                <i class="<?= getMfsIcon($log['mfs_type']) ?>"></i><?= getMfsName($log['mfs_type'], $log['mfs_label']) ?>
                                            </span>
                                        </td>
                                        <td><span class="amount-text" style="color:var(--red);"><?= formatAmount($log['amount']) ?></span></td>
                                        <td style="text-align:center;">
                                            <div style="display:flex;align-items:center;justify-content:center;">
                                                <?php if ($log['trx_id'] || $log['message']): ?>
                                                    <button class="view-msg-btn btn-view-message"
                                                            data-sender="<?= sanitize($log['sender_name']) ?>"
                                                            data-message="<?= sanitize($log['message']) ?>"
                                                            data-trx="<?= sanitize($log['trx_id']) ?>"
                                                            title="বিস্তারিত দেখুন">
                                                        <i class="fa-solid fa-eye"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <span style="color:var(--t3);">—</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td style="font-size:.78rem;color:var(--t3);"><?= timeAgo($log['created_at']) ?></td>
                                        <td>
                                            <button class="toggle-status-btn btn-delete-log" data-id="<?= $log['id'] ?>"
                                                    title="লগ মুছুন" style="background:rgba(239,68,68,0.1);border-color:rgba(239,68,68,0.3);color:var(--red);">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div><!-- /content -->
    </main>

    <!-- ── BOTTOM NAV ── -->
    <nav class="bottom-nav">
        <div class="bottom-nav-inner">
            <button class="bnav-item <?= $activeTab==='overview'?'active':'' ?>" data-tab="overview">
                <i class="fa-solid fa-house"></i><span>হোম</span>
            </button>
            <button class="bnav-item <?= $activeTab==='salami'?'active':'' ?>" data-tab="salami">
                <i class="fa-solid fa-receipt"></i><span>সালামি</span>
                <?php if ($pendingCount > 0): ?><span class="bnav-badge"><?= $pendingCount ?></span><?php endif; ?>
            </button>
            <button class="bnav-item <?= $activeTab==='mfs'?'active':'' ?>" data-tab="mfs">
                <i class="fa-solid fa-wallet"></i><span>MFS</span>
            </button>
            <button class="bnav-item <?= $activeTab==='share'?'active':'' ?>" data-tab="share">
                <i class="fa-solid fa-share-nodes"></i><span>শেয়ার</span>
            </button>
            <button class="bnav-item <?= $activeTab==='settings'?'active':'' ?>" data-tab="settings">
                <i class="fa-solid fa-gear"></i><span>সেটিংস</span>
            </button>
            <button class="bnav-item <?= $activeTab==='blocked'?'active':'' ?>" data-tab="blocked" style="<?= $activeTab==='blocked'?'color:var(--red)':'' ?>">
                <i class="fa-solid fa-ban"></i><span>ব্লক্ড</span>
                <?php if ($blockedCount > 0): ?><span class="bnav-badge" style="background:var(--red);"><?= $blockedCount ?></span><?php endif; ?>
            </button>
        </div>
    </nav>
</div>

<!-- ── ADD MFS MODAL ── -->
<div class="modal-backdrop" id="addMfsModal">
    <div class="modal-box">
        <div class="modal-header">
            <span class="modal-title"><i class="fa-solid fa-plus-circle" style="color:var(--p-light); margin-right:8px;"></i>নতুন MFS যোগ করুন</span>
            <button class="modal-close" onclick="document.getElementById('addMfsModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="mfs_action" value="add">
                <div class="form-group">
                    <label class="form-label">MFS ধরন</label>
                    <select class="form-control" name="mfs_type" id="addMfsType" required>
                        <option value="" disabled selected>নির্বাচন করুন…</option>
                        <option value="বিকাশ">বিকাশ</option>
                        <option value="নগদ">নগদ</option>
                        <option value="রকেট">রকেট</option>
                        <option value="উপায়">উপায়</option>
                        <option value="অন্যান্য">অন্যান্য</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">MFS নম্বর</label>
                    <input type="tel" class="form-control" name="mfs_number" placeholder="01XXXXXXXXX" required>
                </div>
                <div class="form-group" id="labelGroup" style="margin-bottom:0; display:none;">
                    <label class="form-label">লেবেল <span style="color:var(--t3); font-weight:400;">(ঐচ্ছিক)</span></label>
                    <input type="text" class="form-control" name="mfs_label" placeholder="যেমন: ব্যক্তিগত, অফিস…" maxlength="50">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-ghost" onclick="document.getElementById('addMfsModal').classList.remove('open')">বাতিল</button>
                <button type="submit" class="btn-primary"><i class="fa-solid fa-plus"></i> যোগ করুন</button>
            </div>
        </form>
    </div>
</div>

<!-- ── VIEW MESSAGE MODAL ── -->
<div class="modal-backdrop" id="msgModal">
    <div class="modal-box">
        <div class="modal-header">
            <span class="modal-title"><i class="fa-solid fa-envelope-open-text" style="color:var(--p-light); margin-right:8px;"></i>সালামি বার্তা</span>
            <button class="modal-close" onclick="document.getElementById('msgModal').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:16px;">
                <div style="font-size:0.8rem; color:var(--t3);">প্রেরক: <span id="msgSender" style="color:var(--t1); font-weight:700;"></span></div>
                <div style="font-size:0.8rem; color:var(--t3);">TrxID: <span id="msgTrx" style="color:var(--p-light); font-weight:700; font-family:monospace;"></span></div>
            </div>
            <div id="msgTextWrap">
                <div style="font-size:0.75rem; color:var(--t3); margin-bottom:6px;">বার্তা:</div>
                <div id="msgText" style="font-size:0.95rem; line-height:1.6; color:var(--t2); background:var(--surface-2); padding:16px; border-radius:var(--r-md); border:1px solid var(--border); white-space: pre-wrap;"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-primary" onclick="document.getElementById('msgModal').classList.remove('open')">ঠিক আছে</button>
        </div>
    </div>
</div>

<script>
window.BASE_URL = '<?= BASE_URL ?>';

(function(){
    // Sidebar toggle
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const ham = document.getElementById('hamburger');
    if (ham) ham.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('show');
    });
    if (overlay) overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    });

    // Tab switching
    window.switchTab = function(tabName) {
        const panels = document.querySelectorAll('.tab-panel');
        panels.forEach(p => { if (p.classList.contains('active')) p.classList.remove('visible'); });

        setTimeout(() => {
            panels.forEach(p => p.classList.remove('active'));
            const target = document.getElementById('panel-' + tabName);
            if (target) {
                target.classList.add('active');
                requestAnimationFrame(() => requestAnimationFrame(() => target.classList.add('visible')));
            }
            document.querySelectorAll('[data-tab]').forEach(el => {
                el.classList.toggle('active', el.dataset.tab === tabName);
            });
            const newUrl = window.BASE_URL + '/dashboard' + (tabName === 'overview' ? '' : '/' + tabName);
            history.pushState({ tab: tabName }, '', newUrl);
        }, 220);

        if (window.innerWidth < 992) {
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
        }
    };

    document.querySelectorAll('[data-tab]').forEach(el => {
        el.addEventListener('click', e => {
            if (el.tagName === 'A') e.preventDefault();
            switchTab(el.dataset.tab);
        });
    });

    window.addEventListener('popstate', e => { if (e.state?.tab) switchTab(e.state.tab); });
    history.replaceState({ tab: '<?= $activeTab ?>' }, '', window.location.href);

    // Copy buttons
    document.querySelectorAll('.copy-btn[data-target]').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.target);
            if (!input) return;
            navigator.clipboard?.writeText(input.value).then(() => {
                btn.textContent = '✓ কপি হয়েছে';
                btn.classList.add('copied');
                setTimeout(() => { btn.textContent = btn.dataset.target === 'overviewLink' ? 'কপি' : 'কপি করুন'; btn.classList.remove('copied'); }, 2000);
            });
        });
    });

    // Modal close on backdrop click
    document.getElementById('addMfsModal')?.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('open');
    });

    document.getElementById('msgModal')?.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('open');
    });

    // View Message
    document.querySelectorAll('.btn-view-message').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('msgSender').textContent = btn.dataset.sender || 'Unknown';
            document.getElementById('msgTrx').textContent = btn.dataset.trx || '—';
            
            const msgBody = document.getElementById('msgText');
            const msgWrap = document.getElementById('msgTextWrap');
            if (btn.dataset.message) {
                msgBody.textContent = btn.dataset.message;
                msgWrap.style.display = 'block';
            } else {
                msgWrap.style.display = 'none';
            }
            
            document.getElementById('msgModal').classList.add('open');
        });
    });

    // Profile image preview
    window.previewImage = function(input) {
        if (!input.files?.[0]) return;
        const reader = new FileReader();
        reader.onload = e => {
            const p = document.getElementById('profilePreview');
            if (p.tagName === 'IMG') { p.src = e.target.result; return; }
            const img = document.createElement('img');
            img.src = e.target.result;
            img.id = 'profilePreview';
            img.className = 'avatar avatar-lg avatar-img';
            img.style.borderRadius = 'var(--r-md)';
            p.replaceWith(img);
        };
        reader.readAsDataURL(input.files[0]);
    };

    // Toggle label field based on MFS type
    const typeSelect = document.getElementById('addMfsType');
    const labelGroup = document.getElementById('labelGroup');
    if (typeSelect && labelGroup) {
        typeSelect.addEventListener('change', function() {
            if (this.value === 'অন্যান্য') {
                labelGroup.style.display = 'block';
            } else {
                labelGroup.style.display = 'none';
            }
        });
    }

    // ── Delete salami log ──
    document.querySelectorAll('.btn-delete-log').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            Swal.fire({
                title: 'লগ মুছবেন?',
                text: 'এই সালামি লগটি স্থায়ীভাবে মুছে যাবে।',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#475569',
                confirmButtonText: 'হ্যাঁ, মুছুন',
                cancelButtonText: 'বাতিল',
                background: '#111627',
                color: '#f1f5f9'
            }).then(result => {
                if (!result.isConfirmed) return;
                const fd = new FormData();
                fd.append('csrf_token', '<?= generateCsrfToken() ?>');
                fd.append('ajax_action', 'delete_log');
                fd.append('log_id', id);
                fetch(window.location.href, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({ title: 'মুছে গেছে!', text: data.message, icon: 'success', background: '#111627', color: '#f1f5f9', timer: 1500, showConfirmButton: false })
                            .then(() => location.reload());
                        } else {
                            Swal.fire({ title: 'এরর!', text: data.message, icon: 'error', background: '#111627', color: '#f1f5f9' });
                        }
                    });
            });
        });
    });

    // ── Block sender IP ──
    document.querySelectorAll('.btn-block-ip').forEach(btn => {
        btn.addEventListener('click', function() {
            const logId = this.dataset.id;
            Swal.fire({
                title: 'প্রেরককে ব্লক করুন',
                html: `<p style="color:#94a3b8;margin-bottom:12px;">আপনি কি এই প্রেরককে ব্লক করতে নিশ্চিত?</p>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#475569',
                confirmButtonText: 'ব্লক করুন',
                cancelButtonText: 'বাতিল',
                background: '#111627',
                color: '#f1f5f9'
            }).then(result => {
                if (!result.isConfirmed) return;
                const fd = new FormData();
                fd.append('csrf_token', '<?= generateCsrfToken() ?>');
                fd.append('ajax_action', 'block_log_id');
                fd.append('log_id', logId);
                fd.append('note', '');
                fetch(window.location.href, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(data => {
                        Swal.fire({
                            title: data.success ? 'ব্লক হয়েছে!' : 'এরর!',
                            text: data.message,
                            icon: data.success ? 'success' : 'error',
                            background: '#111627', color: '#f1f5f9',
                            timer: data.success ? 1500 : undefined,
                            showConfirmButton: !data.success
                        }).then(() => { if (data.success) location.reload(); });
                    });
            });
        });
    });

    // Toggle salami status
    document.querySelectorAll('.btn-toggle-status').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const currentStatus = this.dataset.status;
            const newStatus = currentStatus === 'pending' ? 'verified' : 'pending';
            
            this.disabled = true;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

            fetch(window.BASE_URL + '/status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ id: id, status: newStatus })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Error occurred');
                    this.disabled = false;
                    this.innerHTML = '<i class="fa-solid ' + (currentStatus === 'pending' ? 'fa-check' : 'fa-rotate-left') + '"></i>';
                }
            })
            .catch(e => {
                console.error(e);
                window.location.reload(); // Reload anyway as a fallback
            });
        });
    });
})();
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= BASE_URL ?>/assets/js/notice.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
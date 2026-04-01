<?php
/**
 * SalamiPay - Public Profile Page (Redesigned)
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Dynamic base path (works regardless of installation folder, no hard-coded constant)
$sitePath = rtrim(str_replace('\\', '/', substr(realpath(__DIR__), strlen(realpath($_SERVER['DOCUMENT_ROOT'])))), '/');
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$siteUrl  = $protocol . '://' . $_SERVER['HTTP_HOST'] . $sitePath;

$username = $_GET['u'] ?? '';
if (empty($username)) redirect('/');

// Dynamic Audio Playlist Scanner
$audioDir = __DIR__ . '/assets/audios';
$playlist = [];
if (is_dir($audioDir)) {
    $files = scandir($audioDir);
    foreach ($files as $f) {
        if (strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'mp3') {
            $playlist[] = $sitePath . '/assets/audios/' . $f;
        }
    }
}
sort($playlist); // Sorts 1.mp3, 2.mp3, etc.
$firstTrack = !empty($playlist) ? $playlist[0] : '';


// OPTIMIZED: Fetch user and stats in a single complex join/subquery if possible, 
// or at least consolidate the core stats.
$profileUser = getUserByUsername($pdo, $username);
if (!$profileUser) { header("HTTP/1.0 404 Not Found"); exit('User Not Found'); }

// Check if profile is suspended
if (isset($profileUser['is_suspended']) && $profileUser['is_suspended'] == 1) {
    echo '<style>body{background:#0b0f1e;color:#f1f5f9;display:flex;align-items:center;justify-content:center;height:100vh;font-family:sans-serif;} .box{text-align:center;padding:40px;background:#111627;border-radius:20px;border:1px solid rgba(255,255,255,0.07);}</style>';
    echo '<div class="box"><h1>🚫 অ্যাকাউন্ট স্থগিত</h1><p>অতিরিক্ত বালপাকনামী করার জন্য এই ব্যবহার কারীর হোগায় লাত্থি মারা হয়েছে।</p><a href="/" style="color:#5b6af0;text-decoration:none;">হোমপেজে ফিরে যান</a></div>';
    exit;
}

// ── IP Block Check ──
$visitorIp = $_SERVER['HTTP_CF_CONNECTING_IP']
          ?? (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]) : null)
          ?? $_SERVER['REMOTE_ADDR']
          ?? 'unknown';

$ipBlock = $pdo->prepare("SELECT 1 FROM blocked_ips WHERE ip_address = ?");
$ipBlock->execute([$visitorIp]);
if ($ipBlock->fetchColumn()) {
    http_response_code(403);
    echo '<style>body{background:#0b0f1e;color:#f1f5f9;display:flex;align-items:center;justify-content:center;height:100vh;font-family:sans-serif;} .box{text-align:center;padding:40px;background:#111627;border-radius:20px;border:1px solid rgba(255,255,255,0.07);}</style>';
    echo '<div class="box"><h1>🚫 অ্যাক্সেস ব্লক</h1><p>অতিরিক্ত বালপাকনামী করার জন্য আপনার হোগায় লাত্থি মারা হয়েছে।</p></div>';
    exit;
}

// Consolidate stats
$stats = getUserStats($pdo, $profileUser['id']);
$totalCollected = $stats['verified_amount'];
$salamiCount    = $stats['verified_count'];
$mfsAccounts    = getUserMfsAccounts($pdo, $profileUser['id']);
$primaryMfs     = getPrimaryMfs($pdo, $profileUser['id'], $mfsAccounts);
$accentColor    = $primaryMfs ? getMfsColor($primaryMfs['mfs_type']) : '#5b6af0';

// Increment Views (Keep as separate for clarity, but could be async or delayed)
if (session_status() === PHP_SESSION_NONE) session_start();
$viewKey = 'viewed_profile_' . $profileUser['id'];
if (!isset($_SESSION[$viewKey])) {
    $pdo->prepare("UPDATE users SET views = views + 1 WHERE id = ?")->execute([$profileUser['id']]);
    $_SESSION[$viewKey] = true;
    $profileUser['views']++;
}

$page      = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$perPage   = 10;
$offset    = ($page - 1) * $perPage;
$totalLogs = $salamiCount;
$totalPages = ceil($totalLogs / $perPage);

$stmtLog = $pdo->prepare("
    SELECT sl.*, ma.mfs_type, ma.label as mfs_label 
    FROM salami_logs sl 
    LEFT JOIN mfs_accounts ma ON sl.mfs_account_id = ma.id 
    WHERE sl.receiver_id = ? AND sl.status = 'verified'
    AND NOT EXISTS (SELECT 1 FROM blocked_ips bi WHERE bi.ip_address = sl.ip_address)
    ORDER BY sl.created_at DESC 
    LIMIT ? OFFSET ?
");
$stmtLog->execute([$profileUser['id'], $perPage, $offset]); // Passing array is cleaner
$paginatedLogs = $stmtLog->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $sender_name    = trim($_POST['sender_name'] ?? '');
    $amount         = floatval($_POST['amount'] ?? 0);
    $message        = mb_substr(trim($_POST['message'] ?? ''), 0, 500);
    $trx_id         = mb_substr(trim($_POST['trx_id'] ?? ''), 0, 100);
    $mfs_account_id = intval($_POST['mfs_account_id'] ?? 0);

    // Validate inputs
    if (empty($sender_name) || mb_strlen($sender_name) > 100) {
        echo json_encode(['success' => false, 'message' => 'সঠিক নাম দিন।']);
        exit;
    }
    if ($amount <= 0 || $amount > 1000000) {
        echo json_encode(['success' => false, 'message' => 'সঠিক পরিমাণ দিন।']);
        exit;
    }

    // Anti-Bullying / Profanity Check
    if (isProfanity($sender_name) || isProfanity($message)) {
        echo json_encode(['success' => false, 'message' => 'দুঃখিত, আপত্তিকর ভাষা ব্যবহার করা যাবে না। মার্জিত ভাষা ব্যবহার করুন।']);
        exit;
    }

    // ── Spam Protection: Rate Limit by IP ──
    $userIp = $_SERVER['HTTP_CF_CONNECTING_IP']
           ?? (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]) : null)
           ?? $_SERVER['REMOTE_ADDR']
           ?? 'unknown';

    // Block check on submission too (double enforcement)
    $ipBlockPost = $pdo->prepare("SELECT 1 FROM blocked_ips WHERE ip_address = ?");
    $ipBlockPost->execute([$userIp]);
    if ($ipBlockPost->fetchColumn()) {
        echo json_encode(['success' => false, 'message' => 'আপনার অ্যাক্সেস ব্লক করা হয়েছে।']);
        exit;
    }
    // Limit: Max 3 submissions per hour per IP for this specific receiver
    $checkRate = $pdo->prepare("SELECT COUNT(*) FROM salami_logs WHERE receiver_id = ? AND ip_address = ? AND created_at > (NOW() - INTERVAL 1 HOUR)");
    $checkRate->execute([$profileUser['id'], $userIp]);
    if ($checkRate->fetchColumn() >= 3) {
        echo json_encode(['success' => false, 'message' => 'আপনি এই ইউজারের জন্য লিমিট অতিক্রম করেছেন। ১ ঘণ্টা পর আবার চেষ্টা করুন।']);
        exit;
    }

    // Verify MFS account belongs to this profile user (prevent abuse)
    if ($mfs_account_id > 0) {
        $mfsCheck = $pdo->prepare("SELECT id FROM mfs_accounts WHERE id = ? AND user_id = ?");
        $mfsCheck->execute([$mfs_account_id, $profileUser['id']]);
        if (!$mfsCheck->fetch()) {
            echo json_encode(['success' => false, 'message' => 'অবৈধ MFS অ্যাকাউন্ট।']);
            exit;
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO salami_logs (receiver_id, mfs_account_id, sender_name, amount, message, trx_id, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$profileUser['id'], $mfs_account_id ?: null, $sender_name, $amount, $message ?: null, $trx_id ?: null, $userIp]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'সার্ভার সমস্যা হয়েছে।']);
    }
    exit;
}

$initials = mb_substr($profileUser['full_name'], 0, 1);

$desc = $profileUser['description'];
if (empty($desc)) {
    $funnies = [
        "সালামি দেওয়া সুন্নত, না দেওয়া কবিরা গুনাহ! 😂",
        "সালামি ছাড়া ঈদ মানে চিনি ছাড়া সেমাই! জলদি পাঠান। 🥣",
        "আমার পকেট এখন একদম ফাঁকা, আপনার সালামিই পারে এই হাহাকার দূর করতে! 💸",
        "বড়রা সালামি দেয়, ছোটরা শুধু হাত পাতে। আমি প্রফেশনাল হাত পাতুড়ে! ✋"
    ];
    $desc = $funnies[array_rand($funnies)];
}
?>
<!DOCTYPE html>
<html lang="bn" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= sanitize($profileUser['full_name']) ?> | <?= SITE_NAME ?></title>
    <meta name="description" content="<?= sanitize($profileUser['full_name']) ?>-এর সালামি পেজ। এখানে আপনি সালামি পাঠাতে পারেন খুব সহজেই।">
    <script data-host="https://analytics.hs.vc" data-dnt="false" src="https://analytics.hs.vc/js/script.js" id="ZwSg9rf6GA" async defer></script>
    <!-- Dynamic Social Meta for Profiles -->
    <meta property="og:title" content="<?= sanitize($profileUser['full_name']) ?>-কে সালামি পাঠান | <?= SITE_NAME ?>">
    <meta property="og:description" content="<?= sanitize($profileUser['description'] ?: 'আমার সালামি পেজে আপনাকে স্বাগতম!') ?>">
    <meta property="og:image" content="<?= (!empty($profileUser['profile_image']) && file_exists(__DIR__ . '/uploads/profiles/' . $profileUser['profile_image'])) ? $sitePath . '/uploads/profiles/' . $profileUser['profile_image'] : $sitePath . '/assets/img/default-avatar.png' ?>">
    <meta property="og:type" content="profile">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="icon" type="image/png" href="<?= SITE_FAVICON ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=Noto+Sans+Bengali:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:          #0b0f1e;
            --surface:     #111627;
            --surface-2:   #161d30;
            --border:      rgba(255,255,255,0.07);
            --border-glow: rgba(91,106,240,0.25);

            --accent:      <?= $accentColor ?>;
            --accent-bg:   <?= $accentColor ?>18;
            --accent-glow: <?= $accentColor ?>40;

            --p:           #5b6af0;
            --p-light:     #818cf8;
            --p-glow:      rgba(91,106,240,0.35);

            --green:       #22c55e;
            --green-bg:    rgba(34,197,94,0.12);
            --amber:       #f59e0b;
            --red:         #ef4444;

            --t1: #f1f5f9;
            --t2: #94a3b8;
            --t3: #475569;

            --r-sm: 10px;
            --r-md: 16px;
            --r-lg: 22px;
            --r-xl: 28px;

            --font-en: 'Sora', sans-serif;
            --font-bn: 'Noto Sans Bengali', 'Sora', sans-serif;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: var(--font-bn);
            background: var(--bg);
            color: var(--t1);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; }

        /* ── TOPBAR ── */
        .sp-topbar {
            height: 60px;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 24px; max-width: 860px; margin: 0 auto;
        }
        .sp-logo {
            display: flex; align-items: center; gap: 9px; text-decoration: none;
        }
        .sp-logo-icon {
            width: 34px; height: 34px; border-radius: 9px;
            background: var(--p); display: flex; align-items: center; justify-content: center;
            font-size: .85rem; color: #fff; box-shadow: 0 4px 12px var(--p-glow);
        }
        .sp-logo-text {
            font-family: var(--font-en); font-weight: 800; font-size: 1rem;
            letter-spacing: -.5px; color: var(--t1);
        }
        .sp-logo-text span { color: var(--p-light); }
        .sp-topbar-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 16px; border-radius: var(--r-sm);
            background: none; border: 1px solid var(--border);
            color: var(--t2); font-size: .8rem; font-weight: 600;
            text-decoration: none; transition: all .2s; font-family: var(--font-bn);
        }
        .sp-topbar-btn:hover { border-color: var(--border-glow); color: var(--t1); }

        /* ── LAYOUT ── */
        .page {
            max-width: 860px; margin: 0 auto;
            padding: 0 16px 60px;
        }

        /* ── PROFILE HERO ── */
        .profile-hero {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--r-xl);
            overflow: hidden;
            margin-bottom: 16px;
            position: relative;
        }

        /* accent top bar */
        .profile-hero-accent {
            height: 4px;
            background: linear-gradient(90deg, var(--accent), rgba(91,106,240,0.4), transparent);
        }

        /* glow behind avatar */
        .profile-hero-glow {
            position: absolute; top: 4px; left: -60px;
            width: 280px; height: 280px; border-radius: 50%;
            background: radial-gradient(circle, var(--accent-glow) 0%, transparent 70%);
            pointer-events: none;
        }

        .profile-hero-body {
            padding: 28px 28px 24px;
            display: flex; gap: 24px; align-items: flex-start;
            position: relative; z-index: 1;
        }

        .profile-avatar-wrap { position: relative; flex-shrink: 0; }
        .profile-avatar {
            width: 90px; height: 90px; border-radius: 50%;
            border: 3px solid var(--accent);
            object-fit: cover;
            box-shadow: 0 0 24px var(--accent-glow);
        }
        .profile-avatar-default {
            width: 90px; height: 90px; border-radius: 50%;
            background: linear-gradient(135deg, var(--accent) 0%, #8b5cf6 100%);
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; font-weight: 800; color: #fff;
            border: 3px solid var(--accent);
            box-shadow: 0 0 24px var(--accent-glow);
        }
        .active-dot {
            position: absolute; bottom: 4px; right: 4px;
            width: 16px; height: 16px; border-radius: 50%;
            background: var(--green); border: 3px solid var(--surface);
            box-shadow: 0 0 8px var(--green);
            animation: dotPulse 2s infinite;
        }
        @keyframes dotPulse { 0%,100%{box-shadow:0 0 8px var(--green);} 50%{box-shadow:0 0 16px var(--green);} }

        .profile-info { flex: 1; min-width: 0; }

        .profile-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; }
        .profile-tag {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 3px 10px; border-radius: 20px;
            font-size: .68rem; font-weight: 700;
            background: rgba(255,255,255,0.05); border: 1px solid var(--border);
            color: var(--t2);
        }
        .profile-tag i { font-size: .65rem; }

        .profile-name {
            font-family: var(--font-en);
            font-size: clamp(1.3rem, 4vw, 1.8rem);
            font-weight: 800; letter-spacing: -.5px;
            color: var(--t1); margin-bottom: 6px; line-height: 1.2;
        }
        .profile-name .bn-name {
            font-family: var(--font-bn);
            background: linear-gradient(90deg, var(--t1) 0%, var(--accent) 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .profile-handle { font-size: .8rem; color: var(--t3); margin-bottom: 10px; }

        .profile-desc { font-size: .875rem; color: var(--t2); line-height: 1.7; }

        /* ── STATS ROW ── */
        .stats-row {
            display: grid; grid-template-columns: repeat(3, 1fr);
            gap: 12px; margin-bottom: 16px;
        }
        .stat-box {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: var(--r-lg); padding: 18px 16px; text-align: center;
            transition: border-color .2s, transform .2s;
        }
        .stat-box:hover { border-color: var(--border-glow); transform: translateY(-2px); }
        .stat-val {
            font-family: var(--font-en); font-size: 1.5rem; font-weight: 800;
            letter-spacing: -1px; color: var(--t1); display: block;
        }
        .stat-val.bn { font-family: var(--font-bn); font-size: 1.4rem; }
        .stat-lbl { font-size: .72rem; color: var(--t3); font-weight: 600; margin-top: 4px; display: block; }

        /* ── SALAMI WIZARD ── */
        .wizard-wrap {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: var(--r-xl); overflow: hidden; margin-bottom: 16px;
        }
        .wizard-head {
            padding: 20px 24px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .wizard-title {
            font-size: .95rem; font-weight: 700; color: var(--t1);
            display: flex; align-items: center; gap: 8px;
        }
        .wizard-title i { color: var(--accent); }
        .wizard-body { padding: 24px; }

        /* Steps indicator */
        .step-indicator {
            display: flex; align-items: center; justify-content: center;
            gap: 0; margin-bottom: 28px;
        }
        .step-pip {
            width: 32px; height: 32px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: .78rem; font-weight: 800; font-family: var(--font-en);
            background: var(--surface-2); border: 2px solid var(--border);
            color: var(--t3); transition: all .3s; flex-shrink: 0;
            position: relative; z-index: 1;
        }
        .step-pip.active { background: var(--accent); border-color: var(--accent); color: #fff; box-shadow: 0 0 14px var(--accent-glow); }
        .step-pip.done   { background: var(--green-bg); border-color: var(--green); color: var(--green); }
        .step-line {
            flex: 1; height: 2px; background: var(--border); max-width: 60px;
            transition: background .3s;
        }
        .step-line.done { background: var(--green); }

        /* Panels */
        .wizard-panel { display: none; animation: panelIn .3s ease both; }
        .wizard-panel.active { display: block; }
        @keyframes panelIn { from{opacity:0;transform:translateY(8px);} to{opacity:1;transform:translateY(0);} }

        .panel-label {
            font-size: .82rem; font-weight: 700; color: var(--t2);
            margin-bottom: 14px; display: flex; align-items: center; gap: 7px;
        }
        .panel-label i { color: var(--accent); }

        /* MFS Options */
        .mfs-opts { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .mfs-opt {
            background: var(--surface-2); border: 1.5px solid var(--border);
            border-radius: var(--r-md); padding: 14px 12px;
            cursor: pointer; transition: all .2s;
            display: flex; align-items: center; gap: 12px;
        }
        .mfs-opt:hover { border-color: rgba(255,255,255,0.15); }
        .mfs-opt.selected {
            border-color: var(--accent);
            background: var(--accent-bg);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }
        .mfs-opt-icon {
            width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-size: 1rem;
        }
        .mfs-opt-name { font-size: .85rem; font-weight: 700; color: var(--t1); }
        .mfs-opt-num  { font-size: .72rem; color: var(--t3); font-family: monospace; margin-top: 2px; }

        /* Form inputs */
        .field { margin-bottom: 14px; }
        .field-label { font-size: .78rem; font-weight: 700; color: var(--t2); margin-bottom: 6px; display: block; }
        .field-input {
            width: 100%; background: var(--surface-2); border: 1.5px solid var(--border);
            border-radius: var(--r-sm); padding: 12px 14px;
            color: var(--t1); font-size: .88rem; font-family: var(--font-bn);
            outline: none; transition: border-color .2s; -webkit-appearance: none;
        }
        .field-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); }
        .field-input::placeholder { color: var(--t3); }
        textarea.field-input { resize: vertical; min-height: 80px; }

        /* Amount presets */
        .amount-presets { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
        .preset-btn {
            padding: 7px 14px; border-radius: var(--r-sm);
            background: var(--surface-2); border: 1px solid var(--border);
            color: var(--t2); font-size: .8rem; font-weight: 700;
            cursor: pointer; transition: all .2s; font-family: var(--font-en);
        }
        .preset-btn:hover { border-color: var(--accent); color: var(--t1); }
        .preset-btn.active { background: var(--accent-bg); border-color: var(--accent); color: var(--accent); }

        /* Warning note */
        .warn-note {
            background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2);
            border-radius: var(--r-sm); padding: 12px 14px;
            font-size: .78rem; color: #fca5a5; line-height: 1.6;
            display: flex; gap: 8px; margin-bottom: 16px;
        }
        .warn-note i { color: var(--red); flex-shrink: 0; margin-top: 2px; }

        /* Buttons */
        .btn-primary {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 24px; border-radius: var(--r-sm);
            background: var(--accent); color: #fff;
            font-size: .88rem; font-weight: 700; border: none;
            cursor: pointer; font-family: var(--font-bn);
            transition: all .2s;
            box-shadow: 0 4px 16px var(--accent-glow);
        }
        .btn-primary:hover:not(:disabled) { filter: brightness(1.1); transform: translateY(-1px); }
        .btn-primary:disabled { opacity: .45; cursor: not-allowed; }
        .btn-primary.full { width: 100%; justify-content: center; }

        .btn-back {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 12px 16px; border-radius: var(--r-sm);
            background: none; border: 1px solid var(--border);
            color: var(--t2); font-size: .85rem; font-weight: 600;
            cursor: pointer; font-family: var(--font-bn); transition: all .2s;
        }
        .btn-back:hover { border-color: var(--border-glow); color: var(--t1); }

        .panel-actions { display: flex; align-items: center; justify-content: space-between; margin-top: 20px; }
        .panel-actions.end { justify-content: flex-end; }

        /* Success state */
        .success-box {
            display: none; text-align: center; padding: 40px 20px;
        }
        .success-icon {
            width: 72px; height: 72px; border-radius: 50%;
            background: var(--green-bg); border: 2px solid var(--green);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; color: var(--green); margin: 0 auto 20px;
            animation: popIn .5s cubic-bezier(.22,.68,0,1.4) both;
        }
        @keyframes popIn { from{transform:scale(0);opacity:0;} to{transform:scale(1);opacity:1;} }
        .success-title { font-size: 1.2rem; font-weight: 800; color: var(--t1); margin-bottom: 8px; }
        .success-sub { font-size: .875rem; color: var(--t2); line-height: 1.6; }

        /* ── HOW IT WORKS ── */
        .hiw-wrap {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: var(--r-xl); padding: 24px;
            margin-bottom: 16px;
        }
        .hiw-title {
            font-size: .9rem; font-weight: 700; color: var(--t1);
            display: flex; align-items: center; gap: 8px; margin-bottom: 18px;
        }
        .hiw-title i { color: var(--p-light); }
        .hiw-steps { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .hiw-step { text-align: center; }
        .hiw-step-num {
            width: 44px; height: 44px; border-radius: 50%;
            background: var(--surface-2); border: 2px solid var(--border-glow);
            display: flex; align-items: center; justify-content: center;
            font-family: var(--font-en); font-size: .9rem; font-weight: 800;
            color: var(--p-light); margin: 0 auto 10px;
            transition: all .2s;
        }
        .hiw-step:hover .hiw-step-num { border-color: var(--accent); color: var(--accent); box-shadow: 0 0 14px var(--accent-glow); }
        .hiw-step-title { font-size: .78rem; font-weight: 700; color: var(--t1); margin-bottom: 4px; }
        .hiw-step-text { font-size: .72rem; color: var(--t3); line-height: 1.5; }

        /* ── LOGS TABLE ── */
        .logs-wrap {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: var(--r-xl); overflow: hidden;
        }
        .logs-head {
            padding: 18px 20px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .logs-title {
            font-size: .9rem; font-weight: 700; color: var(--t1);
            display: flex; align-items: center; gap: 8px;
        }
        .logs-title i { color: var(--p-light); }

        .data-table { width: 100%; border-collapse: collapse; }
        .data-table thead tr { border-bottom: 1px solid var(--border); }
        .data-table th {
            padding: 10px 16px; font-size: .68rem; font-weight: 700;
            color: var(--t3); text-transform: uppercase; letter-spacing: .8px; text-align: left;
        }
        .data-table td {
            padding: 14px 16px; font-size: .85rem;
            border-bottom: 1px solid var(--border); vertical-align: middle;
        }
        .data-table tbody tr:last-child td { border-bottom: none; }
        .data-table tbody tr { transition: background .15s; }
        .data-table tbody tr:hover { background: rgba(255,255,255,0.02); }

        .sender-cell { display: flex; align-items: center; gap: 10px; }
        .sender-avatar {
            width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
            background: linear-gradient(135deg, var(--p) 0%, #8b5cf6 100%);
            display: flex; align-items: center; justify-content: center;
            font-size: .72rem; font-weight: 700; color: #fff;
        }
        .sender-name  { font-weight: 700; font-size: .875rem; color: var(--t1); }
        .sender-time  { font-size: .7rem; color: var(--t3); margin-top: 1px; display: none; }

        .amount-blur {
            font-weight: 800; color: var(--green); font-family: var(--font-en);
            filter: blur(4px); user-select: none; opacity: .7;
            transition: filter .2s;
        }
        .amount-blur:hover { filter: none; opacity: 1; }

        /* Pagination */
        .pagination {
            display: flex; align-items: center; justify-content: center;
            gap: 6px; padding: 18px;
            border-top: 1px solid var(--border);
        }
        .pag-btn {
            width: 34px; height: 34px; border-radius: var(--r-sm);
            display: flex; align-items: center; justify-content: center;
            font-size: .8rem; font-weight: 700; text-decoration: none;
            background: var(--surface-2); border: 1px solid var(--border);
            color: var(--t2); transition: all .2s;
        }
        .pag-btn:hover { border-color: var(--border-glow); color: var(--t1); }
        .pag-btn.active { background: var(--p); border-color: var(--p); color: #fff; }
        .pag-btn.arrow { color: var(--t3); }

        /* ── TOAST ── */
        .toast-wrap {
            position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%);
            z-index: 9999; pointer-events: none;
        }
        .sp-toast {
            display: none; align-items: center; gap: 8px;
            padding: 12px 20px; border-radius: 30px;
            background: var(--green); color: #fff;
            font-size: .85rem; font-weight: 700; font-family: var(--font-bn);
            box-shadow: 0 8px 24px rgba(34,197,94,0.35);
            animation: toastIn .3s cubic-bezier(.22,.68,0,1.2) both;
        }
        .sp-toast.show { display: flex; }
        .sp-toast.error { background: var(--red); box-shadow: 0 8px 24px rgba(239,68,68,0.35); }
        @keyframes toastIn { from{transform:translateY(16px);opacity:0;} to{transform:translateY(0);opacity:1;} }

        /* Input highlight */
        .bad-input { border-color: var(--red) !important; box-shadow: 0 0 0 4px rgba(239,68,68,0.15) !important; animation: shake .4s ease-in-out; }
        @keyframes shake { 0%,100%{transform:translateX(0);} 25%{transform:translateX(-5px);} 75%{transform:translateX(5px);} }

        /* ── RESPONSIVE ── */
        @media (max-width: 600px) {
            .profile-hero-body { flex-direction: column; align-items: center; text-align: center; padding: 20px 18px 18px; }
            .profile-tags { justify-content: center; }
            .stats-row { gap: 8px; }
            .stat-val { font-size: 1.2rem; }
            .stat-box { padding: 14px 10px; }
            .mfs-opts { grid-template-columns: 1fr; }
            .hiw-steps { grid-template-columns: repeat(2, 1fr); gap: 16px; }
            .wizard-body { padding: 16px; }
            .data-table .hide-xs { display: none; }
            .show-xs { display: block !important; }
            .amount-presets { gap: 6px; }
            .preset-btn { padding: 6px 10px; font-size: .75rem; }
        }
        @media (max-width: 400px) {
            .sp-topbar { padding: 0 14px; }
            .profile-avatar, .profile-avatar-default { width: 76px; height: 76px; }
            .profile-name { font-size: 1.25rem; }
        }
    </style>
</head>
<body>

<!-- TOPBAR -->
<nav style="border-bottom: 1px solid var(--border); background: rgba(11,15,30,0.8); backdrop-filter: blur(16px); position: sticky; top: 0; z-index: 900;">
    <div class="sp-topbar">
        <a class="sp-logo" href="<?= BASE_URL ?>/">
            <div class="sp-logo-icon"><i class="fa-solid fa-hand-holding-heart"></i></div>
            <span class="sp-logo-text"><?= SITE_NAME ?></span>
        </a>
        <?php if (isLoggedIn()): ?>
            <a href="<?= BASE_URL ?>/dashboard" class="sp-topbar-btn"><i class="fa-solid fa-chart-pie"></i> ড্যাশবোর্ড</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/register" class="sp-topbar-btn" style="background:var(--p); border-color:var(--p); color:#fff;"><i class="fa-solid fa-user-plus"></i> রেজিস্ট্রেশন</a>
        <?php endif; ?>
    </div>
</nav>

<div class="page">

    <!-- ── PROFILE HERO ── -->
    <div class="profile-hero" style="margin-top:16px;">
        <div class="profile-hero-accent"></div>
        <div class="profile-hero-glow"></div>
        <div class="profile-hero-body">
            <div class="profile-avatar-wrap">
                <?php 
                $profilePic = (!empty($profileUser['profile_image']) && file_exists(__DIR__ . '/uploads/profiles/' . $profileUser['profile_image'])) 
                    ? $sitePath . '/uploads/profiles/' . $profileUser['profile_image'] 
                    : $sitePath . '/assets/img/default-avatar.png';
                ?>
                <img src="<?= $profilePic ?>" class="profile-avatar">
                <div class="active-dot" title="সক্রিয়"></div>
            </div>
            <div class="profile-info">
                <div class="profile-tags">
                    <span class="profile-tag"><i class="fa-solid fa-circle-check" style="color:var(--p-light);"></i> ভেরিফাইড</span>
                    <span class="profile-tag"><i class="fa-solid fa-bolt" style="color:var(--amber);"></i> ফাস্ট রেসপন্ডার</span>
                </div>
                <div class="profile-name"><span class="bn-name"><?= sanitize($profileUser['full_name']) ?></span></div>
                <div class="profile-handle">@<?= sanitize($profileUser['username']) ?></div>
                <p class="profile-desc"><?= nl2br(sanitize($desc)) ?></p>
            </div>
        </div>
    </div>

    <!-- ── STATS ── -->
    <div class="stats-row">
        <div class="stat-box">
            <span class="stat-val bn"><?= bn_number($salamiCount) ?></span>
            <span class="stat-lbl">সালামি</span>
        </div>
        <div class="stat-box">
            <span class="stat-val bn"><?= bn_number(count($mfsAccounts)) ?></span>
            <span class="stat-lbl">MFS মেথড</span>
        </div>
        <div class="stat-box">
            <span class="stat-val bn"><?= bn_number($profileUser['views']) ?></span>
            <span class="stat-lbl">ভিউস</span>
        </div>
    </div>

    <!-- ── SALAMI WIZARD ── -->
    <div class="wizard-wrap">
        <div class="wizard-head">
            <div class="wizard-title">
                <i class="fa-solid fa-hand-holding-dollar"></i>
                সালামি প্রদান করুন
            </div>
            <span style="font-size:.72rem; color:var(--t3);">৩টি সহজ ধাপ</span>
        </div>
        <div class="wizard-body">

            <!-- Step indicator -->
            <div class="step-indicator">
                <div class="step-pip active" id="s1">১</div>
                <div class="step-line" id="l1"></div>
                <div class="step-pip" id="s2">২</div>
                <div class="step-line" id="l2"></div>
                <div class="step-pip" id="s3">৩</div>
            </div>

            <form id="salamiForm" action="<?= $_SERVER['REQUEST_URI'] ?>" method="POST">
                <input type="hidden" name="mfs_account_id" id="mfsInput">
                <input type="hidden" name="amount" id="amtInput">

                <!-- PANEL 1: Select MFS -->
                <div class="wizard-panel active" id="panel1">
                    <div class="panel-label"><i class="fa-solid fa-wallet"></i> পেমেন্ট মেথড বেছে নিন</div>
                    <?php if (empty($mfsAccounts)): ?>
                        <div style="text-align:center; padding:32px; color:var(--t3); font-size:.875rem;">
                            <i class="fa-solid fa-triangle-exclamation" style="font-size:2rem; margin-bottom:12px; display:block; color:var(--amber);"></i>
                            এই ব্যবহারকারী এখনো কোনো পেমেন্ট মেথড যোগ করেননি।
                        </div>
                    <?php else: ?>
                        <div class="mfs-opts">
                            <?php foreach ($mfsAccounts as $mfs): ?>
                            <div class="mfs-opt" data-id="<?= $mfs['id'] ?>" data-number="<?= sanitize($mfs['mfs_number']) ?>">
                                <div class="mfs-opt-icon" style="background:<?= getMfsColor($mfs['mfs_type']) ?>18; color:<?= getMfsColor($mfs['mfs_type']) ?>;">
                                    <i class="<?= getMfsIcon($mfs['mfs_type']) ?>"></i>
                                </div>
                                <div>
                                    <div class="mfs-opt-name"><?= getMfsName($mfs['mfs_type'], $mfs['label']) ?></div>
                                    <div class="mfs-opt-num"><?= sanitize($mfs['mfs_number']) ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="panel-actions end">
                            <button type="button" class="btn-primary" id="go2" disabled>
                                পরবর্তী <i class="fa-solid fa-arrow-right"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- PANEL 2: Amount & Message -->
                <div class="wizard-panel" id="panel2">
                    <div class="panel-label"><i class="fa-solid fa-bangladeshi-taka-sign"></i> পরিমাণ ও বার্তা</div>
                    <div class="amount-presets">
                        <?php foreach ([100, 200, 500, 1000] as $preset): ?>
                        <button type="button" class="preset-btn" data-amount="<?= $preset ?>">৳<?= $preset ?></button>
                        <?php endforeach; ?>
                    </div>
                    <div class="field">
                        <label class="field-label">নিজে লিখুন</label>
                        <input type="number" class="field-input" id="customAmt" placeholder="সালামি পরিমাণ (৳)" min="1">
                    </div>
                    <div class="field">
                        <label class="field-label">বার্তা <span style="color:var(--t3); font-weight:400;">(ঐচ্ছিক)</span></label>
                        <textarea class="field-input" name="message" placeholder="কিছু মেসেজ লিখুন…" rows="2"></textarea>
                    </div>
                    <div class="panel-actions">
                        <button type="button" class="btn-back" onclick="goStep(1)"><i class="fa-solid fa-arrow-left"></i> পিছনে</button>
                        <button type="button" class="btn-primary" id="go3" disabled>পরবর্তী <i class="fa-solid fa-arrow-right"></i></button>
                    </div>
                </div>

                <!-- PANEL 3: Confirm -->
                <div class="wizard-panel" id="panel3">
                    <div class="panel-label"><i class="fa-solid fa-user"></i> আপনার তথ্য দিন</div>
                    <div class="field">
                        <label class="field-label">আপনার নাম *</label>
                        <input type="text" class="field-input" name="sender_name" id="sName" placeholder="আপনার সুন্দর নাম" required>
                    </div>
                    <div class="field">
                        <label class="field-label">Trx ID <span style="color:var(--t3); font-weight:400;">(ঐচ্ছিক)</span></label>
                        <input type="text" class="field-input" name="trx_id" placeholder="পেমেন্টের Transaction ID">
                    </div>
                    <div class="warn-note">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span> টাকা না দিয়ে কনফার্ম করলে আপনি শয়তানের প্রতিরূপ, আপনি মুনাফিক জাহান্নামী, এটা তার লক্ষণ!</span>
                    </div>
                    <div class="panel-actions">
                        <button type="button" class="btn-back" onclick="goStep(2)"><i class="fa-solid fa-arrow-left"></i> পিছনে</button>
                        <button type="submit" class="btn-primary" id="finalBtn">
                            <i class="fa-solid fa-paper-plane"></i> সালামি নিশ্চিত করুন 🎉
                        </button>
                    </div>
                </div>
            </form>

            <!-- SUCCESS -->
            <div class="success-box" id="successBox">
                <div class="success-icon"><i class="fa-solid fa-check"></i></div>
                <div class="success-title">সালামি সম্পন্ন হয়েছে! 🎉</div>
                <p class="success-sub" style="margin:0 0 20px;">আপনার ভালোবাসা গ্রহণ করা হলো। অনেক ধন্যবাদ!</p>
                <button class="btn-primary" onclick="location.reload()">
                    <i class="fa-solid fa-rotate-right"></i> আরেকটি পাঠান
                </button>
            </div>

        </div>
    </div>

    <!-- ── HOW IT WORKS ── -->
    <div class="hiw-wrap">
        <div class="hiw-title"><i class="fa-solid fa-circle-info"></i> এটি যেভাবে কাজ করে</div>
        <div class="hiw-steps">
            <div class="hiw-step">
                <div class="hiw-step-num">১</div>
                <div class="hiw-step-title">মেথড বেছে নিন</div>
                <p class="hiw-step-text">পছন্দের পেমেন্ট মেথড সিলেক্ট করুন</p>
            </div>
            <div class="hiw-step">
                <div class="hiw-step-num">২</div>
                <div class="hiw-step-title">টাকা পাঠান</div>
                <p class="hiw-step-text">নির্দিষ্ট নম্বরে সেন্ড মানি করুন</p>
            </div>
            <div class="hiw-step">
                <div class="hiw-step-num">৩</div>
                <div class="hiw-step-title">তথ্য দিন</div>
                <p class="hiw-step-text">নাম ও পরিমাণ দিয়ে কনফার্ম করুন</p>
            </div>
            <div class="hiw-step">
                <div class="hiw-step-num">৪</div>
                <div class="hiw-step-title">ঈদ এনজয়!</div>
                <p class="hiw-step-text">সালামি পৌঁছে যাবে! ঈদ মোবারক 🌙</p>
            </div>
        </div>
    </div>

    <!-- ── RECENT SALAMI LOGS ── -->
    <div class="logs-wrap">
        <div class="logs-head">
            <div class="logs-title"><i class="fa-solid fa-clock-rotate-left"></i> সাম্প্রতিক প্রাপ্তি</div>
            <span style="font-size:.75rem; color:var(--t3);">মোট <?= bn_number($salamiCount) ?>টি</span>
        </div>
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>প্রেরক</th>
                        <th>গেটওয়ে</th>
                        <th>পরিমাণ</th>
                        <th class="hide-xs">সময়</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($paginatedLogs)): ?>
                        <tr>
                            <td colspan="4">
                                <div style="text-align:center; padding:40px; color:var(--t3);">
                                    <i class="fa-solid fa-inbox" style="font-size:2rem; margin-bottom:10px; display:block;"></i>
                                    এখনো কোনো সালামি আসেনি।
                                </div>
                            </td>
                        </tr>
                    <?php else: foreach ($paginatedLogs as $log): ?>
                        <tr>
                            <td>
                                <div class="sender-cell">
                                    <div class="sender-avatar"><?= mb_substr($log['sender_name'], 0, 1) ?></div>
                                    <div>
                                        <div class="sender-name"><?= sanitize($log['sender_name']) ?></div>
                                        <div class="sender-time show-xs"><?= timeAgo($log['created_at']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span style="font-size:.82rem; font-weight:600; color:<?= getMfsColor($log['mfs_type']) ?>;">
                                    <i class="<?= getMfsIcon($log['mfs_type']) ?>" style="margin-right:5px;"></i><?= getMfsName($log['mfs_type'], $log['mfs_label']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="amount-blur" title="হোভার করুন দেখতে">৳<?= number_format($log['amount']) ?></span>
                            </td>
                            <td class="hide-xs" style="font-size:.75rem; color:var(--t3);"><?= timeAgo($log['created_at']) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?u=<?= $username ?>&p=<?= $page-1 ?>" class="pag-btn arrow"><i class="fa-solid fa-chevron-left"></i></a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?u=<?= $username ?>&p=<?= $i ?>" class="pag-btn <?= $page===$i?'active':'' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="?u=<?= $username ?>&p=<?= $page+1 ?>" class="pag-btn arrow"><i class="fa-solid fa-chevron-right"></i></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer credit -->
    <div style="text-align:center; padding:28px 0 8px; font-size:.75rem; color:var(--t3);">
        <a href="<?= BASE_URL ?>/" style="color:var(--t3); text-decoration:none;">
            <i class="fa-solid fa-hand-holding-heart" style="color:var(--p-light); margin-right:5px;"></i>
             <?= SITE_NAME ?> দিয়ে তৈরি — বিনামূল্যে আপনার পেজ বানান
        </a>
    </div>

</div><!-- /page -->

<!-- TOAST -->
<div class="toast-wrap">
    <div class="sp-toast" id="spToast">
        <i class="fa-solid fa-copy" id="toastIcon"></i>
        <span id="toastMsg">নম্বর কপি হয়েছে!</span>
    </div>
</div>

<script>
(function(){
    let selectedMfsId = null;
    const toast = document.getElementById('spToast');

    // ── Step navigation ──
    window.goStep = function(n) {
        document.querySelectorAll('.wizard-panel').forEach(p => p.classList.remove('active'));
        const target = document.getElementById('panel' + n);
        if (target) target.classList.add('active');

        // Update pips
        [1,2,3].forEach(i => {
            const pip = document.getElementById('s' + i);
            pip.classList.remove('active', 'done');
            if (i === n) pip.classList.add('active');
            else if (i < n) pip.classList.add('done');
        });

        // Update lines
        [1,2].forEach(i => {
            const line = document.getElementById('l' + i);
            if (line) line.classList.toggle('done', i < n);
        });
    };

    // ── Toast Helper ──
    const showNotice = (msg, type = 'success') => {
        const toast = document.getElementById('spToast');
        const icon  = document.getElementById('toastIcon');
        const m     = document.getElementById('toastMsg');
        
        m.innerText = msg;
        toast.className = 'sp-toast show ' + type;
        icon.className = type === 'error' ? 'fa-solid fa-triangle-exclamation' : 'fa-solid fa-circle-check';
        
        setTimeout(() => toast.classList.remove('show'), 3500);
    };

    // ── Interaction Logic ──
    // go2: blocked if sender name or message has bad words
    document.getElementById('go2')?.addEventListener('click', () => {
        const nameVal = document.getElementById('sName')?.value || '';
        const msgVal  = document.querySelector('textarea[name="message"]')?.value || '';
        if (hasBadWord(nameVal) || hasBadWord(msgVal)) {
            showNotice('আপত্তিকর ভাষা ব্যবহার করা যাবে না।', 'error');
            return;
        }
        goStep(2);
    });

    // go3: amount step only, no text to check
    document.getElementById('go3')?.addEventListener('click', () => goStep(3));

    document.querySelectorAll('.preset-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const amt = parseFloat(this.dataset.amount);
            document.getElementById('customAmt').value = amt;
            document.getElementById('amtInput').value = amt;
            document.getElementById('go3').disabled = false;
        });
    });

    document.getElementById('customAmt')?.addEventListener('input', function() {
        const amt = parseFloat(this.value) || 0;
        document.getElementById('amtInput').value = amt;
        document.getElementById('go3').disabled = amt <= 0;
        document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
    });

    // ── Modified selection logic to use showNotice ──
    document.querySelectorAll('.mfs-opt').forEach(opt => {
        opt.addEventListener('click', function() {
            document.querySelectorAll('.mfs-opt').forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
            selectedMfsId = this.dataset.id;
            document.getElementById('mfsInput').value = selectedMfsId;
            document.getElementById('go2').disabled = false;

            const num = this.dataset.number;
            navigator.clipboard.writeText(num).then(() => showNotice('নম্বর কপি হয়েছে!'));
        });
    });

    // ── Profanity Filter (Strong ~95% + emoji bypass fix) ──
    const badWords = <?= json_encode([
        'fuck','fucking','fucker','fucked','fucks','motherfucker','motherfucking',
        'shit','shitty','bullshit','shitting',
        'piss','pissing','pissed',
        'bastard','bastards',
        'bitch','bitches','bitchy',"sex","cudi","chudi","choda","chuda",        'asshole','arsehole','ass',
        'dick','dicks','dickhead',
        'cunt','cunts',
        'pussy','pussies',
        'cock','cocks',
        'whore','whores',
        'slut','sluts','abal',
        'faggot','fag',
        'চুদি','চুদে','চুদির','চোদ','চোদার','চোদাচুদি',
        'মাদারচোদ','মাদারচুদ','মাদারচোদা',
        'খানকি','খানকির',
        'বেশ্যা','বেশ্যার',
        'শালা','শালার',
        'কুত্তা','কুত্তার',
        'শুয়োর','শুয়োরের',
        'হারামি','হারামজাদা','হারামজাদার',
        'পোদ','পোদের',
        'বাল','বালের','আবাল',
        'মাগী','মাগির',
    ]) ?>;

    const leetMap = {'@':'a','$':'s','0':'o','1':'i','3':'e','4':'a','5':'s','7':'t','8':'b','!':'i','|':'i','+':'t'};

    const normalizeJs = (str) => {
        let s = str.toLowerCase();
        s = s.replace(/[\u200B-\u200D\uFEFF]/g, '');
        s = s.replace(/[\u{1F000}-\u{1FFFF}\u{2600}-\u{27BF}\u{1F300}-\u{1F9FF}\u{FE00}-\u{FE0F}]/gu, '');
        s = s.replace(/[@$01345781!|+]/g, c => leetMap[c] || c);
        s = s.replace(/[\s\-_.,:;\\/]+/g, '');
        s = s.replace(/[^a-z\u0980-\u09FF]/g, '');
        return s;
    };

    const collapseRepeats = (str) => str.replace(/(.){2,}/g, '$1');

    const hasBadWord = (text) => {
        const lower = text.toLowerCase();
        const norm  = normalizeJs(text);
        const col   = collapseRepeats(norm);
        for (let w of badWords) {
            const wC = collapseRepeats(normalizeJs(w));
            if (lower.includes(w.toLowerCase()) || norm.includes(normalizeJs(w)) || col.includes(wC))
                return true;
        }
        return false;
    };

    const checkLive = (el) => {
        if (hasBadWord(el.value)) {
            el.classList.add('bad-input');
            showNotice('আপত্তিকর ভাষা পাওয়া গেছে!', 'error');
            document.getElementById('go2').disabled = true;
            document.getElementById('finalBtn').disabled = true;
        } else {
            el.classList.remove('bad-input');
            document.getElementById('go2').disabled = false;
            document.getElementById('finalBtn').disabled = false;
        }
    };

    document.getElementById('sName')?.addEventListener('input', e => checkLive(e.target));
    document.querySelector('textarea[name="message"]')?.addEventListener('input', e => checkLive(e.target));


    // ── Form submit with Premium Alerts ──
    const form = document.getElementById('salamiForm');
    form?.addEventListener('submit', function(e) {
        e.preventDefault();

        // Hard profanity gate (catches paste, autofill, emoji, any bypass)
        const nameVal = document.getElementById('sName')?.value || '';
        const msgVal  = document.querySelector('textarea[name="message"]')?.value || '';
        if (hasBadWord(nameVal) || hasBadWord(msgVal)) {
            showNotice('আপত্তিকর ভাষা ব্যবহার করা যাবে না।', 'error');
            return; // stops fetch entirely
        }

        const btn = document.getElementById('finalBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> প্রসেসিং…';

        fetch(form.action, {
            method: 'POST',
            body: new FormData(this),
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                form.style.display = 'none';
                document.querySelector('.step-indicator').style.display = 'none';
                document.getElementById('successBox').style.display = 'block';
            } else {
                showNotice(data.message || 'সমস্যা হয়েছে।', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> সালামি নিশ্চিত করুন 🎉';
            }
        })
        .catch(() => {
            showNotice('সার্ভার সমস্যা হয়েছে।', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> সালামি নিশ্চিত করুন 🎉';
        });
    });
})();
</script>

<!-- ── BACKGROUND AUDIO ── -->
<iframe src="about:blank" allow="autoplay" id="audioTrick" style="display:none"></iframe>
<?php if ($firstTrack): ?>
<audio id="bgAudio" preload="auto" autoplay src="<?= $firstTrack ?>"></audio>

<button id="audioToggle" title="অডিও চালু/বন্ধ করুন" style="
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 9999;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: none;
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(12px);
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    transition: all 0.2s;
">
    <i class="fa-solid fa-volume-high" id="audioIcon"></i>
</button>

<script>
(function() {
    const audio  = document.getElementById('bgAudio');
    const btn    = document.getElementById('audioToggle');
    const icon   = document.getElementById('audioIcon');
    const tracks = <?= json_encode($playlist) ?>;
    let current  = 0;
    let played   = false;

    function setIcon(muted) {
        icon.className = muted ? 'fa-solid fa-volume-xmark' : 'fa-solid fa-volume-high';
    }

    function tryPlay() {
        if (played) return;
        audio.volume = 0.4;
        audio.play().then(() => {
            played = true;
            setIcon(audio.muted);
        }).catch(() => {});
    }

    audio.addEventListener('ended', function() {
        current = (current + 1) % tracks.length;
        console.log("Playing next track: " + tracks[current]);
        audio.src = tracks[current];
        audio.load();
        audio.play().catch(e => console.error("Playback failed", e));
    });

    // Aggressive triggers: Play on first interaction
    const triggerAudio = () => {
        if (played) return;
        audio.volume = 0.4;
        audio.play().then(() => {
            played = true;
            setIcon(audio.muted);
            // Remove listeners once successful
            ['scroll', 'wheel', 'click', 'mousedown', 'keydown', 'touchstart', 'pointerdown'].forEach(ev => {
                window.removeEventListener(ev, triggerAudio);
            });
            console.log("Playback started by user gesture");
        }).catch(() => {
            // Still blocked, listener stays active
        });
    };

    ['scroll', 'wheel', 'click', 'mousedown', 'keydown', 'touchstart', 'pointerdown'].forEach(ev => {
        window.addEventListener(ev, triggerAudio, { passive: true });
    });

    tryPlay(); // Initial attempt


    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        if (!played) { tryPlay(); return; }
        audio.muted = !audio.muted;
        setIcon(audio.muted);
        btn.style.background = audio.muted ? 'rgba(239,68,68,0.3)' : 'rgba(255,255,255,0.1)';
    });

    btn.addEventListener('mouseenter', () => btn.style.transform = 'scale(1.1)');
    btn.addEventListener('mouseleave', () => btn.style.transform = 'scale(1)');
})();
</script>
<?php endif; ?>

</body>
</html>
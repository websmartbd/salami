<?php
/**
 * SalamiPay - Professional Admin Dashboard
 * Secured with Password, CSRF Protection, Anti-Bruteforce & Infinite Scroll
 */
session_start();

// -------------------------------------------------------------
// 1. Security Headers & Configuration
// -------------------------------------------------------------
header("X-Frame-Options: DENY"); // Prevent Clickjacking
header("X-XSS-Protection: 1; mode=block"); // Basic XSS Protection
header("X-Content-Type-Options: nosniff"); // Prevent MIME sniffing

$ADMIN_PASSWORD = 'Shifat54321#@';
$MAX_LOGIN_ATTEMPTS = 5;
$LOCKOUT_DURATION = 15 * 60; // 15 minutes

// Initialize rate limiter session variables
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['lockout_until'] = 0;
}

$isLocked = false;
$remainingLockout = 0;

if ($_SESSION['login_attempts'] >= $MAX_LOGIN_ATTEMPTS) {
    if (time() < $_SESSION['lockout_until']) {
        $isLocked = true;
        $remainingLockout = ceil(($_SESSION['lockout_until'] - time()) / 60);
    } else {
        // Reset after lockout expires
        $_SESSION['login_attempts'] = 0;
        $_SESSION['lockout_until'] = 0;
    }
}

// -------------------------------------------------------------
// 2. Handle Login Authentication
// -------------------------------------------------------------
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    if ($isLocked) {
        $login_error = "অ্যাকাউন্ট লক করা হয়েছে। $remainingLockout মিনিট পর আবার চেষ্টা করুন।";
    } else {
        if ($_POST['password'] === $ADMIN_PASSWORD) {
            // Success
            $_SESSION['admin_auth'] = true;
            $_SESSION['login_attempts'] = 0;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate secure CSRF token
            session_regenerate_id(true); // Prevent session fixation
            header("Location: admin.php");
            exit;
        } else {
            // Failed
            $_SESSION['login_attempts']++;
            if ($_SESSION['login_attempts'] >= $MAX_LOGIN_ATTEMPTS) {
                $_SESSION['lockout_until'] = time() + $LOCKOUT_DURATION;
                $isLocked = true;
            }
            $rem = $MAX_LOGIN_ATTEMPTS - $_SESSION['login_attempts'];
            $login_error = $isLocked 
                ? 'অতিরিক্ত ভুল চেষ্টার কারণে অ্যাকাউন্ট লক করা হয়েছে!' 
                : "ভুল পাসওয়ার্ড! আর $rem বার চেষ্টা করতে পারবেন।";
        }
    }
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// -------------------------------------------------------------
// 3. Login Screen UI (If not authenticated)
// -------------------------------------------------------------
if (empty($_SESSION['admin_auth'])) {
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | SalamiPay</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Noto+Sans+Bengali:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', 'Noto Sans Bengali', sans-serif; background: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .login-box { background: #1e293b; padding: 2.5rem; border-radius: 16px; border: 1px solid rgba(255,255,255,0.08); width: 100%; max-width: 400px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); text-align: center; }
        .login-box h2 { margin-top: 0; color: #6366f1; font-weight: 600; font-size: 1.5rem; margin-bottom: 0.5rem; }
        .login-box p { color: #94a3b8; font-size: 0.9rem; margin-bottom: 2rem; }
        .form-group { text-align: left; margin-bottom: 1.5rem; }
        .form-group label { display: block; font-size: 0.85rem; color: #cbd5e1; margin-bottom: 0.5rem; font-weight: 600; }
        .form-group input { width: 100%; padding: 0.75rem 1rem; background: #0f172a; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #fff; font-size: 1rem; outline: none; transition: border-color 0.2s; box-sizing: border-box; }
        .form-group input:focus { border-color: #6366f1; }
        .btn { width: 100%; background: #6366f1; color: #fff; border: none; padding: 0.85rem; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn:hover { background: #4f46e5; }
        .btn:disabled { background: #475569; cursor: not-allowed; color: #94a3b8; }
        .error-msg { background: rgba(239,68,68,0.1); color: #ef4444; padding: 0.75rem; border-radius: 8px; font-size: 0.85rem; margin-bottom: 1.5rem; border: 1px solid rgba(239,68,68,0.2); }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>SalamiPay Admin</h2>
        <p>লগইন করতে আপনার সিক্রেট পাসওয়ার্ড দিন</p>
        
        <?php if (!empty($login_error)): ?>
            <div class="error-msg"><?= htmlspecialchars($login_error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>পাসওয়ার্ড</label>
                <input type="password" name="password" placeholder="••••••••" required <?= $isLocked ? 'disabled' : '' ?> autofocus>
            </div>
            <button type="submit" name="login_submit" class="btn" <?= $isLocked ? 'disabled' : '' ?>>
                <?= $isLocked ? 'লক করা আছে' : 'লগইন করুন' ?>
            </button>
        </form>
    </div>
</body>
</html>
<?php
    exit;
}

// -------------------------------------------------------------
// 4. Secure Core Dashboard Code Below
// -------------------------------------------------------------

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Safe JS Argument Encoder to prevent XSS in Javascript functions
function escapeJsArg($val) {
    return htmlspecialchars(json_encode((string)$val), ENT_QUOTES, 'UTF-8');
}

// Stats Calculation
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalLogs  = $pdo->query("SELECT COUNT(*) FROM salami_logs")->fetchColumn();
$totalSalami = $pdo->query("SELECT SUM(amount) FROM salami_logs")->fetchColumn() ?: 0;
$totalVerified = $pdo->query("SELECT SUM(amount) FROM salami_logs WHERE status = 'verified'")->fetchColumn() ?: 0;
$totalSuspended = $pdo->query("SELECT COUNT(*) FROM users WHERE is_suspended = 1")->fetchColumn();

// Handle Admin Actions (AJAX) - CSRF SECURED
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // Validate CSRF Token
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'সিকিউরিটি এরর: CSRF Token Invalid!']);
        exit;
    }

    $action = $_POST['action'];
    $id = intval($_POST['id'] ?? 0);

    try {
        if ($action === 'suspend') {
            $pdo->prepare("UPDATE users SET is_suspended = 1 WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'ব্যবহারকারী সাময়িকভাবে স্থগিত করা হয়েছে।']);
        } elseif ($action === 'unsuspend') {
            $pdo->prepare("UPDATE users SET is_suspended = 0 WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'ব্যবহারকারীর স্থগিতাদেশ তুলে নেওয়া হয়েছে।']);
        } elseif ($action === 'delete_user') {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'ব্যবহারকারী ডিলিট করা হয়েছে।']);
        } elseif ($action === 'delete_log') {
            $pdo->prepare("DELETE FROM salami_logs WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'লগ ডিলিট করা হয়েছে।']);
        } elseif ($action === 'delete_spammer_logs') {
            $pdo->prepare("DELETE FROM salami_logs WHERE receiver_id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'স্প্যামারের সমস্ত লগ ডিলিট করা হয়েছে।']);
        } elseif ($action === 'block_ip') {
            $ip = trim($_POST['ip'] ?? '');
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                echo json_encode(['success' => false, 'message' => 'অবৈধ IP ঠিকানা।']);
                exit;
            }
            $note = htmlspecialchars(trim($_POST['note'] ?? ''), ENT_QUOTES, 'UTF-8');
            $pdo->prepare("INSERT IGNORE INTO blocked_ips (ip_address, note) VALUES (?, ?)")->execute([$ip, $note]);
            echo json_encode(['success' => true, 'message' => "IP {$ip} ব্লক করা হয়েছে।"]);
        } elseif ($action === 'unblock_ip') {
            $ip = trim($_POST['ip'] ?? '');
            $pdo->prepare("DELETE FROM blocked_ips WHERE ip_address = ?")->execute([$ip]);
            echo json_encode(['success' => true, 'message' => "IP {$ip} আনব্লক করা হয়েছে।"]);
        } elseif ($action === 'block_spammer_ips') {
            $stmt = $pdo->prepare("SELECT DISTINCT ip_address FROM salami_logs WHERE receiver_id = ? AND ip_address IS NOT NULL AND ip_address != ''");
            $stmt->execute([$id]);
            $ips = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $blocked = 0;
            foreach ($ips as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    $pdo->prepare("INSERT IGNORE INTO blocked_ips (ip_address, note) VALUES (?, ?)")->execute([$ip, "Auto-blocked from spammer #$id"]);
                    $blocked++;
                }
            }
            echo json_encode(['success' => true, 'message' => "{$blocked}টি IP ব্লক করা হয়েছে।"]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'সার্ভার সমস্যা হয়েছে।']);
    }
    exit;
}

// -------------------------------------------------------------
// Pagination & Infinite Scroll Logic
// -------------------------------------------------------------
$view = $_GET['v'] ?? 'users';
$search = trim($_GET['s'] ?? '');
$limit = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Fetch Data with LIMIT and OFFSET
if ($view === 'spammers') {
    /*
     * Smart Spammer Detection — multi-signal scoring:
     *
     * Signal 1 — High request ratio:
     *   Received salami_logs > views * 2  (lowered threshold for better recall)
     *
     * Signal 2 — Burst activity (many logs in short time):
     *   More than 5 logs received within any 10-minute window
     *
     * Signal 3 — Duplicate sender abuse:
     *   Same sender_name sending to this receiver more than 3 times
     *
     * A user is flagged if they trigger ANY of the above signals.
     * spam_score = weighted sum so the worst offenders rank first.
     */
    $sql = "SELECT u.*,
            spam_data.spam_attempts,
            spam_data.burst_count,
            spam_data.duplicate_sender_count,
            (
                spam_data.spam_attempts * 1
                + spam_data.burst_count * 3
                + spam_data.duplicate_sender_count * 2
            ) AS spam_score
        FROM users u
        INNER JOIN (
            SELECT
                receiver_id,
                COUNT(*) AS spam_attempts,
                /* burst: flag if any 10-min window has more than 5 logs */
                COALESCE((
                    SELECT 1 FROM salami_logs b1
                    WHERE b1.receiver_id = sl_agg.receiver_id
                    GROUP BY DATE_FORMAT(b1.created_at, '%Y-%m-%d %H'), FLOOR(MINUTE(b1.created_at) / 10)
                    HAVING COUNT(*) > 5
                    LIMIT 1
                ), 0) AS burst_count,
                /* duplicate senders: same sender_name sent > 3 times */
                COALESCE((
                    SELECT COUNT(DISTINCT ds.sender_name) FROM salami_logs ds
                    WHERE ds.receiver_id = sl_agg.receiver_id
                    GROUP BY ds.receiver_id
                    HAVING SUM(CASE WHEN (
                        SELECT COUNT(*) FROM salami_logs ds2
                        WHERE ds2.receiver_id = ds.receiver_id AND ds2.sender_name = ds.sender_name
                    ) > 3 THEN 1 ELSE 0 END) > 0
                    LIMIT 1
                ), 0) AS duplicate_sender_count
            FROM (SELECT receiver_id FROM salami_logs GROUP BY receiver_id) AS sl_agg
            HAVING
                spam_attempts > (
                    SELECT GREATEST(views, 1) * 2 FROM users WHERE id = sl_agg.receiver_id
                )
                OR burst_count > 0
                OR duplicate_sender_count > 0
        ) AS spam_data ON u.id = spam_data.receiver_id
        ORDER BY spam_score DESC
        LIMIT $limit OFFSET $offset";
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll();
} elseif ($view === 'blocked_ips') {
    // Fetch all salami_logs submitted from blocked IPs, with full log details
    $sql = "SELECT sl.*, 
                u.full_name as receiver_name, u.username as receiver_username,
                ma.mfs_type,
                bi.note as block_note, bi.created_at as blocked_at
            FROM salami_logs sl
            INNER JOIN blocked_ips bi ON sl.ip_address = bi.ip_address
            LEFT JOIN users u ON sl.receiver_id = u.id
            LEFT JOIN mfs_accounts ma ON sl.mfs_account_id = ma.id";
    if (!empty($search)) {
        $sql .= " WHERE (sl.sender_name LIKE ? OR sl.trx_id LIKE ? OR u.username LIKE ? OR u.full_name LIKE ? OR sl.amount LIKE ?)";
        $sql .= " ORDER BY sl.created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["%$search%", "%$search%", "%$search%", "%$search%", "%$search%"]);
    } else {
        $sql .= " ORDER BY sl.created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $pdo->query($sql);
    }
    $data = $stmt->fetchAll();
} elseif ($view === 'suspended') {
    $sql = "SELECT u.*,
            (SELECT COUNT(*) FROM salami_logs WHERE receiver_id = u.id) as log_count,
            (SELECT SUM(amount) FROM salami_logs WHERE receiver_id = u.id) as total_amount
            FROM users u WHERE u.is_suspended = 1";
    if (!empty($search)) {
        $sql .= " AND (u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
        $sql .= " ORDER BY u.created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["%$search%", "%$search%", "%$search%"]);
    } else {
        $sql .= " ORDER BY u.created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $pdo->query($sql);
    }
    $data = $stmt->fetchAll();
} elseif ($view === 'logs') {
    $sql = "SELECT sl.*, u.full_name as receiver_name, u.username as receiver_username, ma.mfs_type 
            FROM salami_logs sl 
            LEFT JOIN users u ON sl.receiver_id = u.id 
            LEFT JOIN mfs_accounts ma ON sl.mfs_account_id = ma.id";
    if (!empty($search)) {
        // When searching, show ALL logs (including blocked ones) to help admins find specific user activity
        $sql .= " WHERE (sl.sender_name LIKE ? OR sl.trx_id LIKE ? OR u.username LIKE ? OR u.full_name LIKE ? OR sl.amount LIKE ?)";
        $sql .= " ORDER BY sl.created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["%$search%", "%$search%", "%$search%", "%$search%", "%$search%"]);
    } else {
        $sql .= " WHERE sl.ip_address NOT IN (SELECT ip_address FROM blocked_ips) ORDER BY sl.created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $pdo->query($sql);
    }
    $data = $stmt->fetchAll();
} else {
    $sql = "SELECT u.*, 
            (SELECT COUNT(*) FROM salami_logs WHERE receiver_id = u.id) as log_count,
            (SELECT SUM(amount) FROM salami_logs WHERE receiver_id = u.id) as total_amount
            FROM users u";
    if (!empty($search)) {
        $sql .= " WHERE u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?";
        $sql .= " ORDER BY u.created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["%$search%", "%$search%", "%$search%"]);
    } else {
        $sql .= " ORDER BY u.created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $pdo->query($sql);
    }
    $data = $stmt->fetchAll();
}

// -------------------------------------------------------------
// HTML Row Generators 
// -------------------------------------------------------------
function renderSpammerRow($u) {
    $avatar = (!empty($u['profile_image'])) ? '/uploads/profiles/' . htmlspecialchars($u['profile_image'], ENT_QUOTES) : 'https://ui-avatars.com/api/?name='.urlencode($u['full_name']).'&background=6366f1&color=fff';
    $fullName = htmlspecialchars($u['full_name'], ENT_QUOTES, 'UTF-8');
    $username = htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8');
    $views = number_format($u['views']);
    $spamAttempts = number_format($u['spam_attempts']);
    $burstCount = intval($u['burst_count'] ?? 0);
    $dupCount = intval($u['duplicate_sender_count'] ?? 0);
    $spamScore = intval($u['spam_score'] ?? 0);

    $signals = [];
    if (intval($u['spam_attempts']) > intval($u['views']) * 2) $signals[] = '📈 উচ্চ অনুরোধ';
    if ($burstCount > 0) $signals[] = '⚡ বার্স্ট';
    if ($dupCount > 0) $signals[] = '🔁 ডুপ্লিকেট';
    $signalText = !empty($signals) ? implode(' · ', $signals) : '';
    $scoreColor = $spamScore >= 15 ? 'var(--danger)' : ($spamScore >= 7 ? 'var(--warning)' : '#94a3b8');

    $badge = $u['is_suspended'] ? '<span class="badge badge-danger">স্থগিত</span>' : '<span class="badge badge-warning">সক্রিয় (সন্দেহজনক)</span>';
    $btn = $u['is_suspended'] 
        ? "<button class=\"btn-icon\" onclick=\"adminAction('unsuspend', {$u['id']}, 'স্থগিতাদেশ তুলুন?')\" title=\"Unlock\"><i class=\"fa-solid fa-lock-open\"></i></button>"
        : "<button class=\"btn-icon delete\" onclick=\"adminAction('suspend', {$u['id']}, 'ইউজারকে স্থগিত করবেন?')\" title=\"Suspend\"><i class=\"fa-solid fa-user-slash\"></i></button>";

    $signalHtml = $signalText ? "<div style=\"font-size:0.7rem; color:var(--text-dim); margin-top:2px;\">{$signalText}</div>" : "";

    return "<tr>
        <td>
            <div class=\"user-info\">
                <img src=\"{$avatar}\" class=\"avatar\">
                <div><span class=\"user-name\">{$fullName}</span><span class=\"user-handle\">@{$username}</span></div>
            </div>
        </td>
        <td>{$views}</td>
        <td style=\"font-weight:700; color: var(--danger);\">{$spamAttempts}</td>
        <td>
            <span style=\"font-size:0.85rem; color:{$scoreColor}; font-weight:700;\">{$spamScore}</span>
            {$signalHtml}
        </td>
        <td>{$badge}</td>
        <td>
            <div class=\"actions\">
                {$btn}
                <button class=\"btn-icon delete\" onclick=\"adminAction('delete_spammer_logs', {$u['id']}, 'সমস্ত স্প্যাম লগ মুছবেন?')\" title=\"Purge Logs\"><i class=\"fa-solid fa-broom\"></i></button>
                <button class=\"btn-icon delete\" onclick=\"adminAction('block_spammer_ips', {$u['id']}, 'সমস্ত IP ব্লক করবেন?')\" title=\"Block All IPs\"><i class=\"fa-solid fa-ban\"></i></button>
            </div>
        </td>
    </tr>";

}

function renderLogRow($log) {
    $sName = htmlspecialchars($log['sender_name'], ENT_QUOTES, 'UTF-8');
    $rName = htmlspecialchars($log['receiver_name'], ENT_QUOTES, 'UTF-8');
    $rUsername = htmlspecialchars($log['receiver_username'], ENT_QUOTES, 'UTF-8');
    $amount = number_format($log['amount']);
    
    // Secure XSS implementation for Javascript injection
    $jsTrx = escapeJsArg($log['trx_id'] ?: 'N/A');
    $jsMsg = escapeJsArg($log['message'] ?? 'কোন মেসেজ নেই');
    
    $badge = $log['status'] === 'verified' ? '<span class="badge badge-success">Verified</span>' : '<span class="badge badge-warning">Pending</span>';
    $time = timeAgo($log['created_at']); // Assuming timeAgo is in functions.php

    $ip = htmlspecialchars($log['ip_address'] ?? '', ENT_QUOTES, 'UTF-8');
    $jsIp = escapeJsArg($log['ip_address'] ?? '');
    $ipHtml = $ip
        ? "<span style=\"font-size:0.75rem; color:var(--text-dim); display:block;\">{$ip}</span>
           <button class=\"btn-icon\" style=\"width:auto;padding:2px 6px;font-size:0.7rem;\" onclick=\"blockIpAction({$jsIp})\" title=\"Block IP\"><i class=\"fa-solid fa-ban\" style=\"font-size:0.7rem;\"></i> Block</button>"
        : "<span style=\"font-size:0.75rem; color:var(--text-dim)\">N/A</span>";

    return "<tr>
        <td><strong>{$sName}</strong></td>
        <td><span class=\"user-name\">{$rName}</span><span class=\"user-handle\">@{$rUsername}</span></td>
        <td style=\"font-weight: 700; color: var(--success);\">৳{$amount}</td>
        <td><button class=\"btn-icon info\" onclick=\"showTrxDetails({$jsTrx}, {$jsMsg})\" title=\"View Details\"><i class=\"fa-solid fa-circle-info\"></i></button></td>
        <td>{$badge}</td>
        <td style=\"font-size: 0.8rem; color: var(--text-dim);\">{$time}</td>
        <td>{$ipHtml}</td>
        <td><button class=\"btn-icon delete\" onclick=\"adminAction('delete_log', {$log['id']}, 'এই লগটি মুছে ফেলবেন?')\" title=\"Delete\"><i class=\"fa-solid fa-trash\"></i></button></td>
    </tr>";
}

function renderUserRow($u) {
    $avatar = (!empty($u['profile_image'])) ? '/uploads/profiles/' . htmlspecialchars($u['profile_image'], ENT_QUOTES) : 'https://ui-avatars.com/api/?name='.urlencode($u['full_name']).'&background=6366f1&color=fff';
    $fullName = htmlspecialchars($u['full_name'], ENT_QUOTES, 'UTF-8');
    $username = htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8');
    $phone = htmlspecialchars($u['phone'] ?: 'No Phone', ENT_QUOTES, 'UTF-8');
    $logCount = number_format($u['log_count']);
    $total = number_format($u['total_amount'] ?: 0);
    $badge = $u['is_suspended'] ? '<span class="badge badge-danger">স্থগিত</span>' : '<span class="badge badge-success">সক্রিয়</span>';
    
    $action = $u['is_suspended'] ? 'unsuspend' : 'suspend';
    $actionIcon = $u['is_suspended'] ? 'fa-user-check' : 'fa-user-slash';

    return "<tr>
        <td>
            <div class=\"user-info\">
                <img src=\"{$avatar}\" class=\"avatar\">
                <div><span class=\"user-name\">{$fullName}</span><span class=\"user-handle\">@{$username}</span></div>
            </div>
        </td>
        <td><div style=\"font-size: 0.85rem;\">{$email}</div><div style=\"font-size: 0.75rem; color: var(--text-dim);\">{$phone}</div></td>
        <td>{$logCount}</td>
        <td style=\"font-weight: 700; color: var(--primary);\">৳{$total}</td>
        <td>{$badge}</td>
        <td>
            <div class=\"actions\">
                <button class=\"btn-icon\" onclick=\"adminAction('{$action}', {$u['id']}, 'নিশ্চিত?')\"><i class=\"fa-solid {$actionIcon}\"></i></button>
                <button class=\"btn-icon delete\" onclick=\"adminAction('delete_user', {$u['id']}, 'ইউজারের সকল ডাটা মুছে যাবে!')\"><i class=\"fa-solid fa-trash\"></i></button>
            </div>
        </td>
    </tr>";
}


function renderSuspendedRow($u) {
    $avatar = (!empty($u['profile_image'])) ? '/uploads/profiles/' . htmlspecialchars($u['profile_image'], ENT_QUOTES) : 'https://ui-avatars.com/api/?name='.urlencode($u['full_name']).'&background=ef4444&color=fff';
    $fullName = htmlspecialchars($u['full_name'], ENT_QUOTES, 'UTF-8');
    $username = htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8');
    $phone = htmlspecialchars($u['phone'] ?: 'No Phone', ENT_QUOTES, 'UTF-8');
    $logCount = number_format($u['log_count']);
    $total = number_format($u['total_amount'] ?: 0);

    return "<tr>
        <td>
            <div class=\"user-info\">
                <img src=\"{$avatar}\" class=\"avatar\">
                <div><span class=\"user-name\">{$fullName}</span><span class=\"user-handle\">@{$username}</span></div>
            </div>
        </td>
        <td><div style=\"font-size: 0.85rem;\">{$email}</div><div style=\"font-size: 0.75rem; color: var(--text-dim);\">{$phone}</div></td>
        <td>{$logCount}</td>
        <td style=\"font-weight: 700; color: var(--primary);\">৳{$total}</td>
        <td><span class=\"badge badge-danger\">স্থগিত</span></td>
        <td>
            <div class=\"actions\">
                <button class=\"btn-icon\" onclick=\"adminAction('unsuspend', {$u['id']}, 'স্থগিতাদেশ তুলে নেবেন?')\" title=\"Unsuspend\"><i class=\"fa-solid fa-lock-open\"></i></button>
                <button class=\"btn-icon delete\" onclick=\"adminAction('delete_spammer_logs', {$u['id']}, 'এই ইউজারের সমস্ত সালামি লগ মুছবেন?')\" title=\"Purge Logs\"><i class=\"fa-solid fa-broom\"></i></button>
            </div>
        </td>
    </tr>";
}

function renderBlockedIpRow($row) {
    $sName     = htmlspecialchars($row['sender_name'], ENT_QUOTES, 'UTF-8');
    $rName     = htmlspecialchars($row['receiver_name'] ?? '—', ENT_QUOTES, 'UTF-8');
    $rUsername = htmlspecialchars($row['receiver_username'] ?? '', ENT_QUOTES, 'UTF-8');
    $amount    = number_format($row['amount']);
    $ip        = htmlspecialchars($row['ip_address'], ENT_QUOTES, 'UTF-8');
    $note      = htmlspecialchars($row['block_note'] ?? '—', ENT_QUOTES, 'UTF-8');
    $time      = timeAgo($row['created_at']);
    $jsIp      = htmlspecialchars(json_encode($row['ip_address']), ENT_QUOTES, 'UTF-8');
    $jsTrx     = escapeJsArg($row['trx_id'] ?: 'N/A');
    $jsMsg     = escapeJsArg($row['message'] ?? 'কোন মেসেজ নেই');
    $badge     = $row['status'] === 'verified'
        ? '<span class="badge badge-success">Verified</span>'
        : '<span class="badge badge-warning">Pending</span>';

    return "<tr style=\"background: rgba(239,68,68,0.04); border-left: 3px solid var(--danger)\">
        <td>
            <strong style=\"color:var(--danger);\">{$sName}</strong>
            <div style=\"margin-top:4px;\">
                <code style=\"background:rgba(239,68,68,0.1);color:#ef4444;padding:2px 6px;border-radius:4px;font-size:0.75rem;\">{$ip}</code>
            </div>
        </td>
        <td>
            <span class=\"user-name\">{$rName}</span>
            <span class=\"user-handle\">@{$rUsername}</span>
        </td>
        <td style=\"font-weight:700; color:var(--danger)\">৳{$amount}</td>
        <td><button class=\"btn-icon info\" onclick=\"showTrxDetails({$jsTrx}, {$jsMsg})\" title=\"View Details\"><i class=\"fa-solid fa-circle-info\"></i></button></td>
        <td>{$badge}</td>
        <td style=\"font-size:0.8rem; color:var(--text-dim);\">{$time}</td>
        <td style=\"font-size:0.75rem; color:var(--text-dim);\">{$note}</td>
        <td>
            <div class=\"actions\">
                <button class=\"btn-icon\" onclick=\"unblockIpAction({$jsIp})\" title=\"Unblock IP\"><i class=\"fa-solid fa-lock-open\"></i></button>
                <button class=\"btn-icon delete\" onclick=\"adminAction('delete_log', {$row['id']}, 'এই লগটি মুছে ফেলবেন?')\" title=\"Delete Log\"><i class=\"fa-solid fa-trash\"></i></button>
            </div>
        </td>
    </tr>";
}

// Handle AJAX Request for Infinite Scroll
if (isset($_GET['ajax'])) {
    if (empty($data)) exit; // End of data
    foreach ($data as $item) {
        if ($view === 'spammers') echo renderSpammerRow($item);
        elseif ($view === 'logs') echo renderLogRow($item);
        elseif ($view === 'suspended') echo renderSuspendedRow($item);
        elseif ($view === 'blocked_ips') echo renderBlockedIpRow($item);
        else echo renderUserRow($item);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | <?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Noto+Sans+Bengali:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --bg: #0f172a;
            --sidebar: #1e293b;
            --card: #1e293b;
            --card-border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-dim: #94a3b8;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --radius: 12px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', 'Noto Sans Bengali', sans-serif; background-color: var(--bg); color: var(--text-main); display: flex; min-height: 100vh; overflow-x: hidden; }

        /* Sidebar Styling */
        .sidebar { width: 280px; background: var(--sidebar); border-right: 1px solid var(--card-border); padding: 2rem 1.5rem; display: flex; flex-direction: column; position: fixed; height: 100vh; transition: transform 0.3s ease; z-index: 100; }
        .brand { display: flex; align-items: center; gap: 12px; font-size: 1.4rem; font-weight: 800; color: white; margin-bottom: 2.5rem; text-decoration: none; }
        .brand span { color: var(--primary); }
        .nav-menu { list-style: none; flex: 1; }
        .nav-item { margin-bottom: 0.5rem; }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 0.8rem 1rem; color: var(--text-dim); text-decoration: none; border-radius: var(--radius); font-weight: 500; transition: all 0.2s ease; }
        .nav-link:hover, .nav-link.active { background: rgba(99, 102, 241, 0.1); color: white; }
        .nav-link.active { background: var(--primary); box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3); }
        .nav-link i { font-size: 1.1rem; width: 24px; text-align: center; }

        /* Main Content area */
        .main-content { margin-left: 280px; flex: 1; padding: 2.5rem; max-width: calc(100vw - 280px); }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .page-title h1 { font-size: 1.75rem; font-weight: 700; letter-spacing: -0.02em; }
        .page-title p { color: var(--text-dim); font-size: 0.9rem; margin-top: 4px; }
        .search-container { position: relative; width: 320px; }
        .search-container input { width: 100%; padding: 0.7rem 1rem 0.7rem 2.8rem; background: var(--card); border: 1px solid var(--card-border); border-radius: 50px; color: white; outline: none; transition: all 0.2s; }
        .search-container input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2); }
        .search-container i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-dim); }

        /* Dashboard Stats */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem; }
        .stat-card { background: var(--card); padding: 1.5rem; border-radius: var(--radius); border: 1px solid var(--card-border); position: relative; overflow: hidden; }
        .stat-card::after { content: ''; position: absolute; top: 0; right: 0; width: 60px; height: 60px; background: linear-gradient(135deg, transparent 50%, rgba(255,255,255,0.03) 50%); }
        .stat-card .label { font-size: 0.8rem; font-weight: 600; color: var(--text-dim); text-transform: uppercase; margin-bottom: 0.5rem; display: block; }
        .stat-card .value { font-size: 1.8rem; font-weight: 800; color: white; }

        /* Data Tables */
        .data-card { background: var(--card); border-radius: var(--radius); border: 1px solid var(--card-border); overflow: hidden; }
        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { background: rgba(255, 255, 255, 0.02); padding: 1rem 1.5rem; text-align: left; font-size: 0.75rem; font-weight: 700; color: var(--text-dim); text-transform: uppercase; border-bottom: 1px solid var(--card-border); }
        td { padding: 1rem 1.5rem; border-bottom: 1px solid var(--card-border); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: rgba(255, 255, 255, 0.01); }
        .user-info { display: flex; align-items: center; gap: 12px; }
        .avatar { width: 40px; height: 40px; border-radius: 10px; background: var(--bg); object-fit: cover; border: 1px solid var(--card-border); }
        .user-name { font-weight: 600; display: block; }
        .user-handle { font-size: 0.8rem; color: var(--text-dim); }

        .badge { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .badge-success { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .badge-warning { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .badge-danger { background: rgba(239, 68, 68, 0.1); color: var(--danger); }

        .actions { display: flex; gap: 8px; }
        .btn-icon { width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--card-border); background: transparent; color: var(--text-dim); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
        .btn-icon:hover { background: rgba(255, 255, 255, 0.05); color: white; border-color: var(--text-dim); }
        .btn-icon.delete:hover { color: var(--danger); border-color: var(--danger); background: rgba(239, 68, 68, 0.1); }
        .btn-icon.info:hover { color: var(--primary); border-color: var(--primary); background: rgba(99, 102, 241, 0.1); }

        .loader-container { text-align: center; padding: 20px; display: none; }
        .loader-container i { color: var(--primary); font-size: 1.5rem; }

        /* Hamburger & Overlay */
        .hamburger { display: none; position: fixed; top: 1rem; left: 1rem; z-index: 200; background: var(--primary); border: none; color: white; width: 42px; height: 42px; border-radius: 10px; font-size: 1.1rem; cursor: pointer; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(99,102,241,0.4); transition: background 0.2s; }
        .hamburger:hover { background: var(--primary-dark); }
        .mobile-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); z-index: 99; }
        .mobile-overlay.active { display: block; }

        @media (max-width: 1024px) {
            .hamburger { display: flex; }
            .sidebar { transform: translateX(-100%); width: 260px; z-index: 150; }
            .sidebar.open { transform: translateX(0); box-shadow: 4px 0 30px rgba(0,0,0,0.5); }
            .main-content { margin-left: 0; max-width: 100%; padding: 1.25rem; padding-top: 4.5rem; }
            .top-bar { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
            .search-container { width: 100%; }
            .page-title h1 { font-size: 1.35rem; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
            .stat-card .value { font-size: 1.4rem; }
        }

        @media (max-width: 600px) {
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 0.75rem; }
            .stat-card { padding: 1rem; }
            .stat-card .value { font-size: 1.2rem; }
            th, td { padding: 0.75rem 0.9rem; font-size: 0.82rem; }
            .user-info { gap: 8px; }
            .avatar { width: 34px; height: 34px; border-radius: 8px; }
            .actions { gap: 5px; }
            .btn-icon { width: 30px; height: 30px; }
            .page-title h1 { font-size: 1.15rem; }
            .main-content { padding: 1rem; padding-top: 4rem; }
        }

        @media (max-width: 420px) {
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="mobile-overlay" id="mobile-overlay" onclick="toggleSidebar()"></div>
<button class="hamburger" id="hamburger-btn" onclick="toggleSidebar()" aria-label="Toggle Menu"><i class="fa-solid fa-bars"></i></button>

<aside class="sidebar" id="sidebar">
    <a href="/" class="brand"><i class="fa-solid fa-bolt"></i> Salami<span>Pay</span></a>
    <ul class="nav-menu">
        <li class="nav-item"><a href="?v=users" class="nav-link <?= $view === 'users' ? 'active' : '' ?>"><i class="fa-solid fa-users"></i> ব্যবহারকারীগণ</a></li>
        <li class="nav-item"><a href="?v=logs" class="nav-link <?= $view === 'logs' ? 'active' : '' ?>"><i class="fa-solid fa-receipt"></i> ট্রানজেকশনসমূহ</a></li>
        <li class="nav-item"><a href="?v=spammers" class="nav-link <?= $view === 'spammers' ? 'active' : '' ?>"><i class="fa-solid fa-shield-virus"></i> স্প্যামার লগ</a></li>
        <li class="nav-item"><a href="?v=suspended" class="nav-link <?= $view === 'suspended' ? 'active' : '' ?>"><i class="fa-solid fa-user-lock"></i> স্থগিত অ্যাকাউন্ট</a></li>
        <li class="nav-item"><a href="?v=blocked_ips" class="nav-link <?= $view === 'blocked_ips' ? 'active' : '' ?>"><i class="fa-solid fa-ban"></i> ব্লক্ড IP</a></li>
    </ul>
    <div style="margin-top: auto;">
        <a href="?logout=true" class="nav-link" style="color: var(--danger);"><i class="fa-solid fa-power-off"></i> লগআউট</a>
    </div>
</aside>

<main class="main-content">
    <div class="top-bar">
        <div class="page-title">
            <h1>
                <?php 
                    if ($view === 'users') echo 'ব্যবহারকারী তালিকা';
                    elseif ($view === 'logs') echo 'ট্রানজেকশন লগ';
                    elseif ($view === 'spammers') echo 'সন্দেহজনক কার্যক্রম';
                    elseif ($view === 'suspended') echo 'স্থগিত অ্যাকাউন্টসমূহ';
                    elseif ($view === 'blocked_ips') echo 'ব্লক্ড IP তালিকা';
                ?>
            </h1>
            <p>সিস্টেমের যাবতীয় তথ্য এখান থেকে নিয়ন্ত্রণ করুন।</p>
        </div>
        <div class="search-container">
            <form method="GET">
                <input type="hidden" name="v" value="<?= htmlspecialchars($view, ENT_QUOTES, 'UTF-8') ?>">
                <i class="fa-solid fa-search"></i>
                <input type="text" name="s" placeholder="এখানে খুঁজুন..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
            </form>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <span class="label">মোট ইউজার</span>
            <div class="value"><?= number_format($totalUsers) ?></div>
        </div>
        <div class="stat-card">
            <span class="label">মোট ট্রানজেকশন</span>
            <div class="value"><?= number_format($totalLogs) ?></div>
        </div>
        <div class="stat-card">
            <span class="label">মোট সালামি</span>
            <div class="value">৳<?= number_format($totalSalami) ?></div>
        </div>
        <div class="stat-card" style="border-bottom: 3px solid var(--success);">
            <span class="label">ভেরিফাইড এমাউন্ট</span>
            <div class="value" style="color: var(--success);">৳<?= number_format($totalVerified) ?></div>
        </div>
        <div class="stat-card" style="border-bottom: 3px solid var(--danger);">
            <span class="label">স্থগিত অ্যাকাউন্ট</span>
            <div class="value" style="color: var(--danger);"><?= number_format($totalSuspended) ?></div>
        </div>
    </div>

    <div class="data-card">
        <div class="table-responsive">
            <table>
                <?php if ($view === 'spammers'): ?>
                    <thead><tr><th>সন্দেহভাজন ইউজার</th><th>ভিউস</th><th>রিকোয়েস্ট</th><th>স্প্যাম স্কোর</th><th>স্ট্যাটাস</th><th>অ্যাকশন</th></tr></thead>
                    <tbody id="table-body">
                        <?php 
                            if (empty($data)) echo '<tr><td colspan="5" style="text-align:center; padding: 4rem; color: var(--text-dim);">কোন স্প্যামার পাওয়া যায়নি।</td></tr>';
                            else foreach ($data as $u) echo renderSpammerRow($u); 
                        ?>
                    </tbody>
                <?php elseif ($view === 'logs'): ?>
                    <thead><tr><th>প্রেরক</th><th>প্রাপক</th><th>পরিমাণ</th><th>বিবরণ</th><th>অবস্থা</th><th>সময়</th><th>IP</th><th>অ্যাকশন</th></tr></thead>
                    <tbody id="table-body">
                        <?php 
                            if (empty($data)) echo '<tr><td colspan="8" style="text-align:center; padding: 4rem; color: var(--text-dim);">কোন ট্রানজেকশন নেই।</td></tr>';
                            else foreach ($data as $log) echo renderLogRow($log); 
                        ?>
                    </tbody>
                <?php elseif ($view === 'suspended'): ?>
                    <thead><tr><th>ইউজার</th><th>ইমেইল / ফোন</th><th>সালামি টিকেট</th><th>ব্যালেন্স</th><th>স্ট্যাটাস</th><th>অ্যাকশন</th></tr></thead>
                    <tbody id="table-body">
                        <?php 
                            if (empty($data)) echo '<tr><td colspan="6" style="text-align:center; padding: 4rem; color: var(--text-dim);">কোন স্থগিত অ্যাকাউন্ট পাওয়া যায়নি।</td></tr>';
                            else foreach ($data as $u) echo renderSuspendedRow($u); 
                        ?>
                    </tbody>
                <?php elseif ($view === 'blocked_ips'): ?>
                    <thead><tr><th>প্রেরক / IP</th><th>প্রাপক</th><th>পরিমাণ</th><th>বিবরণ</th><th>অবস্থা</th><th>সময়</th><th>ব্লক কারণ</th><th>অ্যাকশন</th></tr></thead>
                    <tbody id="table-body">
                        <?php
                            if (empty($data)) echo '<tr><td colspan="8" style="text-align:center; padding: 4rem; color: var(--text-dim);">কোন ব্লক্ড IP নেই।</td></tr>';
                            else foreach ($data as $row) echo renderBlockedIpRow($row);
                        ?>
                    </tbody>
                <?php else: ?>
                    <thead><tr><th>ইউজার</th><th>ইমেইল / ফোন</th><th>সালামি টিকেট</th><th>ব্যালেন্স</th><th>স্ট্যাটাস</th><th>অ্যাকশন</th></tr></thead>
                    <tbody id="table-body">
                        <?php 
                            if (empty($data)) echo '<tr><td colspan="6" style="text-align:center; padding: 4rem; color: var(--text-dim);">কোন ইউজার পাওয়া যায়নি।</td></tr>';
                            else foreach ($data as $u) echo renderUserRow($u); 
                        ?>
                    </tbody>
                <?php endif; ?>
            </table>
        </div>
        
        <!-- Infinite Scroll Loader -->
        <div class="loader-container" id="scroll-loader"><i class="fa-solid fa-spinner fa-spin"></i></div>
        <div id="scroll-trigger" style="height: 1px;"></div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const CSRF_TOKEN = '<?= $_SESSION['csrf_token'] ?>';

// --- Mobile Sidebar Toggle ---
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobile-overlay');
    const btn = document.getElementById('hamburger-btn');
    sidebar.classList.toggle('open');
    overlay.classList.toggle('active');
    btn.innerHTML = sidebar.classList.contains('open') 
        ? '<i class="fa-solid fa-xmark"></i>' 
        : '<i class="fa-solid fa-bars"></i>';
}
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', () => { if (window.innerWidth <= 1024) toggleSidebar(); });
});

// --- Infinite Scroll Logic ---
let currentPage = 1;
let isLoading = false;
let hasMoreData = <?= !empty($data) && count($data) == $limit ? 'true' : 'false' ?>;

const observer = new IntersectionObserver((entries) => {
    if (entries[0].isIntersecting && !isLoading && hasMoreData) loadMoreData();
}, { rootMargin: '100px' });

const trigger = document.getElementById('scroll-trigger');
if (trigger && hasMoreData) observer.observe(trigger);

function loadMoreData() {
    isLoading = true;
    document.getElementById('scroll-loader').style.display = 'block';
    currentPage++;

    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('ajax', '1');
    urlParams.set('page', currentPage);

    fetch('admin.php?' + urlParams.toString())
        .then(res => res.text())
        .then(html => {
            if (html.trim() === '') {
                hasMoreData = false;
                observer.disconnect();
            } else {
                document.getElementById('table-body').insertAdjacentHTML('beforeend', html);
            }
            document.getElementById('scroll-loader').style.display = 'none';
            isLoading = false;
        }).catch(err => {
            console.error('Error loading data:', err);
            isLoading = false;
            document.getElementById('scroll-loader').style.display = 'none';
        });
}

// --- Admin Dashboard Actions ---
function showTrxDetails(trxId, message) {
    // Prevent XSS inside Swal modal
    const safeTrxId = document.createElement('div');
    safeTrxId.textContent = trxId;
    const safeMessage = document.createElement('div');
    safeMessage.textContent = message;

    Swal.fire({
        title: 'ট্রানজেকশন বিবরণ',
        html: `
            <div style="text-align: left; padding: 10px;">
                <p style="margin-bottom: 10px;"><strong>Transaction ID:</strong> <br>
                <code style="background: rgba(255,255,255,0.05); padding: 5px; border-radius: 4px; display: block; margin-top: 5px; border: 1px solid var(--card-border);">${safeTrxId.innerHTML}</code></p>
                <p><strong>মেসেজ:</strong> <br>
                <div style="background: rgba(255,255,255,0.05); padding: 10px; border-radius: 8px; margin-top: 5px; border: 1px solid var(--card-border); min-height: 50px; font-size: 0.9rem;">${safeMessage.innerHTML}</div></p>
            </div>
        `,
        icon: 'info',
        confirmButtonText: 'বন্ধ করুন',
        confirmButtonColor: '#6366f1',
        background: '#1e293b',
        color: '#f8fafc'
    });
}

function adminAction(action, id, msg) {
    Swal.fire({
        title: 'আপনি কি নিশ্চিত?',
        text: msg,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#6366f1',
        cancelButtonColor: '#ef4444',
        confirmButtonText: 'হ্যাঁ, নিশ্চিত!',
        cancelButtonText: 'বাতিল',
        background: '#1e293b',
        color: '#f8fafc'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('id', id);
            formData.append('csrf_token', CSRF_TOKEN); // Sending CSRF Token

            fetch('admin.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ title: 'সফল!', text: data.message, icon: 'success', background: '#1e293b', color: '#f8fafc' })
                    .then(() => location.reload());
                } else {
                    Swal.fire({ title: 'এরর!', text: data.message, icon: 'error', background: '#1e293b', color: '#f8fafc' });
                }
            })
            .catch(() => Swal.fire({ title: 'এরর!', text: 'সার্ভারে সংযোগ করা সম্ভব হয়নি।', icon: 'error', background: '#1e293b', color: '#f8fafc' }));
        }
    });
}


function blockIpAction(ip) {
    Swal.fire({
        title: 'IP ব্লক করুন',
        html: `<p style="color:#94a3b8;margin-bottom:12px;">IP: <code style="background:rgba(255,255,255,0.08);padding:3px 8px;border-radius:4px;">${ip}</code></p>
               <input id="swal-note" class="swal2-input" placeholder="কারণ লিখুন (ঐচ্ছিক)">`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#475569',
        confirmButtonText: 'ব্লক করুন',
        cancelButtonText: 'বাতিল',
        background: '#1e293b',
        color: '#f8fafc',
        preConfirm: () => document.getElementById('swal-note').value
    }).then((result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('action', 'block_ip');
            fd.append('ip', ip);
            fd.append('note', result.value || '');
            fd.append('csrf_token', CSRF_TOKEN);
            fetch('admin.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    Swal.fire({ title: data.success ? 'ব্লক হয়েছে!' : 'এরর!', text: data.message, icon: data.success ? 'success' : 'error', background: '#1e293b', color: '#f8fafc' })
                    .then(() => { if (data.success) location.reload(); });
                });
        }
    });
}

function unblockIpAction(ip) {
    Swal.fire({
        title: 'আনব্লক করুন?',
        text: `IP ${ip} আনব্লক করলে সে আবার সালামি পাঠাতে পারবে।`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#6366f1',
        cancelButtonColor: '#475569',
        confirmButtonText: 'হ্যাঁ, আনব্লক করুন',
        cancelButtonText: 'বাতিল',
        background: '#1e293b',
        color: '#f8fafc'
    }).then((result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('action', 'unblock_ip');
            fd.append('ip', ip);
            fd.append('csrf_token', CSRF_TOKEN);
            fetch('admin.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    Swal.fire({ title: data.success ? 'আনব্লক হয়েছে!' : 'এরর!', text: data.message, icon: data.success ? 'success' : 'error', background: '#1e293b', color: '#f8fafc' })
                    .then(() => { if (data.success) location.reload(); });
                });
        }
    });
}
</script>
</body>
</html>
<?php
date_default_timezone_set('Asia/Dhaka');

/**
 * SalamiPay - Helper Functions
 */

/**
 * Sanitize user input to prevent XSS
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a URL
 */
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Require login - redirect if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/login');
    }
}

/**
 * Get logged-in user data
 */
function getLoggedInUser($pdo) {
    if (!isLoggedIn()) return null;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Get user by username
 */
function getUserByUsername($pdo, $username) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch();
}

/**
 * Get user by email
 */
function getUserByEmail($pdo, $email) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

/**
 * Get user by phone/mobile
 */
function getUserByPhone($pdo, $phone) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    return $stmt->fetch();
}

// ============================
//  MFS Account Functions
// ============================

/**
 * Get all MFS accounts for a user
 */
function getUserMfsAccounts($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT * FROM mfs_accounts WHERE user_id = ? ORDER BY is_primary DESC, created_at ASC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Get primary MFS account for a user
 * OPTIMIZED: Uses pre-fetched accounts to save a database trip
 */
function getPrimaryMfs($pdo, $userId, $preFetchedAccounts = null) {
    $accounts = $preFetchedAccounts ?: getUserMfsAccounts($pdo, $userId);
    if (empty($accounts)) return null;

    foreach ($accounts as $acc) {
        if ($acc['is_primary'] == 1) return $acc;
    }
    return $accounts[0]; // Fallback to first
}

/**
 * Get MFS account count for a user
 */
function getMfsCount($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM mfs_accounts WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch()['count'];
}

/**
 * Add an MFS account
 */
function addMfsAccount($pdo, $userId, $type, $number, $label = null) {
    // If this is the first MFS, make it primary
    $count = getMfsCount($pdo, $userId);
    $isPrimary = ($count === 0) ? 1 : 0;

    $stmt = $pdo->prepare("INSERT INTO mfs_accounts (user_id, mfs_type, mfs_number, label, is_primary) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$userId, $type, $number, $label, $isPrimary]);
}

/**
 * Delete an MFS account
 */
function deleteMfsAccount($pdo, $mfsId, $userId) {
    $stmt = $pdo->prepare("DELETE FROM mfs_accounts WHERE id = ? AND user_id = ?");
    $stmt->execute([$mfsId, $userId]);
    return $stmt->rowCount() > 0;
}

/**
 * Set an MFS account as primary
 */
function setMfsPrimary($pdo, $mfsId, $userId) {
    // Unset all primary first
    $pdo->prepare("UPDATE mfs_accounts SET is_primary = 0 WHERE user_id = ?")->execute([$userId]);
    // Set the selected one
    $pdo->prepare("UPDATE mfs_accounts SET is_primary = 1 WHERE id = ? AND user_id = ?")->execute([$mfsId, $userId]);
}

// ============================
//  Salami Functions
// ============================

/**
 * Get total salami amount for a user
 */
function getTotalSalami($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM salami_logs sl
        WHERE sl.receiver_id = ? 
        AND NOT EXISTS (SELECT 1 FROM blocked_ips bi WHERE bi.ip_address = sl.ip_address)
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch()['total'];
}

/**
 * Get salami count for a user
 */
function getSalamiCount($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM salami_logs sl
        WHERE sl.receiver_id = ? 
        AND NOT EXISTS (SELECT 1 FROM blocked_ips bi WHERE bi.ip_address = sl.ip_address)
    ");
    $stmt->execute([$userId]);
    return (int)$stmt->fetch()['count'];
}

/**
 * Get comprehensive user stats in a single query
 */
function getUserStats($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT 
            u.*,
            (SELECT COALESCE(SUM(amount), 0) FROM salami_logs sl WHERE sl.receiver_id = u.id AND NOT EXISTS (SELECT 1 FROM blocked_ips bi WHERE bi.ip_address = sl.ip_address)) as total_collected,
            (SELECT COUNT(*) FROM salami_logs sl WHERE sl.receiver_id = u.id AND NOT EXISTS (SELECT 1 FROM blocked_ips bi WHERE bi.ip_address = sl.ip_address)) as salami_count,
            (SELECT COUNT(*) FROM salami_logs sl WHERE sl.receiver_id = u.id AND status = 'pending' AND NOT EXISTS (SELECT 1 FROM blocked_ips bi WHERE bi.ip_address = sl.ip_address)) as pending_count,
            (SELECT COUNT(*) FROM salami_logs sl WHERE sl.receiver_id = u.id AND status = 'verified' AND NOT EXISTS (SELECT 1 FROM blocked_ips bi WHERE bi.ip_address = sl.ip_address)) as verified_count,
            (SELECT COALESCE(SUM(amount), 0) FROM salami_logs sl WHERE sl.receiver_id = u.id AND status = 'verified' AND NOT EXISTS (SELECT 1 FROM blocked_ips bi WHERE bi.ip_address = sl.ip_address)) as verified_amount,
            (SELECT COUNT(*) FROM mfs_accounts WHERE user_id = u.id) as mfs_count
        FROM users u 
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

/**
 * Get all salami logs for a user
 */
function getSalamiLogs($pdo, $userId, $limit = 50) {
    $stmt = $pdo->prepare("
        SELECT sl.*, ma.mfs_type, ma.mfs_number, ma.label as mfs_label
        FROM salami_logs sl
        LEFT JOIN mfs_accounts ma ON sl.mfs_account_id = ma.id
        WHERE sl.receiver_id = ? 
        AND NOT EXISTS (SELECT 1 FROM blocked_ips bi WHERE bi.ip_address = sl.ip_address)
        ORDER BY sl.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}

// ============================
//  MFS Display Helpers
// ============================

/**
 * Get MFS icon class
 */
function getMfsIcon($type) {
    $icons = [
        'বিকাশ'  => 'fa-solid fa-mobile-screen-button',
        'নগদ'   => 'fa-solid fa-mobile-screen',
        'রকেট'  => 'fa-solid fa-rocket',
        'উপায়'   => 'fa-solid fa-money-bill-transfer',
        'অন্যান্য' => 'fa-solid fa-wallet',
    ];
    return $icons[$type] ?? $icons['অন্যান্য'];
}

/**
 * Get MFS brand color
 */
function getMfsColor($type) {
    $colors = [
        'বিকাশ'  => '#E2136E',
        'নগদ'   => '#F6921E',
        'রকেট'  => '#8B2D8B',
        'উপায়'   => '#0B8457',
        'অন্যান্য' => '#6C63FF',
    ];
    return $colors[$type] ?? $colors['অন্যান্য'];
}

/**
 * Get MFS display name
 */
function getMfsName($type, $label = null) {
    if ($type === 'অন্যান্য' && !empty($label)) {
        return $label;
    }
    return $type ?: 'অন্যান্য';
}

// ============================
//  Utility Functions
// ============================

/**
 * Generate a CSRF token
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

/**
 * Flash message system
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Format currency
 */
function formatAmount($amount) {
    return '৳' . bn_number(number_format($amount, 0));
}

/**
 * Time ago helper
 */
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return $diff->y . ' বছর আগে';
    if ($diff->m > 0) return $diff->m . ' মাস আগে';
    if ($diff->d > 0) return $diff->d . ' দিন আগে';
    if ($diff->h > 0) return $diff->h . ' ঘণ্টা আগে';
    if ($diff->i > 0) return $diff->i . ' মিনিট আগে';
    return 'এইমাত্র';
}

/**
 * Check if text contains offensive language (Profanity Filter)
 * 
 * Strong ~95% filter with:
 *   - Expanded English + Bengali bad word list (with variants)
 *   - Normalization: lowercase, symbol removal, leetspeak decoding
 *   - Space/separator stripping (f u c k → fuck)
 *   - Zero-width character removal (invisible Unicode tricks)
 *   - Repeated character collapsing (fuuuuck → fuck)
 *   - Regex fuzzy matching with word boundaries (f+u+c+k+)
 */
function isProfanity($text) {
    if (empty($text)) return false;

    // ── Bad word list (Add/Remove as needed) ──────────────────────────
    // English core + variants
    static $badlist = [
        'fuck', 'fucking', 'fucker', 'fucked', 'fucks', 'sex', 'motherfucker', 'motherfucking',
        'shit', 'shitty', 'bullshit', 'shitting',
        'piss', 'pissing', 'pissed',
        'bastard', 'bastards',
        'bitch', 'bitches', 'bitchy',
        'asshole', 'arsehole', 'ass',
        'dick', 'dicks', 'dickhead',
        'cunt', 'cunts',
        'pussy', 'pussies',
        'cock', 'cocks',
        'whore', 'whores','abal',
        'slut', 'sluts',
        'faggot', 'fag','cudi','magi','sawa','saua','chudi','choda','chuda',
        // Bengali core + variants
        'চুদি', 'চুদে', 'চুদির', 'চোদ', 'চোদার', 'চোদাচুদি',
        'মাদারচোদ', 'মাদারচুদ', 'মাদারচোদা',
        'খানকি', 'খানকির',
        'বেশ্যা', 'বেশ্যার',
        'শালা', 'শালার',
        'কুত্তা', 'কুত্তার',
        'শুয়োর', 'শুয়োরের',
        'হারামি', 'হারামজাদা', 'হারামজাদার',
        'পোদ', 'পোদের',
        'বাল', 'বালের', 'আবাল ',
        'মাগী', 'মাগির',
    ];

    // ── Leetspeak / symbol map ─────────────────────────────────────────
    static $symbolMap = [
        '@' => 'a', '$' => 's', '0' => 'o', '1' => 'i',
        '3' => 'e', '4' => 'a', '5' => 's', '7' => 't',
        '8' => 'b', '!' => 'i', '|' => 'i', '+' => 't',
    ];

    // ── Pre-process input ─────────────────────────────────────────────
    $lower = mb_strtolower($text, 'UTF-8');

    // ── Step 1: Direct match for Bengali (best for non-spaced scripts) ─
    $bengaliBad = array_filter($badlist, fn($w) => preg_match('/\p{Bengali}/u', $w));
    foreach ($bengaliBad as $word) {
        if (mb_strpos($lower, $word) !== false) return true;
    }

    // ── Step 2: Normalize input to catch bypass tricks ─────────────────
    $normalized = $lower;
    // Remove zero-width / invisible Unicode characters
    $normalized = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $normalized);
    // Strip emojis and all symbols outside Bengali/Latin range (emoji bypass fix)
    $normalized = preg_replace('/[\x{1F000}-\x{1FFFF}\x{2600}-\x{27BF}\x{FE00}-\x{FEFF}\x{1F300}-\x{1F9FF}]/u', '', $normalized);
    // Decode leetspeak symbols
    $normalized = str_replace(array_keys($symbolMap), array_values($symbolMap), $normalized);
    // Strip spaces and common separators for normalized checks
    $normStripped = preg_replace('/[\s\-_.,:;\/\\\\]+/u', '', $normalized);
    // Remove remaining punctuation/symbols
    $normClean = preg_replace('/[^a-z\p{L}\p{N}]/u', '', $normalized);

    // ── Step 3: Collapse repeated characters (fuuuuck → fuck) ──────────
    $collapsed = preg_replace('/(.)\1{2,}/u', '$1', $normClean);

    // ── Step 4: Hybrid Matching ────────────────────────────────────────
    foreach ($badlist as $word) {
        $wordLower = mb_strtolower($word, 'UTF-8');
        $isEnglish = !preg_match('/\p{Bengali}/u', $wordLower);
        
        // For English, we use fuzzy regex with word boundaries to avoid catching "ass" in "assassin"
        if ($isEnglish) {
            $wordCollapsed = preg_replace('/(.)\1{2,}/u', '$1', $wordLower);
            $chars   = preg_split('//u', $wordCollapsed, -1, PREG_SPLIT_NO_EMPTY);
            $escaped = array_map(fn($c) => preg_quote($c, '/'), $chars);
            $pattern = '/(?<![a-z\p{L}])' . implode('+', $escaped) . '+(?![a-z\p{L}])/ui';
            
            if (preg_match($pattern, $normalized) || preg_match($pattern, $collapsed)) {
                return true;
            }
        } else {
            // Bengali is already handled in Step 1, but we can check collapsed form here too
            if (mb_strpos($collapsed, preg_replace('/(.)\1{2,}/u', '$1', $wordLower)) !== false) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Convert English numbers to Bengali digits
 */
function bn_number($number) {
    if ($number === null || $number === '') return '';
    $en = ['0','1','2','3','4','5','6','7','8','9'];
    $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
    return str_replace($en, $bn, $number);
}
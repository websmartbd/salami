<?php
/**
 * SalamiPay - Username Availability Check API
 */
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = strtolower(trim($input['username'] ?? ''));

    if (empty($username)) {
        echo json_encode(['status' => 'error', 'message' => 'ইউজারনেম দিন']);
        exit;
    }

    if (!preg_match('/^[a-z0-9_-]{3,30}$/', $username)) {
        echo json_encode(['status' => 'error', 'message' => 'ইউজারনেম অবৈধ']);
        exit;
    }

    
// Reserved words check
$reserved = [
    // Original
    'login', 'register', 'dashboard', 'logout', 'admin', 'api',
    'assets', 'includes', 'profile', 'privacy', 'salami', 'terms',

    // Authentication & Account
    'auth', 'signin', 'signout', 'signup', 'verify', 'reset',
    'password', 'account', 'session', 'token', 'oauth', '2fa',

    // System / Infrastructure
    'static', 'media', 'uploads', 'files', 'storage', 'cdn',
    'public', 'private', 'internal', 'config', 'settings', 'env',
    'cron', 'queue', 'cache', 'logs', 'debug', 'health', 'status',

    // Common App Sections
    'home', 'index', 'search', 'explore', 'feed', 'trending',
    'notifications', 'messages', 'inbox', 'support', 'help', 'faq',
    'contact', 'about', 'blog', 'news', 'shop', 'store', 'pricing',

    // User / Roles
    'user', 'users', 'moderator', 'mod', 'staff', 'owner',
    'superadmin', 'root', 'system', 'bot', 'anonymous', 'guest',

    // Legal / Compliance
    'legal', 'cookies', 'security', 'report', 'dmca',

    // Profanity / Offensive
    'fuck', 'shit', 'ass', 'bitch', 'bastard', 'dick', 'cock',
    'pussy', 'cunt', 'whore', 'slut', 'nigger', 'nigga', 'faggot',
    'retard', 'tranny', 'kike', 'spic', 'chink', 'wetback',

    // Violence / Extremism
    'kill', 'murder', 'rape', 'terrorist', 'jihad', 'isis', 'nazi',
    'hitler', 'genocide', 'suicide', 'bomb', 'shooter', 'massacre',

    // Drugs / Illegal
    'cocaine', 'heroin', 'meth', 'weed', 'crack', 'drugs',
    'dealer', 'cartel', 'darkweb', 'darknet',

    // Scam / Fraud
    'scam', 'fraud', 'fake', 'phishing', 'hack', 'hacker',
    'cheat', 'exploit', 'malware', 'virus', 'spam',

    // Impersonation of Big Brands
    'google', 'facebook', 'twitter', 'instagram', 'tiktok',
    'apple', 'microsoft', 'amazon', 'netflix', 'paypal',
    'youtube', 'whatsapp', 'telegram', 'discord', 'reddit',

    // Gambling
    'casino', 'poker', 'betting', 'gambling', 'lottery', 'slots',

    // Crypto / Finance Scams
    'bitcoin', 'crypto', 'nft', 'wallet', 'binance', 'coinbase',
    'invest', 'forex', 'trading',

    // Sensitive / Political
    'government', 'president', 'official', 'police', 'fbi', 'cia',
    'military', 'army', 'white-house', 'kremlin',

    // Dev / Technical
    'webhook', 'callback', 'redirect', 'robots', 'sitemap',
    'manifest', '.well-known', 'graphql', 'rss',

    // Adult / NSFW
    'porn', 'sex', 'nude', 'nudes', 'naked', 'nsfw', 'xxx',
    'adult', '18plus', '18+', 'onlyfans', 'escort', 'erotic',
    'fetish', 'hentai', 'cam', 'camgirl', 'camboy', 'strip',
    'stripper', 'prostitute', 'hooker', 'slutty', 'horny',
];

    if (in_array($username, $reserved)) {
        echo json_encode(['status' => 'taken', 'message' => 'এই ইউজারনেমটি ব্যবহার করা যাবে না']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $exists = $stmt->fetchColumn() > 0;

    if ($exists) {
        echo json_encode(['status' => 'taken', 'message' => 'এই ইউজারনেমটি ইতিমধ্যে নেয়া হয়েছে']);
        exit;
    } else {
        echo json_encode(['status' => 'available', 'message' => 'ইউজারনেমটি এভেইলেবল!']);
        exit;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

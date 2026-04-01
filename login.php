<?php
/**
 * SalamiPay - Login Page (Redesigned)
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) redirect('/dashboard');

$errors = [];

// Brute-force protection
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['login_last_attempt'] = 0;
}

$lockoutTime   = 300; // 5 minutes
$maxAttempts   = 5;
$isLocked      = ($_SESSION['login_attempts'] >= $maxAttempts) &&
                 (time() - $_SESSION['login_last_attempt'] < $lockoutTime);

if ($isLocked) {
    $remaining = $lockoutTime - (time() - $_SESSION['login_last_attempt']);
    $errors[] = 'অনেক বেশি ব্যর্থ প্রচেষ্টা। ' . ceil($remaining / 60) . ' মিনিট পরে আবার চেষ্টা করুন।';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isLocked) {
    $identifier = strtolower(trim($_POST['username'] ?? ''));
    $password   = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $errors[] = 'তথ্য ও পাসওয়ার্ড দিন।';
    } else {
        $user = getUserByUsername($pdo, $identifier);
        if (!$user) $user = getUserByEmail($pdo, $identifier);
        if (!$user) $user = getUserByPhone($pdo, $identifier);

        if ($user && password_verify($password, $user['password'])) {
            // Reset attempt counter and regenerate session ID (prevents session fixation)
            $_SESSION['login_attempts'] = 0;
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            setFlash('success', 'সফলভাবে লগইন হয়েছে! 🎉');
            redirect('/dashboard');
        } else {
            $_SESSION['login_attempts']++;
            $_SESSION['login_last_attempt'] = time();
            $remaining_attempts = $maxAttempts - $_SESSION['login_attempts'];
            $errors[] = 'ভুল তথ্য বা পাসওয়ার্ড।' . ($remaining_attempts > 0 ? ' আর ' . $remaining_attempts . ' বার সুযোগ আছে।' : '');
        }
    }
}

$pageTitle = 'লগইন';
?>
<!DOCTYPE html>
<html lang="bn" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>লগইন | <?= SITE_NAME ?></title
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
    <meta name="description" content="সালামির পাতা অ্যাকাউন্টে লগইন করুন এবং আপনার পার্সোনাল সালামির পাতা পরিচালনা করুন। ডিজিটাল সালামি ট্র্যাক করুন সহজেই।">
    <script data-host="https://analytics.hs.vc" data-dnt="false" src="https://analytics.hs.vc/js/script.js" id="ZwSg9rf6GA" async defer></script>
    <!-- Social Meta -->
    <meta property="og:title" content="লগইন | <?= SITE_NAME ?>">
    <meta property="og:description" content="সালামির পাতা অ্যাকাউন্টে লগইন করুন এবং আপনার পার্সোনাল সালামির পাতা পরিচালনা করুন। ডিজিটাল সালামি ট্র্যাক করুন সহজেই।">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary">

    <link rel="icon" type="image/png" href="<?= SITE_FAVICON ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:          #0b0f1e;
            --surface:     #111627;
            --surface-2:   #161d30;
            --border:      rgba(255,255,255,0.07);
            --border-glow: rgba(91,106,240,0.3);

            --p:           #5b6af0;
            --p-light:     #818cf8;
            --p-glow:      rgba(91,106,240,0.35);

            --green:       #22c55e;
            --red:         #ef4444;
            --red-bg:      rgba(239,68,68,0.1);

            --t1: #f1f5f9;
            --t2: #94a3b8;
            --t3: #475569;

            --r-sm: 10px;
            --r-md: 16px;
            --r-lg: 22px;

            --font-en: 'Sora', sans-serif;
            --font-bn: 'Noto Sans Bengali', 'Sora', sans-serif;
        }

        html { height: 100%; }

        body {
            font-family: var(--font-bn);
            background: var(--bg);
            color: var(--t1);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        /* Grid bg */
        body::before {
            content: '';
            position: fixed; inset: 0; z-index: 0;
            background-image:
                linear-gradient(rgba(91,106,240,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(91,106,240,0.04) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: radial-gradient(ellipse 70% 70% at 50% 50%, black 20%, transparent 100%);
            pointer-events: none;
        }

        /* Glow orbs */
        .orb {
            position: fixed; border-radius: 50%; pointer-events: none; z-index: 0;
        }
        .orb-1 {
            width: 420px; height: 420px;
            top: -100px; right: -80px;
            background: radial-gradient(circle, rgba(91,106,240,0.12) 0%, transparent 70%);
            animation: orb1 9s ease-in-out infinite;
        }
        .orb-2 {
            width: 320px; height: 320px;
            bottom: -60px; left: -60px;
            background: radial-gradient(circle, rgba(236,72,153,0.09) 0%, transparent 70%);
            animation: orb2 11s ease-in-out infinite;
        }
        @keyframes orb1 { 0%,100%{transform:translate(0,0);} 50%{transform:translate(-20px,25px);} }
        @keyframes orb2 { 0%,100%{transform:translate(0,0);} 50%{transform:translate(18px,-20px);} }

        /* ── TOPBAR ── */
        .topbar {
            position: relative; z-index: 10;
            padding: 20px 24px;
            display: flex; align-items: center; justify-content: space-between;
            max-width: 1100px; margin: 0 auto; width: 100%;
        }
        .sp-logo {
            display: flex; align-items: center; gap: 9px; text-decoration: none;
        }
        .sp-logo-icon {
            width: 36px; height: 36px; border-radius: 9px;
            background: var(--p); display: flex; align-items: center; justify-content: center;
            font-size: .9rem; color: #fff; box-shadow: 0 4px 12px var(--p-glow);
        }
        .sp-logo-text {
            font-family: var(--font-en); font-weight: 800; font-size: 1rem;
            letter-spacing: -.5px; color: var(--t1);
        }
        .sp-logo-text span { color: var(--p-light); }
        .topbar-link {
            font-size: .82rem; font-weight: 600; color: var(--t3);
            text-decoration: none; transition: color .2s;
        }
        .topbar-link:hover { color: var(--t1); }

        /* ── MAIN AREA ── */
        .auth-wrap {
            flex: 1; display: flex; align-items: center; justify-content: center;
            padding: 16px 16px 40px; position: relative; z-index: 1;
        }

        /* ── CARD ── */
        .auth-card {
            width: 100%; max-width: 420px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(0,0,0,0.4);
            animation: cardIn .5s cubic-bezier(.22,.68,0,1.1) both;
        }
        @keyframes cardIn { from{opacity:0;transform:translateY(20px);} to{opacity:1;transform:translateY(0);} }

        /* Card top accent */
        .card-accent {
            height: 3px;
            background: linear-gradient(90deg, var(--p), #8b5cf6, var(--p-light));
        }

        .card-body { padding: 32px 28px 28px; }

        /* Icon badge */
        .auth-icon-wrap {
            width: 56px; height: 56px; border-radius: 14px;
            background: rgba(91,106,240,0.12); border: 1px solid rgba(91,106,240,0.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; color: var(--p-light);
            margin: 0 auto 20px;
        }

        .auth-title {
            font-family: var(--font-en); font-size: 1.4rem; font-weight: 800;
            letter-spacing: -.5px; color: var(--t1);
            text-align: center; margin-bottom: 6px;
        }
        .auth-sub {
            font-size: .82rem; color: var(--t3); text-align: center;
            margin-bottom: 24px; line-height: 1.6;
        }

        /* Error box */
        .err-box {
            display: flex; align-items: flex-start; gap: 10px;
            background: var(--red-bg); border: 1px solid rgba(239,68,68,0.25);
            border-radius: var(--r-sm); padding: 12px 14px;
            font-size: .82rem; color: #fca5a5; line-height: 1.6;
            margin-bottom: 20px;
        }
        .err-box i { color: var(--red); flex-shrink: 0; margin-top: 2px; }

        /* Form fields */
        .field { margin-bottom: 14px; position: relative; }
        .field-label {
            display: block; font-size: .75rem; font-weight: 700;
            color: var(--t2); margin-bottom: 6px;
        }
        .field-input-wrap { position: relative; }
        .field-icon {
            position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
            color: var(--t3); font-size: .85rem; pointer-events: none;
            transition: color .2s;
        }
        .field-input {
            width: 100%; background: var(--surface-2);
            border: 1.5px solid var(--border);
            border-radius: var(--r-sm); padding: 12px 42px 12px 38px;
            color: var(--t1); font-size: .9rem; font-family: var(--font-bn);
            outline: none; transition: border-color .2s, box-shadow .2s;
            -webkit-appearance: none;
        }
        .field-input:focus {
            border-color: var(--p);
            box-shadow: 0 0 0 3px rgba(91,106,240,0.15);
        }
        .field-input:focus + .field-icon,
        .field-input-wrap:focus-within .field-icon { color: var(--p-light); }
        .field-input::placeholder { color: var(--t3); }

        /* Password toggle */
        .pw-toggle {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: var(--t3);
            font-size: .85rem; cursor: pointer; padding: 4px;
            transition: color .2s; line-height: 1;
        }
        .pw-toggle:hover { color: var(--t2); }

        /* Submit */
        .btn-submit {
            width: 100%; padding: 13px;
            background: var(--p); color: #fff;
            font-size: .9rem; font-weight: 700; border: none;
            border-radius: var(--r-sm); cursor: pointer;
            font-family: var(--font-bn);
            display: flex; align-items: center; justify-content: center; gap: 8px;
            transition: background .2s, transform .15s, box-shadow .2s;
            box-shadow: 0 4px 16px var(--p-glow);
            margin-top: 4px;
        }
        .btn-submit:hover { background: #4a59d9; transform: translateY(-1px); box-shadow: 0 8px 24px var(--p-glow); }
        .btn-submit:active { transform: translateY(0); }

        /* Footer link */
        .auth-footer {
            text-align: center; margin-top: 20px;
            font-size: .82rem; color: var(--t3);
        }
        .auth-footer a { color: var(--p-light); font-weight: 700; text-decoration: none; transition: color .2s; }
        .auth-footer a:hover { color: var(--t1); }

        /* Divider */
        .divider {
            display: flex; align-items: center; gap: 12px;
            margin: 20px 0; color: var(--t3); font-size: .72rem;
        }
        .divider::before, .divider::after {
            content: ''; flex: 1; height: 1px; background: var(--border);
        }

        @media (max-width: 480px) {
            .card-body { padding: 24px 18px 22px; }
            .auth-title { font-size: 1.2rem; }
            .topbar { padding: 16px; }
        }
    </style>
</head>
<body>

<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<div class="topbar">
    <a class="sp-logo" href="<?= BASE_URL ?>/">
        <div class="sp-logo-icon"><i class="fa-solid fa-hand-holding-heart"></i></div>
        <span class="sp-logo-text"><?= SITE_NAME ?></span>
    </a>
    <a href="<?= BASE_URL ?>/register" class="topbar-link">
        অ্যাকাউন্ট নেই? <span style="color:var(--p-light); font-weight:700;">রেজিস্ট্রেশন করুন</span>
    </a>
</div>

<div class="auth-wrap">
    <div class="auth-card">
        <div class="card-accent"></div>
        <div class="card-body">

            <div class="auth-icon-wrap">
                <i class="fa-solid fa-right-to-bracket"></i>
            </div>
            <h1 class="auth-title">স্বাগতম ফিরে আসুন</h1>
            <p class="auth-sub">ইউজারনেম, ইমেইল বা নম্বর দিয়ে লগইন করুন</p>

            <?php if (!empty($errors)): ?>
            <div class="err-box">
                <i class="fa-solid fa-circle-xmark"></i>
                <div><?= implode('<br>', array_map('sanitize', $errors)) ?></div>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="field">
                    <label class="field-label" for="username">ইউজারনেম / ইমেইল / নম্বর</label>
                    <div class="field-input-wrap">
                        <input type="text" class="field-input" id="username" name="username"
                               placeholder="আপনার তথ্য দিন"
                               value="<?= sanitize($identifier ?? '') ?>"
                               required autofocus>
                        <i class="fa-solid fa-user field-icon"></i>
                    </div>
                </div>

                <div class="field">
                    <label class="field-label" for="password">পাসওয়ার্ড</label>
                    <div class="field-input-wrap">
                        <input type="password" class="field-input" id="password" name="password"
                               placeholder="আপনার পাসওয়ার্ড"
                               required autocomplete="current-password">
                        <i class="fa-solid fa-lock field-icon"></i>
                        <button type="button" class="pw-toggle" id="pwToggle" aria-label="পাসওয়ার্ড দেখুন">
                            <i class="fa-solid fa-eye" id="pwToggleIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-right-to-bracket"></i> লগইন করুন
                </button>
            </form>

            <p class="auth-footer">
                অ্যাকাউন্ট নেই? <a href="<?= BASE_URL ?>/register">রেজিস্ট্রেশন করুন</a>
            </p>

        </div>
    </div>
</div>

<script>
(function(){
    const pwInput = document.getElementById('password');
    const pwIcon  = document.getElementById('pwToggleIcon');
    document.getElementById('pwToggle')?.addEventListener('click', () => {
        const show = pwInput.type === 'password';
        pwInput.type = show ? 'text' : 'password';
        pwIcon.className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
    });
})();
</script>

</body>
</html>
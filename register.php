<?php
/**
 * SalamiPay - Registration Page (Redesigned)
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) redirect('/dashboard');

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = strtolower(trim($_POST['email'] ?? ''));
    $phone     = trim($_POST['phone'] ?? '');
    $username  = strtolower(trim($_POST['username'] ?? ''));
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    $old = compact('full_name', 'email', 'phone', 'username');

    if (empty($full_name) || mb_strlen($full_name) > 100) {
        $errors[] = 'পুরো নাম আবশ্যক (সর্বোচ্চ ১০০ অক্ষর)।';
    }

    if (empty($email)) {
        $errors[] = 'ইমেইল আবশ্যক।';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'সঠিক ইমেইল ঠিকানা দিন।';
    } else {
        if (!preg_match('/@(gmail\.com|icloud\.com|me\.com|apple\.com)$/i', $email)) {
            $errors[] = 'শুধুমাত্র Gmail বা Apple ইমেইল ব্যবহার করা যাবে।';
        } elseif (getUserByEmail($pdo, $email)) {
            $errors[] = 'এই ইমেইল ইতিমধ্যে ব্যবহৃত হয়েছে।';
        }
    }

    if (empty($phone)) {
        $errors[] = 'মোবাইল নম্বর আবশ্যক।';
    } elseif (!preg_match('/^(013|014|015|016|017|018|019)[0-9]{8}$/', $phone)) {
        $errors[] = 'সঠিক ১১ ডিজিটের মোবাইল নম্বর দিন (013-019 দিয়ে শুরু)।';
    } else {
        if (getUserByPhone($pdo, $phone)) $errors[] = 'এই নম্বরটি ইতিমধ্যে ব্যবহৃত হয়েছে।';
    }

    if (empty($username)) {
        $errors[] = 'ইউজারনেম আবশ্যক।';
    } elseif (!preg_match('/^[a-z0-9_-]{3,30}$/', $username)) {
        $errors[] = 'ইউজারনেমে শুধু ছোট হাতের ইংরেজি, সংখ্যা, _ বা - (৩-৩০ অক্ষর)।';
    } else {
        $reserved = [
            'login','register','dashboard','logout','admin','api',
            'assets','includes','profile','status','uploads','index',
            'privacy','terms','about','help','support','root','www',
            'mail','smtp','ftp','null','undefined','test',
        ];
        if (in_array($username, $reserved)) {
            $errors[] = 'এই ইউজারনেম ব্যবহার করা যাবে না।';
        } elseif (getUserByUsername($pdo, $username)) {
            $errors[] = 'এই ইউজারনেম ইতিমধ্যে ব্যবহৃত হয়েছে।';
        }
    }

    if (strlen($password) < 6) $errors[] = 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষর হতে হবে।';
    if ($password !== $confirm)  $errors[] = 'পাসওয়ার্ড মিলছে না।';

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, username, password) VALUES (?, ?, ?, ?, ?)");
        try {
            $stmt->execute([$full_name, $email, $phone, $username, $hashed]);
            session_regenerate_id(true); // Prevent session fixation
            $_SESSION['user_id'] = $pdo->lastInsertId();
            setFlash('success', 'অ্যাকাউন্ট তৈরি হয়েছে! এখন আপনার MFS অ্যাকাউন্ট যোগ করুন। 🎉');
            redirect('/dashboard');
        } catch (PDOException $e) {
            $errors[] = 'রেজিস্ট্রেশনে সমস্যা হয়েছে। আবার চেষ্টা করুন।';
        }
    }
}

$pageTitle = 'রেজিস্ট্রেশন';
?>
<!DOCTYPE html>
<html lang="bn" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>রেজিস্ট্রেশন | <?= SITE_NAME ?></title>
    <meta name="description" content="<?= SITE_NAME ?>-তে ফ্রি অ্যাকাউন্ট তৈরি করুন এবং আপনার নিজস্ব সালামি পেজ চালু করুন।">
    <link rel="icon" type="image/png" href="/assets/img/favicon.png">
<script data-host="https://analytics.hs.vc" data-dnt="false" src="https://analytics.hs.vc/js/script.js" id="ZwSg9rf6GA" async defer></script>    
    <!-- Social Meta -->
    <meta property="og:title" content="রেজিস্ট্রেশন | <?= SITE_NAME ?>">
    <meta property="og:description" content="<?= SITE_NAME ?>-তে এখনই যোগ দিন।">
    <meta property="og:type" content="website">

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
            --green-bg:    rgba(34,197,94,0.1);
            --red:         #ef4444;
            --red-bg:      rgba(239,68,68,0.1);
            --amber:       #f59e0b;

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
            display: flex; flex-direction: column;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed; inset: 0; z-index: 0;
            background-image:
                linear-gradient(rgba(91,106,240,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(91,106,240,0.04) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: radial-gradient(ellipse 70% 70% at 50% 40%, black 20%, transparent 100%);
            pointer-events: none;
        }

        .orb {
            position: fixed; border-radius: 50%; pointer-events: none; z-index: 0;
        }
        .orb-1 {
            width: 450px; height: 450px; top: -120px; left: -80px;
            background: radial-gradient(circle, rgba(91,106,240,0.11) 0%, transparent 70%);
            animation: orb1 10s ease-in-out infinite;
        }
        .orb-2 {
            width: 300px; height: 300px; bottom: -60px; right: -40px;
            background: radial-gradient(circle, rgba(236,72,153,0.08) 0%, transparent 70%);
            animation: orb2 12s ease-in-out infinite;
        }
        @keyframes orb1 { 0%,100%{transform:translate(0,0);} 50%{transform:translate(25px,20px);} }
        @keyframes orb2 { 0%,100%{transform:translate(0,0);} 50%{transform:translate(-18px,-22px);} }

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

        /* ── LAYOUT ── */
        .auth-wrap {
            flex: 1; display: flex; align-items: center; justify-content: center;
            padding: 8px 16px 40px; position: relative; z-index: 1;
        }

        /* ── CARD ── */
        .auth-card {
            width: 100%; max-width: 460px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(0,0,0,0.4);
            animation: cardIn .5s cubic-bezier(.22,.68,0,1.1) both;
        }
        @keyframes cardIn { from{opacity:0;transform:translateY(20px);} to{opacity:1;transform:translateY(0);} }

        .card-accent {
            height: 3px;
            background: linear-gradient(90deg, var(--p), #8b5cf6, var(--p-light));
        }

        .card-body { padding: 28px 28px 24px; }

        .auth-icon-wrap {
            width: 52px; height: 52px; border-radius: 13px;
            background: rgba(91,106,240,0.12); border: 1px solid rgba(91,106,240,0.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; color: var(--p-light);
            margin: 0 auto 18px;
        }

        .auth-title {
            font-family: var(--font-en); font-size: 1.35rem; font-weight: 800;
            letter-spacing: -.5px; color: var(--t1);
            text-align: center; margin-bottom: 5px;
        }
        .auth-sub {
            font-size: .8rem; color: var(--t3); text-align: center;
            margin-bottom: 22px; line-height: 1.6;
        }

        /* Error */
        .err-box {
            display: flex; align-items: flex-start; gap: 10px;
            background: var(--red-bg); border: 1px solid rgba(239,68,68,0.25);
            border-radius: var(--r-sm); padding: 12px 14px;
            font-size: .82rem; color: #fca5a5; line-height: 1.6;
            margin-bottom: 18px;
        }
        .err-box i { color: var(--red); flex-shrink: 0; margin-top: 2px; }

        /* Fields */
        .field { margin-bottom: 12px; }
        .field-label {
            display: block; font-size: .73rem; font-weight: 700;
            color: var(--t2); margin-bottom: 5px;
        }
        .field-input-wrap { position: relative; }
        .field-icon {
            position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
            color: var(--t3); font-size: .82rem; pointer-events: none; transition: color .2s;
        }
        .field-input {
            width: 100%; background: var(--surface-2);
            border: 1.5px solid var(--border);
            border-radius: var(--r-sm); padding: 11px 14px 11px 36px;
            color: var(--t1); font-size: .88rem; font-family: var(--font-bn);
            outline: none; transition: border-color .2s, box-shadow .2s;
            -webkit-appearance: none;
        }
        .field-input:focus {
            border-color: var(--p);
            box-shadow: 0 0 0 3px rgba(91,106,240,0.15);
        }
        .field-input-wrap:focus-within .field-icon { color: var(--p-light); }
        .field-input::placeholder { color: var(--t3); }
        .field-input.has-toggle { padding-right: 40px; }

        /* Password toggle */
        .pw-toggle {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: var(--t3);
            font-size: .82rem; cursor: pointer; padding: 4px;
            transition: color .2s; line-height: 1;
        }
        .pw-toggle:hover { color: var(--t2); }

        /* Username preview */
        .url-preview {
            display: flex; align-items: center; gap: 7px;
            padding: 8px 12px; border-radius: var(--r-sm);
            background: rgba(91,106,240,0.07); border: 1px solid rgba(91,106,240,0.15);
            font-size: .75rem; color: var(--t3); margin-top: 6px;
        }
        .url-preview i { color: var(--p-light); font-size: .72rem; flex-shrink: 0; }
        .url-preview strong { color: var(--p-light); font-family: var(--font-en); font-weight: 700; }

        /* Password strength */
        .pw-strength { margin-top: 6px; }
        .pw-strength-bar {
            height: 3px; border-radius: 2px; background: var(--border);
            overflow: hidden; margin-bottom: 4px;
        }
        .pw-strength-fill {
            height: 100%; border-radius: 2px; width: 0%;
            transition: width .3s, background .3s;
        }
        .pw-strength-text { font-size: .7rem; color: var(--t3); }

        /* Two-col row */
        .field-row {
            display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
        }

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
            margin-top: 6px;
        }
        .btn-submit:hover { background: #4a59d9; transform: translateY(-1px); box-shadow: 0 8px 24px var(--p-glow); }
        .btn-submit:active { transform: translateY(0); }

        /* Benefits strip */
        .benefits {
            display: flex; justify-content: center; gap: 16px;
            margin-bottom: 20px; flex-wrap: wrap;
        }
        .benefit-item {
            display: flex; align-items: center; gap: 5px;
            font-size: .72rem; color: var(--t3);
        }
        .benefit-item i { color: var(--green); font-size: .68rem; }

        .auth-footer {
            text-align: center; margin-top: 16px;
            font-size: .82rem; color: var(--t3);
        }
        .auth-footer a { color: var(--p-light); font-weight: 700; text-decoration: none; transition: color .2s; }
        .auth-footer a:hover { color: var(--t1); }

        /* Validation states */
        .field-input.is-valid { border-color: var(--green) !important; box-shadow: 0 0 0 3px rgba(34,197,94,0.15) !important; }
        .field-input.is-invalid { border-color: var(--red) !important; box-shadow: 0 0 0 3px rgba(239,68,68,0.15) !important; }
        .validation-msg { font-size: .72rem; margin-top: 4px; display: none; }
        .validation-msg.error { color: #fca5a5; display: block; }
        .validation-msg.success { color: var(--green); display: block; }

        @media (max-width: 480px) {
            .card-body { padding: 22px 16px 20px; }
            .auth-title { font-size: 1.15rem; }
            .topbar { padding: 16px; }
            .field-row { grid-template-columns: 1fr; gap: 12px; }
            .benefits { gap: 10px; }
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
    <a href="<?= BASE_URL ?>/login" class="topbar-link">
        ইতিমধ্যে অ্যাকাউন্ট আছে? <span style="color:var(--p-light); font-weight:700;">লগইন করুন</span>
    </a>
</div>

<div class="auth-wrap">
    <div class="auth-card">
        <div class="card-accent"></div>
        <div class="card-body">

            <div class="auth-icon-wrap">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <h1 class="auth-title">অ্যাকাউন্ট তৈরি করুন</h1>
            <p class="auth-sub">আপনার সালামি কালেকশন পেজ তৈরি করতে রেজিস্ট্রেশন করুন</p>

            <!-- Benefits -->
            <div class="benefits">
                <div class="benefit-item"><i class="fa-solid fa-check"></i> সম্পূর্ণ বিনামূল্যে</div>
                <div class="benefit-item"><i class="fa-solid fa-check"></i> নিজস্ব লিংক</div>
                <div class="benefit-item"><i class="fa-solid fa-check"></i> সব MFS সাপোর্ট</div>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="err-box">
                <i class="fa-solid fa-circle-xmark"></i>
                <div><?= implode('<br>', array_map('sanitize', $errors)) ?></div>
            </div>
            <?php endif; ?>

            <form method="POST" action="" id="registerForm">

                <div class="field">
                    <label class="field-label" for="full_name">পুরো নাম</label>
                    <div class="field-input-wrap">
                        <input type="text" class="field-input" id="full_name" name="full_name"
                               placeholder="আপনার পুরো নাম"
                               value="<?= sanitize($old['full_name'] ?? '') ?>"
                               required autocomplete="name">
                        <i class="fa-solid fa-user field-icon"></i>
                    </div>
                </div>

                <div class="field">
                    <label class="field-label" for="email">ইমেইল</label>
                    <div class="field-input-wrap">
                        <input type="email" class="field-input" id="email" name="email"
                               placeholder="example@gmail.com"
                               value="<?= sanitize($old['email'] ?? '') ?>"
                               required autocomplete="email">
                        <i class="fa-solid fa-envelope field-icon"></i>
                    </div>
                    <div id="emailMsg" class="validation-msg"></div>
                </div>

                <div class="field">
                    <label class="field-label" for="phone">মোবাইল নম্বর</label>
                    <div class="field-input-wrap">
                        <input type="tel" class="field-input" id="phone" name="phone"
                               placeholder="০১XXXXXXXXX"
                               value="<?= sanitize($old['phone'] ?? '') ?>"
                               required autocomplete="tel">
                        <i class="fa-solid fa-mobile-screen field-icon"></i>
                    </div>
                    <div id="phoneMsg" class="validation-msg"></div>
                </div>

                <div class="field">
                    <label class="field-label" for="username">ইউজারনেম <span style="color:var(--t3);font-weight:400;">(ইংরেজিতে)</span></label>
                    <div class="field-input-wrap">
                        <input type="text" class="field-input" id="username" name="username"
                               placeholder="your_username"
                               value="<?= sanitize($old['username'] ?? '') ?>"
                               pattern="[a-z0-9_-]{3,30}" required autocomplete="username">
                        <i class="fa-solid fa-at field-icon"></i>
                    </div>
                    <div id="userMsg" class="validation-msg"></div>
                    <div class="url-preview">
                        <i class="fa-solid fa-link"></i>
                        <span>আপনার লিংক: <strong id="previewLink">সালামির.পাতা.বাংলা/username</strong></span>
                    </div>
                </div>

                <div class="field-row">
                    <div class="field" style="margin-bottom:0;">
                        <label class="field-label" for="password">পাসওয়ার্ড</label>
                        <div class="field-input-wrap">
                            <input type="password" class="field-input has-toggle" id="password" name="password"
                                   placeholder="কমপক্ষে ৬ অক্ষর"
                                   minlength="6" required autocomplete="new-password">
                            <i class="fa-solid fa-lock field-icon"></i>
                            <button type="button" class="pw-toggle" id="pwToggle1" aria-label="দেখুন">
                                <i class="fa-solid fa-eye" id="pw1Icon"></i>
                            </button>
                        </div>
                        <div class="pw-strength" id="pwStrength" style="display:none;">
                            <div class="pw-strength-bar"><div class="pw-strength-fill" id="pwFill"></div></div>
                            <span class="pw-strength-text" id="pwText"></span>
                        </div>
                    </div>

                    <div class="field" style="margin-bottom:0;">
                        <label class="field-label" for="confirm_password">পাসওয়ার্ড নিশ্চিত</label>
                        <div class="field-input-wrap">
                            <input type="password" class="field-input has-toggle" id="confirm_password" name="confirm_password"
                                   placeholder="পুনরায় লিখুন"
                                   required autocomplete="new-password">
                            <i class="fa-solid fa-lock field-icon"></i>
                            <button type="button" class="pw-toggle" id="pwToggle2" aria-label="দেখুন">
                                <i class="fa-solid fa-eye" id="pw2Icon"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-submit" style="margin-top:18px;">
                    <i class="fa-solid fa-rocket"></i> রেজিস্ট্রেশন করুন
                </button>
            </form>

            <p class="auth-footer">
                ইতিমধ্যে অ্যাকাউন্ট আছে? <a href="<?= BASE_URL ?>/login">লগইন করুন</a>
            </p>
        </div>
    </div>
</div>

<script>
(function(){
    // Live Email Validation
    const emailInput = document.getElementById('email');
    const emailMsg   = document.getElementById('emailMsg');
    emailInput?.addEventListener('input', function() {
        const val = this.value.toLowerCase();
        if (!val) {
            this.className = 'field-input';
            emailMsg.style.display = 'none';
            return;
        }
        const isGmailApple = /@(gmail\.com|icloud\.com|me\.com|apple\.com)$/i.test(val);
        if (isGmailApple) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
            emailMsg.className = 'validation-msg success';
            emailMsg.textContent = '✓ বৈধ ইমেইল ফরম্যাট';
        } else {
            this.classList.remove('is-valid');
            this.classList.add('is-invalid');
            emailMsg.className = 'validation-msg error';
            emailMsg.textContent = '✕ শুধুমাত্র Gmail বা Apple ইমেইল প্রয়োজন';
        }
    });

    // Live Phone Validation
    const phoneInput = document.getElementById('phone');
    const phoneMsg   = document.getElementById('phoneMsg');
    phoneInput?.addEventListener('input', function() {
        let val = this.value.replace(/[^0-9]/g, '');
        if (val.length > 11) val = val.substring(0, 11);
        this.value = val;

        if (!val) {
            this.className = 'field-input';
            phoneMsg.style.display = 'none';
            return;
        }

        const isValidPrefix = /^(013|014|015|016|017|018|019)/.test(val);
        const isFullLength = val.length === 11;

        if (isValidPrefix && isFullLength) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
            phoneMsg.className = 'validation-msg success';
            phoneMsg.textContent = '✓ বৈধ নম্বর';
        } else if (!isValidPrefix && val.length >= 3) {
            this.classList.remove('is-valid');
            this.classList.add('is-invalid');
            phoneMsg.className = 'validation-msg error';
            phoneMsg.textContent = '✕ সঠিক প্রিফিক্স ব্যবহার করুন (013-019)';
        } else if (isFullLength && !isValidPrefix) {
            this.classList.add('is-invalid');
            phoneMsg.textContent = '✕ ভুল নম্বর';
        } else {
            this.className = 'field-input';
            phoneMsg.style.display = 'none';
        }
    });

    // Username live preview + sanitize + Availability Check
    const usernameInput = document.getElementById('username');
    const previewLink   = document.getElementById('previewLink');
    const userMsg       = document.getElementById('userMsg');
    let userTimeout;

    usernameInput?.addEventListener('input', function() {
        const val = this.value.toLowerCase().replace(/[^a-z0-9_-]/g, '');
        this.value = val;
        previewLink.textContent = 'সালামির.পাতা.বাংলা/' + (val || 'username');

        clearTimeout(userTimeout);
        if (val.length < 3) {
            this.className = 'field-input';
            userMsg.style.display = 'none';
            return;
        }

        userTimeout = setTimeout(async () => {
            try {
                userMsg.style.display = 'block';
                userMsg.className = 'validation-msg';
                userMsg.textContent = 'যাচাই করা হচ্ছে...';

                const res = await fetch('includes/check-username.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username: val })
                });

                if (!res.ok) throw new Error('Network response was not ok');

                const data = await res.json();

                if (data.status === 'available') {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                    userMsg.className = 'validation-msg success';
                    userMsg.textContent = '✓ ' + data.message;
                } else {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                    userMsg.className = 'validation-msg error';
                    userMsg.textContent = '✕ ' + data.message;
                }
            } catch (err) {
                console.error('Check failed', err);
                userMsg.className = 'validation-msg error';
                userMsg.textContent = '✕ ইউজারনেম যাচাই করা সম্ভব হচ্ছে না';
            }
        }, 600);
    });

    // Password toggles
    function makePwToggle(btnId, inputId, iconId) {
        const btn   = document.getElementById(btnId);
        const input = document.getElementById(inputId);
        const icon  = document.getElementById(iconId);
        btn?.addEventListener('click', () => {
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            icon.className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
        });
    }
    makePwToggle('pwToggle1', 'password', 'pw1Icon');
    makePwToggle('pwToggle2', 'confirm_password', 'pw2Icon');

    // Password strength
    const pwInput    = document.getElementById('password');
    const pwStrength = document.getElementById('pwStrength');
    const pwFill     = document.getElementById('pwFill');
    const pwText     = document.getElementById('pwText');

    pwInput?.addEventListener('input', function() {
        const val = this.value;
        if (!val) { pwStrength.style.display = 'none'; return; }
        pwStrength.style.display = 'block';

        let score = 0;
        if (val.length >= 6)  score++;
        if (val.length >= 10) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        const levels = [
            { w:'20%',  bg:'var(--red)',   t:'খুব দুর্বল' },
            { w:'40%',  bg:'var(--amber)', t:'দুর্বল' },
            { w:'60%',  bg:'var(--amber)', t:'মোটামুটি' },
            { w:'80%',  bg:'var(--green)', t:'ভালো' },
            { w:'100%', bg:'var(--green)', t:'শক্তিশালী' },
        ];
        const lv = levels[Math.min(score, 4)];
        pwFill.style.width      = lv.w;
        pwFill.style.background = lv.bg;
        pwText.textContent      = lv.t;
        pwText.style.color      = lv.bg;
    });
})();
</script>

</body>
</html>
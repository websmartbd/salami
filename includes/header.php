<!DOCTYPE html>
<html lang="bn" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= isset($pageTitle) ? SITE_NAME . ' | ' . sanitize($pageTitle) : SITE_NAME ?></title>
    <meta name="description" content="<?= isset($pageDescription) ? sanitize($pageDescription) : SITE_NAME . ' - সহজে আপনার সালামি সংগ্রহ করুন। লিংক শেয়ার করুন, সালামি গ্রহণ করুন।' ?>">
    
    <script data-host="https://analytics.hs.vc" data-dnt="false" src="https://analytics.hs.vc/js/script.js" id="ZwSg9rf6GA" async defer></script>
    
    <!-- SEO & Social Media Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= SITE_NAME ?>">
    <meta property="og:title" content="<?= isset($pageTitle) ? sanitize($pageTitle) . ' | ' . SITE_NAME : SITE_NAME ?>">
    <meta property="og:description" content="<?= isset($pageDescription) ? sanitize($pageDescription) : SITE_NAME . ' - সহজে আপনার সালামি সংগ্রহ করুন।' ?>">
    <meta property="og:image" content="<?= BASE_URL ?>/assets/img/og-image.png">
    <meta property="og:url" content="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= isset($pageTitle) ? sanitize($pageTitle) . ' | ' . SITE_NAME : SITE_NAME ?>">
    <meta name="twitter:description" content="<?= isset($pageDescription) ? sanitize($pageDescription) : SITE_NAME . ' - সহজে আপনার সালামি সংগ্রহ করুন।' ?>">
    <meta name="twitter:image" content="<?= BASE_URL ?>/assets/img/og-image.png">

    <link rel="icon" type="image/png" href="<?= SITE_FAVICON ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=Noto+Sans+Bengali:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
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
            --amber:       #f59e0b;
            --red:         #ef4444;
            --pink:        #ec4899;

            --t1: #f1f5f9;
            --t2: #94a3b8;
            --t3: #475569;

            --r-sm: 10px;
            --r-md: 16px;
            --r-lg: 22px;
            --r-xl: 28px;

            --nav-h: 68px;

            --font-en: 'Sora', sans-serif;
            --font-bn: 'Noto Sans Bengali', 'Sora', sans-serif;
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

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; }

        /* ── NAVBAR ── */
        .sp-nav {
            position: fixed; top: 0; left: 0; right: 0;
            height: var(--nav-h); z-index: 900;
            transition: background .3s, border-color .3s, box-shadow .3s;
        }
        .sp-nav.scrolled {
            background: rgba(11,15,30,0.92);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            box-shadow: 0 4px 24px rgba(0,0,0,0.3);
        }
        .sp-nav-inner {
            max-width: 1160px; margin: 0 auto;
            height: 100%; padding: 0 24px;
            display: flex; align-items: center; justify-content: space-between;
        }

        /* Logo */
        .sp-logo {
            display: flex; align-items: center; gap: 10px;
            text-decoration: none;
        }
        .sp-logo-icon {
            width: 38px; height: 38px; border-radius: 10px;
            background: var(--p); display: flex; align-items: center; justify-content: center;
            font-size: 1rem; color: #fff;
            box-shadow: 0 4px 14px var(--p-glow);
        }
        .sp-logo-text {
            font-family: var(--font-en); font-weight: 800; font-size: 1.1rem;
            letter-spacing: -.5px; color: var(--t1);
        }
        .sp-logo-text span { color: var(--p-light); }

        /* Nav links */
        .sp-nav-links {
            display: flex; align-items: center; gap: 4px;
            list-style: none;
        }
        .sp-nav-link {
            padding: 8px 14px; border-radius: var(--r-sm);
            color: var(--t2); font-size: .875rem; font-weight: 500;
            text-decoration: none; transition: color .2s, background .2s;
            display: flex; align-items: center; gap: 6px;
        }
        .sp-nav-link:hover { color: var(--t1); background: rgba(255,255,255,0.06); }
        .sp-nav-link.active { color: var(--p-light); }

        .btn-nav-outline {
            padding: 8px 18px; border-radius: var(--r-sm);
            border: 1px solid var(--border); color: var(--t1);
            font-size: .875rem; font-weight: 600;
            text-decoration: none; transition: all .2s;
            display: flex; align-items: center; gap: 6px;
            font-family: var(--font-bn);
        }
        .btn-nav-outline:hover { border-color: var(--border-glow); background: rgba(255,255,255,0.05); color: var(--t1); }

        .btn-nav-primary {
            padding: 8px 20px; border-radius: var(--r-sm);
            background: var(--p); color: #fff;
            font-size: .875rem; font-weight: 700;
            text-decoration: none; transition: all .2s;
            display: flex; align-items: center; gap: 6px;
            font-family: var(--font-bn);
            box-shadow: 0 4px 14px var(--p-glow);
        }
        .btn-nav-primary:hover { background: #4a59d9; color: #fff; transform: translateY(-1px); }

        /* Hamburger */
        .sp-hamburger {
            display: none; width: 40px; height: 40px;
            background: var(--surface); border: 1px solid var(--border);
            border-radius: var(--r-sm); align-items: center; justify-content: center;
            cursor: pointer; color: var(--t1); font-size: 1rem;
            transition: all .2s; z-index: 1001; position: relative;
        }
        .sp-hamburger:hover { border-color: var(--border-glow); }

        /* Mobile menu */
        .sp-mobile-menu {
            display: none; position: fixed;
            top: var(--nav-h); left: 0; right: 0; bottom: 0;
            background: rgba(11,15,30,0.98); backdrop-filter: blur(20px);
            z-index: 850; flex-direction: column;
            padding: 24px; gap: 8px;
            border-top: 1px solid var(--border);
            overflow-y: auto;
            animation: menuSlide .3s ease both;
        }
        .sp-mobile-menu.open { display: flex; }
        @keyframes menuSlide { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }

        .sp-mobile-link {
            display: flex; align-items: center; gap: 12px;
            padding: 14px 16px; border-radius: var(--r-md);
            color: var(--t2); font-size: 1rem; font-weight: 600;
            text-decoration: none; transition: all .2s;
            border: 1px solid transparent;
        }
        .sp-mobile-link:hover, .sp-mobile-link:active {
            background: var(--surface-2); color: var(--t1);
            border-color: var(--border);
        }
        .sp-mobile-link i { width: 20px; text-align: center; color: var(--p-light); }
        .sp-mobile-divider { height: 1px; background: var(--border); margin: 8px 0; }
        .sp-mobile-cta {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            padding: 14px; border-radius: var(--r-md);
            background: var(--p); color: #fff;
            font-size: .95rem; font-weight: 700;
            text-decoration: none; transition: background .2s;
        }
        .sp-mobile-cta:hover { background: #4a59d9; color: #fff; }

        /* ── FLASH ── */
        .flash-wrap {
            position: fixed; top: calc(var(--nav-h) + 12px); left: 50%;
            transform: translateX(-50%); z-index: 999; width: min(92vw, 440px);
        }
        .flash-alert {
            display: flex; align-items: center; gap: 10px;
            padding: 14px 18px; border-radius: var(--r-md);
            font-size: .875rem; font-weight: 500;
            backdrop-filter: blur(16px);
            animation: slideDown .4s cubic-bezier(.22,.68,0,1.2) both;
        }
        .flash-alert.success { background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.3); color: #86efac; }
        .flash-alert.error   { background: rgba(239,68,68,0.15);  border: 1px solid rgba(239,68,68,0.3);  color: #fca5a5; }
        .flash-alert .close-btn { margin-left: auto; cursor: pointer; opacity: .6; background: none; border: none; color: inherit; font-size: 1rem; }
        .flash-alert .close-btn:hover { opacity: 1; }
        @keyframes slideDown { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }

        @media (max-width: 767px) {
            .sp-nav-links { display: none; }
            .sp-hamburger { display: flex; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="sp-nav" id="spNav">
    <div class="sp-nav-inner">
        <a class="sp-logo" href="<?= BASE_URL ?>/">
            <div class="sp-logo-icon"><i class="fa-solid fa-hand-holding-heart"></i></div>
            <span class="sp-logo-text"><?= SITE_NAME ?></span>
        </a>

        <ul class="sp-nav-links">
            <li><a href="<?= BASE_URL ?>/" class="sp-nav-link"><i class="fa-solid fa-house"></i> হোম</a></li>
            <?php if (isLoggedIn()): ?>
                <li><a href="<?= BASE_URL ?>/dashboard" class="sp-nav-link"><i class="fa-solid fa-chart-pie"></i> ড্যাশবোর্ড</a></li>
                <li><a href="<?= BASE_URL ?>/logout" class="btn-nav-outline" style="margin-left:8px;"><i class="fa-solid fa-right-from-bracket"></i> লগআউট</a></li>
            <?php else: ?>
                <li><a href="<?= BASE_URL ?>/login" class="sp-nav-link"><i class="fa-solid fa-right-to-bracket"></i> লগইন</a></li>
                <li><a href="<?= BASE_URL ?>/register" class="btn-nav-primary" style="margin-left:8px;"><i class="fa-solid fa-user-plus"></i> রেজিস্ট্রেশন</a></li>
            <?php endif; ?>
        </ul>

        <button class="sp-hamburger" id="spHam" aria-label="মেনু">
            <i class="fa-solid fa-bars" id="spHamIcon"></i>
        </button>
    </div>
</nav>

<!-- Mobile menu -->
<div class="sp-mobile-menu" id="spMobileMenu">
    <a href="<?= BASE_URL ?>/" class="sp-mobile-link"><i class="fa-solid fa-house"></i> হোম</a>
    <?php if (isLoggedIn()): ?>
        <a href="<?= BASE_URL ?>/dashboard" class="sp-mobile-link"><i class="fa-solid fa-chart-pie"></i> ড্যাশবোর্ড</a>
        <div class="sp-mobile-divider"></div>
        <a href="<?= BASE_URL ?>/logout" class="sp-mobile-link" style="color:var(--red);"><i class="fa-solid fa-right-from-bracket"></i> লগআউট</a>
    <?php else: ?>
        <a href="<?= BASE_URL ?>/login" class="sp-mobile-link"><i class="fa-solid fa-right-to-bracket"></i> লগইন</a>
        <div class="sp-mobile-divider"></div>
        <a href="<?= BASE_URL ?>/register" class="sp-mobile-cta"><i class="fa-solid fa-user-plus"></i> ফ্রি রেজিস্ট্রেশন করুন</a>
    <?php endif; ?>
</div>

<?php
$flash = getFlash();
if ($flash):
?>
<div class="flash-wrap" id="flashWrap">
    <div class="flash-alert <?= $flash['type'] ?>">
        <i class="fa-solid <?= $flash['type']==='success' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
        <span><?= sanitize($flash['message']) ?></span>
        <button class="close-btn" onclick="document.getElementById('flashWrap').remove()"><i class="fa-solid fa-xmark"></i></button>
    </div>
</div>
<script>setTimeout(()=>{const f=document.getElementById('flashWrap');if(f)f.remove();},4500);</script>
<?php endif; ?>

<script>
(function(){
    const nav = document.getElementById('spNav');
    const ham = document.getElementById('spHam');
    const menu = document.getElementById('spMobileMenu');
    const icon = document.getElementById('spHamIcon');
    let menuOpen = false;

    window.addEventListener('scroll', () => {
        nav.classList.toggle('scrolled', window.scrollY > 20);
    }, { passive: true });
    if (window.scrollY > 20) nav.classList.add('scrolled');

    ham?.addEventListener('click', () => {
        menuOpen = !menuOpen;
        menu.classList.toggle('open', menuOpen);
        icon.className = menuOpen ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
        document.body.style.overflow = menuOpen ? 'hidden' : '';
    });

    menu?.querySelectorAll('a').forEach(a => {
        a.addEventListener('click', () => {
            menuOpen = false;
            menu.classList.remove('open');
            icon.className = 'fa-solid fa-bars';
            document.body.style.overflow = '';
        });
    });
})();
</script>
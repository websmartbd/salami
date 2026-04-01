<?php
/**
 * SalamiPay - Landing Page (SEO Optimized for "সালামির পাতা")
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// SEO Optimized Meta Tags
$pageTitle = ' ডিজিটাল সালামি সংগ্রহের সহজ মাধ্যম';
$pageDescription = 'সালামির পাতা-তে আপনার নিজস্ব "সালামির পাতা" তৈরি করুন একদম সহজে। বিকাশ, নগদ বা রকেটে ডিজিটাল সালামি সংগ্রহ করতে আজই ফ্রি অ্যাকাউন্ট খুলুন।';

require_once __DIR__ . '/includes/header.php';
?>

<style>
    /* ── PAGE-LEVEL STYLES ── */
    .page-wrap {
        padding-top: var(--nav-h);
    }

    /* ── HERO ── */
    .hero {
        position: relative;
        overflow: hidden;
        padding: 80px 0 100px;
        min-height: calc(100vh - var(--nav-h));
        display: flex; align-items: center;
    }

    /* Grid background */
    .hero::before {
        content: '';
        position: absolute; inset: 0;
        background-image:
            linear-gradient(rgba(91,106,240,0.05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(91,106,240,0.05) 1px, transparent 1px);
        background-size: 48px 48px;
        mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 20%, transparent 100%);
        pointer-events: none;
    }

    /* Glow orbs */
    .hero-glow-1 {
        position: absolute; top: -100px; left: -100px;
        width: 500px; height: 500px; border-radius: 50%;
        background: radial-gradient(circle, rgba(91,106,240,0.15) 0%, transparent 70%);
        pointer-events: none; animation: orb1 8s ease-in-out infinite;
    }
    .hero-glow-2 {
        position: absolute; bottom: -80px; right: -80px;
        width: 400px; height: 400px; border-radius: 50%;
        background: radial-gradient(circle, rgba(236,72,153,0.1) 0%, transparent 70%);
        pointer-events: none; animation: orb2 10s ease-in-out infinite;
    }
    @keyframes orb1 { 0%,100%{transform:translate(0,0);} 50%{transform:translate(30px,20px);} }
    @keyframes orb2 { 0%,100%{transform:translate(0,0);} 50%{transform:translate(-20px,-30px);} }

    .hero-inner {
        max-width: 1160px; margin: 0 auto; padding: 0 24px;
        display: grid; grid-template-columns: 1fr 1fr;
        gap: 64px; align-items: center;
        position: relative; z-index: 1;
    }

    /* Left content */
    .hero-badge {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 6px 14px; border-radius: 20px;
        background: rgba(91,106,240,0.1); border: 1px solid rgba(91,106,240,0.25);
        font-size: .78rem; font-weight: 700; color: var(--p-light);
        margin-bottom: 24px; font-family: var(--font-bn);
        animation: fadeUp .6s .1s both;
    }
    .hero-badge-dot {
        width: 6px; height: 6px; border-radius: 50%;
        background: var(--green); box-shadow: 0 0 6px var(--green);
        animation: pulse 2s infinite;
    }
    @keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:.4;} }

    .hero-h1 {
        font-family: var(--font-bn);
        font-size: clamp(2.2rem, 4.5vw, 3.4rem);
        font-weight: 800; line-height: 1.15;
        letter-spacing: -1px; color: var(--t1);
        margin-bottom: 20px;
        animation: fadeUp .6s .2s both;
    }
    .hero-h1 .highlight-text {
        background: linear-gradient(135deg, var(--p-light) 0%, var(--pink) 100%);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .hero-sub {
        font-size: 1rem; color: var(--t2); line-height: 1.75;
        margin-bottom: 36px; max-width: 460px;
        animation: fadeUp .6s .3s both;
    }

    .hero-btns {
        display: flex; flex-wrap: wrap; gap: 12px;
        animation: fadeUp .6s .4s both;
    }
    .btn-hero-main {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 14px 28px; border-radius: var(--r-md);
        background: var(--p); color: #fff;
        font-size: .95rem; font-weight: 700;
        text-decoration: none; font-family: var(--font-bn);
        transition: all .25s; box-shadow: 0 8px 24px var(--p-glow);
    }
    .btn-hero-main:hover { background: #4a59d9; color: #fff; transform: translateY(-2px); box-shadow: 0 12px 32px var(--p-glow); }
    .btn-hero-ghost {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 14px 24px; border-radius: var(--r-md);
        background: none; color: var(--t2);
        font-size: .95rem; font-weight: 600;
        text-decoration: none; font-family: var(--font-bn);
        border: 1px solid var(--border); transition: all .25s;
    }
    .btn-hero-ghost:hover { border-color: var(--border-glow); color: var(--t1); background: rgba(255,255,255,0.04); }

    @keyframes fadeUp { from{opacity:0;transform:translateY(20px);} to{opacity:1;transform:translateY(0);} }

    /* Right visual — floating cards */
    .hero-visual {
        display: grid; grid-template-columns: 1fr 1fr; gap: 14px;
        animation: fadeUp .7s .3s both;
    }
    .hcard {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--r-lg); padding: 20px;
        transition: border-color .2s, transform .2s;
        position: relative; overflow: hidden;
    }
    .hcard:hover { border-color: var(--border-glow); transform: translateY(-3px); }
    .hcard::before {
        content: ''; position: absolute; inset: 0;
        background: radial-gradient(ellipse at top left, var(--hcard-glow, transparent) 0%, transparent 70%);
        pointer-events: none;
    }
    .hcard.full { grid-column: 1 / -1; }
    .hcard.offset { margin-top: 20px; }

    .hcard-icon {
        width: 44px; height: 44px; border-radius: var(--r-sm);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; margin-bottom: 14px;
    }
    .hcard-val { font-family: var(--font-en); font-size: 1.7rem; font-weight: 800; letter-spacing: -1px; color: var(--t1); }
    .hcard-lbl { font-size: .78rem; color: var(--t3); margin-top: 4px; font-weight: 500; }

    /* notification card */
    .notif-row { display: flex; align-items: center; gap: 14px; }
    .notif-avatar {
        width: 42px; height: 42px; border-radius: 50%; flex-shrink: 0;
        background: linear-gradient(135deg, var(--p) 0%, #8b5cf6 100%);
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; color: #fff; font-size: .9rem;
    }
    .notif-title { font-weight: 700; font-size: .9rem; color: var(--t1); }
    .notif-sub { font-size: .75rem; color: var(--t3); margin-top: 3px; }
    .notif-amount {
        margin-left: auto; font-family: var(--font-en);
        font-weight: 800; font-size: 1.1rem; color: var(--green);
        flex-shrink: 0;
    }

    /* mfs tags */
    .mfs-tags { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
    .mfs-tag {
        padding: 5px 12px; border-radius: 20px;
        font-size: .78rem; font-weight: 700; font-family: var(--font-bn);
    }

    /* float animation for visual */
    .float-1 { animation: fl1 4s ease-in-out infinite; }
    .float-2 { animation: fl2 5s ease-in-out infinite .5s; }
    .float-3 { animation: fl3 6s ease-in-out infinite 1s; }
    @keyframes fl1 { 0%,100%{transform:translateY(0);} 50%{transform:translateY(-8px);} }
    @keyframes fl2 { 0%,100%{transform:translateY(0);} 50%{transform:translateY(-6px);} }
    @keyframes fl3 { 0%,100%{transform:translateY(0);} 50%{transform:translateY(-10px);} }

    /* ── SECTION COMMONS ── */
    .section { padding: 96px 0; }
    .section-inner { max-width: 1160px; margin: 0 auto; padding: 0 24px; }
    .section-head { text-align: center; margin-bottom: 56px; }

    .s-badge {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 6px 16px; border-radius: 20px;
        background: rgba(91,106,240,0.08); border: 1px solid rgba(91,106,240,0.2);
        font-size: .78rem; font-weight: 700; color: var(--p-light);
        margin-bottom: 16px;
    }
    .s-title {
        font-family: var(--font-bn);
        font-size: clamp(1.7rem, 3vw, 2.4rem);
        font-weight: 800; letter-spacing: -1px; color: var(--t1);
        line-height: 1.2; margin-bottom: 16px;
    }
    .s-title .hl {
        background: linear-gradient(135deg, var(--p-light) 0%, var(--pink) 100%);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .s-sub {
        font-size: .95rem; color: var(--t2); line-height: 1.7;
        max-width: 520px; margin: 0 auto;
    }

    /* ── FEATURES ── */
    .features-grid {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;
    }
    .feat-card {
        background: var(--surface); border: 1px solid var(--border);
        border-radius: var(--r-lg); padding: 28px 24px;
        transition: border-color .25s, transform .25s;
        position: relative; overflow: hidden;
    }
    .feat-card::after {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px;
        background: linear-gradient(90deg, transparent, var(--feat-accent, var(--p)), transparent);
        opacity: 0; transition: opacity .3s;
    }
    .feat-card:hover { border-color: var(--border-glow); transform: translateY(-4px); }
    .feat-card:hover::after { opacity: 1; }

    .feat-icon {
        width: 48px; height: 48px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; margin-bottom: 18px;
    }
    .feat-title {
        font-size: 1rem; font-weight: 700; color: var(--t1); margin-bottom: 8px;
    }
    .feat-text { font-size: .875rem; color: var(--t2); line-height: 1.7; }

    /* ── HOW IT WORKS ── */
    .hiw-bg { background: var(--surface); }
    .steps-grid {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 0;
        position: relative;
    }
    .steps-grid::before {
        content: '';
        position: absolute; top: 36px; left: calc(16.66% + 12px); right: calc(16.66% + 12px);
        height: 1px;
        background: linear-gradient(90deg, var(--p), rgba(91,106,240,0.3), var(--p));
        pointer-events: none;
    }
    .step {
        text-align: center; padding: 0 24px;
        position: relative;
    }
    .step-num {
        width: 72px; height: 72px; border-radius: 50%;
        background: var(--bg); border: 2px solid var(--border-glow);
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 24px;
        font-family: var(--font-en); font-size: 1.4rem; font-weight: 800;
        color: var(--p-light);
        box-shadow: 0 0 20px rgba(91,106,240,0.15);
        position: relative; z-index: 1;
        transition: border-color .2s, box-shadow .2s, transform .2s;
    }
    .step:hover .step-num { border-color: var(--p); box-shadow: 0 0 30px var(--p-glow); transform: scale(1.05); }
    .step-title { font-size: 1rem; font-weight: 700; color: var(--t1); margin-bottom: 10px; }
    .step-text { font-size: .875rem; color: var(--t2); line-height: 1.7; }

    /* ── STATS STRIP ── */
    .stats-strip {
        display: grid; grid-template-columns: repeat(3, 1fr);
        gap: 1px; background: var(--border); overflow: hidden;
        border-radius: var(--r-xl); border: 1px solid var(--border);
    }
    .stat-strip-item {
        background: var(--surface); padding: 36px 24px; text-align: center;
    }
    .stat-strip-val {
        font-family: var(--font-en); font-size: 2.2rem; font-weight: 800;
        letter-spacing: -2px; color: var(--t1);
        background: linear-gradient(135deg, var(--p-light), var(--pink));
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .stat-strip-lbl { font-size: .82rem; color: var(--t3); margin-top: 6px; font-weight: 500; }

    /* ── CTA ── */
    .cta-section {
        padding: 96px 0;
        text-align: center;
        position: relative; overflow: hidden;
    }
    .cta-section::before {
        content: '';
        position: absolute; inset: 0;
        background:
            radial-gradient(ellipse 60% 80% at 50% 100%, rgba(91,106,240,0.12) 0%, transparent 70%);
        pointer-events: none;
    }
    .cta-section .section-inner { position: relative; z-index: 1; }
    .cta-title {
        font-family: var(--font-bn); font-size: clamp(1.8rem,3.5vw,2.6rem);
        font-weight: 800; letter-spacing: -1px; color: var(--t1);
        line-height: 1.2; margin-bottom: 16px;
    }
    .cta-sub { font-size: 1rem; color: var(--t2); line-height: 1.7; max-width: 480px; margin: 0 auto 36px; }

    /* ── RESPONSIVE ── */
    @media (max-width: 991px) {
        .hero-inner { grid-template-columns: 1fr; gap: 48px; }
        .hero { padding: 72px 0 80px; min-height: auto; }
        .hero-visual { max-width: 480px; margin: 0 auto; }
        .features-grid { grid-template-columns: repeat(2, 1fr); }
        .steps-grid::before { display: none; }
        .steps-grid { grid-template-columns: 1fr; gap: 32px; }
        .stats-strip { grid-template-columns: 1fr; gap: 1px; }
    }

    @media (max-width: 767px) {
        .section { padding: 64px 0; }
        .features-grid { grid-template-columns: 1fr; }
        .hero-visual { grid-template-columns: 1fr 1fr; }
        .hero-h1 { letter-spacing: -1px; }
        .s-title { letter-spacing: -1px; }
        .hcard.offset { margin-top: 0; }
        .stat-strip-val { font-size: 1.8rem; }
    }

    @media (max-width: 480px) {
        .hero-btns { flex-direction: column; }
        .btn-hero-main, .btn-hero-ghost { justify-content: center; width: 100%; }
        .hero-visual { grid-template-columns: 1fr; }
        .hcard.offset { margin-top: 0; }
    }
</style>

<div class="page-wrap">

    <section class="hero">
        <div class="hero-glow-1"></div>
        <div class="hero-glow-2"></div>
        <div class="hero-inner">

            <div class="hero-left">
                <div class="hero-badge">
                    <span class="hero-badge-dot"></span>
                    নতুন জেনারেশনের সালামি কালেকশন
                </div>
                <h1 class="hero-h1">
                    আপনার ডিজিটাল <br>
                    <span class="highlight-text">সালামির পাতা</span> <br>
                    এখন অনলাইনে
                </h1>
                <p class="hero-sub">
                    আপনার নিজস্ব লিংক তৈরি করুন, প্রিয়জনদের সাথে শেয়ার করুন এবং সরাসরি আপনার বিকাশ বা নগদ অ্যাকাউন্টে সালামি গ্রহণ করুন।
                </p>
                <div class="hero-btns">
                    <a href="<?= BASE_URL ?>/register" class="btn-hero-main">
                        <i class="fa-solid fa-rocket"></i> অ্যাকাউন্ট তৈরি করুন
                    </a>
                    <a href="#how-it-works" class="btn-hero-ghost">
                        <i class="fa-solid fa-circle-play"></i> বিস্তারিত দেখুন
                    </a>
                </div>
            </div>

            <div class="hero-visual">
                <div class="hcard float-1" style="--hcard-glow: rgba(91,106,240,0.08);">
                    <div class="hcard-icon" style="background:rgba(91,106,240,0.12); color:var(--p-light);">
                        <i class="fa-solid fa-bangladeshi-taka-sign"></i>
                    </div>
                    <div class="hcard-val">৳১২,৫০০</div>
                    <div class="hcard-lbl">মোট সংগ্রহ</div>
                </div>

                <div class="hcard float-2 offset" style="--hcard-glow: rgba(34,197,94,0.07);">
                    <div class="hcard-icon" style="background:rgba(34,197,94,0.12); color:var(--green);">
                        <i class="fa-solid fa-heart"></i>
                    </div>
                    <div class="hcard-val">৪৮</div>
                    <div class="hcard-lbl">সালামি প্রাপ্ত</div>
                </div>

                <div class="hcard full float-3" style="--hcard-glow: rgba(236,72,153,0.05);">
                    <div class="notif-row">
                        <div class="notif-avatar">র</div>
                        <div>
                            <div class="notif-title">নতুন সালামি এসেছে!</div>
                            <div class="notif-sub">রহিম সালামি পাঠিয়ে আপনার "সালামির পাতা" সমৃদ্ধ করেছেন</div>
                        </div>
                        <div class="notif-amount">+৫০০</div>
                    </div>
                    <div class="mfs-tags">
                        <span class="mfs-tag" style="background:rgba(226,19,110,0.12); color:#e2136e;">বিকাশ</span>
                        <span class="mfs-tag" style="background:rgba(239,68,68,0.12); color:#f87171;">নগদ</span>
                        <span class="mfs-tag" style="background:rgba(124,58,237,0.12); color:#a78bfa;">রকেট</span>
                        <span class="mfs-tag" style="background:rgba(34,197,94,0.12); color:var(--green);">উপায়</span>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <div style="max-width:1160px; margin:0 auto; padding:0 24px 80px;">
        <div class="stats-strip">
            <div class="stat-strip-item">
                <div class="stat-strip-val">১০০%</div>
                <div class="stat-strip-lbl">নিরাপদ ও সহজ</div>
            </div>
            <div class="stat-strip-item">
                <div class="stat-strip-val">৪টি</div>
                <div class="stat-strip-lbl">MFS মেথড সাপোর্ট</div>
            </div>
            <div class="stat-strip-item">
                <div class="stat-strip-val">২৪/৭</div>
                <div class="stat-strip-lbl">সার্ভিস এভেইলএবল</div>
            </div>
        </div>
    </div>

    <section class="section" id="features">
        <div class="section-inner">
            <div class="section-head">
                <div class="s-badge"><i class="fa-solid fa-star"></i> আকর্ষণীয় ফিচার</div>
                <h2 class="s-title">কেন আমাদের <span class="hl">সালামির পাতা</span> সেরা?</h2>
                <p class="s-sub">ডিজিটাল যুগে সালামি সংগ্রহ হোক আধুনিক ও ঝামেলামুক্ত।</p>
            </div>
            <div class="features-grid">
                <div class="feat-card" style="--feat-accent: var(--p-light);">
                    <div class="feat-icon" style="background:rgba(91,106,240,0.12); color:var(--p-light);">
                        <i class="fa-solid fa-link"></i>
                    </div>
                    <div class="feat-title">পার্সোনালাইজড লিংক</div>
                    <p class="feat-text">পছন্দমতো ইউজারনেম দিয়ে নিজের "সালামির পাতা" লিংক সেট করুন। যেমন: সালামির.পাতা.বাংলা/atikur</p>
                </div>
                <div class="feat-card" style="--feat-accent: #e2136e;">
                    <div class="feat-icon" style="background:rgba(226,19,110,0.12); color:#e2136e;">
                        <i class="fa-solid fa-mobile-screen-button"></i>
                    </div>
                    <div class="feat-title">মাল্টিপল পেমেন্ট</div>
                    <p class="feat-text">বিকাশ, নগদ, রকেট সহ জনপ্রিয় সব মাধ্যম ব্যবহার করে সালামি পাঠানোর সুবিধা।</p>
                </div>
                <div class="feat-card" style="--feat-accent: var(--green);">
                    <div class="feat-icon" style="background:rgba(34,197,94,0.12); color:var(--green);">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <div class="feat-title">লাইভ ড্যাশবোর্ড</div>
                    <p class="feat-text">কে কত সালামি পাঠিয়েছে এবং মোট কত টাকা হলো তার সব আপডেট লাইভ দেখার সুযোগ।</p>
                </div>
                <div class="feat-card" style="--feat-accent: var(--pink);">
                    <div class="feat-icon" style="background:rgba(236,72,153,0.12); color:var(--pink);">
                        <i class="fa-solid fa-share-nodes"></i>
                    </div>
                    <div class="feat-title">সহজে শেয়ারিং</div>
                    <p class="feat-text">Facebook বা WhatsApp-এ এক ক্লিকে নিজের প্রোফাইল লিংক শেয়ার করে সবাইকে জানিয়ে দিন।</p>
                </div>
                <div class="feat-card" style="--feat-accent: #38bdf8;">
                    <div class="feat-icon" style="background:rgba(56,189,248,0.12); color:#38bdf8;">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div class="feat-title">টপ নচ সিকিউরিটি</div>
                    <p class="feat-text">আপনার প্রোফাইল এবং ডাটা সুরক্ষিত রাখতে আমরা ব্যবহার করি আধুনিক এনক্রিপশন।</p>
                </div>
                <div class="feat-card" style="--feat-accent: var(--amber);">
                    <div class="feat-icon" style="background:rgba(245,158,11,0.12); color:var(--amber);">
                        <i class="fa-solid fa-mobile"></i>
                    </div>
                    <div class="feat-title">সব ডিভাইসে ফ্রেন্ডলি</div>
                    <p class="feat-text">মোবাইল থেকে কম্পিউটার—সবখানেই সালামির পাতা দেখতে এবং ব্যবহার করতে চমৎকার।</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section hiw-bg" id="how-it-works">
        <div class="section-inner">
            <div class="section-head">
                <div class="s-badge"><i class="fa-solid fa-route"></i> সহজ ধাপ</div>
                <h2 class="s-title">কিভাবে <span class="hl">শুরু করবেন</span>?</h2>
                <p class="s-sub">মাত্র এক মিনিটেই আপনার সালামির পাতা সেটআপ করুন</p>
            </div>
            <div class="steps-grid">
                <div class="step">
                    <div class="step-num">১</div>
                    <div class="step-title">রেজিস্ট্রেশন করুন</div>
                    <p class="step-text">ইউজারনেম ও MFS নাম্বার দিয়ে দ্রুত আপনার নিজের একটি অ্যাকাউন্ট খুলুন।</p>
                </div>
                <div class="step">
                    <div class="step-num">২</div>
                    <div class="step-title">লিংক শেয়ার করুন</div>
                    <p class="step-text">প্রোফাইল থেকে লিংক কপি করে আপনার বন্ধুদের এবং পরিচিতদের পাঠিয়ে দিন।</p>
                </div>
                <div class="step">
                    <div class="step-num">৩</div>
                    <div class="step-title">সালামি সংগ্রহ করুন</div>
                    <p class="step-text">সরাসরি আপনার মোবাইল ব্যাংকিংয়ে টাকা বুঝে নিন এবং ড্যাশবোর্ডে ট্র্যাক করুন।</p>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="section-inner">
            <h2 class="cta-title">আজই সাজিয়ে নিন <br> আপনার <span style="background:linear-gradient(135deg,var(--p-light),var(--pink));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">সালামির পাতা</span></h2>
            <p class="cta-sub">দেরি না করে এখনই ফ্রি রেজিস্ট্রেশন করুন এবং আপনার ডিজিটাল সালামি উৎসব শুরু করুন।</p>
            <a href="<?= BASE_URL ?>/register" class="btn-hero-main" style="font-size:1rem; padding:16px 36px;">
                <i class="fa-solid fa-user-plus"></i> এখনই শুরু করুন
            </a>
        </div>
    </section>

</div><?php require_once __DIR__ . '/includes/footer.php'; ?>
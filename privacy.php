<?php
/**
 * SalamiPay - Privacy Policy
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'প্রাইভেসি পলিসি';
$pageDescription = 'সালামির পাতা আপনার ব্যক্তিগত তথ্যের গোপনীয়তা রক্ষায় অঙ্গীকারবদ্ধ। আমাদের প্ল্যাটফর্মে আপনার নাম, মোবাইল নম্বর এবং লেনদেনের তথ্য কীভাবে সুরক্ষিত রাখা হয়, তার বিস্তারিত নীতিমালা এখানে জানুন।';
require_once __DIR__ . '/includes/header.php';
?>

<style>
    .legal-hero {
        padding: 100px 0 60px;
        position: relative;
        overflow: hidden;
        text-align: center;
    }
    .legal-hero::before {
        content: '';
        position: absolute;
        top: -100px;
        left: 50%;
        transform: translateX(-50%);
        width: 600px;
        height: 600px;
        background: radial-gradient(circle, rgba(91,106,240,0.1) 0%, transparent 70%);
        pointer-events: none;
    }
    .legal-title {
        font-size: 3rem;
        font-weight: 800;
        margin-bottom: 20px;
        background: linear-gradient(135deg, #fff 30%, var(--p-light) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .legal-subtitle {
        color: var(--t2);
        font-size: 1.1rem;
        max-width: 600px;
        margin: 0 auto;
    }

    .legal-content {
        padding-bottom: 100px;
    }
    .legal-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--r-xl);
        padding: 50px;
        box-shadow: var(--shadow-card);
        position: relative;
        overflow: hidden;
    }
    .legal-card::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle at top right, rgba(91,106,240,0.05), transparent 70%);
        pointer-events: none;
    }

    .policy-section {
        margin-bottom: 40px;
    }
    .policy-section:last-child {
        margin-bottom: 0;
    }
    .policy-section h2 {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--t1);
        margin-bottom: 18px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .policy-section h2 i {
        color: var(--p-light);
        font-size: 1.2rem;
    }
    .policy-section p {
        color: var(--t2);
        line-height: 1.8;
        font-size: 1.05rem;
        margin-bottom: 15px;
    }
    .policy-list {
        list-style: none;
        padding-left: 0;
    }
    .policy-list li {
        color: var(--t2);
        margin-bottom: 12px;
        display: flex;
        gap: 12px;
        line-height: 1.6;
    }
    .policy-list li::before {
        content: '→';
        color: var(--p-light);
        font-weight: 800;
    }

    .last-updated {
        margin-top: 50px;
        padding-top: 30px;
        border-top: 1px solid var(--border);
        color: var(--t3);
        font-size: 0.9rem;
        text-align: center;
    }

    @media (max-width: 768px) {
        .legal-hero { padding: 80px 0 40px; }
        .legal-title { font-size: 2.2rem; }
        .legal-card { padding: 30px 20px; }
        .policy-section h2 { font-size: 1.3rem; }
    }
</style>

<div class="legal-hero">
    <div class="container">
        <h1 class="legal-title">প্রাইভেসি পলিসি</h1>
        <p class="legal-subtitle">আমরা আপনার ব্যক্তিগত তথ্যের সুরক্ষা এবং গোপনীয়তা বজায় রাখতে প্রতিশ্রুতিবদ্ধ।</p>
    </div>
</div>

<section class="legal-content">
    <div class="container">
        <div class="legal-card">
            <div class="policy-section">
                <h2><i class="fa-solid fa-shield-halved"></i> ভূমিকা</h2>
                <p><?= SITE_NAME ?>-তে আপনার ব্যক্তিগত তথ্যের নিরাপত্তা আমাদের কাছে অত্যন্ত গুরুত্বপূর্ণ। এই প্রাইভেসি পলিসি পেজে আমরা বর্ণনা করেছি যে কীভাবে আমরা আপনার তথ্য সংগ্রহ করি, ব্যবহার করি এবং সুরক্ষিত রাখি।</p>
            </div>

            <div class="policy-section">
                <h2><i class="fa-solid fa-database"></i> আমরা কোন তথ্য সংগ্রহ করি?</h2>
                <p>আমাদের সেবা প্রদান করার জন্য আমরা নিচের তথ্যগুলো সংগ্রহ করে থাকি:</p>
                <ul class="policy-list">
                    <li>আপনার পুরো নাম এবং ইউজারনেম।</li>
                    <li>আপনার ইমেইল ঠিকানা (অ্যাকাউন্ট ভেরিফিকেশন এবং যোগাযোগের জন্য)।</li>
                    <li>আপনার মোবাইল ব্যাংকিং (বিকাশ, নগদ ইত্যাদি) নম্বর এবং বিবরণ।</li>
                    <li>সালামি প্রেরকের দেয়া নাম এবং ট্রানজেকশন আইডি।</li>
                </ul>
            </div>

            <div class="policy-section">
                <h2><i class="fa-solid fa-eye"></i> তথ্যের ব্যবহার</h2>
                <p>সংগৃহীত তথ্যগুলো আমরা নিচের কাজগুলোতে ব্যবহার করি:</p>
                <ul class="policy-list">
                    <li>আপনার ব্যক্তিগত সালামি কালেকশন পেজ তৈরি এবং পরিচালনা করা।</li>
                    <li>সালামি প্রাপ্তির নোটিফিকেশন প্রদান করা।</li>
                    <li>সেবার মান উন্নত করা এবং নিরাপত্তা নিশ্চিত করা।</li>
                    <li>আইনগত বাধ্যবাধকতা পালন করা।</li>
                </ul>
            </div>

            <div class="policy-section">
                <h2><i class="fa-solid fa-lock"></i> তথ্য সুরক্ষা</h2>
                <p>আমরা অত্যাধুনিক এনক্রিপশন এবং নিরাপত্তা ব্যবস্থা ব্যবহার করি যাতে আপনার তথ্য অননুমোদিত প্রবেশ বা ক্ষতির হাত থেকে সুরক্ষিত থাকে। আমাদের সার্ভারগুলো নিয়মিত পর্যবেক্ষণ করা হয়।</p>
            </div>

            <div class="policy-section">
                <h2><i class="fa-solid fa-cookie-bite"></i> কুকি (Cookies)</h2>
                <p>আপনার ব্রাউজিং অভিজ্ঞতা উন্নত করার জন্য আমরা কুকি ব্যবহার করতে পারি। কুকি হলো ছোট ফাইল যা আপনার ডিভাইসে সংরক্ষিত থাকে এবং আমাদের সাইটে আপনার পছন্দ মনে রাখতে সাহায্য করে।</p>
            </div>

            <div class="policy-section">
                <h2><i class="fa-solid fa-user-gear"></i> আপনার অধিকার</h2>
                <p>আপনার ব্যক্তিগত তথ্য যে কোনো সময় আপডেট বা ডিলিট করার অধিকার আপনার রয়েছে। আপনার ড্যাশবোর্ড থেকে আপনি সরাসরি প্রোফাইল এবং পেমেন্ট মেথড এডিট করতে পারেন।</p>
            </div>

            <div class="policy-section">
                <h2><i class="fa-solid fa-paper-plane"></i> যোগাযোগ করুন</h2>
                <p>প্রাইভেসি পলিসি নিয়ে আপনার কোনো প্রশ্ন বা মতামত থাকলে আমাদের সাথে যোগাযোগ করতে পারেন।</p>
            </div>

            <div class="last-updated">
                সর্বশেষ আপডেট: ১৬ মার্চ ২০২৬
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

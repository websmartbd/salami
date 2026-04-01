<?php
/**
 * SalamiPay - Terms and Conditions
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'টার্মস অ্যান্ড কন্ডিশনস';
$pageDescription = 'সালামির পাতা ব্যবহারের সাধারণ শর্তাবলী এবং নিয়মাবলী জানুন। আমাদের প্ল্যাটফর্মটি ব্যবহারের মাধ্যমে আপনি যে সকল নীতিমালার সাথে একমত পোষণ করছেন, তার বিস্তারিত বিবরণ এখানে দেওয়া হলো।';
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
        <h1 class="legal-title">টার্মস অ্যান্ড কন্ডিশনস</h1>
        <p class="legal-subtitle"> <?= SITE_NAME ?> ব্যবহার করার আগে অনুগ্রহ করে আমাদের শর্তাবলী মনোযোগ দিয়ে পড়ুন।</p>
    </div>
</div>

<section class="legal-content">
    <div class="container">
        <div class="legal-card">
            <div class="policy-section">
                <h2><i class="fa-solid fa-file-contract"></i> ব্যবহারকারীর শর্তাবলী</h2>
                <p> <?= SITE_NAME ?> ব্যবহার করার মাধ্যমে আপনি আমাদের শর্তাবলীর সাথে একমত পোষণ করছেন। এই প্ল্যাটফর্মটি শুধুমাত্র বৈধ এবং ব্যক্তিগত প্রয়োজনে ডিজিটাল সালামি সংগ্রহের জন্য তৈরি করা হয়েছে।</p>
            </div>

            <div class="policy-section">
                <h2><i class="fa-solid fa-user-check"></i> অ্যাকাউন্ট এবং নিরাপত্তা</h2>
                <p>রেজিস্ট্রেশনের সময় আপনাকে সঠিক এবং নির্ভুল তথ্য প্রদান করতে হবে।</p>
                <ul class="policy-list">
                    <li>আপনার পাসওয়ার্ড এবং অ্যাকাউন্টের গোপনীয়তা রক্ষার দায়িত্ব সম্পূর্ণ আপনার।</li>
                    <li>একই ব্যক্তি একাধিক অ্যাকাউন্ট তৈরি করতে পারবেন না যা সিস্টেমের জন্য ক্ষতিকর হতে পারে।</li>
                    <li>কোনো প্রকার স্প্যামিং বা অবৈধ কাজে ইউজারনেম ব্যবহার করা যাবে না।</li>
                </ul>
            </div>

            <div class="policy-section">
                <h2><i class="fa-solid fa-money-bill-transfer"></i> লেনদেন এবং দায়বদ্ধতা</h2>
                <p> <?= SITE_NAME ?> সরাসরি কোনো আর্থিক লেনদেনের সাথে জড়িত নয়। আমরা শুধুমাত্র প্রেরক এবং প্রাপকের মধ্যে তথ্যের সমন্বয় করে থাকি।</p>
                <ul class="policy-list">
                    <li>লেনদেন আপনার নিজস্ব মোবাইল ব্যাংকিং অ্যাপ (বিকাশ, নগদ ইত্যাদি) এর মাধ্যমে সম্পন্ন হয়।</li>
                    <li>যেকোনো ভুল লেনদেন বা আর্থিক ক্ষতির জন্য  <?= SITE_NAME ?> দায়ী থাকবে না।</li>
                    <li>প্রাপককে অবশ্যই নিশ্চিত হতে হবে যে তারা সঠিক ট্রানজেকশন ডেটা যাচাই করছেন।</li>
                </ul>
            </div>

            <div class="policy-section">
                <h2><i class="fa-solid fa-ban"></i> নিষিদ্ধ কার্যাবলী</h2>
                <p>নিচের কাজগুলো আমাদের প্ল্যাটফর্মে কঠোরভাবে নিষিদ্ধ:</p>
                <ul class="policy-list">
                    <li>মিথ্যা ট্রানজেকশন আইডি দিয়ে পেমেন্ট কনফার্ম করা।</li>
                    <li>অন্য কারো পরিচয় ব্যবহার করে সালামি দাবি করা।</li>
                    <li>সিস্টেম হ্যাকিং বা ক্ষতিকারক কোড ব্যবহারের চেষ্টা।</li>
                    <li>শ্লীলতাহানি বা আপত্তিকর মেসেজ আদান-প্রদান।</li>
                </ul>
            </div>

            <div class="policy-section">
                <h2><i class="fa-solid fa-triangle-exclamation"></i> অ্যাকাউন্ট বাতিল বা স্থগিত</h2>
                <p>যদি কোনো ব্যবহারকারী আমাদের শর্তাবলী লঙ্ঘন করেন বা কোনো প্রকার প্রতারণামূলক কাজে লিপ্ত হন, তবে  <?= SITE_NAME ?> কর্তৃপক্ষ কোনো প্রকার পূর্ব নোটিশ ছাড়াই সেই অ্যাকাউন্টটি স্থগিত বা চিরতরে ডিলিট করার অধিকার রাখে।</p>
            </div>

            <div class="policy-section">
                <h2><i class="fa-solid fa-rotate"></i> পরিবর্তন এবং পরিমার্জন</h2>
                <p> <?= SITE_NAME ?> কর্তৃপক্ষ যেকোনো সময় এই শর্তাবলী পরিবর্তন বা পরিমার্জন করার অধিকার সংরক্ষণ করে। পরিবর্তনের পর সাইট ব্যবহার অব্যাহত রাখার অর্থ হলো আপনি নতুন শর্তাবলীর সাথে একমত।</p>
            </div>

            <div class="last-updated">
                সর্বশেষ আপডেট: ১৬ মার্চ ২০২৬
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

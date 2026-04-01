<style>
    .footer-container {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 32px;
        align-items: center;
    }

    /* Mobile Responsive Tweak */
    @media (max-width: 600px) {
        .footer-container {
            grid-template-columns: 1fr;
            text-align: center !important;
            gap: 24px;
        }
        .footer-right {
            text-align: center !important;
        }
        .footer-logo-wrapper {
            justify-content: center;
        }
    }
</style>

<footer style="background: var(--surface); border-top: 1px solid var(--border); padding: 48px 0 32px; margin-top: 0;">
    <div style="max-width:1160px; margin:0 auto; padding:0 24px;">
        <div class="footer-container">
            <div>
                <div class="footer-logo-wrapper" style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                    <div style="width:32px; height:32px; border-radius:8px; background:var(--p); display:flex; align-items:center; justify-content:center; font-size:.8rem; color:#fff;">
                        <i class="fa-solid fa-hand-holding-heart"></i>
                    </div>
                    <span style="font-family:var(--font-en); font-weight:800; font-size:1rem; color:var(--t1);">
                        <?= SITE_NAME ?>
                    </span>
                </div>
                <p style="font-size:.82rem; color:var(--t3); margin:0; line-height:1.6;">
                    সহজে সালামি সংগ্রহের আধুনিক প্ল্যাটফর্ম
                </p>
            </div>

            <div class="footer-right" style="text-align:right;">
                <p style="font-size:.78rem; color:var(--t3); margin:0; line-height: 1.8;">
                    &copy; <?= date('Y') ?> <?= SITE_NAME ?> &bull; 
                    <a href="<?= BASE_URL ?>/privacy" style="color:var(--t3); text-decoration:none;">প্রাইভেসি পলিসি</a> &bull; 
                    <a href="<?= BASE_URL ?>/terms" style="color:var(--t3); text-decoration:none;">টার্মস অ্যান্ড কন্ডিশনস</a>
                </p>
                <p style="font-size:.72rem; color:var(--t3); opacity:.6; margin:8px 0 0;">
                    সর্বস্বত্ব সংরক্ষিত
                </p>
            </div>
        </div>
    </div>
</footer>
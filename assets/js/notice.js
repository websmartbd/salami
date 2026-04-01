/**
 * SalamiPay Notice Handler (One-time popup)
 * Move update config here to keep dashboard.php clean
 */
(function() {
    const CURRENT_NOTICE = {
        version: 'v1.1',
        title: 'নতুন আপডেট ও উন্নতি! ✨',
        content: `
            <p style="margin-bottom:12px;">সালামির পাতায় আমরা কিছু নতুন ফিচার ও উন্নতি এনেছি:</p>
            <ul style="list-style:none; padding:0; margin:0;">
                <li style="margin-bottom:8px;"><i class="fa-solid fa-lock" style="color:#6366f1; width:20px;"></i> পাসওয়ার্ড পরিবর্তনের সুবিধা (সেটিংস)।</li>
                <li style="margin-bottom:8px;"><i class="fa-solid fa-eye" style="color:#a855f7; width:20px;"></i> প্রোফাইল ভিউ দেখার অপশন (সাইডবার)।</li>
                <li style="margin-bottom:8px;"><i class="fa-solid fa-shield-halved" style="color:#ef4444; width:20px;"></i> আরও নিরাপদ ব্লকিং সিস্টেম।</li>
                <li style="margin-bottom:8px;"><i class="fa-solid fa-check-double" style="color:#22c55e; width:20px;"></i> পুরনো লগের বাগ ফিক্স ও পারফরম্যান্স উন্নতি।</li>
            </ul>
        `,
        timer: 10000 // Forced wait time in ms
    };

    const storageKey = 'saw_notice_' + CURRENT_NOTICE.version;

    window.addEventListener('load', function() {
        // Return if user already clicked "Confirm" in previous sessions
        if (localStorage.getItem(storageKey) === 'confirmed') return;

        setTimeout(() => {
            if (typeof Swal === 'undefined') return;

            Swal.fire({
                title: `<span style="color:#a5b4fc; font-size:1.2rem;">${CURRENT_NOTICE.title}</span>`,
                html: `
                    <div style="text-align:left; color:#cbd5e1; font-size:0.85rem; line-height:1.6; padding:10px;">
                        ${CURRENT_NOTICE.content}
                    </div>
                `,
                background: '#111627',
                color: '#f1f5f9',
                showConfirmButton: false, // Hidden until timeout
                confirmButtonText: 'ঠিক আছে!',
                confirmButtonColor: '#6366f1',
                padding: '1.2em',
                customClass: {
                    container: 'swal-wide',
                    popup: 'swal-custom-br'
                },
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    // Hide the button for 15 seconds silently
                    setTimeout(() => {
                        Swal.update({
                            showConfirmButton: true
                        });
                    }, CURRENT_NOTICE.timer);
                }
            }).then((result) => {
                // Persistent: Only stop showing if they explicitly clicked the button
                if (result.isConfirmed) {
                    localStorage.setItem(storageKey, 'confirmed');
                }
            });
        }, 1000);
    });
})();

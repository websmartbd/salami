/**
 * SalamiPay - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {

    // --- Navbar scroll effect ---
    const navbar = document.getElementById('mainNav');
    if (navbar) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }

    // --- Particle system for hero ---
    const heroSection = document.querySelector('.hero-section');
    if (heroSection) {
        createParticles(heroSection, 20);
    }

    // --- Copy link functionality ---
    const copyBtns = document.querySelectorAll('.btn-copy');
    copyBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const input = this.closest('.share-link-box').querySelector('input');
            if (input) {
                navigator.clipboard.writeText(input.value).then(() => {
                    const originalHTML = this.innerHTML;
                    this.innerHTML = '<i class="fa-solid fa-check me-1"></i> কপি হয়েছে!';
                    this.style.background = 'linear-gradient(135deg, #22C55E, #16A34A)';
                    setTimeout(() => {
                        this.innerHTML = originalHTML;
                        this.style.background = '';
                    }, 2000);
                }).catch(() => {
                    // Fallback
                    input.select();
                    document.execCommand('copy');
                    Swal.fire({
                        icon: 'success',
                        title: 'লিংক কপি হয়েছে!',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000,
                        background: '#1A1F45',
                        color: '#fff'
                    });
                });
            }
        });
    });

    // --- Salami form submission ---
    const salamiForm = document.getElementById('salamiForm');
    if (salamiForm) {
        salamiForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;

            // Loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> প্রক্রিয়াকরণ...';

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'সালামি পাঠানো হয়েছে! 🎉',
                        html: '<p style="color: rgba(255,255,255,0.7);">আপনার সালামি সফলভাবে রেকর্ড করা হয়েছে।</p>',
                        background: '#1A1F45',
                        color: '#fff',
                        confirmButtonColor: '#6C63FF',
                        confirmButtonText: 'ধন্যবাদ!',
                        backdrop: 'rgba(10, 14, 39, 0.8)'
                    });
                    salamiForm.reset();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'সমস্যা হয়েছে!',
                        text: data.message || 'আবার চেষ্টা করুন।',
                        background: '#1A1F45',
                        color: '#fff',
                        confirmButtonColor: '#EF4444'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'সার্ভার ত্রুটি!',
                    text: 'দয়া করে পরে আবার চেষ্টা করুন।',
                    background: '#1A1F45',
                    color: '#fff',
                    confirmButtonColor: '#EF4444'
                });
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        });
    }

    // --- Page enter animation ---
    document.body.classList.add('page-enter');

    // --- Auto-dismiss flash messages ---
    const flashAlerts = document.querySelectorAll('.flash-container .alert');
    flashAlerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });
});

/**
 * Create floating particles
 */
function createParticles(container, count) {
    for (let i = 0; i < count; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDuration = (Math.random() * 15 + 10) + 's';
        particle.style.animationDelay = (Math.random() * 10) + 's';
        particle.style.width = (Math.random() * 3 + 2) + 'px';
        particle.style.height = particle.style.width;
        container.appendChild(particle);
    }
}

/**
 * Copy text to clipboard (fallback)
 */
function copyToClipboard(text) {
    if (navigator.clipboard) {
        return navigator.clipboard.writeText(text);
    }
    // Fallback
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    return Promise.resolve();
}

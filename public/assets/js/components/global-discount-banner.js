// JS for Global Discount Banner: compute width and position, add dismiss
document.addEventListener('DOMContentLoaded', function () {
    const banner = document.getElementById('globalDiscountBanner');
    if (!banner) return;

    // horizontal margin to keep banner away from viewport edges
    const HORIZONTAL_MARGIN = 12; // px

    const computeAndApply = () => {
        const viewportWidth = window.innerWidth;

        // Compute top offset to avoid overlapping any fixed header/navbars
        let topOffset = 12; // default gap from top
        const headerEl = document.querySelector('.header-mobile, header, .topbar, .navbar, .site-header');
        if (headerEl) {
            try {
                const rect = headerEl.getBoundingClientRect();
                // rect.bottom gives pixel position where header ends in viewport
                topOffset = Math.max(8, rect.bottom + 8);
            } catch (e) {
                // fallback to default
                topOffset = 12;
            }
        }

        // Sidebar width (only if desktop width)
        const sidebar = document.getElementById('sidebar') || document.querySelector('.sidebar');
        const isDesktop = window.matchMedia('(min-width: 992px)').matches;
        const sidebarWidth = (isDesktop && sidebar) ? sidebar.getBoundingClientRect().width : 0;

        // content-wrapper paddings
        const content = document.querySelector('.content-wrapper');
        let paddingLeft = 0, paddingRight = 0;
        if (content) {
            const cs = getComputedStyle(content);
            paddingLeft = parseFloat(cs.paddingLeft) || 0;
            paddingRight = parseFloat(cs.paddingRight) || 0;
        }

        // Scrollbar width
        const scrollbarWidth = Math.max(0, window.innerWidth - document.documentElement.clientWidth) || 0;

        if (!isDesktop) {
            // On mobile/tablet, keep margins on both sides
            const left = HORIZONTAL_MARGIN;
            const width = Math.max(0, viewportWidth - (HORIZONTAL_MARGIN * 2) - scrollbarWidth);
            banner.style.left = left + 'px';
            banner.style.width = width + 'px';
            banner.style.right = 'auto';
            banner.style.top = topOffset + 'px';
        } else {
            // Desktop: align to content area but leave a small gap from sidebar/content edges
            const extraGap = 8; // additional gap to make it visually separated
            const left = sidebarWidth + paddingLeft + extraGap;
            const width = Math.max(0, viewportWidth - sidebarWidth - paddingLeft - paddingRight - scrollbarWidth - (HORIZONTAL_MARGIN + extraGap));
            banner.style.left = left + 'px';
            banner.style.width = width + 'px';
            // keep a small top offset on desktop as well
            banner.style.top = topOffset + 'px';
        }
    };

    // Close button
    const closeBtn = banner.querySelector('.gdb-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            banner.style.transform = 'translateY(-8px)';
            banner.style.opacity = '0';
            setTimeout(() => { try { banner.style.display = 'none'; } catch (e) {} }, 200);
            // store dismiss in sessionStorage to avoid hiding repeatedly during the session
            try { sessionStorage.setItem('gdb_dismissed', '1'); } catch (e) {}
        });
    }

    // If dismissed, don't show
    try {
        if (sessionStorage.getItem('gdb_dismissed') === '1') {
            banner.style.display = 'none';
            return;
        }
    } catch (e) {}

    // Initial compute and on resize
    computeAndApply();
    window.addEventListener('resize', computeAndApply);
});

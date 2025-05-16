document.addEventListener('DOMContentLoaded', function() {
    var bell = document.getElementById('notification-bell');
    var dropdown = document.getElementById('notification-dropdown');
    if (!bell || !dropdown) return;
    bell.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.classList.toggle('open');
    });
    document.addEventListener('click', function(e) {
        if (dropdown.classList.contains('open')) {
            dropdown.classList.remove('open');
        }
    });
    dropdown.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    // Xóa logic xử lý Hoạt động gần đây
    const detailsPopup = document.getElementById('details-popup');
    if (detailsPopup) {
        detailsPopup.remove();
    }
    document.querySelectorAll('.btn-view-details').forEach(button => {
        button.remove();
    });
});
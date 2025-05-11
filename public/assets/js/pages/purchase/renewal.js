document.addEventListener('DOMContentLoaded', function() {
    const packageCards = document.querySelectorAll('.package-card');
    const packageIdInput = document.getElementById('package-id-input');
    const selectedPackageName = document.getElementById('selected-package-name');
    const packagePrice = document.getElementById('package-price');
    const totalPrice = document.getElementById('total-price');
    const submitButton = document.getElementById('submit-button');
    const accountCount = window.RENEWAL_PAGE_DATA ? window.RENEWAL_PAGE_DATA.accountCount : 1;

    packageCards.forEach(card => {
        card.addEventListener('click', function() {
            packageCards.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            const packageId = this.dataset.packageId;
            const price = parseFloat(this.dataset.packagePrice);
            packageIdInput.value = packageId;
            selectedPackageName.textContent = this.querySelector('.package-name').textContent;
            packagePrice.textContent = new Intl.NumberFormat('vi-VN').format(price) + ' đ';
            const total = price * accountCount;
            totalPrice.textContent = new Intl.NumberFormat('vi-VN').format(total) + ' đ';
            submitButton.disabled = false;
        });
    });
});

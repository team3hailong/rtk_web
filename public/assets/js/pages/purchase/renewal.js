document.addEventListener('DOMContentLoaded', function() {
    const packageCards = document.querySelectorAll('.package-card');
    const packageIdInput = document.getElementById('package-id-input');
    const selectedPackageName = document.getElementById('selected-package-name');
    const packagePrice = document.getElementById('package-price');
    const totalPrice = document.getElementById('total-price');
    const submitButton = document.getElementById('submit-button');
    const accountCount = window.RENEWAL_PAGE_DATA ? window.RENEWAL_PAGE_DATA.accountCount : 1;
    const vatPercentFromPHP = window.RENEWAL_PAGE_DATA ? window.RENEWAL_PAGE_DATA.vatPercent : 10; // Default to 10 if not set
    const purchaseTypeRadios = document.querySelectorAll('input[name="purchase_type"]');

    function calculateAndDisplayTotal() {
        const selectedCard = document.querySelector('.package-card.selected');
        if (!selectedCard) return;

        const price = parseFloat(selectedCard.dataset.packagePrice);
        const purchaseType = document.querySelector('input[name="purchase_type"]:checked').value;
        const currentVatPercent = (purchaseType === 'company') ? vatPercentFromPHP : 0;

        const baseTotal = price * accountCount;
        const vatAmount = Math.round(baseTotal * currentVatPercent / 100);
        const finalTotal = baseTotal + vatAmount;

        packagePrice.textContent = new Intl.NumberFormat('vi-VN').format(price) + ' đ';
        totalPrice.textContent = new Intl.NumberFormat('vi-VN').format(finalTotal) + ' đ';
        submitButton.disabled = false;
    }

    packageCards.forEach(card => {
        card.addEventListener('click', function() {
            packageCards.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            const packageId = this.dataset.packageId;
            packageIdInput.value = packageId;
            selectedPackageName.textContent = this.querySelector('.package-name').textContent;
            calculateAndDisplayTotal(); // Call the new function
        });
    });

    purchaseTypeRadios.forEach(radio => {
        radio.addEventListener('change', calculateAndDisplayTotal);
    });
});

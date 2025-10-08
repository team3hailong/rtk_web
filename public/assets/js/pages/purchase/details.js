document.addEventListener('DOMContentLoaded', function() {
    const quantityInput = document.getElementById('quantity');
    const basePrice = parseFloat(document.getElementById('base_price').value);
    const totalPriceView = document.getElementById('total-price-view');
    const totalPriceHidden = document.getElementById('total_price_hidden');
    // no PHP here, JS will infer from existence of quantityInput

    function updateTotalPrice() {
        let quantity = 1;
        if (quantityInput) {
            quantity = parseInt(quantityInput.value) || 1;
        }
        const total = basePrice * quantity;
        if (totalPriceView && quantityInput) {
            totalPriceView.textContent = isNaN(parseInt(quantityInput.value)) ? '--' : total.toLocaleString('vi-VN', { style: 'currency', currency: 'VND' });
        }
        totalPriceHidden.value = total;
    }

    updateTotalPrice();
    if (quantityInput) {
        quantityInput.addEventListener('input', updateTotalPrice);
    }

    const form = document.getElementById('details-form');
    const provincesContainer = document.getElementById('provinces-container');
    
    form.addEventListener('submit', function(event) {
        // Kiểm tra xem có chọn ít nhất 1 tỉnh không
        if (provincesContainer) {
            const selectedCheckboxes = provincesContainer.querySelectorAll('input[type="checkbox"]:checked');
            if (selectedCheckboxes.length === 0) {
                alert('Vui lòng chọn ít nhất 1 Tỉnh/Thành phố sử dụng.');
                event.preventDefault();
                provincesContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
        }
        if (quantityInput) {
            const currentQuantity = parseInt(quantityInput.value);
            if (isNaN(currentQuantity) || currentQuantity < 1) {
                alert('Vui lòng nhập số lượng tài khoản hợp lệ (tối thiểu là 1).');
                event.preventDefault();
                quantityInput.focus();
                return;
            }
        }
        updateTotalPrice();
    });
});

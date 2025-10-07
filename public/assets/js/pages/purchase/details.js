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
    const locationSelect = document.getElementById('location_id');
    
    // Cho phép chọn/bỏ chọn nhiều option bằng click thông thường (không cần Ctrl)
    if (locationSelect) {
        locationSelect.addEventListener('mousedown', function(e) {
            e.preventDefault();
            const option = e.target;
            if (option.tagName === 'OPTION') {
                // Toggle selection
                option.selected = !option.selected;
                // Trigger change event
                locationSelect.dispatchEvent(new Event('change'));
            }
        });
        
        // Prevent default behavior of select
        locationSelect.addEventListener('click', function(e) {
            e.preventDefault();
        });
    }
    
    form.addEventListener('submit', function(event) {
        // Kiểm tra xem có chọn ít nhất 1 tỉnh không
        const selectedOptions = Array.from(locationSelect.selectedOptions);
        if (selectedOptions.length === 0) {
            alert('Vui lòng chọn ít nhất 1 Tỉnh/Thành phố sử dụng.');
            event.preventDefault();
            locationSelect.focus();
            return;
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

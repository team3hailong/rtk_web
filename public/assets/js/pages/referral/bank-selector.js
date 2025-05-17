/**
 * File: bank-selector.js
 * Cung cấp dropdown chọn ngân hàng cho form rút tiền
 */

// Danh sách các ngân hàng Việt Nam (nguồn: https://qrcode.io.vn/banklist)
const vietnamBanks = [
    { code: "ABBANK", name: "Ngân hàng TMCP An Bình (ABBANK)" },
    { code: "ACB", name: "Ngân hàng TMCP Á Châu (ACB)" },
    { code: "AGRIBANK", name: "Ngân hàng Nông nghiệp và Phát triển Nông thôn Việt Nam (Agribank)" },
    { code: "BAC A BANK", name: "Ngân hàng TMCP Bắc Á (BAC A BANK)" },
    { code: "BAOVIET Bank", name: "Ngân hàng TMCP Bảo Việt (BAOVIET Bank)" },
    { code: "BIDV", name: "Ngân hàng TMCP Đầu tư và Phát triển Việt Nam (BIDV)" },
    { code: "CBBANK", name: "Ngân hàng Thương mại TNHH MTV Xây dựng Việt Nam (CB)" },
    { code: "CIMB", name: "Ngân hàng TNHH MTV CIMB Việt Nam (CIMB)" },
    { code: "COOP BANK", name: "Ngân hàng Hợp tác xã Việt Nam (Co-opBank)" },
    { code: "DONG A BANK", name: "Ngân hàng TMCP Đông Á (Dong A Bank)" },
    { code: "EXIMBANK", name: "Ngân hàng TMCP Xuất Nhập khẩu Việt Nam (Eximbank)" },
    { code: "GPBANK", name: "Ngân hàng Thương mại TNHH MTV Dầu Khí Toàn Cầu (GPBank)" },
    { code: "HDBANK", name: "Ngân hàng TMCP Phát triển TP. Hồ Chí Minh (HDBank)" },
    { code: "HONGLEONG", name: "Ngân hàng TNHH MTV Hong Leong Việt Nam (HLB)" },
    { code: "HSBC", name: "Ngân hàng TNHH MTV HSBC (Việt Nam) (HSBC)" },
    { code: "IBK - HCM", name: "Ngân hàng Công nghiệp Hàn Quốc - Chi nhánh TP. Hồ Chí Minh (IBK - HCM)" },
    { code: "IBK - HN", name: "Ngân hàng Công nghiệp Hàn Quốc - Chi nhánh Hà Nội (IBK - HN)" },
    { code: "INDOVINA", name: "Ngân hàng TNHH Indovina (IVB)" },
    { code: "KIENLONGBANK", name: "Ngân hàng TMCP Kiên Long (KienLongBank)" },
    { code: "LIENVIETPOSTBANK", name: "Ngân hàng TMCP Bưu điện Liên Việt (LienVietPostBank)" },
    { code: "MBBANK", name: "Ngân hàng TMCP Quân đội (MB)" },
    { code: "MSB", name: "Ngân hàng TMCP Hàng Hải (MSB)" },
    { code: "NAM A BANK", name: "Ngân hàng TMCP Nam Á (Nam A Bank)" },
    { code: "NCB", name: "Ngân hàng TMCP Quốc Dân (NCB)" },
    { code: "OCB", name: "Ngân hàng TMCP Phương Đông (OCB)" },
    { code: "OCEANBANK", name: "Ngân hàng Thương mại TNHH MTV Đại Dương (OceanBank)" },
    { code: "PGBANK", name: "Ngân hàng TMCP Xăng dầu Petrolimex (PGBank)" },
    { code: "PUBLICBANK", name: "Ngân hàng TNHH MTV Public Việt Nam (PBVN)" },
    { code: "PVCOMBANK", name: "Ngân hàng TMCP Đại Chúng Việt Nam (PVcomBank)" },
    { code: "SACOMBANK", name: "Ngân hàng TMCP Sài Gòn Thương Tín (Sacombank)" },
    { code: "SAIGONBANK", name: "Ngân hàng TMCP Sài Gòn Công Thương (SaigonBank)" },
    { code: "SCB", name: "Ngân hàng TMCP Sài Gòn (SCB)" },
    { code: "SEA BANK", name: "Ngân hàng TMCP Đông Nam Á (SeABank)" },
    { code: "SHB", name: "Ngân hàng TMCP Sài Gòn - Hà Nội (SHB)" },
    { code: "SHINHAN", name: "Ngân hàng TNHH MTV Shinhan Việt Nam (ShinhanBank)" },
    { code: "STANDARD CHARTERED", name: "Ngân hàng TNHH MTV Standard Chartered Bank Việt Nam (Standard Chartered)" },
    { code: "TECHCOMBANK", name: "Ngân hàng TMCP Kỹ thương Việt Nam (Techcombank)" },
    { code: "TPBANK", name: "Ngân hàng TMCP Tiên Phong (TPBank)" },
    { code: "UOB", name: "Ngân hàng TNHH MTV United Overseas Bank (Việt Nam) (UOB)" },
    { code: "VIB", name: "Ngân hàng TMCP Quốc tế Việt Nam (VIB)" },
    { code: "VIETABANK", name: "Ngân hàng TMCP Việt Á (VietABank)" },
    { code: "VIETBANK", name: "Ngân hàng TMCP Việt Nam Thương Tín (VietBank)" },
    { code: "VIETCAPITALBANK", name: "Ngân hàng TMCP Bản Việt (Viet Capital Bank)" },
    { code: "VIETCOMBANK", name: "Ngân hàng TMCP Ngoại thương Việt Nam (Vietcombank)" },
    { code: "VIETINBANK", name: "Ngân hàng TMCP Công thương Việt Nam (VietinBank)" },
    { code: "VPBANK", name: "Ngân hàng TMCP Việt Nam Thịnh Vượng (VPBank)" },
    { code: "WOORIBANK", name: "Ngân hàng TNHH MTV Woori Việt Nam (WooriBank)" }
];

/**
 * Tạo và thay thế input text bank_name bằng dropdown select
 */
function initializeBankSelector() {
    // Tìm input field ngân hàng
    const bankNameInput = document.getElementById('bank_name');
    if (!bankNameInput) return;

    // Kiểm tra trạng thái disabled
    const isDisabled = bankNameInput.disabled;
    
    // Tạo select element
    const bankSelect = document.createElement('select');
    bankSelect.id = 'bank_name';
    bankSelect.name = 'bank_name';
    bankSelect.className = 'form-control';
    bankSelect.required = true;
    bankSelect.disabled = isDisabled;
    
    // Thêm option mặc định
    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = 'Chọn ngân hàng...';
    defaultOption.selected = true;
    defaultOption.disabled = true;
    bankSelect.appendChild(defaultOption);
    
    // Thêm các options từ danh sách ngân hàng
    vietnamBanks.forEach(bank => {
        const option = document.createElement('option');
        option.value = bank.name;
        option.textContent = bank.name;
        bankSelect.appendChild(option);
    });
    
    // Thay thế input bằng select
    bankNameInput.parentNode.replaceChild(bankSelect, bankNameInput);
    
    // Tùy chỉnh style cho select
    bankSelect.style.appearance = 'auto'; // Hiển thị arrow dropdown
    
    // Thêm tính năng tìm kiếm với datalist cho các trình duyệt cũ
    if (!('onfocusin' in document)) { // Kiểm tra nếu trình duyệt không hỗ trợ focusin (một số trình duyệt cũ)
        const datalist = document.createElement('datalist');
        datalist.id = 'bank_list';
        vietnamBanks.forEach(bank => {
            const option = document.createElement('option');
            option.value = bank.name;
            datalist.appendChild(option);
        });
        document.body.appendChild(datalist);
        
        // Tạo input để tìm kiếm
        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.placeholder = 'Tìm kiếm ngân hàng...';
        searchInput.className = 'form-control mb-2';
        searchInput.setAttribute('list', 'bank_list');
        searchInput.disabled = isDisabled;
        
        // Thêm sự kiện khi chọn từ datalist
        searchInput.addEventListener('change', function() {
            const selectedValue = this.value;
            const options = bankSelect.options;
            for (let i = 0; i < options.length; i++) {
                if (options[i].value === selectedValue) {
                    bankSelect.selectedIndex = i;
                    break;
                }
            }
        });
        
        // Chèn input tìm kiếm trước select
        bankSelect.parentNode.insertBefore(searchInput, bankSelect);
    }
}

// Thêm vào sự kiện document ready
document.addEventListener('DOMContentLoaded', function() {
    initializeBankSelector();
    
    // Đảm bảo tương thích với mã JavaScript hiện tại
    if (typeof initializeReferralSystem === 'function') {
        // Mã này đã được chạy trong initializeReferralSystem, chúng ta không cần làm gì thêm
    }
});

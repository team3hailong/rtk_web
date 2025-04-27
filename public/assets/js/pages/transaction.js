// transaction.js: Script cho trang lịch sử giao dịch
// Copy toàn bộ phần từ file cũ vào đây để giữ nguyên chức năng tương tác
// --- JavaScript cho tương tác phía client ---

// --- Lấy các element của Modal ---
const modalOverlay = document.getElementById('transaction-details-modal');
const modalTxId = document.getElementById('modal-tx-id');
const modalTxTime = document.getElementById('modal-tx-time');
const modalTxUpdated = document.getElementById('modal-tx-updated');
const modalTxType = document.getElementById('modal-tx-type');
const modalTxAmount = document.getElementById('modal-tx-amount');
const modalTxMethod = document.getElementById('modal-tx-method');
const modalTxStatusBadge = document.getElementById('modal-tx-status-badge');
const modalTxStatusText = document.getElementById('modal-tx-status-text');
const modalTitle = document.getElementById('modal-title');


// --- Hàm hiển thị modal chi tiết giao dịch ---
function showTransactionDetails(txData) {
    // Kiểm tra xem modal và dữ liệu có tồn tại không
    if (!modalOverlay || !txData) return;

    // Điền dữ liệu từ object txData vào các element trong modal
    modalTitle.textContent = `Chi Tiết Giao Dịch #${txData.id}`;
    modalTxId.textContent = txData.id;
    modalTxTime.textContent = txData.time;
    modalTxUpdated.textContent = txData.updated_at;
    modalTxType.textContent = txData.type;
    modalTxAmount.textContent = txData.amount;
    modalTxMethod.textContent = txData.method;
    modalTxStatusText.textContent = txData.status_text;

    // Cập nhật class cho status badge để đổi màu sắc tương ứng
    // Reset các class cũ và thêm class mới từ txData.status_class
    modalTxStatusBadge.className = 'status-badge status-badge-modal ' + txData.status_class;

    // Hiển thị modal bằng cách thêm class 'active'
    modalOverlay.classList.add('active');
}

// --- Hàm đóng modal ---
function closeModal() {
    if (modalOverlay) {
        // Ẩn modal bằng cách xóa class 'active'
        modalOverlay.classList.remove('active');
    }
}

// --- Xử lý đóng modal khi click vào vùng overlay bên ngoài ---
if (modalOverlay) {
    modalOverlay.addEventListener('click', function(event) {
        // Chỉ đóng nếu click trực tiếp vào overlay (event.target), không phải vào content bên trong
        if (event.target === modalOverlay) {
            closeModal();
        }
    });
}

// --- Xử lý đóng modal khi nhấn phím Escape ---
document.addEventListener('keydown', function(event) {
    // Kiểm tra nếu là phím Escape VÀ modal đang hiển thị
    if (event.key === 'Escape' && modalOverlay && modalOverlay.classList.contains('active')) {
        closeModal();
    }
});

// --- Hàm hiển thị lý do thất bại (đơn giản bằng alert) ---
function showFailureReason(transactionId, reason) {
    // Hiển thị thông báo alert với ID giao dịch và lý do
    alert(`Lý do thất bại cho GD #${transactionId}:\n\n${reason || 'Không có thông tin lý do.'}`);
}

// --- Xử lý Filter và Search khi DOM đã tải xong ---
document.addEventListener('DOMContentLoaded', function() {
    // Lấy các element cần thiết cho filter và search
    const filterButtons = document.querySelectorAll('.filter-button');
    const transactionRows = document.querySelectorAll('.transactions-table tbody tr'); // Tất cả các hàng trong tbody
    const searchBox = document.querySelector('.search-box');
    // Tìm hàng chứa thông báo "empty state" để xử lý ẩn/hiện riêng
    const emptyStateRow = document.querySelector('.transactions-table tbody tr .empty-state')?.closest('tr');

    // --- Gắn sự kiện click cho các nút Filter ---
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Bỏ class 'active' khỏi tất cả các nút filter
            filterButtons.forEach(btn => btn.classList.remove('active'));
            // Thêm class 'active' cho nút vừa được click
            this.classList.add('active');
            // Gọi hàm để áp dụng filter và search
            applyFiltersAndSearch();
        });
    });

    // --- Gắn sự kiện input cho ô Search ---
    searchBox.addEventListener('input', function() {
        // Gọi hàm để áp dụng filter và search mỗi khi nội dung ô search thay đổi
        applyFiltersAndSearch();
    });

    // --- Hàm tổng hợp để áp dụng Filter và Search ---
    function applyFiltersAndSearch() {
        // Lấy giá trị filter đang active (từ thuộc tính data-filter)
        const activeFilterButton = document.querySelector('.filter-button.active');
        const filterValue = activeFilterButton ? activeFilterButton.getAttribute('data-filter') : 'all';
        // Lấy giá trị search, chuyển về chữ thường và bỏ khoảng trắng thừa
        const searchTerm = searchBox.value.toLowerCase().trim();
        let hasVisibleRows = false; // Cờ để kiểm tra xem có hàng dữ liệu nào được hiển thị không

        // Duyệt qua từng hàng trong bảng
        transactionRows.forEach(row => {
            // Bỏ qua hàng "empty state" đặc biệt, không filter hàng này
            if (row === emptyStateRow) {
                return;
            }

            // Xác định trạng thái của hàng dựa vào class của status badge
            const statusCell = row.querySelector('td.status .status-badge');
            let rowStatus = 'unknown'; // Mặc định
            if (statusCell) {
                if (statusCell.classList.contains('status-completed')) rowStatus = 'completed';
                else if (statusCell.classList.contains('status-pending')) rowStatus = 'pending';
                else if (statusCell.classList.contains('status-failed')) rowStatus = 'failed';
                else if (statusCell.classList.contains('status-refunded')) rowStatus = 'refunded';
                else if (statusCell.classList.contains('status-cancelled')) rowStatus = 'cancelled';
                // Thêm các else if khác nếu có thêm trạng thái
            }
            // Kiểm tra xem trạng thái hàng có khớp với filter đang chọn không (hoặc filter là 'all')
            const statusMatch = (filterValue === 'all' || rowStatus === filterValue);

            // Kiểm tra xem nội dung hàng có khớp với từ khóa tìm kiếm không
            const idCell = row.cells[0]?.textContent.toLowerCase() || ''; // Lấy text ô ID
            const typeCell = row.cells[2]?.textContent.toLowerCase() || ''; // Lấy text ô Loại GD
            // Khớp nếu search trống HOẶC ID chứa search HOẶC Loại GD chứa search
            const searchMatch = (searchTerm === '' || idCell.includes(searchTerm) || typeCell.includes(searchTerm));

            // Quyết định hiển thị hay ẩn hàng dựa trên kết quả khớp filter và search
            if (statusMatch && searchMatch) {
                row.style.display = ''; // Hiển thị hàng
                hasVisibleRows = true; // Đánh dấu là có ít nhất 1 hàng được hiển thị
            } else {
                row.style.display = 'none'; // Ẩn hàng
            }
        });

        // Xử lý hiển thị/ẩn thông báo "empty state"
        if (emptyStateRow) {
            // Nếu tìm thấy hàng empty state, ẩn nó đi nếu có hàng dữ liệu hiển thị, và ngược lại
            emptyStateRow.style.display = hasVisibleRows ? 'none' : '';
        } else if (!hasVisibleRows) {
             // Ghi log nếu không tìm thấy hàng empty state nhưng lại không có hàng nào hiển thị
             // (có thể hữu ích để debug nếu cấu trúc HTML thay đổi)
             // console.log("Không có giao dịch khớp và không tìm thấy hàng empty state.");
        }
    }

    // Gọi hàm applyFiltersAndSearch một lần khi trang tải xong để áp dụng trạng thái filter/search ban đầu
    applyFiltersAndSearch();
});
// End of transaction.js
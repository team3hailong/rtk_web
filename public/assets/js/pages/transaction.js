// --- Modal Elements ---
const modalOverlay = document.getElementById('transaction-details-modal');
const modalTxId = document.getElementById('modal-tx-id');
const modalTxTime = document.getElementById('modal-tx-time');
const modalTxUpdated = document.getElementById('modal-tx-updated');
const modalTxType = document.getElementById('modal-tx-type');
const modalTxAmount = document.getElementById('modal-tx-amount');
const modalTxMethod = document.getElementById('modal-tx-method');
const modalTxStatusBadge = document.getElementById('modal-tx-status-badge');
const modalTitle = document.getElementById('modal-title');
const rejectionReasonSection = document.getElementById('rejection-reason-section');
const modalTxRejectionReason = document.getElementById('modal-tx-rejection-reason');

function showTransactionDetails(txData) {
    if (!modalOverlay || !txData) return;
    modalTitle.textContent = `Chi Tiết Giao Dịch #${txData.id}`;
    modalTxId.textContent = txData.id;
    modalTxTime.textContent = txData.time;
    modalTxUpdated.textContent = txData.updated_at;
    modalTxType.textContent = txData.type;
    modalTxAmount.textContent = txData.amount;
    modalTxMethod.textContent = txData.method;
    modalTxStatusBadge.className = 'status-badge status-badge-modal ' + txData.status_class;
    modalTxStatusBadge.textContent = txData.status_text; // Set text directly on the badge

    // Only show rejection reason if the status is 'failed'
    if (txData.status_class === 'status-failed' && txData.rejection_reason) {
        modalTxRejectionReason.textContent = txData.rejection_reason;
        rejectionReasonSection.style.display = 'block';
    } else {
        rejectionReasonSection.style.display = 'none'; // Hide for other statuses or if no reason
    }
    modalOverlay.classList.add('active');
}

function closeModal() {
    if (modalOverlay) {
        modalOverlay.classList.remove('active');
    }
}
if (modalOverlay) {
    modalOverlay.addEventListener('click', function(event) {
        if (event.target === modalOverlay) {
            closeModal();
        }
    });
}
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && modalOverlay && modalOverlay.classList.contains('active')) {
        closeModal();
    }
});
function showFailureReason(transactionId, reason) {
    alert(`Lý do thất bại cho GD #${transactionId}:\n\n${reason || 'Không có thông tin lý do.'}`);
}
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.filter-button');
    const transactionRows = document.querySelectorAll('.transactions-table tbody tr');
    const searchBox = document.querySelector('.search-box');
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            applyFiltersAndSearch();
        });
    });
    searchBox.addEventListener('input', function() {
        applyFiltersAndSearch();
    });
    function applyFiltersAndSearch() {
        const activeFilterButton = document.querySelector('.filter-button.active');
        const filterValue = activeFilterButton ? activeFilterButton.getAttribute('data-filter') : 'all';
        const searchTerm = searchBox.value.toLowerCase().trim();
        transactionRows.forEach(row => {
            if (row.querySelector('.empty-state')) {
                row.style.display = '';
                return;
            }
            const statusCell = row.querySelector('td.status .status-badge');
            let rowStatus = 'unknown';
            if (statusCell) {
                if (statusCell.classList.contains('status-completed')) rowStatus = 'completed';
                else if (statusCell.classList.contains('status-pending')) rowStatus = 'pending';
                else if (statusCell.classList.contains('status-failed')) rowStatus = 'failed';
                else if (statusCell.classList.contains('status-refunded')) rowStatus = 'refunded';
                else if (statusCell.classList.contains('status-cancelled')) rowStatus = 'cancelled';
            }
            const statusMatch = (filterValue === 'all' || rowStatus === filterValue);
            const idCell = row.cells[0]?.textContent.toLowerCase() || '';
            const amountCell = row.cells[2]?.textContent.toLowerCase() || '';
            const methodCell = row.cells[3]?.textContent.toLowerCase() || '';
            const searchMatch = (searchTerm === '' || idCell.includes(searchTerm) || amountCell.includes(searchTerm) || methodCell.includes(searchTerm));
            if (statusMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        const visibleRows = Array.from(transactionRows).filter(row => row.style.display !== 'none' && !row.querySelector('.empty-state'));
        const emptyStateRow = document.querySelector('.transactions-table tbody .empty-state');
        if (visibleRows.length === 0 && !emptyStateRow) {
            console.log("No transactions match the current filter/search.");
        }
    }
    applyFiltersAndSearch();
});
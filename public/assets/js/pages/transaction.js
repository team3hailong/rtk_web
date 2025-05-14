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
const paymentProofSection = document.getElementById('payment-proof-section');
const modalTxPaymentImageLink = document.getElementById('modal-tx-payment-image-link');

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

    // Show payment proof image link if available
    if (txData.payment_image) {
        paymentProofSection.style.display = 'block';
        modalTxPaymentImageLink.href = `/public/uploads/payment_proofs/${txData.payment_image}`;
    } else {
        paymentProofSection.style.display = 'none';
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

// --- Pagination & Filter Handling ---
function buildPaginationUrl(options = {}) {
    const params = new URLSearchParams(window.location.search);
    
    if (options.page) {
        params.set('page', options.page);
    }
    
    if (options.perPage) {
        params.set('per_page', options.perPage);
    }
    
    if (options.filter && options.filter !== 'all') {
        params.set('filter', options.filter);
    } else if (options.filter === 'all') {
        params.delete('filter');
    }
    
    return `${window.location.pathname}?${params.toString()}`;
}

document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.filter-button');
    const transactionRows = document.querySelectorAll('.transactions-table tbody tr');
    const searchBox = document.querySelector('.search-box');
    const perPageSelect = document.getElementById('per-page');
    
    // Handle filter buttons (redirect with the selected filter)
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            const url = buildPaginationUrl({
                page: 1, // Reset to page 1 when changing filter
                filter: filter,
                perPage: paginationConfig.perPage
            });
            window.location.href = url;
        });
    });
    
    // Handle per-page selector
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function() {
            const perPage = this.value;
            const url = buildPaginationUrl({
                page: 1, // Reset to page 1 when changing per_page
                perPage: perPage,
                filter: paginationConfig.currentFilter
            });
            window.location.href = url;
        });
    }
    
    // Handle client-side search (doesn't affect pagination)
    if (searchBox) {
        searchBox.addEventListener('input', function() {
            applyClientSideSearch();
        });
    }
    
    function applyClientSideSearch() {
        const searchTerm = searchBox.value.toLowerCase().trim();
        
        transactionRows.forEach(row => {
            if (row.querySelector('.empty-state')) {
                row.style.display = '';
                return;
            }
            
            const idCell = row.cells[0]?.textContent.toLowerCase() || '';
            const timeCell = row.cells[1]?.textContent.toLowerCase() || '';
            const amountCell = row.cells[2]?.textContent.toLowerCase() || '';
            const methodCell = row.cells[3]?.textContent.toLowerCase() || '';
            const statusCell = row.cells[4]?.textContent.toLowerCase() || '';
            
            const searchMatch = (searchTerm === '' || 
                                idCell.includes(searchTerm) || 
                                timeCell.includes(searchTerm) || 
                                amountCell.includes(searchTerm) || 
                                methodCell.includes(searchTerm) ||
                                statusCell.includes(searchTerm));
            
            if (searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        const visibleRows = Array.from(transactionRows).filter(row => row.style.display !== 'none' && !row.querySelector('.empty-state'));
        if (visibleRows.length === 0) {
            showNoResults();
        } else {
            hideNoResults();
        }
    }
    
    function showNoResults() {
        let noResultsRow = document.querySelector('.no-results-row');
        if (!noResultsRow) {
            const tbody = document.querySelector('.transactions-table tbody');
            noResultsRow = document.createElement('tr');
            noResultsRow.className = 'no-results-row';
            noResultsRow.innerHTML = `<td colspan="6"><div class="empty-state"><i class="fas fa-search"></i><p>Không tìm thấy giao dịch phù hợp</p></div></td>`;
            tbody.appendChild(noResultsRow);
        } else {
            noResultsRow.style.display = '';
        }
    }
    
    function hideNoResults() {
        const noResultsRow = document.querySelector('.no-results-row');
        if (noResultsRow) {
            noResultsRow.style.display = 'none';
        }
    }
    
    // Initialize search
    if (searchBox) {
        applyClientSideSearch();
    }
});
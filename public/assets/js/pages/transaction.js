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
    const searchInput = document.getElementById('search-input');
    const searchButton = document.getElementById('search-button');
    const resetButton = document.getElementById('reset-button');
    const perPageSelect = document.getElementById('per-page');
    const amountFilter = document.getElementById('amount-filter');
    const timeFilter = document.getElementById('time-filter');
    const dateFrom = document.getElementById('date-from');
    const dateTo = document.getElementById('date-to');
    const customTimeFilters = document.querySelectorAll('.time-custom-filter');
    
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
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            applyClientSideSearch();
        });
    }
    
    if (searchButton) {
        searchButton.addEventListener('click', function() {
            applyClientSideSearch();
        });
    }
    
    if (resetButton) {
        resetButton.addEventListener('click', function() {
            // Reset all filters
            if (searchInput) searchInput.value = '';
            if (amountFilter) amountFilter.value = 'all';
            if (timeFilter) timeFilter.value = 'all';
            if (dateFrom) dateFrom.value = '';
            if (dateTo) dateTo.value = '';
            
            // Hide custom date inputs
            customTimeFilters.forEach(filter => {
                filter.style.display = 'none';
            });
            
            // Apply the reset filters
            applyClientSideSearch();
        });
    }
    
    // Handle amount filter
    if (amountFilter) {
        amountFilter.addEventListener('change', function() {
            applyClientSideSearch();
        });
    }
    
    // Handle time filter
    if (timeFilter) {
        timeFilter.addEventListener('change', function() {
            const selectedValue = this.value;
            
            // Show/hide custom date inputs based on selection
            if (selectedValue === 'custom') {
                customTimeFilters.forEach(filter => {
                    filter.style.display = 'flex';
                });
            } else {
                customTimeFilters.forEach(filter => {
                    filter.style.display = 'none';
                });
            }
            
            applyClientSideSearch();
        });
    }
    
    // Handle date inputs
    if (dateFrom) {
        dateFrom.addEventListener('change', function() {
            applyClientSideSearch();
        });
    }
    
    if (dateTo) {
        dateTo.addEventListener('change', function() {
            applyClientSideSearch();
        });
    }

    // Define the applyClientSideSearch function with new filter functionality
    function applyClientSideSearch() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const selectedAmountFilter = amountFilter ? amountFilter.value : 'all';
        const selectedTimeFilter = timeFilter ? timeFilter.value : 'all';
        const fromDate = dateFrom && dateFrom.value ? new Date(dateFrom.value) : null;
        const toDate = dateTo && dateTo.value ? new Date(dateTo.value) : null;
        
        transactionRows.forEach(row => {
            if (row.querySelector('.empty-state')) {
                row.style.display = '';
                return;
            }
            
            const idCell = row.cells[1]?.textContent.toLowerCase() || '';
            const timeCell = row.cells[2]?.textContent.toLowerCase() || '';
            const amountCell = row.cells[3]?.textContent.toLowerCase() || '';
            const amountValue = parseFloat(amountCell.replace(/[^\d]/g, ''));
            const methodCell = row.cells[4]?.textContent.toLowerCase() || '';
            const statusCell = row.cells[5]?.textContent.toLowerCase() || '';
              // Get the transaction time using our helper function
            const rowDate = getRowDate(row);
            
            // Text search match
            const searchMatch = (searchTerm === '' || 
                                idCell.includes(searchTerm) || 
                                timeCell.includes(searchTerm) || 
                                amountCell.includes(searchTerm) || 
                                methodCell.includes(searchTerm) ||
                                statusCell.includes(searchTerm));
            
            // Amount filter match
            let amountMatch = true;
            if (selectedAmountFilter !== 'all') {
                if (selectedAmountFilter === 'less-than-500k' && amountValue >= 500000) {
                    amountMatch = false;
                } else if (selectedAmountFilter === '500k-to-1m' && (amountValue < 500000 || amountValue > 1000000)) {
                    amountMatch = false;
                } else if (selectedAmountFilter === '1m-to-5m' && (amountValue < 1000000 || amountValue > 5000000)) {
                    amountMatch = false;
                } else if (selectedAmountFilter === 'more-than-5m' && amountValue <= 5000000) {
                    amountMatch = false;
                }
            }
            
            // Date/time filter match
            let timeMatch = true;
            if (selectedTimeFilter !== 'all' && rowDate) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                const lastWeekStart = new Date(today);
                lastWeekStart.setDate(today.getDate() - 7);
                
                const lastMonthStart = new Date(today);
                lastMonthStart.setMonth(today.getMonth() - 1);
                
                if (selectedTimeFilter === 'today') {
                    const dateOnly = new Date(rowDate);
                    dateOnly.setHours(0, 0, 0, 0);
                    timeMatch = dateOnly.getTime() === today.getTime();
                } else if (selectedTimeFilter === 'last-week') {
                    timeMatch = rowDate >= lastWeekStart && rowDate <= today;
                } else if (selectedTimeFilter === 'last-month') {
                    timeMatch = rowDate >= lastMonthStart && rowDate <= today;
                } else if (selectedTimeFilter === 'custom') {
                    if (fromDate && toDate) {
                        // Adjust toDate to the end of the day for inclusive comparison
                        const adjustedToDate = new Date(toDate);
                        adjustedToDate.setHours(23, 59, 59, 999);
                        
                        timeMatch = rowDate >= fromDate && rowDate <= adjustedToDate;
                    } else if (fromDate) {
                        timeMatch = rowDate >= fromDate;
                    } else if (toDate) {
                        // Adjust toDate to the end of the day for inclusive comparison
                        const adjustedToDate = new Date(toDate);
                        adjustedToDate.setHours(23, 59, 59, 999);
                        
                        timeMatch = rowDate <= adjustedToDate;
                    }
                }
            }
            
            if (searchMatch && amountMatch && timeMatch) {
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
    applyClientSideSearch();
    
    // Retail invoice export logic
    const exportBtn = document.getElementById('export-retail-invoice-btn');
    const checkboxes = document.querySelectorAll('.retail-invoice-checkbox');
    const msg = document.getElementById('retail-invoice-msg');
    function updateExportBtn() {
        const checked = document.querySelectorAll('.retail-invoice-checkbox:checked');
        exportBtn.disabled = checked.length === 0 || checked.length > 5;
        if (checked.length > 5) {
            msg.textContent = 'Chỉ chọn tối đa 5 giao dịch.';
        } else {
            msg.textContent = '';
        }
    }
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateExportBtn);
    });
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            const checked = Array.from(document.querySelectorAll('.retail-invoice-checkbox:checked'));
            if (checked.length === 0 || checked.length > 5) return;
            exportBtn.disabled = true;
            msg.textContent = 'Đang xử lý...';
            fetch('/public/handlers/export_retail_invoice.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ transaction_ids: checked.map(cb => cb.value) })
            })
            .then(response => {
                if (!response.ok) throw response;
                return response.blob();
            })            .then(blob => {
                const contentType = blob.type;
                const now = new Date();
                // Format date as YYYY_MM_DD_HH_MM_SS
                const formattedDate = now.getFullYear() + '_' +
                    String(now.getMonth() + 1).padStart(2, '0') + '_' +
                    String(now.getDate()).padStart(2, '0') + '_' +
                    String(now.getHours()).padStart(2, '0') + '_' +
                    String(now.getMinutes()).padStart(2, '0') + '_' +
                    String(now.getSeconds()).padStart(2, '0');
                let filename;
                
                // Get the transaction IDs - always use the last selected transaction for the filename
                const txIds = checked.map(cb => cb.value);
                const lastTxId = txIds[txIds.length - 1]; // Get the last transaction ID
                
                // Always create a PDF file named after the last transaction ID, regardless of how many were selected
                filename = `hoadonbanle_${lastTxId}_${formattedDate}.pdf`;
                
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);
                msg.textContent = '';
            })
            .catch(async err => {
                let errorMsg = 'Lỗi khi xuất hóa đơn bán lẻ.';
                if (err && err.json) {
                    const data = await err.json();
                    if (data && data.error) errorMsg = data.error;
                }
                msg.textContent = errorMsg;
            })
            .finally(() => {
                exportBtn.disabled = false;
            });
        });
    }
      // Add an information note about time filtering
    const timeFilterLabels = document.querySelectorAll('.filter-group-item .filter-label');
    timeFilterLabels.forEach(label => {
        if (label.textContent.includes('Thời gian:')) {
            const infoIcon = document.createElement('i');
            infoIcon.className = 'fas fa-info-circle time-tooltip';
            infoIcon.style.marginLeft = '5px';
            infoIcon.title = 'Lọc theo thời gian xử lý (updated_at), không phải thời gian tạo (created_at)';
            label.appendChild(infoIcon);
        }
    });
    
    // Update row date filters to ensure we're using the processing time (updated_at)
    function getRowDate(row) {
        // First try to get from data-transaction-time attribute
        if (row.getAttribute('data-transaction-time')) {
            return new Date(row.getAttribute('data-transaction-time'));
        } 
        
        // Then try to get from the hidden span
        const updatedTimeElement = row.querySelector('.transaction-updated-time');
        if (updatedTimeElement && updatedTimeElement.textContent) {
            return new Date(updatedTimeElement.textContent.trim());
        }
        
        // Look for "Xử lý:" in the time cell as a fallback
        const timeCell = row.cells[2]?.innerHTML || '';
        const updatedTimeMatch = timeCell.match(/Xử lý: ([^<]+)/);
        if (updatedTimeMatch && updatedTimeMatch[1]) {
            return new Date(updatedTimeMatch[1].trim());
        }
        
        // Last fallback to visible time (should be handled by one of the above methods)
        return row.cells[2]?.textContent ? new Date(row.cells[2].textContent) : null;
    }

    // Update the table header to make it clear which time is used for filtering
    const timeHeader = document.querySelector('.transactions-table thead th:nth-child(3)');
    if (timeHeader) {
        // Update the header text to clarify that filtering is based on processing time
        timeHeader.innerHTML = 'Thời gian <i class="fas fa-info-circle" title="Bộ lọc thời gian sử dụng thời gian xử lý"></i>';
    }
});
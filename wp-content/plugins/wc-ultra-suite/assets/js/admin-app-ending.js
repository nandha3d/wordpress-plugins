
/**
 * Utility: Format currency
 */
formatCurrency(amount) {
    const symbol = wcUltraSuite.currencySymbol || '$';
    const formatted = parseFloat(amount).toFixed(2);
    return `${symbol}${formatted}`;
},

/**
 * Utility: Format date
 */
formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString();
},

/**
 * Utility: Get status badge
 */
getStatusBadge(status) {
    const statusMap = {
        'completed': 'badge-success',
        'processing': 'badge-info',
        'pending': 'badge-warning',
        'failed': 'badge-danger',
        'cancelled': 'badge-danger',
        'on-hold': 'badge-warning'
    };

    const badgeClass = statusMap[status] || 'badge-info';
    return `<span class="badge ${badgeClass}">${status}</span>`;
}
    };

// Initialize when document is ready
$(document).ready(() => {
    UltraSuite.init();
});

}) (jQuery);

// Toast Notification System - Replaces all alert() popups

function showToast(message, type = 'info', title = '') {
    const titles = {
        success: 'Success!',
        error: 'Error!',
        warning: 'Warning!',
        info: 'Information'
    };
    
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    const toastTitle = title || titles[type];
    const icon = icons[type];
    
    const toastHtml = `
        <div class="toast-notification ${type}" style="animation: slideInRight 0.3s ease-out">
            <div class="toast-icon">
                <i class="fas ${icon}"></i>
            </div>
            <div class="toast-content">
                <div class="toast-title">${toastTitle}</div>
                <div class="toast-message">${message}</div>
            </div>
            <div class="toast-close">
                <i class="fas fa-times"></i>
            </div>
        </div>
    `;
    
    let container = $('.toast-container');
    if(container.length === 0) {
        $('body').append('<div class="toast-container"></div>');
        container = $('.toast-container');
    }
    
    const toast = $(toastHtml);
    container.append(toast);
    
    toast.find('.toast-close').click(function() {
        toast.fadeOut(300, function() { $(this).remove(); });
    });
    
    setTimeout(function() {
        toast.fadeOut(300, function() { $(this).remove(); });
    }, 4000);
}

// Usage examples:
// showToast('Product added to cart!', 'success');
// showToast('Please login first', 'error');
// showToast('Stock is low!', 'warning');
// showToast('Order placed successfully', 'success');
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const customerName = document.getElementById('customerName').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const branch = document.getElementById('branch').value.trim();
            const entryDate = document.getElementById('entryDate').value;
            
            if (!customerName || !phone || !branch || !entryDate) {
                e.preventDefault();
                alert('Please fill in all fields');
                return false;
            }
            
            // Simple phone validation
            const phonePattern = /^[0-9+\-\s()]+$/;
            if (!phonePattern.test(phone)) {
                e.preventDefault();
                alert('Please enter a valid phone number');
                return false;
            }
        });
    }
    
    // Clear message after 5 seconds
    const messageEl = document.querySelector('.message');
    if (messageEl && messageEl.textContent.trim() !== '') {
        setTimeout(() => {
            messageEl.textContent = '';
            messageEl.style.display = 'none';
        }, 5000);
    }
    
    // Enhance pagination for better UX
    const paginationLinks = document.querySelectorAll('.pagination-link');
    paginationLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Add a small loading indicator
            const tableContainer = document.querySelector('.table-container');
            tableContainer.style.opacity = '0.7';
            
            // Remove loading indicator after a short delay
            setTimeout(() => {
                tableContainer.style.opacity = '1';
            }, 500);
        });
    });
    
    // Date validation for filters
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    
    if (startDateInput && endDateInput) {
        startDateInput.addEventListener('change', function() {
            if (endDateInput.value && startDateInput.value > endDateInput.value) {
                alert('Start date cannot be after end date');
                startDateInput.value = '';
            }
        });
        
        endDateInput.addEventListener('change', function() {
            if (startDateInput.value && endDateInput.value < startDateInput.value) {
                alert('End date cannot be before start date');
                endDateInput.value = '';
            }
        });
    }
});
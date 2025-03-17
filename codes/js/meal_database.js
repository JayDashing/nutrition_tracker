document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            tabs.forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Show relevant tab content
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId + '-content').classList.add('active');
        });
    });
    
    // Toggle rejection form
    window.toggleRejectionForm = function(mealId) {
        const form = document.getElementById('rejection-form-' + mealId);
        form.style.display = form.style.display === 'block' ? 'none' : 'block';
    };
    
    // Confirmation for delete action
    window.confirmDelete = function() {
        return confirm('Are you sure you want to permanently delete this meal submission? This action cannot be undone.');
    };
    
    // Search and filter functionality
    const searchInput = document.getElementById('search-meals');
    const statusFilter = document.getElementById('status-filter');
    const mealItems = document.querySelectorAll('.meal-item');
    
    function filterMeals() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value;
        
        mealItems.forEach(item => {
            const mealTitle = item.querySelector('.meal-title').textContent.toLowerCase();
            const mealUser = item.querySelector('.meal-user').textContent.toLowerCase();
            const mealStatus = item.getAttribute('data-status');
            
            const matchesSearch = mealTitle.includes(searchTerm) || mealUser.includes(searchTerm);
            const matchesStatus = statusValue === 'all' || mealStatus === statusValue;
            
            if (matchesSearch && matchesStatus) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }
    
    searchInput.addEventListener('input', filterMeals);
    statusFilter.addEventListener('change', filterMeals);
});
// Show/hide rejection form
document.querySelectorAll('.show-rejection-form').forEach(button => {
    button.addEventListener('click', function() {
        const rejectionForm = this.nextElementSibling;
        rejectionForm.style.display = rejectionForm.style.display === 'none' ? 'block' : 'none';
    });
});

// Filter posts
document.querySelectorAll('.filter-btn').forEach(button => {
    button.addEventListener('click', function() {
        // Update active button
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        this.classList.add('active');
        
        // Get filter value
        const filter = this.getAttribute('data-filter');
        const posts = document.querySelectorAll('.post-card');
        
        posts.forEach(post => {
            if (filter === 'all') {
                post.style.display = 'block';
            } else {
                if (post.classList.contains(filter)) {
                    post.style.display = 'block';
                } else {
                    post.style.display = 'none';
                }
            }
        });
    });
});
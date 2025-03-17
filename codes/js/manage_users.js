    // Handle toast messages
    document.addEventListener('DOMContentLoaded', function() {
        const toast = document.querySelector('.toast');
        if (toast) {
            setTimeout(function() {
                toast.classList.remove('show');
            }, 3000);
        }
    });

    // Handle sorting
    function updateSort(sortBy) {
        const params = new URLSearchParams(window.location.search);
        params.set('sort', sortBy);
        window.location.search = params.toString();
    }

    function updateOrder(orderBy) {
        const params = new URLSearchParams(window.location.search);
        params.set('order', orderBy);
        window.location.search = params.toString();
    }

    function sortTable(column) {
        const params = new URLSearchParams(window.location.search);
        const currentSort = params.get('sort') || 'id';
        const currentOrder = params.get('order') || 'ASC';
        
        if (currentSort === column) {
            params.set('order', currentOrder === 'ASC' ? 'DESC' : 'ASC');
        } else {
            params.set('sort', column);
            params.set('order', 'ASC');
        }
        
        window.location.search = params.toString();
    }
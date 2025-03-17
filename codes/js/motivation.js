// Add this to the existing script section or create a new one
document.addEventListener('DOMContentLoaded', function() {
    const gallery = document.querySelector('.progress-gallery');
    
    // Skip if no gallery or if it's empty
    if (!gallery || gallery.querySelector('.empty-gallery')) {
        return;
    }
    
    // Create arrow container
    const arrowsContainer = document.createElement('div');
    arrowsContainer.className = 'carousel-arrows';
    
    // Create left arrow
    const leftArrow = document.createElement('div');
    leftArrow.className = 'carousel-arrow left';
    leftArrow.innerHTML = '<i class="bx bx-chevron-left" style="font-size: 24px;"></i>';
    leftArrow.style.left = '10px'; // Adjust this value to move the arrow closer to the center
    
    // Create right arrow
    const rightArrow = document.createElement('div');
    rightArrow.className = 'carousel-arrow right';
    rightArrow.innerHTML = '<i class="bx bx-chevron-right" style="font-size: 24px;"></i>';
    
    // Add arrows to container
    arrowsContainer.appendChild(leftArrow);
    arrowsContainer.appendChild(rightArrow);
    
    // Add container to parent element (not the gallery itself)
    const gallerySection = document.querySelector('.progress-gallery-section');
    gallerySection.style.position = 'relative';
    gallerySection.appendChild(arrowsContainer);
    
    // Calculate scroll amount (width of one card plus margin)
    const scrollAmount = 270; // 250px card width + 20px margins
    
    // Add click events
    leftArrow.addEventListener('click', function() {
        gallery.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
    });
    
    rightArrow.addEventListener('click', function() {
        gallery.scrollBy({ left: scrollAmount, behavior: 'smooth' });
    });
});
// JavaScript for improving the Community Progress Gallery

document.addEventListener('DOMContentLoaded', function() {
    // Make progress cards clickable with modal popup
    const progressCards = document.querySelectorAll('.progress-card');
    const body = document.body;
    
    // Create modal container
    const modalContainer = document.createElement('div');
    modalContainer.className = 'modal-container';
    modalContainer.style.display = 'none';
    modalContainer.style.position = 'fixed';
    modalContainer.style.top = '0';
    modalContainer.style.left = '0';
    modalContainer.style.width = '100%';
    modalContainer.style.height = '100%';
    modalContainer.style.backgroundColor = 'rgba(0, 0, 0, 0.7)';
    modalContainer.style.zIndex = '1000';
    modalContainer.style.display = 'flex';
    modalContainer.style.justifyContent = 'center';
    modalContainer.style.alignItems = 'center';
    modalContainer.style.opacity = '0';
    modalContainer.style.visibility = 'hidden';
    modalContainer.style.transition = 'opacity 0.3s ease, visibility 0.3s ease';
    
    const modalContent = document.createElement('div');
    modalContent.className = 'modal-content';
    modalContent.style.backgroundColor = 'white';
    modalContent.style.borderRadius = '16px';
    modalContent.style.padding = '20px';
    modalContent.style.maxWidth = '60%';
    modalContent.style.maxHeight = '80%';
    modalContent.style.overflow = 'auto';
    modalContent.style.boxShadow = '0 8px 30px rgba(0, 0, 0, 0.2)';
    modalContent.style.transform = 'scale(0.8)';
    modalContent.style.transition = 'transform 0.3s ease';
    
    const closeButton = document.createElement('button');
    closeButton.innerHTML = '×';
    closeButton.style.position = 'absolute';
    closeButton.style.top = '10px';
    closeButton.style.right = '15px';
    closeButton.style.border = 'none';
    closeButton.style.background = 'transparent';
    closeButton.style.fontSize = '24px';
    closeButton.style.cursor = 'pointer';
    closeButton.style.color = '#2e7d32';
    
    modalContent.appendChild(closeButton);
    modalContainer.appendChild(modalContent);
    body.appendChild(modalContainer);
    
    // Fix the progress descriptions to prevent overflow
    const progressDescriptions = document.querySelectorAll('.progress-description');
    progressDescriptions.forEach(desc => {
        desc.style.display = '-webkit-box';
        desc.style.webkitLineClamp = '3';
        desc.style.webkitBoxOrient = 'vertical';
        desc.style.overflow = 'hidden';
        desc.style.textOverflow = 'ellipsis';
        desc.style.maxHeight = '4.5em'; // Approximately 3 lines of text
    });
    
    // Make progress cards clickable
    progressCards.forEach(card => {
        card.style.cursor = 'pointer';
        
        card.addEventListener('click', function() {
            const username = this.querySelector('.progress-user span').textContent;
            const description = this.querySelector('.progress-description').getAttribute('data-full-text') || 
                                this.querySelector('.progress-description').textContent;
            const date = this.querySelector('.progress-date').textContent;
            const hasImage = this.querySelector('.progress-image img');
            
            // Clear previous content
            modalContent.innerHTML = '';
            modalContent.appendChild(closeButton);
            
            // Add content to modal
            const modalHeader = document.createElement('div');
            modalHeader.className = 'modal-header';
            modalHeader.style.marginBottom = '15px';
            modalHeader.style.borderBottom = '1px solid rgba(46, 125, 50, 0.1)';
            modalHeader.style.paddingBottom = '10px';
            
            const modalUser = document.createElement('div');
            modalUser.className = 'modal-user';
            modalUser.style.display = 'flex';
            modalUser.style.alignItems = 'center';
            modalUser.style.gap = '10px';
            modalUser.style.fontSize = '18px';
            modalUser.style.fontWeight = '600';
            modalUser.style.color = '#1b5e20';
            modalUser.innerHTML = '<i class="bx bx-user-circle" style="font-size: 24px;"></i>' + username;
            
            modalHeader.appendChild(modalUser);
            modalContent.appendChild(modalHeader);
            
            // Add image if available
            if (hasImage) {
                const modalImage = document.createElement('div');
                modalImage.className = 'modal-image';
                modalImage.style.marginBottom = '20px';
                modalImage.style.textAlign = 'center';
                
                const img = document.createElement('img');
                img.src = hasImage.src;
                img.style.maxWidth = '50%';
                img.style.borderRadius = '8px';
                img.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.1)';
                
                modalImage.appendChild(img);
                modalContent.appendChild(modalImage);
            }
            
            // Add description
            const modalDescription = document.createElement('div');
            modalDescription.className = 'modal-description';
            modalDescription.style.marginBottom = '15px';
            modalDescription.style.fontSize = '16px';
            modalDescription.style.lineHeight = '1.6';
            modalDescription.style.color = '#333';
            modalDescription.textContent = description;
            
            // Add date
            const modalDate = document.createElement('div');
            modalDate.className = 'modal-date';
            modalDate.style.fontSize = '14px';
            modalDate.style.color = '#757575';
            modalDate.style.textAlign = 'right';
            modalDate.textContent = date;
            
            modalContent.appendChild(modalDescription);
            modalContent.appendChild(modalDate);
            
            // Show modal with animation
            modalContainer.style.visibility = 'visible';
            modalContainer.style.opacity = '1';
            
            setTimeout(() => {
                modalContent.style.transform = 'scale(1)';
            }, 50);
        });
    });
    
    // Close modal when clicking close button or outside
    closeButton.addEventListener('click', closeModal);
    modalContainer.addEventListener('click', function(e) {
        if (e.target === modalContainer) {
            closeModal();
        }
    });
    
    function closeModal() {
        modalContent.style.transform = 'scale(0.8)';
        modalContainer.style.opacity = '0';
        
        setTimeout(() => {
            modalContainer.style.visibility = 'hidden';
        }, 300);
    }
    
    // Store full description text
    document.querySelectorAll('.progress-description').forEach(desc => {
        // Store the full text before truncating
        const fullText = desc.textContent;
        desc.setAttribute('data-full-text', fullText);
        
        // Ensure the image containers maintain proper dimensions
        const imgContainers = document.querySelectorAll('.progress-image');
        imgContainers.forEach(container => {
            container.style.width = '100%';
            container.style.height = '200px';
            container.style.overflow = 'hidden';
            
            const img = container.querySelector('img');
            if (img) {
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'cover';
            }
        });
    });
});
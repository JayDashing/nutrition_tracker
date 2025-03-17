document.addEventListener('DOMContentLoaded', function() {
    const autoCalculateCheckbox = document.getElementById('auto-calculate');
    const caloriesInput = document.getElementById('calories-input');
    const personalDetailsGroup = document.querySelector('.personal-details-group');
    const personalDetailsInputs = personalDetailsGroup.querySelectorAll('input, select');
    
    // Function to toggle calories input field and personal details
    function toggleInputs() {
        if (autoCalculateCheckbox.checked) {
            // Auto-calculate is ON
            // Disable calories input
            caloriesInput.disabled = true;
            caloriesInput.removeAttribute('required');
            caloriesInput.style.opacity = '0.5';
            caloriesInput.placeholder = 'Auto-calculated';
            
            // Enable personal details section
            personalDetailsGroup.classList.add('active');
            personalDetailsInputs.forEach(input => {
                input.disabled = false;
                input.style.opacity = '1';
            });
        } else {
            // Auto-calculate is OFF
            // Enable calories input
            caloriesInput.disabled = false;
            caloriesInput.setAttribute('required', '');
            caloriesInput.style.opacity = '1';
            caloriesInput.placeholder = 'Calories Burned';
            
            // Disable personal details section
            personalDetailsGroup.classList.remove('active');
            personalDetailsInputs.forEach(input => {
                input.disabled = true;
                input.style.opacity = '0.5';
            });
        }
    }
    
    // Initial state
    toggleInputs();
    
    // Add event listener for checkbox change
    autoCalculateCheckbox.addEventListener('change', toggleInputs);
});
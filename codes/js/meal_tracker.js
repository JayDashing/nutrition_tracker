       // This code should be added to all pages that use the theme toggle

       document.addEventListener('DOMContentLoaded', function() {
        const themeToggle = document.getElementById('theme-toggle');
        const modeText = document.querySelector('.mode-text');
        
        // Check if theme preference exists in localStorage
        const savedTheme = localStorage.getItem('theme');
        
        // Apply saved theme if it exists
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
            themeToggle.checked = true;
            modeText.textContent = 'Dark Mode';
        } else {
            document.body.classList.remove('dark-mode');
            themeToggle.checked = false;
            modeText.textContent = 'Light Mode';
        }
        
        // Theme Toggle Event Listener
        themeToggle.addEventListener('change', function() {
            if (this.checked) {
                document.body.classList.add('dark-mode');
                modeText.textContent = 'Dark Mode';
                localStorage.setItem('theme', 'dark');
            } else {
                document.body.classList.remove('dark-mode');
                modeText.textContent = 'Light Mode';
                localStorage.setItem('theme', 'light');
            }
        });
    });
    
    document.addEventListener('DOMContentLoaded', function() {
        // Tab functionality
        const tabs = document.querySelectorAll('.tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs and contents
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab and its content
                this.classList.add('active');
                const tabContent = document.getElementById(this.getAttribute('data-tab'));
                tabContent.classList.add('active');
            });
        });
        
        // Food search functionality
        const searchInput = document.getElementById('foodSearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchValue = this.value.toLowerCase();
                const foodItems = document.querySelectorAll('.food-item');
                
                foodItems.forEach(item => {
                    const foodName = item.querySelector('strong').textContent.toLowerCase();
                    if (foodName.includes(searchValue)) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        }
        
        // Food selection
        const foodItems = document.querySelectorAll('.food-item');
        foodItems.forEach(item => {
            item.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const calories = this.getAttribute('data-calories');
                const protein = this.getAttribute('data-protein');
                const carbs = this.getAttribute('data-carbs');
                const fat = this.getAttribute('data-fat');
                
                // Show nutrient info section
                const infoSection = document.getElementById('selectedFoodInfo');
                infoSection.style.display = 'block';
                
                // Update values
                document.getElementById('selectedFoodName').textContent = name;
                document.getElementById('caloriesValue').textContent = calories;
                document.getElementById('proteinValue').textContent = protein + 'g';
                document.getElementById('carbsValue').textContent = carbs + 'g';
                document.getElementById('fatValue').textContent = fat + 'g';
                
                // Update form fields
                document.getElementById('foodName').value = name;
                document.getElementById('calculatedCalories').value = calories;
                document.getElementById('foodId').value = id;
                
                // Scroll to the info section
                infoSection.scrollIntoView({ behavior: 'smooth' });
                
                updateCalories();
            });
        });
        
        // Calculate calories based on portion size
        const portionSize = document.getElementById('portionSize');
        const portionUnit = document.getElementById('portionUnit');
        
        if (portionSize && portionUnit) {
            portionSize.addEventListener('input', updateCalories);
            portionUnit.addEventListener('change', updateCalories);
        }
        
        function updateCalories() {
            const baseCalories = parseFloat(document.getElementById('caloriesValue').textContent);
            const size = parseFloat(document.getElementById('portionSize').value);
            const unit = document.getElementById('portionUnit').value;
            
            // Convert to grams based on unit
            let grams = size;
            switch(unit) {
                case 'oz':
                    grams = size * 28.35;
                    break;
                case 'cup':
                    grams = size * 240;
                    break;
                case 'tbsp':
                    grams = size * 15;
                    break;
                case 'tsp':
                    grams = size * 5;
                    break;
                case 'ml':
                    grams = size;
                    break;
                case 'serving':
                    grams = size * 100; // Assume one serving is 100g
                    break;
            }
            
            // Calculate calories based on portion
            const calculatedCalories = Math.round((baseCalories / 100) * grams);
            document.getElementById('calculatedCalories').value = calculatedCalories;
        }
    });
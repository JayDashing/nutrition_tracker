// Sidebar Toggle
document.querySelector('.sidebar').classList.add('active');
document.querySelector('.main-content').classList.add('active');

document.querySelector('.sidebar-toggle').addEventListener('click', function() {
    document.querySelector('.sidebar').classList.toggle('active');
    document.querySelector('.main-content').classList.toggle('active');
});

// Theme Toggle
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

// Announcements Sidebar Toggle
document.getElementById('notification-toggle').addEventListener('click', function() {
    document.getElementById('announcements-sidebar').classList.add('show');
    document.getElementById('overlay').classList.add('show');
    
    // Mark all announcements as read via AJAX
    fetch('mark_announcements_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the badge
            const badge = this.querySelector('.badge');
            if (badge) {
                badge.remove();
            }
        }
    })
    .catch(error => {
        console.error('Error marking announcements as read:', error);
    });
});

document.getElementById('close-announcements').addEventListener('click', function() {
    document.getElementById('announcements-sidebar').classList.remove('show');
    document.getElementById('overlay').classList.remove('show');
});

document.getElementById('overlay').addEventListener('click', function() {
    document.getElementById('announcements-sidebar').classList.remove('show');
    document.getElementById('overlay').classList.remove('show');
});

// Water intake counter with AJAX saving
document.getElementById('add-water').addEventListener('click', function(e) {
    e.preventDefault();
    
    const waterIntakeDisplay = document.getElementById('water-display');
    const waterCountInput = document.getElementById('water-count');
    let current = parseInt(waterCountInput.value);
    const total = 8; // Water goal
    
    if (current < total) {
        current++;
        waterIntakeDisplay.textContent = `${current} / ${total} glasses`;
        
        const progressBar = document.getElementById('water-progress');
        const percentage = (current / total) * 100;
        progressBar.style.width = `${percentage}%`;
        
        // Update hidden form field
        waterCountInput.value = current;
        
        // Save water intake via AJAX
        fetch('save_water.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `water_count=${current}`
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Error saving water intake:', data.message);
            }
        })
        .catch(error => {
            console.error('Error saving water intake:', error);
        });
    }
});

// Add event listener for the remove water button
document.getElementById('delete-water').addEventListener('click', function(e) {
    e.preventDefault();
    
    const waterIntakeDisplay = document.getElementById('water-display');
    const waterCountInput = document.getElementById('water-count');
    let current = parseInt(waterCountInput.value);
    const total = 8; // Water goal
    
    if (current > 0) {
        current--;
        waterIntakeDisplay.textContent = `${current} / ${total} glasses`;
        
        const progressBar = document.getElementById('water-progress');
        const percentage = (current / total) * 100;
        progressBar.style.width = `${percentage}%`;
        
        // Update hidden form field
        waterCountInput.value = current;
        
        // Save water intake via AJAX
        fetch('save_water.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `water_count=${current}`
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Error saving water intake:', data.message);
            }
        })
        .catch(error => {
            console.error('Error saving water intake:', error);
        });
    }
});

// Display joined challenges when the home page loads
document.addEventListener('DOMContentLoaded', function() {
    displayJoinedChallenges();
    displayRandomChallenge();
    
    // Set interval to rotate challenge display every 10 seconds
    setInterval(displayRandomChallenge, 10000);
});

function displayJoinedChallenges() {
    const joinedChallenges = JSON.parse(localStorage.getItem('joinedChallenges')) || [];
    const container = document.getElementById('joined-challenges');
    
    // Clear current content
    container.innerHTML = '';
    
    if (joinedChallenges.length === 0) {
        container.innerHTML = '<p class="no-challenges">You haven\'t joined any challenges yet. <a href="challenges.php">Explore challenges</a></p>';
        return;
    }
    
    // Add each joined challenge to the container
    joinedChallenges.forEach(challenge => {
        const challengeCard = document.createElement('div');
        challengeCard.className = 'challenge-card ' + challenge.difficulty.toLowerCase();
        
        challengeCard.innerHTML = `
            <div class="challenge-icon">${challenge.icon}</div>
            <h3>${challenge.title}</h3>
            <p>${challenge.description}</p>
            <div class="challenge-meta">
                <span class="duration"><i class='bx bx-time'></i> ${challenge.duration}</span>
                <span class="difficulty">${challenge.difficulty}</span>
            </div>
            <div class="challenge-progress">
                <span>Started: ${challenge.startDate}</span>
            </div>
            <button class="leave-btn" onclick="leaveChallenge('${challenge.title}')">Leave Challenge</button>
        `;
        
        container.appendChild(challengeCard);
    });
}

function displayRandomChallenge() {
    const joinedChallenges = JSON.parse(localStorage.getItem('joinedChallenges')) || [];
    
    if (joinedChallenges.length === 0) {
        // No challenges joined, hide or reset the challenge card
        document.getElementById('challenge-title').textContent = 'Active Challenge';
        document.getElementById('challenge-progress').textContent = 'No active challenges';
        document.getElementById('challenge-progress-bar').style.width = '0%';
        document.getElementById('challenge-progress-bar').className = 'progress';
        return;
    }
    
    // Select a random challenge from joined challenges
    const randomIndex = Math.floor(Math.random() * joinedChallenges.length);
    const challenge = joinedChallenges[randomIndex];
    
    // Update the challenge card
    document.getElementById('challenge-title').textContent = challenge.title;
    
    // Calculate days remaining
    const today = new Date();
    const endDate = new Date(challenge.endDate);
    const daysRemaining = Math.max(Math.ceil((endDate - today) / (1000 * 60 * 60 * 24)), 0);
    
    // Update the progress text
    document.getElementById('challenge-progress').textContent = `${daysRemaining} / ${challenge.duration} days remaining`;
    
    // Calculate progress percentage
    const startDate = new Date(challenge.startDate);
    const totalDuration = parseInt(challenge.duration);
    const daysElapsed = Math.min(Math.floor((today - startDate) / (1000 * 60 * 60 * 24)), totalDuration);
    const progressPercentage = Math.min(Math.floor((daysElapsed / totalDuration) * 100), 100);
    
    // Update the progress bar
    document.getElementById('challenge-progress-bar').style.width = `${progressPercentage}%`;
    
    // Set color class based on difficulty
    document.getElementById('challenge-progress-bar').className = `progress ${challenge.difficulty.toLowerCase()}`;
}

function leaveChallenge(title) {
    let joinedChallenges = JSON.parse(localStorage.getItem('joinedChallenges')) || [];
    joinedChallenges = joinedChallenges.filter(challenge => challenge.title !== title);
    localStorage.setItem('joinedChallenges', JSON.stringify(joinedChallenges));
    
    // Refresh the challenges display
    displayJoinedChallenges();
}
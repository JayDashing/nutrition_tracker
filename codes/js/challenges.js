function joinChallenge(title, icon, duration, difficulty, description) {
    // Get existing challenges from localStorage or initialize an empty array
    let joinedChallenges = JSON.parse(localStorage.getItem('joinedChallenges')) || [];
    const button = event.target;
    const card = button.parentElement;
    const progressBar = card.querySelector('.challenge-progress');
    const progressIndicator = card.querySelector('.progress-bar');
    const timer = card.querySelector('.timer');
    const timeRemaining = card.querySelector('.time-remaining');
    
    // Check if this challenge is already joined
    const existingIndex = joinedChallenges.findIndex(challenge => challenge.title === title);
    
    if (existingIndex !== -1) {
        // Challenge already joined - remove it (toggle functionality)
        joinedChallenges.splice(existingIndex, 1);
        button.textContent = "Join Challenge";
        button.classList.remove("joined");
        button.classList.remove("completed");
        
        // Hide progress bar and timer
        progressBar.style.display = "none";
        timer.style.display = "none";
    } else {
        // Add the challenge to joined challenges
        const today = new Date();
        const startDate = today.toISOString().slice(0, 10);
        const endDate = new Date(today.setDate(today.getDate() + parseInt(duration))).toISOString().slice(0, 10);
        
        joinedChallenges.push({
            title: title,
            icon: icon,
            duration: duration,
            difficulty: difficulty,
            description: description,
            startDate: startDate,
            endDate: endDate,
            isCompleted: false
        });
        
        button.textContent = "Joined";
        button.classList.add("joined");
        
        // Show and update progress bar and timer
        progressBar.style.display = "block";
        timer.style.display = "flex";
        
        // Calculate and display days remaining
        updateChallengeProgress(title, startDate, endDate, progressIndicator, timeRemaining, button);
    }
    
    // Save back to localStorage
    localStorage.setItem('joinedChallenges', JSON.stringify(joinedChallenges));
}

function updateChallengeProgress(title, startDate, endDate, progressBar, timerElement, button) {
    const start = new Date(startDate);
    const end = new Date(endDate);
    const today = new Date();
    
    // Calculate total duration and days elapsed
    const totalDuration = (end - start) / (1000 * 60 * 60 * 24);
    const daysElapsed = (today - start) / (1000 * 60 * 60 * 24);
    
    // Calculate progress percentage (capped at 100%)
    let progressPercentage = Math.min(Math.floor((daysElapsed / totalDuration) * 100), 100);
    if (progressPercentage < 0) progressPercentage = 0;
    
    // Calculate days remaining
    const daysRemaining = Math.max(Math.ceil((end - today) / (1000 * 60 * 60 * 24)), 0);
    
    // Update the UI
    progressBar.style.width = progressPercentage + "%";
    timerElement.textContent = daysRemaining + " days remaining";
    
    // If challenge is complete
    if (today >= end) {
        timerElement.textContent = "Challenge complete!";
        progressBar.style.width = "100%";
        
        // Update button text and styling
        button.textContent = "Completed";
        button.classList.add("completed");
        
        // Update completed status in localStorage
        let joinedChallenges = JSON.parse(localStorage.getItem('joinedChallenges')) || [];
        const challengeIndex = joinedChallenges.findIndex(challenge => challenge.title === title);
        
        if (challengeIndex !== -1 && !joinedChallenges[challengeIndex].isCompleted) {
            joinedChallenges[challengeIndex].isCompleted = true;
            localStorage.setItem('joinedChallenges', JSON.stringify(joinedChallenges));
            
            // Increment completed challenges counter
            incrementCompletedChallenges();
        }
    }
}

function incrementCompletedChallenges() {
    // This would typically be an AJAX call to update the server
    // For now, we'll just update the display
    const statsElement = document.querySelector('.stats strong');
    const currentCount = parseInt(statsElement.textContent);
    statsElement.textContent = currentCount + 1;
}

// Check which challenges are already joined when the page loads
document.addEventListener('DOMContentLoaded', function() {
    const joinedChallenges = JSON.parse(localStorage.getItem('joinedChallenges')) || [];
    const cards = document.querySelectorAll('.challenge-card');
    
    cards.forEach(card => {
        const challengeTitle = card.querySelector('h3').textContent;
        const joinedChallenge = joinedChallenges.find(challenge => challenge.title === challengeTitle);
        const button = card.querySelector('.join-btn');
        const progressBar = card.querySelector('.challenge-progress');
        const progressIndicator = card.querySelector('.progress-bar');
        const timer = card.querySelector('.timer');
        const timeRemaining = card.querySelector('.time-remaining');
        
        if (joinedChallenge) {
            // Show challenge as joined
            button.classList.add("joined");
            
            // Check if challenge is completed
            const today = new Date();
            const end = new Date(joinedChallenge.endDate);
            
            if (today >= end || joinedChallenge.isCompleted) {
                button.textContent = "Completed";
                button.classList.add("completed");
            } else {
                button.textContent = "Joined";
            }
            
            // Show and update progress bar and timer
            progressBar.style.display = "block";
            timer.style.display = "flex";
            
            // Update progress and timer information
            updateChallengeProgress(
                joinedChallenge.title,
                joinedChallenge.startDate,
                joinedChallenge.endDate,
                progressIndicator,
                timeRemaining,
                button
            );
        }
    });
    
    // Set up auto-refresh for timers - update every hour
    setInterval(function() {
        const joinedChallenges = JSON.parse(localStorage.getItem('joinedChallenges')) || [];
        const cards = document.querySelectorAll('.challenge-card');
        
        cards.forEach(card => {
            const challengeTitle = card.querySelector('h3').textContent;
            const joinedChallenge = joinedChallenges.find(challenge => challenge.title === challengeTitle);
            
            if (joinedChallenge) {
                const progressIndicator = card.querySelector('.progress-bar');
                const timeRemaining = card.querySelector('.time-remaining');
                const button = card.querySelector('.join-btn');
                
                updateChallengeProgress(
                    joinedChallenge.title,
                    joinedChallenge.startDate,
                    joinedChallenge.endDate,
                    progressIndicator,
                    timeRemaining,
                    button
                );
            }
        });
    }, 3600000); // Update every hour
});
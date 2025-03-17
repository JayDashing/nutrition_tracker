function clearSearch() {
    document.getElementById("searchInput").value = "";
    window.location.href = "meal_history.php"; // Reloads page to show full list
}
// Dark/Light Mode Toggle
document.addEventListener("DOMContentLoaded", function() {
    const themeToggle = document.getElementById("theme-toggle");
    const themeIcon = document.getElementById("theme-icon");
    const body = document.body;
    
    // Charger le thème sauvegardé
    const savedTheme = localStorage.getItem("theme") || "dark";
    if (savedTheme === "light") {
        body.classList.add("light-mode");
        themeIcon.setAttribute("data-lucide", "moon");
        lucide.createIcons();
    }
    
    // Toggle au clic
    themeToggle.addEventListener("click", function() {
        body.classList.toggle("light-mode");
        
        if (body.classList.contains("light-mode")) {
            localStorage.setItem("theme", "light");
            themeIcon.setAttribute("data-lucide", "moon");
        } else {
            localStorage.setItem("theme", "dark");
            themeIcon.setAttribute("data-lucide", "sun");
        }
        
        // Re-init Lucide pour changer l'icône
        lucide.createIcons();
    });
});

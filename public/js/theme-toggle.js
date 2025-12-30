// Theme Toggle - Vault.gg
console.log("Theme Toggle chargé");

document.addEventListener("DOMContentLoaded", function() {
    const themeToggle = document.getElementById("theme-toggle");
    const themeIcon = document.getElementById("theme-icon");
    const html = document.documentElement;
    
    if (!themeToggle || !themeIcon) {
        console.error("❌ Éléments theme-toggle introuvables");
        return;
    }
    
    // Charger le thème sauvegardé (dark par défaut)
    const savedTheme = localStorage.getItem("theme") || "dark";
    console.log("Thème actuel :", savedTheme);
    
    // Appliquer le thème au chargement
    if (savedTheme === "light") {
        html.classList.add("light-mode");
        themeIcon.setAttribute("data-lucide", "moon");
    } else {
        html.classList.remove("light-mode");
        themeIcon.setAttribute("data-lucide", "sun");
    }
    
    // Re-initialiser les icônes Lucide
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    // Toggle au clic
    themeToggle.addEventListener("click", function() {
        html.classList.toggle("light-mode");
        
        if (html.classList.contains("light-mode")) {
            // Passage en mode LIGHT
            console.log("Mode LIGHT activé");
            localStorage.setItem("theme", "light");
            themeIcon.setAttribute("data-lucide", "moon");
        } else {
            // Passage en mode DARK
            console.log("Mode DARK activé");
            localStorage.setItem("theme", "dark");
            themeIcon.setAttribute("data-lucide", "sun");
        }
        
        // Re-initialiser les icônes Lucide
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
    
    console.log("Theme toggle initialisé");
});
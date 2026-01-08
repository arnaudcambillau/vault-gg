// Theme Toggle - Vault.gg
console.log("Theme Toggle chargé");

document.addEventListener("DOMContentLoaded", function() {
    const themeToggle = document.getElementById("theme-toggle");
    const themeIcon = document.getElementById("theme-icon");
    const html = document.documentElement;
    
    if (!themeToggle || !themeIcon) {
        console.error("Éléments theme-toggle introuvables");
        return;
    }
    
    // Charger le thème sauvegardé (dark par défaut)
    const savedTheme = localStorage.getItem("theme") || "dark";
    
    // Fonction pour appliquer le thème
    function applyTheme(theme) {
        if (theme === "light") {
            html.classList.add("light-mode");
            // Icône lune pour mode light
            themeIcon.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
                </svg>
            `;
        } else {
            html.classList.remove("light-mode");
            // Icône soleil pour mode dark
            themeIcon.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="4"/>
                    <path d="M12 2v2"/>
                    <path d="M12 20v2"/>
                    <path d="m4.93 4.93 1.41 1.41"/>
                    <path d="m17.66 17.66 1.41 1.41"/>
                    <path d="M2 12h2"/>
                    <path d="M20 12h2"/>
                    <path d="m6.34 17.66-1.41 1.41"/>
                    <path d="m19.07 4.93-1.41 1.41"/>
                </svg>
            `;
        }
    }
    
    // Appliquer le thème au chargement
    applyTheme(savedTheme);
    
    // Toggle au clic
    themeToggle.addEventListener("click", function() {
        const currentTheme = html.classList.contains("light-mode") ? "light" : "dark";
        const newTheme = currentTheme === "dark" ? "light" : "dark";
        
        localStorage.setItem("theme", newTheme);
        applyTheme(newTheme);
    });
    
    console.log("Theme toggle initialisé");
});
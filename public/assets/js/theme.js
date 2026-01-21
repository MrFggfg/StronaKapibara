function setTheme(theme) {
    document.documentElement.className = theme;
    localStorage.setItem('theme', theme);
}

document.addEventListener("DOMContentLoaded", () => {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        document.documentElement.className = savedTheme;
    }
});

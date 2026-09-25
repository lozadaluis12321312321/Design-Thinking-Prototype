// theme.js
document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('theme-toggle');
    if (!toggleBtn) return;

    // Check for saved theme
    const savedTheme = localStorage.getItem('oneOpticsTheme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        toggleBtn.innerHTML = '☀️';
    } else {
        toggleBtn.innerHTML = '🌙';
    }

    toggleBtn.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        if (document.body.classList.contains('dark-mode')) {
            localStorage.setItem('oneOpticsTheme', 'dark');
            toggleBtn.innerHTML = '☀️';
        } else {
            localStorage.setItem('oneOpticsTheme', 'light');
            toggleBtn.innerHTML = '🌙';
        }
    });
});

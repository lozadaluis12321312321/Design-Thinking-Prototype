// theme.js
document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('theme-toggle');
    if (!toggleBtn) return;

    const iconEl = toggleBtn.querySelector('.tt-icon') || toggleBtn;
    const labelEl = toggleBtn.querySelector('.tt-label');
    const render = (dark) => {
        iconEl.textContent = dark ? '☀️' : '🌙';
        if (labelEl) labelEl.textContent = dark ? 'Light mode' : 'Dark mode';
    };

    // Check for saved theme
    const savedTheme = localStorage.getItem('oneOpticsTheme');
    if (savedTheme === 'dark') {
        document.documentElement.classList.add('dark-mode');
        document.body.classList.add('dark-mode');
        render(true);
    } else {
        document.documentElement.classList.remove('dark-mode');
        document.body.classList.remove('dark-mode');
        render(false);
    }

    toggleBtn.addEventListener('click', (e) => {
        e.preventDefault();
        
        const isDark = document.body.classList.contains('dark-mode');
        
        if (isDark) {
            document.documentElement.classList.remove('dark-mode');
            document.body.classList.remove('dark-mode');
            localStorage.setItem('oneOpticsTheme', 'light');
            render(false);
        } else {
            document.documentElement.classList.add('dark-mode');
            document.body.classList.add('dark-mode');
            localStorage.setItem('oneOpticsTheme', 'dark');
            render(true);
        }
    });
});

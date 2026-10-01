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
        document.body.classList.add('dark-mode');
        render(true);
    } else {
        render(false);
    }

    toggleBtn.addEventListener('click', (e) => {
        e.preventDefault();
        document.body.classList.toggle('dark-mode');
        if (document.body.classList.contains('dark-mode')) {
            localStorage.setItem('oneOpticsTheme', 'dark');
            render(true);
        } else {
            localStorage.setItem('oneOpticsTheme', 'light');
            render(false);
        }
    });
});

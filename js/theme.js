// theme.js
document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('theme-toggle');
    if (!toggleBtn) return;

    const iconEl = toggleBtn.querySelector('.tt-icon') || toggleBtn;
    const labelEl = toggleBtn.querySelector('.tt-label');
    const SUN = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>';
    const MOON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>';
    const useSvg = toggleBtn.classList.contains('theme-toggle');
    const render = (dark) => {
        toggleBtn.setAttribute('aria-pressed', String(dark));
        if (useSvg) iconEl.innerHTML = dark ? SUN : MOON;
        else iconEl.textContent = dark ? '☀️' : '🌙';
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

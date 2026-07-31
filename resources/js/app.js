import Alpine from 'alpinejs';

/*
 * The theme the visitor picked, kept in local storage so it survives a reload.
 * The `.dark` class on <html> is what every token in app.css reads, and the
 * inline script in partials/meta sets it before the first paint so there is no
 * flash of the wrong theme. This store only has to keep the two in sync.
 */
Alpine.store('theme', {
    dark: document.documentElement.classList.contains('dark'),

    set(dark) {
        this.dark = dark;
        document.documentElement.classList.toggle('dark', dark);

        try {
            localStorage.setItem('theme', dark ? 'dark' : 'light');
        } catch (e) {
            // Local storage is unavailable in private mode on some browsers.
        }
    },

    toggle() {
        this.set(! this.dark);
    },
});

window.Alpine = Alpine;

Alpine.start();

/**
 * ScholarVault Security Deterrents
 * 
 * This script implements common deterrents to hinder casual inspection of the source code.
 * Note: These are deterrents and not absolute security measures.
 */

// 1. Disable Right Click (Context Menu)
document.addEventListener('contextmenu', (e) => {
    e.preventDefault();
}, false);

// 2. Disable Keyboard Shortcuts for Inspect/Source
document.addEventListener('keydown', (e) => {
    // Disable F12
    if (e.key === 'F12') {
        e.preventDefault();
    }
    // Disable Ctrl+Shift+I (Inspect), Ctrl+Shift+J (Console), Ctrl+Shift+C (Elements), Ctrl+U (View Source)
    if (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'J' || e.key === 'C')) {
        e.preventDefault();
    }
    if (e.ctrlKey && e.key.toLowerCase() === 'u') {
        e.preventDefault();
    }
}, false);

// 3. Debugger Guard (Anti-Development Tools)
// This infinite debugger will pause execution if DevTools is opened.
(function () {
    const detectDevTools = () => {
        const start = new Date();
        debugger;
        const end = new Date();
        if (end - start > 100) {
            // DevTools is likely open
            document.body.innerHTML = '<div style="display:flex; justify-content:center; align-items:center; height:100vh; font-family:sans-serif; text-align:center; padding: 2rem;">' +
                '<div><h1 style="color:#ef4444;">Security Alert</h1>' +
                '<p>Inspection of this system is prohibited. Please close developer tools and refresh.</p></div></div>';
        }
    };
    
    // Check every second
    setInterval(detectDevTools, 1000);
})();

// 4. Console Wipe
setInterval(() => {
    console.clear();
}, 2000);

// Disable drag to prevent saving assets easily
document.addEventListener('dragstart', (e) => {
    e.preventDefault();
});

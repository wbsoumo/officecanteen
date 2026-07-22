/**
 * Office Canteen Core JS Helper Library
 */

document.addEventListener('DOMContentLoaded', () => {
    // Initialize Theme
    initTheme();
});

/**
 * Toast Notification system
 */
function showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `flex items-center p-4 rounded-2xl shadow-lg border transform translate-y-2 opacity-0 transition-all duration-300 ease-out max-w-sm glass`;
    
    // Set border and background style based on toast type
    let icon = 'fa-check-circle text-green-600';
    let border = 'border-green-100 dark:border-green-900/30';
    if (type === 'error') {
        icon = 'fa-exclamation-circle text-red-500';
        border = 'border-red-100 dark:border-red-900/30';
    } else if (type === 'warning') {
        icon = 'fa-exclamation-triangle text-amber-500';
        border = 'border-amber-100 dark:border-amber-900/30';
    } else if (type === 'info') {
        icon = 'fa-info-circle text-blue-500';
        border = 'border-blue-100 dark:border-blue-900/30';
    }

    toast.classList.add(border);
    toast.innerHTML = `
        <div class="flex-shrink-0 mr-3">
            <i class="fas ${icon} text-xl"></i>
        </div>
        <div class="flex-grow text-sm font-semibold text-slate-800 dark:text-slate-200">
            ${message}
        </div>
        <button class="ml-4 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 focus:outline-none" onclick="this.parentElement.remove()">
            <i class="fas fa-times text-xs"></i>
        </button>
    `;

    container.appendChild(toast);

    // Slide up and fade in
    setTimeout(() => {
        toast.classList.remove('translate-y-2', 'opacity-0');
    }, 10);

    // Auto dismiss after 4 seconds
    setTimeout(() => {
        toast.classList.add('translate-y-2', 'opacity-0');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 4000);
}

/**
 * Initialize Dark Mode State
 */
function initTheme() {
    const isDark = localStorage.getItem('theme') === 'dark' || 
                  (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches);
    
    if (isDark) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
}

/**
 * Toggle Dark Mode
 */
function toggleDarkMode() {
    if (document.documentElement.classList.contains('dark')) {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
        showToast('Switched to Light Mode', 'info');
    } else {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
        showToast('Switched to Dark Mode', 'info');
    }
}

/**
 * API Fetch wrapper with status toast messages and loading callback Support
 */
async function apiRequest(url, options = {}, showLoader = false) {
    if (showLoader) {
        // Toggle global body overlay spinner
        let loader = document.getElementById('global-loader');
        if (loader) loader.classList.remove('hidden');
    }

    try {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                ...options.headers
            },
            ...options
        });

        const data = await response.json();
        
        if (showLoader) {
            let loader = document.getElementById('global-loader');
            if (loader) loader.classList.add('hidden');
        }

        if (!response.ok || data.status === 'error') {
            throw new Error(data.message || `API error: ${response.status}`);
        }

        return data;
    } catch (error) {
        if (showLoader) {
            let loader = document.getElementById('global-loader');
            if (loader) loader.classList.add('hidden');
        }
        showToast(error.message || 'Something went wrong', 'error');
        throw error;
    }
}

/**
 * Beautiful dynamic Confirmation Dialog helper
 */
function showConfirm(title, message, confirmBtnText = 'Confirm', type = 'info') {
    return new Promise((resolve) => {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in';
        
        let accentColor = 'bg-green-600 hover:bg-green-700';
        let iconClass = 'fa-check text-green-600 bg-green-50';
        if (type === 'danger') {
            accentColor = 'bg-red-600 hover:bg-red-700';
            iconClass = 'fa-exclamation-triangle text-red-600 bg-red-50';
        }

        modal.innerHTML = `
            <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 max-w-sm w-full shadow-2xl border border-slate-100 dark:border-slate-700 transform scale-95 opacity-0 transition-all duration-200 animate-pop-in">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="p-2 rounded-full ${iconClass.split(' ').slice(1).join(' ')}">
                        <i class="fas ${iconClass.split(' ')[0]} text-lg"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100">${title}</h3>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-6">${message}</p>
                <div class="flex justify-end space-x-3">
                    <button id="confirm-cancel" class="px-4 py-2 text-sm font-semibold rounded-2xl text-slate-500 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                    <button id="confirm-ok" class="px-4 py-2 text-sm font-semibold text-white rounded-2xl ${accentColor} transition-colors">
                        ${confirmBtnText}
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Animate modal pop
        setTimeout(() => {
            const inner = modal.querySelector('.transform');
            inner.classList.remove('scale-95', 'opacity-0');
        }, 10);

        const cleanUp = (value) => {
            modal.remove();
            resolve(value);
        };

        modal.querySelector('#confirm-ok').addEventListener('click', () => cleanUp(true));
        modal.querySelector('#confirm-cancel').addEventListener('click', () => cleanUp(false));
    });
}

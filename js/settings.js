// Settings Manager - Gente Vigente
class SettingsManager {
    constructor() {
        this.dropdown = document.getElementById('settingsDropdown');
        this.trigger = document.getElementById('settingsTrigger');
        this.menu = document.getElementById('settingsMenu');
        
        // Toggles
        this.animationsToggle = document.getElementById('animationsToggle');
        this.animationsSwitch = document.getElementById('animationsSwitch');
        
        // Settings state
        this.settings = {
            animations: true
        };
        
        this.init();
    }
    
    init() {
        // Check if elements exist
        if (!this.dropdown || !this.trigger || !this.menu) {
            console.warn('Settings dropdown elements not found');
            return;
        }
        
        // Load saved settings
        this.loadSettings();
        
        // Event listeners
        this.trigger.addEventListener('click', this.toggleDropdown.bind(this));
        
        if (this.animationsToggle) {
            this.animationsToggle.addEventListener('click', () => this.toggle('animations'));
        }
        
        // Reset button
        const resetButton = document.getElementById('resetSettings');
        if (resetButton) {
            resetButton.addEventListener('click', this.resetSettings.bind(this));
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', this.handleOutsideClick.bind(this));
        
        // Respetar prefers-reduced-motion del sistema
        this.checkSystemPreference();
        
        // Update UI
        this.updateUI();
        this.applySettings();
    }
    
    loadSettings() {
        try {
            const saved = localStorage.getItem('gv-settings');
            if (saved) {
                this.settings = { ...this.settings, ...JSON.parse(saved) };
            }
        } catch (error) {
            console.warn('Error loading settings:', error);
        }
    }
    
    saveSettings() {
        try {
            localStorage.setItem('gv-settings', JSON.stringify(this.settings));
        } catch (error) {
            console.warn('Error saving settings:', error);
        }
    }
    
    checkSystemPreference() {
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            // Si el sistema prefiere menos movimiento, ocultar el toggle
            if (this.dropdown) {
                this.dropdown.style.display = 'none';
            }
            this.settings.animations = false;
            this.applySettings();
        }
    }
    
    toggleDropdown() {
        if (!this.dropdown) return;
        
        this.dropdown.classList.toggle('active');
        
        // Accessibility
        const isOpen = this.dropdown.classList.contains('active');
        this.trigger.setAttribute('aria-expanded', isOpen);
    }
    
    toggle(setting) {
        if (!(setting in this.settings)) return;
        
        this.settings[setting] = !this.settings[setting];
        this.updateUI();
        this.saveSettings();
        this.applySettings();
        
        // Visual feedback
        const switchElement = document.getElementById(`${setting}Switch`);
        if (switchElement) {
            switchElement.style.transform = 'scale(0.95)';
            setTimeout(() => {
                switchElement.style.transform = '';
            }, 150);
        }
        
        // Show notification
        this.showNotification(
            this.settings[setting] ? 
            `${setting.charAt(0).toUpperCase() + setting.slice(1)} activadas` : 
            `${setting.charAt(0).toUpperCase() + setting.slice(1)} desactivadas`
        );
    }
    
    updateUI() {
        // Update animations toggle switch
        if (this.animationsSwitch) {
            this.animationsSwitch.classList.toggle('active', this.settings.animations);
        }
    }
    
    applySettings() {
        // Apply animations setting
        if (!this.settings.animations) {
            document.body.classList.add('animations-disabled');
        } else {
            document.body.classList.remove('animations-disabled');
        }
        
        // Notify other components about settings change
        window.dispatchEvent(new CustomEvent('settingsChanged', { 
            detail: this.settings 
        }));
        
        // Console log for debugging
        console.log('Settings applied:', this.settings);
    }
    
    resetSettings() {
        this.settings = {
            animations: true
        };
        
        this.updateUI();
        this.saveSettings();
        this.applySettings();
        
        // Show notification
        this.showNotification('Configuración restablecida');
    }
    
    handleOutsideClick(event) {
        if (!this.dropdown) return;
        
        if (!this.dropdown.contains(event.target)) {
            this.dropdown.classList.remove('active');
            this.trigger.setAttribute('aria-expanded', 'false');
        }
    }
    
    showNotification(message) {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.settings-notification');
        existingNotifications.forEach(notification => {
            if (document.body.contains(notification)) {
                document.body.removeChild(notification);
            }
        });
        
        const notification = document.createElement('div');
        notification.className = 'settings-notification';
        notification.style.cssText = `
            position: fixed;
            top: 90px;
            right: 20px;
            background: rgba(26, 26, 26, 0.95);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            font-family: 'Inter', sans-serif;
            z-index: 1002;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(199, 139, 66, 0.3);
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            pointer-events: none;
        `;
        
        notification.textContent = message;
        document.body.appendChild(notification);
        
        // Show
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Hide and remove
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
    
    // Public methods
    getSettings() {
        return { ...this.settings };
    }
    
    updateSetting(key, value) {
        if (key in this.settings) {
            this.settings[key] = value;
            this.updateUI();
            this.saveSettings();
            this.applySettings();
        }
    }
    
    isAnimationsEnabled() {
        return this.settings.animations;
    }
}

// Initialize settings manager when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.settingsManager = new SettingsManager();
});

// Listen for settings changes (for other components)
window.addEventListener('settingsChanged', function(event) {
    console.log('Settings changed:', event.detail);
});

// Public API for external use
window.GVSettings = {
    disable: () => window.settingsManager?.updateSetting('animations', false),
    enable: () => window.settingsManager?.updateSetting('animations', true),
    isEnabled: () => window.settingsManager?.isAnimationsEnabled() || false,
    getAll: () => window.settingsManager?.getSettings() || {}
};
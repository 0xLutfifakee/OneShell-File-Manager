// OneShell - JavaScript Functions
// Version: 1.2.0
// Optimized and cleaned version

// ======================
// GLOBAL VARIABLES
// ======================
let selectedItems = new Set();
let currentDir = '';
let dragOverTimeout = null;

// ======================
// INITIALIZATION
// ======================
document.addEventListener('DOMContentLoaded', initApp);

function initApp() {
    // Get current directory from URL
    const urlParams = new URLSearchParams(window.location.search);
    currentDir = urlParams.get('dir') || '';
    
    // Initialize all components
    initTooltips();
    setupModals();
    setupFileActions();
    setupUpload();
    setupKeyboardShortcuts();
    setupThemeToggle();
    createSakura();
    setupDragAndDrop();
    setupSystemInfo();
}

// ======================
// TOOLTIPS
// ======================
function initTooltips() {
    // Tooltips menggunakan title attribute native browser
    // Bisa ditambahkan library tooltip jika diperlukan
}

// ======================
// MODAL MANAGEMENT
// ======================
function setupModals() {
    // Close modals when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            hideModal(e.target.id);
        }
        if (e.target.classList.contains('modal-close')) {
            const modal = e.target.closest('.modal');
            if (modal) hideModal(modal.id);
        }
    });
    
    // Close modals with escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal[style*="flex"]').forEach(modal => {
                hideModal(modal.id);
            });
        }
    });
}

function showModal(modalId, focusInput = true) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    // Hide all other modals first
    document.querySelectorAll('.modal[style*="flex"]').forEach(m => {
        if (m.id !== modalId) hideModal(m.id);
    });
    
    modal.style.display = 'flex';
    modal.classList.add('fade-in');
    
    // Focus on first input if requested
    if (focusInput) {
        setTimeout(() => {
            const firstInput = modal.querySelector('input, textarea, select');
            if (firstInput) firstInput.focus();
        }, 150);
    }
}

function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    modal.classList.remove('fade-in');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 150);
}

// ======================
// FILE SELECTION SYSTEM
// ======================
function setupFileActions() {
    // File selection with checkboxes
    document.addEventListener('change', handleFileCheckboxChange);
    
    // Select all checkbox
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', handleSelectAll);
    }
}

function handleFileCheckboxChange(e) {
    if (!e.target.classList.contains('file-checkbox')) return;
    
    const checkbox = e.target;
    const filename = checkbox.value;
    
    if (checkbox.checked) {
        selectedItems.add(filename);
    } else {
        selectedItems.delete(filename);
    }
    
    updateSelectionUI();
}

function handleSelectAll(e) {
    const checkboxes = document.querySelectorAll('.file-checkbox');
    const isChecked = e.target.checked;
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = isChecked;
        const filename = checkbox.value;
        
        if (isChecked) {
            selectedItems.add(filename);
        } else {
            selectedItems.delete(filename);
        }
    });
    
    updateSelectionUI();
}

function updateSelectionUI() {
    updateSelectAllCheckbox();
    updateBatchActions();
}

function updateSelectAllCheckbox() {
    const selectAll = document.getElementById('selectAll');
    if (!selectAll) return;
    
    const checkboxes = document.querySelectorAll('.file-checkbox');
    const checkedCount = document.querySelectorAll('.file-checkbox:checked').length;
    const totalCount = checkboxes.length;
    
    if (checkedCount === 0) {
        selectAll.checked = false;
        selectAll.indeterminate = false;
    } else if (checkedCount === totalCount) {
        selectAll.checked = true;
        selectAll.indeterminate = false;
    } else {
        selectAll.checked = false;
        selectAll.indeterminate = true;
    }
}

function updateBatchActions() {
    const batchActions = document.querySelector('.batch-actions');
    
    if (selectedItems.size > 0) {
        if (!batchActions) {
            createBatchActions();
        } else {
            batchActions.querySelector('span').textContent = `🌸 ${selectedItems.size} item(s) selected`;
        }
    } else if (batchActions) {
        batchActions.remove();
    }
}

function createBatchActions() {
    // Remove existing batch actions if any
    const existing = document.querySelector('.batch-actions');
    if (existing) existing.remove();
    
    const batchActions = document.createElement('div');
    batchActions.className = 'batch-actions fade-in';
    batchActions.innerHTML = `
        <span>🌸 ${selectedItems.size} item(s) selected</span>
        <button class="btn btn-sm" onclick="downloadSelected()">
            <i class="fas fa-download"></i> Download
        </button>
        <button class="btn btn-sm btn-danger" onclick="deleteSelected()">
            <i class="fas fa-trash"></i> Delete
        </button>
        <button class="btn btn-sm btn-warning" onclick="clearSelection()">
            <i class="fas fa-times"></i> Clear
        </button>
    `;
    
    document.body.appendChild(batchActions);
}

// ======================
// BATCH OPERATIONS
// ======================
function downloadSelected() {
    if (selectedItems.size === 0) {
        showMessage('Please select items first!', 'warning');
        return;
    }
    
    if (selectedItems.size === 1) {
        const filename = Array.from(selectedItems)[0];
        downloadFile(filename);
    } else {
        showMessage('Multiple file download coming soon!', 'info');
        // TODO: Implement zip download
    }
}

function deleteSelected() {
    if (selectedItems.size === 0) {
        showMessage('Please select items first!', 'warning');
        return;
    }
    
    if (confirm(`Delete ${selectedItems.size} selected item(s)?`)) {
        submitBatchAction('batch_delete', Array.from(selectedItems));
    }
}

function clearSelection() {
    selectedItems.clear();
    document.querySelectorAll('.file-checkbox').forEach(cb => {
        cb.checked = false;
    });
    updateSelectionUI();
}

// ======================
// FILE OPERATIONS
// ======================
function renameItem(filename) {
    const renameNewName = document.getElementById('renameNewName');
    if (!renameNewName) return;
    
    document.getElementById('renameTarget').value = filename;
    renameNewName.value = filename;
    showModal('renameModal');
}

function editFile(filename) {
    fetch(`actions.php?action=get_content&file=${encodeURIComponent(filename)}&dir=${encodeURIComponent(currentDir)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('editTarget').value = filename;
                document.getElementById('editContent').value = data.content;
                showModal('editModal');
            } else {
                showMessage(data.message || 'Error loading file', 'error');
            }
        })
        .catch(error => {
            showMessage('Error loading file: ' + error, 'error');
        });
}

function changePermission(filename) {
    document.getElementById('chmodTarget').value = filename;
    showModal('chmodModal');
}

function moveItem(filename) {
    document.getElementById('moveTarget').value = filename;
    document.getElementById('moveAction').value = 'move';
    showModal('moveModal');
}

function copyItem(filename) {
    document.getElementById('moveTarget').value = filename;
    document.getElementById('moveAction').value = 'copy';
    showModal('moveModal');
}

function setPermission(perm) {
    document.getElementById('chmodPermission').value = perm;
}

function deleteItem(filename) {
    if (confirm(`Delete "${filename}"?`)) {
        submitForm('delete', { target: filename });
    }
}

function downloadFile(filename) {
    window.location.href = `actions.php?action=download&file=${encodeURIComponent(filename)}&dir=${encodeURIComponent(currentDir)}`;
}

function previewFile(filename) {
    const extension = filename.split('.').pop().toLowerCase();
    const imageExt = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    const textExt = ['txt', 'php', 'html', 'css', 'js', 'json', 'xml', 'md', 'log', 'csv'];
    
    if (imageExt.includes(extension)) {
        // Image preview
        document.getElementById('previewTitle').innerHTML = `<i class="fas fa-eye"></i> Preview: ${filename}`;
        document.getElementById('previewContent').innerHTML = `
            <img src="actions.php?action=preview&file=${encodeURIComponent(filename)}&dir=${encodeURIComponent(currentDir)}" 
                 class="preview-image" alt="${filename}"
                 onerror="this.onerror=null; this.src='data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 100 100\"><text y=\".9em\" font-size=\"90\">🖼️</text></svg>'">
        `;
        showModal('previewModal', false);
    } else if (textExt.includes(extension)) {
        // Text preview
        fetch(`actions.php?action=preview&file=${encodeURIComponent(filename)}&dir=${encodeURIComponent(currentDir)}`)
            .then(response => response.text())
            .then(content => {
                document.getElementById('previewTitle').innerHTML = `<i class="fas fa-eye"></i> Preview: ${filename}`;
                document.getElementById('previewContent').innerHTML = `
                    <pre class="preview-code">${escapeHtml(content)}</pre>
                `;
                showModal('previewModal', false);
            })
            .catch(() => {
                showMessage('Cannot preview this file', 'error');
            });
    } else {
        downloadFile(filename);
    }
}

// ======================
// UPLOAD SYSTEM
// ======================
function setupUpload() {
    const fileInput = document.getElementById('fileUpload');
    if (!fileInput) return;
    
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            showMessage(`Uploading ${this.files.length} file(s)...`, 'info');
            this.form.submit();
        }
    });
}

function handleFileUpload(files) {
    if (!files || files.length === 0) return;
    
    const formData = new FormData();
    formData.append('action', 'upload');
    formData.append('dir', currentDir);
    
    for (let i = 0; i < files.length; i++) {
        formData.append('files[]', files[i]);
    }
    
    fetch('actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message || `Uploaded ${files.length} file(s)`, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showMessage(data.message || 'Upload failed', 'error');
        }
    })
    .catch(error => {
        showMessage('Upload error: ' + error.message, 'error');
    });
}

// ======================
// KEYBOARD SHORTCUTS
// ======================
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Skip if user is typing in input/textarea
        if (e.target.matches('input, textarea, [contenteditable="true"]')) {
            return;
        }
        
        const key = e.key.toLowerCase();
        const ctrl = e.ctrlKey || e.metaKey;
        
        // Ctrl + A: Select all
        if (ctrl && key === 'a') {
            e.preventDefault();
            const selectAll = document.getElementById('selectAll');
            if (selectAll) selectAll.click();
        }
        
        // Delete: Delete selected
        if (key === 'delete') {
            e.preventDefault();
            deleteSelected();
        }
        
        // F2: Rename first selected
        if (key === 'f2') {
            e.preventDefault();
            const firstSelected = Array.from(selectedItems)[0];
            if (firstSelected) renameItem(firstSelected);
        }
        
        // Ctrl + N: New file
        if (ctrl && key === 'n') {
            e.preventDefault();
            quickNewFile();
        }
        
        // Ctrl + U: Upload
        if (ctrl && key === 'u') {
            e.preventDefault();
            quickUpload();
        }
        
        // Ctrl + D: Clear selection
        if (ctrl && key === 'd') {
            e.preventDefault();
            clearSelection();
        }
    });
}

// ======================
// THEME SYSTEM
// ======================
function setupThemeToggle() {
    // Load saved theme
    const savedTheme = localStorage.getItem('oneshell-theme');
    const body = document.body;
    
    if (savedTheme === 'dark') {
        body.classList.add('dark-theme');
        updateThemeIcon('sun');
    } else {
        localStorage.setItem('oneshell-theme', 'light');
    }
}

function toggleTheme() {
    const body = document.body;
    const isDark = body.classList.contains('dark-theme');
    
    if (isDark) {
        body.classList.remove('dark-theme');
        localStorage.setItem('oneshell-theme', 'light');
        updateThemeIcon('moon');
        showMessage('Switched to light theme 🌸', 'info');
    } else {
        body.classList.add('dark-theme');
        localStorage.setItem('oneshell-theme', 'dark');
        updateThemeIcon('sun');
        showMessage('Switched to dark theme 🌙', 'info');
    }
}

function updateThemeIcon(iconName) {
    const themeToggle = document.querySelector('.theme-toggle i');
    if (themeToggle) {
        themeToggle.className = `fas fa-${iconName}`;
    }
}

// ======================
// SAKURA EFFECT
// ======================
function createSakura() {
    // Skip if disabled
    if (localStorage.getItem('oneshell-sakura') === 'disabled') return;
    
    const sakuraEmojis = ['🌸', '💮', '🏵️', '🎐', '🎀', '✨', '⭐', '🌟'];
    const sakuraCount = 15;
    
    // Clear existing sakura
    document.querySelectorAll('.sakura').forEach(s => s.remove());
    
    for (let i = 0; i < sakuraCount; i++) {
        const sakura = document.createElement('div');
        sakura.className = 'sakura';
        sakura.textContent = sakuraEmojis[Math.floor(Math.random() * sakuraEmojis.length)];
        sakura.style.left = Math.random() * 100 + 'vw';
        sakura.style.animationDelay = Math.random() * 15 + 's';
        sakura.style.fontSize = (Math.random() * 15 + 10) + 'px';
        sakura.style.opacity = Math.random() * 0.15 + 0.05;
        sakura.style.animationDuration = (Math.random() * 15 + 20) + 's';
        
        // Click to remove individual sakura
        sakura.addEventListener('click', function() {
            this.remove();
        });
        
        document.body.appendChild(sakura);
    }
}

function toggleSakura() {
    const isEnabled = localStorage.getItem('oneshell-sakura') !== 'disabled';
    
    if (isEnabled) {
        localStorage.setItem('oneshell-sakura', 'disabled');
        document.querySelectorAll('.sakura').forEach(s => s.remove());
        showMessage('Sakura effect disabled', 'info');
    } else {
        localStorage.removeItem('oneshell-sakura');
        createSakura();
        showMessage('Sakura effect enabled 🌸', 'success');
    }
}

// ======================
// DRAG & DROP
// ======================
function setupDragAndDrop() {
    const dropZone = document.body;
    
    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.add('drag-over');
        
        // Clear any existing timeout
        if (dragOverTimeout) clearTimeout(dragOverTimeout);
    });
    
    dropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Delay removal to prevent flickering
        dragOverTimeout = setTimeout(() => {
            if (!e.relatedTarget || !dropZone.contains(e.relatedTarget)) {
                dropZone.classList.remove('drag-over');
            }
        }, 100);
    });
    
    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.remove('drag-over');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFileUpload(files);
        }
    });
}

// ======================
// SYSTEM INFO
// ======================
function setupSystemInfo() {
    // Pre-fetch system info on page load
    fetch('actions.php?action=system_info')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                localStorage.setItem('oneshell-system-info', JSON.stringify(data.info));
            }
        })
        .catch(() => {
            // Silently fail - we'll show cached or empty data
        });
}

function showSystemInfo() {
    const cachedInfo = localStorage.getItem('oneshell-system-info');
    const infoContent = document.getElementById('systemInfoContent');
    
    if (cachedInfo) {
        const info = JSON.parse(cachedInfo);
        infoContent.innerHTML = `
            <div class="info-grid">
                ${Object.entries(info).map(([key, value]) => `
                    <div class="info-item">
                        <strong>${key}:</strong>
                        <span>${value}</span>
                    </div>
                `).join('')}
            </div>
        `;
    } else {
        infoContent.innerHTML = '<p class="text-muted">System information not available.</p>';
    }
    
    showModal('systemInfoModal');
}

// ======================
// MESSAGE SYSTEM
// ======================
function showMessage(text, type = 'info') {
    const container = document.querySelector('.messages-container') || createMessageContainer();
    
    const message = document.createElement('div');
    message.className = `message message-${type} fade-in`;
    
    const icons = {
        'info': 'ℹ️',
        'success': '✅',
        'error': '❌',
        'warning': '⚠️'
    };
    
    message.innerHTML = `
        <span class="message-icon">${icons[type] || icons.info}</span>
        <span class="message-text">${text}</span>
        <small>${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</small>
    `;
    
    container.appendChild(message);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        message.style.opacity = '0';
        setTimeout(() => message.remove(), 300);
    }, 5000);
}

function createMessageContainer() {
    const container = document.createElement('div');
    container.className = 'messages-container';
    document.body.appendChild(container);
    return container;
}

// ======================
// UTILITY FUNCTIONS
// ======================
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function submitForm(action, data = {}) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'actions.php';
    
    let html = `
        <input type="hidden" name="action" value="${action}">
        <input type="hidden" name="dir" value="${encodeURIComponent(currentDir)}">
    `;
    
    Object.entries(data).forEach(([key, value]) => {
        html += `<input type="hidden" name="${key}" value="${encodeURIComponent(value)}">`;
    });
    
    form.innerHTML = html;
    document.body.appendChild(form);
    form.submit();
}

function submitBatchAction(action, items) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'actions.php';
    
    let html = `
        <input type="hidden" name="action" value="${action}">
        <input type="hidden" name="dir" value="${encodeURIComponent(currentDir)}">
    `;
    
    items.forEach(item => {
        html += `<input type="hidden" name="selected_items[]" value="${encodeURIComponent(item)}">`;
    });
    
    form.innerHTML = html;
    document.body.appendChild(form);
    form.submit();
}

// ======================
// QUICK FUNCTIONS (Global)
// ======================
window.quickNewFile = function() {
    const newFileName = document.getElementById('newFileName');
    if (newFileName) newFileName.value = '';
    
    const newFileContent = document.getElementById('newFileContent');
    if (newFileContent) newFileContent.value = '';
    
    const fileTemplate = document.getElementById('fileTemplate');
    if (fileTemplate) fileTemplate.value = '';
    
    showModal('newFileModal');
};

window.quickUpload = function() {
    showModal('quickUploadModal');
};

window.triggerFileUpload = function() {
    const fileInput = document.getElementById('fileUpload');
    if (fileInput) fileInput.click();
};

// ======================
// EXPORT FUNCTIONS TO GLOBAL SCOPE
// ======================
window.showModal = showModal;
window.hideModal = hideModal;
window.renameItem = renameItem;
window.editFile = editFile;
window.changePermission = changePermission;
window.moveItem = moveItem;
window.copyItem = copyItem;
window.switchToCopy = function() {
    document.getElementById('moveAction').value = 'copy';
};
window.setPermission = setPermission;
window.deleteItem = deleteItem;
window.downloadFile = downloadFile;
window.previewFile = previewFile;
window.downloadSelected = downloadSelected;
window.deleteSelected = deleteSelected;
window.clearSelection = clearSelection;
window.showMessage = showMessage;
window.toggleTheme = toggleTheme;
window.showSystemInfo = showSystemInfo;
window.toggleSakura = toggleSakura;
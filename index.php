<?php
// OneShell File Manager - Index
require_once 'config.php';
require_once 'functions.php';

// Get current directory
$current_dir = isset($_GET['dir']) ? BASE_PATH . '/' . ltrim($_GET['dir'], '/') : BASE_PATH;
$current_dir = realpath($current_dir) ?: BASE_PATH;

// Validate path security
if (!validate_path($current_dir)) {
    $current_dir = BASE_PATH;
}

// Handle file operations via actions.php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    require_once 'actions.php';
    exit;
}

// Scan current directory
$items = scan_directory($current_dir);

// Calculate stats
$total_files = 0;
$total_folders = 0;
$total_size = 0;

foreach ($items as $item) {
    if ($item['is_dir']) {
        $total_folders++;
    } else {
        $total_files++;
        $total_size += $item['size'];
    }
}

// Get disk usage
$disk_usage = get_disk_usage();

// Generate breadcrumb
$breadcrumbs = generate_breadcrumb($current_dir);

// Get relative path for URLs
$relative_path = str_replace(BASE_PATH, '', $current_dir);
$relative_path = ltrim($relative_path, '/');

// Store current dir in session for actions
$_SESSION['current_dir'] = $current_dir;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OneShell File Manager - Sakura Kawaii</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🌸</text></svg>">
    <style>
        .quick-btn.active {
            background: rgba(255, 255, 255, 0.4);
            transform: scale(1.1);
        }
        .dark-theme .quick-btn {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header fade-in">
        <div class="header-content">
            <div class="logo">
                <h1>OneShell <span class="version">v<?php echo ONESHELL_VERSION; ?></span></h1>
                <div class="current-path">
                    <i class="fas fa-folder"></i> 
                    <?php echo htmlspecialchars('/' . $relative_path ?: 'root'); ?>
                </div>
            </div>
            <div class="quick-actions">
                <button class="quick-btn" onclick="quickNewFile()" title="New File">
                    <i class="fas fa-file-alt"></i>
                </button>
                <button class="quick-btn" onclick="quickUpload()" title="Quick Upload">
                    <i class="fas fa-cloud-upload-alt"></i>
                </button>
                <button class="quick-btn" onclick="window.location.reload()" title="Refresh">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <button class="quick-btn theme-toggle" title="Toggle Theme" onclick="toggleTheme()">
                    <i class="fas fa-moon"></i>
                </button>
                <button class="quick-btn" onclick="showSystemInfo()" title="System Info">
                    <i class="fas fa-info-circle"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Messages Container -->
    <div class="messages-container">
        <?php show_messages(); ?>
    </div>

    <!-- Main Container -->
    <div class="container">
        <!-- Toolbar -->
        <div class="toolbar fade-in">
            <a href="?dir=<?php echo urlencode(dirname($relative_path)); ?>" class="btn">
                <i class="fas fa-arrow-up"></i> Up
            </a>
            
            <form id="uploadForm" method="POST" action="actions.php" enctype="multipart/form-data" class="upload-area">
                <input type="hidden" name="action" value="upload">
                <input type="hidden" name="dir" value="<?php echo htmlspecialchars($relative_path); ?>">
                <input type="file" name="files[]" multiple id="fileUpload" style="display: none;" 
                       onchange="handleFileUpload(this.files)">
                <button type="button" class="btn btn-success" onclick="triggerFileUpload()">
                    <i class="fas fa-upload"></i> Upload Files
                </button>
            </form>
            
            <button class="btn" onclick="showModal('mkdirModal')">
                <i class="fas fa-folder-plus"></i> New Folder
            </button>
            
            <button class="btn" onclick="showModal('newFileModal')">
                <i class="fas fa-file-alt"></i> New File
            </button>
            
            <button class="btn btn-info" onclick="showModal('searchModal')">
                <i class="fas fa-search"></i> Search
            </button>
            
            <button class="btn btn-warning" onclick="downloadSelected()">
                <i class="fas fa-download"></i> Download
            </button>
            
            <button class="btn btn-danger" onclick="deleteSelected()">
                <i class="fas fa-trash"></i> Delete
            </button>
        </div>

        <!-- Breadcrumb -->
        <nav class="breadcrumb fade-in">
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <a href="?dir=<?php echo urlencode($crumb['path']); ?>">
                    <?php echo htmlspecialchars($crumb['name']); ?>
                </a>
                <?php if ($index < count($breadcrumbs) - 1): ?>
                    <i class="fas fa-chevron-right"></i>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>

        <!-- File List -->
        <div class="file-list fade-in">
            <div class="file-list-header">
                <div>
                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                </div>
                <div>Name</div>
                <div>Size</div>
                <div>Modified</div>
                <div>Permissions</div>
                <div>Actions</div>
            </div>
            
            <?php if (empty($items)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📁</div>
                    <h3>Folder is empty</h3>
                    <p>Upload files or create a new folder to get started</p>
                    <div style="margin-top: 20px;">
                        <button class="btn" onclick="showModal('newFileModal')">
                            <i class="fas fa-file-alt"></i> Create First File
                        </button>
                        <button class="btn btn-success" onclick="triggerFileUpload()">
                            <i class="fas fa-upload"></i> Upload Files
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                <div class="file-item" data-name="<?php echo htmlspecialchars($item['name']); ?>" 
                     data-type="<?php echo $item['is_dir'] ? 'folder' : 'file'; ?>"
                     oncontextmenu="showContextMenu(event, this)">
                    
                    <div class="file-icon">
                        <?php echo $item['icon']; ?>
                    </div>
                    
                    <div class="file-name">
                        <input type="checkbox" class="file-checkbox" value="<?php echo htmlspecialchars($item['name']); ?>"
                               onchange="updateSelection(this)">
                        <?php if ($item['is_dir']): ?>
                            <a href="?dir=<?php echo urlencode($relative_path . '/' . $item['name']); ?>">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </a>
                        <?php else: ?>
                            <a href="#" onclick="previewFile('<?php echo htmlspecialchars($item['name']); ?>')" 
                               title="Click to preview">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="file-size">
                        <?php echo $item['is_dir'] ? '<em>Folder</em>' : format_size($item['size']); ?>
                    </div>
                    
                    <div class="file-modified">
                        <?php echo date('Y-m-d H:i', $item['modified']); ?>
                    </div>
                    
                    <div class="file-permissions">
                        <?php echo get_permission_string($item['permissions']); ?>
                    </div>
                    
                    <div class="file-actions">
                        <?php if (!$item['is_dir']): ?>
                            <button class="action-btn" onclick="downloadFile('<?php echo htmlspecialchars($item['name']); ?>')" 
                                    title="Download">
                                <i class="fas fa-download"></i>
                            </button>
                            
                            <?php if (is_editable_file($item['name'])): ?>
                                <button class="action-btn" onclick="editFile('<?php echo htmlspecialchars($item['name']); ?>')" 
                                        title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                            <?php endif; ?>
                            
                            <button class="action-btn" onclick="previewFile('<?php echo htmlspecialchars($item['name']); ?>')" 
                                    title="Preview">
                                <i class="fas fa-eye"></i>
                            </button>
                        <?php endif; ?>
                        
                        <button class="action-btn" onclick="renameItem('<?php echo htmlspecialchars($item['name']); ?>')" 
                                title="Rename">
                            <i class="fas fa-i-cursor"></i>
                        </button>
                        
                        <button class="action-btn" onclick="moveItem('<?php echo htmlspecialchars($item['name']); ?>')" 
                                title="Move">
                            <i class="fas fa-arrows-alt"></i>
                        </button>
                        
                        <button class="action-btn" onclick="changePermission('<?php echo htmlspecialchars($item['name']); ?>')" 
                                title="Change Permission">
                            <i class="fas fa-lock"></i>
                        </button>
                        
                        <button class="action-btn delete-btn" 
                                onclick="deleteItem('<?php echo htmlspecialchars($item['name']); ?>')" 
                                title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Stats -->
        <div class="stats fade-in">
            <div class="stat-item">
                <span class="stat-value"><?php echo count($items); ?></span>
                <span class="stat-label">Total Items</span>
            </div>
            <div class="stat-item">
                <span class="stat-value"><?php echo $total_folders; ?></span>
                <span class="stat-label">Folders</span>
            </div>
            <div class="stat-item">
                <span class="stat-value"><?php echo $total_files; ?></span>
                <span class="stat-label">Files</span>
            </div>
            <div class="stat-item">
                <span class="stat-value"><?php echo format_size($total_size); ?></span>
                <span class="stat-label">Total Size</span>
            </div>
            <div class="stat-item">
                <span class="stat-value"><?php echo $disk_usage['used_percent']; ?>%</span>
                <span class="stat-label">Disk Usage</span>
            </div>
        </div>
    </div>

    <!-- ========== MODALS ========== -->
    
    <!-- New File Modal -->
    <div id="newFileModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-file-alt"></i> Create New File</h3>
            <form method="POST" action="actions.php" id="newFileForm">
                <input type="hidden" name="action" value="newfile">
                <input type="hidden" name="dir" value="<?php echo htmlspecialchars($relative_path); ?>">
                <div class="form-group">
                    <label>File Name:</label>
                    <input type="text" name="filename" required placeholder="example.php, style.css, script.js" id="newFileName">
                    <small class="file-hint">Include extension: .txt, .html, .php, .css, .js, .json, .xml</small>
                </div>
                <div class="form-group">
                    <label>File Content:</label>
                    <textarea name="content" rows="15" placeholder="Enter file content here..." id="newFileContent"></textarea>
                </div>
                <div class="form-group">
                    <label>Template:</label>
                    <select id="fileTemplate" onchange="loadTemplate(this.value)">
                        <option value="">-- Select Template --</option>
                        <option value="html">HTML Document</option>
                        <option value="php">PHP Script</option>
                        <option value="css">CSS Stylesheet</option>
                        <option value="js">JavaScript</option>
                        <option value="json">JSON File</option>
                        <option value="xml">XML File</option>
                        <option value="sql">SQL File</option>
                        <option value="txt">Text File</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn btn-success">Create File</button>
                    <button type="button" class="btn btn-danger" onclick="hideModal('newFileModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Upload Modal -->
    <div id="quickUploadModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-cloud-upload-alt"></i> Quick Upload</h3>
            <div class="upload-dropzone" id="uploadDropzone">
                <div class="dropzone-content">
                    <i class="fas fa-cloud-upload-alt fa-3x"></i>
                    <h4>Drop files here</h4>
                    <p>or click to select files</p>
                    <input type="file" id="quickUploadInput" multiple style="display: none;">
                </div>
                <div class="upload-progress" id="quickUploadProgress" style="display: none;">
                    <div class="progress-bar">
                        <div class="progress-fill" id="uploadProgressFill"></div>
                    </div>
                    <div class="progress-text" id="uploadProgressText">0%</div>
                </div>
                <div class="upload-queue" id="uploadQueue"></div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-success" onclick="startUpload()" id="startUploadBtn" style="display: none;">Upload All</button>
                <button type="button" class="btn btn-danger" onclick="hideModal('quickUploadModal')">Cancel</button>
            </div>
        </div>
    </div>

    <!-- New Folder Modal -->
    <div id="mkdirModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-folder-plus"></i> Create New Folder</h3>
            <form method="POST" action="actions.php">
                <input type="hidden" name="action" value="mkdir">
                <input type="hidden" name="dir" value="<?php echo htmlspecialchars($relative_path); ?>">
                <div class="form-group">
                    <label>Folder Name:</label>
                    <input type="text" name="folder_name" required placeholder="Enter folder name">
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn btn-success">Create</button>
                    <button type="button" class="btn btn-danger" onclick="hideModal('mkdirModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Rename Modal -->
    <div id="renameModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-i-cursor"></i> Rename Item</h3>
            <form method="POST" action="actions.php">
                <input type="hidden" name="action" value="rename">
                <input type="hidden" name="dir" value="<?php echo htmlspecialchars($relative_path); ?>">
                <input type="hidden" id="renameTarget" name="target" value="">
                <div class="form-group">
                    <label>New Name:</label>
                    <input type="text" id="renameNewName" name="new_name" required>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn btn-success">Rename</button>
                    <button type="button" class="btn btn-danger" onclick="hideModal('renameModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit File Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-edit"></i> Edit File</h3>
            <form method="POST" action="actions.php">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="dir" value="<?php echo htmlspecialchars($relative_path); ?>">
                <input type="hidden" id="editTarget" name="target" value="">
                <div class="form-group">
                    <label>File Content:</label>
                    <textarea id="editContent" name="content" rows="20" style="font-family: monospace;"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn btn-success">Save</button>
                    <button type="button" class="btn btn-danger" onclick="hideModal('editModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Permission Modal -->
    <div id="chmodModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-lock"></i> Change Permission</h3>
            <form method="POST" action="actions.php">
                <input type="hidden" name="action" value="chmod">
                <input type="hidden" name="dir" value="<?php echo htmlspecialchars($relative_path); ?>">
                <input type="hidden" id="chmodTarget" name="target" value="">
                <div class="form-group">
                    <label>Permission (octal):</label>
                    <input type="text" id="chmodPermission" name="permission" value="644" pattern="[0-7]{3,4}">
                    <div class="permission-examples">
                        <small>Common permissions:</small>
                        <div class="perm-buttons">
                            <button type="button" class="perm-btn" onclick="setPermission(755)">755 (Folder)</button>
                            <button type="button" class="perm-btn" onclick="setPermission(644)">644 (File)</button>
                            <button type="button" class="perm-btn" onclick="setPermission(777)">777 (Full)</button>
                            <button type="button" class="perm-btn" onclick="setPermission(600)">600 (Private)</button>
                        </div>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn btn-success">Change</button>
                    <button type="button" class="btn btn-danger" onclick="hideModal('chmodModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Move/Copy Modal -->
    <div id="moveModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-arrows-alt"></i> Move Item</h3>
            <form method="POST" action="actions.php">
                <input type="hidden" name="action" value="move" id="moveAction">
                <input type="hidden" name="dir" value="<?php echo htmlspecialchars($relative_path); ?>">
                <input type="hidden" id="moveTarget" name="target" value="">
                <div class="form-group">
                    <label>Destination Path:</label>
                    <input type="text" name="destination" placeholder="/path/to/destination" 
                           value="<?php echo htmlspecialchars($relative_path); ?>">
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn btn-success">Move</button>
                    <button type="button" class="btn btn-info" onclick="switchToCopy()">Copy Instead</button>
                    <button type="button" class="btn btn-danger" onclick="hideModal('moveModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Search Modal -->
    <div id="searchModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-search"></i> Search Files</h3>
            <form onsubmit="return searchFiles(event)">
                <div class="form-group">
                    <label>Search Term:</label>
                    <input type="text" id="searchTerm" placeholder="Enter filename or content" required>
                </div>
                <div class="form-group">
                    <label>Search In:</label>
                    <select id="searchType">
                        <option value="filename">Filename</option>
                        <option value="content">File Content</option>
                        <option value="both">Both</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="searchRecursive"> Search in subfolders
                    </label>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn btn-success">Search</button>
                    <button type="button" class="btn btn-danger" onclick="hideModal('searchModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Preview Modal -->
    <div id="previewModal" class="modal">
        <div class="modal-content">
            <h3 id="previewTitle"><i class="fas fa-eye"></i> Preview</h3>
            <div id="previewContent" class="preview-content">
                <!-- Preview content will be loaded here -->
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-danger" onclick="hideModal('previewModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- System Info Modal -->
    <div id="systemInfoModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-info-circle"></i> System Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">PHP Version:</span>
                    <span class="info-value"><?php echo PHP_VERSION; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Server Software:</span>
                    <span class="info-value"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Max Upload Size:</span>
                    <span class="info-value"><?php echo format_size(min(
                        return_bytes(ini_get('upload_max_filesize')),
                        return_bytes(ini_get('post_max_size')),
                        MAX_UPLOAD_SIZE
                    )); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Memory Limit:</span>
                    <span class="info-value"><?php echo ini_get('memory_limit'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Max Execution Time:</span>
                    <span class="info-value"><?php echo ini_get('max_execution_time'); ?>s</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Disk Total:</span>
                    <span class="info-value"><?php echo format_size($disk_usage['total']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Disk Used:</span>
                    <span class="info-value"><?php echo format_size($disk_usage['used']); ?> (<?php echo $disk_usage['used_percent']; ?>%)</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Disk Free:</span>
                    <span class="info-value"><?php echo format_size($disk_usage['free']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Server OS:</span>
                    <span class="info-value"><?php echo php_uname('s') . ' ' . php_uname('r'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Server IP:</span>
                    <span class="info-value"><?php echo $_SERVER['SERVER_ADDR'] ?? 'N/A'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Client IP:</span>
                    <span class="info-value"><?php echo $_SERVER['REMOTE_ADDR'] ?? 'N/A'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">OneShell Version:</span>
                    <span class="info-value">v<?php echo ONESHELL_VERSION; ?></span>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-danger" onclick="hideModal('systemInfoModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p>OneShell File Manager v<?php echo ONESHELL_VERSION; ?> • Lutfifakee • PHP <?php echo PHP_VERSION; ?></p>
        <p>🌸 Dibuat dengan cinta untuk pengelolaan file yang mudah dan menyenangkan</p>
    </footer>

    <!-- JavaScript -->
    <script src="script.js"></script>
    <script>
        // Global variables
        const currentDir = "<?php echo $relative_path; ?>";
        const selectedItems = new Set();
        let uploadQueue = [];
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            setupDragAndDrop();
            setupKeyboardShortcuts();
            setupThemeToggle();
            createSakura();
            initQuickUpload();
            initTemplates();
        });
        
        // New File Functions
        function quickNewFile() {
            document.getElementById('newFileName').value = '';
            document.getElementById('newFileContent').value = '';
            document.getElementById('fileTemplate').value = '';
            showModal('newFileModal');
        }
        
        function loadTemplate(template) {
            const contentArea = document.getElementById('newFileContent');
            const filenameInput = document.getElementById('newFileName');
            
            const templates = {
                'html': `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Document</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <h1>Hello World!</h1>
    <p>This is a new HTML document created with OneShell.</p>
</body>
</html>`,
                
                'php': `<?php
// New PHP File
echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>";
echo "    <title>PHP File</title>";
echo "</head>";
echo "<body>";
echo "    <h1>Hello from PHP!</h1>";
echo "    <p>Current time: " . date('Y-m-d H:i:s') . "</p>";
echo "    <p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "</body>";
echo "</html>";
?>`,
                
                'css': `/* New CSS File */
:root {
    --primary-color: #ff9eb5;
    --secondary-color: #ffd1dc;
    --text-color: #5a3d5c;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: var(--text-color);
    line-height: 1.6;
    background-color: #f9f9f9;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.header {
    background: linear-gradient(135deg, var(--primary-color) 0%, #ff6b93 100%);
    color: white;
    padding: 2rem;
    border-radius: 10px;
    margin-bottom: 2rem;
}

.btn {
    background: var(--primary-color);
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.3s ease;
}

.btn:hover {
    background: #ff6b93;
}`,
                
                'js': `// New JavaScript File
console.log('Hello from OneShell!');

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded');
    
    // Example function
    function showMessage(message, type = 'info') {
        const div = document.createElement('div');
        div.className = 'message message-' + type;
        div.textContent = message;
        document.body.appendChild(div);
        
        setTimeout(() => {
            div.remove();
        }, 3000);
    }
    
    // Example event listener
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('click', function() {
            showMessage('Button clicked!', 'success');
        });
    });
    
    // Fetch example
    function loadData() {
        fetch('/api/data')
            .then(response => response.json())
            .then(data => {
                console.log('Data loaded:', data);
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
});`,
                
                'json': `{
    "name": "new-config",
    "version": "1.0.0",
    "description": "Configuration file",
    "settings": {
        "theme": "sakura",
        "language": "en",
        "autoSave": true,
        "fontSize": 14
    },
    "features": {
        "upload": true,
        "edit": true,
        "preview": true,
        "search": true
    }
}`,
                
                'xml': `<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <app>
        <name>OneShell</name>
        <version>1.0.0</version>
        <author>OneShell Team</author>
    </app>
    <settings>
        <theme>sakura</theme>
        <language>en</language>
        <max_upload_size>100MB</max_upload_size>
    </settings>
</configuration>`,
                
                'sql': `-- New SQL File
-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create files table
CREATE TABLE files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    filename VARCHAR(255) NOT NULL,
    filepath TEXT NOT NULL,
    filesize INT,
    filetype VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert sample data
INSERT INTO users (username, email, password_hash) VALUES
('admin', 'admin@example.com', 'hashed_password'),
('user1', 'user1@example.com', 'hashed_password');`,
                
                'txt': `OneShell File Manager
====================

Created: ${new Date().toLocaleString()}
Location: /${currentDir}

Welcome to OneShell File Manager!

This is a new text file. You can use it for:
- Notes and documentation
- Configuration files
- Log entries
- TODO lists
- Project planning

Features:
✓ File management
✓ Code editing
✓ File preview
✓ Upload/download
✓ Security features

Enjoy using OneShell! 🌸`
            };
            
            if (template && templates[template]) {
                contentArea.value = templates[template];
                
                // Set suggested filename based on template
                const extensions = {
                    'html': 'index.html',
                    'php': 'script.php',
                    'css': 'style.css',
                    'js': 'app.js',
                    'json': 'config.json',
                    'xml': 'config.xml',
                    'sql': 'database.sql',
                    'txt': 'notes.txt'
                };
                
                if (extensions[template] && (!filenameInput.value || filenameInput.value === '')) {
                    filenameInput.value = extensions[template];
                }
            }
        }
        
        function initTemplates() {
            const templateSelect = document.getElementById('fileTemplate');
            if (templateSelect) {
                templateSelect.addEventListener('change', function() {
                    loadTemplate(this.value);
                });
            }
        }
        
        // Upload Functions
        function quickUpload() {
            uploadQueue = [];
            document.getElementById('uploadQueue').innerHTML = '';
            document.getElementById('quickUploadProgress').style.display = 'none';
            document.getElementById('startUploadBtn').style.display = 'none';
            showModal('quickUploadModal');
        }
        
        function triggerFileUpload() {
            document.getElementById('fileUpload').click();
        }
        
        function handleFileUpload(files) {
            if (files.length > 0) {
                // Add to upload queue
                for (let file of files) {
                    uploadQueue.push({
                        file: file,
                        status: 'pending',
                        progress: 0,
                        id: Date.now() + Math.random()
                    });
                }
                
                // Show upload modal and update queue
                showModal('quickUploadModal');
                updateUploadQueue();
            }
        }
        
        function initQuickUpload() {
            const dropzone = document.getElementById('uploadDropzone');
            const fileInput = document.getElementById('quickUploadInput');
            
            if (dropzone) {
                // Click to select files
                dropzone.addEventListener('click', function(e) {
                    if (e.target.closest('.queue-item')) return;
                    fileInput.click();
                });
                
                // Drag and drop
                dropzone.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.classList.add('drag-over');
                });
                
                dropzone.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.classList.remove('drag-over');
                });
                
                dropzone.addEventListener('drop', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.classList.remove('drag-over');
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        handleFileUpload(files);
                    }
                });
            }
            
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    if (this.files.length > 0) {
                        handleFileUpload(this.files);
                    }
                });
            }
        }
        
        function updateUploadQueue() {
            const queueElement = document.getElementById('uploadQueue');
            const uploadBtn = document.getElementById('startUploadBtn');
            
            if (!queueElement) return;
            
            queueElement.innerHTML = '';
            
            if (uploadQueue.length === 0) {
                uploadBtn.style.display = 'none';
                return;
            }
            
            uploadQueue.forEach((item, index) => {
                const queueItem = document.createElement('div');
                queueItem.className = `queue-item queue-${item.status}`;
                queueItem.innerHTML = `
                    <div class="queue-info">
                        <span class="queue-filename">${item.file.name}</span>
                        <span class="queue-size">${formatFileSize(item.file.size)}</span>
                    </div>
                    <div class="queue-status">
                        <span class="status-text">${item.status}</span>
                        ${item.status === 'uploading' ? `<div class="queue-progress" style="width: ${item.progress}%"></div>` : ''}
                    </div>
                    <button class="queue-remove" onclick="removeFromQueue(${index})" title="Remove">×</button>
                `;
                queueElement.appendChild(queueItem);
            });
            
            uploadBtn.style.display = 'block';
        }
        
        function removeFromQueue(index) {
            uploadQueue.splice(index, 1);
            updateUploadQueue();
        }
        
        function formatFileSize(bytes) {
            if (bytes >= 1073741824) {
                return (bytes / 1073741824).toFixed(2) + ' GB';
            } else if (bytes >= 1048576) {
                return (bytes / 1048576).toFixed(2) + ' MB';
            } else if (bytes >= 1024) {
                return (bytes / 1024).toFixed(2) + ' KB';
            } else {
                return bytes + ' bytes';
            }
        }
        
        async function startUpload() {
            if (uploadQueue.length === 0) return;
            
            const progressBar = document.getElementById('uploadProgressFill');
            const progressText = document.getElementById('uploadProgressText');
            const uploadProgress = document.getElementById('quickUploadProgress');
            
            uploadProgress.style.display = 'block';
            document.getElementById('startUploadBtn').disabled = true;
            
            let uploadedCount = 0;
            
            for (let i = 0; i < uploadQueue.length; i++) {
                const item = uploadQueue[i];
                
                if (item.status === 'pending') {
                    item.status = 'uploading';
                    updateUploadQueue();
                    
                    const formData = new FormData();
                    formData.append('action', 'quick_upload');
                    formData.append('dir', currentDir);
                    formData.append('file', item.file);
                    
                    try {
                        const response = await fetch('actions.php?ajax=1', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            item.status = 'completed';
                            item.progress = 100;
                            uploadedCount++;
                            showMessage(`Uploaded: ${item.file.name}`, 'success');
                        } else {
                            item.status = 'error';
                            showMessage(`Failed: ${item.file.name} - ${result.message}`, 'error');
                        }
                    } catch (error) {
                        item.status = 'error';
                        showMessage(`Error: ${item.file.name} - ${error.message}`, 'error');
                    }
                    
                    // Update overall progress
                    const progress = Math.round((uploadedCount / uploadQueue.length) * 100);
                    progressBar.style.width = progress + '%';
                    progressText.textContent = progress + '%';
                    
                    updateUploadQueue();
                }
            }
            
            document.getElementById('startUploadBtn').disabled = false;
            
            // Show completion message
            if (uploadedCount === uploadQueue.length) {
                showMessage(`Successfully uploaded ${uploadedCount} file(s)`, 'success');
                setTimeout(() => {
                    hideModal('quickUploadModal');
                    setTimeout(() => window.location.reload(), 500);
                }, 2000);
            }
        }
        
        // Theme Toggle Functions
        function toggleTheme() {
            const body = document.body;
            const themeToggle = document.querySelector('.theme-toggle i');
            
            if (body.classList.contains('dark-theme')) {
                body.classList.remove('dark-theme');
                themeToggle.className = 'fas fa-moon';
                localStorage.setItem('oneshell-theme', 'light');
                showMessage('Switched to light theme 🌸', 'info');
            } else {
                body.classList.add('dark-theme');
                themeToggle.className = 'fas fa-sun';
                localStorage.setItem('oneshell-theme', 'dark');
                showMessage('Switched to dark theme 🌙', 'info');
            }
        }
        
        function setupThemeToggle() {
            // Load saved theme
            const savedTheme = localStorage.getItem('oneshell-theme');
            const themeToggle = document.querySelector('.theme-toggle i');
            
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-theme');
                if (themeToggle) {
                    themeToggle.className = 'fas fa-sun';
                }
            }
        }
        
        // System Info Functions
        function showSystemInfo() {
            showModal('systemInfoModal');
        }
        
        // Clipboard paste support
        document.addEventListener('paste', function(e) {
            const items = e.clipboardData.items;
            const files = [];
            
            for (let item of items) {
                if (item.kind === 'file') {
                    const file = item.getAsFile();
                    if (file) {
                        files.push(file);
                    }
                }
            }
            
            if (files.length > 0) {
                e.preventDefault();
                handleFileUpload(files);
                showMessage(`Pasted ${files.length} file(s)`, 'info');
            }
        });
        
        // File operations
        function renameItem(filename) {
            document.getElementById('renameTarget').value = filename;
            document.getElementById('renameNewName').value = filename;
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
        
        function switchToCopy() {
            document.getElementById('moveAction').value = 'copy';
        }
        
        function setPermission(perm) {
            document.getElementById('chmodPermission').value = perm;
        }
        
        function deleteItem(filename) {
            if (confirm(`Delete "${filename}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'actions.php';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="target" value="${filename}">
                    <input type="hidden" name="dir" value="${currentDir}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function downloadFile(filename) {
            window.location.href = `actions.php?action=download&file=${encodeURIComponent(filename)}&dir=${encodeURIComponent(currentDir)}`;
        }
        
        function previewFile(filename) {
            const extension = filename.split('.').pop().toLowerCase();
            const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
            const textExtensions = ['txt', 'php', 'html', 'css', 'js', 'json', 'xml', 'md', 'log', 'csv'];
            
            if (imageExtensions.includes(extension)) {
                // Show image preview
                document.getElementById('previewTitle').innerHTML = `<i class="fas fa-eye"></i> Preview: ${filename}`;
                document.getElementById('previewContent').innerHTML = `
                    <img src="actions.php?action=preview&file=${encodeURIComponent(filename)}&dir=${encodeURIComponent(currentDir)}" 
                         class="preview-image" alt="${filename}"
                         onerror="this.onerror=null; this.src='data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 100 100\"><text y=\".9em\" font-size=\"90\">🖼️</text></svg>'">
                `;
                showModal('previewModal');
            } else if (textExtensions.includes(extension)) {
                // Show text preview
                fetch(`actions.php?action=preview&file=${encodeURIComponent(filename)}&dir=${encodeURIComponent(currentDir)}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.text();
                    })
                    .then(content => {
                        document.getElementById('previewTitle').innerHTML = `<i class="fas fa-eye"></i> Preview: ${filename}`;
                        document.getElementById('previewContent').innerHTML = `
                            <pre class="preview-code">${content}</pre>
                        `;
                        showModal('previewModal');
                    })
                    .catch(error => {
                        showMessage('Cannot preview this file', 'error');
                        downloadFile(filename);
                    });
            } else {
                // Try to download
                downloadFile(filename);
            }
        }
        
        // Selection handling
        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.file-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = checkbox.checked;
                updateSelection(cb);
            });
        }
        
        function updateSelection(checkbox) {
            if (checkbox.checked) {
                selectedItems.add(checkbox.value);
            } else {
                selectedItems.delete(checkbox.value);
            }
            updateBatchActions();
        }
        
        function updateBatchActions() {
            const batchActions = document.querySelector('.batch-actions');
            
            if (selectedItems.size > 0) {
                if (!batchActions) {
                    createBatchActions();
                } else {
                    batchActions.querySelector('span').textContent = `🌸 ${selectedItems.size} item selected`;
                }
            } else if (batchActions) {
                batchActions.remove();
            }
        }
        
        function createBatchActions() {
            const batchActions = document.createElement('div');
            batchActions.className = 'batch-actions';
            batchActions.innerHTML = `
                <span>🌸 ${selectedItems.size} item selected</span>
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
        
        function deleteSelected() {
            if (selectedItems.size === 0) {
                showMessage('Please select items first!', 'warning');
                return;
            }
            
            if (confirm(`Delete ${selectedItems.size} selected items?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'actions.php';
                
                let html = `
                    <input type="hidden" name="action" value="batch_delete">
                    <input type="hidden" name="dir" value="${currentDir}">
                `;
                
                selectedItems.forEach(item => {
                    html += `<input type="hidden" name="selected_items[]" value="${item}">`;
                });
                
                form.innerHTML = html;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function downloadSelected() {
            if (selectedItems.size === 0) {
                showMessage('Please select items first!', 'warning');
                return;
            }
            
            // For single file, download directly
            if (selectedItems.size === 1) {
                const filename = Array.from(selectedItems)[0];
                downloadFile(filename);
            } else {
                // For multiple files, create zip (future feature)
                showMessage('Multiple file download coming soon! Currently downloading first file only.', 'info');
                const filename = Array.from(selectedItems)[0];
                downloadFile(filename);
            }
        }
        
        function clearSelection() {
            selectedItems.clear();
            document.querySelectorAll('.file-checkbox').forEach(cb => {
                cb.checked = false;
            });
            updateBatchActions();
        }
        
        // Modal functions
        function showModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }
        
        function hideModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Search function
        function searchFiles(e) {
            e.preventDefault();
            const term = document.getElementById('searchTerm').value;
            const type = document.getElementById('searchType').value;
            const recursive = document.getElementById('searchRecursive').checked;
            
            // Implement search logic here
            showMessage(`Search for "${term}" (${type}, recursive: ${recursive}) - Coming soon!`, 'info');
            hideModal('searchModal');
            return false;
        }
        
        // Context menu
        function showContextMenu(event, fileItem) {
            event.preventDefault();
            // Context menu implementation
        }
        
        function setupDragAndDrop() {
            const dropZone = document.body;
            
            dropZone.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.add('drag-over');
            });
            
            dropZone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.remove('drag-over');
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
        
        function setupKeyboardShortcuts() {
            document.addEventListener('keydown', function(e) {
                // Ctrl + A: Select all
                if (e.ctrlKey && e.key === 'a') {
                    e.preventDefault();
                    const selectAll = document.getElementById('selectAll');
                    if (selectAll) {
                        selectAll.click();
                    }
                }
                
                // Delete: Delete selected
                if (e.key === 'Delete') {
                    e.preventDefault();
                    deleteSelected();
                }
                
                // F2: Rename first selected
                if (e.key === 'F2') {
                    e.preventDefault();
                    const firstSelected = Array.from(selectedItems)[0];
                    if (firstSelected) {
                        renameItem(firstSelected);
                    }
                }
                
                // Ctrl + N: New file
                if (e.ctrlKey && e.key === 'n') {
                    e.preventDefault();
                    quickNewFile();
                }
                
                // Ctrl + U: Upload
                if (e.ctrlKey && e.key === 'u') {
                    e.preventDefault();
                    quickUpload();
                }
                
                // F5: Refresh (allow default)
                // F12: Open dev tools (allow default)
            });
        }
        
        function createSakura() {
            const sakuraEmojis = ['🌸', '💮', '🏵️', '🎐', '🎀', '✨', '⭐', '🌟'];
            
            for (let i = 0; i < 15; i++) {
                const sakura = document.createElement('div');
                sakura.className = 'sakura';
                sakura.textContent = sakuraEmojis[Math.floor(Math.random() * sakuraEmojis.length)];
                sakura.style.left = Math.random() * 100 + 'vw';
                sakura.style.animationDelay = Math.random() * 15 + 's';
                sakura.style.fontSize = (Math.random() * 20 + 15) + 'px';
                sakura.style.opacity = Math.random() * 0.2 + 0.1;
                sakura.style.animationDuration = (Math.random() * 10 + 10) + 's';
                
                document.body.appendChild(sakura);
            }
        }
        
        function showMessage(text, type = 'info') {
            const container = document.querySelector('.messages-container') || createMessageContainer();
            
            const message = document.createElement('div');
            message.className = `message message-${type} fade-in`;
            
            let icon = 'ℹ️';
            switch (type) {
                case 'success': icon = '✅'; break;
                case 'error': icon = '❌'; break;
                case 'warning': icon = '⚠️'; break;
            }
            
            message.innerHTML = `
                <span class="message-icon">${icon}</span>
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
        
        // Export functions to global scope
        window.showModal = showModal;
        window.hideModal = hideModal;
        window.renameItem = renameItem;
        window.editFile = editFile;
        window.changePermission = changePermission;
        window.moveItem = moveItem;
        window.copyItem = copyItem;
        window.switchToCopy = switchToCopy;
        window.setPermission = setPermission;
        window.deleteItem = deleteItem;
        window.downloadFile = downloadFile;
        window.previewFile = previewFile;
        window.downloadSelected = downloadSelected;
        window.deleteSelected = deleteSelected;
        window.clearSelection = clearSelection;
        window.showMessage = showMessage;
        window.quickNewFile = quickNewFile;
        window.quickUpload = quickUpload;
        window.triggerFileUpload = triggerFileUpload;
        window.startUpload = startUpload;
        window.toggleTheme = toggleTheme;
        window.showSystemInfo = showSystemInfo;
    </script>
</body>
</html>
<?php
// OneShell Actions Handler

require_once 'config.php';
require_once 'functions.php';

// Set JSON header for AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
}

// Get current directory
$current_dir = isset($_GET['dir']) ? BASE_PATH . '/' . trim($_GET['dir'], '/') : BASE_PATH;
$current_dir = realpath($current_dir) ?: BASE_PATH;

// Validate path security
if (!validate_path($current_dir)) {
    $current_dir = BASE_PATH;
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $target = $_POST['target'] ?? '';
    $destination = $_POST['destination'] ?? '';
    
    switch ($action) {
        case 'rename':
            if (!empty($_POST['new_name']) && !empty($target)) {
                $old_path = $current_dir . '/' . sanitize_filename($target);
                $new_name = sanitize_filename($_POST['new_name']);
                $new_path = $current_dir . '/' . $new_name;
                
                if (validate_path($old_path) && validate_path($new_path) && file_exists($old_path)) {
                    if (rename($old_path, $new_path)) {
                        add_message('success', "Renamed '{$target}' to '{$new_name}'");
                    } else {
                        add_message('error', "Failed to rename '{$target}'");
                    }
                } else {
                    add_message('error', "Invalid path or file doesn't exist");
                }
            }
            break;
            
        case 'delete':
            if (!empty($target)) {
                $path = $current_dir . '/' . sanitize_filename($target);
                
                if (validate_path($path) && file_exists($path)) {
                    if (is_dir($path)) {
                        if (delete_directory($path)) {
                            add_message('success', "Folder '{$target}' deleted successfully");
                        } else {
                            add_message('error', "Failed to delete folder '{$target}'");
                        }
                    } else {
                        if (unlink($path)) {
                            add_message('success', "File '{$target}' deleted successfully");
                        } else {
                            add_message('error', "Failed to delete file '{$target}'");
                        }
                    }
                } else {
                    add_message('error', "Invalid path or file doesn't exist");
                }
            }
            break;
            
        case 'move':
            if (!empty($target) && !empty($destination)) {
                $source_path = $current_dir . '/' . sanitize_filename($target);
                $dest_dir = BASE_PATH . '/' . trim($destination, '/');
                $dest_path = $dest_dir . '/' . sanitize_filename($target);
                
                if (validate_path($source_path) && validate_path($dest_path) && file_exists($source_path)) {
                    if (rename($source_path, $dest_path)) {
                        add_message('success', "Moved '{$target}' to '{$destination}'");
                    } else {
                        add_message('error', "Failed to move '{$target}'");
                    }
                } else {
                    add_message('error', "Invalid path or file doesn't exist");
                }
            }
            break;
            
        case 'copy':
            if (!empty($target) && !empty($destination)) {
                $source_path = $current_dir . '/' . sanitize_filename($target);
                $dest_dir = BASE_PATH . '/' . trim($destination, '/');
                $dest_path = $dest_dir . '/' . sanitize_filename($target);
                
                if (validate_path($source_path) && validate_path($dest_path) && file_exists($source_path)) {
                    if (is_dir($source_path)) {
                        // Copy directory recursively
                        $iterator = new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator($source_path, RecursiveDirectoryIterator::SKIP_DOTS),
                            RecursiveIteratorIterator::SELF_FIRST
                        );
                        
                        @mkdir($dest_path, 0755, true);
                        
                        foreach ($iterator as $item) {
                            if ($item->isDir()) {
                                @mkdir($dest_path . '/' . $iterator->getSubPathName());
                            } else {
                                copy($item, $dest_path . '/' . $iterator->getSubPathName());
                            }
                        }
                        add_message('success', "Copied folder '{$target}' to '{$destination}'");
                    } else {
                        if (copy($source_path, $dest_path)) {
                            add_message('success', "Copied file '{$target}' to '{$destination}'");
                        } else {
                            add_message('error', "Failed to copy '{$target}'");
                        }
                    }
                } else {
                    add_message('error', "Invalid path or file doesn't exist");
                }
            }
            break;
            
        case 'mkdir':
            if (!empty($_POST['folder_name'])) {
                $folder_name = trim($_POST['folder_name']);
                
                // Debug
                if (defined('DEBUG') && DEBUG) {
                    error_log("Creating folder: $folder_name");
                    error_log("Current dir: $current_dir");
                }
                
                // Basic validation
                if (empty($folder_name) || $folder_name == '.' || $folder_name == '..') {
                    add_message('error', "Invalid folder name");
                    break;
                }
                
                // Sanitize folder name
                $folder_name = str_replace(['../', './', '/', '\\', ':', '*', '?', '"', '<', '>', '|'], '', $folder_name);
                $folder_name = trim($folder_name, '. ');
                
                // Jika masih kosong setelah sanitize
                if (empty($folder_name)) {
                    $folder_name = 'new_folder_' . time();
                }
                
                $new_dir = $current_dir . '/' . $folder_name;
                
                // Debug path
                if (defined('DEBUG') && DEBUG) {
                    error_log("New dir path: $new_dir");
                    error_log("BASE_PATH: " . BASE_PATH);
                }
                
                // Validasi path (gunakan versi yang sudah diperbaiki)
                // $valid = validate_path($new_dir);
                $valid = true; // Bypass sementara untuk testing
                
                if (!$valid) {
                    add_message('error', "Invalid folder name or path. Cannot create folder.");
                    break;
                }
                
                // Cek apakah folder sudah ada
                if (file_exists($new_dir)) {
                    add_message('error', "Folder '{$folder_name}' already exists");
                    break;
                }
                
                // Cek permission directory
                if (!is_writable($current_dir)) {
                    $perm = substr(sprintf('%o', fileperms($current_dir)), -4);
                    add_message('error', "Cannot create folder. Directory is not writable. Permission: $perm");
                    break;
                }
                
                // Coba buat folder
                if (mkdir($new_dir, 0755, true)) {
                    add_message('success', "Folder '{$folder_name}' created successfully");
                    
                    // Debug
                    if (defined('DEBUG') && DEBUG) {
                        error_log("Folder created successfully: $new_dir");
                    }
                } else {
                    add_message('error', "Failed to create folder '{$folder_name}'");
                    
                    // Debug error
                    if (defined('DEBUG') && DEBUG) {
                        error_log("mkdir() failed for: $new_dir");
                        error_log("Error: " . error_get_last()['message']);
                    }
                }
            } else {
                add_message('error', "Folder name is required");
            }
            break;
            
        case 'chmod':
            if (!empty($target) && !empty($_POST['permission'])) {
                $path = $current_dir . '/' . sanitize_filename($target);
                $permission = octdec($_POST['permission']);
                
                if (validate_path($path) && file_exists($path)) {
                    if (chmod($path, $permission)) {
                        add_message('success', "Changed permissions for '{$target}'");
                    } else {
                        add_message('error', "Failed to change permissions for '{$target}'");
                    }
                } else {
                    add_message('error', "Invalid path or file doesn't exist");
                }
            }
            break;
            
        case 'edit':
            if (!empty($target) && isset($_POST['content'])) {
                $path = $current_dir . '/' . sanitize_filename($target);
                
                if (validate_path($path) && file_exists($path) && is_writable($path)) {
                    // Buat backup sebelum edit
                    backup_file($path);
                    
                    if (file_put_contents($path, $_POST['content'])) {
                        add_message('success', "File '{$target}' saved successfully");
                    } else {
                        add_message('error', "Failed to save file '{$target}'");
                    }
                } else {
                    add_message('error', "File doesn't exist or is not writable");
                }
            }
            break;
            
        case 'newfile':
            if (!empty($_POST['filename']) && isset($_POST['content'])) {
                $filename = trim($_POST['filename']);
                
                // Basic validation
                if (empty($filename) || $filename == '.' || $filename == '..') {
                    add_message('error', "Invalid filename");
                    break;
                }
                
                // Sanitize filename (gunakan yang simple dulu)
                $filename = str_replace(['../', './', '/', '\\', ':', '*', '?', '"', '<', '>', '|'], '', $filename);
                $filename = trim($filename, '. ');
                
                // Jika masih kosong setelah sanitize
                if (empty($filename)) {
                    $filename = 'new_file_' . time() . '.txt';
                }
                
                // Tambahkan .txt jika tidak ada ekstensi
                if (strpos($filename, '.') === false) {
                    $filename .= '.txt';
                }
                
                $filepath = $current_dir . '/' . $filename;
                $content = $_POST['content'];
                
                // Debug
                if (defined('DEBUG') && DEBUG) {
                    error_log("Creating file: $filepath");
                    error_log("Current dir: $current_dir");
                    error_log("BASE_PATH: " . BASE_PATH);
                }
                
                // Skip path validation untuk testing (HATI-HATI!)
                // $valid = validate_path($filepath);
                $valid = true; // Bypass sementara untuk testing
                
                if (!$valid) {
                    add_message('error', "Invalid filename or path. Cannot create file.");
                    break;
                }
                
                // Cek apakah file sudah ada
                if (file_exists($filepath)) {
                    add_message('error', "File '{$filename}' already exists");
                    break;
                }
                
                // Coba buat file
                if (file_put_contents($filepath, $content) !== false) {
                    add_message('success', "File '{$filename}' created successfully");
                    
                    // Debug
                    if (defined('DEBUG') && DEBUG) {
                        error_log("File created successfully: $filepath");
                    }
                } else {
                    // Cek permission
                    if (!is_writable($current_dir)) {
                        add_message('error', "Cannot write to directory. Check permissions.");
                    } else {
                        add_message('error', "Failed to create file '{$filename}'");
                    }
                }
            } else {
                add_message('error', "Filename and content are required");
            }
            break;

        case 'upload':
            if (!empty($_FILES['files'])) {
                $uploaded = 0;
                $failed = 0;
                
                foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
                    $filename = $_FILES['files']['name'][$key];
                    $filepath = $current_dir . '/' . $filename;
                    
                    // Validasi ekstensi
                    if (!validate_extension($filename)) {
                        add_message('error', "Ekstensi file {$filename} tidak diizinkan");
                        $failed++;
                        continue;
                    }
                    
                    // Validasi ukuran
                    if ($_FILES['files']['size'][$key] > MAX_UPLOAD_SIZE) {
                        add_message('error', "File {$filename} terlalu besar");
                        $failed++;
                        continue;
                    }
                    
                    if (move_uploaded_file($tmp_name, $filepath)) {
                        $uploaded++;
                    } else {
                        $failed++;
                    }
                }
                
                if ($uploaded > 0) {
                    add_message('success', "{$uploaded} file berhasil di-upload");
                }
                if ($failed > 0) {
                    add_message('error', "{$failed} file gagal di-upload");
                }
            }
            break;
            
        case 'quick_upload':
            // Debug log untuk melihat apa yang diterima
            error_log("Quick upload called");
            error_log("Files received: " . print_r($_FILES, true));
            error_log("POST data: " . print_r($_POST, true));
            
            if (!empty($_FILES)) {
                // Tangani multiple files
                $uploaded_files = [];
                $failed_files = [];
                
                foreach ($_FILES as $key => $file) {
                    // Skip jika file kosong
                    if ($file['error'] == UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    
                    $filename = sanitize_filename($file['name']);
                    $filepath = $current_dir . '/' . $filename;
                    
                    // Validasi error upload
                    if ($file['error'] != UPLOAD_ERR_OK) {
                        $error_msg = get_upload_error($file['error']);
                        $failed_files[] = [
                            'name' => $filename,
                            'error' => $error_msg
                        ];
                        continue;
                    }
                    
                    // Validasi ekstensi
                    if (!validate_extension($filename)) {
                        $failed_files[] = [
                            'name' => $filename,
                            'error' => 'File type not allowed'
                        ];
                        continue;
                    }
                    
                    // Validasi ukuran
                    if ($file['size'] > MAX_UPLOAD_SIZE) {
                        $failed_files[] = [
                            'name' => $filename,
                            'error' => 'File too large (max ' . format_size(MAX_UPLOAD_SIZE) . ')'
                        ];
                        continue;
                    }
                    
                    // Cek jika file sudah ada
                    if (file_exists($filepath)) {
                        // Generate unique filename
                        $counter = 1;
                        $pathinfo = pathinfo($filename);
                        $extension = isset($pathinfo['extension']) ? '.' . $pathinfo['extension'] : '';
                        $basename = $pathinfo['filename'];
                        
                        while (file_exists($filepath)) {
                            $filename = $basename . '_' . $counter . $extension;
                            $filepath = $current_dir . '/' . $filename;
                            $counter++;
                        }
                    }
                    
                    // Upload file
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        // Set permission yang aman
                        chmod($filepath, 0644);
                        
                        $uploaded_files[] = [
                            'name' => $filename,
                            'size' => $file['size'],
                            'path' => $filepath
                        ];
                        
                        error_log("File uploaded successfully: " . $filename);
                    } else {
                        $failed_files[] = [
                            'name' => $filename,
                            'error' => 'Upload failed - move_uploaded_file returned false'
                        ];
                        error_log("Upload failed for: " . $filename);
                    }
                }
                
                // Response untuk AJAX
                if (isset($_GET['ajax'])) {
                    $response = [
                        'success' => true,
                        'uploaded' => count($uploaded_files),
                        'failed' => count($failed_files),
                        'uploaded_files' => $uploaded_files,
                        'failed_files' => $failed_files
                    ];
                    
                    if (count($uploaded_files) > 0) {
                        $response['message'] = count($uploaded_files) . ' file(s) uploaded successfully';
                    }
                    
                    if (count($failed_files) > 0) {
                        $response['message'] = count($uploaded_files) . ' uploaded, ' . count($failed_files) . ' failed';
                    }
                    
                    echo json_encode($response);
                    exit;
                } else {
                    // Response untuk non-AJAX
                    if (count($uploaded_files) > 0) {
                        add_message('success', count($uploaded_files) . ' file(s) uploaded successfully');
                    }
                    
                    foreach ($failed_files as $failed) {
                        add_message('error', $failed['name'] . ': ' . $failed['error']);
                    }
                }
            } else {
                // No files received
                if (isset($_GET['ajax'])) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'No files received'
                    ]);
                } else {
                    add_message('error', 'No files received');
                }
            }
            exit;
            break;
            
        case 'batch_delete':
            if (!empty($_POST['selected_items'])) {
                $deleted = 0;
                $failed = 0;
                
                foreach ($_POST['selected_items'] as $item) {
                    $path = $current_dir . '/' . sanitize_filename($item);
                    
                    if (validate_path($path) && file_exists($path)) {
                        if (is_dir($path)) {
                            if (delete_directory($path)) {
                                $deleted++;
                            } else {
                                $failed++;
                            }
                        } else {
                            if (unlink($path)) {
                                $deleted++;
                            } else {
                                $failed++;
                            }
                        }
                    }
                }
                
                if ($deleted > 0) {
                    add_message('success', "{$deleted} item(s) deleted successfully");
                }
                if ($failed > 0) {
                    add_message('error', "Failed to delete {$failed} item(s)");
                }
            }
            break;
    }
    
    // Redirect kembali ke halaman sebelumnya
    $redirect_url = 'index.php?dir=' . urlencode(str_replace(BASE_PATH . '/', '', $current_dir));
    if (isset($_GET['ajax'])) {
        echo json_encode(['success' => true, 'redirect' => $redirect_url]);
        exit;
    } else {
        header("Location: $redirect_url");
        exit;
    }
}

// Handle GET actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $target = $_GET['file'] ?? '';
    
    switch ($action) {
        case 'download':
            if (!empty($target)) {
                $path = $current_dir . '/' . sanitize_filename($target);
                
                if (validate_path($path) && file_exists($path) && !is_dir($path) && is_readable($path)) {
                    $filesize = filesize($path);
                    $filename = basename($path);
                    
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="' . $filename . '"');
                    header('Content-Transfer-Encoding: binary');
                    header('Content-Length: ' . $filesize);
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    header('Expires: 0');
                    
                    readfile($path);
                    exit;
                } else {
                    add_message('error', "File not found or not readable");
                    header('Location: index.php?dir=' . urlencode(str_replace(BASE_PATH . '/', '', $current_dir)));
                    exit;
                }
            }
            break;
            
        case 'preview':
            if (!empty($target)) {
                $path = $current_dir . '/' . sanitize_filename($target);
                
                if (validate_path($path) && file_exists($path) && !is_dir($path) && is_readable($path)) {
                    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                    
                    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
                        $mime = mime_content_type($path);
                        header('Content-Type: ' . $mime);
                        readfile($path);
                        exit;
                    } elseif (in_array($extension, ['txt', 'php', 'html', 'css', 'js', 'json', 'xml', 'md', 'log', 'csv'])) {
                        $content = get_file_preview($path, 500);
                        if ($content !== false) {
                            header('Content-Type: text/plain; charset=utf-8');
                            echo $content;
                            exit;
                        }
                    }
                }
            }
            break;
            
        case 'get_content':
            if (!empty($target)) {
                $path = $current_dir . '/' . sanitize_filename($target);
                
                if (validate_path($path) && file_exists($path) && !is_dir($path) && is_readable($path)) {
                    $content = get_file_content($path);
                    if ($content !== false) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => true,
                            'content' => $content
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Cannot read file'
                        ]);
                    }
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'File not found'
                    ]);
                }
                exit;
            }
            break;
            
        case 'view':
            if (!empty($target)) {
                $path = $current_dir . '/' . sanitize_filename($target);
                
                if (validate_path($path) && file_exists($path) && !is_dir($path) && is_readable($path)) {
                    $mime_type = mime_content_type($path);
                    header('Content-Type: ' . $mime_type);
                    readfile($path);
                    exit;
                }
            }
            break;
    }
}

// Default redirect if no action matched
header('Location: index.php?dir=' . urlencode(str_replace(BASE_PATH . '/', '', $current_dir)));
exit;
?>
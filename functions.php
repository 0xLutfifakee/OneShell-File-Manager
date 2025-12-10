<?php
// OneShell Functions - Lengkap

/**
 * Validasi path untuk keamanan
 */
function validate_path($path) {
    // Normalize path
    $path = realpath($path);
    if ($path === false) {
        return false;
    }
    
    // Cek jika path di luar BASE_PATH
    if (strpos($path, BASE_PATH) !== 0) {
        return false;
    }
    
    // Cek restricted patterns
    $restricted = ['..', './../', '../', '..\\', '.git', '.env', 'config.php', 'actions.php'];
    foreach ($restricted as $r) {
        if (strpos($path, $r) !== false) {
            return false;
        }
    }
    
    return true;
}

/**
 * Format ukuran file
 */
function format_size($bytes) {
    if ($bytes >= 1099511627776) {
        return number_format($bytes / 1099511627776, 2) . ' TB';
    } elseif ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return '1 byte';
    } else {
        return '0 bytes';
    }
}

/**
 * Dapatkan icon berdasarkan ekstensi file
 */
function get_file_icon($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    $icons = [
        // Images
        'jpg' => '🖼️', 'jpeg' => '🖼️', 'png' => '🖼️', 'gif' => '🖼️',
        'svg' => '🖼️', 'bmp' => '🖼️', 'webp' => '🖼️', 'ico' => '🖼️',
        'tiff' => '🖼️',
        
        // Documents
        'pdf' => '📄', 'doc' => '📄', 'docx' => '📄', 'odt' => '📄',
        'txt' => '📝', 'rtf' => '📝', 'md' => '📝',
        
        // Spreadsheets
        'xls' => '📊', 'xlsx' => '📊', 'csv' => '📊', 'ods' => '📊',
        
        // Presentations
        'ppt' => '📽️', 'pptx' => '📽️', 'odp' => '📽️',
        
        // Code
        'php' => '🐘', 'html' => '🌐', 'htm' => '🌐',
        'js' => '📜', 'css' => '🎨', 'json' => '📋',
        'xml' => '📋', 'sql' => '🗃️', 'py' => '🐍',
        'java' => '☕', 'cpp' => '⚙️', 'c' => '⚙️',
        'sh' => '🐚', 'bat' => '⚙️', 'ps1' => '🐚',
        
        // Archives
        'zip' => '📦', 'rar' => '📦', '7z' => '📦',
        'tar' => '📦', 'gz' => '📦', 'bz2' => '📦',
        
        // Video
        'mp4' => '🎬', 'avi' => '🎬', 'mkv' => '🎬',
        'mov' => '🎬', 'wmv' => '🎬', 'flv' => '🎬',
        'webm' => '🎬',
        
        // Audio
        'mp3' => '🎵', 'wav' => '🎵', 'ogg' => '🎵',
        'flac' => '🎵', 'm4a' => '🎵', 'aac' => '🎵',
        
        // Executables
        'exe' => '⚙️', 'msi' => '⚙️', 'apk' => '📱',
        'dmg' => '🍎',
        
        // Fonts
        'ttf' => '🔤', 'otf' => '🔤', 'woff' => '🔤',
        
        // Default
        'default' => '📄'
    ];
    
    return $icons[$extension] ?? $icons['default'];
}

/**
 * Konversi permission angka ke format string
 */
function get_permission_string($mode) {
    $permissions = '';
    
    // Owner
    $permissions .= (($mode & 0x0100) ? 'r' : '-');
    $permissions .= (($mode & 0x0080) ? 'w' : '-');
    $permissions .= (($mode & 0x0040) ? (($mode & 0x0800) ? 's' : 'x') : (($mode & 0x0800) ? 'S' : '-'));
    
    // Group
    $permissions .= (($mode & 0x0020) ? 'r' : '-');
    $permissions .= (($mode & 0x0010) ? 'w' : '-');
    $permissions .= (($mode & 0x0008) ? (($mode & 0x0400) ? 's' : 'x') : (($mode & 0x0400) ? 'S' : '-'));
    
    // Others
    $permissions .= (($mode & 0x0004) ? 'r' : '-');
    $permissions .= (($mode & 0x0002) ? 'w' : '-');
    $permissions .= (($mode & 0x0001) ? (($mode & 0x0200) ? 't' : 'x') : (($mode & 0x0200) ? 'T' : '-'));
    
    return $permissions;
}

/**
 * Scan direktori dengan filter
 */
function scan_directory($path) {
    $items = [];
    
    if (!is_dir($path) || !is_readable($path)) {
        return $items;
    }
    
    $contents = @scandir($path);
    if ($contents === false) {
        return $items;
    }
    
    foreach ($contents as $item) {
        if ($item == '.' || $item == '..') continue;
        
        $full_path = $path . DIRECTORY_SEPARATOR . $item;
        $is_dir = is_dir($full_path);
        
        // Skip restricted items
        if (in_array($item, $GLOBALS['restricted_folders'])) {
            continue;
        }
        
        $items[] = [
            'name' => $item,
            'path' => $full_path,
            'is_dir' => $is_dir,
            'size' => $is_dir ? null : (@filesize($full_path) ?: 0),
            'modified' => @filemtime($full_path) ?: time(),
            'permissions' => @fileperms($full_path) ?: 0644,
            'icon' => $is_dir ? '📁' : get_file_icon($item),
            'type' => $is_dir ? 'directory' : (@mime_content_type($full_path) ?: 'application/octet-stream'),
            'readable' => is_readable($full_path),
            'writable' => is_writable($full_path),
            'executable' => is_executable($full_path)
        ];
    }
    
    // Urutkan: folder dulu, kemudian file
    usort($items, function($a, $b) {
        if ($a['is_dir'] && !$b['is_dir']) return -1;
        if (!$a['is_dir'] && $b['is_dir']) return 1;
        return strcasecmp($a['name'], $b['name']);
    });
    
    return $items;
}

/**
 * Get human readable file type
 */
function get_file_type($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    $types = [
        // Images
        'jpg' => 'JPEG Image', 'jpeg' => 'JPEG Image', 'png' => 'PNG Image',
        'gif' => 'GIF Image', 'svg' => 'SVG Image', 'bmp' => 'Bitmap Image',
        'webp' => 'WebP Image', 'ico' => 'Icon',
        
        // Documents
        'pdf' => 'PDF Document', 'doc' => 'Word Document', 'docx' => 'Word Document',
        'txt' => 'Text File', 'rtf' => 'Rich Text', 'md' => 'Markdown',
        'odt' => 'OpenDocument Text',
        
        // Code
        'php' => 'PHP Script', 'html' => 'HTML File', 'htm' => 'HTML File',
        'js' => 'JavaScript File', 'css' => 'Stylesheet', 'json' => 'JSON File',
        'xml' => 'XML File', 'sql' => 'SQL File', 'py' => 'Python Script',
        
        // Archives
        'zip' => 'ZIP Archive', 'rar' => 'RAR Archive', '7z' => '7-Zip Archive',
        'tar' => 'TAR Archive',
        
        // Video
        'mp4' => 'MP4 Video', 'avi' => 'AVI Video', 'mkv' => 'Matroska Video',
        'mov' => 'QuickTime Video',
        
        // Audio
        'mp3' => 'MP3 Audio', 'wav' => 'WAV Audio', 'ogg' => 'Ogg Vorbis Audio',
        
        // Default
        'default' => 'File'
    ];
    
    return $types[$extension] ?? $types['default'];
}

/**
 * Generate breadcrumb navigation
 */
function generate_breadcrumb($current_path) {
    $breadcrumbs = [];
    $relative_path = str_replace(BASE_PATH, '', $current_path);
    
    if ($relative_path == '') {
        return [['name' => 'Home', 'path' => '']];
    }
    
    $parts = explode(DIRECTORY_SEPARATOR, trim($relative_path, DIRECTORY_SEPARATOR));
    $current = '';
    
    $breadcrumbs[] = ['name' => 'Home', 'path' => ''];
    
    foreach ($parts as $part) {
        if ($part == '') continue;
        $current .= '/' . $part;
        $breadcrumbs[] = [
            'name' => $part,
            'path' => ltrim($current, '/')
        ];
    }
    
    return $breadcrumbs;
}

/**
 * Get disk usage information
 */
function get_disk_usage() {
    $total = @disk_total_space(BASE_PATH) ?: 0;
    $free = @disk_free_space(BASE_PATH) ?: 0;
    $used = $total - $free;
    
    return [
        'total' => $total,
        'free' => $free,
        'used' => $used,
        'used_percent' => $total > 0 ? round(($used / $total) * 100, 2) : 0
    ];
}

/**
 * Create backup of file
 */
function backup_file($filepath) {
    if (!file_exists($filepath)) {
        return false;
    }
    
    $backup_dir = BASE_PATH . '/backups';
    if (!is_dir($backup_dir)) {
        @mkdir($backup_dir, 0755, true);
    }
    
    $filename = basename($filepath);
    $backup_name = $filename . '.' . date('Ymd_His') . '.bak';
    $backup_path = $backup_dir . '/' . $backup_name;
    
    return @copy($filepath, $backup_path);
}

/**
 * Delete directory recursively
 */
function delete_directory($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            delete_directory($path);
        } else {
            @unlink($path);
        }
    }
    
    return @rmdir($dir);
}

/**
 * Get file preview content
 */
function get_file_preview($filepath, $max_lines = 100) {
    if (!file_exists($filepath) || !is_readable($filepath)) {
        return false;
    }
    
    $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    $content = '';
    
    // Untuk file teks
    $text_extensions = ['txt', 'php', 'html', 'htm', 'css', 'js', 'json', 'xml', 'sql', 'md', 'log', 'csv', 'ini', 'conf'];
    if (in_array($extension, $text_extensions)) {
        $handle = @fopen($filepath, 'r');
        if ($handle) {
            $lines = [];
            $line_count = 0;
            while (($line = fgets($handle)) !== false && $line_count < $max_lines) {
                $lines[] = htmlspecialchars($line);
                $line_count++;
            }
            fclose($handle);
            
            $total_lines = count(file($filepath));
            $content = implode('', $lines);
            if ($total_lines > $max_lines) {
                $content .= "\n\n... (" . ($total_lines - $max_lines) . " more lines)";
            }
        }
    }
    
    return $content;
}

/**
 * Validate file extension
 */
function validate_extension($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $forbidden = ['php', 'phtml', 'phar', 'htaccess', 'htpasswd', 'sh', 'bash', 'exe', 'bat', 'cmd', 'js', 'jar'];
    
    if (in_array($extension, $forbidden)) {
        return false;
    }
    
    return empty(ALLOWED_EXTENSIONS) || in_array($extension, ALLOWED_EXTENSIONS);
}

/**
 * Create zip archive
 */
function create_zip($files, $destination) {
    if (!class_exists('ZipArchive')) {
        return false;
    }
    
    $zip = new ZipArchive();
    if ($zip->open($destination, ZipArchive::CREATE) !== true) {
        return false;
    }
    
    foreach ($files as $file) {
        if (is_dir($file)) {
            // Add directory recursively
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($file),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    $zip->addEmptyDir(str_replace($file . '/', '', $item->getPathname() . '/'));
                } else {
                    $zip->addFile($item->getPathname(), str_replace($file . '/', '', $item->getPathname()));
                }
            }
        } else {
            $zip->addFile($file, basename($file));
        }
    }
    
    return $zip->close();
}

/**
 * Sanitize filename
 */
function sanitize_filename($filename) {
    // Remove path traversal attempts
    $filename = str_replace(['../', './', '..\\', '.\\'], '', $filename);
    
    // Remove dangerous characters
    $filename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '', $filename);
    
    // Trim
    $filename = trim($filename, '. ');
    
    return $filename;
}

/**
 * Get file content for editing
 */
function get_file_content($filepath) {
    if (!file_exists($filepath) || !is_readable($filepath)) {
        return false;
    }
    
    $content = file_get_contents($filepath);
    if ($content === false) {
        return false;
    }
    
    return $content;
}

/**
 * Check if file is editable
 */
function is_editable_file($filename) {
    $editable_extensions = ['txt', 'php', 'html', 'htm', 'css', 'js', 'json', 'xml', 'md', 'sql', 'ini', 'conf', 'env', 'log'];
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($extension, $editable_extensions);
}

/**
 * Get file extension
 */
function get_extension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}
?>
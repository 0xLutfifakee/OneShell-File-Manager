<?php
// OneShell Configuration - Tema Anime Sakura Kawaii

session_start();
ob_start();

// Konfigurasi dasar
define('ONESHELL_VERSION', '1.1.0');
define('ONESHELL_THEME', 'Sakura Kawaii');
define('BASE_PATH', realpath(dirname(__FILE__)));
define('MAX_UPLOAD_SIZE', 100 * 1024 * 1024); // 100MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'rar', 'php', 'html', 'css', 'js', 'json', 'xml', 'sql', 'mp3', 'mp4', 'avi', 'mkv', 'md', 'log', 'csv', 'odt', 'ods', 'odp', 'ico', 'svg', 'webp', 'bmp']);

// Keamanan
$allowed_actions = ['list', 'open', 'rename', 'delete', 'move', 'copy', 'upload', 'edit', 'mkdir', 'chmod', 'download', 'preview', 'compress', 'extract', 'get_content', 'newfile', 'quick_upload'];
$restricted_folders = ['../', '..\\', '.git', '.env', 'vendor', 'node_modules', 'config.php', 'actions.php'];

// Tema warna Sakura Kawaii
$theme_colors = [
    'primary' => '#ff9eb5',
    'secondary' => '#ffd1dc',
    'accent' => '#ff6b93',
    'background' => '#fff9fb',
    'text' => '#5a3d5c',
    'text-light' => '#8a6b8c',
    'border' => '#ffc8dd',
    'success' => '#a8e6cf',
    'danger' => '#ff8ba7',
    'warning' => '#ffd3b6',
    'info' => '#b5deff'
];

// Setel timezone
date_default_timezone_set('Asia/Jakarta');

// Error reporting
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/error.log');

// Cek PHP version
if (version_compare(PHP_VERSION, '7.0.0') < 0) {
    die("OneShell membutuhkan PHP 7.0 atau lebih tinggi. Versi PHP saat ini: " . PHP_VERSION);
}

// Auto include functions
if (!function_exists('validate_path')) {
    require_once 'functions.php';
}

// Inisialisasi session
if (!isset($_SESSION['messages'])) {
    $_SESSION['messages'] = [];
}

// Fungsi helper untuk menambah message
function add_message($type, $text) {
    $_SESSION['messages'][] = [
        'type' => $type,
        'text' => $text,
        'time' => time()
    ];
}

// Tampilkan messages
function show_messages() {
    if (!empty($_SESSION['messages'])) {
        echo '<div class="messages">';
        foreach ($_SESSION['messages'] as $index => $message) {
            $class = '';
            $icon = '';
            switch ($message['type']) {
                case 'success': 
                    $class = 'message-success'; 
                    $icon = '✅';
                    break;
                case 'error': 
                    $class = 'message-error'; 
                    $icon = '❌';
                    break;
                case 'warning': 
                    $class = 'message-warning'; 
                    $icon = '⚠️';
                    break;
                case 'info': 
                    $class = 'message-info'; 
                    $icon = 'ℹ️';
                    break;
            }
            echo "<div class='message {$class} fade-in'>";
            echo "<span class='message-icon'>{$icon}</span>";
            echo "<span class='message-text'>{$message['text']}</span>";
            echo "<small>" . date('H:i:s', $message['time']) . "</small>";
            echo "</div>";
        }
        echo '</div>';
        // Clear messages setelah ditampilkan
        $_SESSION['messages'] = [];
    }
}

// Convert ini size string to bytes
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $num = (float) substr($val, 0, -1);
    
    switch($last) {
        case 'g':
            $num *= 1024;
        case 'm':
            $num *= 1024;
        case 'k':
            $num *= 1024;
    }
    
    return $num;
}
?>
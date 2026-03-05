<?php
session_start();
require_once 'config/Database.php';

// 🌍 MULTI-LANGUAGE ENGINE
function __($key) {
    static $translations = null;
    
    // Load the JSON file only once per page load to keep it lightning fast
    if ($translations === null) {
        $lang = $_SESSION['lang'] ?? 'fr'; // Default to French
        $file = __DIR__ . "/lang/{$lang}.json";
        
        if (file_exists($file)) {
            $translations = json_decode(file_get_contents($file), true);
        } else {
            $translations = [];
        }
    }
    
    // Return the translated word, or the original key if the translation is missing
    return $translations[$key] ?? $key;
}

// Basic Routing Logic
$url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : 'dashboard/index';
$urlParts = explode('/', $url);

$controllerName = ucfirst($urlParts[0]) . 'Controller';
$method = isset($urlParts[1]) ? $urlParts[1] : 'index';
$param = isset($urlParts[2]) ? $urlParts[2] : null;

$controllerFile = 'controllers/' . $controllerName . '.php';

if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $controller = new $controllerName();
    
    if (method_exists($controller, $method)) {
        if ($param) {
            $controller->{$method}($param);
        } else {
            $controller->{$method}();
        }
    } else {
        echo "Error 404: Method '$method' not found in $controllerName.";
    }
} else {
    // Fallback to login
    require_once 'controllers/AuthController.php';
    $auth = new AuthController();
    $auth->login();
}
?>
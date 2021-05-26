<?php
namespace Appkita\SPARK;
use Exception;

Class App {
    protected $path = '';
    protected $router = '';
    protected $autoload = '';
    private $indexFiles = ['index.html', 'index.php'];

    function __construct($config = '') {
        $this->init($config);
    }

    public function getListFiles() {
        return $this->listFiles;
    }

    public function init($config = '') {
        if (!empty($config)) {
            if (\is_string($config)) {
                $this->path = $config;
            } else {
                if (\is_array($config)) {
                    $config = (object) $config;
                }
                if (isset($config->path)) $this->path = $config->path;
                if (isset($config->router)) $this->router = $config->router;
                if (isset($config->indexFiles)) $this->indexFiles = $config->indexFiles;
                if (isset($config->autoload)) $this->autoload = $config->autoload;
            }
        }
    }

    public function getRouter($page) {
        $router = $this->router;
        if (\is_array($router)){
            foreach ($router as $regex => $fn)
            {
                if (preg_match('%'.$regex.'%', $page))
                {
                    return dirname(__FILE__) . $fn;
                    break;
                }
            }
        }
        return $page;
    }

    public function getIndexPage($page) {
        if (is_dir($page))
        {
            foreach ($this->indexFiles as $filename)
            {

                $fn = $page.DIRECTORY_SEPARATOR.$filename;
                if (is_file($fn)) {
                    return $fn;
                    break;
                }
            }
            return self::pageError(404);
        }
        return $page;
    }

    public function pageError(int $code=404) {
        $path = \dirname(__FILE__);
        switch($code) {
            case 404:
                return 'Error/notfound.php';
                break;
        }
    }

    public function _autoload() {
        if (is_string($this->autoload)) {
            if (\file_exists($this->autoload)) {
                require_once $this->autoload;
            }
        } else if (\is_array($this->autoload)) {
            for($i = 0; $i < sizeof($this->autoload); $i++) {
                if (\file_exists($this->autoload[$i])) {
                    require_once $this->autoload[$i];
                }
            }
        }
    }

    public function openPage($page = null) {
        if (empty($page)) {
            $page = '';
        }
        $page = \ltrim(\rtrim(rtrim($page, '/'), '\\'));
        $page = $this->path . $page;
        if (!\file_exists($page)) {
            include_once self::pageError(404);
            return true;
        }
        $page = self::getRouter($page);
        $page = self::getIndexPage($page);
        $ext  = (new \SplFileInfo($page))->getExtension();
        if (\strtolower($ext) == 'php') {
            $this->_autoload();
            include_once $page;
            return true;
        } else {
             if ($ext == 'html' || $ext == 'htm') {
                $mimi = 'text/html';
            } else if ($ext == 'xhtml') {
                $mimi = 'application/xhtml+xml';
            }else if ($ext == 'js') {
                $mimi = 'application/javascript';
                header('access-control-allow-origin: *');
            }else{
                $mimi = mime_content_type($page);
            }
             header('Content-Type: '. $mimi);
            $fh = fopen($page, 'r');
            fpassthru($fh);
            fclose($fh);
            return true;
        }        
    }
}
 function is_cli() : bool{
        if ( defined('STDIN') ||  
             php_sapi_name() === 'cli' || 
             array_key_exists('SHELL', $_ENV) ||
             empty($_SERVER['REMOTE_ADDR']) and !isset($_SERVER['HTTP_USER_AGENT']) and count($_SERVER['argv']) > 0 ||
            !array_key_exists('REQUEST_METHOD', $_SERVER))
        {
            return true;
        } else {
            return false;
        }
    }

if (!is_cli()) {
    $env = json_decode(\getenv('CONFIG_ENV'));
    $app = new App($env);
   $page = !empty($page) ? $page : $_SERVER['REQUEST_URI'];
   $app->openPage($page);
}
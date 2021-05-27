<?php
namespace Appkita\SPARK;
require_once __DIR__.DIRECTORY_SEPARATOR.'config.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'helps.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'Request.php';

use Exception;
use \Appkita\SPARK\Helps;
use \Appkita\SPARK\Request;
use \Appkita\SPARK\Config;

Class App {
    use Config;
    public $request;

    function __construct($config = '') {
        $this->initConfig($config);

        $this->request = new Request([
            'host'=>$this->getHost(),
            'port'=>$this->getPort(),
            'path'=>$this->getPath(),
            'router'=>$this->getRouter(),
            'indexFiles'=>$this->getIndexFiles()
        ]);
    }

    public function showError(int $code = 404) {
        $page = $this->pageError($code);
        if (\file_exists($page)) {
            $page = $this->pageError(404);
        }
        include $page;
        die();
    }

    public function pageError(int $code=404) {
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

    public function run() {
        $page = rtrim($this->request->getPage(), '\\%');
        $ext  = (new \SplFileInfo($page))->getExtension();
        $isClass = false;
        if (empty($ext)) {
            if ($this->getRewrite() && !empty($this->getFileClass())) {
                $page = $this->getFileClass();
                $isClass = true;
            } else {
               $page = $this->pageError(404);
            }
        }
        return $this->openPage($page, $isClass);
    }

    public function openPage($page, $isClass = false) {
        $ext  = (new \SplFileInfo($page))->getExtension();
        if (\strtolower($ext) == 'php') {
            $this->_autoload();
            $exp_page  = explode('\\', $page);
            $pagename = str_replace('.php', '', $exp_page[(sizeof($exp_page) - 1)]);
            $class_file = strtolower(str_replace('.php', '', $this->file_class));
            if ($pagename == $class_file) {
                $isClass = true;
            }
             if ($isClass && $this->getRewrite() && !empty($this->getFileClass())) {
                 if (file_exists($page)) {
                     $page = $page;
                 } else if (file_exists($this->getPath() . $page)) {
                     $page = $this->getPath().$page;
                 } else {
                     return $this->showError(404);
                 }
                 include_once $page;
                 $initclass = Helps::getClassNameFile($page);
                 $classname = !empty($initclass->namespace) ? '\\'. $initclass->namespace : '\\';
                 $classname .= $initclass->class;
                 $class = new $classname();
                 $uri = $this->request->getUri();
                 if (\is_callable(array($class, strtolower($uri)))) {
                     $param = $this->request->argsURL($uri);
                     return \call_user_func_array(array($class, $uri), $param);
                 } else {
                   return $this->showError(404);
                 }
             } else {
                 if (file_exists($page)) {
                    include_once $page;
                 } else {
                   return $this->showError(404);
                 }                
             }
             die();
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
            die();
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
   $app->run();
}
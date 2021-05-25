<?php
namespace Appkita\SPARK;
use Exception;

Class App {
    protected static $path = '';
    protected static $router = '';
    private static $indexFiles = ['index.html', 'index.php'];

    public static function init($config = '') {
        if (!empty($config)) {
            if (\is_string($config)) {
                App::$path = $config;
            } else if (\is_object($config)) {
                if (isset($config->path)) App::$path = $config->path;
                if (isset($config->router)) App::$router = $config->router;
                if (isset($config->indexFiles)) App::$indexFiles = $config->indexFiles;
            } else if (\is_array($config)) {
                $config = (object) $config;
                if (isset($config->path)) App::$path = $config->path;
                if (isset($config->router)) App::$router = $config->router;
                if (isset($config->indexFiles)) App::$indexFiles = $config->indexFiles;
            }
        }
    }

    public static function getRouter($page) {
        $router = App::$router;
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

    public static function getIndexPage($page) {
        if (is_dir($page))
        {
            foreach (App::$indexFiles as $filename)
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

    public static function openPage($page = null) {
        if (empty($page)) {
            $page = '';
        }
        $page = \ltrim(\rtrim(rtrim($page, '/'), '\\'));
        $page = App::$path . $page;
        if (!\file_exists($page)) {
            include_once self::pageError(404);
            return true;
        }
        $page = self::getRouter($page);
        $page = self::getIndexPage($page);
        $ext  = (new \SplFileInfo($page))->getExtension();
        if (\strtolower($ext) == 'php') {
            include_once $page;
            return true;
        } else {
            header('Content-Type: '.mime_content_type($page));
            $fh = fopen($page, 'r');
            fpassthru($fh);
            fclose($fh);
            return true;
        }        
    }
}
$env = json_decode(\getenv('CONFIG_ENV'));
$app = new App();
$app::init($env);
$page =  $_SERVER['REQUEST_URI'];
return $app::openPage($page);
<?php
namespace Appkita\SPARK;
class Request {
    private $_base_url = '';
    private $_method = '';
    private $_uri_segment;
    private $_path = '';
    private $_page = '';
    private $_list_index = [];
    private $_router= [];

    function __construct(array $config = []) {
        $this->_method =  $_SERVER['REQUEST_METHOD'];
        $this->init($config);
        $this->initRequest();
    }
    
    public function initRequest () {
        $page = \ltrim(\ltrim(\parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'), '\\');
        $this->_uri_segment = \explode("/", $page);
        $this->_page = $this->getRouter($page);
        if ($this->_page == $page) {
            $this->_page = rtrim($this->_path, '\\') .'\\'. $this->_page;
            $this->_page = $this->getIndexPage($this->_page);
        }
        $explode = \explode('/', $this->_page);
        $_page = [];
        for($i=0; $i < sizeof($explode); $i++) {
            $ext  = (new \SplFileInfo($explode[$i]))->getExtension(); 
            if (empty($ext)) {
                array_push($_page, $explode[$i]);
            } else {
                array_push($_page, $explode[$i]);
                break;
            }
        }
        $this->_page = \implode(DIRECTORY_SEPARATOR, $_page);
    }

    public function showNotFound() {
        $page = __DIR__ .DIRECTORY_SEPARATOR. 'Error/notfound.php';
        include $page;
        die();
    }

    public function init(array $config) {
        if (isset($config['host'])) {
            $this->_base_url = $config['host'];
        }
        if (isset($config['path'])) {
            $this->_path = $config['path'];
        }
        if (isset($config['indexFiles'])) {
            $this->_list_index = $config['indexFiles'];
        }
        if (isset($config['router']) && \is_array($config['router'])) {
            $this->_router = $config['router'];
        }
        if (isset($config['port']) && !empty($config['port'])) {
            if ($port != '80') {
                $this->_base_url = $this->host.':'. $this->port;
            }
        }
    }

    public function getRouter($page) {
        $router = $this->_router;
        if (\is_array($router)){
            foreach ($router as $regex => $fn)
            {
                if (preg_match('%'.$regex.'%', $page))
                {
                    return $fn;
                    break;
                }
            }
        }
        return $page;
    }

    public function getIndexPage($page) {
        if (is_dir($page))
        {
            foreach ($this->_list_index as $filename)
            {
                $page = \rtrim($page, '\\');
                $page =  \ltrim($page, '/');
                $fn = $page.DIRECTORY_SEPARATOR.$filename;
                if (is_file($fn)) {
                    return $fn;
                    break;
                }
            }
            return $this->showNotFound();
        }
        return $page;
    }

    public function  getClass() {
        return $this->_class;
    }

    public function getPage() {
        return $this->_page;
    }

    public function getUri(int $segment = 1) {
        $uri = $this->getSegment(($segment > 0 ? $segment : 1));
        if (empty($uri)) {
            $uri = 'index';
        } else {
            if(strtolower(substr($uri, -4)) == '.php') {
                $uri = $this->getUri(2);
            }
        } 
        return $uri;
    }

    public function method(bool $lower) {
        return $lower ? \strtolower($this->_method) : \strtoupper($this->_method);
    }

    public function base_url() {
        $host = \ltrim(\ltrim($this->_base_url, '\\'), '/');
        return $host .'/';
    }

    public function argsURL($uri) {
        $hasil = [];
        $seg = $this->getSegment(0);
        for ($i = 0; $i < \sizeof($seg); $i++) {
            $arg = $seg[$i];
            if (!empty($arg)) {
                if(strtolower(substr($arg, -4)) != '.php' && $arg != $uri) {
                    array_push($hasil, $arg);
                }
            }
        }
        return $hasil;
    }

    public function getSegment(int $segment = 0) {
        if ($segment > 0) {
            $segment = (int) $segment - 1;
            return isset($this->_uri_segment[$segment]) ? $this->_req($this->_uri_segment[$segment], false) : '';
        } else {
            return $this->_uri_segment;
        }
    }

    public function getVar($key = '', $safe = true, $filter = FILTER_SANITIZE_STRING) {
        if (!empty($post = $this->GetPOST($key, $safe, $filter))) {
            return $post;
        } else if (!empty($get = $this->getGet($key, $safe, $filter))) {
            return $get;
        } else {
            return $this->getJSON($key. $safe, $filter);
        }
    }

    public function getJSON($key = '', $safe = true, $filter = FILTER_SANITIZE_STRING) {
        $json = file_get_contents('php://input');
        $data = json_decode($json);
        if (!empty($key)) {
            if (isset($data->{$key})) {
                return $this->_req($data->key, $safe, $filter);
            } else {
                return '';
            }
        } else {
            return $data;
        }
    }

    public function getPOST($key = '', $safe = true, $filter = FILTER_SANITIZE_STRING) {
        $get = empty($key) ? $_GET : (isset($_POST[$key]) ? $_POST[$key] : '');
        return $this->_req($get, $safe, $fitler);
    }

    public function getGet($key = '', $safe = true, $filter = FILTER_SANITIZE_STRING) {
        $get = empty($key) ? $_GET : (isset($_GET[$key]) ? $_GET[$key] : '');
        return $this->_req($get, $safe, $fitler);
    }

    private function _req($var, $safe = true, $filter = FILTER_SANITIZE_STRING) {
        if ($safe) {
            $var = \htmlspecialchars($get);
        }
        if ($filter && \is_string($var)) {
            return \filter_var($var, $filter);
        } else {
            return $var;
        }
    }
}
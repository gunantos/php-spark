<?php
namespace Appkita\SPARK;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Symfony\Component\Filesystem\Filesystem;

trait Config {
    protected $path = '';
    protected $router = [];
    protected $host = '127.0.0.1';
    protected $port = '8080';
    private $indexFiles = ['index.html', 'index.php'];
    protected $autoload = [];
    protected $show_header = true;
    protected $rewrite = false;
    protected $file_class = '';

    protected function initConfig() {
        $this->path = $this->getRootPath();
        list($path, $router, $host, $port, $show_header) = \func_get_args();
        if (!empty($router)) $this->setRouter($rotuer);
        if (!empty($host)) $this->setHost($host);
        if (!empty($port)) $this->setPort($port);
        if (!empty($show_header)) $this->setShowHeader($show_header);
        if (!empty($path)) {
            if (\is_string($path)) {
                 $this->setPath($path);
            } else {
                if (\is_object($path)) {
                    $path = (array) $path;
                }
                if (isset($path['path'])) $this->setPath($path['path']);
                if (isset($path['host'])) $this->setHost($path['host']);
                if (isset($path['router'])) $this->setRouter($path['router']);
                if (isset($path['port'])) $this->setPort($path['port']);
                if (isset($path['indexFiles'])) $this->setIndexFiles($path['indexFiles']);
                if (isset($path['show_header'])) $this->setShowHeader($path['show_header']);
                if (isset($path['autoload'])) $this->setAutoload($path['autoload']);
                if (isset($path['rewrite'])) $this->setRewrite($path['rewrite']);
                if (isset($path['file_class'])) $this->setFileClass($path['file_class']);
            }
        }
    }

    public function setIndexFiles(array $indeks) {
        if (!empty($indeks) && \is_array($indeks) && \sizeof($indeks) > 0) {
            $this->indexFiles = $indeks;
        }
        return $this;
    }

    public function getIndexFiles() {
        if (empty($this->getIndexFiles) || !is_array($this->getIndexFiles) || sizeof($this->getIndexFiles) < 1) {
            return  ['index.html', 'index.php'];
        }
        return $this->getIndexFiles;
    }

    public function setShowHeader(bool $header) {
        $this->show_header = $header;
        return $this;
    }
    
    public function getRootPath($page = '')
    {
        $page = \ltrim(\rtrim(\rtrim($page, '/'), '\\'));
        if (!empty($this->path)) {
            if (\file_exists($this->path)) {
                return realpath($this->path.DIRECTORY_SEPARATOR. $page);
            } else {
                throw new PublicPathNotSet('Your web host path '. $this->path.' not found');
            }
        }
        if (class_exists('\Composer\InstalledVersions')) {
            return dirname(dirname(dirname(dirname(__DIR__)))). $page;
        } else {
            return \dirname(realpath(__FILE__)). $page;
        }
    }

	public function setRouter(array $router) {
        if (!empty($router)) {
            $this->router = $router;
        }
        return $this;
	}

	public function getRouter() : array {
        if (empty($this->router)) {
            $this->router = [];
        }
        if (!\is_array($this->router)) {
            $this->router = [$this->router];
        }
        return $this->router;
	}

	public function setPath(string $path) {
        if (!empty($path)) {
            $this->path = $path;
        }
        return $this;
	}

	public function getPath() : string {
        if (empty($this->path)) {
            $this->path = $this->getRootPath();
        }
        return $this->path;
	}

    public function setHost(string $host) {
        if (!empty($host)) {
            $this->host = $host;
        }
        return $this;
    }

    public function getHost() : string {
        return empty($this->host) ? '127.0.0.1' : $this->host;
    }

    public function setPort($port) {
        if (!empty($port) && \is_numeric($port)) {
            $this->port = $port;
        }
        return $this;
    }

    public function getPort() : string {
        return (string) empty($this->port) ? '8080' : $this->port;
    }

    public function setAutoload($autoload = '') {
        if (!empty($autoload)) {
            $this->autoload = $autoload;
        }
        return $this;
    }

    public function getAutoload() {
        if (empty($this->autoload) || (\is_array($this->autoload) && sizeof($this->autoload) < 1)) {
            $this->autoload = $this->getComposerLoad();
        }
        return $this->autoload;
    }

    protected function getComposerLoad() {
        if (\class_exists('\Composer\Factory')) {
			$projectRootPath = dirname(\Composer\Factory::getComposerFile());
            return $projectRootPath .DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPERATOR.'autload.php';
		} else {
            return '';   
        }
    }

    protected function getRewrite() {
        return $this->rewrite;
    }

    protected function setRewrite($val) {
        $this->rewrite = $val;
        return $this;
    }

    protected function getFileClass() {
        return $this->file_class;
    }

    protected function setFileClass($val){
        $this->file_class = $val;
        return  $this;
    }
}
<?php
namespace Appkita\SPARK;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Symfony\Component\Filesystem\Filesystem;

trait Config {
    protected $path = '';
    protected $router = '';
    protected $host = '::1';
    protected $port = '8080';
    protected $show_header = true;

    protected function initConfig() {
        $this->path = $this->getRootPath();
        list($path, $router, $host, $port, $show_header) = \func_get_args();
        if (!empty($router)) $this->setRouter($rotuer);
        if (!empty($host)) $this->setHost($host);
        if (!empty($port)) $this->setPort($port);
        if (!empty($show_header)) $this->setShowHeader($show_header);
        if (!empty($path)) {
            if (\is_array($path)) {
                if (count($path) == count($path, COUNT_RECURSIVE)) {
                   if (isset($path[0])) $this->setPath($path[0]);
                    if (isset($path[1])) $this->setHost($path[1]);
                    if (isset($path[2])) $this->setPort($path[2]);
                    if (isset($path[3])) $this->setShowHeader($path[3]);
                    if (isset($path[4])) $this->setRouter($path[4]);
                } else {
                    if (isset($path[3])) $this->setPort($path[2]);
                    if (isset($path['path'])) $this->setPath($path['path']);
                    if (isset($path['host'])) $this->setHost($path['host']);
                    if (isset($path['router'])) $this->setRouter($path['router']);
                    if (isset($path['port'])) $this->setPort($path['port']);
                    if (isset($path['show_header'])) $this->setShowHeader($path['show_header']);
                }
            } else {
                $this->setPath($path);
            }
        }
    }

    public function setShowHeader(bool $header) {
        $this->show_header = $header;
        return $this;
    }
    
    public static function getRootPath()
    {
        return dirname(dirname(dirname(dirname(__DIR__))));
    }

	public function setRouter(array $router) {
        if (!empty($router)) {
            $this->router = $router;
        }
        return $this;
	}

	public function getRouter() : array {
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
        return empty($this->host) ? '127.0.0' : $this->host;
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
}
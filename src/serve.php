<?php
namespace Appkita\SPARK;
use \Appkita\SPARK\Exceptions\PHPVersionNotSupport;
use \Appkita\SPARK\Exceptions\PathNotExist;
use Exception;
use \Appkita\SPARK\CLI;

class Serve {
	use Config;

	const MIN_VERSION = '7.2';

	function __construct($config=[]) {
		ignore_user_abort(true);
		$this->check();
		$this->initConfig($config);
	}
	
	public function run() {
		$cmd = "cd %path && php -S %host:%port";
		if (!\file_exists($this->getPath())) {
			throw new PathNotExist("Path {$this->path} not exist");
		}
		CLI::write(printf($cmd, $this->getPath(), $this->getHost(), $this->getPort()));
		CLI::newLine();
	}

	
    protected function _header() {
        if (empty($this->header)) {
            $this->header = printf('APPKITA SPARK v%s Command Line Tool - Server Time: %s UTC%s', \Composer\InstalledVersions::getPrettyVersion('appkita/spark-serve'), date('Y-m-d H:i:s'), date('P'));
        }
        return $this->header;
    }

	public function showHeader()
	{
		CLI::write($this->_header(), 'green');
		CLI::newLine();
	}

	public function check() {
		if (!CLI::is_cli())
		{
			throw new RuntimeException(static::class . ' needs to run from the command line.'); 
		}
		if (version_compare(PHP_VERSION, Serve::MIN_VERSION, '<')) {
			throw new PHPNotSupport("Your PHP version must be ".Serve::MIN_VERSION ."or higher to run CodeIgniter. Current version: " . PHP_VERSION);
		}
		if (strpos(PHP_SAPI, 'cgi') === 0)
		{
			throw new PHPNotSupport('The cli tool is not supported when running php-cgi. It needs php-cli to function!\n\n');
		}
	}
	
}
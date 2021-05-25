<?php
namespace Appkita\SPARK;
use \Appkita\SPARK\Exceptions\PHPVersionNotSupport;
use \Appkita\SPARK\Exceptions\PathNotExist;
use \Appkita\SPARK\Exceptions\CLIException;
use \Appkita\SPARK\CLI;
use Exception;
use Throwable;


class Serve {
	use Config;

	const MIN_VERSION = '7.2';
	private $config;

	function __construct($config=[]) {
		ignore_user_abort(true);
		$this->check();
		$this->config = $config;
		$this->initConfig($config);
	}
	
	public function run() {
		$cmd = "php -S %s:%s %s";
		if (!\file_exists($this->getPath())) {
			throw new PathNotExist("Path {$this->path} not exist");
		}
		$app = \dirname(\realpath(__FILE__)) .DIRECTORY_SEPARATOR. "App.php";
		$cmd = 'php -S '. $this->getHost() .':'. $this->getPort().' '. $app;
		$configuration = (object) [
			'indexFiles'=>$this->getIndexFiles(),
			'path'=>$this->getPath(),
			'router'=>$this->getRouter()
		];
		\putenv('CONFIG_ENV='.json_encode($configuration));
		$PID = $this->LaunchBackgroundProcess($cmd);
		CLI::clearScreen();
		if ($this->show_header){
			$this->showHeader();
		}
		CLI::write('Server Run in '. $this->gethost() .':'. $this->getPort(), 'green');
	}
	function LaunchBackgroundProcess($command){
		if(PHP_OS=='WINNT' || PHP_OS=='WIN32' || PHP_OS=='Windows'){
			// Windows
			$command = 'start "" '. $command;
		} else {
			// Linux/UNIX
			$command = $command .' /dev/null &';
		}
		$handle = popen($command, 'r');
		if($handle!==false){
			pclose($handle);
			return true;
		} else {
			return false;
		}
	}


	function execCommand($command) {

    if (substr(php_uname(), 0, 7) == "Windows")
    {
        //windows
       pclose(popen("start /B " . $command ." &", "r"));
    }
    else
    {
        //linux
        shell_exec( $command ." &");
    }
   
    return false;
}

	public function showHeader()
	{
		CLI::write($this->_header(), 'green');
		CLI::newLine();
	}

    protected function _header() {
        if (empty($this->header)) {
            $this->header = sprintf('APPKITA SPARK v%s Command Line Tool - Server Time: %s UTC%s', \Composer\InstalledVersions::getPrettyVersion('appkita/spark-serve'), date('Y-m-d H:i:s'), date('P'));
        }
        return $this->header;
    }

	public function is_cli()
    {
        if ( defined('STDIN') )
        {
            return true;
        }

        if ( php_sapi_name() === 'cli' )
        {
            return true;
        }

        if ( array_key_exists('SHELL', $_ENV) ) {
            return true;
        }

        if ( empty($_SERVER['REMOTE_ADDR']) and !isset($_SERVER['HTTP_USER_AGENT']) and count($_SERVER['argv']) > 0) 
        {
            return true;
        } 

        if ( !array_key_exists('REQUEST_METHOD', $_SERVER) )
        {
            return true;
        }

        return false;
    }

	public function check() {
		if (!$this->is_cli())
		{
			throw new CLIException(static::class . ' needs to run from the command line.'); 
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
<?php
namespace Appkita\SPARK;
use \Appkita\SPARK\Exceptions\PHPVersionNotSupport;
use \Appkita\SPARK\Exceptions\PathNotExist;
use \Appkita\SPARK\Exceptions\CLIException;
use \Appkita\SPARK\CLI;
use \Appkita\SPARK\Helps;
use Exception;
use Throwable;


class Serve {
	use Config;

	const MIN_VERSION = '7.2';
	private $config;
	private $version = '1.0';
	private $pid = null;

	function __construct($config=[]) {
		ignore_user_abort(true);
		$this->check();
		$this->config = $config;
		$this->initConfig($config);
		
		if (\class_exists('\Composer\InstalledVersions')) {
			$this->version = \Composer\InstalledVersions::getPrettyVersion('appkita/spark-serve');
		}
	}
	
	public function getPID() {
		return $this->pid;
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
			'router'=>$this->getRouter(),
			'autoload'=>$this->getAutoload()
		];
		\putenv('CONFIG_ENV='.json_encode($this->config));
		CLI::clearScreen();
		$this->pid = $this->LaunchBackgroundProcess($cmd);
		
		CLI::clearScreen();
		if ($this->show_header){
			$this->showHeader();
		}
		CLI::write($this->pid .' > Server Run in '. $this->gethost() .':'. $this->getPort(), 'white');
	}

	public function clear() {
		if(PHP_OS=='WINNT' || PHP_OS=='WIN32' || PHP_OS=='Windows'){
			pclose($this->pid);
		} else {
			\exec('kill '. $this->pid);
		}
	}

    private function detectChangeFile(){
        $this->listFiles = Helps::listFiles($this->path);
            for ($i =0; $i < sizeof($this->listFiles); $i++) {
                $fl = $this->listFiles[$i];
                $modifiedTs = filemtime($fl);
                $path_info = \pathinfo($fl);
                $filename = $path_info['filename'];
                if (isset($this->log_files[$filename])) {
                    if ($modifiedTs != $this->log_files[$filename]) {
                        return true;
                        break;
                    }
                } else {
                    $this->log_files[$filename];
                    return true;
                    break;
                }
            }
        return false;
    }

	function LaunchBackgroundProcess($command){
		if(PHP_OS=='WINNT' || PHP_OS=='WIN32' || PHP_OS=='Windows'){
			return \popen($command, 'r');
		} else {
			return exec($command .' > /dev/null &');
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
            $this->header = sprintf('APPKITA SPARK v%s Command Line Tool - Server Time: %s UTC%s', $this->version, date('Y-m-d H:i:s'), date('P'));
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
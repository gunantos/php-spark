<?php
namespace Appkita\SPARK;
use Appkita\SPARK\Exceptions\CLIException;
use Throwable;

class CLI
{
    
	public static $readline_support = false;
    public static $wait_msg = 'Press any key to continue...';
    protected static $initialized = false;

    protected static $foreground_colors = [
		'black'        => '0;30',
		'dark_gray'    => '1;30',
		'blue'         => '0;34',
		'dark_blue'    => '0;34',
		'light_blue'   => '1;34',
		'green'        => '0;32',
		'light_green'  => '1;32',
		'cyan'         => '0;36',
		'light_cyan'   => '1;36',
		'red'          => '0;31',
		'light_red'    => '1;31',
		'purple'       => '0;35',
		'light_purple' => '1;35',
		'yellow'       => '0;33',
		'light_yellow' => '1;33',
		'light_gray'   => '0;37',
		'white'        => '1;37',
	];
	protected static $background_colors = [
		'black'      => '40',
		'red'        => '41',
		'green'      => '42',
		'yellow'     => '43',
		'blue'       => '44',
		'magenta'    => '45',
		'cyan'       => '46',
		'light_gray' => '47',
	];
	protected static $segments = [];
	protected static $options = [];
    protected static $lastWrite;
	protected static $height;
	protected static $width;
	protected static $isColored = false;

    public static function init()
	{
		if (CLI::is_cli())
		{
			static::$readline_support = extension_loaded('readline');
			static::$segments = [];
			static::$options  = [];
			static::$isColored = static::hasColorSupport(STDOUT);

			static::parseCommandLine();

			static::$initialized = true;
		}
		else
		{
			define('STDOUT', 'php://output');
		}
	}

    public static function input(string $prefix = null): string
	{
		if (static::$readline_support)
		{
			return readline($prefix);
		}

		echo $prefix;

		return fgets(STDIN);
	}

    public static function prompt(string $field, $options = null, string $validation = null): string
	{
		$extraOutput = '';
		$default     = '';

		if (is_string($options))
		{
			$extraOutput = ' [' . static::color($options, 'white') . ']';
			$default     = $options;
		}

		if (is_array($options) && $options)
		{
			$opts               = $options;
			$extraOutputDefault = static::color($opts[0], 'white');
			unset($opts[0]);
			if (empty($opts))
			{
				$extraOutput = $extraOutputDefault;
			}
			else
			{
				$extraOutput = ' [' . $extraOutputDefault . ', ' . implode(', ', $opts) . ']';
				$validation .= '|in_list[' . implode(',', $options) . ']';
				$validation = trim($validation, '|');
			}
			$default = $options[0];
		}
		static::fwrite(STDOUT, $field . $extraOutput . ': ');
		$input = trim(static::input()) ?: $default;
		return empty($input) ? '' : $input;
	}

    public static function print(string $text = '', string $foreground = null, string $background = null)
	{
		if ($foreground || $background)
		{
			$text = static::color($text, $foreground, $background);
		}
		static::$lastWrite = null;
		static::fwrite(STDOUT, $text);
	}

    protected static function _arg($arg) {
        $return = array();
        foreach($arg as $key) {
            if (\ereg('--([^=]+)=(.*)', $key, $reg)) {
                $result[$req[1]] = $req[2];
            } else if (\ereg('-([a-zA-Z0-9])', $key, $reg)) {
                $result[$req[1]] = 'true';
            }
        }
        return $return;
    }

    public static function write(string $text = '', string $foreground = null, string $background = null)
	{
		if ($foreground || $background)
		{
			$text = static::color($text, $foreground, $background);
		}

		if (static::$lastWrite !== 'write')
		{
			$text              = PHP_EOL . $text;
			static::$lastWrite = 'write';
		}

		static::fwrite(STDOUT, $text . PHP_EOL);
	}

    public static function error(string $text, string $foreground = 'light_red', string $background = null)
	{
		$stdout            = static::$isColored;
		static::$isColored = static::hasColorSupport(STDERR);
		if ($foreground || $background)
		{
			$text = static::color($text, $foreground, $background);
		}
		static::fwrite(STDERR, $text . PHP_EOL);
		static::$isColored = $stdout;
	}

    public static function beep(int $num = 1)
	{
		echo str_repeat("\x07", $num);
	}

    public static function wait(int $seconds, bool $countdown = false)
	{
		if ($countdown === true)
		{
			$time = $seconds;

			while ($time > 0)
			{
				static::fwrite(STDOUT, $time . '... ');
				sleep(1);
				$time --;
			}
			static::write();
		}
		else
		{
			if ($seconds > 0)
			{
				sleep($seconds);
			}
			else
			{
				// this chunk cannot be tested because of keyboard input
				// @codeCoverageIgnoreStart
				static::write(static::$wait_msg);
				static::input();
				// @codeCoverageIgnoreEnd
			}
		}
	}

    public static function isWindows(): bool
	{
		return stripos(PHP_OS, 'WIN') === 0;
	}

   public static function newLine(int $num = 1)
	{
		// Do it once or more, write with empty string gives us a new line
		for ($i = 0; $i < $num; $i ++)
		{
			static::write();
		}
	}
    public static function arg () {
        return CLI::_arg(CLI::command());
    }

    public static function clearScreen()
	{
		// Unix systems, and Windows with VT100 Terminal support (i.e. Win10)
		// can handle CSI sequences. For lower than Win10 we just shove in 40 new lines.
		static::isWindows() && ! static::streamSupports('sapi_windows_vt100_support', STDOUT)
			? static::newLine(40)
			: static::fwrite(STDOUT, "\033[H\033[2J");
	}

    public static function color(string $text, string $foreground, string $background = null, string $format = null): string
	{
		if (! static::$isColored)
		{
			return $text;
		}

		if (! array_key_exists($foreground, static::$foreground_colors))
		{
			throw CLIException::forInvalidColor('foreground', $foreground);
		}

		if ($background !== null && ! array_key_exists($background, static::$background_colors))
		{
			throw CLIException::forInvalidColor('background', $background);
		}

		$string = "\033[" . static::$foreground_colors[$foreground] . 'm';

		if ($background !== null)
		{
			$string .= "\033[" . static::$background_colors[$background] . 'm';
		}

		if ($format === 'underline')
		{
			$string .= "\033[4m";
		}

		// Detect if color method was already in use with this text
		if (strpos($text, "\033[0m") !== false)
		{
			// Split the text into parts so that we can see
			// if any part missing the color definition
			$chunks = mb_split("\\033\[0m", $text);
			// Reset text
			$text = '';

			foreach ($chunks as $chunk)
			{
				if ($chunk === '')
				{
					continue;
				}

				// If chunk doesn't have colors defined we need to add them
				if (strpos($chunk, "\033[") === false)
				{
					$chunk = static::color($chunk, $foreground, $background, $format);
					// Add color reset before chunk and clear end of the string
					$text .= rtrim("\033[0m" . $chunk, "\033[0m");
				}
				else
				{
					$text .= $chunk;
				}
			}
		}

		return $string . $text . "\033[0m";
	}

    public static function strlen(?string $string): int
	{
		if (is_null($string))
		{
			return 0;
		}

		foreach (static::$foreground_colors as $color)
		{
			$string = strtr($string, ["\033[" . $color . 'm' => '']);
		}

		foreach (static::$background_colors as $color)
		{
			$string = strtr($string, ["\033[" . $color . 'm' => '']);
		}

		$string = strtr($string, ["\033[4m" => '', "\033[0m" => '']);

		return mb_strwidth($string);
	}

    public static function streamSupports(string $function, $resource): bool
	{
		$env = 'development';
		if (\defined('ENVERONMENT')) {
			$env = ENVIRONMENT;
		}
		if ($env === 'testing')
		{
			// In the current setup of the tests we cannot fully check
			// if the stream supports the function since we are using
			// filtered streams.
			return function_exists($function);
		}

		// @codeCoverageIgnoreStart
		return function_exists($function) && @$function($resource);
		// @codeCoverageIgnoreEnd
	}

    public static function hasColorSupport($resource): bool
	{
		// Follow https://no-color.org/
		if (isset($_SERVER['NO_COLOR']) || getenv('NO_COLOR') !== false)
		{
			return false;
		}

		if (getenv('TERM_PROGRAM') === 'Hyper')
		{
			return true;
		}

		if (static::isWindows())
		{
			// @codeCoverageIgnoreStart
			return static::streamSupports('sapi_windows_vt100_support', $resource)
				|| isset($_SERVER['ANSICON'])
				|| getenv('ANSICON') !== false
				|| getenv('ConEmuANSI') === 'ON'
				|| getenv('TERM') === 'xterm';
			// @codeCoverageIgnoreEnd
		}

		return static::streamSupports('stream_isatty', $resource);
	}

    public static function getWidth(int $default = 80): int
	{
		if (\is_null(static::$width))
		{
			static::generateDimensions();
		}

		return static::$width ?: $default;
	}

    public static function getHeight(int $default = 32): int
	{
		if (\is_null(static::$height))
		{
			static::generateDimensions();
		}

		return static::$height ?: $default;
	}

    public static function generateDimensions()
	{
		try
		{
			if (static::isWindows())
			{
				// Shells such as `Cygwin` and `Git bash` returns incorrect values
				// when executing `mode CON`, so we use `tput` instead
				// @codeCoverageIgnoreStart
				if (($shell = getenv('SHELL')) && preg_match('/(?:bash|zsh)(?:\.exe)?$/', $shell) || getenv('TERM'))
				{
					static::$height = (int) exec('tput lines');
					static::$width  = (int) exec('tput cols');
				}
				else
				{
					$return = -1;
					$output = [];
					exec('mode CON', $output, $return);

					if ($return === 0 && $output)
					{
						// Look for the next lines ending in ": <number>"
						// Searching for "Columns:" or "Lines:" will fail on non-English locales
						if (preg_match('/:\s*(\d+)\n[^:]+:\s*(\d+)\n/', implode("\n", $output), $matches))
						{
							static::$height = (int) $matches[1];
							static::$width  = (int) $matches[2];
						}
					}
				}
				// @codeCoverageIgnoreEnd
			}
			else
			{
				if (($size = exec('stty size')) && preg_match('/(\d+)\s+(\d+)/', $size, $matches))
				{
					static::$height = (int) $matches[1];
					static::$width  = (int) $matches[2];
				}
				else
				{
					// @codeCoverageIgnoreStart
					static::$height = (int) exec('tput lines');
					static::$width  = (int) exec('tput cols');
					// @codeCoverageIgnoreEnd
				}
			}
		}
		// @codeCoverageIgnoreStart
		catch (Throwable $e)
		{
			// Reset the dimensions so that the default values will be returned later.
			// Then let the developer know of the error.
			static::$height = null;
			static::$width  = null;
			log_message('error', $e->getMessage());
		}
		// @codeCoverageIgnoreEnd
	}

    public static function showProgress($thisStep = 1, int $totalSteps = 10)
	{
		static $inProgress = false;

		// restore cursor position when progress is continuing.
		if ($inProgress !== false && $inProgress <= $thisStep)
		{
			static::fwrite(STDOUT, "\033[1A");
		}
		$inProgress = $thisStep;

		if ($thisStep !== false)
		{
			// Don't allow div by zero or negative numbers....
			$thisStep   = abs($thisStep);
			$totalSteps = $totalSteps < 1 ? 1 : $totalSteps;

			$percent = (int) (($thisStep / $totalSteps) * 100);
			$step    = (int) round($percent / 10);

			// Write the progress bar
			static::fwrite(STDOUT, "[\033[32m" . str_repeat('#', $step) . str_repeat('.', 10 - $step) . "\033[0m]");
			// Textual representation...
			static::fwrite(STDOUT, sprintf(' %3d%% Complete', $percent) . PHP_EOL);
		}
		else
		{
			static::fwrite(STDOUT, "\007");
		}
	}
    public static function wrap(string $string = null, int $max = 0, int $padLeft = 0): string
	{
		if (empty($string))
		{
			return '';
		}

		if ($max === 0)
		{
			$max = CLI::getWidth();
		}

		if (CLI::getWidth() < $max)
		{
			$max = CLI::getWidth();
		}

		$max = $max - $padLeft;

		$lines = wordwrap($string, $max, PHP_EOL);

		if ($padLeft > 0)
		{
			$lines = explode(PHP_EOL, $lines);

			$first = true;

			array_walk($lines, function (&$line, $index) use ($padLeft, &$first) {
				if (! $first)
				{
					$line = str_repeat(' ', $padLeft) . $line;
				}
				else
				{
					$first = false;
				}
			});

			$lines = implode(PHP_EOL, $lines);
		}

		return $lines;
	}

	//--------------------------------------------------------------------
	//--------------------------------------------------------------------
	// Command-Line 'URI' support
	//--------------------------------------------------------------------

	/**
	 * Parses the command line it was called from and collects all
	 * options and valid segments.
	 */
	public static function parseCommandLine()
	{
		$args = $_SERVER['argv'] ?? [];
		array_shift($args); // scrap invoking program
		$optionValue = false;

		foreach ($args as $i => $arg)
		{
			// If there's no "-" at the beginning, then
			// this is probably an argument or an option value
			if (mb_strpos($arg, '-') !== 0)
			{
				if ($optionValue)
				{
					// We have already included this in the previous
					// iteration, so reset this flag
					$optionValue = false;
				}
				else
				{
					// Yup, it's a segment
					static::$segments[] = $arg;
				}

				continue;
			}

			$arg   = ltrim($arg, '-');
			$value = null;

			if (isset($args[$i + 1]) && mb_strpos($args[$i + 1], '-') !== 0)
			{
				$value       = $args[$i + 1];
				$optionValue = true;
			}

			static::$options[$arg] = $value;
		}
	}

	public static function getURI(): string
	{
		return implode('/', static::$segments);
	}

	//--------------------------------------------------------------------

	/**
	 * Returns an individual segment.
	 *
	 * This ignores any options that might have been dispersed between
	 * valid segments in the command:
	 *
	 *  // segment(3) is 'three', not '-f' or 'anOption'
	 *  > php spark one two -f anOption three
	 *
	 * **IMPORTANT:** The index here is one-based instead of zero-based.
	 *
	 * @param integer $index
	 *
	 * @return mixed|null
	 */
	public static function getSegment(int $index)
	{
		if (! isset(static::$segments[$index - 1]))
		{
			return null;
		}

		return static::$segments[$index - 1];
	}

	//--------------------------------------------------------------------

	/**
	 * Returns the raw array of segments found.
	 *
	 * @return array
	 */
	public static function getSegments(): array
	{
		return static::$segments;
	}

	//--------------------------------------------------------------------

	/**
	 * Gets a single command-line option. Returns TRUE if the option
	 * exists, but doesn't have a value, and is simply acting as a flag.
	 *
	 * @param string $name
	 *
	 * @return boolean|mixed|null
	 */
	public static function getOption(string $name)
	{
		if (! array_key_exists($name, static::$options))
		{
			return null;
		}

		// If the option didn't have a value, simply return TRUE
		// so they know it was set, otherwise return the actual value.
		$val = static::$options[$name] === null ? true : static::$options[$name];

		return $val;
	}

	//--------------------------------------------------------------------

	/**
	 * Returns the raw array of options found.
	 *
	 * @return array
	 */
	public static function getOptions(): array
	{
		return static::$options;
	}

	//--------------------------------------------------------------------

	/**
	 * Returns the options as a string, suitable for passing along on
	 * the CLI to other commands.
	 *
	 * @param boolean $useLongOpts Use '--' for long options?
	 * @param boolean $trim        Trim final string output?
	 *
	 * @return string
	 */
	public static function getOptionString(bool $useLongOpts = false, bool $trim = false): string
	{
		if (empty(static::$options))
		{
			return '';
		}

		$out = '';

		foreach (static::$options as $name => $value)
		{
			if ($useLongOpts && mb_strlen($name) > 1)
			{
				$out .= "--{$name} ";
			}
			else
			{
				$out .= "-{$name} ";
			}

			// If there's a space, we need to group
			// so it will pass correctly.
			if (mb_strpos($value, ' ') !== false)
			{
				$out .= '"' . $value . '" ';
			}
			elseif ($value !== null)
			{
				$out .= "{$value} ";
			}
		}

		return $trim ? trim($out) : $out;
	}

	//--------------------------------------------------------------------

	/**
	 * Returns a well formatted table
	 *
	 * @param array $tbody List of rows
	 * @param array $thead List of columns
	 *
	 * @return void
	 */
	public static function table(array $tbody, array $thead = [])
	{
		// All the rows in the table will be here until the end
		$tableRows = [];

		// We need only indexes and not keys
		if (! empty($thead))
		{
			$tableRows[] = array_values($thead);
		}

		foreach ($tbody as $tr)
		{
			$tableRows[] = array_values($tr);
		}

		// Yes, it really is necessary to know this count
		$totalRows = count($tableRows);

		// Store all columns lengths
		// $all_cols_lengths[row][column] = length
		$allColsLengths = [];

		// Store maximum lengths by column
		// $max_cols_lengths[column] = length
		$maxColsLengths = [];

		// Read row by row and define the longest columns
		for ($row = 0; $row < $totalRows; $row ++)
		{
			$column = 0; // Current column index
			foreach ($tableRows[$row] as $col)
			{
				// Sets the size of this column in the current row
				$allColsLengths[$row][$column] = static::strlen($col);

				// If the current column does not have a value among the larger ones
				// or the value of this is greater than the existing one
				// then, now, this assumes the maximum length
				if (! isset($maxColsLengths[$column]) || $allColsLengths[$row][$column] > $maxColsLengths[$column])
				{
					$maxColsLengths[$column] = $allColsLengths[$row][$column];
				}

				// We can go check the size of the next column...
				$column ++;
			}
		}

		// Read row by row and add spaces at the end of the columns
		// to match the exact column length
		for ($row = 0; $row < $totalRows; $row ++)
		{
			$column = 0;
			foreach ($tableRows[$row] as $col)
			{
				$diff = $maxColsLengths[$column] - static::strlen($col);
				if ($diff)
				{
					$tableRows[$row][$column] = $tableRows[$row][$column] . str_repeat(' ', $diff);
				}
				$column ++;
			}
		}

		$table = '';

		// Joins columns and append the well formatted rows to the table
		for ($row = 0; $row < $totalRows; $row ++)
		{
			// Set the table border-top
			if ($row === 0)
			{
				$cols = '+';
				foreach ($tableRows[$row] as $col)
				{
					$cols .= str_repeat('-', static::strlen($col) + 2) . '+';
				}
				$table .= $cols . PHP_EOL;
			}

			// Set the columns borders
			$table .= '| ' . implode(' | ', $tableRows[$row]) . ' |' . PHP_EOL;

			// Set the thead and table borders-bottom
			if (isset($cols) && ($row === 0 && ! empty($thead) || $row + 1 === $totalRows))
			{
				$table .= $cols . PHP_EOL;
			}
		}

		static::write($table);
	}

	//--------------------------------------------------------------------

	/**
	 * While the library is intended for use on CLI commands,
	 * commands can be called from controllers and elsewhere
	 * so we need a way to allow them to still work.
	 *
	 * For now, just echo the content, but look into a better
	 * solution down the road.
	 *
	 * @param resource $handle
	 * @param string   $string
	 *
	 * @return void
	 */
	protected static function fwrite($handle, string $string)
	{
		if (! CLI::is_cli())
		{
			// @codeCoverageIgnoreStart
			echo $string;
			return;
			// @codeCoverageIgnoreEnd
		}

		fwrite($handle, $string);
	}
	/**
	 * Determines if this request was made from the command line (CLI).
	 *
	 * @return boolean
	 */
    public static function is_cli()
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

	//--------------------------------------------------------------------
}

// Ensure the class is initialized. Done outside of code coverage
// @codeCoverageIgnoreStart
CLI::init();
// @codeCoverageIgnoreEnd
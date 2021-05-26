<?php
namespace Appkita\SPARK;

Class Helps {
    public static function listFiles($dir, $ext='', $include_path= false, &$results = array()) {
        if (!empty($dir) && \file_exists($dir)){
            $files = scandir($dir);
            foreach ($files as $key => $value) {
                $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
                if (!is_dir($path)) {
                    $_ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                    if (!empty($ext)) {
                        if (\strtolower($ext) == $_ext) {
                            $results[] = $path;
                        }
                    } else {
                        $results[] = $path;
                    }
                } else if ($value != "." && $value != "..") {
                    Helps::listFiles($path, $ext, $include_path, $results);
                    if ($include_path) {
                        $results[] = $path;
                    }
                }
            }
        }

        return $results;
    }

    public static function getClassNameFile(string $file) {
        $fp = fopen($file, 'r');
        $class = $namespace = $buffer = '';
        if (\file_exists($file)) {
            $i = 0;
            while (!$class) {
                if (feof($fp)) break;

                $buffer .= fread($fp, 512);
                $tokens = token_get_all($buffer);

                if (strpos($buffer, '{') === false) continue;

                for (;$i<count($tokens);$i++) {
                    if ($tokens[$i][0] === T_NAMESPACE) {
                        for ($j=$i+1;$j<count($tokens); $j++) {
                            if ($tokens[$j][0] === T_STRING) {
                                $namespace .= '\\'.$tokens[$j][1];
                            } else if ($tokens[$j] === '{' || $tokens[$j] === ';') {
                                break;
                            }
                        }
                    }

                    if ($tokens[$i][0] === T_CLASS) {
                        for ($j=$i+1;$j<count($tokens);$j++) {
                            if ($tokens[$j] === '{') {
                                $class = $tokens[$i+2][1];
                            }
                        }
                    }
                }
            }
        }
        return (object) ['namespace'=>$namespace, 'class'=>$class];
    }
}
?>
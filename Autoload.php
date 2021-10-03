<?php
defined('_DIR') || define('_DIR', get_include_path());
$autoload = $die = true;

/**
 * @property string _dir
 */
class Autoload
{

    private $option = [];
    private $require = [];
    private static $files = [];
    private static $aliases = [];
    private $applicationPath = '';
    private $aliasSpaceName = null;
    private $className = '';
    private $basedirMatches = '';
    private static $debugAlias = false;
    private static $autoloadTrace = '';

    //$applicationPath - if u want set application path as main path then u can call application classes like new Model\modelname\filename instead of new \application\Model\modelname\filename
    function __construct($applicationPath = '')
    {
        if ($applicationPath)
            $this->applicationPath = $applicationPath;
        spl_autoload_register(['Autoload', 'autoload']);
        spl_autoload_extensions('.php');
    }

    public static function register()
    {
        global $autoload;
        $autoload = true;
    }

    public static function unregister()
    {
        global $autoload;
        $autoload = false;
    }

    public static function isRegister()
    {
        global $autoload;
        return $autoload;
    }

    public static function dieOnError()
    {
        global $die;
        $die = true;
    }

    public static function undieOnError()
    {
        global $die;
        $die = false;
    }

    //param 1 = namespace
    //param 2 = namespace starts from

    //start namespace with folder Tablet and set start location to /application/Modules
    //\Autoload::alias('Tablet', _DIR . '/application/'._MODULES);

    //start namespace with PHPExcel and set location to application/Package/PHPExcel
    //\Autoload::alias('PHPExcel', 'application/Package/PHPExcel');

    //start namespace with chillerlan and set start location application/Package/Qrcode/src
    //\Autoload::alias('chillerlan', 'application/Package/Qrcode/src');

    public static function alias($baseNamespace, $namespaceBasePath, $debugAlias = null)
    {
        if (isset(self::$aliases[$baseNamespace]) && self::$aliases[$baseNamespace])
            die('Namespace: ' . $baseNamespace . ' allready exists ');
        else
            self::$aliases[$baseNamespace] = str_replace(_DIR, '', $namespaceBasePath);

        if ($debugAlias != null) {

            self::$debugAlias = $debugAlias;
        }
    }

    /**
     * @param $name
     * @param $value
     */
    private function setOption($name, $value)
    {

        $this->option[$name] = $value;
    }

    private function getBasePath($className)
    {

        $strpos = strpos($className, $this->getSeparator());
        if (!$strpos)
            $classBaseName = $className;
        else
            $classBaseName = substr($className, 0, $strpos);
        $classNamespaceName = substr($className, 0, strrpos($className, $this->getSeparator()));

        $nname = (self::$aliases[$classNamespaceName]) ?? '';
        $nbname = (self::$aliases[$classBaseName]) ? $classBaseName : null;
        $nbnameDir = str_replace('//', '/', _DIR . DIRECTORY_SEPARATOR . self::$aliases[$nbname]);

        //root_dir/application/Modules/Model
        if (is_dir(get_include_path() . DIRECTORY_SEPARATOR . $this->applicationPath . DIRECTORY_SEPARATOR . $classBaseName)) {

            $this->_dir = get_include_path() . DIRECTORY_SEPARATOR . $this->applicationPath;
            $this->basedirMatches = 'MATCH 1';
        }
        elseif (isset($nbname) && $nbname !== null && is_dir($nbnameDir)) {

            $this->_dir = $nbnameDir;
            $aliasSpaceName = null;
            $tmp = $nbnameDir . DIRECTORY_SEPARATOR . str_replace($this->getSeparator(), DIRECTORY_SEPARATOR, str_replace($nbname . $this->getSeparator(), '', $className));
            if (is_dir($tmp)) {

                $aliasSpaceName = str_replace($nbname . $this->getSeparator(), '', $className);
            }
            if (is_file($tmp . '.php')) {

                $classTmpName = str_replace($nbname . $this->getSeparator(), '', $className);
                $aliasSpaceName = substr($classTmpName, 0, strrpos($classTmpName, $this->getSeparator()));
            }

            if (!is_dir($tmp) && !is_file($tmp . '.php')) {

                $aliasSpaceName = '';

                $exp = explode($this->getSeparator(), $className);
                $test = $nbnameDir;
                foreach($exp as $name){

                    if( is_dir($test . DIRECTORY_SEPARATOR . $name) ){

                        $test .= DIRECTORY_SEPARATOR . $name;
                    }
                }
                if( $testBaseName = str_replace($nbnameDir, '', $test) ){

                    $aliasSpaceName = substr($testBaseName, strpos($testBaseName, $this->getSeparator()));
                }
            }

            $this->basedirMatches = 'MATCH 1.1';
            $this->aliasSpaceName = $aliasSpaceName;
        }
        elseif (!empty(self::$aliases) && isset($nname) && $nname && is_dir(get_include_path() . DIRECTORY_SEPARATOR . ($nname))) {

            if (similar_text($nname, get_include_path())) {

                $this->_dir = (strlen($nname) > 0 && strpos(get_include_path(),
                        ((string)$nname)) ? '' : get_include_path() . DIRECTORY_SEPARATOR) . $nname;
                $this->basedirMatches = 'MATCH 2.1';
            }
            else {

                $this->_dir = get_include_path() . DIRECTORY_SEPARATOR . ($nname);
                $this->basedirMatches = 'MATCH 2.2';
            }
            $this->aliasSpaceName = $classNamespaceName;
        }
        elseif (!empty(self::$aliases) && isset(self::$aliases[$className]) && self::$aliases[$className] && is_dir(get_include_path() . DIRECTORY_SEPARATOR . (self::$aliases[$className]))) {

            if (similar_text(self::$aliases[$className], get_include_path())) {

                $this->_dir = (strlen($nname) > 0 && strpos(get_include_path(),
                        ((string)$nname)) ? '' : get_include_path() . DIRECTORY_SEPARATOR) . self::$aliases[$className];
                $this->basedirMatches = 'MATCH 3.1';
            }
            else {

                $this->_dir = get_include_path() . DIRECTORY_SEPARATOR . (self::$aliases[$className]);
                $this->basedirMatches = 'MATCH 3.2';
            }
            $this->aliasSpaceName = $className;
        }
        elseif (!empty(self::$aliases) && isset(self::$aliases[$classBaseName]) && self::$aliases[$classBaseName] && is_dir(get_include_path() . DIRECTORY_SEPARATOR . (self::$aliases[$classBaseName]))) {

            if (preg_match('/' . str_replace('/', '\/', get_include_path()) . '/i', self::$aliases[$classBaseName])) {

                $nname = isset($nname) ? $nname : '';
                $this->_dir = (strlen($nname) > 0 && strpos(get_include_path(),
                        ((string)$nname)) ? '' : get_include_path() . DIRECTORY_SEPARATOR) . self::$aliases[$classBaseName];
                $this->basedirMatches = 'MATCH 5.1';
            }
            else {

                $this->_dir = get_include_path() . DIRECTORY_SEPARATOR . (self::$aliases[$classBaseName]);
                $this->basedirMatches = 'MATCH 5.3';
            }
            $this->aliasSpaceName = $classBaseName;
        }
        else {

            $this->_dir = get_include_path();
            $this->basedirMatches = 'MATCH 6';
        }
    }

    private function getSeparator()
    {

        if ($this->option['separator'])
            return $this->option['separator'];
        else
            return '\\';
    }

    private function getPath()
    {

        return $this->option['path'];
    }

    private function getFile()
    {

        return $this->option['file'];
    }

    private function getNamespace()
    {

        return $this->option['namespace'];
    }

    private function autoload($className)
    {
        global $autoload;
        $this->aliasSpaceName = null;
        $this->className = $className;
        self::$autoloadTrace = self::trace();

        if (!class_exists($className, false) && gettype($autoload) == 'boolean' && $autoload == true) {

            if (preg_match('/\\\/i', $className) && substr($className, 0, 1) != '\\')
                $this->setOption('separator', '\\');
            elseif (preg_match('/_/i', $className) && substr($className, 0, 1) != '_')
                $this->setOption('separator', '_');
            else
                $this->setOption('separator', '');

            $this->setOption('namespace', substr($className, 0, strrpos($className, '\\')));
            $this->getBasePath($className);
            $this->setPath($className);
            $this->setFile($className);

            $this->require();
        }
    }

    private function setPath($className)
    {

        if ($this->getNamespace()) {

            $dir = str_replace($this->getSeparator(), DIRECTORY_SEPARATOR, $this->getNamespace());

            if (preg_match('/_/i', $className) && !preg_match('/\_/i', $className) && $this->getSeparator() != '_') {

                $dirTest = str_replace($this->getSeparator(), DIRECTORY_SEPARATOR, substr($className, 0, strrpos($className, '_')));
                $dirTest = str_replace('_', DIRECTORY_SEPARATOR, $dirTest);

                if (is_dir($this->_dir . DIRECTORY_SEPARATOR . $dirTest))
                    $dir = $dirTest;
            }
        }
        else {

            $dir = str_replace($this->getSeparator(), DIRECTORY_SEPARATOR, substr($className, 0, strrpos($className, $this->getSeparator())));
        }

        if ($this->aliasSpaceName !== null) {

            if ($this->aliasSpaceName) {

                $dir = str_replace($this->getSeparator(), DIRECTORY_SEPARATOR, substr($className, 0, strrpos($className, $this->getSeparator())));
                $dirPlusAlias = $this->_dir . DIRECTORY_SEPARATOR . str_replace($this->getSeparator(), DIRECTORY_SEPARATOR, $this->aliasSpaceName);
                $fullDir = str_replace([DIRECTORY_SEPARATOR . str_replace($this->getSeparator(), DIRECTORY_SEPARATOR, $this->aliasSpaceName)], '',
                    $this->_dir . ($dir ? DIRECTORY_SEPARATOR . $dir : ''));
            }
            else {

                $dir = str_replace($this->getSeparator(), DIRECTORY_SEPARATOR, substr($className, 0, strrpos($className, $this->getSeparator())));
                $dirPlusAlias = $this->_dir;
                $fullDir = $this->_dir . ($dir ? DIRECTORY_SEPARATOR . $dir : '');
            }

            if (is_dir($this->_dir . ($dir ? DIRECTORY_SEPARATOR . $dir : ''))) {

                $this->setOption('path', $this->_dir . ($dir ? DIRECTORY_SEPARATOR . $dir : ''));
            }
            elseif (is_dir($dirPlusAlias)) {

                $this->setOption('path', $dirPlusAlias);
            }
            elseif (is_dir($fullDir)) {

                $this->setOption('path', $fullDir);
            }
            else {

                $this->setOption('path', $this->_dir);
            }
        }
        else
            $this->setOption('path', $this->_dir . ($dir ? DIRECTORY_SEPARATOR . $dir : ''));
    }

    private function setFile($className)
    {

        //FOR PSR-0
        if (is_file($this->getPath() . DIRECTORY_SEPARATOR . $className . '.php')) {

            $file = $className . '.php';
        }
        //FOR PSR-0
        elseif (is_file($this->getPath() . DIRECTORY_SEPARATOR . strtolower($className) . '.php')) {

            $file = strtolower($className) . '.php';
        }
        //FOR PSR-1+
        else {

            $file = str_replace($this->getSeparator(), DIRECTORY_SEPARATOR, $className) . '.php';
            if ($this->getSeparator()) {

                $exp = explode($this->getSeparator(), $className);
                $file = str_replace($this->getSeparator(), DIRECTORY_SEPARATOR,
                        end($exp)) . '.php';
            }
            if (preg_match('/_/i', $className) && !preg_match('/\_/i', $className) && $this->getSeparator() != '_') {

                $fileTest = str_replace($this->getSeparator(), DIRECTORY_SEPARATOR, $className) . '.php';
                $fileTest = str_replace('_', DIRECTORY_SEPARATOR, $fileTest);
                if (is_File($this->_dir . DIRECTORY_SEPARATOR . $fileTest)) {

                    $exp = explode(DIRECTORY_SEPARATOR, $fileTest);
                    $file = end($exp);
                }
            }
        }
        $this->setOption('file', $file);
        $this->setDebug();
    }

    /**
     * @return bool
     */
    private function require()
    {

        global $die;

        $file = $this->getPath() . DIRECTORY_SEPARATOR . $this->getFile();

        if (!is_file($file)) {

            if ($die) {

                $strpos = strpos($this->className, $this->getSeparator());
                if (!$strpos)
                    $classBaseName = $this->className;
                else
                    $classBaseName = substr($this->className, 0, $strpos);

                $message = '<div style="padding:20px;background:#ff702b;color:#000000;">' .
                    '<b>ERROR:</b> AUTOLOAD NOT FOUND FILE: ' . $file . '<br><br>' .
                    '<b>BASE MATCH:</b> ' . $this->basedirMatches . ' BASEDIR: ' . $this->_dir . '<br>' .
                    '<b>USE Alias:</b> \Autoload::alias( \'' . $this->className . '\', \'full/location/' . $classBaseName . '\' );<br>' .
                    '<b>CURRENT Aliases:</b> ' . stripslashes(json_encode(self::$aliases, JSON_PRETTY_PRINT)) . '<br>' .
                    '<b>OR Turn off Autoloader:</b> \Autoload::unregister(); After Turn on Autoloader: \Autoload::register();' .
                    '</div>' .
                    '<div style="padding:20px;border:1px solid #ff702b;color:#000000;">' . implode('<br>', $this->require) . '<pre>' . self::$autoloadTrace . '</pre>' . '</div>';

                die($message);
            }
            else
                return false;
        }

        unset($this->require);
        require_once $this->getPath() . DIRECTORY_SEPARATOR . $this->getFile();
        self::$files[$this->className] = $this->getPath() . DIRECTORY_SEPARATOR . $this->getFile();
        return false;
    }

    private static function trace()
    {
        ob_start();
        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $trace = ob_get_contents();
        ob_end_clean();

        return $trace;
    }

    private function setDebug()
    {

        $fullLocation = $this->getPath() . DIRECTORY_SEPARATOR . $this->getFile();
        if (!in_array($fullLocation, (empty($this->require) ? [] : $this->require)))
            $this->require[] = $fullLocation;
    }
}


<?php
/**
 * Description of Amazon_Autoloader
 *
 * @author gambit
 * @todo Add namespace support!!!
 */ 
    
class Amazon_Autoloader
{
    public static $loader;

    public static function init()
    {
        if (self::$loader == NULL)
        {
            self::$loader = new self();
        }
        return self::$loader;
    }
    
    public function __construct()
    {
        spl_autoload_register(array($this,'amazon_loader'));
    }

    /**
     * Load UserClass
     * @param string $class
     */
    private function amazon_loader($className)
    {
        //echo "\nAmazon Loader get class [".$className."]\n";
        $parentPath = dirname(__FILE__) . DIRECTORY_SEPARATOR;
        $filePath = str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
        if (file_exists($parentPath . $filePath))
        {
            //echo "Amazon Loader class found! [".$parentPath . $filePath."]\n";
            require_once $parentPath . $filePath;
            return;
        }
        else
        {
            //echo "Amazon Loader class not found! [".$parentPath . $filePath."]\n";
        }
    }
}
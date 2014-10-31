<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Cache
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */


/**
 * @package    Zend_Cache
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Cache
{

    /**
     * Standard frontends
     *
     * @var array
     */
    public static $standardFrontends = array('Core', 'Output', 'Class', 'File', 'Function', 'Page');
    
    /**
     * Standard backends
     *
     * @var array
     */
    public static $standardBackends = array('File', 'Sqlite', 'Memcached', 'Apc', 'ZendPlatform');
    
    /**
     * Only for backward compatibily (will be removed in 1.2.0)
     *
     * @var array
     */
    public static $availableFrontends = array('Core', 'Output', 'Class', 'File', 'Function', 'Page');
    
    /**
     * Only for backward compatibily (will be removed in 1.2.0)
     *
     * @var array
     */
    public static $availableBackends = array('File', 'Sqlite', 'Memcached', 'Apc', 'ZendPlatform');
     
    /**
     * Consts for clean() method
     */
    const CLEANING_MODE_ALL              = 'all';
    const CLEANING_MODE_OLD              = 'old';
    const CLEANING_MODE_MATCHING_TAG     = 'matchingTag';
    const CLEANING_MODE_NOT_MATCHING_TAG = 'notMatchingTag';

    /**
     * Factory
     *
     * @param string $frontend        frontend name
     * @param string $backend         backend name
     * @param array  $frontendOptions associative array of options for the corresponding frontend constructor
     * @param array  $backendOptions  associative array of options for the corresponding backend constructor
     * @throws Zend_Cache_Exception
     * @return Zend_Cache_Frontend
     */
    public static function factory($frontend, $backend, $frontendOptions = array(), $backendOptions = array())
    {

        // because lowercase will fail
        $frontend = self::_normalizeName($frontend);
        $backend  = self::_normalizeName($backend);

        // working on the frontend
        if (in_array($frontend, self::$availableFrontends)) {
            // we use a standard frontend
            // For perfs reasons, with frontend == 'Core', we can interact with the Core itself
            $frontendClass = 'Zend_Cache_' . ($frontend != 'Core' ? 'Frontend_' : '') . $frontend;
            // For perfs reasons, we do not use the Zend_Loader::loadClass() method
            // (security controls are explicit)
            require_once LS_DKCACHE_PATH.str_replace('_', DIRECTORY_SEPARATOR, $frontendClass) . '.php';
        } else {
            // we use a custom frontend
            $frontendClass = 'Zend_Cache_Frontend_' . $frontend;
            // To avoid security problems in this case, we use Zend_Loader to load the custom class
            require_once LS_DKCACHE_PATH.'Zend/Loader.php';
            $file = str_replace('_', DIRECTORY_SEPARATOR, $frontendClass) . '.php';
            if (!(Zend_Loader::isReadable($file))) {
                self::throwException("file $file not found in include_path");
            }
            Zend_Loader::loadClass($frontendClass);
        }
        
        // working on the backend
        if (in_array($backend, Zend_Cache::$availableBackends)) {
            // we use a standard backend
            $backendClass = 'Zend_Cache_Backend_' . $backend;
            // For perfs reasons, we do not use the Zend_Loader::loadClass() method
            // (security controls are explicit)
            require_once LS_DKCACHE_PATH.str_replace('_', DIRECTORY_SEPARATOR, $backendClass) . '.php';
        } else {
            // we use a custom backend
            $backendClass = 'Zend_Cache_Backend_' . $backend;
            // To avoid security problems in this case, we use Zend_Loader to load the custom class
            require_once LS_DKCACHE_PATH.'Zend/Loader.php';
            $file = str_replace('_', DIRECTORY_SEPARATOR, $backendClass) . '.php';
            if (!(Zend_Loader::isReadable($file))) {
                self::throwException("file $file not found in include_path");
            }
            Zend_Loader::loadClass($backendClass);
        }
        
        // Making objects
        $frontendObject = new $frontendClass($frontendOptions);
        $backendObject = new $backendClass($backendOptions);
        $frontendObject->setBackend($backendObject);
        return $frontendObject;

    }

    /**
     * Throw an exception
     *
     * Note : for perf reasons, the "load" of Zend/Cache/Exception is dynamic
     * @param  string $msg  Message for the exception
     * @throws Zend_Cache_Exception
     */
    public static function throwException($msg)
    {
        // For perfs reasons, we use this dynamic inclusion
        require_once LS_DKCACHE_PATH.'Zend/Cache/Exception.php';
        throw new Zend_Cache_Exception($msg);
    }

    /**
     * Normalize frontend and backend names to allow multiple words TitleCased
     *
     * @param  string $name  Name to normalize
     * @return string
     */
    protected static function _normalizeName($name)
    {
        $name = ucfirst(strtolower($name));
        $name = str_replace(array('-', '_', '.'), ' ', $name);
        $name = ucwords($name);
        $name = str_replace(' ', '', $name);
        return $name;
    }

}

class Zend_Exception extends Exception
{}

class Zend_Cache_Backend
{
    /**
     * Frontend or Core directives
     *
     * =====> (int) lifetime :
     * - Cache lifetime (in seconds)
     * - If null, the cache is valid forever
     *
     * =====> (int) logging :
     * - if set to true, a logging is activated throw Zend_Log
     *
     * @var array directives
     */
    protected $_directives = array(
        'lifetime' => 3600,
        'logging'  => false,
        'logger'   => null
    );

    /**
     * Available options
     *
     * @var array available options
     */
    protected $_options = array();

    /**
     * Constructor
     *
     * @param  array $options Associative array of options
     * @throws Zend_Cache_Exception
     * @return void
     */
    public function __construct($options = array())
    {
        if (!is_array($options)) {
            Zend_Cache::throwException('Options parameter must be an array');
        }
        while (list($name, $value) = each($options)) {
            $this->setOption($name, $value);
        }
    }

    /**
     * Set the frontend directives
     *
     * @param  array $directives Assoc of directives
     * @throws Zend_Cache_Exception
     * @return void
     */
    public function setDirectives($directives)
    {
        if (!is_array($directives)) Zend_Cache::throwException('Directives parameter must be an array');
        while (list($name, $value) = each($directives)) {
            if (!is_string($name)) {
                Zend_Cache::throwException("Incorrect option name : $name");
            }
            $name = strtolower($name);
            if (array_key_exists($name, $this->_directives)) {
                $this->_directives[$name] = $value;
            }

        }

        $this->_loggerSanity();
    }

    /**
     * Set an option
     *
     * @param  string $name
     * @param  mixed  $value
     * @throws Zend_Cache_Exception
     * @return void
     */
    public function setOption($name, $value)
    {
        if (!is_string($name)) {
            Zend_Cache::throwException("Incorrect option name : $name");
        }
        $name = strtolower($name);
        if (!array_key_exists($name, $this->_options)) {
            Zend_Cache::throwException("Incorrect option name : $name");
        }
        $this->_options[$name] = $value;
    }

    /**
     * Get the life time
     *
     * if $specificLifetime is not false, the given specific life time is used
     * else, the global lifetime is used
     *
     * @param  int $specificLifetime
     * @return int Cache life time
     */
    public function getLifetime($specificLifetime)
    {
        if ($specificLifetime === false) {
            return $this->_directives['lifetime'];
        }
        return $specificLifetime;
    }

    /**
     * Return true if the automatic cleaning is available for the backend
     *
     * @return boolean
     */
    public function isAutomaticCleaningAvailable()
    {
        return true;
    }

    /**
     * Return a system-wide tmp directory
     *
     * @return string System-wide tmp directory
     */
    static function getTmpDir()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // windows...
            foreach (array($_ENV, $_SERVER) as $tab) {
                foreach (array('TEMP', 'TMP', 'windir', 'SystemRoot') as $key) {
                    if (isset($tab[$key])) {
                        $result = $tab[$key];
                        if (($key == 'windir') or ($key == 'SystemRoot')) {
                            $result = $result . '\\temp';
                        }
                        return $result;
                    }
                }
            }
            return '\\temp';
        } else {
            // unix...
            if (isset($_ENV['TMPDIR']))    return $_ENV['TMPDIR'];
            if (isset($_SERVER['TMPDIR'])) return $_SERVER['TMPDIR'];
            return '/tmp';
        }
    }

    /**
     * Make sure if we enable logging that the Zend_Log class
     * is available.
     * Create a default log object if none is set.
     *
     * @throws Zend_Cache_Exception
     * @return void
     */
    protected function _loggerSanity()
    {
        if (!isset($this->_directives['logging']) || !$this->_directives['logging']) {
            return;
        }
        try {
            /**
             * @see Zend_Loader
             * @see Zend_Log
             */
            require_once LS_DKCACHE_PATH.'Zend/Loader.php';
            Zend_Loader::loadClass('Zend_Log');
        } catch (Zend_Exception $e) {
            Zend_Cache::throwException('Logging feature is enabled but the Zend_Log class is not available');
        }
        if (isset($this->_directives['logger']) && $this->_directives['logger'] instanceof Zend_Log) {
            return;
        }
        // Create a default logger to the standard output stream
        Zend_Loader::loadClass('Zend_Log_Writer_Stream');
        $logger = new Zend_Log(new Zend_Log_Writer_Stream('php://output'));
        $this->_directives['logger'] = $logger;
    }

    /**
     * Log a message at the WARN (4) priority.
     *
     * @param  string $message
     * @throws Zend_Cache_Exception
     * @return void
     */
    protected function _log($message, $priority = 4)
    {
        if (!$this->_directives['logging']) {
            return;
        }
        if (!(isset($this->_directives['logger']) || $this->_directives['logger'] instanceof Zend_Log)) {
            Zend_Cache::throwException('Logging is enabled but logger is not set');
        }
        $logger = $this->_directives['logger'];
        $logger->log($message, $priority);
    }

}

class Zend_Cache_Exception extends Zend_Exception {}

class Zend_Cache_Backend_APC extends Zend_Cache_Backend implements Zend_Cache_Backend_Interface
{
    /**
     * Persistent
     */
    const DEFAULT_PERSISTENT = true;

    /**
     * Constructor
     *
     * @param array $options associative array of options
     * @throws Zend_Cache_Exception
     * @return void
     */
    public function __construct($options = array())
    {
        if (!extension_loaded('apc')) {
            Zend_Cache::throwException('The apc extension must be loaded for using this backend !');
        }
    }

    /**
     * Test if a cache is available for the given id and (if yes) return it (false else)
     *
     * @param  string  $id                     Cache id
     * @param  boolean $doNotTestCacheValidity If set to true, the cache validity won't be tested
     * @return string|false cached datas
     */
    public function load($id, $doNotTestCacheValidity = false)
    {
        // WARNING : $doNotTestCacheValidity is not supported !!!
        if ($doNotTestCacheValidity) {
            $this->_log("Zend_Cache_Backend_APC::load() : \$doNotTestCacheValidity=true is unsupported by the APC backend");
        }

        $tmp = apc_fetch($id);
        if (is_array($tmp)) {
            return $tmp[0];
        }
        return false;
    }

    /**
     * Test if a cache is available or not (for the given id)
     *
     * @param  string $id Cache id
     * @return mixed|false (a cache is not available) or "last modified" timestamp (int) of the available cache record
     */
    public function test($id)
    {
        $tmp = apc_fetch($id);
        if (is_array($tmp)) {
            return $tmp[1];
        }
        return false;
    }

    /**
     * Save some string datas into a cache record
     *
     * Note : $data is always "string" (serialization is done by the
     * core not by the backend)
     *
     * @param  string $data             Datas to cache
     * @param  string $id               Cache id
     * @param  array  $tags             Array of strings, the cache record will be tagged by each string entry
     * @param  int    $specificLifetime If != false, set a specific lifetime for this cache record (null => infinite lifetime)
     * @return boolean True if no problem
     */
    public function save($data, $id, $tags = array(), $specificLifetime = false)
    {
        $lifetime = $this->getLifetime($specificLifetime);

        $result = apc_store($id, array($data, time()), $lifetime);
        return $result;
    }

    /**
     * Remove a cache record
     *
     * @param  string $id Cache id
     * @return boolean True if no problem
     */
    public function remove($id)
    {
        return apc_delete($id);
    }

    /**
     * Clean some cache records
     *
     * Available modes are :
     * 'all' (default)  => remove all cache entries ($tags is not used)
     * 'old'            => remove too old cache entries ($tags is not used)
     * 'matchingTag'    => remove cache entries matching all given tags
     *                     ($tags can be an array of strings or a single string)
     * 'notMatchingTag' => remove cache entries not matching one of the given tags
     *                     ($tags can be an array of strings or a single string)
     *
     * @param  string $mode Clean mode
     * @param  array  $tags Array of tags
     * @return boolean True if no problem
     */
    public function clean($mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array())
    {
        return true;
    }

    /**
     * Return true if the automatic cleaning is available for the backend
     *
     * @return boolean
     */
    public function isAutomaticCleaningAvailable()
    {
        return false;
    }

}

interface Zend_Cache_Backend_Interface
{
    /**
     * Set the frontend directives
     *
     * @param array $directives assoc of directives
     */
    public function setDirectives($directives);

    /**
     * Test if a cache is available for the given id and (if yes) return it (false else)
     *
     * Note : return value is always "string" (unserialization is done by the core not by the backend)
     *
     * @param  string  $id                     Cache id
     * @param  boolean $doNotTestCacheValidity If set to true, the cache validity won't be tested
     * @return string|false cached datas
     */
    public function load($id, $doNotTestCacheValidity = false);

    /**
     * Test if a cache is available or not (for the given id)
     *
     * @param  string $id cache id
     * @return mixed|false (a cache is not available) or "last modified" timestamp (int) of the available cache record
     */
    public function test($id);

    /**
     * Save some string datas into a cache record
     *
     * Note : $data is always "string" (serialization is done by the
     * core not by the backend)
     *
     * @param  string $data            Datas to cache
     * @param  string $id              Cache id
     * @param  array $tags             Array of strings, the cache record will be tagged by each string entry
     * @param  int   $specificLifetime If != false, set a specific lifetime for this cache record (null => infinite lifetime)
     * @return boolean true if no problem
     */
    public function save($data, $id, $tags = array(), $specificLifetime = false);

    /**
     * Remove a cache record
     *
     * @param  string $id Cache id
     * @return boolean True if no problem
     */
    public function remove($id);

    /**
     * Clean some cache records
     *
     * Available modes are :
     * Zend_Cache::CLEANING_MODE_ALL (default)    => remove all cache entries ($tags is not used)
     * Zend_Cache::CLEANING_MODE_OLD              => remove too old cache entries ($tags is not used)
     * Zend_Cache::CLEANING_MODE_MATCHING_TAG     => remove cache entries matching all given tags
     *                                               ($tags can be an array of strings or a single string)
     * Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG => remove cache entries not {matching one of the given tags}
     *                                               ($tags can be an array of strings or a single string)
     *
     * @param  string $mode Clean mode
     * @param  array  $tags Array of tags
     * @return boolean true if no problem
     */
    public function clean($mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array());

}

class Zend_Cache_Backend_Memcached extends Zend_Cache_Backend implements Zend_Cache_Backend_Interface
{
    /**
     * Default Host IP Address or DNS
     */
    const DEFAULT_HOST       = '127.0.0.1';

    /**
     * Default port
     */
    const DEFAULT_PORT       = 11211;

    /**
     * Persistent
     */
    const DEFAULT_PERSISTENT = true;

    /**
     * Available options
     *
     * =====> (array) servers :
     * an array of memcached server ; each memcached server is described by an associative array :
     * 'host' => (string) : the name of the memcached server
     * 'port' => (int) : the port of the memcached server
     * 'persistent' => (bool) : use or not persistent connections to this memcached server
     *
     * =====> (boolean) compression :
     * true if you want to use on-the-fly compression
     *
     * @var array available options
     */
    protected $_options = array(
        'servers' => array(array(
            'host' => Zend_Cache_Backend_Memcached::DEFAULT_HOST,
            'port' => Zend_Cache_Backend_Memcached::DEFAULT_PORT,
            'persistent' => Zend_Cache_Backend_Memcached::DEFAULT_PERSISTENT
        )),
        'compression' => false
    );

    /**
     * Memcache object
     *
     * @var mixed memcache object
     */
    private $_memcache = null;

    /**
     * Constructor
     *
     * @param array $options associative array of options
     * @throws Zend_Cache_Exception
     * @return void
     */
    public function __construct($options = array())
    {
        if (!extension_loaded('memcache')) {
            Zend_Cache::throwException('The memcache extension must be loaded for using this backend !');
        }
        parent::__construct($options);
        if (isset($this->_options['servers'])) {
            $value= $this->_options['servers'];
            if (isset($value['host'])) {
                // in this case, $value seems to be a simple associative array (one server only)
                $value = array(0 => $value); // let's transform it into a classical array of associative arrays
            }
            $this->setOption('servers', $value);
        }
        $this->_memcache = new Memcache;
        foreach ($this->_options['servers'] as $server) {
            if (!array_key_exists('persistent', $server)) {
                $server['persistent'] = Zend_Cache_Backend_Memcached::DEFAULT_PERSISTENT;
            }
            if (!array_key_exists('port', $server)) {
                $server['port'] = Zend_Cache_Backend_Memcached::DEFAULT_PORT;
            }
            $this->_memcache->addServer($server['host'], $server['port'], $server['persistent']);
        }
    }

    /**
     * Test if a cache is available for the given id and (if yes) return it (false else)
     *
     * @param  string  $id                     Cache id
     * @param  boolean $doNotTestCacheValidity If set to true, the cache validity won't be tested
     * @return string|false cached datas
     */
    public function load($id, $doNotTestCacheValidity = false)
    {
        // WARNING : $doNotTestCacheValidity is not supported !!!
        if ($doNotTestCacheValidity) {
            $this->_log("Zend_Cache_Backend_Memcached::load() : \$doNotTestCacheValidity=true is unsupported by the Memcached backend");
        }
        
        $tmp = $this->_memcache->get($id);
        if (is_array($id)) {
        	if (is_array($tmp)) {
        		$aDataReturn=array();
        		foreach ($tmp as $key => $data) {
        			if (is_array($data)) {
            			$aDataReturn[$key]=$data[0];
        			}
        		}
        		if (count($aDataReturn)>0) {
        			return $aDataReturn;
        		}
        	}        	
        } else {
        	if (is_array($tmp)) {
            	return $tmp[0];
        	}	
        }        
        return false;
    }

    /**
     * Test if a cache is available or not (for the given id)
     *
     * @param  string $id Cache id
     * @return mixed|false (a cache is not available) or "last modified" timestamp (int) of the available cache record
     */
    public function test($id)
    {
        $tmp = $this->_memcache->get($id);
        if (is_array($tmp)) {
            return $tmp[1];
        }
        return false;
    }

    /**
     * Save some string datas into a cache record
     *
     * Note : $data is always "string" (serialization is done by the
     * core not by the backend)
     *
     * @param  string $data             Datas to cache
     * @param  string $id               Cache id
     * @param  array  $tags             Array of strings, the cache record will be tagged by each string entry
     * @param  int    $specificLifetime If != false, set a specific lifetime for this cache record (null => infinite lifetime)
     * @return boolean True if no problem
     */
    public function save($data, $id, $tags = array(), $specificLifetime = false)
    {
        $lifetime = $this->getLifetime($specificLifetime);
        if ($this->_options['compression']) {
            $flag = MEMCACHE_COMPRESSED;
        } else {
            $flag = 0;
        }
        $result = $this->_memcache->set($id, array($data, time()), $flag, $lifetime);
        if (count($tags) > 0) {
            $this->_log("Zend_Cache_Backend_Memcached::save() : tags are unsupported by the Memcached backend");
        }
        return $result;
    }

    /**
     * Remove a cache record
     *
     * @param  string $id Cache id
     * @return boolean True if no problem
     */
    public function remove($id)
    {
        return $this->_memcache->delete($id,0);
    }

    /**
     * Clean some cache records
     *
     * Available modes are :
     * 'all' (default)  => remove all cache entries ($tags is not used)
     * 'old'            => remove too old cache entries ($tags is not used)
     * 'matchingTag'    => remove cache entries matching all given tags
     *                     ($tags can be an array of strings or a single string)
     * 'notMatchingTag' => remove cache entries not matching one of the given tags
     *                     ($tags can be an array of strings or a single string)
     *
     * @param  string $mode Clean mode
     * @param  array  $tags Array of tags
     * @return boolean True if no problem
     */
    public function clean($mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array())
    {
        if ($mode==Zend_Cache::CLEANING_MODE_ALL) {
            return $this->_memcache->flush();
        }
        if ($mode==Zend_Cache::CLEANING_MODE_OLD) {
            $this->_log("Zend_Cache_Backend_Memcached::clean() : CLEANING_MODE_OLD is unsupported by the Memcached backend");
        }
        if ($mode==Zend_Cache::CLEANING_MODE_MATCHING_TAG) {
            $this->_log("Zend_Cache_Backend_Memcached::clean() : tags are unsupported by the Memcached backend");
        }
        if ($mode==Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG) {
            $this->_log("Zend_Cache_Backend_Memcached::clean() : tags are unsupported by the Memcached backend");
        }
    }

    /**
     * Return true if the automatic cleaning is available for the backend
     *
     * @return boolean
     */
    public function isAutomaticCleaningAvailable()
    {
        return false;
    }

}
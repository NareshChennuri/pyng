<?php

class JO_Db_DataSource {
	const SUPPLIER_SERVER     = 'master';
	const CONSUMER_SERVER     = 'slave';
	const ACTIVE_CONNECTION   = '%s_datasource_active_connection_%s';
	const FAILED_CONNECTIONS  = '%s_datasource_failed_connections';
	
	/**
	 * @var array
	 */
	private $config = array();
	
	/**
	 * @var JO_Cache_Core
	 */
	private $cache = null;
	
	/**
	 * @var string
	 */
	private $cacheTag = '';
	
	/**
	 * @var array
	 */
	private $connections = array();
	
	/**
	 * Class constructor.
	 *
	 * @param array|JO_Config $config
	 * @param JO_Cache_Core $cache
	 * @param string $cacheTag
	 */
	public function __construct($config, JO_Cache_Abstract $cache, $cacheTag)
	{
		$this->setConfig($config);
		$this->setCache($cache);
		$this->setCacheTag($cacheTag);
	}
	
	/**
	 * Set configuration array.
	 *
	 * @param array|JO_Config $config
	 * @return void
	 */
	public function setConfig($config)
	{
		if ($config instanceof JO_Config) {
			$config = $config->toArray();
		}
		$this->config = $config;
	}
	
	/**
	 * Return configuration array.
	 *
	 * @return array
	 */
	public function getConfig()
	{
		return $this->config;
	}
	
	/**
	 * Set instance of JO_Cache_Core.
	 *
	 * @param JO_Cache_Core $cache
	 * @return void
	 */
	public function setCache(JO_Cache_Abstract $cache)
	{
		$this->cache = $cache;
	}
	
	/**
	 * Return instance of JO_Cache_Core.
	 *
	 * @return JO_Cache_Core
	 */
	public function getCache()
	{
		return $this->cache;
	}
	
	/**
	 * Set cache tag name.
	 *
	 * @param string
	 * @return void
	 */
	public function setCacheTag($name)
	{
		$this->cacheTag = $name;
	}
	
	/**
	 * Return cache tag name.
	 *
	 * @return string
	 */
	public function getCacheTag()
	{
		return $this->cacheTag;
	}
	
	/**
	 * Set an instance of JO_Db_Adapter_Abstract.
	 *
	 * @param JO_Db_Adapter_Abstract $conn
	 * @param string $server Options: master, slave
	 * @return void
	 */
	public function setConnection(JO_Db_Adapter_Abstract $conn, $server)
	{
		$namespace = sprintf(self::ACTIVE_CONNECTION, $this->getCacheTag(), strtolower($server));
		$this->connections[$namespace] = $conn;
	}
	
	/**
	 * Return an instance of JO_Db_Adapter_Abstract.
	 *
	 * @param string $server master (supplier) or slave (consumer)
	 * @return JO_Db_Adapter_Abstract
	 * @throws JO_Db_Exception
	 */
	public function getConnection($server)
	{
		$server = strtolower($server);
		$namespace = sprintf(self::ACTIVE_CONNECTION, $this->getCacheTag(), $server);
		if ($this->hasConnection($namespace)) {
			return $this->connections[$namespace];
		}
	
		$failedCacheKey = sprintf(self::FAILED_CONNECTIONS, $this->getCacheTag());
		$result = $this->getCache()->get($failedCacheKey);
		$failed = ($result && is_array($result)) ? $result : array();
	
		$servers = $this->getListOfServers($server);
		$keys = count($servers) ? (array) array_rand($servers, count($servers)) : array();
		foreach ($keys as $i => $key) {
			if (in_array($key, $failed)) {
				continue;
			}
			$connection = $this->createConnection($servers[$key]);
			if ($connection instanceof JO_Db_Adapter_Abstract) {
				$this->setConnection($connection, $server);
				return $connection;
			}
			$failed[] = $key;
			$this->getCache()->add($failedCacheKey, array_unique($failed), array(), 30);
		}
		throw new JO_Db_Exception(sprintf('Unable to connect to "%s" server', $server));
	}
	
	/**
	 * Verify that a given connection name exists.
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function hasConnection($name)
	{
		return array_key_exists($name, $this->connections);
	}
	
	/**
	 * Create an instance of JO_Db_Adapter_Abstract.
	 *
	 * @param array $server master (supplier) or slave (consumer)
	 * @return JO_Db_Adapter_Abstract|false
	 * @see JO_Db
	 */
	public function createConnection($server)
	{
		$config = $this->getConfig();
		foreach ($config as $key => $value) {
			if ('servers' !== $key && !array_key_exists($key, $server)) {
				$server[$key] = $value;
			}
		} 
		$db = JO_Db::factory($config['adapter'], $server);
		
		if ($this->isConnected($db)) {
			return $db;
		}
		return false;
	}
	
	/**
	 * Verify that we have a valid connection.
	 *
	 * @param JO_Db_Adapter_Abstract $adapter
	 * @return boolean
	 * @throws JO_Db_Exception
	 */
	public function isConnected(JO_Db_Adapter_Abstract $adapter)
	{
		try {
			return ($adapter->getConnection()) ? true : false;
		} catch (JO_Exception $e) {
			return false;
			//throw new JO_Db_Exception($e->getMessage());
		}
	}
	
	/**
	 * Return list of database servers that will be used to create a
	 * connection.
	 *
	 * @param string $server master (supplier) or slave (consumer)
	 * @return array
	 */
	public function getListOfServers($server)
	{
		$config = $this->getConfig();
		$servers = (isset($config['servers'])) ? $config['servers'] : array();
		$masterServers = (isset($config['master_servers'])) ? $config['master_servers'] : 1;
		if (self::SUPPLIER_SERVER === $server) {
			$servers = array_slice($servers, 0, $masterServers);
		} elseif (self::CONSUMER_SERVER === $server) {
			$masterRead = (isset($config['master_read'])) ? $config['master_read'] : false;
			if (false === $masterRead) {
				$servers = array_slice($servers, $masterServers, count($servers), true);
			}
		}
		return $servers;
	}
}

?>
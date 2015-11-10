<?php
/**
 * JO Framework
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
 * @category   JO
 * @package    JO_Db
 * @subpackage Table
 * @copyright  Copyright (c) 2005-2010 JO Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Definition.php 20096 2010-01-06 02:05:09Z bkarwin $
 */

/**
 * Class for SQL table interface.
 *
 * @category   JO
 * @package    JO_Db
 * @subpackage Table
 * @copyright  Copyright (c) 2005-2010 JO Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class JO_Db_Table_Definition
{

    /**
     * @var array
     */
    protected $_tableConfigs = array();

    /**
     * __construct()
     *
     * @param array|JO_Config $options
     */
    public function __construct($options = null)
    {
        if ($options instanceof JO_Config) {
            $this->setConfig($options);
        } elseif (is_array($options)) {
            $this->setOptions($options);
        }
    }

    /**
     * setConfig()
     *
     * @param JO_Config $config
     * @return JO_Db_Table_Definition
     */
    public function setConfig(JO_Config $config)
    {
        $this->setOptions($config->toArray());
        return $this;
    }

    /**
     * setOptions()
     *
     * @param array $options
     * @return JO_Db_Table_Definition
     */
    public function setOptions(Array $options)
    {
        foreach ($options as $optionName => $optionValue) {
            $this->setTableConfig($optionName, $optionValue);
        }
        return $this;
    }

    /**
     * @param string $tableName
     * @param array  $tableConfig
     * @return JO_Db_Table_Definition
     */
    public function setTableConfig($tableName, array $tableConfig)
    {
        // @todo logic here
        $tableConfig[JO_Db_Table::DEFINITION_CONFIG_NAME] = $tableName;
        $tableConfig[JO_Db_Table::DEFINITION] = $this;

        if (!isset($tableConfig[JO_Db_Table::NAME])) {
            $tableConfig[JO_Db_Table::NAME] = $tableName;
        }

        $this->_tableConfigs[$tableName] = $tableConfig;
        return $this;
    }

    /**
     * getTableConfig()
     *
     * @param string $tableName
     * @return array
     */
    public function getTableConfig($tableName)
    {
        return $this->_tableConfigs[$tableName];
    }

    /**
     * removeTableConfig()
     *
     * @param string $tableName
     */
    public function removeTableConfig($tableName)
    {
        unset($this->_tableConfigs[$tableName]);
    }

    /**
     * hasTableConfig()
     *
     * @param string $tableName
     * @return bool
     */
    public function hasTableConfig($tableName)
    {
        return (isset($this->_tableConfigs[$tableName]));
    }

}

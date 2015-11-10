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
 * @subpackage Adapter
 * @copyright  Copyright (c) 2005-2010 JO Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Exception.php 20629 2010-01-25 21:17:23Z ralph $
 */

/**
 * @see JO_Db_Adapter_Exception
 */
require_once 'JO/Db/Adapter/Exception.php';

/**
 * JO_Db_Adapter_Sqlsrv_Exception
 *
 * @category   JO
 * @package    JO_Db
 * @subpackage Adapter
 * @copyright  Copyright (c) 2005-2010 JO Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class JO_Db_Adapter_Sqlsrv_Exception extends JO_Db_Adapter_Exception
{
    /**
     * Constructor
     *
     * If $message is an array, the assumption is that the return value of
     * sqlsrv_errors() was provided. If so, it then retrieves the most recent
     * error from that stack, and sets the message and code based on it.
     *
     * @param null|array|string $message
     * @param null|int $code
     */
    public function __construct($message = null, $code = 0)
    {
       if (is_array($message)) {
            // Error should be array of errors
            // We only need first one (?)
            if (isset($message[0])) {
                $message = $message[0];
            }

            $code    = (int)    $message['code'];
            $message = (string) $message['message'];
       }
       parent::__construct($message, $code, new Exception($message, $code));
   }
}

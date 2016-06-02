<?php
/**
 * Chronolabs REST Session Identity Selector API
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       Chronolabs Cooperative http://labs.coop
 * @license         GNU GPL 2 (http://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
 * @package         identity
 * @since           1.0.2
 * @author          Simon Roberts <meshy@labs.coop>
 * @version         $Id: functions.php 1000 2013-06-07 01:20:22Z mynamesnot $
 * @subpackage		api
 * @description		Screening API Service REST
 * @link			https://screening.labs.coop Screening API Service Operates from this URL
 * @filesource
 */


include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'identityhash.php';

if (!class_exists('identityHashesAPI')) {
	
	/**
	 * 
	 * @author syrus
	 *
	 */
	class identityHashesAPI {
		
		var $identityHashes = array();
		
		function __construct() {
			$this->identityHashes = $this->getHashesArray();
		}
		
		/**
		 *
		 * @param string $single
		 * @param system $class
		 * @return unknown
		 */
		static public function getInstance($single = true, $class = __CLASS__)
		{
			if ($single==true) {
				static $_object;
				if (!is_a($_object, $class))
					$_object = new $class();
				return $_object;
			}
			return new $class();
		}
		
		function __call($method, $variables) {
			if (!isset($variables[0])) {
				return array('error'=>'Neither $_GET["data"] or $_POST["data"] is set there is no data to checksum at the moment');
			}
			if (!in_array($method, array_keys($this->identityHashes))) {
				return array('error'=>'The identityHashing algoritm selected is not valid you may choose from the options of: '. implode(', ', array_keys($this->identityHashes)));
			}	
			return $this->identityHashes[$method]['class']->calc($variables[0]);
		}
		
		private function getHashesArray() 
		{
			static $identityHashes = array();
			if (empty($identityHashes) && count($identityHashes)==0) {
				foreach($this->getDirListAsArray(dirname(__FILE__)) as $dir) {
					if (file_exists($filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $dir . '.php')) {
						if (!class_exists($class_name = ucfirst(strtolower($dir)) . 'HashAPI')) {
							include_once($filename);
							if (class_exists($class_name)) {
								$identityHashes[$dir]['class'] = new $class_name();
								$identityHashes[$dir]['info'] = $identityHashes[$dir]['class']->getInfo();
							}
						}
					}
				}
			}
			return $identityHashes;
		}
		
		/**
		 * gets list of name of directories inside a directory
		 */
		private function getDirListAsArray($dirname)
		{
			$ignored = array('cvs' , '_darcs');
			$list = array();
            if (substr($dirname, - 1) != '/') {
                $dirname .= '/';
            }
            if ($handle = opendir($dirname)) {
                while ($file = readdir($handle)) {
                    if (substr($file, 0, 1) == '.' || in_array(strtolower($file), $ignored))
                        continue;
                    if (is_dir($dirname . $file)) {
                        $list[$file] = $file;
                    }
                }
                closedir($handle);
                asort($list);
                reset($list);
            }
            return $list;
        }
	}
}
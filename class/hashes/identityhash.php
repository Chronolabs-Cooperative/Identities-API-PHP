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

if (!class_exists('identityHashAPI')) {
	
	/**
	 * 
	 * @author syrus
	 *
	 */
	class identityHashAPI {
		
		var $data = array();
		
		public function setInfo($info) {
			$this->data['info'] = $info;
		}
		
		public function getInfo() {
			return $this->data['info'];
		}

		public function setVariables($variables) {
			$this->data['vars'] = $variables;
		}
		
		public function getVariables() {
			return $this->data['vars'];
		}
		
		public function calc($data) {
			$ret = array();
			$ret['seconds']['start'] = microtime(true);
			$func = $this->data['info']['function'];
			$number = isset($this->data['vars']['number'])?$this->data['vars']['number']:0;
			if (!empty($func) && function_exists($func) && $number == 0) {
				$ret['identityHash'] = $func($data);
			} elseif (!empty($func) && function_exists($func) && $number > 0) {
				$vars = array();
				foreach($this->data['vars']['defines'] as $var => $data) {
					if (!isset($_GET[$data['variable']]) && !isset($_POST[$data['variable']])) {
						$vars[$var] = $data['default'];
					} else {
						$vars[$var] = (!isset($_GET[$data['variable']])?$_POST[$data['variable']]:$_GET[$data['variable']]);
					}
				}
				$ret['identityHash'] = $func($data, $vars);
			}
			$ret['seconds']['end'] = microtime(true);
			$ret['seconds']['took'] = $ret['seconds']['end'] - $ret['seconds']['start'];
			return $ret; 
		}
	}	
}
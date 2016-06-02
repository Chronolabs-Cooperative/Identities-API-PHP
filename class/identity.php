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
 * @link			https://identity.labs.coop Screening API Service Operates from this URL
 * @filesource
 */


include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'hashes' . DIRECTORY_SEPARATOR . 'identityHashes.php';
include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'identitycache.php';
include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'file' . DIRECTORY_SEPARATOR . 'identityfile.php';

if (!class_exists('identity')) {
	
	/**
	 * 
	 * @author syrus
	 *
	 */
	class identity {
		
		/** 
		 *   Presets available
		 *   Loaded from ./preset.diz
		 */
		var $_preset = array();
		
		/**
		 *   Default Checksum Hashing to use
		 */
		var $_default_hash = 'xoopscrc';
		
		/**
		 *   Whether API established a session!
		 */
		var $_sessioned = true;
		
		/**
         *   Uses File Store Cache
		 */
		var $_cached = true;
		
		/**
		 *   Uses the $_SERVER variables to generate query identifier
		 */
		var $_hash_server_variables = 'PHP_SELF|SERVER_NAME|HTTP_HOST|REMOTE_HOST';

		/**
		 *   Domain Root Paths of $_SERVER variables to generate query identifier
		 */
		var $_root_tld_variables = 'HTTP_HOST|REMOTE_HOST';
		
		/**
		 *   Uses IP Exactity
		 */
		var $_ip_exact = true;

		/**
		 *   Session Identity
		 */
		var $_id = '';	

		/**
		 *   Seconds Cache is stored for
		 */
		var $_cache_seconds = 87131111;
		
		/**
		 * 
		 */
		function __construct()
		{
			$this->establishSession();
			$this->initalise();
			
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
		
		/**
		 * 
		 * @param string $mode
		 * @param string $algorithm
		 */		
		function getIdentity($unique = NULL, $output = 'json', $mode = 'default',  $algorithm = 'xoopscrc', $length = 29)
		{
			if ($this->_sessioned && !in_array(whitelistGetIP(true), whitelistGetIPAddy())) {
				if (isset($_SESSION['reset']) && $_SESSION['reset']<microtime(true))
					$_SESSION['hits'] = 0;
				if ($_SESSION['hits']<=MAXIMUM_QUERIES) {
					if (!isset($_SESSION['hits']) || $_SESSION['hits'] = 0)
						$_SESSION['reset'] = microtime(true) + 3600;
					$_SESSION['hits']++;
				} else {
					header("HTTP/1.0 404 Not Found");
					exit;
				}
			}
			if (!in_array($mode, array_keys(identity::getInstance(true)->_preset)))
				$mode = 'default';
			if (!in_array($algorithm, array_keys(identityHashesAPI::getInstance(true)->identityHashes)))
				$algorithm = identity::getInstance(true)->_preset[$mode]['algorithm'];
			
			if ($length<2)
				$length = $algorithm = identity::getInstance(true)->_preset[$mode]['length'];

			return $this->makeModal($output, $mode, $this->generateIdentity($mode, $unique, $_length, $algorithm));	
		}
		
		/**
		 * 
		 * @param string $mode
		 * @param string $_type
		 * @param string $str_data
		 * @return string|multitype:string
		 */
		private function makeModal($output = 'json', $_type = 'default', $str_data = '')
		{
			if (in_array($output, array('html', 'raw')))
				return $str_data;
			else 
				return array($this->_preset[$_type]['name'] => $str_data);
		}
		
		/**
		 * 
		 * @param string $type
		 * @param string $salt
		 * @param string $algorithm
		 */
		private function generateIdentity($type = 'default', $salt = '', $length = 29, $algorithm = 'xoopscrc')
		{
			if ($this->_sessioned && isset($_SESSION[$type][$salt][$algorithm][$length]))
				return $_SESSION[$type][$salt][$algorithm][$length];
			if (empty($algorithm))
				$algorithm = $this->_preset[$type]['algorithm'];
			if (empty($length) || $length < 2)
				$length = $this->_preset[$type]['length'];
			if ($this->_cached && $_vars = identityCache::read($this->getLocalSessionHash()))
			{
				if (isset($_vars[$type][$salt][$algorithm][$length]) && !empty($_vars[$type][$salt][$algorithm][$length]))
					return $_vars[$type][$salt][$algorithm][$length];
			}
			if (!is_array($_vars))
				$_vars = array();
			if ($this->_sessioned)
				$_SESSION[$type][$salt][$algorithm][$length] = $_vars[$type][$salt][$algorithm][$length] = $this->doHash($salt, $this->_preset[$type]['length'], $this->_preset[$type]['algorithm']);
			else
				$_vars[$type][$salt][$algorithm][$length] = $this->doHash($salt, $length, $algorithm);
			identityCache::write($this->getLocalSessionHash(), $_vars, $this->_cache_seconds);
			return $_vars[$type][$salt][$algorithm][$length];
		}
		
		/** function doHash()
		 *
		 * 	Function that does the hash for the API
		 * @author Simon Roberts (Chronolabs) simon@labs.coop
		 *
		 * @param string $data the Data being hashed
		 * @param string $mode API Output mode (JSON, XML, SERIAL, HTML, RAW)
		 * @param string $algorithm The algorithm of hash being used
		 *
		 * @return mixed
		 */
		private function doHash($data = '', $length = 32, $algorithm = 'xoopscrc')
		{
			if (!is_object($GLOBALS['hashing']))
				$GLOBALS['hashing'] = identityHashesAPI::getInstance(true);
			$GLOBALS['hashing']->setVariables(array('length'=>$length));
			$ret = $GLOBALS['hashing']->$algorithm($data);
			return $ret;
		}
		
		/**
		 * 
		 * @return string
		 */
		private function establishSession()
		{
			if ($this->_sessioned)
				if (session_id())
					if (session_id() != $this->getLocalSessionHash())
					{
						session_destroy();
						session_id($this->getLocalSessionHash());
						session_start(true);
					}
				else {
					session_id($this->getLocalSessionHash());
					session_start();
				}	
			elseif ($this->_sessioned && session_id())
				session_destroy();
		}
		
		/**
		 * 
		 * @return boolean
		 */
		private function getLocalSessionHash()
		{
			if (empty($this->_id) || is_null($this->_id))
			{
				$_vars = explode("|", $this->_hash_server_variables);
				$this->_id = sha1(NULL);
				foreach($_vars as $key => $var)
					if (!in_array($var, explode("|", $this->_root_tld_variables)))
						$this->_id = md5($this->_id . $_SERVER[$var]);
					else
						$this->_id = md5($this->_id . $this->getRootTLD($_SERVER[$var]));
			}
			return $this->_id;
		}
		
		/**
		 * 
		 */
		private function initalise()
		{
			if (file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'preset.diz')) 
			{
				foreach(file(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'preset.diz') as $fln)
				{
					$parts = explode("|", $fln);
					$this->_preset[$parts[0]] = array('length' => $parts[1], 'algorithm' => $parts[2], 'name' => $parts[3], 'desc' => $parts[3]);
				}
			}
		}
		
		/**
		 * 
		 * @param unknown $url
		 * @param number $debug
		 * @return string|unknown
		 */
		private function getRootTLD($url, $debug = 0)
		{
			$base_domain = '';
			$url = strtolower($url);
		
			// generic tlds (source: http://en.wikipedia.org/wiki/Generic_top-level_domain)
			$G_TLD = array(
					'biz','com','edu','gov','info','int','mil','name','net','org','aero','asia','cat','coop','jobs','mobi','museum','pro','tel','travel',
					'arpa','root','berlin','bzh','cym','gal','geo','kid','kids','lat','mail','nyc','post','sco','web','xxx',
					'nato', 'example','invalid','localhost','test','bitnet','csnet','ip','local','onion','uucp','co');
		
			// country tlds (source: http://en.wikipedia.org/wiki/Country_code_top-level_domain)
			$C_TLD = array(
					// active
					'ac','ad','ae','af','ag','ai','al','am','an','ao','aq','ar','as','at','au','aw','ax','az',
					'ba','bb','bd','be','bf','bg','bh','bi','bj','bm','bn','bo','br','bs','bt','bw','by','bz',
					'ca','cc','cd','cf','cg','ch','ci','ck','cl','cm','cn','co','cr','cu','cv','cx','cy','cz',
					'de','dj','dk','dm','do','dz','ec','ee','eg','er','es','et','eu','fi','fj','fk','fm','fo',
					'fr','ga','gd','ge','gf','gg','gh','gi','gl','gm','gn','gp','gq','gr','gs','gt','gu','gw',
					'gy','hk','hm','hn','hr','ht','hu','id','ie','il','im','in','io','iq','ir','is','it','je',
					'jm','jo','jp','ke','kg','kh','ki','km','kn','kr','kw','ky','kz','la','lb','lc','li','lk',
					'lr','ls','lt','lu','lv','ly','ma','mc','md','mg','mh','mk','ml','mm','mn','mo','mp','mq',
					'mr','ms','mt','mu','mv','mw','mx','my','mz','na','nc','ne','nf','ng','ni','nl','no','np',
					'nr','nu','nz','om','pa','pe','pf','pg','ph','pk','pl','pn','pr','ps','pt','pw','py','qa',
					're','ro','ru','rw','sa','sb','sc','sd','se','sg','sh','si','sk','sl','sm','sn','sr','st',
					'sv','sy','sz','tc','td','tf','tg','th','tj','tk','tl','tm','tn','to','tr','tt','tv','tw',
					'tz','ua','ug','uk','us','uy','uz','va','vc','ve','vg','vi','vn','vu','wf','ws','ye','yu',
					'za','zm','zw',
					// inactive
			'eh','kp','me','rs','um','bv','gb','pm','sj','so','yt','su','tp','bu','cs','dd','zr');
				
			// break up domain, reverse
			$DOMAIN = explode('.', $full_domain);
			if ($debug) {
				print_r($DOMAIN);
			}
			$DOMAIN = array_reverse($DOMAIN);
			if ($debug) {
				print_r($DOMAIN);
			}
			// first check for ip address
			if (count($DOMAIN) == 4 && is_numeric($DOMAIN[0]) && is_numeric($DOMAIN[3])) {
				return $full_domain;
			}
		
			// if only 2 domain parts, that must be our domain
			if (count($DOMAIN) <= 2) {
				return $full_domain;
			}
		
			/*
			 finally, with 3+ domain parts: obviously D0 is tld now,
			if D0 = ctld and D1 = gtld, we might have something like com.uk so,
			if D0 = ctld && D1 = gtld && D2 != 'www', domain = D2.D1.D0 else if D0 = ctld && D1 = gtld && D2 == 'www',
			domain = D1.D0 else domain = D1.D0 - these rules are simplified below.
			*/
			if (in_array($DOMAIN[0], $C_TLD) && in_array($DOMAIN[1], $G_TLD) && $DOMAIN[2] != 'www') {
				$full_domain = $DOMAIN[2] . '.' . $DOMAIN[1] . '.' . $DOMAIN[0];
			} else {
				$full_domain = $DOMAIN[1] . '.' . $DOMAIN[0];
			}
			// did we succeed?
			return $full_domain;
		}
		
	}
}
		
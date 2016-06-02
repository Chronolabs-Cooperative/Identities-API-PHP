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

	$parts = explode(".", microtime(true));
	mt_srand(mt_rand(-microtime(true), microtime(true))/$parts[1]);
	mt_srand(mt_rand(-microtime(true), microtime(true))/$parts[1]);
	mt_srand(mt_rand(-microtime(true), microtime(true))/$parts[1]);
	mt_srand(mt_rand(-microtime(true), microtime(true))/$parts[1]);
	$salter = ((float)(mt_rand(0,1)==1?'':'-').$parts[1].'.'.$parts[0]) / sqrt((float)$parts[1].'.'.intval(cosh($parts[0])))*tanh($parts[1]) * mt_rand(1, intval($parts[0] / $parts[1]));
	header('Blowfish-salt: '. $salter);
	
	global $domain, $protocol, $business, $entity, $contact, $referee, $peerings, $source;
	require_once __DIR__ . DIRECTORY_SEPARATOR . 'apiconfig.php';
	
	/**
	 * Global API Configurations and Setting from file Constants!
	 */
	$domain = getDomainSupportism('domain', $_SERVER["HTTP_HOST"]);
	$protocol = getDomainSupportism('protocol', $_SERVER["HTTP_HOST"]);
	$business = getDomainSupportism('business', $_SERVER["HTTP_HOST"]);
	$entity = getDomainSupportism('entity', $_SERVER["HTTP_HOST"]);
	$contact = getDomainSupportism('contact', $_SERVER["HTTP_HOST"]);
	$referee = getDomainSupportism('referee', $_SERVER["HTTP_HOST"]);
	$peerings = getPeersSupporting();
	
	/**
	 * URI Path Finding of API URL Source Locality
	 * @var unknown_type
	 */
	$pu = parse_url($_SERVER['REQUEST_URI']);
	$source = (isset($_SERVER['HTTPS'])?'https://':'http://').strtolower($_SERVER['HTTP_HOST']).$pu['path'];
	unset($pu);
	
	define('PATH_CACHE', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'cache');
	
	ini_set('display_errors', true);
	define('MAXIMUM_QUERIES', 128 * 1831);
	ini_set('memory_limit', '256M');
	
	include dirname(__FILE__).'/class/identity.php';
	include dirname(__FILE__).'/functions.php';
	
	$help=false;
	if ((!isset($_GET['output']) || empty($_GET['output'])) || (!isset($_REQUEST['unique']) || empty($_REQUEST['unique']))) {
		$help=true;
	} elseif (isset($_GET['output']) && !empty($_GET['output']) && isset($_REQUEST['unique']) && !empty($_REQUEST['unique'])) {
		$output = (string)trim($_GET['output']);
		$algorithm = (string)trim($_GET['algorithm']);
		$unique = (string)trim($_REQUEST['unique']);
		$mode = (string)trim($_GET['type']);
		$length = (integer)trim($_REQUEST['length']);
		if (empty($mode))
			$mode = 'default';
		parse_str(parse_url($_SERVER["REQUEST_URI"], PHP_URL_QUERY), $__vars);
		$__vars = array_merge($__vars, $_GET);
		$__vars = array_merge($__vars, $_POST);
		unset($__vars['length']);
		unset($_POST['length']);
		unset($__vars['type']);
		unset($_POST['type']);
		unset($__vars['output']);
		unset($_POST['output']);
		unset($__vars['algorithm']);
		unset($_POST['algorithm']);
		unset($__vars['unique']);
		unset($_POST['unique']);
		if (count($__vars))
			$unique = sha1($unique . json_encode($__vars));
	} else {
		$help=true;
	}
	if ($help==true) {
		if (function_exists('http_response_code'))
			http_response_code(400);
		include dirname(__FILE__).'/help.php';
		exit;
	}
	if (function_exists('http_response_code'))
		http_response_code(200);
	$data = identity::getInstance(true)->getIdentity($unique, $output, $mode, $algorithm, $length);
	switch ($output) {
		default:
			echo '<h1>Session Method: ' . strtoupper($algorithm) . '</h1>';
			echo '<pre style="font-family: \'Courier New\', Courier, Terminal; font-size: 0.77em;">';
			if (!is_array($data))
				echo $data;
			else
				echo "{ '". implode("' } { '", $data) . "' }";
			echo '</pre>';
			break;
		case 'raw':
			if (!is_array($data))
				echo $data;
			else
				echo "{ '". implode("' } { '", $data) . "' }";
			break;
		case 'json':
			header('Content-type: application/json');
			echo json_encode($data);
			break;
		case 'serial':
			header('Content-type: text/html');
			echo serialize($data);
			break;
		case 'xml':
			header('Content-type: application/xml');
			$dom = new XmlDomConstruct('1.0', 'utf-8');
			$dom->fromMixed(array('root'=>$data));
 			echo $dom->saveXML();
			break;
	}
?>
		
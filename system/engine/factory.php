<?php

/* ---------------------------------------------------------------------------------- */
/*  OpenCart Factory (with support for the override feature)                          */
/*                                                                                    */
/*  Copyright © 2012 by J.Neuhoff (www.mhccorp.com)                                   */
/*                                                                                    */
/*  This file is part of OpenCart.                                                    */
/*                                                                                    */
/*  OpenCart is free software: you can redistribute it and/or modify                  */
/*  it under the terms of the GNU General Public License as published by              */
/*  the Free Software Foundation, either version 3 of the License, or                 */
/*  (at your option) any later version.                                               */
/*                                                                                    */
/*  OpenCart is distributed in the hope that it will be useful,                       */
/*  but WITHOUT ANY WARRANTY; without even the implied warranty of                    */
/*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                     */
/*  GNU General Public License for more details.                                      */
/*                                                                                    */
/*  You should have received a copy of the GNU General Public License                 */
/*  along with OpenCart.  If not, see <http://www.gnu.org/licenses/>.                 */
/* ---------------------------------------------------------------------------------- */

final class Factory 
{
	protected $registry;
	protected $addonDir;
	protected $addons;
	protected $isAdmin;
	protected $vqmod;
	protected $languageData = NULL;
	
	const CONTROLLER_CLASS = 1;
	const MODEL_CLASS = 2;
	const SYSTEM_CLASS = 3;


	public function __construct($registry) {
		$this->registry = $registry;

		// get the addon directory
		$this->addonDir = realpath( DIR_SYSTEM.'../override/' );
		if ($this->addonDir===FALSE) {
			trigger_error( "Could not find the directory '". DIR_SYSTEM.'../override/' ."'!" );
			exit;
		}
		if (!$this->endsWith( $this->addonDir, '/' )) {
			$this->addonDir .= '/';
		}

		// get all the names of the addons
		if (!$handle = opendir($this->addonDir)) {
			trigger_error( "Could not open the directory '".$this->addonDir."'!" );
			exit;
		}
		$this->addons = array();
		while (false !== ($addon = readdir($handle))) {
			if (!is_dir($this->addonDir.$addon)) {
				continue;
			}
			if (($addon=='..') || ($addon=='.')) {
				continue;
			}
			$this->addons[] = $addon;
		}
		closedir($handle);
		sort( $this->addons );

		// find out whether we are on the OpenCart frontend or admin backend
		$this->isAdmin = defined( 'DIR_CATALOG' );

		// register a variable stream wrapper
		require_once( DIR_SYSTEM . 'library/variable_stream.php' );
		$ok = stream_wrapper_register("var", "VariableStream");
		if (!$ok) {
			trigger_error("Failed to register protocol for a variable stream");
			exit;
		}
		
		// find out whether VQMod is installed
		$this->vqmod = empty($GLOBALS['vqmod']) ? NULL : $GLOBALS['vqmod'];
	}


	public function __get($key) {
		return $this->registry->get($key);
	}


	public function __set($key, $value) {
		$this->registry->set($key, $value);
	}


	public function getAddonDir() {
		return $this->addonDir;
	}


	public function getAddons() {
		return $this->addons;
	}


	public function getIsAdmin() {
		return $this->isAdmin;
	}

	private function pathToClassName( $prefix, $route ) {
		$class = $prefix;
		$isUpper = TRUE;
		for ($i=0; $i<strlen($route); $i++) {
			if ($route[$i]=='/') {
				$isUpper = TRUE;
				continue;
			}
			if ($route[$i]=='_') {
				$isUpper = TRUE;
				continue;
			}
			$class .= ($isUpper) ? strtoupper( $route[$i] ) : $route[$i];
			$isUpper = FALSE;
		}
		return $class;
	}


	private function actionProperties( $route, $args ) {
		$route = str_replace('../', '', (string)$route);

		$file = DIR_APPLICATION . 'controller/' . $route . '.php';
		if (file_exists( $file ) && is_file( $file )) {
			$class = $this->pathToClassName( 'Controller', $route );
			return array( 'file'=>$file, 'class'=>$class, 'method'=>'index', 'args'=>$args );
		} 

		$i = strrpos( $route, '/' );
		if ($i===FALSE) {
			trigger_error("Cannot find controller class file for route '$route'");
			exit;
		}
		$method = substr( $route, $i+1 );
		$filepath = substr( $route, 0, $i );
		if ($filepath===FALSE) {
			trigger_error("Cannot find controller class file for route '$route'");
			exit;
		}

		$file = DIR_APPLICATION . 'controller/' . $filepath . '.php';
		if (file_exists( $file ) && is_file( $file )) {
			$class = $this->pathToClassName( 'Controller', $filepath );
			return array( 'file'=>$file, 'class'=>$class, 'method'=>$method, 'args'=>$args );
		}

		trigger_error("Cannot find controller class file for route '$route'");
		exit;
	}


	public function newAction( $route, $args=array() ) {
		$properties = $this->actionProperties( $route, $args );
		return new Action( $properties );
	}


	private function isLetterOrUnderscore( $ch ) {
		if (($ch>='a') && ($ch<='z')) {
			return TRUE;
		}
		if (($ch>='A') && ($ch<='Z')) {
			return TRUE;
		}
		if ($ch=='_') {
			return TRUE;
		}
		return FALSE;
		if (($ch>='a') && ($ch<='z') || ($ch>='A') && ($ch<='Z') || ($ch=='_') || ($ch>="\x7f") && ($ch<="\xff")) {
			return TRUE;
		}
		return FALSE;
	}


	private function isLetterOrNumberOrUnderscore( $ch ) {
		if (($ch>='a') && ($ch<='z')) {
			return TRUE;
		}
		if (($ch>='A') && ($ch<='Z')) {
			return TRUE;
		}
		if ($ch=='_') {
			return TRUE;
		}
		if (($ch>='0') && ($ch<='9')) {
			return TRUE;
		}
		return FALSE;
		if (($ch>='0') && ($ch<='9') || ($ch>='a') && ($ch<='z') || ($ch>='A') && ($ch<='Z') || ($ch=='_') || ($ch>="\x7f") && ($ch<="\xff")) {
			return TRUE;
		}
		return FALSE;
	}


	private function isWhiteSpace( $ch ) {
		return (($ch==' ') || ($ch=="\n") || ($ch=="\r") || ($ch=="\t"));
	}


	private function nextToken( $buffer, $start ) {
		$j = strlen($buffer);
		$i = $start;
		while ($i<$j) {
			$ch = $buffer[$i];
			if ($this->isWhiteSpace( $ch )) {
				$i += 1;
				continue;
			}
			if ($this->isLetterOrUnderscore( $ch )) {
				$k = $i+1;
				while ($k < $j) {
					$ch = $buffer[$k];
					if ($this->isLetterOrNumberOrUnderscore( $ch )) {
						$k += 1;
						continue;
					}
					break;
				}
				return array( $i, substr( $buffer, $i, $k-$i ) );
			}
			if (($ch=='/') && ($i>0) && ($buffer[$i-1]=='/')) {
				// skip line comment until "\n"
				$i += 1;
				while ($i<$j) {
					$ch = $buffer[$i];
					if ($ch=="\n") {
						break;
					}
					$i += 1;
				}
			} else if (($ch=='*') && ($i>0) && ($buffer[$i-1]=='/')) {
				// skip comment until "*/"
				$i += 1;
				while ($i<$j) {
					$ch = $buffer[$i];
					if (($ch=='/') && ($buffer[$i-1]=='*')) {
						break;
					}
					$i += 1;
				}
			}
			$i += 1;
		}
		return array( NULL, '' );
	}


	private function modifyParent( $modFile, $parent ) {
		// load class file into a string buffer
		$buffer = file_get_contents( $modFile );

		// find the position of the parent class name
		$pos = 0;
		$token = '';
		while (($pos!==NULL) && ($token!='class')) {
			$pos += strlen($token);
			list( $pos, $token ) = $this->nextToken( $buffer, $pos );
		}
		if ($pos===NULL) {
			trigger_error( "The contents in file '$modFile' does not appear to be a valid class" );
			exit;
		}
		$pos += strlen($token);
		list( $pos, $token ) = $this->nextToken( $buffer, $pos );
		if ($pos===NULL) {
			trigger_error( "The contents in file '$modFile' does not appear to be a valid class" );
			exit;
		}
		$pos += strlen($token);
		list( $pos, $token ) = $this->nextToken( $buffer, $pos );
		if ($pos===NULL) {
			trigger_error( "The contents in file '$modFile' does not appear to be a valid class" );
			exit;
		}
		if ($token != 'extends') {
			trigger_error( "The contents in file '$modFile' does not appear to be a valid child class" );
			exit;
		}
		$pos += strlen($token);
		list( $pos, $token ) = $this->nextToken( $buffer, $pos );
		if ($pos===NULL) {
			trigger_error( "The contents in file '$modFile' does not appear to be a valid class" );
			exit;
		}
		
		// replace the parent class name in the string buffer with the new one 
		$oldParent = $token;
		$result = substr( $buffer, 0, $pos ) . $parent . substr( $buffer, $pos+strlen($oldParent) );
		return $result;
	}


	private function newInstance( $type, $file, $class, $args=array() ) {
		// get the relative class file path 
		//   '/controller/<...>/<filename>.php' or '/model/<...>/<filename>.php' or '/library/<...>/<filename>.php'
		$i=FALSE;
		switch ($type) {
			case self::CONTROLLER_CLASS:
				$i = strrpos( $file, '/controller/' );
				if ($i===FALSE) {
					trigger_error("Invalid file path '$file' for controller class '$class'");
					exit;
				}
				break;
			case self::MODEL_CLASS:
				$i = strrpos( $file, '/model/' );
				if ($i===FALSE) {
					trigger_error("Invalid file path '$file' for model class '$class'");
					exit;
				}
				break;
			case self::SYSTEM_CLASS:
				$i = strrpos( $file, '/system/' );
				if ($i===FALSE) {
					trigger_error("Invalid file path '$file' for system class '$class'");
					exit;
				}
				break;
			default:
				trigger_error("Invalid class type for file path '$file' and class '$class'");
				exit;
		}
		$filepath = substr( $file, $i );

		// find all similar class files from addons which extend the original class
		$modFiles = array();
		if ($type==self::SYSTEM_CLASS) {
			$prefix = '';
		} else {
			$prefix = ($this->isAdmin) ? '/admin' : '/catalog';
		}
		foreach ($this->addons as $addon) {
			$modFile = $this->addonDir . $addon . $prefix . $filepath;
			if (file_exists( $modFile ) && is_file( $modFile )) {
				$modFiles[$addon] = $modFile;
			}
		}

		// load original class file (possibly modified by vqmod)
		require_once( (empty($this->vqmod) || (strpos($file,'vq2-')!==FALSE)) ? $file : $this->vqmod->modCheck($file) );

		// load child classes extending the original class
		$parent = $class;
		foreach ($modFiles as $addon => $modFile) {
			if ($parent == $class) {
				// first child class file can be loaded without dynamic modifications
				require_once( $modFile );
			} else {
				// Parent name of this child class must be dynamically set to the previous child class.
				//   We use our own variable stream to accomplish this:
				//     see http://uk3.php.net/manual/en/stream.streamwrapper.example-1.php
				//     see http://uk3.php.net/manual/en/function.eval.php#100032
				//   We could have used the standard data://text/plain buffer stream, 
				//     e.g. require_once("data://text/plain;base64,".base64_encode($buffer))
				//   but it is not always supported on cheaper web hosts because of the PHP settings.
				$var_id = 'override_'.str_replace( array('-','/','.'),array('_','_','_'), substr( $modFile, strlen($this->addonDir) ) );
				$GLOBALS[$var_id] = $this->modifyParent( $modFile, $parent );
				require_once( "var://".$var_id );
			}
			$parent = str_replace('-','_',$addon).'_'.$class;
		}
		$class = $parent;

		// create a new instance of the last child class, or the original class if there were no child classes
		if (empty($args))
			return new $class();                                                                                                                                                         
		else {
			$ref = new ReflectionClass($class);
			return $ref->newInstanceArgs($args);
		}
	}


	public function newController( $file, $class ) {
		return $this->newInstance( self::CONTROLLER_CLASS, $file, $class, array( $this->registry ) );
	}


	public function newModel( $path ) {
		// find the original class file
		$file = DIR_APPLICATION . 'model/' . $path . '.php';
		if (file_exists( $file ) && is_file( $file )) {
			$class = $this->pathToClassName( 'Model', $path );
		} else {
			trigger_error("Cannot find model class file for path '$path'");
			exit;
		}

		// get a new instance of the class
		return $this->newInstance( self::MODEL_CLASS, $file, $class, array( $this->registry ) );
	}


	private function newSystemClass( $filepath, $args=array() ) {
		// find the original class file
		$basename = basename( $filepath, '.php' );
		$file = DIR_SYSTEM . $filepath;
		if (file_exists( $file ) && is_file( $file )) {
			$class = $this->pathToClassName( '', $basename );
		} else {
			trigger_error("Cannot find system class file for '$filepath'");
			exit;
		}
		
		// get a new instance of the class
		return $this->newInstance( self::SYSTEM_CLASS, $file, $class, $args );
	}


	public function loadLanguage( $path ) {
		// get the details of the currently chosen language once from the database
		if (empty($this->languageData)) {
			$language_id = $this->config->get( 'config_language_id' );
			$model = $this->newModel( 'localisation/language' );
			$this->languageData = $model->getLanguage($language_id);
		}

		// get the directories of the current and the default languages
		$language = $this->languageData;
		if (!isset($language['directory'])) {
			trigger_error("Cannot find language file '$path.php'");
			exit;
		}
		$default = 'english';
		$directory = $this->languageData['directory'];

		// load original english language file (possibly modified on the fly by VQmod)
		$data = array();
		$filepath = $default . '/' . $path . '.php';
		$file = DIR_LANGUAGE . $filepath;
		if (file_exists( $file ) && is_file( $file )) {
			$_ = array();
			require( (empty($this->vqmod) || (strpos($file,'vq2-')!==FALSE)) ? $file : $this->vqmod->modCheck($file) );
			$data = array_merge($data, $_);
		} else {
			trigger_error("Cannot find language file '$file'");
			exit;
		}
		
		// overload with english language file modifications from addons
		$modFiles = array();
		$prefix = ($this->isAdmin) ? '/admin/language/' : '/catalog/language/';
		foreach ($this->addons as $addon) {
			$modFile = $this->addonDir . $addon . $prefix . $filepath;
			if (file_exists( $modFile ) && is_file( $modFile )) {
				$_ = array();
				require( $modFile );
				$data = array_merge($data, $_);
			}
		}
		
		if ($directory == $default) {
			return $data;
		}

		// load original non-english language file  (possibly modified on the fly by VQmod)
		$filepath = $directory . '/' . $path . '.php';
		$file = DIR_LANGUAGE . $filepath;
		if (file_exists( $file ) && is_file( $file )) {
			$_ = array();
			require( (empty($this->vqmod) || (strpos($file,'vq2-')!==FALSE)) ? $file : $this->vqmod->modCheck($file) );
			$data = array_merge($data, $_);
		}

		// overload with non-english language file modifications from addons
		$modFiles = array();
		$prefix = ($this->isAdmin) ? '/admin/language/' : '/catalog/language/';
		foreach ($this->addons as $addon) {
			$modFile = $this->addonDir . $addon . $prefix . $filepath;
			if (file_exists( $modFile ) && is_file( $modFile )) {
				$_ = array();
				require( $modFile );
				$data = array_merge($data, $_);
			}
		}

		return $data;
	}


	public function readTemplate( $template ) {
		// load the template file (possibly modified by vqmod) into a string buffer
		$file = DIR_TEMPLATE . $template;
		if (file_exists( $file ) && is_file( $file )) {
			return file_get_contents( (empty($this->vqmod) || (strpos($file,'vq2-')!==FALSE)) ? $file : $this->vqmod->modCheck($file) );
		} else {
			trigger_error("Cannot find template file '$template'");
			exit;
		}
	}


	private function startsWith( $haystack, $needle ) {
		if (strlen( $haystack ) < strlen( $needle )) {
			return FALSE;
		}
		return (substr( $haystack, 0, strlen($needle) ) == $needle);
	}


	private function endsWith( $haystack, $needle ) {
		if (strlen( $haystack ) < strlen( $needle )) {
			return FALSE;
		}
		return (substr( $haystack, strlen($haystack)-strlen($needle), strlen($needle) ) == $needle);
	}


	public function newAffiliate( $registry ) {
		return $this->newSystemClass( 'library/affiliate.php', array( $registry ) );
	}


	public function newCache() {
		return $this->newSystemClass( 'library/cache.php' );
	}


	public function newCart( $registry ) {
		$cart = $this->newSystemClass( 'library/cart.php', array( $registry ) );
		return $cart;
	}


	public function newConfig() {
		return $this->newSystemClass( 'library/config.php' );
	}


	public function newCurrency( $registry ) {
		return $this->newSystemClass( 'library/currency.php', array( $registry ) );
	}


	public function newCustomer( $registry ) {
		return $this->newSystemClass( 'library/customer.php', array( $registry ) );
	}


	public function newDB( $driver, $hostname, $username, $password, $database ) {
		return $this->newSystemClass( 'library/db.php', array( $driver, $hostname, $username, $password, $database ) );
	}


	public function newDocument() {
		return $this->newSystemClass( 'library/document.php' );
	}


	public function newEncryption( $key ) {
		return $this->newSystemClass( 'library/encryption.php', array($key) );
	}


	public function newLanguage( $languageDirectory ) {
		return $this->newSystemClass( 'library/language.php', array( $languageDirectory, $this ) );
	}


	public function newLength( $registry ) {
		return $this->newSystemClass( 'library/length.php', array( $registry ) );
	}


	public function newLog( $filename ) {
		return $this->newSystemClass( 'library/log.php', array($filename) );
	}


	public function newRequest() {
		return $this->newSystemClass( 'library/request.php' );
	}


	public function newResponse() {
		return $this->newSystemClass( 'library/response.php' );
	}


	public function newSession( $session_id='' ) {
		return $this->newSystemClass( 'library/session.php', array( $session_id ) );
	}


	public function newTax( $registry ) {
		return $this->newSystemClass( 'library/tax.php', array( $registry ) );
	}


	public function newUrl($url, $ssl = '') {
		return $this->newSystemClass( 'library/url.php', array( $url, $ssl ) );
	}


	public function newUser( $registry ) {
		return $this->newSystemClass( 'library/user.php', array( $registry ) );
	}


	public function newWeight( $registry ) {
		return $this->newSystemClass( 'library/weight.php', array( $registry ) );
	}


}
?>
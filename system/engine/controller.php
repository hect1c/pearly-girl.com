<?php

/* ---------------------------------------------------------------------------------- */
/*  OpenCart Controller (with modififications for the override feature)               */
/*                                                                                    */
/*  Original file Copyright © 2012 by Daniel Kerr (www.opencart.com)                  */
/*  Modifications Copyright © 2012 by J.Neuhoff (www.mhccorp.com)                     */
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

abstract class Controller {
	protected $registry;
	protected $id;
	protected $layout;
	protected $template;
	protected $children = array();
	protected $data = array();
	protected $output;
	
	public function __construct($registry) {
		$this->registry = $registry;
	}
	
	public function __get($key) {
		return $this->registry->get($key);
	}
	
	public function __set($key, $value) {
		$this->registry->set($key, $value);
	}
			
	protected function forward($route, $args = array()) {
		return ($this->factory) ? $this->factory->newAction( $route, $args ) : new Action($route, $args);
	}

	protected function redirect($url, $status = 302) {
		header('Status: ' . $status);
		header('Location: ' . str_replace(array('&amp;', "\n", "\r"), array('&', '', ''), $url));
		exit();
	}
	
	protected function getChild($child, $args = array()) {
		if ($this->factory) {
			$actionDetails = $this->factory->newAction( $child, $args );
			$actionFile = $actionDetails->getFile();
			$class = $actionDetails->getClass();
			$method = $actionDetails->getMethod();
			if (file_exists($actionFile)) {
				$controller = $this->factory->newController( $actionFile, $class );
				$controller->$method($args);
				return $controller->output;
			}
			trigger_error('Error: Could not load controller ' . $child . '!');
			exit();
		}

		$actionDetails = new Action($child, $args);
		$file = $actionDetails->getFile();
		$class = $actionDetails->getClass();
		$method = $actionDetails->getMethod();
	
		if (file_exists($file)) {
			if ($this->factory) {
				$controller = $this->factory->newController( $file, $class );
			} else {
				require_once($file);
				$controller = new $class($this->registry);
			}
			
			$controller->$method($args);
			
			return $controller->output;
		} else {
			trigger_error('Error: Could not load controller ' . $child . '!');
			exit();
		}
	}


	protected function preRender( $templateBuffer ) {
		/* This method can be overriden to give an extended Controller from an addon the chance to modify the template */
		return $templateBuffer;
	}


	protected function render() {
		foreach ($this->children as $child) {
			$this->data[basename($child)] = $this->getChild($child);
		}
		if ($this->factory) {
			$prefix = $this->factory->getIsAdmin() ? 'admin_view_template_' : 'catalog_view_theme_';
			$template_id = $prefix.str_replace( array('/','.'),array('_','_'),$this->template );
			$GLOBALS[$template_id] = $this->preRender( $this->factory->readTemplate( $this->template ) );
			extract($this->data);
			ob_start();
			/* Could have used the standard data://text/plain buffer stream, 
			   e.g. require("data://text/plain;base64,".base64_encode($buffer))
			   but it is not always supported on cheaper web hosts.
			   Instead we use our own variable stream var://<global-var-id> based on library/variable_stream.php.
			*/
			require("var://".$template_id);
			$this->output = ob_get_contents();
			ob_end_clean();
			return $this->output;
		}

		if (file_exists(DIR_TEMPLATE . $this->template)) {
			extract($this->data);
			
			ob_start();
	
			require(DIR_TEMPLATE . $this->template);
	
			$this->output = ob_get_contents();

			ob_end_clean();
			
			return $this->output;
		} else {
			trigger_error('Error: Could not load template ' . DIR_TEMPLATE . $this->template . '!');
			exit();
		}
	}
}
?>
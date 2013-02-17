<?php

/* ---------------------------------------------------------------------------------- */
/*  OpenCart Action (with support for the override feature)                           */
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

final class Action {
	protected $file;
	protected $class;
	protected $method;
	protected $args = array();

	public function __construct($route_or_properties, $args = array()) {
		if (is_array($route_or_properties)) {
			$properties = $route_or_properties;
			$this->file = $properties['file'];
			$this->class = $properties['class'];
			$this->method = $properties['method'];
			$this->args = $properties['args'];
			return;
		}

		$route = $route_or_properties;
		$path = '';
		
		$parts = explode('/', str_replace(array('../', '..\\', '..'), '', (string)$route));
		
		foreach ($parts as $part) { 
			$path .= $part;
			
			if (is_dir(DIR_APPLICATION . 'controller/' . $path)) {
				$path .= '/';
				
				array_shift($parts);
				
				continue;
			}
			
			if (is_file(DIR_APPLICATION . 'controller/' . str_replace('../', '', $path) . '.php')) {
				$this->file = DIR_APPLICATION . 'controller/' . str_replace('../', '', $path) . '.php';
				
				$this->class = 'Controller' . preg_replace('/[^a-zA-Z0-9]/', '', $path);

				array_shift($parts);
				
				break;
			}
		}
		
		if ($args) {
			$this->args = $args;
		}
			
		$method = array_shift($parts);
				
		if ($method) {
			$this->method = $method;
		} else {
			$this->method = 'index';
		}
	}
	
	public function getFile() {
		return $this->file;
	}
	
	public function getClass() {
		return $this->class;
	}
	
	public function getMethod() {
		return $this->method;
	}
	
	public function getArgs() {
		return $this->args;
	}
}
?>
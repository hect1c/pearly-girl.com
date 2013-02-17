<?php

/* ---------------------------------------------------------------------------------- */
/*  OpenCart library class Language (with modififications for the override feature)   */
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

class Language {
	private $default = 'english';
	private $directory;
	private $data = array();
	private $factory;
 
	public function __construct($directory,$factory=NULL) {
		$this->directory = $directory;
		$this->factory = $factory;
	}
	
	public function get($key) {
		return (isset($this->data[$key]) ? $this->data[$key] : $key);
	}
	
	public function load($filename) {
		if (!empty($this->factory)) {
			$_ = $this->factory->loadLanguage($filename);
			$this->data = array_merge( $this->data, $_ );
			return $this->data;
		}

		$file = DIR_LANGUAGE . $this->directory . '/' . $filename . '.php';
		
		if (file_exists($file)) {
			$_ = array();
			
			require($file);
		
			$this->data = array_merge($this->data, $_);
			
			return $this->data;
		}
		
		$file = DIR_LANGUAGE . $this->default . '/' . $filename . '.php';
		
		if (file_exists($file)) {
			$_ = array();
			
			require($file);
		
			$this->data = array_merge($this->data, $_);
			
			return $this->data;
		} else {
			trigger_error('Error: Could not load language ' . $filename . '!');
			exit();
		}
	}
}
?>
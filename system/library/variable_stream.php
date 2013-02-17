<?php

/* ---------------------------------------------------------------------------------- */
/*  OpenCart library class VariableStream (used by the override feature)              */
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

class VariableStream {
	/* A simple variable stream based on a global text string which can be used for require_once calls.
	   See http://www.php.net/manual/en/stream.streamwrapper.example-1.php for more details. 
	   Used by the Factory class for require_once statements to include modified buffer strings.
	*/
	protected $position;
	protected $varname;
	protected $stat;


	public function __construct() {
		$stat_index_file = stat( "index.php" );
		$stat = array(
			'dev'		=> 0,
			'ino'		=> 0,
			'mode'		=> 0,
			'nlink'		=> 0,
			'uid'		=> $stat_index_file['uid'],
			'gid'		=> $stat_index_file['gid'],
			'rdev'		=> 0,
			'size'		=> 0,
			'atime'		=> time(),
			'mtime'		=> time(),
			'ctime'		=> time(),
			'blksize'	=> -1,
			'blocks'	=> -1
		);
	}


	function stream_open($path, $mode, $options, &$opened_path)
	{
		$url = parse_url($path);
		$this->varname = $url["host"];
		$this->position = 0;
		$this->stat['size'] = isset($GLOBALS[$this->varname]) ? strlen($GLOBALS[$this->varname]) : 0;

		return true;
	}

	function stream_read($count)
	{
		$ret = substr($GLOBALS[$this->varname], $this->position, $count);
		$this->position += strlen($ret);
		return $ret;
	}

	function stream_write($data)
	{
		$left = substr($GLOBALS[$this->varname], 0, $this->position);
		$right = substr($GLOBALS[$this->varname], $this->position + strlen($data));
		$GLOBALS[$this->varname] = $left . $data . $right;
		$this->position += strlen($data);
		return strlen($data);
	}

	function stream_tell()
	{
		return $this->position;
	}

	function stream_eof()
	{
		return $this->position >= strlen($GLOBALS[$this->varname]);
	}

	function stream_seek($offset, $whence)
	{
		switch ($whence) {
			case SEEK_SET:
				if ($offset < strlen($GLOBALS[$this->varname]) && $offset >= 0) {
					$this->position = $offset;
					return true;
				} else {
					return false;
				}
				break;

			case SEEK_CUR:
				if ($offset >= 0) {
					$this->position += $offset;
					return true;
				} else {
					return false;
				}
				break;

			case SEEK_END:
				if (strlen($GLOBALS[$this->varname]) + $offset >= 0) {
					$this->position = strlen($GLOBALS[$this->varname]) + $offset;
					return true;
				} else {
					return false;
				}
				break;

			default:
				return false;
		}
	}

	function stream_metadata($path, $option, $var) 
	{
		if($option == STREAM_META_TOUCH) {
			$url = parse_url($path);
			$varname = $url["host"];
			if(!isset($GLOBALS[$varname])) {
				$GLOBALS[$varname] = '';
			}
			return true;
		}
		return false;
	}

	function stream_stat() {
		return $this->stat;
	}

	public function stream_cast($cast_as) {
		return FALSE;
	}
}
?>
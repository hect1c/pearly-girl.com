<?php

/* ---------------------------------------------------------------------------------- */
/*  OpenCart helper class Modifier                                                    */
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

final class Modifier {

	/**
		@param string $buffer    The string buffer to be modified
		@param string $search    Single line search expression
		@param string $add       The actual modification data
		@param string $position  Search position: one of 'before', 'after', 'replace', 'top', 'bottom', or 'all'
			'replace' will replace the data in the search tag with the data in the add tag. (default)
			'before' will insert the add data before the search data
			'after' will insert the add data after the search data
			'top' will insert the add data at the top of the file. The search data is ignored.
			'bottom' will insert the add data at the bottom of the file. The search data is ignored.
			'all' will completely replace all the code in the file with the add data. The search data is ignored. 
		@param int $offset       Optional offset to work with the position
			if the search position is 'before' and offset '3' it will put the add data before the line, 3 lines above the searched line
			if the search position is 'after' and offset '3' it will put the add data after the line, 3 lines below the searched line
			if the search position is 'replace' and offset '3' it will remove the code from the search line and the next 3 lines and replace it with the add data
			if the search position is 'top' and offset '3' it will put the code before the line, 3 lines below the top of the file
			if the search position is 'bottom' and offset '3' it will put the code after the line, 3 lines above the bottom of the file 
		@param $index            Optional index for specifying which instances of a search tag should be acted on 
			If the search string is "echo" and there are 5 echos in the file, but only want to replace the 1st and 3rd, use index="1,3"
			Comma delimited for multiple instances starting with "1"
			Leave out or set to FALSE to replace all instances. (default) 
		@param boolean $trim     Wether or not to trim away whitespace and linebreaks
		@return string           A modified string buffer
		
		@description
		This helper function can be used to modify the template buffer.
		The functionality is somewhat similar to an operation as carried out in the VQmod tool (www.vqmod.com).
		Not thoroughly tested yet, use at your own risk!
	*/
	public static function modifyStringBuffer( $buffer, $search, $add='', $position='after', $offset=0, $index=FALSE, $trim=FALSE ) {
		// return unchanged template buffer if it is empty, or if the search expression is empty
		if ($buffer=='') {
			return $buffer;
		}
		if ($search=='') {
			return $buffer;
		}

		// handle special case modification where position is one of 'top', 'bottom' or 'all'
		if ($position=='all') {
			return $add;
		}
		if ($position=='top') {
			return $add.$buffer;
		}
		if ($position=='bottom') {
			return $buffer.$add;
		}

		// convert into an array of lines
		$lines = explode( "\n", $buffer );
		if ($lines===FALSE) {
			return $buffer;
		}

		// do the searches and apply the modifications
		$indexes = ($index===FALSE) ? $index : explode(',',$index);
		$lineNumber = 0;
		$lineCount = count($lines);
		$nextIndex = 0;
		while ($lineNumber < $lineCount) {
			$lineCol = 0;
			while ($lineCol!==FALSE) {
				$lineCol = strpos( $lines[$lineNumber], $search, $lineCol );
				if ($lineCol===FALSE) {
					continue;
				}
				$nextIndex += 1;
				if ($indexes) {
					if (!in_array( $nextIndex, $indexes )) {
						$lineCol += strlen($search);
						continue;
					}
				}
				// apply the modification
				switch ($position) {
					case 'before': 
						$i = $lineNumber - $offset;
						if ($i < 0) {
							$error  = "Modifier::modifyStringBuffer: unable to apply modification before non-existing line number $i\n";
							$error .= "search='$search'\nadd='$add'\nposition='$position'\noffset='$offset'\n";
							trigger_error( $error );
							exit();
						}
						if ($trim) {
							$adds = explode("\n",trim($add));
						} else {
							$adds = explode("\n",$add);
						}
						if ($i==0) {
							$lines = array_merge( $adds, $lines );
						} else {
							$lines = array_merge( array_slice($lines,0,$i), $adds, array_slice($lines,$i) );
						}
						$lineNumber += count($adds);
						$lineCount += count($adds);
						$lineCol = FALSE;
						break;
					case 'after':
						$i = $lineNumber + $offset;
						if ($i >= $lineCount) {
							$error  = "Modifier::modifyStringBuffer: unable to apply modification after non-existing line number $i\n";
							$error .= "search='$search'\nadd='$add'\nposition='$position'\noffset='$offset'\n";
							trigger_error( $error );
							exit();
						}
						if ($trim) {
							$adds = explode("\n",trim($add));
						} else {
							$adds = explode("\n",$add);
						}
						if ($i<$lineCount-1) {
							$lines = array_merge( array_slice($lines,0,$i+1), $adds, array_slice($lines,$i+1) );
						} else {
							$lines = array_merge( $lines, $adds );
						}
						$lineCount += count($adds);
						$lineCol = FALSE;
						break;
					case 'replace':
						$i = $lineNumber + $offset;
						if ($i >= $lineCount) {
							$error  = "Modifier::modifyStringBuffer: unable to replace the non-existing line range from $lineNumber to $i\n";
							$error .= "search='$search'\nadd='$add'\nposition='$position'\noffset='$offset'\n";
							trigger_error( $error );
							exit();
						}
						if (($trim) && ($offset>0)) {
							$adds = explode("\n",trim($add));
						} else {
							$adds = explode("\n",$add);
						}
						if ($offset>0) {
							$lines = array_merge( array_splice($lines,0,$lineNumber), $adds, array_splice($i) );
							$lineCount -= $offset;
							$lineCount += count($adds);
							$lineNumber -= $offset;
							$lineNumber += count($adds);
							$lineCol = FALSE;
						} else {
							$replaceCount = 1;
							$lines[$lineNumber] = str_replace( $search, $add, $lines[$lineNumber], $replaceCount );
							$lineCol += strlen($add);
						}
						break;
					default:
						$lineCol = FALSE;
						break;
				}
			}
			$lineNumber += 1;
		}

		// return modified lines
		$buffer = implode( "\n", $lines );
		return $buffer;
	}

}
?>
<?php
/**
 *  OpenLSS - Lighter Smarter Simpler
 *
 *	This file is part of OpenLSS.
 *
 *	OpenLSS is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU Lesser General Public License as
 *	published by the Free Software Foundation, either version 3 of
 *	the License, or (at your option) any later version.
 *
 *	OpenLSS is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU Lesser General Public License for more details.
 *
 *	You should have received a copy of the 
 *	GNU Lesser General Public License along with OpenLSS.
 *	If not, see <http://www.gnu.org/licenses/>.
 */
namespace LSS;

//---------------------------------------------------------
//Interface to Force the PHP Stream Wrapper prototype
//---------------------------------------------------------
interface WrapperInterface {
	/**
	 * resource context
	 *
	 * @var resource
	 */
	//public $context;

	/**
	 * constructor
	 *
	 */
	public function __construct();

	/**
	 * Close directory handle
	 *
	 * @return bool
	 */
	public function dir_closedir();

	/**
	 * Open directory handle
	 *
	 * @param string $path
	 * @param int $options
	 * @return bool
	 */
	public function dir_opendir($path,$options);

	/**
	 * Read entry from directory handle
	 * Should return the next file name
	 *
	 * @return string
	 */
	public function dir_readdir();

	/**
	 * Rewind directory handle
	 *
	 * @return bool
	 */
	public function dir_rewinddir();

	/**
	 * Create directory from path
	 *
	 * @param string $path
	 * @param int $mode
	 * @param int $options
	 * @return bool
	 */
	public function mkdir($path,$mode,$options);

	/**
	 * Rename a file or directory
	 *
	 * @param string $path_from
	 * @param string $path_to
	 * @return bool
	 */
	public function rename($path_from,$path_to);

	/**
	 * Remove directory by path
	 *
	 * @param string $path
	 * @param int $options
	 * @return bool
	 */
	public function rmdir($path,$options);

	/**
	 * Return underlying stream resource
	 *
	 * @param int $cast_as
	 * @return resource
	 */
	public function stream_cast($cast_as);

	/**
	 * Close stream handle (flush buffers/cache)
	 *
	 */
	public function stream_close();

	/**
	 * Check for end of file
	 *
	 * @return bool
	 */
	public function stream_eof();

	/**
	 * Force buffer flush
	 *
	 * @return bool
	 */
	public function stream_flush();

	/**
	 * Advisory file locking
	 *
	 * @param mode $operation
	 * @return bool
	 */
	public function stream_lock($operation);

	/**
	 * Open stream resource by path
	 *
	 * @param string $path
	 * @param string $mode
	 * @param int $options
	 * @param string &$opened_path
	 * @return bool
	 */
	public function stream_open($path,$mode,$options,&$opened_path);

	/**
	 * Read $count bytes from resource
	 *
	 * @param int $count
	 * @return string
	 */
	public function stream_read($count);

	/**
	 * Seek to new position in resource
	 *
	 * @param int $offset
	 * @param int $whence = SEEK_SET
	 * @return bool
	 */
	public function stream_seek($offset,$whence=SEEK_SET);

	/**
	 * Set options for the stream
	 *
	 * @param int $option
	 * @param int $arg1
	 * @param int $arg2
	 * @return bool
	 */
	public function stream_set_option($option,$arg1,$arg2);

	/**
	 * Return stats about the stream
	 *
	 * @return array
	 */
	public function stream_stat();

	/**
	 * Return the current pointer position
	 *
	 * @return int
	 */
	public function stream_tell();

	/**
	 * Write $data to the stream and return $int bytes written
	 * 
	 * @param string $data
	 * @return int 
	 */
	public function stream_write($data);

	/**
	 * Remove entry by path
	 *
	 * @param string $path
	 * @return bool
	 */
	public static function unlink($path);

	/**
	 * Return information about a path
	 *
	 * @param string $path
	 * @param int $flags
	 * @return array
	 */
	public static function url_stat($path , $flags);
}

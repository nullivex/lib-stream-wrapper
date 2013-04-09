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

abstract class StreamWrapper implements StreamWrapperInterface {

	static $stream_prefix = 'lib-stream-wrapper';

	public $context = null;
	private $chksum = null;
	private $position = 0;
	private $dir_pointer = 0;
	private $eof = false;
	private $mode = null;
	protected $info = array();

	public static function register(){
		//(re)register the wrapper
		if(in_array(static::$stream_prefix,stream_get_wrappers()))
			stream_wrapper_unregister(static::$stream_prefix);
		stream_wrapper_register(static::$stream_prefix,get_called_class());
	}

	public function __construct(){
		$this->reset();
	}

	protected function reset(){
		$this->context = null;
		$this->chksum = null;
		$this->position = 0;
		$this->dir_pointer = 0;
		$this->node = null;
		$this->info = array(
			 'size'		=>	0					//file size in bytes
			,'ctime'	=>	null				//created timestamp
			,'mtime'	=>	null				//last modified timestamp
			,'atime'	=>	null				//last accessed timestamp
			,'dev'		=>	0					//device number
			,'ino'		=>	0					//inode number
			,'mode'		=>	octdec('0100666')	//permission mask
			,'nlink'	=>	0					//number of links
			,'uid'		=>	0					//owner user id
			,'gid'		=>	0					//owner group id
			,'rdev'		=>	0					//device type, when inode device
			,'blksize'	=>	512					//block size of filesystem
		);
	}

	//getters
	public static function getPrefix($suffix=true){
		return static::$stream_prefix.($suffix ? '://' : null);
	}

	public function getSize(){
		return mda_get($this->info,'size');
	}

	public function getCtime(){
		return round(mda_get($this->info,'ctime'));
	}

	public function getMtime(){
		return round(mda_get($this->info,'mtime'));
	}

	public function getAtime(){
		return round(mda_get($this->info,'atime'));
	}

	public function getMode(){
		return $this->mode;
	}

	//general utility functions
	public static function parsePath($path){
		//strip prefix
		$path = str_replace(self::getPrefix(),'',$path);
		//break off the query string if we can
		if(strpos($path,'?') !== false){
			$query = substr($path,strpos($path,'?'));
			$path = substr($path,0,(strlen($path)-strlen($query)));
			//remove the questionmark
			$query = str_replace('?','',$query);
		}
		//setup opts
		$opts['path'] = $path;
		$opts['params'] = array();
		if(isset($query)) parse_str($query,$opts['params']);
		return $opts;
	}

	public static function unlink($path){
		$opts = static::parsePath($path);
		try {
			$wrapper = new static();
			return $wrapper->delete($opts);
		} catch(Exception $e){
			dolog(self::getPrefix(false).' Error: [unlink] '.$e->getMessage(),LOG_WARN);
			return false;
		}
	}

	public static function url_stat($path,$flags){
		$opts = static::parsePath($path);
		try {
			$wrapper = new static();
			$wrapper->reset();
			$wrapper->populateInfo($opts);
			$stat = $wrapper->stat($wrapper->stream_stat());
			return $stat;
		} catch(Exception $e){
			dolog(self::getPrefix(false).' [url_stat] '.$e->getMessage(),LOG_WARN);
			return false;
		}
	}

	// StreamWrapper functions from here on out
	public function stream_set_option($option,$arg1,$arg2){
		switch($option){
			case STREAM_OPTION_BLOCKING: //The method was called in response to stream_set_blocking()
			case STREAM_OPTION_READ_TIMEOUT: //The method was called in response to stream_set_timeout()
			default:
				return false; //unsupported
				break;
			case STREAM_OPTION_WRITE_BUFFER: //The method was called in response to stream_set_write_buffer()
				switch($arg1){
					case STREAM_BUFFER_NONE:
						//DGAF, we buffer anyways because PHP streams use 8K buffers and this isn't 1998
					case STREAM_BUFFER_FULL:
						break;
				}
				$this->setBufferLimit($arg2);
				break;
		}
		return true;
	}

	public function stream_cast($cast_as){
		return $this->cast($cast_as);
	}

	public function stream_open($path,$mode,$options,&$opened_path){
		$this->mode = $mode;

		//parse path and get options
		$opts = static::parsePath($path);

		//startup handlers
		$this->reset();

		//populate info
		$this->populateInfo($opts);

		//open path
		return $this->open($opts,$mode,$options,$opened_path);
	}

	public function stream_close(){
		$rv = $this->close();
		$this->reset();
		return $rv;
	}

	public function stream_read($count=null){
		$orig_count = $count;
		$position = $this->position;
		$buf = '';
		while($data = $this->read($position,$count)){
			$buf .= $data;
			$position = $this->position + strlen($buf);
			if(is_numeric($count)){
				$count = $orig_count - strlen($buf);
				if($count <= 0) break;
			}
		}
		if(is_numeric($orig_count))
			$buf = substr($buf,0,$orig_count);
		$this->position += strlen($buf);
		if($this->position > ($this->getSize()-1)) $this->eof = true;
		return $buf;
	}

	public function stream_write($data){
		$bytes_written = $this->write($this->position,$data);
		if(!is_numeric($bytes_written))
			return false;
		$this->position += $bytes_written;
		return $bytes_written;
	}

	public function stream_flush(){
		return $this->flush();
	}

	public function stream_tell(){
		return $this->position;
	}

	public function stream_eof(){
		return $this->eof;
	}

	public function stream_seek($offset,$whence=SEEK_SET){
		switch($whence){
			case SEEK_END: //Set position to end-of-file plus offset.
				$offset = (($this->getSize() - 1) + $offset) - $this->position;
				//NOTE this is designed to fall through to SEEK_CUR: DO NOT add break or return construct here
			case SEEK_CUR: //Set position to current location plus offset.
				$offset += $this->position;
				//NOTE this is designed to fall through to SEEK_SET: DO NOT add break or return construct here
			case SEEK_SET: //Set position equal to offset bytes.
				// no need to modify $offset here, but we do need to break so default doesn't happen
				break;
			default:
				//other whences unsupported (because there aren't any other whences according to docs)
				return false;
				break;
		}
		//sanity check the resulting $offset
		if($offset < 0)
			return false;
		if(strpos($this->getMode(),'w') !== 0 && strpos($this->getMode(),'a') !== 0)
			if($offset > $this->getSize())
				return false;
		//if we didn't return false by now, $offset contains a valid literal position to set
		$this->position = $offset;
		return true;
	}

	public function stream_metadata($path,$option,$value){
		$opts = static::parsePath($path);
		switch($option){
			case PHP_STREAM_META_TOUCH: //(The method was called in response to touch())
				return $this->touch($opts);
				break;
			case PHP_STREAM_META_OWNER_NAME: //(The method was called in response to chown() with string parameter)
			case PHP_STREAM_META_OWNER: //(The method was called in response to chown())
				return $this->setOwner($opts,$value);
				break;
			case PHP_STREAM_META_GROUP_NAME: //(The method was called in response to chgrp())
			case PHP_STREAM_META_GROUP: //(The method was called in response to chgrp())
				return $this->setGroup($opts,$value);
				break;
			case PHP_STREAM_META_ACCESS: //(The method was called in response to chmod())
				return $this->setPermissions($opts,$value);
				break;
			default:
				return false; //unsupported
				break;
		}
	}

	public function stream_stat(){
		$stat = array();
		// 0	dev		device number
		$stat[0]	=	$stat['dev']		= mda_get($this->info,'dev');
		// 1	ino		inode number *
		$stat[1]	=	$stat['ino']		= mda_get($this->info,'ino');
		// 2	mode	inode protection mode
		$stat[2]	=	$stat['mode']		= mda_get($this->info,'mode');
		// 3	nlink	number of links
		$stat[3]	=	$stat['nlink']		= mda_get($this->info,'nlink');
		// 4	uid		userid of owner *
		$stat[4]	=	$stat['uid']		= mda_get($this->info,'uid');
		// 5	gid		groupid of owner *
		$stat[5]	=	$stat['gid']		= mda_get($this->info,'gid');
		// 6	rdev	device type, if inode device
		$stat[6]	=	$stat['rdev']		= mda_get($this->info,'rdev');
		// 7	size	size in bytes
		$stat[7]	=	$stat['size']		= $this->getSize();
		// 8	atime	time of last access (Unix timestamp)
		$stat[8]	=	$stat['atime']		= $this->getAtime();
		// 9	mtime	time of last modification (Unix timestamp)
		$stat[9]	=	$stat['mtime']		= $this->getMtime();
		//10	ctime	time of last inode change (Unix timestamp)
		$stat[10]	=	$stat['ctime']		= $this->getCtime();
		//11	blksize	blocksize of filesystem IO **
		$stat[11]	=	$stat['blksize']	= mda_get($this->info,'blksize');
		//12	blocks	number of 512-byte blocks allocated **
		$stat[12]	=	$stat['blocks']		= ceil($this->getSize()/$stat['blksize']);
		return $stat;
	}

	public function stream_lock($operation){
		$nonblock = ($operation & LOCK_NB) ? true : false;
		if($nonblock) $operation -= LOCK_NB;
		switch($operation){
			case LOCK_SH: //to acquire a shared lock (reader).
			case LOCK_EX: //to acquire an exclusive lock (writer).
			case LOCK_UN: //to release a lock (shared or exclusive).
				return false; //unsupported
				break;
		}
	}
	
	//-----------------------------------------------------
	//Directory Listing and Modification
	//-----------------------------------------------------
	public function dir_closedir(){
		return $this->dirClose();
	}

	public function dir_opendir($path,$options){
		return $this->dirOpen(static::parsePath($path),$options);
	}

	public function dir_readdir(){
		$dir = $this->dirRead($this->dir_pointer);
		$this->dir_pointer++;
		return $dir;
	}

	public function dir_rewinddir(){
		$this->dir_pointer = 0;
		return $dir;
	}

	public function mkdir($path,$mode,$options){
		return $this->dirCreate(static::parsePath($path),$mode,$options);
	}

	public function rename($path_from,$path_to){
		return $this->move(static::parsePath($path_from),static::parsePath($path_to));
	}

	public function rmdir($path,$options){
		return $this->dirDelete(static::parsePath($path),$options);
	}

	//-----------------------------------------------------
	//Some default functions that can be extended
	//-----------------------------------------------------
	public function stat($arr){
		return $arr;
	}

	public function flush(){
		return true;
	}

	public function setBufferLimit($limit){}

	
	public function populateInfo($opts){
		//void
	}

	public function cast($cast_as){
		return null;
	}

	public function open($opts,$mode,$options,$opened_path){
		return false;
	}

	public function touch($opts){
		return false;
	}

	public function setOwner($opts,$value){
		return false;
	}

	public function setGroup($opts,$value){
		return false;
	}

	public function setPermissions($opts,$value){
		return false;
	}
	public function read($position=0,$length=null){
		return false;
	}

	public function write($position=0,$data=null){
		return false;
	}
	
	public function close(){
		return false;
	}

	public function delete($opts){
		return false;
	}
	
	//-----------------------------------------------------
	//Directory Listing and Modification
	//-----------------------------------------------------
	public function dirClose(){
		return false;
	}

	public function dirOpen($opts,$options){
		return false;
	}

	public function dirRead($pointer){
		return false;
	}

	public function dirCreate($opts,$mode,$options){
		return false;
	}

	public function move($opts,$opts){
		return false;
	}

	public function dirDelete($opts,$options){
		return false;
	}

}


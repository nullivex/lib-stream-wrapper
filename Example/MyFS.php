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
namespace Example;

//---------------------------------------------------------
//Simple filesystem access implementation
//---------------------------------------------------------
class MyFS extends \LSS\StreamWrapper implements \LSS\StreamWrapperInterface {

	static $stream_prefix = 'myfs';

	//internal file handle
	protected $fh;

	//internal dir listing
	protected $dl;

	//Populate file info
	//	No return
	public function populateInfo($opts){
		if(file_exists($opts['path']))
			$this->info = array_merge($this->info,stat($opts['path']));
	}

	public function cast($cast_as){
		return $this->fh;
	}

	public function open($opts,$mode,$options,$opened_path){
		$this->fh = fopen($opts['path'],$mode);
		if(is_resource($this->fh))
			return true;
		return false;
	}

	public function touch($opts){
		return touch($opts['path']);
	}

	public function setOwner($opts,$value){
		return chown($opts['path'],$value);
	}

	public function setGroup($opts,$value){
		return chgrp($opts['path'],$value);
	}

	public function setPermissions($opts,$value){
		return chmod($opts['path'],$value);
	}
	public function read($position=0,$length=null){
		fseek($this->fh,$position);
		return fread($this->fh,$length);
	}

	public function write($position=0,$data=null){
		fseek($this->fh,$position);
		return fwrite($this->fh,$data);
	}
	
	public function close(){
		return fclose($this->fh);
	}

	public function delete($opts){
		return unlink($opts['path']);
	}
	
	//-----------------------------------------------------
	//Directory Listing and Modification
	//-----------------------------------------------------
	public function dirClose(){
		return closedir($this->dh);
	}

	public function dirOpen($opts,$options){
		$this->dl = scandir($opts['path']);
		if($this->dl === false) return false;
		return true;
	}

	public function dirRead($pointer){
		if(isset($this->dl[$pointer]))
			return $this->dl[$pointer];
		return false;
	}

	public function dirCreate($opts,$mode,$options){
		return mkdir($opts['path'],$mode);
	}

	public function move($opts,$opts){
		return rename($opts['path'],$opts['path']);
	}

	public function dirDelete($opts,$options){
		return rmdir($opts['path'],$options);
	}

}

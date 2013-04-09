openlss/lib-stream-wrapper
==================

Simple library that implements PHP's stream wrapper with simpler extension for rapid wrapper development.

The below interface shows the usage of this library.

There is also an example located at Example/MyFS.php this is used to test the library.

Interface
====
```php

//---------------------------------------------------------
//Interface to implement lib-stream-wrapper
//---------------------------------------------------------
interface StreamWrapperInterface extends WrapperInterface {

	//The wrapper call prefix
	//	EG fs:// then $stream_prefix = 'fs';
	//static $stream_prefix

	//$this->info structure and defaults
	//	array(
	//		 'size'		=>	0					//file size in bytes
	//		,'ctime'	=>	null				//created timestamp
	//		,'mtime'	=>	null				//last modified timestamp
	//		,'atime'	=>	null				//last accessed timestamp
	//		,'dev'		=>	0					//device number
	//		,'ino'		=>	0					//inode number
	//		,'mode'		=>	octdec('0100666')	//permission mask
	//		,'nlink'	=>	0					//number of links
	//		,'uid'		=>	0					//owner user id
	//		,'gid'		=>	0					//owner group id
	//		,'rdev'		=>	0					//device type, when inode device
	//		,'blksize'	=>	512					//block size of filesystem
	//		,'blocks'	=>	(internal)			//number of blocks (figured automatically)
	//	);

	//$opts structure
	//	array(
	//		 'path'		=>	'file path'
	//		,'params'	=>	'query options passed'
	//	)

	//Return underlying stream resource
	public function cast($cast_as);

	//Populate file info
	//	No return
	public function populateInfo($opts);

	//Set the buffer limit of the underlying resource
	public function setBufferLimit($limit);

	//Delete a file by path
	//	Should return boolean
	public function delete($opts);

	//Open path
	//	return TRUE on success FALSE on failure
	public function open($opts,$mode,$options,$opened_path);

	//Close resource
	//	return TRUE on success FALSE on failure
	public function close();

	//Read resource
	//	$position 	the pointer to start reading from
	//	$length 	the amount of bytes to read
	//	return data read
	public function read($position=0,$length=null);

	//Write data to resource
	//	$position	the pointer position to write to
	//	$data		data to be written
	//	return the amount of bytes written (int)
	public function write($position=0,$data=null);

	//Flush buffers to disk
	//	return TRUE on success FALSE on failure
	public function flush();
	
	//Touch path
	//	return TRUE on success FALSE on failure
	public function touch($opts);

	//Change owner
	//	return TRUE on success FALSE on failure
	public function setOwner($opts,$value);

	//Change group
	//	return TRUE on success FALSE on failure
	public function setGroup($opts,$value);

	//Set access permissions
	//	return TRUE on success FALSE on failure
	public function setPermissions($opts,$value);

	//Passed the internal stream_stat array
	//	any overrides should be done
	//	Returns the passed array with overrides
	public function stat($arr);

	//-----------------------------------------------------
	//Directory Listing and Modification
	//-----------------------------------------------------
	//Close directory
	//	return TRUE on success FALSE on failure
	public function dirClose();

	//Open directory
	//	Store the listing internally numerically indexed
	//	return TRUE on success FALSE on failure
	public function dirOpen($opts,$options);

	//Read entry from directory list
	//	reads entry from stored list
	//	return directory entry or false if not found
	public function dirRead($pointer);

	//Create a directory
	//	return TRUE on success FALSE on failure
	public function mkdir($opts,$mode,$options);

	//Move a directory or file
	//	return TRUE on success FALSE on failure
	public function rename($opts,$opts);

	//Remove a directory
	public function rmdir($opts,$options);

}

```
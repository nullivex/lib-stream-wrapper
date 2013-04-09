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
require_once(dirname(__DIR__).'/vendor/autoload.php');
require('lss_boot.php');
require_once(dirname(__DIR__).'/Example/MyFS.php');

use \Exception;
use \Example\MyFS;

class StreamWrapperTest extends PHPUNIT_Framework_TestCase {

	static $hash = null;
	static $fh = false;
	static $data = null;
	static $tmp_file = null;
	static $tmp_file_write = null;

	const TEST_SIZE = 131072;

	protected static function randString($length){
		$length = $length - 8;
		$str = null;
		for($i=0;$i<$length;$i++){
			$rand = rand(0,255);
			if($rand == 10) $rand = 11;
			$str .= chr($rand);
		}
		return "\n\0\1\2".$str."\n\0\0\0";
	}

	protected function genData(){
		$data = 'phpunit-test'.str_repeat(0,1024);
		$hash = sha1($data);
		return array($data,$hash);
	}

	public static function setUpBeforeClass(){
		//setup test files
		self::$tmp_file = MyFS::getPrefix().'/tmp/lsw-test?test=true';
		self::$tmp_file_write = MyFS::getPrefix().'/tmp/lsw-test-write?test=true';
		//register stream wrapper
		MyFS::register();
		//get random data for writing tmp file
		self::$data = self::randString(self::TEST_SIZE); //added 1 byte to force the buffers not to line up
		// self::$data = 'this is our test string';
		self::$hash = sha1(self::$data);

		//write the tmp file for operations
		$fh = fopen(self::$tmp_file,'w');
		fwrite($fh,self::$data);
		fclose($fh);

		//open the tmp file for working with
		self::$fh = fopen(self::$tmp_file,'r');
	}

	public static function tearDownAfterClass(){
		//delete tmp file
		fclose(self::$fh);
		if(file_exists(self::$tmp_file))
			unlink(self::$tmp_file);
	}

	public function test_fopen(){
		return true;
		$fh = fopen(self::$tmp_file,'r');
		$this->assertTrue(is_resource($fh));
		$this->assertTrue(fclose($fh));
	}

	public function test_fread(){
		$data = fread(self::$fh,1024);
		$hash_internal = sha1(substr(self::$data,0,1024));
		$hash_remote = sha1($data);
		$this->assertEquals(1024,strlen($data));
		$this->assertEquals($hash_internal,$hash_remote);
	}

	public function test_fwrite(){
		$fh = fopen(self::$tmp_file_write,'w');
		$this->assertTrue(is_resource($fh));
		list($data,$hash) = $this->genData();
		$this->assertEquals(strlen($data),fwrite($fh,$data));
		$this->assertTrue(fclose($fh));
		//read the file back and validate integrity
		$read = file_get_contents(self::$tmp_file_write);
		$this->assertEquals(strlen($data),strlen($read));
		$this->assertEquals($hash,sha1($read));
		//remove the remote file
		$this->assertTrue(unlink(self::$tmp_file_write));
	}

	public function test_copy(){
		$tmp_file = tempnam('/tmp','vc-test');
		list($data,$hash) = $this->genData();
		$this->assertEquals(strlen($data),file_put_contents($tmp_file,$data));
		$this->assertTrue(copy($tmp_file,self::$tmp_file_write));
		$this->assertTrue(unlink(self::$tmp_file_write));
	}

	public function test_fflush(){
		list($data,$hash) = $this->genData();
		$fh = fopen(self::$tmp_file_write,'w');
		$this->assertTrue(is_resource($fh));
		$this->assertEquals(strlen($data),fwrite($fh,$data));
		$this->assertTrue(fflush($fh));
		$this->assertTrue(fclose($fh));
	}

	public function test_fgetc(){
		$this->assertEquals(1,strlen(fgetc(self::$fh)));
	}

	public function test_fgetcsv(){
		$data = '1,2,3,4,5';
		$hash = sha1($data);
		$fh = fopen(self::$tmp_file_write,'w');
		$this->assertTrue(is_resource($fh));
		$this->assertEquals(strlen($data),fwrite($fh,$data));
		$this->assertTrue(fclose($fh));
		$fh = fopen(self::$tmp_file_write,'r');
		$this->assertTrue(is_resource($fh));
		$data = fgetcsv($fh);
		$this->assertEquals($hash,sha1(implode(',',$data)));
		$this->assertEquals(5,count($data));
		$this->assertTrue(fclose($fh));
		$this->assertTrue(unlink(self::$tmp_file_write));
	}

	public function test_fgets(){
		$this->assertEquals(1023,strlen(fgets(self::$fh,1024)));
	}

	public function test_fgetss(){
		$this->assertTrue(is_string(fgetss(self::$fh,1024)));
	}

	public function test_fileatime(){
		$this->assertGreaterThan(0,fileatime(self::$tmp_file));
	}

	public function test_filectime(){
		$this->assertGreaterThan(0,filectime(self::$tmp_file));
	}

	public function test_filesize(){
		$this->assertEquals(self::TEST_SIZE,filesize(self::$tmp_file));
	}

	public function test_filetype(){
		$this->assertEquals('file',filetype(self::$tmp_file));
	}

	public function test_fscanf(){
		$this->assertTrue(is_array(fscanf(self::$fh,'%s')));
	}

	public function test_fstat(){
		$this->assertEquals(26,count(fstat(self::$fh)));
	}

	public function test_fseek(){
		$this->assertEquals(0,fseek(self::$fh,0));
	}

	public function test_ftell(){
		$this->assertEquals(0,fseek(self::$fh,1024));
		$this->assertEquals(1024,ftell(self::$fh));
	}

	/**
	 * @expectedException ErrorException
	 */
	public function test_ftruncate(){
		ftruncate(self::$fh,1024);
	}

	public function test_is_writable(){
		$this->assertTrue(is_writable(self::$tmp_file_write));
	}

	public function test_is_readable(){
		$this->assertTrue(is_readable(self::$tmp_file));
	}

	public function test_readfile(){
		// $this->assertEquals(self::TEST_SIZE,readfile(self::$tmp_file));
		// $data = ob_get_contents(); ob_clean();
		// $this->assertEquals(self::TEST_SIZE,strlen($data));
	}

	public function test_rewind(){
		$this->assertTrue(rewind(self::$fh));
	}

	public function test_stream_set_write_buffer(){
		$this->assertEquals(0,stream_set_write_buffer(self::$fh,8192));
	}

	public function test_stat(){
		$this->assertEquals(26,count(stat(self::$tmp_file)));
	}

	/**
	 * @expectedException ErrorException
	 */
	public function test_touch(){
		touch(self::$tmp_file);
	}

	public function test_unlink(){
		list($data,$hash) = $this->genData();
		$fh = fopen(self::$tmp_file_write,'w');
		$this->assertTrue(is_resource($fh));
		$this->assertEquals(strlen($data),fwrite($fh,$data));
		$this->assertTrue(fclose($fh));
		unlink(self::$tmp_file_write);
	}

}
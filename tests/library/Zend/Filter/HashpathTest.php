<?php

class Zend_Filter_hashpathTest extends PHPUnit_Framework_TestCase
{
	protected $_basePath;
	protected $_originalFileName	= "originalFile.txt";
	protected $_workFileName 	= "workFile.txt";
	protected $_originalFile;
	protected $_workFile;
	
	function rrmdir($path)
	{
	  return is_file($path)? @unlink($path): array_map(array($this,'rrmdir'),glob($path.'/*'))==@rmdir($path) ;
	}
		
	protected function cleanDir($path)
	{
		return array_map(array($this,'rrmdir'),glob($path.'/*'));
	}
	
	protected function cleanUp()
	{
		$this->cleanDir($this->_basePath);
		$this->_originalFile = $this->_basePath.$this->_originalFileName;
		$this->_workFile = $this->_basePath.$this->_workFileName;
		
        	$fd = fopen($this->_originalFile,'w+');
		if(!$fd)
		{
			throw new Exception("Unable to create original file ".$this->_originalFile);
		}
		
		fwrite($fd,"This is a test file!");
		fclose($fd);
		
		
	        if (!file_exists($this->_workFile)) {
	            copy($this->_originalFile, $this->_workFile);
	        }
		
	}
	
	public function setUp()
	{
		$this->_basePath = dirname(__FILE__).DIRECTORY_SEPARATOR.'_files'.DIRECTORY_SEPARATOR;
		
		
		
		if(!is_writable($this->_basePath))
		{
			mkdir($this->_basePath, 0700);
		        chmod($this->_basePath, 0700); 
			if(!is_writable($this->_basePath))
			{
				throw new Exception("Directory ".$this->_basePath." must exist and be writable.");
			}
		}
		
	}

	public function tearDown()
	{
		$this->cleanDir($this->_basePath);
		rmdir($this->_basePath);
	}
	
	public function testHashLevelOne()
	{
		$this->cleanUp();
		
		$options = array('base_dir'=>$this->_basePath,'directory_level' => 1);
		$filter = new Zend_Filter_Hashpath($options);
		$result = $filter->filter($this->_workFile);
		$this->assertFileExists($result);
		$this->assertFileEquals($this->_originalFile, $result);
		
		$path =  pathinfo($result);
		$path = str_replace($this->_basePath, '', $path['dirname']);
		$path_parts = explode(DIRECTORY_SEPARATOR,$path);
		
		$this->assertEquals(1, count($path_parts),$result.' '.$path);
	}
	
	public function testHashLevelTwo()
	{
		$this->cleanUp();
		
		$options = array('base_dir'=>$this->_basePath,'directory_level' => 2);
		$filter = new Zend_Filter_Hashpath($options);
		$result = $filter->filter($this->_workFile);
		$this->assertFileExists($result);
		$this->assertFileEquals($this->_originalFile, $result);
		
		$path =  pathinfo($result);
		$path = str_replace($this->_basePath, '', $path['dirname']);
		$path_parts = explode(DIRECTORY_SEPARATOR,$path);
		
		$this->assertEquals(2, count($path_parts));
	}

	
	public function testNoHashFilename()
	{
		$this->cleanUp();
		
		$options = array('base_dir'=>$this->_basePath,'directory_level' => 2,'hashfilename' => false);
		$filter = new Zend_Filter_Hashpath($options);
		$result = $filter->filter($this->_workFile);
		$this->assertFileExists($result);
		$this->assertFileEquals($this->_originalFile, $result);
		$this->assertEquals(basename($this->_workFile), basename($result));
	}
	
	public function testOverwriteNone()
	{
		$this->cleanUp();
		
		$options = array('base_dir'=>$this->_basePath,'directory_level' => 2);
		$filter = new Zend_Filter_Hashpath($options);
		$result = $filter->filter($this->_workFile);
		$this->assertFileExists($result);
		$this->assertFileEquals($this->_originalFile, $result);
		
		
	        copy($this->_originalFile, $this->_workFile);
	        
	        try{
				$newresult = $filter->filter($this->_workFile);
	        }catch(Zend_Filter_Exception $e){
	        	$this->assertTrue($e instanceof Zend_Filter_Exception);
	        }

		$this->assertFileExists($this->_workFile);
		$this->assertFileEquals($this->_workFile, $this->_originalFile);
		$this->assertFileExists($result);
		$this->assertFileEquals($this->_originalFile, $result);
		
	}
	

	public function testOverwriteAll()
	{
		$this->cleanUp();
		
		$options = array('base_dir'=>$this->_basePath,'directory_level' => 2,'overwrite_mode' => Zend_Filter_Hashpath::OVERWRITE_ALL);
		$filter = new Zend_Filter_Hashpath($options);
		$result = $filter->filter($this->_workFile);
		$this->assertFileExists($result);
		$this->assertFileEquals($this->_originalFile, $result);
 	        copy($this->_originalFile, $this->_workFile);
 	       
		$newresult = $filter->filter($this->_workFile);

		$this->assertFileNotExists($this->_workFile);
		$this->assertFileExists($newresult);
		$this->assertFileEquals($this->_originalFile, $newresult);
		
	}	
	
}

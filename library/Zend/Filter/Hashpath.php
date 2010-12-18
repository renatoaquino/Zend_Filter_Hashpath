<?php

/**
 * @see Zend_Filter_Interface
 */
require_once 'Zend/Filter/Interface.php';

/**
 * @see Zend_Filter_Exception
 */
require_once 'Zend/Filter/Exception.php';

/**
 * Filter for rename files to folders created as hash paths.
 */
class Zend_Filter_Hashpath implements Zend_Filter_Interface
{
    /**
     * Overwrite nothing. 
     * If something is in the way, an exception is thrown. 
     * This is the default.
     * @var string
     */
    const OVERWRITE_NONE = 'none';
    
    /**
     * Override everything.
     * Use this defensively.
     * @var unknown_type
     */
    const OVERWRITE_ALL = 'all';

    /**
     * Available options
     *
     * =====> (int) directory_level :
     * - Hashed directory level
     * - Set the hashed directory structure level. 0 means "no hashed directory
     * structure", 1 means "one level of directory", 2 means "two levels"...
     * This option can speed up only when you have many thousands of
     * files. Only specific benchs can help you to choose the perfect value
     * for you. Maybe, 1 or 2 is a good start. Default 2.
     *
     * =====> (int) directory_umask :
     * - Umask for hashed directory structure
     *
     * =====> (string) base_dir :
     * - The working directory settings.
     * - Will be prepend to the output_dir setting when opening and 
     * writting files. 
     * - This path must exist and have write permissions. Default './'
     *
     * =====> (string) overwrite_mode :
     * - Flag defining the behaviour regarding overwriting existing files.
     * - By default nothing is overwritten. 
     * - Possible options are :
     * 	 	Zend_Filter_Hashpath::OVERWRITE_NONE,
     * 		Zend_Filter_Hashpath::OVERWRITE_ALL
     * - Default Zend_Filter_Hashpath::OVERWRITE_ALL
     *
     * =====> (bool) hashfilename :
     * - Flag defining the behavior of renaming the file to a hash
     * - Default true
     * 
     * 
     * @var array available options
     */
    protected $_options = array(
        'directory_level' => 2,
        'directory_umask' => 0700,
    	'base_dir'	=> './',
    	'overwrite_mode' => self::OVERWRITE_ALL,
    	'hashfilename' => true,
    );    
    
    /**
     * Constructor
     *
     * @param  array $options associative array of options
     * @throws Zend_Filesystem_Exception
     * @return void
     */
    public function __construct(array $options = array())
    {
        if ($options['base_dir'] !== null) { // particular case for this option
            $this->setBaseDir($options['base_dir']);
        } 
        
        if (isset($options['directory_level'])) {
        	$this->_options['directory_level'] = (int) $options['directory_level'];
        }
                
        if (isset($options['directory_umask']) && is_string($options['directory_umask'])) {
            // See #ZF-4422
            $this->_options['directory_umask'] = octdec($this->_options['directory_umask']);
        }
        
        if (isset($options['overwrite_mode'])) {
        	$this->setOverwriteMode($options['overwrite_mode']);
        } 
        
        if (isset($options['hashfilename'])) {
        	$this->_options['hashfilename'] = $options['hashfilename'];
        } 
        
    }

    /**
     * Set the base_dir, usually the DOCUMENT_ROOT_PATH 
     *
     * @param  string  $value
     * @param  boolean $trailingSeparator If true, add a trailing separator is necessary
     * @throws Zend_Filter_Exception
     * @return $this for fluent interface
     */
    public function setBaseDir($value, $trailingSeparator = true)
    {
        if (!is_dir($value)) {
            throw new Zend_Filter_Exception('base_dir must be an existing directory');
        }
        if (!is_writable($value)) {
            throw new Zend_Filter_Exception('base_dir is not writable');
        }
        if ($trailingSeparator) {
            // add a trailing DIRECTORY_SEPARATOR if necessary
            $value = rtrim(realpath($value), '\\/') . DIRECTORY_SEPARATOR;
        }
        $this->_options['base_dir'] = $value;
    }
    
    public function getBaseDir()
    {
    	return $this->_options["base_dir"];
    }
    
    public function setDirectoryLevel($value)
    {
    	$this->_options['directory_level'] = (int) $value;
    	return $this;
    }
    
    public function getDirectoryLevel()
    {
    	return $this->_options["directory_level"];
    }
    
    public function setHashFileName($value)
    {
    	$this->_options['hashfilename'] = (bool) $value;
    	return $this;
    }
    
    public function getHashFileName()
    {
    	return $this->_options['hashfilename'];
    }
    
    
    public function setDirectoryMask($value)
    {
        if(is_string($value)) {
            // See #ZF-4422
            $this->_options['directory_umask'] = octdec($value);
        }else{
        	$this->_options['directory_umask'] = $value;
        }
    }

    public function getDirectoryMask()
    {
    	return $this->_options["directory_umask"];
    }
    
    
    /**
     * Return the complete directory path (including hashedDirectoryStructure)
     *
     * @param  string $id The path identifier
     * @param  boolean $parts if true, returns array of directory parts instead of single string
     * @return string Complete directory path
     */
    protected function _path($id, $parts = false)
    {
        $partsArray = array();
        $root = $this->_options['base_dir'];
        if ($this->_options['directory_level']>0) {
            $hash = hash('adler32', $id);
            for ($i=0 ; $i < $this->_options['directory_level'] ; $i++) {
                $root = $root . substr($hash, 0, $i + 1) . DIRECTORY_SEPARATOR;
                $partsArray[] = $root;
            }
        }
        if ($parts) {
            return $partsArray;
        } else {
            return $root;
        }
    }

    /**
     * Make the directory strucuture for the given id
     *
     * @param string $id cache id
     * @return boolean true
     */
    protected function _recursiveMkdirAndChmod($id)
    {
        if ($this->_options['directory_level'] <=0) {
            return true;
        }
        $partsArray = $this->_path($id, true);
        foreach ($partsArray as $part) {
            if (!is_dir($part)) {
                @mkdir($part, $this->_options['directory_umask']);
                @chmod($part, $this->_options['directory_umask']); // see #ZF-320 (this line is required in some configurations)
            }
        }
        return true;
    }    
 
    /**
     * Returns the result of filtering $value
     *
     * @param  string $value Path to a file.
     * @throws Zend_Filter_Exception If filtering $value is impossible
     * @return string The new path of the file.
     * @throws Zend_Filter_Exception
     */
    public function filter($value)
    {
        if(!file_exists($value)) {
            throw new Zend_Filter_Exception('File does not exist: ' . $value);    
        }
        
        clearstatcache();
    	$id = sha1_file($value);
        $outputPath  = $this->_path($id);
        
        if ($this->_options['directory_level'] > 0) {
            if (!is_writable($outputPath)) {
                // maybe, we just have to build the directory structure
                $this->_recursiveMkdirAndChmod($id);
            }

            if (!is_writable($outputPath)) {
		throw new Zend_Filter_Exception($outputPath.' is not writable');
	    }
        }
        
        if($this->_options['hashfilename'])
        {
        	$path_parts 	= pathinfo($value);
        	$newname  	= $id.'.'.$path_parts['extension'];
        	$newPath  	= $outputPath.$newname;
        }else{
        	$newPath = $outputPath.basename($value);
        }
        
        if(file_exists($newPath)) {
            switch($this->_options['overwrite_mode']) {
                case self::OVERWRITE_ALL:
                    // just do it.
                    unlink($newPath);
                    break;
                case self::OVERWRITE_NONE:
                    throw new Zend_Filter_Exception('File already exists: ' . $newPath);
                default:
                    break;
            }
        }
        
        $result = rename($value, $newPath);
        if ($result === true) {
            return $newPath;        
        }

        throw new Zend_Filter_Exception(sprintf("File '%s' could not be renamed. An error occured while processing the file.", $value));
    }
    
    /**
     * Get overwrite mode.
     * @return string
     */
    public function getOverwriteMode()
    {
        return $this->_options['overwrite_mode'];
    }
    
    /**
     * Set overwrite mode.
     * @param string $mode
     * @return Zend_Filter_ImageSize Fluent interface
     */
    public function setOverwriteMode($mode)
    {
        if((!in_array($mode, array(self::OVERWRITE_ALL, self::OVERWRITE_NONE)))) {
            throw new Zend_Filter_Exception('Unsupported overwrite mode: ' . $mode);            
        }
        $this->_options['overwrite_mode'] = $mode;
        return $this;
    }
  
}

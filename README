Zend_Filter_Hashpath
====================

A filter to store files on a hashed directory structure.
It's a simple modification of Zend_Filter_Rename to store files on a directory struture and avoid an overload of files into only one directory.

The directory structure is based on the code of [Zend_Cache_Backend_File](http://framework.zend.com/manual/en/zend.cache.backends.html).


Instalation
-----------

Place the files of the library directory on your include path to allow the Zend_Loader usage behavior, or just include Zend/Filter/Hashpath.php.


Usage
-----

	class Form_AmazingForm extends Zend_Form
	{
		public function init()
		{

			//Add your elements
			//....
			//Add the file element
			$element = new Zend_Form_Element_File('image');
			$element->addValidator('Extension', false, 'jpg,png,gif');
			//Atach the filter
			$element->addFilter(
					'Hashpath',
					//Set the base directory
					array('base_dir'=>APPLICATION_PATH."/../public/images")
			);
			$element->setLabel("Image");
			$this->addElement($element, 'image');

			//Finish adding our elements
			//...
		}
	}


On your controller:
	
	public function saveAction()
	{
		$request = $this->getRequest();
		$form = new Form_AmazingForm();

		//Validate your form

		if($form->isValid($request->getPost())
		{
			if ($form->image->isUploaded()
	    		{
	    			if($form->image->receive())
	    			{
					$file = $form->image->getFileName();
					//Now $file has a filtered upload in a path like APPLICATION_PATH."/../public/images/0/0b/sha1ofthefile.extension"
					//You can now process the $file value and save it
				}
			}
		}
	}


Configuration
-------------

* base_dir => The base directory to store the structure and file
* directory_level => The Hashed directory level, the same logic of Zend_Cache_Backend_File
* directory_umask => Umask for hashed directory structure
* overwrite_mode => Zend_Filter_Hashpath::OVERWRITE_NONE or Zend_Filter_Hashpath::OVERWRITE_ALL
* hashfilename => Flag defining the behavior of renaming the file to a hash, default true

	$filter = new Zend_Filter_Hashpath(array(
		'base_dir'		=> APPLICATION_PATH."/../public/images/",
		'directory_level'	=> 2, //The default value is 2
		'overwrite_mode'	=> Zend_Filter_Hashpath::OVERWRITE_NONE, // Throws a Zend_Filter_Exception if a file with the same name exists
		'hashfilename' 		=> false //Stores the files with the original name
	));


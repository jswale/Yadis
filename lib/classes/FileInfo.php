<?php
class FileInfo {
	private $id;
	private $name;
	private $path;
	private $index;

	function __construct() {
		if(2 == func_num_args()) {
			call_user_func_array(array($this,"_FileInfo"), func_get_args());
		}
	}

	function _FileInfo($name, $path) {
		$this->id = md5($name);
		$this->name = $name;
		$this->path = $path;
		$this->index = null;
	}

	public function getId() {
		return $this->id;
	}

	public function getName() {
		return $this->name;
	}

	public function getPath() {
		return $this->path;
	}

	public function getIndex() {
		return $this->index;
	}

	public function setIndex($index) {
		return $this->index = $index;
	}

	public function setId($id) {
		return $this->id = $id;
	}

	public function setPath($path) {
		return $this->path = $path;
	}

	public function getUrlArgs() {
		return array(
				ActionResolver::ARGS_PATH => $this->getPath(),
				ActionResolver::ARGS_FILE_INDEX => $this->getIndex(),
				ActionResolver::ARGS_FILE_ID => $this->getId(),
				ActionResolver::ARGS_FILE_NAME => $this->getName()
		);
	}

	public function loadFromUrlArgs() {
		$attribs = array(
				ActionResolver::ARGS_PATH => 'path',
				ActionResolver::ARGS_FILE_INDEX => 'index',
				ActionResolver::ARGS_FILE_ID => 'id',
				ActionResolver::ARGS_FILE_NAME => 'name'
		);
		foreach($attribs as $attrib => $property) {
			if(isset($_REQUEST[$attrib])) {
				$this->{$property} = $_REQUEST[$attrib];
			} else {
				return false;
			}
		}
		return true;
	}
}
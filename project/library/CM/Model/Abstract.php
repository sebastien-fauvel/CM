<?php

abstract class CM_Model_Abstract extends CM_Class_Abstract implements CM_Comparable, Serializable {

	/**
	 * @var array $_assets
	 */
	private $_assets = array();

	/**
	 * @var array $_id
	 */
	protected $_id;

	/**
	 * @var array $_data
	 */
	private $_data;

	/**
	 * @param int $id
	 */
	public function __construct($id) {
		$this->_construct(array('id' => (int) $id));
	}

	/**
	 * @param array $id
	 */
	protected function _construct(array $id) {
		$this->_id = $id;
		foreach ($this->_loadAssets() as $asset) {
			$this->_assets[get_class($asset)] = $asset;
		}
		$this->_get(); // Make sure data can be loaded
	}

	abstract protected function _loadData();

	public function delete() {
		foreach ($this->_assets as $asset) {
			$asset->_onModelDelete();
		}
		$this->_onDelete();
		CM_Cache::delete($this->_getCacheKey());
		$this->_data = null;
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		$id = $this->_getId();
		return $id['id'];
	}

	/**
	 * @param CM_Model_Abstract $model OPTIONAL
	 * @return boolean
	 */
	public function equals(self $model = null) {
		if (empty($model)) {
			return false;
		}
		return (get_class($this) == get_class($model) && $this->_getId() === $model->_getId());
	}

	public function serialize() {
		return serialize(array($this->_id, $this->_data));
	}

	public function unserialize($data) {
		list($this->_id, $this->_data) = unserialize($data);
		foreach ($this->_loadAssets() as $asset) {
			$this->_assets[get_class($asset)] = $asset;
		}
	}

	/**
	 * @return CM_Model_Abstract
	 */
	public function _change() {
		CM_Cache::delete($this->_getCacheKey());
		$this->_data = null;
		$this->_onChange();
		return $this;
	}

	/**
	 * @param string $field
	 * @return mixed
	 * @throws CM_Exception|CM_Exception_Nonexistent
	 */
	final public function _get($field = null) {
		if (!$this->_data) {
			$cacheKey = $this->_getCacheKey();
			if (($this->_data = CM_Cache::get($cacheKey)) === false) {
				$this->_data = $this->_loadData();
				if ($this->_data === false) {
					throw new CM_Exception_Nonexistent(get_called_class() . ' `' . $this->getId() . '` has no data.');
				}
				foreach ($this->_assets as $asset) {
					$asset->_loadAsset();
				}
				CM_Cache::set($cacheKey, $this->_data);
				$this->_onLoad();
			}
		}
		if ($field === null) {
			return $this->_data;
		}
		if (!array_key_exists($field, $this->_data)) {
			throw new CM_Exception('Model has no field `' . $field . '`');
		}
		return $this->_data[$field];
	}

	/**
	 * @param string $field
	 * @return boolean
	 */
	final public function _has($field) {
		$this->_get(); // Make sure data is loaded
		return array_key_exists($field, $this->_data);
	}

	/**
	 * @param string $field
	 * @param mixed $value
	 */
	final public function _set($field, $value) {
		$this->_get(); // Make sure data is loaded
		$this->_data[$field] = $value;
	}

	protected function _onChange() {
	}

	protected function _onDelete() {
	}

	protected function _onLoad() {
	}

	/**
	 * @return array
	 */
	protected function _getId() {
		return $this->_id;
	}

	/**
	 * @return CM_ModelAsset_Abstract[]
	 */
	protected function _loadAssets() {
		return array();
	}

	/**
	 * @param string $className
	 * @return boolean
	 */
	final protected function _hasAsset($className) {
		return isset($this->_assets[$className]);
	}

	/**
	 * @param string $className
	 * @return CM_ModelAsset_Abstract
	 */
	final protected function _getAsset($className) {
		if (!$this->_hasAsset($className)) {
			throw new CM_Exception('No such asset `' . $className . '`');
		}
		return $this->_assets[$className];
	}

	/**
	 * @return string
	 */
	final private function _getCacheKey() {
		return CM_CacheConst::Model . '_class:' . get_class($this) . '_id:' . serialize($this->_getId());
	}

	/**
	 * @param array $data
	 * @return CM_Model_Abstract
	 */
	final public static function create(array $data = null) {
		if ($data === null) {
			$data = array();
		}
		$model = static::_create($data);
		$model->_onChange();
		return $model;
	}

	/**
	 * @param array $data
	 * @return CM_Model
	 */
	protected static function _create(array $data) {
		throw new CM_Exception_NotImplemented();
	}
}

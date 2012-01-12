<?php

class CM_Model_User extends CM_Model_Abstract implements CM_ArrayConvertible {

	const TYPE = 13;

	/**
	 * @return boolean
	 */
	public function canLogin() {
		return true;
	}

	/**
	 * @param int|null $entityType
	 * @param int|null $actionType
	 * @param int|null $period
	 * @return CM_Paging_Action_User
	 */
	public function getActions($entityType = null, $actionType = null, $period = null) {
		return new CM_Paging_Action_User($this, $entityType, $actionType, $period);
	}

	/**
	 * @return int
	 */
	public function getCreated() {
		return $this->_get('createStamp');
	}

	/**
	 * @see CM_ModelAsset_User_Roles::getDefault()
	 * @return int[]
	 */
	public function getDefaultRoles() {
		return array();
	}

	/**
	 * @return string|null
	 */
	public function getEmail() {
		return null;
	}

	/**
	 * @return boolean
	 */
	public function getEmailVerified() {
		return true;
	}

	/**
	 * @return int
	 */
	public function getLatestactivity() {
		return (int) $this->_get('activityStamp');
	}

	/**
	 * @return boolean
	 */
	public function getOnline() {
		return (boolean) $this->_get('online');
	}

	/**
	 * @param boolean $state   OPTIONAL
	 * @param boolean $visible OPTIONAL
	 */
	public function setOnline($state = true, $visible = true) {
		$visible = (bool) $visible;
		if ($state) {
			CM_Mysql::replace(TBL_CM_USER_ONLINE, array('userId' => $this->getId(), 'visible' => $visible));
		} else {
			CM_Mysql::delete(TBL_CM_USER_ONLINE, array('userId' => $this->getId()));
		}
		$this->_change();
	}

	/**
	 * @return CM_ModelAsset_User_Preferences
	 */
	public function getPreferences() {
		return $this->_getAsset('CM_ModelAsset_User_Preferences');
	}

	/**
	 * @return CM_Site_Abstract
	 */
	public function getSite() {
		$siteType = $this->_get('site');
		return CM_Site_Abstract::factory($siteType);
	}

	/**
	 * @return CM_ModelAsset_User_Roles
	 */
	public function getRoles() {
		return $this->_getAsset('CM_ModelAsset_User_Roles');
	}

	/**
	 * @param int $entityType OPTIONAL
	 * @param int $actionType OPTIONAL
	 * @param int $limitType  OPTIONAL
	 * @param int $period	 OPTIONAL
	 * @return CM_Paging_Transgression_User
	 */
	public function getTransgressions($entityType = null, $actionType = null, $limitType = null, $period = null) {
		return new CM_Paging_Transgression_User($this, $entityType, $actionType, $limitType, $period);
	}

	/**
	 * @return CM_Paging_Useragent_User
	 */
	public function getUseragents() {
		return new CM_Paging_Useragent_User($this);
	}

	/**
	 * @return boolean
	 */
	public function getVisible() {
		return (boolean) $this->_get('visible');
	}

	/**
	 * @param boolean $state
	 * @return CM_Model_User
	 */
	public function setVisible($state = true) {
		if (!$this->getOnline()) {
			throw new CM_Exception_Invalid('Must not modify visibility of a user that is offline');
		}
		CM_Mysql::replace(TBL_CM_USER_ONLINE, array('userId' => $this->getId(), 'visible' => (int) $state));
		return $this->_change();
	}

	/**
	 * @return string
	 */
	public function getDisplayName() {
		return 'user' . $this->getId();
	}

	/**
	 * @return CM_Model_User
	 */
	public function updateLatestactivity() {
		CM_Mysql::update(TBL_CM_USER, array('activityStamp' => time()), array('userId' => $this->getId()));
		return $this->_change();
	}

	protected function _loadAssets() {
		return array(new CM_ModelAsset_User_Preferences($this), new CM_ModelAsset_User_Roles($this));
	}

	protected function _loadData() {
		return CM_Mysql::exec("SELECT `main`.*, `online`.`userId` AS `online`, `online`.`visible` FROM TBL_CM_USER AS `main`
								LEFT JOIN TBL_CM_USER_ONLINE AS `online` USING (`userId`)
								WHERE `main`.`userId`=?", $this->getId())->fetchAssoc();
	}

	/**
	 * @param int $id
	 * @return CM_Model_User
	 */
	public static function factory($id) {
		$className = self::_getClassName();
		return new $className($id);
	}

	/**
	 * @param array $data
	 * @return CM_Model_User
	 */
	protected static function _create(array $data) {
		$userId = CM_Mysql::insert(TBL_CM_USER, array('createStamp' => time(), 'activityStamp' => time()));
		return new static($userId);
	}

	protected function _onDelete() {
		$this->getTransgressions()->deleteAll();
		CM_Mysql::delete(TBL_CM_USER, array('userId' => $this->getId()));
	}

	public function toArray() {
		return array('id' => $this->getId(), 'type' => $this->getType(), 'displayName' => $this->getDisplayName(), 'visible' => $this->getVisible());
	}

	public static function fromArray(array $data) {
		return new static($data['id']);
	}
}

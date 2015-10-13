<?php

class CM_Model_StreamChannel_Video extends CM_Model_StreamChannel_Abstract {

    public function onPublish(CM_Model_Stream_Publish $streamPublish) {
    }

    public function onSubscribe(CM_Model_Stream_Subscribe $streamSubscribe) {
    }

    public function onUnpublish(CM_Model_Stream_Publish $streamPublish) {
        if (!CM_Model_StreamChannelArchive_Video::findById($this->getId())) {
            CM_Model_StreamChannelArchive_Video::createStatic(array('streamChannel' => $this));
        }
    }

    public function onUnsubscribe(CM_Model_Stream_Subscribe $streamSubscribe) {
    }

    /**
     * @param int $thumbnailCount
     */
    public function setThumbnailCount($thumbnailCount) {
        $thumbnailCount = (int) $thumbnailCount;
        CM_Db_Db::update('cm_streamChannel_video', array('thumbnailCount' => $thumbnailCount), array('id' => $this->getId()));
        $this->_change();
    }

    /**
     * @return int
     */
    public function getWidth() {
        return (int) $this->_get('width');
    }

    /**
     * @return string
     */
    public function getHash() {
        return md5($this->getStreamPublish()->getKey());
    }

    /**
     * @return int
     */
    public function getHeight() {
        return (int) $this->_get('height');
    }

    /**
     * @return CM_Model_Stream_Publish
     * @throws CM_Exception_Invalid
     */
    public function getStreamPublish() {
        if (!$this->hasStreamPublish()) {
            throw new CM_Exception_Invalid('StreamChannel `' . $this->getId() . '` has no StreamPublish.');
        }
        return $this->getStreamPublishs()->getItem(0);
    }

    /**
     * @return boolean
     */
    public function hasStreamPublish() {
        return (boolean) $this->getStreamPublishs()->getCount();
    }

    /**
     * @return int
     */
    public function getServerId() {
        return (int) $this->_get('serverId');
    }

    public function toArray() {
        $array = parent::toArray();
        $array['key'] = $this->getKey();
        if ($this->hasStreamPublish()) {
            $array['user'] = $this->getStreamPublish()->getUser();
        }
        return $array;
    }

    /**
     * @return int
     */
    public function getThumbnailCount() {
        return (int) $this->_get('thumbnailCount');
    }

    /**
     * @param int $index
     * @return CM_File_UserContent
     */
    public function getThumbnail($index) {
        $index = (int) $index;
        $filename = $this->getId() . '-' . $this->getHash() . '-thumbs' . DIRECTORY_SEPARATOR . $index . '.png';
        return new CM_File_UserContent('streamChannels', $filename, $this->getId());
    }

    /**
     * @return CM_Paging_FileUserContent_StreamChannelVideoThumbnails
     */
    public function getThumbnails() {
        return new CM_Paging_FileUserContent_StreamChannelVideoThumbnails($this);
    }

    protected function _onDelete() {
        CM_Db_Db::delete('cm_streamChannel_video', array('id' => $this->getId()));
        parent::_onDelete();
    }

    protected function _loadData() {
        return CM_Db_Db::exec("SELECT * FROM `cm_streamChannel` JOIN `cm_streamChannel_video` USING (`id`)
								WHERE `id` = ?", array($this->getId()))->fetch();
    }

    protected static function _createStatic(array $data) {
        $key = (string) $data['key'];
        $width = (int) $data['width'];
        $height = (int) $data['height'];
        $serverId = $data['serverId'];
        $thumbnailCount = (int) $data['thumbnailCount'];
        $adapterType = (int) $data['adapterType'];
        $id = CM_Db_Db::insert('cm_streamChannel', array('key' => $key, 'type' => static::getTypeStatic(), 'adapterType' => $adapterType));
        try {
            CM_Db_Db::insert('cm_streamChannel_video', array('id'             => $id, 'width' => $width, 'height' => $height, 'serverId' => $serverId,
                                                             'thumbnailCount' => $thumbnailCount));
        } catch (CM_Exception $ex) {
            CM_Db_Db::delete('cm_streamChannel', array('id' => $id));
            throw $ex;
        }
        return new static($id);
    }
}

<?php

class CM_Log_Handler_MongoDb extends CM_Log_Handler_Abstract {

    /** @var  string */
    protected $_collection;

    /** @var int */
    protected $_recordTtl;

    /** @var  CM_MongoDb_Client */
    protected $_mongoDb;

    /**
     * @param string   $collection
     * @param int|null $recordTtl Time To Live in seconds
     * @param int|null $level
     */
    public function __construct($collection, $recordTtl = null, $level = null) {
        parent::__construct($level);
        $this->_collection = (string) $collection;
        $this->_mongoDb = CM_Service_Manager::getInstance()->getMongoDb();
        $this->_validateCollection($this->_collection);
        $this->_recordTtl = null === $recordTtl ? 3600 * 30 * 2 : (int) $recordTtl;
    }

    /**
     * @param CM_Log_Record $record
     */
    protected function _writeRecord(CM_Log_Record $record) {
        /** @var array $formattedRecord */
        $formattedRecord = $this->_formatRecord($record);

        $this->_mongoDb->insert($this->_collection, $formattedRecord);
    }

    /**
     * @param CM_Log_Record $record
     * @return array
     */
    protected function _formatRecord(CM_Log_Record $record) {
        $recordContext = $record->getContext();
        $computerInfo = $recordContext->getComputerInfo();
        $user = $recordContext->getUser();
        $extra = $recordContext->getExtra();

        $createdAt = $record->getCreatedAt();
        $expireAt = clone $createdAt;
        $expireAt->add(new DateInterval('PT' . $this->_recordTtl . 'S'));

        $formattedContext = [];
        if (null !== $computerInfo) {
            $formattedContext['computerInfo'] = [
                'fqdn'       => $computerInfo->getFullyQualifiedDomainName(),
                'phpVersion' => $computerInfo->getPhpVersion(),
            ];
        }
        if (null !== $extra) {
            $formattedContext['extra'] = $extra;
        }
        if (null !== $user) {
            $formattedContext['user'] = [
                'id'   => $user->getId(),
                'name' => $user->getDisplayName(),
            ];
        }

        return [
            'level'     => (int) $record->getLevel(),
            'message'   => (string) $record->getMessage(),
            'createdAt' => new MongoDate($createdAt->getTimestamp()),
            'context'   => $formattedContext,
            'expireAt'  => new MongoDate($expireAt->getTimestamp()),
        ];
    }

    /**
     * @param string $collection
     * @throws CM_Exception_Invalid
     */
    protected function _validateCollection($collection) {
        $indexInfo = $this->_mongoDb->getIndexInfo($collection);

        $foundIndex = \Functional\some($indexInfo, function ($el) {
            return isset($el['key']['expireAt']) && isset($el['expireAfterSeconds']) && $el['expireAfterSeconds'] == 0;
        });

        if (!$foundIndex) {
            throw new CM_Exception_Invalid('MongoDb Collection `' . $collection . '` does not contain valid TTL index');
        };
    }
}

<?php

$config->dirData = DIR_TESTS . 'tmp' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;
$config->dirTmp = DIR_TESTS . 'tmp' . DIRECTORY_SEPARATOR;
$config->dirUserfiles = DIR_TESTS . 'tmp' . DIRECTORY_SEPARATOR . 'userfiles' . DIRECTORY_SEPARATOR;

$config->CM_Mail->send = false;

$config->CM_Search->enabled = false;

$config->CM_Db_Db->db = $config->CM_Db_Db->db . '_test';
$config->CM_Db_Db->username = 'travis';
$config->CM_Db_Db->password = '';
$config->CM_Db_Db->serversReadEnabled = false;
$config->CM_Db_Db->delayedEnabled = false;

$config->classConfigCacheEnabled = false;

$config->CM_Model_DeviceCapabilities->adapter = null;

$config->CM_Model_Splittest->withoutPersistence = true;

$config->CM_Model_Splitfeature->withoutPersistence = true;

$config->CM_Jobdistribution_Job_Abstract->gearmanEnabled = false;

$config->CMTest_TH->dropDatabase = true;

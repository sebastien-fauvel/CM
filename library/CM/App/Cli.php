<?php

class CM_App_Cli extends CM_Cli_Runnable_Abstract {

    /**
     * @param bool|null $reload
     */
    public function setup($reload = null) {
        $this->_getStreamOutput()->writeln('Setting up filesystem…');
        $this->setupFilesystem();
        $this->_getStreamOutput()->writeln('Setting up database…');
        $this->setupDatabase($reload);
        $this->_getStreamOutput()->writeln('Setting up elasticsearch indexes…');
        $this->setupElasticsearch($reload);
        $this->_getStreamOutput()->writeln('Setting up translations…');
        $this->setupTranslations();

        if ($reload) {
            $cacheCli = new CM_Cache_Cli($this->_getStreamInput(), $this->_getStreamOutput(), $this->_getStreamError());
            $cacheCli->clear();
        }
    }

    public function setupFilesystem() {
        CM_App::getInstance()->setupFilesystem();
    }

    /**
     * @param bool|null $reload
     */
    public function setupDatabase($reload = null) {
        CM_App::getInstance()->setupDatabase($this->_getStreamOutput(), $reload);
    }

    /**
     * @param bool|null $reload
     */
    public function setupElasticsearch($reload = null) {
        $searchCli = new CM_Elasticsearch_Index_Cli($this->_getStreamInput(), $this->_getStreamOutput(), $this->_getStreamError());
        $searchCli->create(null, !$reload);
    }

    public function setupTranslations() {
        CM_App::getInstance()->setupTranslations();
    }

    public function fillCaches() {
        $this->_getStreamOutput()->writeln('Warming up caches…');
        CM_App::getInstance()->fillCaches();
    }

    public function deploy() {
        $this->setup();
        $this->setDeployVersion();

        $dbCli = new CM_Db_Cli($this->_getStreamInput(), $this->_getStreamOutput(), $this->_getStreamError());
        $dbCli->runUpdates();
    }

    public function generateConfigInternal() {
        $indentation = '    ';
        $indent = function ($content) use ($indentation) {
            return preg_replace('/(:?^|[\n])/', '$1' . $indentation, $content);
        };

        $generator = new CM_Config_Generator();
        $classTypesConfig = $generator->generateConfigClassTypes();
        $actionVerbsConfig = $generator->generateConfigActionVerbs();
        foreach ($generator->getClassTypesRemoved() as $classRemoved) {
            $this->_getStreamOutput()->writeln('Removed `' . $classRemoved . '`');
        }
        foreach ($generator->getClassTypesAdded() as $type => $classAdded) {
            $this->_getStreamOutput()->writeln('Added `' . $classAdded . '` with type `' . $type . '`');
        }

        // Create model class types and action verbs config PHP
        $configPhp = new CM_File(DIR_ROOT . 'resources/config/internal.php');
        $configPhp->ensureParentDirectory();
        $configPhp->truncate();
        $configPhp->appendLine('<?php');
        $configPhp->appendLine('// This is autogenerated config file. You should not change it manually.');
        $configPhp->appendLine();
        $configPhp->appendLine('return function (CM_Config_Node $config) {');
        $configPhp->appendLine($indent($classTypesConfig));
        $configPhp->appendLine($indent($actionVerbsConfig));
        $configPhp->appendLine('};');
        $this->_getStreamOutput()->writeln('Created `' . $configPhp->getPath() . '`');

        // Create model class types and action verbs config JS
        $configJs = new CM_File(DIR_ROOT . 'resources/config/js/internal.js');
        $configJs->ensureParentDirectory();
        $configJs->truncate();
        $classTypes = $generator->getNamespaceTypes();
        $configJs->appendLine('cm.model.types = ' . CM_Params::encode(array_flip($classTypes['CM_Model_Abstract']), true) . ';');
        $configJs->appendLine('cm.action.types = ' . CM_Params::encode(array_flip($classTypes['CM_Action_Abstract']), true) . ';');
        $this->_getStreamOutput()->writeln('Created `' . $configJs->getPath() . '`');
    }

    /**
     * @param int|null $deployVersion
     */
    public function setDeployVersion($deployVersion = null) {
        $deployVersion = (null !== $deployVersion) ? (int) $deployVersion : time();
        $sourceCode = join(PHP_EOL, array(
            '<?php',
            'return function (CM_Config_Node $config) {',
            '    $config->deployVersion = ' . $deployVersion . ';',
            '};',
            '',
        ));
        $targetPath = DIR_ROOT . 'resources/config/deploy.php';
        $configFile = new CM_File($targetPath);
        $configFile->ensureParentDirectory();
        $configFile->write($sourceCode);
    }

    public static function getPackageName() {
        return 'app';
    }
}

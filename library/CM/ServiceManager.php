<?php

class CM_ServiceManager extends CM_Class_Abstract {

    /** @var array */
    private $_serviceList = array();

    /** @var array */
    private $_serviceInstanceList = array();

    /** @var CM_ServiceManager */
    protected static $instance;

    /**
     * @param string $serviceName
     * @return bool
     */
    public function has($serviceName) {
        return array_key_exists($serviceName, $this->_serviceList);
    }

    /**
     * @param string      $serviceName
     * @param string|null $assertInstanceOf
     * @throws CM_Exception_Invalid
     * @return mixed
     */
    public function get($serviceName, $assertInstanceOf = null) {
        if (!array_key_exists($serviceName, $this->_serviceInstanceList)) {
            $this->_serviceInstanceList[$serviceName] = $this->_instantiateService($serviceName);
        }
        $service = $this->_serviceInstanceList[$serviceName];
        if (null !== $assertInstanceOf && !is_a($service, $assertInstanceOf, true)) {
            throw new CM_Exception_Invalid('Service `' . $serviceName . '` is a `' . get_class($service) . '`, but not `' . $assertInstanceOf . '`.');
        }
        return $service;
    }

    /**
     * @param string     $serviceName
     * @param string     $className
     * @param array|null $arguments
     * @throws CM_Exception_Invalid
     */
    public function register($serviceName, $className, array $arguments = null) {
        $arguments = (array) $arguments;
        if ($this->has($serviceName)) {
            throw new CM_Exception_Invalid('Service `' . $serviceName . '` already registered.');
        }
        $this->_serviceList[$serviceName] = array(
            'class'     => $className,
            'arguments' => $arguments,
        );
    }

    /**
     * Methods in format get[serviceName] returns a instance of a service with given name.
     *
     * @param string $name
     * @param mixed  $parameters
     * @return mixed
     * @throws CM_Exception_Invalid
     */
    public function __call($name, $parameters) {
        if (preg_match('/get(.+)/', $name, $matches)) {
            $serviceName = $matches[1];
            return $this->get($serviceName);
        }
        throw new CM_Exception_Invalid('Cannot extract service name from `' . $name . '`.');
    }

    /**
     * @param string|null $serviceName
     * @return CM_Service_MongoDb
     */
    public function getMongoDb($serviceName = null) {
        if (null === $serviceName) {
            $serviceName = 'MongoDb';
        }
        return $this->get($serviceName, 'CM_Service_MongoDb');
    }

    /**
     * @param string $serviceName
     * @return CM_Service_Filesystem
     */
    public function getFilesystem($serviceName) {
        return $this->get($serviceName);
    }

    /**
     * @param string $serviceName
     * @throws CM_Exception_Invalid
     * @return mixed
     */
    protected function _instantiateService($serviceName) {
        if (!$this->has($serviceName)) {
            throw new CM_Exception_Invalid("Service {$serviceName} is not registered.");
        }
        $config = $this->_serviceList[$serviceName];
        $arguments = array();
        if (array_key_exists('arguments', $config)) {
            $arguments = $config['arguments'];
        }
        $reflection = new ReflectionClass($config['class']);
        return $reflection->newInstanceArgs($arguments);
    }

    /**
     * @return CM_ServiceManager
     */
    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

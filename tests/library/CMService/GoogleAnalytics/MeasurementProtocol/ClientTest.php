<?php

class CMService_GoogleAnalytics_MeasurementProtocol_ClientTest extends CMTest_TestCase {

    public function testSubmitHit() {
        $clientMock = $this->mockClass('CMService_GoogleAnalytics_MeasurementProtocol_Client');
        $submitRequestMock = $clientMock->mockMethod('_submitRequest')->set(function ($parameterList) {
            $this->assertSame([
                'foo' => 12,
                'v'   => 1,
                'tid' => 'prop1',
            ], $parameterList);
        });
        /** @var CMService_GoogleAnalytics_MeasurementProtocol_Client $client */
        $client = $clientMock->newInstance(['prop1']);

        $client->_submitHit(['foo' => 12]);
        $this->assertSame(1, $submitRequestMock->getCallCount());
    }

    public function testTrackEvent() {
        $clientMock = $this->mockClass('CMService_GoogleAnalytics_MeasurementProtocol_Client');
        $submitRequestMock = $clientMock->mockMethod('_queueHit')->set(function ($parameterList) {
            $this->assertSame([
                'propertyId'    => 'prop1',
                'parameterList' => [
                    'ec' => 'MyCategory',
                    'ea' => 'MyAction',
                    'el' => 'MyLabel',
                    'ev' => 123,
                    'dh' => 'example.com',
                    't'  => 'event',
                ]
            ], $parameterList);
        });
        /** @var CMService_GoogleAnalytics_MeasurementProtocol_Client $client */
        $client = $clientMock->newInstance(['prop1']);

        $client->trackEvent([
            'eventCategory' => 'MyCategory',
            'eventAction'   => 'MyAction',
            'eventLabel'    => 'MyLabel',
            'eventValue'    => 123,
            'hostname'      => 'example.com',
        ]);
        $this->assertSame(1, $submitRequestMock->getCallCount());
    }

    public function testTrackPageView() {
        $clientMock = $this->mockClass('CMService_GoogleAnalytics_MeasurementProtocol_Client');
        $submitRequestMock = $clientMock->mockMethod('_queueHit')->set(function ($parameterList) {
            $this->assertSame([
                'propertyId'    => 'prop1',
                'parameterList' => [
                    'dp'  => '/foo',
                    'dh'  => 'example.com',
                    'cid' => 'abc',
                    'uid' => 123,
                    't'   => 'pageview',
                ]
            ], $parameterList);
        });
        /** @var CMService_GoogleAnalytics_MeasurementProtocol_Client $client */
        $client = $clientMock->newInstance(['prop1']);

        $client->trackPageView([
            'page'     => '/foo',
            'hostname' => 'example.com',
            'clientId' => 'abc',
            'userId'   => 123,
        ]);
        $this->assertSame(1, $submitRequestMock->getCallCount());
    }

    public function testTrackEventInvalidParam() {
        $clientMock = $this->mockClass('CMService_GoogleAnalytics_MeasurementProtocol_Client');
        /** @var CMService_GoogleAnalytics_MeasurementProtocol_Client $client */
        $client = $clientMock->newInstance(['prop1']);

        $exception = $this->catchException(function () use ($client) {
            $client->trackEvent([
                'foo' => 12,
            ]);
        });

        $this->assertInstanceOf('CM_Exception', $exception);
        /** @var CM_Exception $exception */
        $this->assertSame('Unknown parameter', $exception->getMessage());
        $this->assertSame(['name' => 'foo'], $exception->getMetaInfo());
    }

    public function testTrackEventFloatValue() {
        $clientMock = $this->mockClass('CMService_GoogleAnalytics_MeasurementProtocol_Client');
        /** @var CMService_GoogleAnalytics_MeasurementProtocol_Client $client */
        $client = $clientMock->newInstance(['prop1']);

        $exception = $this->catchException(function () use ($client) {
            $client->trackEvent([
                'eventCategory' => 'MyCategory',
                'eventAction'   => 'MyAction',
                'eventLabel'    => 'MyLabel',
                'eventValue'    => 12.23,
            ]);
        });

        $this->assertInstanceOf('CM_Exception', $exception);
        /** @var CM_Exception $exception */
        $this->assertSame('Value for the parameter did not pass validation', $exception->getMessage());
        $this->assertSame(
            [
                'value'     => 12.23,
                'parameter' => 'ev'
            ],
            $exception->getMetaInfo()
        );
    }

    public function testTrackEventNegativeValue() {
        $clientMock = $this->mockClass('CMService_GoogleAnalytics_MeasurementProtocol_Client');
        /** @var CMService_GoogleAnalytics_MeasurementProtocol_Client $client */
        $client = $clientMock->newInstance(['prop1']);

        $exception = $this->catchException(function () use ($client) {
            $client->trackEvent([
                'eventCategory' => 'MyCategory',
                'eventAction'   => 'MyAction',
                'eventLabel'    => 'MyLabel',
                'eventValue'    => -12,
            ]);
        });

        $this->assertInstanceOf('CM_Exception', $exception);
        /** @var CM_Exception $exception */
        $this->assertSame('Value for the parameter did not pass validation', $exception->getMessage());
        $this->assertSame(
            [
                'value'     => -12,
                'parameter' => 'ev',
            ],
            $exception->getMetaInfo()
        );
    }

    public function testTrackEventParamForWrongHitType() {
        $clientMock = $this->mockClass('CMService_GoogleAnalytics_MeasurementProtocol_Client');
        /** @var CMService_GoogleAnalytics_MeasurementProtocol_Client $client */
        $client = $clientMock->newInstance(['prop1']);

        $exception = $this->catchException(function () use ($client) {
            $client->trackEvent([
                'exd' => 'My exception',
            ]);
        });

        $this->assertInstanceOf('CM_Exception', $exception);
        /** @var CM_Exception $exception */
        $this->assertSame('Unexpected parameter for the hitType.', $exception->getMessage());
        $this->assertSame(
            [
                'name'    => 'exd',
                'hitType' => 'event',
            ],
            $exception->getMetaInfo()
        );
    }

    public function testGetRandomClientId() {
        $client = new CMService_GoogleAnalytics_MeasurementProtocol_Client('foo');

        $this->assertInternalType('string', $client->getRandomClientId());
        $this->assertGreaterThan(5, strlen($client->getRandomClientId()));
        $this->assertNotEquals($client->getRandomClientId(), $client->getRandomClientId());
    }
}

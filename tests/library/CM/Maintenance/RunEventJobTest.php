<?php

class CM_Maintenance_RunEventJobTest extends CMTest_TestCase {

    public function testExecute() {
        $this->_setupQueueMock();
        $serviceManager = $this->getServiceManager();
        $job1 = new CM_Maintenance_RunEventJob(CM_Params::factory(['event' => 'foo', 'lastRuntime' => null], false));
        $job2 = new CM_Maintenance_RunEventJob(CM_Params::factory(['event' => 'bar', 'lastRuntime' => null], false));

        $job1->setServiceManager($this->getServiceManager());
        $job2->setServiceManager($this->getServiceManager());

        /** @var CM_Maintenance_Service|\Mocka\AbstractClassTrait $maintenance */
        $maintenance = $this->mockClass(CM_Maintenance_Service::class)->newInstanceWithoutConstructor();
        $mockHandleClockworkEventResult = $maintenance->mockMethod('handleClockworkEventResult')
            ->at(0, function ($eventName, CM_Clockwork_Event_Result $result) {
                $this->assertSame('foo', $eventName);
                $this->assertSame(true, $result->isSuccessful());
            })
            ->at(1, function ($eventName, CM_Clockwork_Event_Result $result) {
                $this->assertSame('bar', $eventName);
                $this->assertSame(false, $result->isSuccessful());
            });
        $serviceManager->replaceInstance('maintenance', $maintenance);

        $fooCounter = 0;
        $maintenance->registerEvent('foo', '1 second', function () use (&$fooCounter) {
            $fooCounter++;
        });
        $maintenance->registerEvent('bar', '1 second', function () {
            throw new Exception('Foo');
        });

        $this->assertSame(0, $fooCounter);
        $this->assertSame(0, $mockHandleClockworkEventResult->getCallCount());
        $serviceManager->getJobQueue()->runSync($job1);
        $this->assertSame(1, $fooCounter);
        $this->assertSame(1, $mockHandleClockworkEventResult->getCallCount());

        $exception = $this->catchException(function () use ($job2) {
            $this->getServiceManager()->getJobQueue()->runSync($job2);
        });
        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertSame('Foo', $exception->getMessage());
        $this->assertSame(2, $mockHandleClockworkEventResult->getCallCount());
    }
}

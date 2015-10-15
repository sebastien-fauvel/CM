<?php

class CM_Model_StreamChannel_VideoTest extends CMTest_TestCase {

    public function testCreate() {
        /** @var CM_Model_StreamChannel_Video $channel */
        $channel = CM_Model_StreamChannel_Video::createStatic(array(
            'key'            => 'foo',
            'width'          => 100,
            'height'         => 200,
            'serverId'       => 1,
            'thumbnailCount' => 2,
            'adapterType'    => 1,
        ));
        $this->assertInstanceOf('CM_Model_StreamChannel_Video', $channel);
        $this->assertSame(100, $channel->getWidth());
        $this->assertSame(200, $channel->getHeight());
        $this->assertSame('foo', $channel->getKey());
        $this->assertSame(1, $channel->getAdapterType());
        $this->assertSame(2, $channel->getThumbnailCount());
    }

    public function testCreateWithoutServerId() {
        try {
            CM_Model_StreamChannel_Video::createStatic(array(
                'key'            => 'bar',
                'width'          => 100,
                'height'         => 200,
                'serverId'       => null,
                'thumbnailCount' => 2,
                'adapterType'    => 1,
            ));
            $this->fail('Can create streamChannel without serverId');
        } catch (CM_Exception $ex) {
            $this->assertContains("Column 'serverId' cannot be null", $ex->getMessage());
        }
    }

    public function testGetStreamPublish() {
        /** @var CM_Model_StreamChannel_Video $streamChannel */
        $streamChannel = CMTest_TH::createStreamChannel();
        try {
            $streamChannel->getStreamPublish();
            $this->fail();
        } catch (CM_Exception_Invalid $ex) {
            $this->assertContains('has no StreamPublish.', $ex->getMessage());
        }
        $streamPublish = CMTest_TH::createStreamPublish(null, $streamChannel);
        $this->assertEquals($streamPublish, $streamChannel->getStreamPublish());
    }

    public function testHasStreamPublish() {
        /** @var CM_Model_StreamChannel_Video $streamChannel */
        $streamChannel = CMTest_TH::createStreamChannel();
        $this->assertFalse($streamChannel->hasStreamPublish());
        CMTest_TH::createStreamPublish(null, $streamChannel);
        $this->assertTrue($streamChannel->hasStreamPublish());
    }

    public function testThumbnailCount() {
        /** @var CM_Model_StreamChannel_Video $streamChannel */
        $streamChannel = CMTest_TH::createStreamChannel();
        $streamChannel->setThumbnailCount(15);
        $this->assertSame(15, $streamChannel->getThumbnailCount());
    }

    public function testOnDelete() {
        $streamChannel = CMTest_TH::createStreamChannel();
        $streamChannel->delete();
        try {
            new CM_Model_StreamChannel_Video($streamChannel->getId());
        } catch (CM_Exception_Nonexistent $ex) {
            $this->assertTrue(true);
        }
        $this->assertNotRow('cm_streamChannel_media', array('id' => $streamChannel->getId()));
    }

    public function testOnUnpublish() {
        $streamChannel = CMTest_TH::createStreamChannel();
        $streamPublish = CMTest_TH::createStreamPublish(null, $streamChannel);
        $this->assertNull(CM_Model_StreamChannelArchive_Media::findById($streamChannel->getId()));

        $streamChannel->onUnpublish($streamPublish);
        $this->assertInstanceOf('CM_Model_StreamChannelArchive_Media', CM_Model_StreamChannelArchive_Media::findById($streamChannel->getId()));

        $streamChannel->onUnpublish($streamPublish);
    }

    public function testOnUnpublishDelete() {
        $streamChannel = CMTest_TH::createStreamChannel();
        $streamPublish = CMTest_TH::createStreamPublish(null, $streamChannel);
        try {
            $streamChannel->onUnpublish($streamPublish);
            new CM_Model_StreamChannelArchive_Media($streamChannel->getId());
            $streamPublish->delete();
        } catch (CM_Exception_Nonexistent $ex) {
            $this->fail('Could not delete CM_Model_Stream_Publish.');
        }
    }

    public function testGetThumbnails() {
        /** @var CM_Model_StreamChannel_Video $streamChannel */
        $streamChannel = CMTest_TH::createStreamChannel();
        CMTest_TH::createStreamPublish(null, $streamChannel);
        $this->assertSame(array(), $streamChannel->getThumbnails()->getItems());
        $streamChannel->setThumbnailCount(2);
        $thumb1 = new CM_File_UserContent('streamChannels',
            $streamChannel->getId() . '-' . $streamChannel->getHash() . '-thumbs/1.png', $streamChannel->getId());
        $thumb2 = new CM_File_UserContent('streamChannels',
            $streamChannel->getId() . '-' . $streamChannel->getHash() . '-thumbs/2.png', $streamChannel->getId());
        $this->assertEquals(array($thumb1, $thumb2), $streamChannel->getThumbnails()->getItems());
    }

    public function testGetThumbnail() {
        /** @var CM_Model_StreamChannel_Video $streamChannel */
        $streamChannel = CMTest_TH::createStreamChannel();
        CMTest_TH::createStreamPublish(null, $streamChannel);
        $thumbnail = $streamChannel->getThumbnail(3);
        $this->assertInstanceOf('CM_File_UserContent', $thumbnail);
        $this->assertSame(
            'streamChannels/' . $streamChannel->getId() . '/' . $streamChannel->getId() . '-' . $streamChannel->getHash() . '-thumbs/3.png',
            $thumbnail->getPathRelative());
    }
}


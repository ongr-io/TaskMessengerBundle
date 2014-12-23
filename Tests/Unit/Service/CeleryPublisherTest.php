<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\TaskMessengerBundle\Tests\Unit\Service;

use ONGR\TaskMessengerBundle\Document\SyncTask;
use ONGR\TaskMessengerBundle\Service\CeleryPublisher;

class CeleryPublisherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Data provider for testMethodExists.
     *
     * @return array
     */
    public function getTestMethodExistsData()
    {
        $out = [];

        // Case #0: logger.
        $loggerMock = $this->getMockBuilder('Psr\Log\LoggerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $out[] = ['setLogger', $loggerMock];

        // Case #1: enabled.
        $out[] = ['setEnabled', true];

        return $out;
    }

    /**
     * Test to check whether method exists.
     *
     * @param string $method
     * @param string $parameter
     *
     * @dataProvider getTestMethodExistsData
     */
    public function testMethodExists($method, $parameter)
    {
        $publisher = $this->getPublisher();

        $this->assertTrue(method_exists($publisher, $method));
        $publisher->{$method}($parameter);
    }

    /**
     * Helper method to get CeleryPublisher object.
     *
     * @return CeleryPublisher
     */
    protected function getPublisher()
    {
        $connectionFactory = $this->getMockBuilder('ONGR\TaskMessengerBundle\Service\ConnectionFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $environment = 'dummy-environment';

        $celeryPublisher = new CeleryPublisher($connectionFactory, $environment);

        return $celeryPublisher;
    }

    /**
     * Tests if task environment overrides publisher's environment.
     */
    public function testGetEnvironment()
    {
        $connectionFactory = $this->getMockBuilder('ONGR\TaskMessengerBundle\Service\ConnectionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $environment = 'dummy-environment';

        $publisher = new CeleryPublisher($connectionFactory, $environment);

        $task = new SyncTask(SyncTask::SYNC_TASK_BROADCAST);
        $task->setName('ongr:sync:download');
        $expectedEnvironment = 'test';
        $task->setEnvironment($expectedEnvironment);

        $reflectionMethod = $this->getProtectedMethod($publisher, 'getEnvironment');

        $this->assertEquals($expectedEnvironment, $reflectionMethod->invoke($publisher, $task));
    }

    /**
     * Tests AMQP connection factory exception handling.
     *
     * @expectedException \ONGR\TaskMessengerBundle\Service\Exception\PublisherConnectionException
     */
    public function testConnectionFactoryException()
    {
        $exception = $this->getMockBuilder('\PhpAmqpLib\Exception\AMQPProtocolConnectionException')
            ->disableOriginalConstructor()
            ->getMock();

        $connectionFactory = $this->getMockBuilder('ONGR\TaskMessengerBundle\Service\ConnectionFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $connectionFactory
            ->expects($this->once())
            ->method('create')
            ->willThrowException($exception);

        $environment = 'dummy-environment';

        $publisher = new CeleryPublisher($connectionFactory, $environment);

        $task = new SyncTask(SyncTask::SYNC_TASK_BROADCAST);
        $task->setName('ongr:sync:download');
        $expectedEnvironment = 'test';
        $task->setEnvironment($expectedEnvironment);

        $publisher->publish($task);

    }
    /**
     * Test to check if disabled publisher do not publish.
     */
    public function testDisabledPublisher()
    {
        $publisher = $this->getMockBuilder('ONGR\TaskMessengerBundle\Service\CeleryPublisher')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $publisher->setEnabled(false);
        $task = new SyncTask(SyncTask::SYNC_TASK_BROADCAST);
        $task->setName('ongr:sync:download');
        $publisher->expects($this->never())->method('send');

        $publisher->publish($task);
    }

    /**
     * Method helper to get method reflection.
     *
     * @param object $object
     * @param string $name
     *
     * @return \ReflectionMethod
     */
    private function getProtectedMethod($object, $name)
    {
        $class = new \ReflectionClass($object);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }
}

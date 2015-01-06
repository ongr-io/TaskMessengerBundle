<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\TaskMessengerBundle\Tests\Functional\Service;

use ONGR\TaskMessengerBundle\Document\SyncTask;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TaskPublisherTest extends WebTestCase
{
    /**
     * Dummy test for verifying that TaskPublisher works with configured brokers.
     */
    public function testPublish()
    {
        $client = self::createClient();
        $publisher = $client->getContainer()->get('ongr_task_messenger.task_publisher.default');

        $task = new SyncTask(SyncTask::SYNC_TASK_PRESERVEHOST);
        $task->setName('task_foo');
        $task->setCommand('command_foo');
        $publisher->publish($task);
    }
}

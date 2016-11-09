<?php
/**
 * Created by PhpStorm.
 * User: daniil
 * Date: 02.11.16
 * Time: 11:07
 */

namespace tests;

use daemon\DaemonProvider;

/**
 * Class DaemonProviderTest
 *
 * @package tests
 */
class DaemonProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        try {
            $this->getMockForAbstractClass('daemon\DaemonProvider');
            $result = '';
        } catch (\Exception $ex) {
            $result = $ex->getMessage();
        }
        $this->assertContains('PID_FILE', $result, 'Check contain PID_FILE in exception');
        $this->assertContains('LOG_FILE', $result, 'Check contain LOG_FILE in exception');
    }

    public function testLoop()
    {
        $stub = $this->getMockForAbstractClass(
            'daemon\DaemonProvider',
            [],
            '',
            false
        );
        $stub->expects($this->any())
            ->method('loop')
            ->will($this->returnCallback(function () {
                return true;
            }));
        /** @var DaemonProvider $stub */
        $this->assertTrue($stub->loop(), 'Check loop abstract method');
    }
}

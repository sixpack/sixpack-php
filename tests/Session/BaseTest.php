<?php

namespace SeatGeek\Sixpack\Test\Session;

use SeatGeek\Sixpack\Session\Base;
use \PHPUnit_Framework_TestCase;

class BaseTest extends PHPUnit_Framework_TestCase
{
    /**
     * Verify the mocked method call to sendRequest, and that the return value
     * is the right class
     */
    public function testParticipateValidArgs()
    {
        $base = $this->getMockBuilder('SeatGeek\Sixpack\Session\Base')
            ->disableOriginalConstructor()
            ->setMethods(['sendRequest'])
            ->getMock();

        $mockedResponse = [
            'raw response',
            ['meta', 'data']
        ];
        $base->expects($this->once())
            ->method('sendRequest')
            ->with(
                'participate',
                [
                    'experiment' => 'the',
                    'alternatives' => ['the', 'alternative'],
                    'traffic_fraction' => '0.42'
                ]
            )->will($this->returnValue($mockedResponse));

        $return = $base->participate('the', ['the', 'alternative'], 0.42);
        $this->assertInstanceOf('SeatGeek\Sixpack\Response\Participation', $return);
    }

    /**
     * testParticipateTooFewAlternatives
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage At least two alternatives are required
     */
    public function testParticipateTooFewAlternatives()
    {
        $base = $this->getMockBuilder('SeatGeek\Sixpack\Session\Base')
            ->disableOriginalConstructor()
            ->setMethods(['sendRequest'])
            ->getMock();

        $base->participate('one', ['one']);
    }
}

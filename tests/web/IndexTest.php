<?php

namespace Tests\Web;

class IndexTest extends TestCase
{
    /**
     * Test that the dashboard shows localhost status
     */
    public function testGetIndex()
    {
        $response = $this->runApp('GET', '/');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('localhost status', (string)$response->getBody());
    }
}

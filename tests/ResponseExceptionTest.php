<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use SDF\Response;
use SDF\HttpResponseException;
use SDF\HeadersSendException;

class ResponseExceptionTest extends TestCase
{
    public function testMissingHttpCodeThrows(): void
    {
        $this->expectException(HttpResponseException::class);
        $r = new class extends Response {
            // expose protected sendHeaders for test
            public function callSendHeadersPublic(): void
            {
                $this->sendHeaders();
            }
        };

        $r->callSendHeadersPublic();
    }

    public function testHeadersAlreadySentThrows(): void
    {
        $this->expectException(HeadersSendException::class);
        $r = new class extends Response {
            protected function headersAlreadySent(): bool
            {
                return true;
            }
            public function callSendHeadersPublic(): void
            {
                // ensure http code present
                $this->setHttpCode(200);
                $this->sendHeaders();
            }
        };

        $r->callSendHeadersPublic();
    }
}

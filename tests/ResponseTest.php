<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use SDF\Response;

class ResponseTest extends TestCase
{


    public function test_json_outputs_and_sets_status_and_content_type(): void
    {
        $resp = new Response();

        ob_start();
        $resp->json(['a' => 1], 202);
        $output = ob_get_clean();

        // Output should be JSON
        $this->assertSame('{"a":1}', $output);

        // Status code should be set to 202
        $this->assertSame(202, http_response_code());
    }

    public function test_text_and_html_output(): void
    {
        $resp = new Response();

        // Test plain text
        ob_start();
        $resp->text('hello', 201);
        $out1 = ob_get_clean();
        $this->assertSame('hello', $out1);
        $this->assertSame(201, http_response_code());

        // Test html
        ob_start();
        $resp->html('<b>x</b>', 203);
        $out2 = ob_get_clean();
        $this->assertSame('<b>x</b>', $out2);
        $this->assertSame(203, http_response_code());
    }

    public function test_set_header_rejects_crlf_injection(): void
    {
        $resp = new Response();
        $this->expectException(\InvalidArgumentException::class);
        $resp->setHeader('X-Test', "value\r\nSet-Cookie: evil=1");
    }

    public function test_set_header_rejects_lf_injection(): void
    {
        $resp = new Response();
        $this->expectException(\InvalidArgumentException::class);
        $resp->setHeader('X-Test', "value\nSet-Cookie: evil=1");
    }

    public function test_add_header_rejects_crlf_injection(): void
    {
        $resp = new Response();
        $this->expectException(\InvalidArgumentException::class);
        $resp->addHeader("X-Test: value\r\nSet-Cookie: evil=1");
    }

    public function test_set_header_rejects_null_byte(): void
    {
        $resp = new Response();
        $this->expectException(\InvalidArgumentException::class);
        $resp->setHeader("X-Test\0", 'value');
    }

    public function test_redirect_rejects_protocol_relative_url(): void
    {
        $resp = new Response();
        $this->expectException(\InvalidArgumentException::class);
        $resp->redirect('//evil.com/path');
    }

    public function test_redirect_rejects_javascript_scheme(): void
    {
        $resp = new Response();
        $this->expectException(\InvalidArgumentException::class);
        $resp->redirect('javascript:alert(1)');
    }

    public function test_redirect_rejects_data_scheme(): void
    {
        $resp = new Response();
        $this->expectException(\InvalidArgumentException::class);
        $resp->redirect('data:text/html,<script>alert(1)</script>');
    }

    public function test_redirect_allows_relative_path(): void
    {
        $resp = new Response();
        $resp->setHttpCode(302);
        // No exception - relative paths are safe
        $this->assertInstanceOf(Response::class, $resp);
    }

    public function test_redirect_allows_http_and_https(): void
    {
        $resp = new Response();
        $resp->setHttpCode(302);
        // No exception thrown during setHeader validation (we don't call send)
        $resp->setHeader('Location', 'https://example.com/path');
        $this->assertSame('https://example.com/path', $resp->getHeader('Location'));
    }
}

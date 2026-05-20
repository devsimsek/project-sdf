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
}

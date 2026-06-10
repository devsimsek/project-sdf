<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use SDF\Http\ServerRequest;
use SDF\Http\Stream;
use SDF\Http\Uri;
use SDF\Http\Response;
use SDF\Http\UploadedFile;

/**
 * Tests for PSR-7 HTTP message implementations.
 *
 * @covers \SDF\Http\Stream
 * @covers \SDF\Http\Uri
 * @covers \SDF\Http\UploadedFile
 * @covers \SDF\Http\Response
 * @covers \SDF\Http\ServerRequest
 */
class HttpMessageTest extends TestCase
{
    // ─── Stream ───────────────────────────────────────────────────

    public function test_stream_from_string(): void
    {
        $s = new Stream('hello world');
        $this->assertSame('hello world', (string) $s);
        $this->assertSame(11, $s->getSize());
    }

    public function test_stream_tell_seek_eof(): void
    {
        $s = new Stream('abcdef');
        $this->assertSame(0, $s->tell());
        $s->seek(3);
        $this->assertSame(3, $s->tell());
        $this->assertSame('def', $s->read(3));
        // Read past end to trigger EOF flag on php://temp
        $s->read(1);
        $this->assertTrue($s->eof());
    }

    public function test_stream_is_readable_writable(): void
    {
        $s = new Stream('test');
        $this->assertTrue($s->isReadable());
        $this->assertTrue($s->isWritable());
    }

    public function test_stream_write(): void
    {
        $s = new Stream('', 'w+');
        $s->write('hello');
        $s->rewind();
        $this->assertSame('hello', $s->getContents());
    }

    public function test_stream_get_metadata(): void
    {
        $s = new Stream('data');
        $meta = $s->getMetadata();
        $this->assertArrayHasKey('stream_type', $meta);
        $this->assertNotNull($s->getMetadata('stream_type'));
    }

    public function test_stream_close_and_detach(): void
    {
        $s = new Stream('data');
        $s->close();
        $this->assertNull($s->getSize());
        $this->assertFalse($s->isReadable());
    }

    public function test_stream_detach_returns_resource(): void
    {
        $s = new Stream('data');
        $res = $s->detach();
        $this->assertIsResource($res);
        fclose($res);
    }

    // ─── Uri ──────────────────────────────────────────────────────

    public function test_uri_parse_full(): void
    {
        $u = new Uri('https://user:pass@example.com:8080/path/to?foo=bar#frag');
        $this->assertSame('https', $u->getScheme());
        $this->assertSame('user:pass', $u->getUserInfo());
        $this->assertSame('example.com', $u->getHost());
        $this->assertSame(8080, $u->getPort());
        $this->assertSame('/path/to', $u->getPath());
        $this->assertSame('foo=bar', $u->getQuery());
        $this->assertSame('frag', $u->getFragment());
        $this->assertSame('user:pass@example.com:8080', $u->getAuthority());
    }

    public function test_uri_immutable_with_scheme(): void
    {
        $u = new Uri('http://example.com');
        $u2 = $u->withScheme('https');
        $this->assertNotSame($u, $u2);
        $this->assertSame('https', $u2->getScheme());
        $this->assertSame('http', $u->getScheme());
    }

    public function test_uri_immutable_with_host(): void
    {
        $u = new Uri('http://a.com');
        $u2 = $u->withHost('b.com');
        $this->assertSame('b.com', $u2->getHost());
        $this->assertSame('a.com', $u->getHost());
    }

    public function test_uri_immutable_with_port(): void
    {
        $u = new Uri('http://example.com');
        $u2 = $u->withPort(9090);
        $this->assertSame(9090, $u2->getPort());
        $this->assertNull($u2->withPort(null)->getPort());
    }

    public function test_uri_immutable_with_path(): void
    {
        $u = new Uri('http://example.com');
        $u2 = $u->withPath('/new');
        $this->assertSame('/new', $u2->getPath());
    }

    public function test_uri_immutable_with_query(): void
    {
        $u = new Uri('http://example.com');
        $u2 = $u->withQuery('key=val');
        $this->assertSame('key=val', $u2->getQuery());
    }

    public function test_uri_immutable_with_fragment(): void
    {
        $u = new Uri('http://example.com');
        $u2 = $u->withFragment('sec');
        $this->assertSame('sec', $u2->getFragment());
    }

    public function test_uri_standard_port_not_in_authority(): void
    {
        $u = new Uri('https://example.com:443/path');
        $this->assertNull($u->getPort());
        $this->assertSame('example.com', $u->getAuthority());
    }

    public function test_uri___toString(): void
    {
        $u = new Uri('https://user@example.com:8080/p?q=1#h');
        $this->assertSame('https://user@example.com:8080/p?q=1#h', (string) $u);
    }

    // ─── UploadedFile ──────────────────────────────────────────────

    public function test_uploaded_file_basics(): void
    {
        $file = new UploadedFile(__FILE__, \UPLOAD_ERR_OK, 'test.php', 'text/plain', 123);
        $this->assertSame('test.php', $file->getClientFilename());
        $this->assertSame('text/plain', $file->getClientMediaType());
        $this->assertSame(123, $file->getSize());
        $this->assertSame(\UPLOAD_ERR_OK, $file->getError());
    }

    public function test_uploaded_file_move(): void
    {
        $tmpDir = sys_get_temp_dir();
        $tmpFile = tempnam($tmpDir, 'psr7_test_');
        file_put_contents($tmpFile, 'uploaded content');

        $file = new UploadedFile($tmpFile, \UPLOAD_ERR_OK);
        $dest = $tmpDir . '/psr7_moved_' . uniqid();
        $file->moveTo($dest);
        $this->assertFileExists($dest);
        $this->assertSame('uploaded content', file_get_contents($dest));
        unlink($dest);
    }

    public function test_uploaded_file_get_stream(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'psr7_');
        file_put_contents($tmpFile, 'stream content');

        $file = new UploadedFile($tmpFile, \UPLOAD_ERR_OK);
        $stream = $file->getStream();
        $this->assertSame('stream content', (string) $stream);
        unlink($tmpFile);
    }

    // ─── Response ─────────────────────────────────────────────────

    public function test_response_status_code(): void
    {
        $r = new Response(200);
        $this->assertSame(200, $r->getStatusCode());
        $this->assertSame('OK', $r->getReasonPhrase());
    }

    public function test_response_custom_reason_phrase(): void
    {
        $r = new Response(299, [], null, '1.1', 'Custom');
        $this->assertSame('Custom', $r->getReasonPhrase());
    }

    public function test_response_with_status(): void
    {
        $r = new Response(200);
        $r2 = $r->withStatus(404);
        $this->assertNotSame($r, $r2);
        $this->assertSame(404, $r2->getStatusCode());
        $this->assertSame('Not Found', $r2->getReasonPhrase());
    }

    public function test_response_with_custom_reason(): void
    {
        $r = new Response(200);
        $r2 = $r->withStatus(418, "I'm a teapot");
        $this->assertSame("I'm a teapot", $r2->getReasonPhrase());
    }

    public function test_response_headers(): void
    {
        $r = new Response(200, ['X-Foo' => 'bar']);
        $this->assertSame(['bar'], $r->getHeader('X-Foo'));
        $this->assertTrue($r->hasHeader('X-Foo'));
        $this->assertFalse($r->hasHeader('X-Missing'));
        $this->assertSame('bar', $r->getHeaderLine('X-Foo'));
    }

    public function test_response_with_header(): void
    {
        $r = new Response();
        $r2 = $r->withHeader('Content-Type', 'application/json');
        $this->assertNotSame($r, $r2);
        $this->assertSame(['application/json'], $r2->getHeader('Content-Type'));
        $this->assertFalse($r->hasHeader('Content-Type'));
    }

    public function test_response_with_added_header(): void
    {
        $r = new Response(200, ['Set-Cookie' => 'a=1']);
        $r2 = $r->withAddedHeader('Set-Cookie', 'b=2');
        $this->assertSame(['a=1', 'b=2'], $r2->getHeader('Set-Cookie'));
    }

    public function test_response_without_header(): void
    {
        $r = new Response(200, ['X-Foo' => 'bar']);
        $r2 = $r->withoutHeader('X-Foo');
        $this->assertFalse($r2->hasHeader('X-Foo'));
        $this->assertTrue($r->hasHeader('X-Foo'));
    }

    public function test_response_body(): void
    {
        $r = new Response(200, [], new Stream('body content'));
        $this->assertSame('body content', (string) $r->getBody());
    }

    public function test_response_protocol_version(): void
    {
        $r = new Response(200, [], null, '2.0');
        $this->assertSame('2.0', $r->getProtocolVersion());
        $r2 = $r->withProtocolVersion('1.0');
        $this->assertSame('1.0', $r2->getProtocolVersion());
    }

    // ─── ServerRequest ────────────────────────────────────────────

    public function test_server_request_method_and_uri(): void
    {
        $r = new ServerRequest('POST', 'http://example.com/foo');
        $this->assertSame('POST', $r->getMethod());
        $this->assertSame('example.com', $r->getUri()->getHost());
        $this->assertSame('/foo', $r->getUri()->getPath());
    }

    public function test_server_request_with_method(): void
    {
        $r = new ServerRequest('GET');
        $r2 = $r->withMethod('DELETE');
        $this->assertSame('DELETE', $r2->getMethod());
        $this->assertSame('GET', $r->getMethod());
    }

    public function test_server_request_with_uri(): void
    {
        $r = new ServerRequest('GET', 'http://a.com');
        $u = new Uri('http://b.com');
        $r2 = $r->withUri($u);
        $this->assertSame('b.com', $r2->getUri()->getHost());
        $this->assertSame('a.com', $r->getUri()->getHost());
    }

    public function test_server_request_request_target(): void
    {
        $r = new ServerRequest('GET', 'http://example.com/path?q=1');
        $this->assertSame('/path?q=1', $r->getRequestTarget());

        $r2 = $r->withRequestTarget('*');
        $this->assertSame('*', $r2->getRequestTarget());
        $this->assertSame('/path?q=1', $r->getRequestTarget());
    }

    public function test_server_request_headers(): void
    {
        $r = new ServerRequest('GET', '', ['X-Custom' => ['val']]);
        $this->assertSame(['val'], $r->getHeader('X-Custom'));
        $this->assertSame('val', $r->getHeaderLine('X-Custom'));
    }

    public function test_server_request_immutable_headers(): void
    {
        $r = new ServerRequest();
        $r2 = $r->withHeader('X-Foo', 'bar');
        $r3 = $r2->withAddedHeader('X-Foo', 'baz');
        $this->assertFalse($r->hasHeader('X-Foo'));
        $this->assertSame(['bar'], $r2->getHeader('X-Foo'));
        $this->assertSame(['bar', 'baz'], $r3->getHeader('X-Foo'));

        $r4 = $r3->withoutHeader('X-Foo');
        $this->assertFalse($r4->hasHeader('X-Foo'));
        $this->assertTrue($r3->hasHeader('X-Foo'));
    }

    public function test_server_request_attributes(): void
    {
        $r = new ServerRequest();
        $r2 = $r->withAttribute('role', 'admin');
        $this->assertSame('admin', $r2->getAttribute('role'));
        $this->assertNull($r->getAttribute('role'));
        $this->assertSame('default', $r->getAttribute('missing', 'default'));

        $r3 = $r2->withoutAttribute('role');
        $this->assertNull($r3->getAttribute('role'));
    }

    public function test_server_request_server_params(): void
    {
        $r = new ServerRequest('GET', '', [], null, '1.1', ['REMOTE_ADDR' => '127.0.0.1']);
        $this->assertSame('127.0.0.1', $r->getServerParams()['REMOTE_ADDR']);
    }

    public function test_server_request_parsed_body(): void
    {
        $r = new ServerRequest('POST');
        $r2 = $r->withParsedBody(['key' => 'value']);
        $this->assertSame(['key' => 'value'], $r2->getParsedBody());
        $this->assertNull($r->getParsedBody());
    }

    public function test_server_request_query_params(): void
    {
        $r = new ServerRequest('GET');
        $r2 = $r->withQueryParams(['page' => 2]);
        $this->assertSame(['page' => 2], $r2->getQueryParams());
    }

    public function test_server_request_cookie_params(): void
    {
        $r = new ServerRequest('GET');
        $r2 = $r->withCookieParams(['session' => 'abc']);
        $this->assertSame(['session' => 'abc'], $r2->getCookieParams());
    }

    public function test_server_request_body(): void
    {
        $r = new ServerRequest('PUT', '', [], 'raw body');
        $this->assertSame('raw body', (string) $r->getBody());
    }

    public function test_server_request_protocol_version(): void
    {
        $r = new ServerRequest('GET', '', [], null, '2.0');
        $this->assertSame('2.0', $r->getProtocolVersion());
    }

    public function test_server_request_from_globals(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'test.dev';
        $_SERVER['REQUEST_URI'] = '/test?q=1';
        $_GET = ['q' => '1'];
        $_POST = [];
        $_COOKIE = [];
        $_FILES = [];

        $r = ServerRequest::fromGlobals();
        $this->assertSame('GET', $r->getMethod());
        $this->assertSame('test.dev', $r->getUri()->getHost());
        $this->assertSame('/test', $r->getUri()->getPath());
        $this->assertSame(['q' => '1'], $r->getQueryParams());
    }

    public function test_server_request_uploaded_files(): void
    {
        $_FILES = [
            'avatar' => [
                'name' => 'photo.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/phpXXX',
                'error' => \UPLOAD_ERR_OK,
                'size' => 12345,
            ],
        ];

        $r = ServerRequest::fromGlobals();
        $files = $r->getUploadedFiles();
        $this->assertArrayHasKey('avatar', $files);
        $this->assertInstanceOf(UploadedFile::class, $files['avatar']);
        $this->assertSame('photo.jpg', $files['avatar']->getClientFilename());
        $this->assertSame(12345, $files['avatar']->getSize());
    }

    // ─── Legacy bridge methods ────────────────────────────────────

    public function test_legacy_request_to_psr(): void
    {
        $req = new \SDF\Request();
        $psr = $req->toPsr();
        $this->assertInstanceOf(\Psr\Http\Message\ServerRequestInterface::class, $psr);
    }

    public function test_legacy_request_from_psr(): void
    {
        $psr = new ServerRequest('PUT', 'http://example.com/api', ['X-Internal' => 'true'], 'body');
        $psr = $psr
            ->withQueryParams(['id' => 5])
            ->withParsedBody(['name' => 'test']);

        $req = \SDF\Request::fromPsr($psr);
        $this->assertInstanceOf(\SDF\Request::class, $req);
        $this->assertSame('PUT', $_SERVER['REQUEST_METHOD']);
        $this->assertSame(['id' => 5], $_GET);
        $this->assertSame(['name' => 'test'], $_POST);
    }

    public function test_legacy_response_to_psr(): void
    {
        $res = new \SDF\Response();
        $res->setHttpCode(201);
        $res->setContent(json_encode(['ok' => true]));
        $res->setHeader('Content-Type', 'application/json');

        $psr = $res->toPsr();
        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $psr);
        $this->assertSame(201, $psr->getStatusCode());
        $this->assertSame('application/json', $psr->getHeaderLine('Content-Type'));
        $this->assertSame(json_encode(['ok' => true]), (string) $psr->getBody());
    }

    public function test_legacy_response_from_psr(): void
    {
        $psr = new Response(404, ['X-Error' => 'not_found'], new Stream('oops'));
        $res = \SDF\Response::fromPsr($psr);
        $this->assertSame(404, $res->statusCode());
        $this->assertSame('oops', $res->getContent());
    }
}

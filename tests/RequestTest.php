<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use SDF\Request;

class RequestTest extends TestCase
{
    protected function setUp(): void
    {
        // Ensure superglobals are clean before each test
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        $_SERVER = [];
        $_SESSION = [];
        $_COOKIE = [];
        $_FILES = [];
    }

    public function test_get_post_request_accessors(): void
    {
        $_GET['foo'] = 'g';
        $_POST['bar'] = 'p';
        $_REQUEST['baz'] = 'r';

        $req = new Request();

        $this->assertSame('g', $req->get('foo'));
        $this->assertSame('p', $req->post('bar'));
        $this->assertSame('r', $req->request('baz'));
        $this->assertNull($req->get('missing'));
        $this->assertSame('d', $req->get('missing', 'd'));
    }

    public function test_server_session_cookie_file_and_header_helpers(): void
    {
        $_SERVER['SOME_KEY'] = 'sv';
        $_SESSION['sid'] = 's123';
        $_COOKIE['ck'] = 'c123';
        $_FILES['avatar'] = ['name' => 'me.png'];
        $_SERVER['HTTP_X_CUSTOM'] = 'value';

        $req = new Request();

        $this->assertSame('sv', $req->server('SOME_KEY'));
        $this->assertSame('s123', $req->session('sid'));
        $this->assertSame('c123', $req->cookie('ck'));
        $this->assertSame(['name' => 'me.png'], $req->file('avatar'));
        $this->assertSame('value', $req->header('X-Custom'));
    }

    public function test_method_and_flags(): void
    {
        $req = new Request();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertTrue($req->isPost());
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertTrue($req->isGet());

        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $this->assertTrue($req->isAjax());

        $_SERVER['HTTPS'] = 'on';
        $this->assertTrue($req->isSecure());

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; Android)';
        $this->assertTrue($req->isMobile());

        $_SERVER['HTTP_USER_AGENT'] = 'Some GoogleBot Agent';
        $this->assertTrue($req->isRobot());
    }
}

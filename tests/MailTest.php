<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use SDF\Mail\LogMailer;
use SDF\Mail\Mail;
use SDF\Mail\Mailable;
use SDF\Mail\Mailer;
use SDF\Core;

class MailTest extends TestCase
{
    private string $logPath;

    protected function setUp(): void
    {
        $this->logPath = sys_get_temp_dir() . '/sdf_mail_test_' . uniqid() . '.log';
        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }
    }

    public function test_mailable_to(): void
    {
        $m = new Mailable();
        $m->to('user@example.com', 'John');
        $to = $m->getTo();
        $this->assertCount(1, $to);
        $this->assertSame('user@example.com', $to[0]['address']);
        $this->assertSame('John', $to[0]['name']);
    }

    public function test_mailable_from(): void
    {
        $m = new Mailable();
        $m->from('noreply@example.com', 'SDF');
        $from = $m->getFrom();
        $this->assertSame('noreply@example.com', $from['address']);
        $this->assertSame('SDF', $from['name']);
    }

    public function test_mailable_subject(): void
    {
        $m = new Mailable();
        $m->subject('Test Subject');
        $this->assertSame('Test Subject', $m->getSubject());
    }

    public function test_mailable_body(): void
    {
        $m = new Mailable();
        $m->body('<h1>Hello</h1>');
        $this->assertSame('<h1>Hello</h1>', $m->getBody());
    }

    public function test_mailable_cc(): void
    {
        $m = new Mailable();
        $m->cc('cc@example.com', 'CC User');
        $cc = $m->getCc();
        $this->assertCount(1, $cc);
        $this->assertSame('cc@example.com', $cc[0]['address']);
    }

    public function test_mailable_bcc(): void
    {
        $m = new Mailable();
        $m->bcc('bcc@example.com');
        $bcc = $m->getBcc();
        $this->assertCount(1, $bcc);
        $this->assertSame('bcc@example.com', $bcc[0]['address']);
    }

    public function test_mailable_attach(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'sdf_attach');
        file_put_contents($tmpFile, 'test content');

        $m = new Mailable();
        $m->attach($tmpFile);
        $attachments = $m->getAttachments();
        $this->assertCount(1, $attachments);
        $this->assertSame($tmpFile, $attachments[0]);

        unlink($tmpFile);
    }

    public function test_mailable_fluent_chaining(): void
    {
        $m = (new Mailable())
            ->to('user@example.com')
            ->from('noreply@example.com')
            ->subject('Hello')
            ->body('World');

        $this->assertSame('Hello', $m->getSubject());
        $this->assertSame('World', $m->getBody());
    }

    public function test_log_mailer_writes_to_log(): void
    {
        $mailer = new LogMailer($this->logPath);
        $m = (new Mailable())
            ->to('user@example.com', 'John')
            ->from('noreply@example.com', 'SDF')
            ->subject('Test')
            ->body('Hello World');

        $result = $mailer->send($m);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($this->logPath));

        $content = file_get_contents($this->logPath);
        $this->assertStringContainsString('user@example.com', $content);
        $this->assertStringContainsString('Test', $content);
        $this->assertStringContainsString('Hello World', $content);
    }

    public function test_log_mailer_multiple_recipients(): void
    {
        $mailer = new LogMailer($this->logPath);
        $m = (new Mailable())
            ->to('a@example.com')
            ->to('b@example.com')
            ->subject('Multiple')
            ->body('Body');

        $mailer->send($m);
        $content = file_get_contents($this->logPath);
        $this->assertStringContainsString('a@example.com', $content);
        $this->assertStringContainsString('b@example.com', $content);
    }

    public function test_log_mailer_with_cc_and_bcc(): void
    {
        $mailer = new LogMailer($this->logPath);
        $m = (new Mailable())
            ->to('to@example.com')
            ->cc('cc@example.com')
            ->bcc('bcc@example.com')
            ->subject('CC BCC Test')
            ->body('Body');

        $mailer->send($m);
        $content = file_get_contents($this->logPath);
        $this->assertStringContainsString('Cc: cc@example.com', $content);
        $this->assertStringContainsString('Bcc: bcc@example.com', $content);
    }

    public function test_log_mailer_default_log_path(): void
    {
        $mailer = new LogMailer();
        $ref = new \ReflectionProperty($mailer, 'logPath');
        $ref->setAccessible(true);
        $path = $ref->getValue($mailer);
        $this->assertSame(sys_get_temp_dir() . '/sdf_mail.log', $path);
    }

    public function test_mail_facade_to(): void
    {
        $m = Mail::to('user@example.com', 'John');
        $this->assertInstanceOf(Mailable::class, $m);
        $this->assertSame('user@example.com', $m->getTo()[0]['address']);
    }

    public function test_mail_facade_send_via_log(): void
    {
        $mailer = new LogMailer($this->logPath);
        $mail = new \SDF\Mail\Mail($mailer, ['address' => 'noreply@test.com', 'name' => 'Test']);
        $ref = new \ReflectionProperty(\SDF\Mail\Mail::class, 'instance');
        $ref->setAccessible(true);
        $ref->setValue(null, $mail);

        $m = (new Mailable())
            ->to('user@test.com')
            ->subject('Facade Test')
            ->body('Facade body');

        $result = \SDF\Mail\Mail::send($m);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($this->logPath));
        $content = file_get_contents($this->logPath);
        $this->assertStringContainsString('Facade Test', $content);

        $ref->setValue(null, null);
    }

    public function test_mail_facade_default_from(): void
    {
        $mailer = new LogMailer($this->logPath);
        $mail = new \SDF\Mail\Mail($mailer, ['address' => 'default@test.com', 'name' => 'Default']);
        $ref = new \ReflectionProperty(\SDF\Mail\Mail::class, 'instance');
        $ref->setAccessible(true);
        $ref->setValue(null, $mail);

        $m = (new Mailable())
            ->to('user@test.com')
            ->subject('Default From')
            ->body('Body');

        \SDF\Mail\Mail::send($m);
        $content = file_get_contents($this->logPath);
        $this->assertStringContainsString('default@test.com', $content);

        $ref->setValue(null, null);
    }

    public function test_mailer_interface_compliance(): void
    {
        $mailer = new LogMailer($this->logPath);
        $this->assertInstanceOf(Mailer::class, $mailer);
    }

    public function test_mailable_getters_return_correct_types(): void
    {
        $m = new Mailable();
        $this->assertIsArray($m->getTo());
        $this->assertIsArray($m->getCc());
        $this->assertIsArray($m->getBcc());
        $this->assertIsArray($m->getFrom());
        $this->assertIsString($m->getSubject());
        $this->assertIsString($m->getBody());
        $this->assertIsArray($m->getAttachments());
    }
}

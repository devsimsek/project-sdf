<?php

namespace SDF\Mail;

/**
 * Project SDF SMTP Mailer
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Mail
 * @file        SmtpMailer.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @link        https://github.com/devsimsek/project-sdf/wiki/libraries/mail
 * @since       Version 2.2
 * @filesource
 */
class SmtpMailer implements Mailer
{
    /** @var string SMTP host. */
    private string $host;

    /** @var int SMTP port. */
    private int $port;

    /** @var string SMTP username. */
    private string $username;

    /** @var string SMTP password. */
    private string $password;

    /** @var string Encryption method (tls or ssl). */
    private string $encryption;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->host = $config['host'] ?? 'localhost';
        $this->port = (int)($config['port'] ?? 587);
        $this->username = $config['username'] ?? '';
        $this->password = $config['password'] ?? '';
        $this->encryption = $config['encryption'] ?? 'tls';
    }

    /**
     * Send a mailable via mail() with constructed headers.
     *
     * @param Mailable $mailable
     * @return bool
     */
    public function send(Mailable $mailable): bool
    {
        $to = $this->formatAddresses($mailable->getTo());
        $subject = $mailable->getSubject();
        $body = $mailable->getBody();

        $headers = [];
        $from = $mailable->getFrom();

        if (!empty($from['address'])) {
            $headers[] = 'From: ' . $this->formatAddress($from);
        }

        $cc = $mailable->getCc();
        if (!empty($cc)) {
            $headers[] = 'Cc: ' . $this->formatAddresses($cc);
        }

        $bcc = $mailable->getBcc();
        if (!empty($bcc)) {
            $headers[] = 'Bcc: ' . $this->formatAddresses($bcc);
        }

        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'X-Mailer: SDF/' . (defined('SDF_VERSION') ? SDF_VERSION : '2.1.0');

        $attachments = $mailable->getAttachments();
        if (!empty($attachments)) {
            $boundary = md5(uniqid(microtime(), true));
            $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';

            $body = $this->buildMultipartBody($body, $attachments, $boundary);
        }

        return mail($to, $subject, $body, implode("\r\n", $headers));
    }

    /**
     * Build multipart body with attachments.
     *
     * @param string   $body
     * @param string[] $attachments
     * @param string   $boundary
     * @return string
     */
    private function buildMultipartBody(string $body, array $attachments, string $boundary): string
    {
        $multipart = "--{$boundary}\r\n";
        $multipart .= "Content-Type: text/html; charset=UTF-8\r\n";
        $multipart .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $multipart .= $body . "\r\n";

        foreach ($attachments as $filePath) {
            if (!is_file($filePath)) {
                continue;
            }
            $content = file_get_contents($filePath);
            $filename = basename($filePath);
            $multipart .= "--{$boundary}\r\n";
            $multipart .= "Content-Type: application/octet-stream\r\n";
            $multipart .= "Content-Transfer-Encoding: base64\r\n";
            $multipart .= "Content-Disposition: attachment; filename=\"{$filename}\"\r\n\r\n";
            $multipart .= chunk_split(base64_encode($content)) . "\r\n";
        }

        $multipart .= "--{$boundary}--\r\n";
        return $multipart;
    }

    /**
     * Format a single address array to string.
     *
     * @param array{address: string, name: string} $addr
     * @return string
     */
    private function formatAddress(array $addr): string
    {
        if (!empty($addr['name'])) {
            return "{$addr['name']} <{$addr['address']}>";
        }
        return $addr['address'];
    }

    /**
     * Format multiple addresses to comma-separated string.
     *
     * @param array<int, array{address: string, name: string}> $addresses
     * @return string
     */
    private function formatAddresses(array $addresses): string
    {
        $parts = [];
        foreach ($addresses as $addr) {
            $parts[] = $this->formatAddress($addr);
        }
        return implode(', ', $parts);
    }
}

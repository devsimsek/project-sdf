<?php

namespace SDF\Mail;

/**
 * Project SDF Log Mailer
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Mail
 * @file        LogMailer.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @link        https://github.com/devsimsek/project-sdf/wiki/libraries/mail
 * @since       Version 2.2
 * @filesource
 */
class LogMailer implements Mailer
{
    /** @var string Log file path. */
    private string $logPath;

    /**
     * @param string|null $logPath
     */
    public function __construct(?string $logPath = null)
    {
        $this->logPath = $logPath ?? sys_get_temp_dir() . '/sdf_mail.log';
    }

    /**
     * Write the mailable to the log file.
     *
     * @param Mailable $mailable
     * @return bool
     */
    public function send(Mailable $mailable): bool
    {
        $from = $mailable->getFrom();
        $subject = $mailable->getSubject();
        $body = $mailable->getBody();

        $lines = [];
        $lines[] = '[' . date('Y-m-d H:i:s') . '] --- New Email ---';
        $lines[] = 'From: ' . ($from['name'] ? "{$from['name']} <{$from['address']}>" : $from['address']);
        $lines[] = 'To: ' . $this->formatAddresses($mailable->getTo());

        $cc = $mailable->getCc();
        if (!empty($cc)) {
            $lines[] = 'Cc: ' . $this->formatAddresses($cc);
        }

        $bcc = $mailable->getBcc();
        if (!empty($bcc)) {
            $lines[] = 'Bcc: ' . $this->formatAddresses($bcc);
        }

        $lines[] = 'Subject: ' . $subject;
        $lines[] = 'Body:';
        $lines[] = $body;

        $attachments = $mailable->getAttachments();
        if (!empty($attachments)) {
            $lines[] = 'Attachments: ' . implode(', ', $attachments);
        }

        $lines[] = '--- End Email ---' . "\n";

        $dir = dirname($this->logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $written = file_put_contents($this->logPath, implode("\n", $lines), FILE_APPEND | LOCK_EX);
        return $written !== false;
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
            if (!empty($addr['name'])) {
                $parts[] = "{$addr['name']} <{$addr['address']}>";
            } else {
                $parts[] = $addr['address'];
            }
        }
        return implode(', ', $parts);
    }
}

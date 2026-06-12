<?php

namespace SDF\Mail;

/**
 * Project SDF Mailable
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Mail
 * @file        Mailable.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @link        https://github.com/devsimsek/project-sdf/wiki/libraries/mail
 * @since       Version 2.2
 * @filesource
 */
class Mailable
{
    /** @var array<int, array{address: string, name: string}> */
    private array $to = [];

    /** @var array<int, array{address: string, name: string}> */
    private array $cc = [];

    /** @var array<int, array{address: string, name: string}> */
    private array $bcc = [];

    /** @var string */
    private string $subject = '';

    /** @var string */
    private string $body = '';

    /** @var array{address: string, name: string} */
    private array $from = ['address' => '', 'name' => ''];

    /** @var array<int, string> */
    private array $attachments = [];

    /**
     * Add a recipient.
     *
     * @param string      $address
     * @param string|null $name
     * @return $this
     */
    public function to(string $address, ?string $name = null): static
    {
        $this->to[] = ['address' => $address, 'name' => $name ?? ''];
        return $this;
    }

    /**
     * Add a CC recipient.
     *
     * @param string      $address
     * @param string|null $name
     * @return $this
     */
    public function cc(string $address, ?string $name = null): static
    {
        $this->cc[] = ['address' => $address, 'name' => $name ?? ''];
        return $this;
    }

    /**
     * Add a BCC recipient.
     *
     * @param string      $address
     * @param string|null $name
     * @return $this
     */
    public function bcc(string $address, ?string $name = null): static
    {
        $this->bcc[] = ['address' => $address, 'name' => $name ?? ''];
        return $this;
    }

    /**
     * Set the sender.
     *
     * @param string      $address
     * @param string|null $name
     * @return $this
     */
    public function from(string $address, ?string $name = null): static
    {
        $this->from = ['address' => $address, 'name' => $name ?? ''];
        return $this;
    }

    /**
     * Set the subject.
     *
     * @param string $subject
     * @return $this
     */
    public function subject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Set the raw HTML body.
     *
     * @param string $body
     * @return $this
     */
    public function body(string $body): static
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Attach a file.
     *
     * @param string $filePath
     * @return $this
     */
    public function attach(string $filePath): static
    {
        $this->attachments[] = $filePath;
        return $this;
    }

    /**
     * Get recipients.
     *
     * @return array<int, array{address: string, name: string}>
     */
    public function getTo(): array
    {
        return $this->to;
    }

    /**
     * Get CC recipients.
     *
     * @return array<int, array{address: string, name: string}>
     */
    public function getCc(): array
    {
        return $this->cc;
    }

    /**
     * Get BCC recipients.
     *
     * @return array<int, array{address: string, name: string}>
     */
    public function getBcc(): array
    {
        return $this->bcc;
    }

    /**
     * Get sender.
     *
     * @return array{address: string, name: string}
     */
    public function getFrom(): array
    {
        return $this->from;
    }

    /**
     * Get subject.
     *
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * Get body.
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Get attachments.
     *
     * @return array<int, string>
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }
}

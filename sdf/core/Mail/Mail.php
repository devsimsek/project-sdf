<?php

namespace SDF\Mail;

use SDF\Core;

/**
 * Project SDF Mail Facade
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Mail
 * @file        Mail.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @link        https://github.com/devsimsek/project-sdf/wiki/libraries/mail
 * @since       Version 2.2
 * @filesource
 */
class Mail
{
    /** @var self|null Singleton instance. */
    private static ?self $instance = null;

    /** @var Mailer Resolved mailer driver. */
    private Mailer $mailer;

    /** @var array{address: string, name: string} Default from address. */
    private array $defaultFrom;

    /**
     * @param Mailer $mailer
     * @param array  $defaultFrom
     */
    public function __construct(Mailer $mailer, array $defaultFrom = ['address' => '', 'name' => ''])
    {
        $this->mailer = $mailer;
        $this->defaultFrom = $defaultFrom;
    }

    /**
     * Get the singleton instance.
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = self::fromConfig();
        }
        return self::$instance;
    }

    /**
     * Create a new Mailable instance.
     *
     * @param string      $address
     * @param string|null $name
     * @return Mailable
     */
    public static function to(string $address, ?string $name = null): Mailable
    {
        return (new Mailable())->to($address, $name);
    }

    /**
     * Send a mailable via the configured mailer.
     *
     * @param Mailable $mailable
     * @return bool
     */
    public static function send(Mailable $mailable): bool
    {
        $instance = self::getInstance();

        $from = $mailable->getFrom();
        if (empty($from['address']) && !empty($instance->defaultFrom['address'])) {
            $mailable->from($instance->defaultFrom['address'], $instance->defaultFrom['name']);
        }

        return $instance->mailer->send($mailable);
    }

    /**
     * Create Mail instance from app/config/mail.php.
     *
     * @return self
     */
    public static function fromConfig(): self
    {
        $config = Core::coreGetConfig('mail') ?: [];

        $defaultFrom = $config['from'] ?? ['address' => '', 'name' => ''];
        $driver = $config['default'] ?? 'log';

        if ($driver === 'smtp') {
            $smtpConfig = $config['smtp'] ?? [];
            $mailer = new NativeMailer($smtpConfig);
        } else {
            $logConfig = $config['log'] ?? [];
            $logPath = $logConfig['path'] ?? null;
            $mailer = new LogMailer($logPath);
        }

        return new self($mailer, $defaultFrom);
    }

    /**
     * Override the mailer at runtime.
     *
     * @param Mailer $mailer
     * @return void
     */
    public function setMailer(Mailer $mailer): void
    {
        $this->mailer = $mailer;
    }

    /**
     * Get the underlying mailer instance.
     *
     * @return Mailer
     */
    public function getMailer(): Mailer
    {
        return $this->mailer;
    }
}

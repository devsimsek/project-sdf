<?php

namespace SDF\Mail;

/**
 * Project SDF Mailer Interface
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Mail
 * @file        Mailer.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @link        https://github.com/devsimsek/project-sdf/wiki/libraries/mail
 * @since       Version 2.2
 * @filesource
 */
interface Mailer
{
    /**
     * Send a mailable.
     *
     * @param Mailable $mailable
     * @return bool
     */
    public function send(Mailable $mailable): bool;
}

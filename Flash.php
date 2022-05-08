<?php

/**
 * smskSoft Flash Library
 * Copyright smskSoft, mtnsmsk, devsimsek, Metin Şimşek.
 * @package     SDF Library Dist
 * @subpackage  Database
 * @file        Database.php
 * @version     v1.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2022, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @link        https://github.com/devsimsek/project-sdf/blob/libraries/Database.php
 * @since       Version 1.0
 * @filesource
 */
class Flash extends SDF\Library
{
    private array $data;

    public function __construct()
    {
        if (!isset($_SESSION)) {
            session_start();
        }
        $this->data = [];
    }

    public function add(string $title, string $message, bool $hide = false, string $type = "info"): bool
    {

        $toBePushed = [
            "title" => $title,
            "message" => $message,
            "hide" => $hide,
            "type" => $type
        ];

        $op = array_push($this->data, $toBePushed);
        $_SESSION["flash"] = $this->data;
        return $op;
    }
    
    public function display(): string
    {
        if (!isset($_SESSION)) {
            session_start();
            if (!isset($_SESSION["flash"]))
                $_SESSION["flash"] = $this->data;
        }
        $html = "";
        $i = 0;
        if (isset($_SESSION["flash"])) {
            foreach ($_SESSION["flash"] as $item) {
                unset($_SESSION["flash"][$i]);
                $html .= sprintf('<div class="toast %s" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header %s">
                    <i class="lni lni-%s me-2"></i>
                    <strong class="me-auto">%s</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    %s
                </div>
            </div>',
                    $item['hide'] ? " nohide" : null,
                    $this->detectType($item['type'])['class'],
                    $this->detectType($item['type'])['icon'],
                    $item['title'],
                    $item['message'],
                );;
                $i++;
            }
        }
        return $html;
    }

    private function detectType(string $type): array
    {
        switch ($type) {
            default:
            case "info":
                return ["class" => "bg-info text-white", "icon" => "information"];
                break;
            case "success":
                return ["class" => "bg-success text-white", "icon" => "checkmark-circle"];
                break;
            case "warning":
                return ["class" => "bg-warning text-white", "icon" => "warning"];
                break;
        }
    }
}

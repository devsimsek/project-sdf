<?php

namespace SDF;

const SDF_SECURITY_SAFE_CALL = true;

class Security
{

    private array $SecurityConfiguration;

    public function __construct()
    {
        $this->SecurityConfiguration = self::openINIFile();
        $this->checkLogs();
        $this->igniteSecurity();
        self::securityDebug('Security Class Initialized.');
    }

    private function igniteSecurity()
    {
    }

    private function checkLogs()
    {
        if ($this->SecurityConfiguration['logging']['purgeLogs']) {
            $allFiles = Core::core_scanDirectory($this->SecurityConfiguration['logging']['path'], '.{sdf_log}');
            foreach ($allFiles as $file) {
                $date = str_replace('.sdf_log', '', str_replace($this->SecurityConfiguration['logging']['prefix'], '', $file));
                if (str_ends_with('d', $this->SecurityConfiguration['logging']['purgeSince'])) {
                    str_replace('d', '', $this->SecurityConfiguration['logging']['purgeSince']);
                    if (date('d', strtotime($date)) <= date('d', strtotime("-" . (int)$this->SecurityConfiguration['logging']['purgeSince'] . " days"))) {
                        print_r('WARN');
                        if (file_exists($this->SecurityConfiguration['logging']['path'] . $file)) {
                            unlink($this->SecurityConfiguration['logging']['path'] . $file);
                        }
                    }
                }
                if (str_ends_with('w', $this->SecurityConfiguration['logging']['purgeSince'])) {
                    str_replace('w', '', $this->SecurityConfiguration['logging']['purgeSince']);
                    if (date('w', strtotime($date)) <= date('w', strtotime("-" . (int)$this->SecurityConfiguration['logging']['purgeSince'] . " weeks"))) {
                        print_r('WARN');
                        if (file_exists($this->SecurityConfiguration['logging']['path'] . $file)) {
                            unlink($this->SecurityConfiguration['logging']['path'] . $file);
                        }
                    }
                }
                if (str_ends_with('m', $this->SecurityConfiguration['logging']['purgeSince'])) {
                    str_replace('m', '', $this->SecurityConfiguration['logging']['purgeSince']);
                    if (date('m', strtotime($date)) <= date('m', strtotime("-" . (int)$this->SecurityConfiguration['logging']['purgeSince'] . " month"))) {
                        print_r('WARN');
                        if (file_exists($this->SecurityConfiguration['logging']['path'] . $file)) {
                            unlink($this->SecurityConfiguration['logging']['path'] . $file);
                        }
                    }
                }
                if (str_ends_with('y', $this->SecurityConfiguration['logging']['purgeSince'])) {
                    str_replace('y', '', $this->SecurityConfiguration['logging']['purgeSince']);
                    if (date('Y', strtotime($date)) <= date('Y', strtotime("-" . (int)$this->SecurityConfiguration['logging']['purgeSince'] . " year"))) {
                        print_r('WARN');
                        if (file_exists($this->SecurityConfiguration['logging']['path'] . $file)) {
                            unlink($this->SecurityConfiguration['logging']['path'] . $file);
                        }
                    }
                }
            }
        }
    }

    /**
     * @return array
     */
    private static function openINIFile(): array
    {
        if (file_exists(SDF_APP_CONF . 'security.ini')) {
            return parse_ini_file(SDF_APP_CONF . 'security.ini', true) ?? array('error' => 'Can\'t validate file format.');
        }
        return array(
            'error' => 'Path doesn\'t exists.',
        );
    }

    /**
     * Put to log file
     * @return void
     */

    public function csrf_setToken()
    {

    }

    /**
     * @return string
     */
    public static function generateHTML(): string
    {
        return "";
    }

    public function verify()
    {

    }

    private function securityDebug(mixed $message, string $flag = 'debug', array $details = null): void
    {
        $template = $this->SecurityConfiguration['logging']['template'];
        $variables = [
            '$timestamp' => date(DATE_RFC3339),
            '$flag' => strtoupper($flag),
            '$message' => $message,
            '$details' => $details ?? ''
        ];
        error_log(strtr($template, $variables));
        if ($this->SecurityConfiguration['logging']['logToFile']) file_put_contents($this->SecurityConfiguration['logging']['path'] . $this->SecurityConfiguration['logging']['prefix'] . date(DATE_RFC3339) . '.sdf_log', strtr($template, $variables));
    }
}
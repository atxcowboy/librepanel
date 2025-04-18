<?php

namespace LibrePanel\Cron\Http;

use LibrePanel\Cron\Http\DomainSSL;
use LibrePanel\Cron\Http\WebserverBase;
use LibrePanel\Cron\Http\Php\PhpInterface;
use LibrePanel\FileDir;
use LibrePanel\Settings;
use LibrePanel\LibrePanelLogger;

class Caddy extends HttpConfigBase
{
    /** @var array Generated Caddy config blocks */
    protected $caddy_data = [];

    public function createIpPort()
    {
        // Caddy uses domain-based configuration; no IP/port-specific files needed
    }

    public function createVirtualHosts()
    {
        $domains = WebserverBase::getVhostsToCreate();
        foreach ($domains as $domain) {
            $ssl = ($domain['ssl'] === '1');
            // Prepare SSL files when needed
            $dssl = new DomainSSL();
            $dssl->setDomainSSLFilesArray($domain);
            // PHP-FPM: get socket if enabled
            if ((int) Settings::Get('phpfpm.enabled') === 1) {
                $php = new PhpInterface($domain);
                $domain['fpm_socket'] = $php->getInterface()->getSocketFile();
            }
            $filename = $this->getVhostFilename($domain, $ssl);
            $scheme = $ssl ? 'https' : 'http';
            $config = "{$scheme}://{$domain['domain']} {\n";
            if ($ssl && !empty($domain['ssl_cert_file']) && !empty($domain['ssl_key_file'])) {
                $config = "{$scheme}://{$domain['domain']} {\n    tls {$domain['ssl_cert_file']} {$domain['ssl_key_file']}\n";
            }
            $config .= "    root * {$domain['documentroot']}\n";
            // PHP-FPM integration
            if ((int) Settings::Get('phpfpm.enabled') === 1 && isset($domain['fpm_socket'])) {
                $config .= "    php_fastcgi unix/{$domain['fpm_socket']}\n";
            }
            $config .= "    file_server\n}";
            $this->caddy_data[$filename] = $config;
        }
    }

    public function createFileDirOptions()
    {
        // No additional file or directory options needed for Caddy
    }

    public function writeConfigs()
    {
        foreach ($this->caddy_data as $path => $content) {
            $dir = dirname($path);
            LibrePanelLogger::getInstanceOf()->logAction(
                LibrePanelLogger::CRON_ACTION,
                LOG_INFO,
                "caddy::writeConfigs: creating {$dir}"
            );
            FileDir::safe_exec('mkdir -p ' . escapeshellarg($dir));
            file_put_contents($path, $content);
            LibrePanelLogger::getInstanceOf()->logAction(
                LibrePanelLogger::CRON_ACTION,
                LOG_INFO,
                "caddy::writeConfigs: wrote {$path}"
            );
        }
    }

    public function createOwnVhostStarter()
    {
        // Caddy does not require an own vhost starter file
    }
}

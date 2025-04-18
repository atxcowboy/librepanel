<?php
use PHPUnit\Framework\TestCase;
use LibrePanel\Settings;
use LibrePanel\Cron\Http\Caddy;

/**
 * @covers \LibrePanel\Cron\Http\Caddy
 */
class CaddyConfigTest extends TestCase
{
    protected function setUp(): void
    {
        Settings::Stash();
    }

    public function testGetVhostFilenameNormal(): void
    {
        Settings::Set('system.webserver', 'caddy');
        Settings::Set('system.caddyconf', '/etc/caddy');
        $domain = ['domain' => 'example.com'];
        $method = new \ReflectionMethod(Caddy::class, 'getVhostFilename');
        $method->setAccessible(true);
        $result = $method->invoke(new Caddy(), $domain, false, false);
        $this->assertSame(
            '/etc/caddy/35_librepanel_normal_vhost_example.com.caddy.conf',
            $result
        );
    }

    public function testGetVhostFilenameSSL(): void
    {
        Settings::Set('system.webserver', 'caddy');
        Settings::Set('system.caddyconf', '/var/lib/caddy');
        $domain = ['domain' => 'sub.domain.example.com'];
        $method = new \ReflectionMethod(Caddy::class, 'getVhostFilename');
        $method->setAccessible(true);
        $result = $method->invoke(new Caddy(), $domain, true, false);
        $this->assertSame(
            '/var/lib/caddy/33_librepanel_ssl_vhost_sub.domain.example.com.caddy.conf',
            $result
        );
    }

    public function testGetVhostFilenameOnlyName(): void
    {
        Settings::Set('system.webserver', 'caddy');
        $domain = ['domain' => 'example.com'];
        $method = new \ReflectionMethod(Caddy::class, 'getVhostFilename');
        $method->setAccessible(true);
        $result = $method->invoke(new Caddy(), $domain, false, true);
        $this->assertSame(
            '35_librepanel_normal_vhost_example.com.caddy.conf',
            $result
        );
    }
}

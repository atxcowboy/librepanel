[![LibrePanel - Build (MariaDB)](https://github.com/atxcowboy/librepanel/actions/workflows/build-mariadb.yml/badge.svg?branch=main)](https://github.com/atxcowboy/librepanel/actions/workflows/build-mariadb.yml)
[![LibrePanel - Build (MySQL)](https://github.com/atxcowboy/librepanel/actions/workflows/build-mysql.yml/badge.svg?branch=main)](https://github.com/atxcowboy/librepanel/actions/workflows/build-mysql.yml)

# LibrePanel

A modern, open-source hosting control panel, forked from Froxlor, and developed by the [Panomity GmbH](https://www.panomity.com). Its goal is to simplify the effort of managing your hosting platform while providing flexibility and transparency.

## Installation

### Fast install

1. Ensure that your webserver serves `/var/www/html`.
2. Extract LibrePanel into `/var/www/html` (for example into `/var/www/html/librepanel`).
3. Point your browser to `http://[ip-of-webserver]/librepanel`.
4. Follow the installer.
5. Log in as administrator.
6. Have fun!

**Note:** If you choose manual configuration during installation, additional steps may be required:

1. Adjust **System > Settings** according to your needs.
2. Choose your distribution under **System > Configuration**.
3. Follow the steps for your services (web server, PHP, etc.).

### Detailed installation

Coming soon at [librepanel.com](https://librepanel.com/) (in development).  
*(For reference to LibrePanel’s docs, you may still look at [docs.librepanel.org](https://docs.librepanel.org/latest/general/installation/) until LibrePanel’s docs are fully available.)*

## Help & Community

You may find help in the following places:

### GitHub

Issues and discussions can be found at our GitHub repository:  
[https://github.com/atxcowboy/librepanel](https://github.com/atxcowboy/librepanel)

*(Community forums, a dedicated Discord, or other support channels may be added as LibrePanel grows.)*

## Caddy Web Server Support

LibrePanel supports Caddy as an alternative to Apache or Nginx. To enable and configure Caddy:

1. In **System > Settings**, set **Webserver** to `Caddy`.
2. (Optional) Adjust **Caddy config directory** (`system_caddyconf`, default `/etc/caddy`).
3. (Optional) Adjust **Caddy reload command** (`system_caddyreload_command`, default `systemctl reload caddy`).
4. Ensure **PHP-FPM** is installed and enabled; Caddy will automatically use the socket defined in PHP-FPM settings.
5. Cron jobs will generate one `.caddy.conf` file per domain under your Caddy config directory.

Each Caddy vhost file includes:

- `tls <cert_file> <key_file>` for SSL/TLS
- `root * <documentroot>` to serve files
- `php_fastcgi unix/<fpm_socket>` when PHP-FPM is enabled
- `file_server` to enable static file serving

After configuration changes, run:
```bash
php bin/librepanel-cli librepanel:cron tasks
```
to rebuild and reload your Caddy configs.

## License

LibrePanel is open-source software. See the [COPYING](COPYING) file for license details.

## Downloads

*(As this is a new project/fork, official packages may still be in progress. Below is an example section for future reference.)*

- **Latest Tarball**  
  *Coming soon at [librepanel.com](https://librepanel.com/downloads)*
  
- **Debian repository**  
  *Planned for future release. Stay tuned!*

## Contributing

We welcome contributions! Check out our [CONTRIBUTING.md](.github/CONTRIBUTING.md) (to be updated) or open an issue/PR on GitHub.

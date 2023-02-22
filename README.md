# Setup instructions

## Requirements
- git
- PHP > 8.0
- composer
- mysql-server

> Note: For more details read the [Laravel Getting Startet Guide](http://laravel.com/docs)

On Ubuntu 22.04: `sudo apt install git php composer npm mysql-server`

1. Clone the repository from https://codeberg.org/iconet-Foundation/bridge

2. Install dependencies with `composer install`. Don't run `composer update`.

3. If there are missing php modules: Look for errors from the previous command. You probably have to install `php-curl, php-dom, ...`
   On Ubuntu use `sudo apt install ...`
   On Windows find your `php.ini` by running `php -ini`. In that file find the section about dynamic extensions and uncomment the needed ones.

4. Create a new Database `bridge` and a user with access.

5. Copy `.env.example` to `.env`
   Enter the details for your MYSQL connection.

6. `php artisan key:generate`

7. `php artisan migrate --seed` to initialize the DB schema.

8. Start the server with `php artisan serve` the default URL is `http://localhost:8000`
9. If you need a https connection, you will need something like mkcert or loophole.

## Problem solving
1. Clear caches:

        php artisan view:clear
        php artisan cache:clear
        php artisan route:clear

2. Give the webserver access to your project's storage folder:

        sudo chgrp -R www-data storage
        sudo chmod -R ug+rwx storage




## Using Docker

Alternatively, you can create a docker instance which will host a mysql and apache server.
The docker container will serve the local development directory, so external changes are immediately made available. (This also means the container can rewrite local files. So take care to avoid data loss, when switching between the two
setups.)

```bash
docker-compose up
```

There is no single docker-compose file, that orchestrates the bridge together with the other two projects, so the manual setup is recommended, if you want to test the full integration.
The traefik reverse proxy from the example-netA repository's docker-compose.yml redirects https://bridge.localhost to this container. Follow the setup instructions from there.

You can attach to it with `docker attach bridge` (`Ctrl+P` `Ctlr+Q` to detach). Apache logs are located
at `/var/log/apache2/`.


## Optional
### XDebug
XDebug is needed for debugging and code coverage analysis of the tests.
1. Install it with `sudo apt install php-xdebug`
2. If php 8.1 is the version you use, `/etc/php/8.1/mods-available/xdebug.ini` should contain at least the first two lines:

    ```apache
    zend_extension=xdebug.so
    xdebug.mode=debug

    #xdebug.remote_enable=1
    #xdebug.remote_connect_back = 1
    #xdebug.remote_port = 9000
    #xdebug.scream=0 
    #xdebug.cli_color=1
    #xdebug.show_local_vars=1
    ```

3. This config will be included in your `php.ini` via `sudo phpenmod -v 8.1 xdebug`.
4. Restart apache: `sudo systemctl restart apache2`
5. In phpstorm under `File->Setting->PHP->Debug`, run the IDE's validation script of step 2 in the `public` sub folder.






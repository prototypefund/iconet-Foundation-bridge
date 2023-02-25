# ActivityPub <==> iconet Bridge
This bridges allows communication between ActivityPub instances and servers implementing the iconet packet transport. To achive this the software simulates both an ActivityPub inbox and an iconet inbox simultaneously.

This is a prototype for demonstration purposes only. It is thought to be used to bridge the mastodon server and the example-netA.

## Example Usage
In the following examples we assume that
 - the bridge is hosted at `https://bridge.net`.
 - there is an ActivityPub (AP) user Alice with AP handle `@alice@activity.net`
 - there is an iconet user Bob with iconet address `bob@iconet.net`

### ActivityPub => Iconet
ActivityPub user Alice wants to send a message to iconet user Bob.
1. To address an iconet user with the iconet address `bob@iconet.net` from ActivityPub (AP), the corresponding AP user's AP handle on the bridge will be`bob__iconet.net@bridge.net`.
2. After this address transformation the bridge can be treated as a usual AP instance. A webfinger query to `https://bridge.net/.well-known/webfinger?resource=acct:bob__iconet.net` will reval the users profile `https://bridge.net/user/bob__iconet.net`. Fetching Bob's profile shows the endpoint for his inbox `https://bridge.net/inbox/bob__iconet.net`.
3. A Create-activity wrapped around an ActicityStream-object can then be posted to Bob's inbox. The object has to include an iconet field, that contains instructions for iconet users on how to interpret the content. The HTTP request must contain a Signature header of the actor (Alice).
4. The bridge will verify the signature and the activity. Then forward the iconet section of the object to its recipients (`to` and `cc`). In this case the recipient `https://bridge.net/user/bob__iconet.net` will cause an iconet packet to be sent to Bob's iconet server's iconet endpoint `https://iconet.net/iconet`. The iconet packet has the type `Packet` and is unencrypted.


### Iconet => ActivityPub
Iconet user Bob (`bob@iconet.net`) wants to send a message back to ActivityPub user Alice.
1. To address an AP user with the AP handle `@alice@activity.net` from iconet, the corresponding iconet user's address on the bridge is `alice__activity.net@bridge.net`.
2. An unencrypted iconet packet addressed to `alice__activity.net@bridge.net` can be posted to the iconet endpoint of the bridge `https://bridge.net/iconet`.
3. The bridge will attach the iconet packet to a Note-object. Then wrap the object in a Create-activity sign it with a key created for Bob's bridge profile `bob__iconet.net@bridge.net`.
4. Next, the endpoint for Alices inbox has to be determined. Therefore a webfinger query is sent to `https://activity.net/.well-known/webfinger?resource=acct:alice`. This shows where Alices's profile is located and the Create-activity can posted to Alice's inbox.
5. The receiving AP-server should verify the signature and will again use a webfinger query to `https://bridge.net/.well-known/webfinger?resource=acct:bob__iconet.net` to fetch the public key created by the bridge for Bob.


## Not implemented
- ActivityPub
    - [ ] Shared inbox for the entire instance (only separate inboxes per user are implemented)
    - [ ] Only activities of type `Create` with the object `Note` are accepted. The object could be of any type, as long as it has an `iconet` field.
    - [ ] Webfinger queries only support the `resource=acct:<user>` parameter.
    -  [ ] Only the signature in the HTTP header is validated.
- Iconet
    - [ ] Only unencryped packets can te sent and received.

-----

![](http://iconet-foundation.org/images/BMBF_en.png)

From September 2022 to February 2023 this project received funding
from the German Ministry of Education and Research.

# Setup instructions

## Requirements
- git
- PHP > 8.2
- composer
- mysql-server

> Note: For more details read the [Laravel Getting Startet Guide](http://laravel.com/docs)

On Ubuntu 22.04: `sudo apt install git php composer mysql-server`

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





# Pure PHP WebSocket Server Example

This example show up how to setup a WebSocket with pure PHP, with minimal codes and dependencies, with:

- The example Nginx configuration.
- Setup wscat with NPM so you can learn how to detect service is on or off.


The components you needs:

- PHP 5.4 or above, with event extension.
- Redis 2.8 or above.
- Nginx if you need run the nginx example.
- NPM if you need `wscat`.


## How to run

First of all, install dependencies by Composer:

```BASH
composer install
```

Than install the NPM components:

```
npm install
```

Start the Redis server if needs:

```BASH
redis-server &
```

Start the daemon:

```BASH
php app.php
```

The daemon will listen for all TCP traffic from anywhere to port 8080.


Now we using `wscat` connect to the WebSocket server. Start another terminal session:

```BASH
node_modules/wscat/bin/wscat ws://127.0.0.1:8080 -p 13
```

Now the wscat interactive shell has been start.

Start another terminal session again, start the `redis-cli`:

```BASH
redis-cli
```

Enter follow line or copy follow line into `redis-cli` and press enter:

```BASH
PUBLISH pubsub:example 'Hello World!'
```

Now you can see 'Hello world!' in terminal session which running `wscat`.


## The Nginx Example

Start Nginx with example configration:

```BASH
nginx -c $(pwd)/nginx/nginx.conf
```

In this example, only traffic send to `/ws/` will pass through to the WebSocket server, and we using port 8000 for nginx:

```BASH
node_modules/wscat/bin/wscat ws://127.0.0.1:8000/ws/ -p 13
```

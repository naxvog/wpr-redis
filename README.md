# WPR-Redis
This plugin enables WP Rocket to store its generated cache files in the Redis database instead of the default filesystem storage.

## Requirements ##
* WP Rocket WordPress Plugin
* Redis Server
* PHP 7+

## Constant configuration ##
You may use the following constants to configure the plugin. All constants will take preference over the defined settings if there are any.

* `WPR_REDIS_SCHEME` Needs to be set to `unix` to use a unix socket to connect.
* `WPR_REDIS_HOST` For TCP connections this will be the IP or hostname of the server. If you are using a socket connection this will be the socket path. Default: `localhost`
* `WPR_REDIS_PORT` Port to connect to the server using TCP. Default `6379`
* `WPR_REDIS_DB` The Database to be used. Default `0`
* `WPR_REDIS_PWD` Redis server password if needed.
* `WPR_REDIS_SALT` If set all stored keys will be prefixed by this salt. To further differentiate all keys will pre prefixed by their respective database table prefix as well.
  
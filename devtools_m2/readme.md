# Instructions for developing locally

## Setting up your environment

A few things need to be setup on your system prior to launching Docker and teh test suite:
 - make sure you have `npm` installed. Currently `npm` 6.11.3 is adequate
```bash
$ npm --version
6.11.3
```
 - in the `devtools_m2` directory, install the cypress test framework:
```bash
$ npm install
```
 - add entries in your `/etc/hosts` file for test endpoints:
```
127.0.0.1 main.magento.localhost
127.0.0.1 site1.magento.localhost
```
 - Spin up Docker and Magento with `setup.sh` in the devtools_m2 directory
 ```aidl
$ DRIP_COMPOSE_ENV=test ./setup.sh
```

You can access the admin at http://main.magento.localhost:3005/admin_123

## Running the tests

To start the cypress test runner ...

```bash
cd devtools_m2/
./node_modules/.bin/cypress open
```

...which will open a small window that allows test execution.

## Full reset

```bash
cd devtools_m2/
docker-compose down
rm -rf db_data/
```

Then run `setup.sh` to bring a clean instance back up.

## To run cron

Run the `cron.sh` script in the `devtools_m2/` directory.

## Debugging using XDebug

XDebug is enabled in the TEST environment ( `DRIP_COMPOSE_ENV=test ./setup.sh` ), and works with VSCode, but there are a few things you'll need to do configure your environment:

- Get the [Xdebug Helper for Firefox](https://addons.mozilla.org/en-US/firefox/addon/xdebug-helper-for-firefox/), or [Xdebug helper for Chrome](https://chrome.google.com/extensions/detail/eadndfjplgieldjbigjakmdgkmoaaaoc)
- Install XDebug locally `pecl install xdebug`
- Install `PHP Debug` in VSCode  (you'll do yourself a favor to restart VSCode)
- Modify `.vscode/launch.json` to have an entry that looks like this:
```
    {
      "name": "M2:Listen for XDebug",
      "type": "php",
      "request": "launch",
      "port": 9000,
      "pathMappings": { "/var/www/html/magento/app/code/Drip/Connect": "${workspaceFolder}" },
      "xdebugSettings": { "max_data": 65535, "show_hidden": 1, "max_children": 100, "max_depth": 5 }
    }
```

From here you'll be ready to debug.
- Click the Debugger icon on the left menu in VSCode
- Make sure your profile is "M2:Listen for XDebug".
- Click the green triangle at the top `Debug` menu
- Set a breakpoint
- Open up your browser and visit a page in the magento test environment (e.g. http://main.magento.localhost:3006/admin_123/).
- Enable debugging by clicking the debug icon that appeared when you installed the Xdebug Helper plugin (above).
- Trigger your breakpoint by navigating in your browswer.

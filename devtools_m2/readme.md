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
 - Spin up Docker and Magento with `setup.sh` in the devtools_m1 directory
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
./docker_compose.sh down
rm -rf db_data/
```

Then run `setup.sh` to bring a clean instance back up.

## To run cron

Run the `cron.sh` script in the `devtools_m2/` directory.

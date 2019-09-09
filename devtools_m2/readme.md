# Instructions for developing locally

To spin up Docker and Magento, run ./setup.sh in the devtools directory.

You can access the admin at http://main.magento.localhost:3005/admin_123

## Full reset

```bash
cd devtools/
docker-compose down
rm -rf db_data/
```

Then run `setup.sh` to bring a clean instance back up.

## To run cron

Run the `cron.sh` script in the `devtools/` directory.

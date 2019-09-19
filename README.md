# BileMo

[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=nicordev_formation-oc-php-projet7-api-rest&metric=alert_status)](https://sonarcloud.io/dashboard?id=nicordev_formation-oc-php-projet7-api-rest)

A restful API to sell mobile phones

## Installation

1. Clone the project with `git clone`
1. Run `composer install`
1. Create a database and configure the `.env.local` file with your database credentials
1. Run `php bin/console doctrine:migrations:migrate` to create the right tables

## Cache

You can change the expiration duration by setting `CACHE_DURATION` in your `.env.local` file

## Load data fixtures

Once the installation is complete, run the following command to load fake data in your database: `php bin/console doctrine:fixtures:load`

Then use the following credentials in your POST request body to get a valid JWT via the uri `/api/login_check`:
```
{
	"username": "user@demo.com",
	"password": "pwdSucks!0"
}
```
or
```
{
	"username": "admin@demo.com",
	"password": "pwdSucks!0"
}
```

*Enjoy!*

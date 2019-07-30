# BileMo

A restful API to sell mobile phones

## Installation

1. Clone the project with `git clone`
1. Run `composer install`
1. Create a database and configure the `.env` file with your database credentials
1. Run `php bin/console doctrine:migrations:migrate` to create the right tables
1. (optional) Run `php bin/console doctrine:fixtures:load` to load some sample data in your database
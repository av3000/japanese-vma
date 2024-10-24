# Japanese Learning Environment

Built using [Laravel](https://laravel.com/docs) for Server API and [React](https://reactjs.org/) for the client side.

Japanese data comes from [Electronic Dictionary Research and Development Group](http://www.edrdg.org/), and are used in conformance with the Group's [licence](http://www.edrdg.org/edrdg/licence.html).

### Backend

- [laravel-snappy](https://github.com/barryvdh/laravel-snappy) - for PDF generating. [wkhtmltopdf](https://github.com/barryvdh/laravel-snappy#wkhtmltopdf-installation) **wkhtmltopdf is required in order for the laravel-snappy library to work.**
- [passport](https://laravel.com/docs/7.x/passport)
- [laravel helpers](https://github.com/laravel/helpers) - more comfortable customized version of the [original laravel helpers](https://laravel.com/docs/7.x/helpers)
- [laravel/ui](https://laravel.com/docs/7.x/frontend) - scaffolding for the frontend. In this project [React](https://reactjs.org/docs/getting-started.html) was used.

### Frontend

- [react-moment](https://github.com/headzoo/react-moment) of [moment](https://www.npmjs.com/package/moment) for date formatting.
- [axios](https://www.npmjs.com/package/axios) for HTTP requesting.
- [react-bootstrap](https://react-bootstrap.github.io/) for navdropbars with links and [bootstrap](https://www.npmjs.com/package/bootstrap) for overall responsiveness.
- [react-router-bootstrap](https://github.com/react-bootstrap/react-router-bootstrap) for [react-router](https://github.com/reactjs/react-router) and [react-bootstrap](https://react-bootstrap.github.io/) integration.
- [redux](https://redux.js.org/introduction/getting-started), [redux-thunk](https://www.npmjs.com/package/redux-thunk) and [react-redux](https://www.npmjs.com/package/react-redux) for redux single-source state management.
- [create-react-app](https://create-react-app.dev/docs/getting-started/).

## Setup Docker

In `/processor-api` repository root run:

```bash
chmod +x .docker/entrypoint.sh
```

Run docker containers in detached mode:

```bash
docker-compose up -d --build
```

Test if Mysql initialized properly, by entering container via bash or docker desktop:

```bash
docker-compose exec db bash
mysql -u <DB_USERNAME> -p
prompted password: <DB_PASSWORD>
```

List tables, there should be all the common ones:

```bash
SHOW databases;
USE <DB_NAME>;
SHOW TABLES;
```

If tables are there, proceed with Japanese data migration.
Data volume is big, so it may take over a minute and be cautions when rebuilding containers to avoid duplication.

Enter container via bash or docker desktop into laravel app:

```bash
# Enter container
docker-compose exec laravel-app bash
# Create japanese material tables
php artisan migrate --path=database/migrations/japanese-data
```

```bash
# Create new tables, generate encryption keys for Passport
php artisan passport:install
```

Necessary to fill-up the categories for the entities objects in "objecttemplates" table.
Creates common and admin users
Example custom lists and articles

```bash
php artisan db:seed
# seed single table
php aritsan db:seed --class=<ClassNameSeeder>
```

If by chance something seems cached or configs not updated try reset:

```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

Take containers down and empty volumes for clean containers rebuild

```bash
docker-compose down -v
docker-compose up -d --build
```

Remember after clean rebuild to run migrations and seed once again.

## Setup Local

Choices for local environment server:

- [Composer](https://github.com/composer/composer) - PHP package manager
- [Node](https://nodejs.org/en/download). - Node js runtime for frontend assets of laravel.
- [Xampp](https://www.apachefriends.org/) - web server Apache, PHP and MariaDB(MySQL).
- [Laravel Herd](https://herd.laravel.com/windows) - dev environment with all you need for laravel development.
- For PDF generating laravel-snappy to work requires [wkhtmltopdf](https://github.com/barryvdh/laravel-snappy#wkhtmltopdf-installation). Install it as a [composer dependency](https://github.com/KnpLabs/snappy#wkhtmltopdf-binary-as-composer-dependencies) or [manually here](http://wkhtmltopdf.org/downloads.html) into your machine.

### Laravel API

In `processor-api` directory
Install backend modules

```bash
composer install
```

Change .env file settings for your database.

```bash
cp .env.example .env
```

Generate unique app key

```bash
php artisan key:generate
```

#### Database MySQL -

Make sure your DB Collation is `utf8mb4_general_ci` for Japanese characters support

Migrate DB tables

\*\*_NOTICE_

BEFORE MIGRATING, NEED TO MAKE SURE THAT MYSQL CONFIG IN my.ini THE FOLLOWING LINE IS UPDATED TO IMPORT LARGE FILES\*\*

```bash
max_allowed_packet=128M
```

If mysql config is correct, proceed steps below

```bash
# Create common tables
php artisan migrate --path=database/migrations/now

# Create japanese material tables
php artisan migrate --path=database/migrations/japanese-data
```

Add Passport clients

```bash
# Create new tables, generate encryption keys for Passport
php artisan passport:install
```

Seed initial data

```bash
# Necessary to fill-up the categories for the resources objects in "objecttemplates" table.
# Creates common and admin users
# Example custom lists
# Example articles
php artisan db:seed
```

API Documentation page is served on laravel side, to have it work install packages.

```bash
npm install
# to watch changes
npm run watch
```

### React App

CD into 'client' directory
Add all node modules used in the react app.

```bash
npm install
```

Start react app

```bash
npm start
```

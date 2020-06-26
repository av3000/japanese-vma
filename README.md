# Japanese Learning Environment

Built using [Laravel](https://laravel.com/docs) for Server API and [React](https://reactjs.org/) for the client side.

Japanese data comes from [Electronic Dictionary Research and Development Group](http://www.edrdg.org/), and are used in conformance with the Group's [licence](http://www.edrdg.org/edrdg/licence.html).

## libraries

### Prerequisite

- Make sure you have composer installed.
- Make sure you have latest stable version of node installed.

### Backend
- [laravel-snappy](https://github.com/barryvdh/laravel-snappy) - for PDF generating. [wkhtmltopdf](https://github.com/barryvdh/laravel-snappy#wkhtmltopdf-installation) is required in order for the library to work.
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

## Setup

### Laravel API

```bash
git clone https://AVaiciulis3000@bitbucket.org/AVaiciulis3000/japanese-vma.git
```

After cloning, cd into project and run:

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

Migrate DB tables
```bash
# Create common tables
php artisan migrate --path=database/migrations/now

# Create japanese material tables
php artisan migrate --path=database/migrations/japanese-data
```

Add Passport clients
```bash
# Create new tables for Passport
php artisan migrate

# Install encryption keys and other necessary stuff for Passport
php artisan passport:install
```

Seed initial data
```bash
# Necessary to fill-up the categories for the resources objects in "objecttemplates" table.
# Creates common and admin users
# Some custom lists
# Some articles
php artisan db:seed
```

API Documentation page
```bash
# to require needed modules
npm install
# to watch changes
npm run watch
```

### React App

Add all node modules used in the react app.
```bash
npm install
```

Start react app
```bash
npm start
```

### Common commands & Readings for future tasks

https://devmarketer.io/learn/setup-laravel-project-cloned-github-com/

https://riptutorial.com/laravel/example/17358/creating-a-seeder

```bash
php artisan db:seed --class=UserSeeder
```

https://github.com/fzaninotto/Faker#fakerproviderja_jpperson
Faker\Provider\ja_JP\Person
https://packagist.org/packages/xyyolab/faker-japanese

Passport Authentication hero
https://medium.com/modulr/create-api-authentication-with-passport-of-laravel-5-6-1dc2d400a7f

https://restfulapi.net/http-status-codes/

https://stackoverflow.com/questions/32494545/where-to-save-the-jwt-token-in-laravel

https://laracasts.com/discuss/channels/laravel/use-name-in-url-instead-of-id

7.+ laravel Blog App example
https://github.com/guillaumebriday/laravel-blog/blob/master/routes/api.php

https://appdividend.com/2018/05/16/laravel-model-relationships-example/
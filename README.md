# Japanese Learning Environment

Browse lists of Japanese texts, including simplified articles and news. Upload your own content to generate PDFs featuring Japanese radicals, kanji, vocabulary, and sentences for learning. Create or explore lists with articles, radicals, kanji, and vocabulary items, track your progress, and chat with other learners.

Component library on [Storybook](https://67e03024743f7597d5a20906-ioduyxshby.chromatic.com/?path=/docs/components-shared-button--docs)

![Application demo1](./docs/assets/images/jpl-short-1.gif)

![Application demo2](./docs/assets/images/jpl-short-2.gif)

Built using [Laravel](https://laravel.com/docs) for Server API and [React](https://reactjs.org/) for the client side generated with [create-react-app](https://create-react-app.dev/docs/getting-started/).

Japanese data comes from [Electronic Dictionary Research and Development Group](http://www.edrdg.org/), and are used in conformance with the Group's [licence](http://www.edrdg.org/edrdg/licence.html).
This site uses the [JMdict](http://www.edrdg.org/wiki/index.php/JMdict-EDICT_Dictionary_Project), [Kanjidic2](http://www.edrdg.org/wiki/index.php/KANJIDIC_Project), [JMnedict](http://www.edrdg.org/enamdict/enamdict_doc.html), and [Radkfile](http://www.edrdg.org/krad/kradinf.html) dictionary files. JLPT data comes from Jonathan Waller's JLPT Resources [page](http://www.tanos.co.uk/jlpt/).

## Table of Contents

- [Features](#features)
  - [Backend](#backend)
  - [Frontend](#frontend)
- [Setup](#setup)
  - [Docker Setup](#docker-setup)
  - [Test API](#test-api)
  - [Local Setup](#local-setup)
    - [Laravel API Setup](#laravel-api)
    - [MySQL Database Setup](#database-mySQL)
    - [React App Setup](#react-app)
  - [To Do List](#to-do-list)
- [Ongoing Development](#ongoing-development)

## Features

- Laravel CRUD REST API endpoints for Articles, Lists, Roles, Users and Posts(forum).
- Like, hashtag, and comment functionalities for articles, lists, and posts.
- Full CRUD and search functionality for lists of kanji, radicals, words, and sentences (sentences sourced and linked from the [Tatoeba](https://tatoeba.org) community).
- Japanese language data extracted from XML to CSV using plain PHP and MySQL script, formatted and imported to Laravel via Laravel migrations CLI. Kanjis and words were matched against JLPT levels assigned from [Jonathan Waller's JLPT Resources](http://www.tanos.co.uk/jlpt/).
- Authentication with [Laravel/passport](https://github.com/laravel/passport) and Laravel's Eloquent ORM.
- Text scanning algorithm to find kanjis and words used in user provided article.
- PDFs generation with english meanings for Kanjis and Words based on saved Lists or Article content.
- State management via Redux and custom styling with Bootstrap and CSS.

### Backend

- [laravel-snappy](https://github.com/barryvdh/laravel-snappy) - for PDF generating. PDFs generated using laravel's blade templates structure. [wkhtmltopdf](https://github.com/barryvdh/laravel-snappy#wkhtmltopdf-installation) **wkhtmltopdf is required in order for the laravel-snappy library to work.**
- [passport](https://laravel.com/docs/7.x/passport)
- [laravel helpers](https://github.com/laravel/helpers) - more comfortable customized version of the [original laravel helpers](https://laravel.com/docs/7.x/helpers)
- [laravel/ui](https://laravel.com/docs/7.x/frontend) - scaffolding for the frontend of laravel API landing page. In this project [React](https://reactjs.org/docs/getting-started.html) was used.

### Frontend

- [react-moment](https://github.com/headzoo/react-moment) of [moment](https://www.npmjs.com/package/moment) for date formatting.
- [axios](https://www.npmjs.com/package/axios) for HTTP requesting.
- [react-bootstrap](https://react-bootstrap.github.io/) for components [bootstrap](https://www.npmjs.com/package/bootstrap) for overall styling.
- [react-router-bootstrap](https://github.com/react-bootstrap/react-router-bootstrap) for [react-router](https://github.com/reactjs/react-router) and [react-bootstrap](https://react-bootstrap.github.io/) integration.
- [redux](https://redux.js.org/introduction/getting-started), [redux-thunk](https://www.npmjs.com/package/redux-thunk) and [react-redux](https://www.npmjs.com/package/react-redux) for data access.

## Setup

### Docker Setup

Laravel API served with PHP-FPM for php scripts execution, NGINX handles browser requests, static data as CSS, JS, images and routes PHP requests to PHP-FPM.

Now, in `/processor-api` repository root run:

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

### Test API

Basic requests for testing purposes. Enter laravel api container and register/login:

```bash
curl -X POST http://nginx_webserver/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "test@test.me", "password": "test123"}'

# or save as a variable:
TOKEN=$(curl -s -X POST http://nginx_webserver/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "test@test.me", "password": "test123"}' \
  | jq -r '.accessToken')
```

Use variable:

```bash
curl -X GET http://nginx_webserver/api/articles \
  -H "Authorization: Bearer $TOKEN" \
  | jq

# or single article:
curl -X GET http://nginx_webserver/api/article/5 \
  -H "Authorization: Bearer $TOKEN" \
  | jq
# or single list
curl -X GET http://nginx_webserver/api/list/3 \
  -H "Authorization: Bearer $TOKEN" \
  | jq
# or regular response
curl -X GET http://nginx_webserver/api/list/3/words-pdf \
  -H "Authorization: Bearer $TOKEN"
# or generate pdf and move localy tp test result
curl -X GET http://nginx_webserver/api/list/3/words-pdf \
  -H "Authorization: Bearer $TOKEN" \
  -o words-test.pdf
docker cp laravel_app:/var/www/html/words-test.pdf C:\Users\USER-NAME\Downloads\
```

Test PDF file on a local machine:

```bash
curl -X GET http://nginx_webserver/api/article/5/kanjis-pdf \
  -H "Authorization: Bearer $TOKEN" \
  -o kanjis-test.pdf
docker cp laravel_app:/var/www/html/kanjis-test.pdf C:\Users\USER-NAME\Downloads\
```

### Local Setup

Required

- Fonts supporting Japanese language, for example: `fonts-ipafont-gothic`.
- [Composer](https://github.com/composer/composer) - PHP package manager
- [Node](https://nodejs.org/en/download). - Node js runtime for frontend assets of laravel.
- For PDF generating laravel-snappy to work requires [wkhtmltopdf](https://github.com/barryvdh/laravel-snappy#wkhtmltopdf-installation). Install it as a [composer dependency](https://github.com/KnpLabs/snappy#wkhtmltopdf-binary-as-composer-dependencies) or [manually here](http://wkhtmltopdf.org/downloads.html) into your machine.

Also, possibly paths for wkhtmltopdf will not work, uncomment variables in .env to use needed ones or modify them according needs.

Optional choices for faster setup:

- [Xampp](https://www.apachefriends.org/) - web server Apache, PHP and MariaDB(MySQL).
- [Laravel Herd](https://herd.laravel.com/windows) - dev environment with all you need for laravel development.

#### Laravel API

In `processor-api` directory
Install composer packages:

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

#### Database MySQL

Requirements:

- Make sure your DB Collation is `utf8mb4_general_ci` for Japanese characters support.
- Must assure that MySQL config in my.ini has packets size adjusted `max_allowed_packet=128M` in order for migrations to enable importing bigger sized japanese files into the database.

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

#### React App

In `client` directory
Install node modules:

```bash
npm install
```

Start react app

```bash
npm start
```

### To Do List

- [x] Make demo gifs to showcase main features.
- [ ] Add swagger for Laravel API, add models for swagger to use swagger UI for quick and documented API usage
- [ ] Refactor single component to functional component using latest react hooks in a composition way to have example component.
- [ ] Strengthen authorization with more persistant implementation..
- [ ] Implement react-query for query-based approach of managing server-data facing for frontend cache, refetch, cancel requests after unmount and have control.
- [ ] Create nhk easy news scrapper to get each days news.
- [ ] re-vamp styling, especially for small UI elements like links, buttons with icons, screen spacings.
- [ ] Design a way to offload scanning algorithm from client to server.
- [ ] Create service worker to build queues for scanning algorithm of user texts to find kanjis and words.
- [ ] Create Github Actions for frontend.
- [ ] Create Github Actions for backend.
- [ ] Write E2E tests for frontend.
- [?] Client side PDF customization. Generate on Backend, customize on frontend.
- [?] Cache or Store pre-generated materials like PDFs.
- [ ] Translations manager.
- [ ] API types schema generation with Orvel.
- [ ] PostHog / Sentry (?)
- [ ] Matomo (?)

<!-- Tasks -->

- [x] Migrate from CRA to Vite
- [x] Add Typescript and start using .tsx
- [x] Create storybook for components
- [x] Add ESlint
- [ ] Add Husky for hooks
-

- UI Components:
  - dependency tree:
    - ex: (article->words->kanjis->radicals)
    - ex: in material details page word->kanjis->radicals
    - ex: in lists
  - [x] Button
  - Product card
  - Dropdown
  - Select
  - Input Text
  - Input Checkbox
  - Tabgroup (articles/lists in dashboard)
  - Badge
  - [x] Icon
  - [x] Link
  - Image
  - Spinner
  - Info message
  - Toast Message
  - Tooltip
  - Drawer
  - Modal
  - Table ( tanstack query table?)
  - Layout components Mobile-first approach:
    - Header
    - Footer
    - Main

### Ongoing

- couple /search endpoints doesnt work

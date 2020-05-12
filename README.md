# Japanese Learning Environment

Built using [Laravel](https://laravel.com/docs) for Server API and [React](https://reactjs.org/) for the client side.

Japanese data comes from [Electronic Dictionary Research and Development Group](http://www.edrdg.org/), and are used in conformance with the Group's [licence](http://www.edrdg.org/edrdg/licence.html).

## libraries



### Backend
- [mpdf](https://mpdf.github.io/)
- [jwt-auth](https://github.com/tymondesigns/jwt-auth)

### Frontend
- [react-moment](https://github.com/headzoo/react-moment)

## Setup

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

## MyLar Installer

- [Server Requirements](#requirements)
- [Installing Mylar](#installing-mylar)
- [Configuration](#configuration)

<a name="requirements"></a>
### Requirements

<div class="content-list" markdown="1">
- PHP >= 5.6.4
- OpenSSL PHP Extension
- PDO PHP Extension
- Mbstring PHP Extension
- Tokenizer PHP Extension
- XML PHP Extension
</div>

<a name="installing-mylar"></a>
### Installing Mylar

Make sure you installed [Composer](https://getcomposer.org) and [Yarn](https://yarnpkg.com) before run command below.

#### Install Command

Command:

    composer global require "vietdien2005/mylar-installer"

Create project:

    mylar new myproject

#### Create project via Composer

Command:

    composer create-project --prefer-dist vietdien2005/mylar myproject

<a name="configuration"></a>
### Configuration 

#### Environment

- Development: config file `.env` is `APP_ENV=local` and put all config in .local_env
- Production: config file `.env` is `APP_ENV=production` and put all config in .production_env

#### Database

Create database `mylar` or config database in your file environment

Run:
	
	chmod +x bash/install.sh

	bash/install.sh

#### Nginx

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

#### Login

Go to link `/login` and Login with username `admin@mylar.com` password `12345678`


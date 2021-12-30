# Url Shortener

## Setup

1 - clone the repo  
2 - connect to database (configuration in .env file) -> DATABASE_URL="mysql://root:root@127.0.0.1:3306/studos?serverVersion=5.7"  
doc: https://symfony.com/doc/current/doctrine.html  
example: DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/db_name?serverVersion=5.7"    

3 - run the migrations: #php bin/console doctrine:migrations:migrate    

## How to use the project  

1 - start local server: console #symfony server:start  
2 - go to http://127.0.0.1:8000/type-a-url-here - here you should type a url after the localhost  
example: http://127.0.0.1:8000/https://google.com   
 

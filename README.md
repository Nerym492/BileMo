# BileMo

Project 7 of my application developer course - PHP/Symfony on [Openclassrooms](https://openclassrooms.com/).\
Creation of a web service exposing an API using the symfony framework.

## Informations

*   Symfony 6.3.1
*   PHP 8.2

## Installation

1.  Open a Terminal on the server root or localhost (git bash on Windows).
2.  Run the following command, replacing "FolderName" with the name you want to give to the Project :
    ```sh
    git clone https://github.com/Nerym492/BileMo FolderName
    ```
3.  Install Symfony CLI (https://symfony.com/download) and composer (https://getcomposer.org/download/)
4.  Create an .env.local file at the root of your project.  
   Copy the following line and complete it according to your database :\
   DATABASE\_URL="mysql://**databaseUser**:**password**@127.0.0.1:3306/**databaseName**?serverVersion=8&charset=utf8mb4"
5.  Install the project's back-end dependencies with Composer :
    ```sh
    composer install
    ```
6. Create the folder /config/jwt.
7. Install gitBash if not already installed.(https://git-scm.com/downloads)
8. With gitBash, run the following commands to generate JWT keys :
   ```sh
   openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
   ```
   ```sh
   openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
   ```
9. Copy the following lines in the .env.local file :  
   JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem  
   JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem  
   JWT_PASSPHRASE=**yourPassword**
   <br>  
   Replace the JWT_PASSPHRASE value with the pass phrase you defined in the previous command lines.
10. Launch wamp, mamp or lamp depending on your operating system.
11. Create the database :
     ```sh
     php bin/console doctrine:database:create
     ```
12. Create database tables by applying the migrations :
    ```sh
    php bin/console doctrine:migrations:migrate
    ```
13. Add a base dataset by loading the fixtures :
    ```sh
    php bin/console doctrine:fixtures:load
    ```
14. Start the Symfony Local Web Server :
    ```sh
    symfony server:start
    ```
15. The API is now ready to use ! Documentation is available at : https://127.0.0.1:8000/api/doc

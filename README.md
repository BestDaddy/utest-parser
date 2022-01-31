git clone {URL REPO}

После нужно ввести следующие команды

composer install

copy .env.example .env

php artisan key:generate

php artisan storage:link // для картинок
    

После запускаем докер

docker-compose up -d

Типы парсера:
   1) Парсер для простого формата где есть @ - вопрос # - ответы 
   2) ДарханДала анализ грунта (.xlsx)
   3) Сложный парсер для олимп тестов

API:

    GET: http://localhost/api/types
        RESPONSE: all available parser types
        

    POST: http://localhost/api/parse
        BODY:   file -> file
                parser_type_id -> id of parser types

если есть картинки, они будут в контенте виде ссылки 
![img.png](img.png)

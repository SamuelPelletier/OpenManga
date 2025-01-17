<p align="center">
    <img src="openmanga.jpg" style="height:100px">
</p>

OpenManga is a **PHP Symfony 6** project for store and download mangas.

Installation
------------

Pre-request : 
- Composer
- PHP 8.1 or more
- MySQL 
- Node and Yarn

Command : 
* Modify .env or create .env.local with your environment informations
* use ``symfony console app:install``
* Fill **/public/media/** with your mangas
* use ``symfony console app:import-manga-folder``

Without Apache Server launch ``symfony server:run``

Documentation
-------------

``symfony console app:import-manga-folder``

Import all mangas in /public/media/ folder in OpenManga.  
All mangas name will be rename and remove all incorrect files.  
**Format** : images only in .jpg extension and numbered.

<br>

``symfony console app:import-manga``

Import mangas from a secret source

<br>

``symfony console app:install``

Launch every command necessary to install OpenManga.

Contributing
------------

OpenManga is an Open Source Project and you can help us !  

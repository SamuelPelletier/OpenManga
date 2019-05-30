<p align="center"
    <img src="openmanga.jpg" style="height:100px"><a style="vertical-align:center">
</p>

OpenManga is a **PHP Symfony 4** project for store and download mangas.

Installation
------------

Pre-request : 
- Composer
- PHP 7.1.3 or more
- MySQL 
- Node and Yarn

Command : 
* Modify .env or create .env.local with your environment informations
* use ``php bin/console app:install``
* Fill **/public/media/** with your mangas
* use ``php bin/console app:import-manga-folder``

Without Apache Server launch ``php bin/console server:run``

Documentation
-------------

``php bin/console app:import-manga-folder``

Import all mangas in /public/media/ folder in OpenManga.  
All mangas name will be rename and remove all incorrect files.  
**Format** : images only in .jpg extension and numbered.

<br>

``php bin/console app:import-manga``

Import mangas from a secret source

<br>

``php bin/console app:install``

Launch every command necessary to install OpenManga.

Contributing
------------

OpenManga is an Open Source and you can help us !  
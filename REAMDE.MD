# GÉNÉRATEUR DE DÉCLARATION NOMINATIVE
# NOMINAL DECLARATION GENERATOR

## Présentation / Introduction

Cette application web est un projet qui récupère les données en base de données. Elle conditionne ensuite le retour des informations pour générer un XML normalisé selon le format des déclarations nominatives CAFAT.

This web application is a project that retrieves data from a database. It then processes the information to generate standardized XML according to the CAFAT nominal declarations format.

## Structure / Structure

- `./data` stocke un JSON pour la nomenclature du fichier
- `./declarations` stocke les documents générés
- `./class/tag` contient les classes nommées d'après les tags qu'elles complètent
- `./test` contient les tests nommés d'après les tags. Exécutez directement les fichiers dans votre console
- `./test/app-validator-2.0.xsd` est un fichier pour valider le format XML. Il est utilisé par `test/testXML.php`
- `./SQL` contient les premiers scripts testés sous MySQL Workbench. *Ils ne sont pas utilisés directement par l'application*

- `./data` stores JSON for file nomenclature
- `./declarations` stores generated documents
- `./class/tag` contains classes named after the tags they complete
- `./test` contains tests named after tags. Execute these files directly in your console
- `./test/app-validator-2.0.xsd` is a file for validating XML format. It's used by `test/testXML.php`
- `./SQL` contains initial scripts tested in MySQL Workbench. *They are not directly used by the application*

## Installation / Installation

Ce projet a été développé sous XAMPP. Les paramètres par défaut y sont conservés. Clonez directement ce projet dans le répertoire `htdocs` de votre installation XAMPP.

This project was developed using XAMPP. Default parameters are preserved. Clone this project directly into the `htdocs` directory of your XAMPP installation.

### Base de données / Database

Le script qui génère toute la base de données est le fichier `./comptagest.sql`.

The script that generates the entire database is the file `./comptagest.sql`.

### Connexion à la BD / DB Connection

Les paramètres de connexion à la base de données sont dans le fichier `config/configDb.php`. Ce sont les paramètres **par défaut de XAMPP**.

Database connection parameters are in the file `config/configDb.php`. These are the **default XAMPP parameters**.
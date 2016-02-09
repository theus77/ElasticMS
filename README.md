# ElasticMS
## About
A minimal CMS to manage generic content in order to publish it in several Elasticsearch index (based on Symfony 3, and AdminLTE)


There are 4 differents roles in this CMS:


The author is able to create and edit a document. He is also able to publish a document


The admin is able to do all previous action but also to manage Elasticsearch indexes, such as:
- define index aliases
- create/delete an elasticsearch index

The Webmaster

The Author


## Setup
### Requirements
- bower
- composer
- elasticsearch
- mysql
- npm
- symfony 3

### Installation
Navigate to the root of the project `ElasticMS` and execute the following command:
> composer update

Add the end you will get a list of questions to configure user database and database user. While the user should exist in your mysql environment, you can automatically create the database and schema with the following commands:
>  php bin/console d:d:c
>  php bin/console d:s:c

You should also install the bower plugins:
> bower install

And now we can launch Symfony's build in server:
> php bin/console server:run

//Todo add information about the elasticsearch cluste

And voila, ElasticMS is up and running!
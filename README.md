# Tadabase API
- With the Tadabase REST API you can now easily view your tables, fields and manipulate records using industry standard REST API.

 
## GETTING STARTED 
- To get started you must first generate a new API Token for your app by going to your app settings and selecting API Keys.
- With each request you must send the following 3 headers found in the API Keys settings page.


## Set API Auth

#### open "src/Config.php" in this file set variable like
- apiUrl 	: https://api.tadabase.io/api/v1/ (Baseurl of tadabase API)
- appId  	: "yourAppID" (App ID: ID of the app. Can be found in the URL or in the API Keys page)
- appKey 	: "yourAppKey" (App Key : Key Generated automatically from the API Keys Page)
- appSecret : "yourAppSecret" (App Secret : Secret key generated automatically from the API Keys Page)


## Features

1. Easy to understand, portable and Simple client to access the Tadabase API

## Requires
The cURL PHP library
Tested on PHP 5.4

## Generate composer Autoload files (Type in CMD)
composer dump-autoload

## Usage
First instantiate a Tadabse API Class

```PHP
require_once __DIR__ . '/vendor/autoload.php';

use TadabaseApi\Api;
$tb_api = new Api;
```


## Function

getTableList()  : Get All tables

getTableField() : Get field of table 

getTableFullField() : Get full detail info about field of table 

getRecords() : Get Records of table 

getRecord() : Get Single Record of Table

saveRecord() : Save new record

updateRecord() : Update Record

deleteRecord() : Delete Record

Need more help about function please View "src/Api.php"

```
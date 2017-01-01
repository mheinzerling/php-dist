[![Build Status](https://travis-ci.org/mheinzerling/php-dist.svg?branch=master)](https://travis-ci.org/mheinzerling/php-dist) [![Code Climate](https://codeclimate.com/github/mheinzerling/php-dist/badges/gpa.svg)](https://codeclimate.com/github/mheinzerling/php-dist) [![Test Coverage](https://codeclimate.com/github/mheinzerling/php-dist/badges/coverage.svg)](https://codeclimate.com/github/mheinzerling/php-dist/coverage) [![Issue Count](https://codeclimate.com/github/mheinzerling/php-dist/badges/issue_count.svg)](https://codeclimate.com/github/mheinzerling/php-dist) 

#mheinzerling/dist

Tool to zip and deploy composer web application. 

##Composer
    "require": {
        "mheinzerling/dist": "^3.0.0"
    },
    
##Usage
* backup your LIVE or TEST system you try to set up
* create a deployment descriptor; based on `resources/deploy.json`
* set up the deploy script
  * `vendor\bin\dist.bat upload deploy.prod.json`
  * `[1] Deploy script`
* create a distributable zip file `vendor\bin\dist.bat zip deploy.prod.json`
* upload the zip file
  * `vendor\bin\dist.bat upload deploy.prod.json`
  * `[2] dist.XXXX.zip`
* deploy the zip file
  * `vendor\bin\dist.bat deploy deploy.prod.json`
  * `[1] dist.XXXX.zip`
  * a maintenance flag file will be written to the server that can be checked
  * check the system and type `y` to disable the maintenance mode
    
##Changelog

### 3.0.0
* update to PHP 7.1

### 2.0.0
* update to PHP 7

### 1.2.0
* passive FTP connection

### 1.1.0
* deploy to subPaths

### 1.0.0
* initial version 
# ModelUtils
[![Build Status](https://travis-ci.org/mkorkmaz/model_utils.svg?branch=develop)](https://travis-ci.org/mkorkmaz/model_utils)
[![Latest Stable Version](https://poser.pugx.org/mkorkmaz/model_utils/v/stable)](https://packagist.org/packages/mkorkmaz/model_utils) [![Total Downloads](https://poser.pugx.org/mkorkmaz/model_utils/downloads)](https://packagist.org/packages/mkorkmaz/model_utils) [![Latest Unstable Version](https://poser.pugx.org/mkorkmaz/model_utils/v/unstable)](https://packagist.org/packages/mkorkmaz/model_utils) [![License](https://poser.pugx.org/mkorkmaz/model_utils/license)](https://packagist.org/packages/mkorkmaz/model_utils)


A simple PHP class for validating variable types, fixing, sanitising and setting default values for a model definition encoded as an array. Can be used before inserting or updating documents. 

This class is experimental, the default behaviour won't be changed but probably has some bugs to be fixed and needs to be extended for different type of cases.

This class can be used as a part of a complete ORM/ODM or in some low level database operations scripts.

## Model definition

###\_type: 
To define variable type. Possible values: boolean, integer, float, string, array
###\_input\_type:
To define more specific input type. Possible values: bool, date, time, datetime, timestamp, mail, url, ip, mac_address or regex 
###\_input\_format:
If \_input\_type is set to regex, value of document items will be tested against this expression. 
###\_min\_length:
To define minimum length if string, minimum value if integer or float
###\_max\_length: 
To \define maximum length if string, maximum value if integer or float
###\_required:
To define if required doc item. Not used in the class for now 
###\_index:
To define if document item must be indexed in database. Not used in the class but can be used executing database operations.
###\_default:
To define default value for the document item.
###\_ref:
To define a relation with other documents like foreign key. Not used in the class but can be used executing database operations.
###\_has_many:
To define a relation with other documents that defined as child documents. Not used in the class but can be used executing database operations.

See [test.php](https://github.com/mkorkmaz/model_utils/blob/master/test/test.php) for sample model definition.


## ModelUtils::validate\_doc

You can use it to test the document. Can be used before inserting documents. Validation throws an exception if there is a conflict.


## ModelUtils::fit\_doc\_to\_model

Can be used for fixing and sanitising partial documents. Can be used before updating documents.

## ModelUtils::setting\_model\_defaults

Can be used for fitting partial document according to model definition. Can be be used before both inserting or updating documents.


## Installation

It's recommended that you use [Composer](https://getcomposer.org/) to install ModelUtils.

```bash
$ composer require mkorkmaz/model_utils ">=1.0"
```

This will install ModelUtils and all required dependencies. ModelUtils requires PHP 5.4.0 or newer.

## Usage
```
use ModelUtils\ModelUtils as ModelUtils;

$doc = ModelUtils::fit_doc_to_model($model, $doc);
$doc = ModelUtils::setting_model_defaults($model, $doc);
ModelUtils::validate_doc($model, $doc);
```
See also [test.php](https://github.com/mkorkmaz/model_utils/blob/master/test/test.php)

## Contribute
* Open issue if found bugs or sent pull request.
* Feel free to ask if have any questions.

# ModelUtils
[![Circle CI](https://circleci.com/gh/mkorkmaz/model_utils.svg?style=shield)](https://circleci.com/gh/mkorkmaz/model_utils)
[![Latest Stable Version](https://poser.pugx.org/mkorkmaz/model_utils/v/stable)](https://packagist.org/packages/mkorkmaz/model_utils) [![Total Downloads](https://poser.pugx.org/mkorkmaz/model_utils/downloads)](https://packagist.org/packages/mkorkmaz/model_utils) [![Latest Unstable Version](https://poser.pugx.org/mkorkmaz/model_utils/v/unstable)](https://packagist.org/packages/mkorkmaz/model_utils) [![License](https://poser.pugx.org/mkorkmaz/model_utils/license)](https://packagist.org/packages/mkorkmaz/model_utils)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mkorkmaz/model_utils/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mkorkmaz/model_utils/?branch=master)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/9635e643684c409dbf1c1bf3c3dbc797)](https://www.codacy.com/app/mehmet/model_utils?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=mkorkmaz/model_utils&amp;utm_campaign=Badge_Grade)

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


## ModelUtils::validateDoc

You can use it to test the document. Can be used before inserting documents. Validation throws an exception if there is a conflict.


## ModelUtils::fitDocToModel

Can be used for fixing and sanitising partial documents. Can be used before updating documents.

## ModelUtils::settingModelDefaults

Can be used for fitting partial document according to model definition. Can be be used before both inserting or updating documents.


## Installation

It's recommended that you use [Composer](https://getcomposer.org/) to install ModelUtils.

```bash
$ composer require --prefer-dist mkorkmaz/model_utils "*"
```

This will install ModelUtils and all required dependencies. ModelUtils requires PHP 5.4.0 or newer. crisu83/shortid package need to support short ids.

## Usage
```
use ModelUtils\ModelUtils as ModelUtils;

$doc = ModelUtils::fitDocToModel($model, $doc);
$doc = ModelUtils::settingModelDefaults($model, $doc);
ModelUtils::validateDoc($model, $doc);
```
See also [test.php](https://github.com/mkorkmaz/model_utils/blob/master/test/test.php)

## Contribute
* Open issue if found bugs or sent pull request.
* Feel free to ask if have any questions.

Create new project
==================

Create project folder
---------------------

    mkdir project
    cd project

Download Yii
------------

    composer create-project --prefer-dist yiisoft/yii2-app-basic ./

Update composer.json
------------

    "repositories": [
            {
                "type": "vcs",
                "url": "git@gitlab.provectus-it.com:reinvently/message-queue-php.git"
            }
    ],
    "require": {
            "reinvently/message-queue-php": "dev-master"
    }

Composer
--------

    composer update








Deploy existing project
=======================

Create project folder
---------------------

    mkdir project
    cd project

Composer
--------

    composer self-update
    composer global require "fxp/composer-asset-plugin:1.0.0-beta3"
    composer update

Permissions
-----------

    chmod 777 runtime/ web/assets

Update composer.json
------------

    "repositories": [
        ...,
        {
            "type": "vcs",
            "url": "git@gitlab.provectus-it.com:reinvently/message-queue-php.git"
        }
    ],
    "require": {
        ...,    
        "reinvently/message-queue-php": "dev-master"
    }

Composer
--------

    composer update


Common part
===========

Migration
---------

    yii migrate/create message_queue_migration

Changing migration code:

    class m170808_122932_message_queue_migration extends \reinvently\messagequeue\migrations\MessageMigration
    {
    }
---
    yii migrate
    
    Yii Migration Tool (based on Yii v2.0.9)
    
    Total 1 new migration to be applied:
            m170808_122932_message_queue_migration
    
    Apply the above migration? (yes|no) [no]:y
    *** applying m170808_122932_message_queue_migration
        > create table {{%message}} ... done (time: 0.016s)
        > create unique index userIdUniqueIdentifier on {{%message}} (userId,uniqueIdentifier) ... done (time: 0.020s)
    *** applied m170808_122932_message_queue_migration (time: 0.075s)
    
    
    1 migration was applied.
    
    Migrated up successfully.

Add Message(s)
--------------

    MessageHandler::add(123, static::SOME_MESSAGE_TYPE, ['some' => 'data']);
or

    MessageHandler::addMultiple([123, 124], static::SOME_MESSAGE_TYPE);
    

Api Controller
--------------

Test Controller:

    use reinvently\messagequeue\MessageHandler;
    use yii\rest\Controller;
    
    class TestController extends Controller
    {
        const SOME_MESSAGE_TYPE = 10;
    
        public function actionAddMessage($userId)
        {
            return MessageHandler::add($userId, static::SOME_MESSAGE_TYPE);
        }
    
        public function actionGetNewAndConfirmLastMessage($userId, $confirmMessageId = 0)
        {
            $delay = 10;
            return MessageHandler::getApiResponse($userId, $delay, $confirmMessageId);
        }
    
    }
    
Request 1 

Adding message to user #123:

    /test/add-message?userId=123
Response:
 
    true

Request 2 

Checking new message of user #123:

    /api/test/get-new-and-confirm-last-Message?userId=123
Response:

    {
      "id": "234",
      "type": 10,
      "data": null,
      "delay": 10
    }

Request 3 

Confirming message #234 and checking new message of user #123:

    /api/test/get-new-and-confirm-last-Message?userId=123&confirmMessageId=234
Response:

    {
      "id": null,
      "type": 0,
      "data": null,
      "delay": 10
    }






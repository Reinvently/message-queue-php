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
    
    
    
    1 migration was applied.
    
    Migrated up successfully.

Add Message(s)
--------------

    MessageHandler::add(123, static::SOME_MESSAGE_TYPE, ['some' => 'data']);
or

    MessageHandler::addMultiple([123, 124], static::SOME_MESSAGE_TYPE);
    

Api Controller And Examples
--------------

Test Controller:

    use reinvently\messagequeue\MessageHandler;
    use yii\rest\Controller;
    
    class TestController extends Controller
    {
        const SOME_MESSAGE_TYPE = 10;
    
        public function actionAddMessage($userId, $channel = null)
        {
            return MessageHandler::add($userId, $channel,static::SOME_MESSAGE_TYPE);
        }
    
        public function actionGetNewAndConfirmLastMessage($userId, $channel = null, $confirmMessageId = 0)
        {
            $delay = 10;
            return MessageHandler::getApiResponse($userId, $delay, $channel, $confirmMessageId);
        }
    
        public function actionClearChannelBySubscriber($userId, $channel = null)
        {
            return MessageHandler::clearChannelBySubscriber($userId, $channel);
        }
    
        public function actionClearOutdatedMessages()
        {
            return MessageHandler::clearOutdatedMessages(time());
        }
    
    }
    
**Adding messages**
    
Request: 

    /api/test/add-message?userId=123
Response:
 
    true

Request:

    /api/test/add-message?userId=123&channel=aa
Response:
 
    true

Request:

    /api/test/add-message?userId=123&channel=ab
Response:
 
    true

Request:

    /api/test/add-message?userId=123&channel=ab
Response:
 
    true
    
**Getting and confirming messages**

Request:

    /api/test/get-new-and-confirm-last-message?userId=123&channel=a%
Response:
 
    {
      "id": "5",
      "type": 10,
      "channel": "aa",
      "data": null,
      "delay": 10
    }

Request:

    /api/test/get-new-and-confirm-last-message?userId=123&channel=a%&confirmMessageId=5
Response:
 
    {
      "id": "6",
      "type": 10,
      "channel": "ab",
      "data": null,
      "delay": 10
    }

Request:

    /api/test/get-new-and-confirm-last-message?userId=123&channel=a%&confirmMessageId=6
Response:
 
    {
      "id": null,
      "type": 0,
      "channel": null,
      "data": null,
      "delay": 10
    }

Request:

    /api/test/get-new-and-confirm-last-message?userId=123
Response:
 
    {
      "id": "7",
      "type": 10,
      "channel": null,
      "data": null,
      "delay": 10
    }

Request:

    /api/test/get-new-and-confirm-last-message?userId=123&confirmMessageId=7
Response:
 
    {
      "id": null,
      "type": 0,
      "channel": null,
      "data": null,
      "delay": 10
    }

**Clear channel by subscriber**

Request:

    /api/test/add-message?userId=123
Response:
 
    true

Request:

    /api/test/add-message?userId=123&channel=aa
Response:
 
    true

Request:

    /api/test/add-message?userId=123&channel=b
Response:
 
    true

Request:

    /api/test/clear-channel-by-subscriber?userId=123&channel=a%
Response:
 
    true

Request:

    /api/test/clear-channel-by-subscriber?userId=123
Response:
 
    true
        
**Clear outdated messages**

Request:

    /api/test/clear-outdated-messages
Response:
 
    true





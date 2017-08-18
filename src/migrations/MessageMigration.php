<?php
/**
 * Created by PhpStorm.
 * User: sglushko
 * Date: 07.08.2017
 * Time: 15:22
 */

namespace reinvently\messagequeue\migrations;

use reinvently\messagequeue\Message;
use yii\db\Migration;

class MessageMigration extends Migration
{
    public function up()
    {
        $this->createTable(Message::tableName(), [
            'id' => $this->primaryKey()->unsigned(),
            'subscriberId' => $this->integer()->unsigned()->notNull(),
            'channel' => $this->string()->null(),
            'type' => $this->smallInteger()->unsigned()->notNull(),
            'createdAt' => $this->integer()->unsigned()->notNull(),
            'deleteAfter' => $this->integer()->unsigned()->notNull(),
            'data' => $this->text()->notNull(),
            'uniqueIdentifier' => $this->string()->notNull(),
        ]);

        $this->createIndex('userIdChannelUniqueIdentifier', Message::tableName(), ['subscriberId', 'channel', 'uniqueIdentifier'], true);
        $this->createIndex('deleteAfter', Message::tableName(), ['deleteAfter']);

    }

    public function down()
    {
        $this->dropTable(Message::tableName());
    }
}

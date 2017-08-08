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
            'userId' => $this->integer()->unsigned(),
            'type' => $this->smallInteger()->unsigned(),
            'createdAt' => $this->integer()->unsigned(),
            'data' => $this->text(),
            'uniqueIdentifier' => $this->string(),
        ]);

        $this->createIndex('userIdUniqueIdentifier', Message::tableName(), ['userId', 'uniqueIdentifier'], true);

    }

    public function down()
    {
        $this->dropTable(Message::tableName());
    }
}

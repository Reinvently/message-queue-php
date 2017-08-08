<?php
/**
 * Created by PhpStorm.
 * User: sglushko
 * Date: 07.08.2017
 * Time: 14:07
 */

namespace reinvently\messagequeue;


use yii\db\IntegrityException;
use yii\helpers\Json;

class MessageHandler
{
    const TYPE_NOC = 0;

    /** @var Message */
    static protected $classMessage = Message::class;

    /**
     * @param integer[] $userIds
     * @param integer $type
     * @param array|object|null $params
     * @throws IntegrityException
     */
    static public function addMultiple($userIds, $type, $params = null)
    {
        /** @var Message $message */
        $class = static::getClassMessage();
        $message = new $class();
        $message->type = $type;
        $message->data = Json::encode($params);
        $message->generateUniqueIdentifier();

        $class::deleteAll(
            [
                'userId' => $userIds,
                'hash' => $message->uniqueIdentifier,
            ]
        );
        foreach ($userIds as $userId) {
            static::add($userId, $type, $params);
        }
    }

    /**
     * @return Message
     */
    static public function getClassMessage()
    {
        return static::$classMessage;
    }

    /**
     * @param $userId
     * @param $type
     * @param array|object|null $data
     * @return bool
     */
    static public function add($userId, $type, $data = null)
    {
        if (empty($userId) || empty($type)) {
            return false;
        }

        /** @var Message $message */
        $class = static::getClassMessage();
        $message = new $class;
        $message->userId = $userId;
        $message->createdAt = time();
        $message->type = $type;
        $message->data = Json::encode($data);
        $result = $message->save();

        return $result;
    }

    /**
     * @param integer $userId
     * @param integer $delay
     * @param integer $messageId
     * @return array
     */
    static public function getApiResponse($userId, $delay, $messageId = 0)
    {
        if ($messageId) {
            MessageHandler::confirmByMessageId($messageId, $userId);
        }

        $message = MessageHandler::getMessageByUser($userId, $delay);

        return $message->getItemForApi();
    }

    /**
     * @param integer $id
     * @param integer $userId for secure delete
     * @return bool
     */
    static public function confirmByMessageId($id, $userId)
    {
        $class = static::getClassMessage();
        return (bool)$class::deleteAll(
            'id = :id AND userId = :userId',
            [
                ':id' => $id,
                ':userId' => $userId,
            ]
        );
    }

    /**
     * @param integer $userId
     * @param integer $delay
     * @return message
     */
    static public function getMessageByUser($userId, $delay)
    {
        /** @var Message $message */
        $class = static::getClassMessage();
        $message = $class::find()
            ->where('userId = :userId', [':userId' => $userId])
            ->one();

        if (!$message) {
            $message = new $class;
            $message->type = static::TYPE_NOC;
        }

        $message->delay = $delay;

        return $message;
    }

}
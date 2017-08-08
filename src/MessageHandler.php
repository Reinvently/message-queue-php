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
    public static function addMultiple($userIds, $type, $params = null)
    {
        /** @var Message $message */
        $message = new (static::getClassMessage());
        $message->type = $type;
        $message->data = Json::encode($params);
        $message->generateUniqueIdentifier();

        (static::getClassMessage())::deleteAll(
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
     * @param $userId
     * @param $type
     * @param array|object|null $data
     * @return bool
     */
    public static function add($userId, $type, $data = null)
    {
        if (empty($userId) || empty($type)) {
            return false;
        }

        /** @var Message $message */
        $message = new (static::getClassMessage());
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
    public function getApiResponse($userId, $delay, $messageId = 0)
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
    public static function confirmByMessageId($id, $userId)
    {
        return (bool)(static::getClassMessage())::deleteAll(
            'id = :id AND userId = :userId',
            [
                ':id' => $id,
                ':userId' => $userId,
            ]
        );
    }

    /**
     * @return Message
     */
    static public function getClassMessage()
    {
        return static::$classMessage;
    }

    /**
     * @param integer $userId
     * @param integer $delay
     * @return message
     */
    static public function getMessageByUser($userId, $delay)
    {
        /** @var Message $message */
        $message = (static::getClassMessage())::find()
            ->where('userId = :userId', [':userId' => $userId])
            ->one();

        if (!$message) {
            $message = new (static::getClassMessage());
            $message->type = static::TYPE_NOC;
        }

        $message->delay = $delay;

        return $message;
    }

}
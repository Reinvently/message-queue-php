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
     * @param integer[] $subscriberIds
     * @param string $channel
     * @param integer $type
     * @param array|object|null $params
     * @param integer $deleteAfter
     * @throws IntegrityException
     */
    static public function addMultiple($subscriberIds, $channel, $type, $params = null, $deleteAfter = 0xffffffff)
    {
        /** @var Message $message */
        $class = static::getClassMessage();
        $message = new $class();
        $message->channel = $channel;
        $message->type = $type;
        $message->data = Json::encode($params);
        $message->deleteAfter = $deleteAfter;
        $message->generateUniqueIdentifier();

        $class::deleteAll(
            [
                'subscriberId' => $subscriberIds,
                'hash' => $message->uniqueIdentifier,
            ]
        );
        foreach ($subscriberIds as $subscriberId) {
            static::add($subscriberId, $channel, $type, $params);
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
     * @param $subscriberId
     * @param string $channel
     * @param $type
     * @param array|object|null $data
     * @param integer $deleteAfter
     * @return bool
     */
    static public function add($subscriberId, $channel, $type, $data = null, $deleteAfter = 0xffffffff)
    {
        if (empty($subscriberId) || empty($type)) {
            return false;
        }

        /** @var Message $message */
        $class = static::getClassMessage();
        $message = new $class;
        $message->subscriberId = $subscriberId;
        $message->channel = $channel;
        $message->createdAt = time();
        $message->type = $type;
        $message->data = Json::encode($data);
        $message->deleteAfter = $deleteAfter;
        $result = $message->save();

        return $result;
    }

    /**
     * @param integer $subscriberId
     * @param integer $delay
     * @param string $channel
     * @param integer $messageId
     * @return array
     */
    static public function getApiResponse($subscriberId, $delay, $channel = null, $messageId = 0)
    {
        if ($messageId) {
            MessageHandler::confirmByMessageId($messageId, $subscriberId);
        }

        $message = MessageHandler::getMessageBySubscriber($subscriberId, $delay, $channel);

        return $message->getItemForApi();
    }

    /**
     * @param integer $id
     * @param integer $subscriberId for secure delete
     * @return bool
     */
    static public function confirmByMessageId($id, $subscriberId)
    {
        $class = static::getClassMessage();
        return (bool)$class::deleteAll(
            [
                'id' => $id,
                'subscriberId' => $subscriberId,
            ]
        );
    }

    /**
     * @param integer $subscriberId
     * @param integer $delay
     * @param string $channel
     * @return message
     */
    static public function getMessageBySubscriber($subscriberId, $delay, $channel = null)
    {
        /** @var Message $message */
        $class = static::getClassMessage();
        $messageQuery = $class::find()
            ->where(['subscriberId' => $subscriberId]);

        if ($channel) {
            $messageQuery->andWhere(['like', 'channel', $channel, false]);
        }

        $message = $messageQuery->one();

        if (!$message) {
            $message = new $class;
            $message->type = static::TYPE_NOC;
        }

        $message->delay = $delay;

        return $message;
    }

    /**
     * @param integer $subscriberId
     * @param string $channel
     * @return boolean
     */
    static public function clearChannelBySubscriber($subscriberId, $channel = null)
    {
        /** @var Message $message */
        $class = static::getClassMessage();
        if ($channel) {
            return (bool)$class::deleteAll(
                'subscriberId = :subscriberId AND channel LIKE :channel',
                [
                    ':subscriberId' => $subscriberId,
                    ':channel' => $channel,
                ]
            );
        } else {
            return (bool)$class::deleteAll(
                [
                    'subscriberId' => $subscriberId,
                ]
            );
        }
    }

    /**
     * @param integer $time
     * @return boolean
     */
    static public function clearOutdatedMessages($time)
    {
        /** @var Message $message */
        $class = static::getClassMessage();
        return (bool)$class::deleteAll(
            'deleteAfter < :time',
            [
                ':time' => $time,
            ]
        );
    }

}
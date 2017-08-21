<?php
/**
 * Created by PhpStorm.
 * User: sglushko
 * Date: 07.08.2017
 * Time: 12:41
 */

namespace reinvently\messagequeue;


use yii\db\ActiveRecord;
use yii\db\IntegrityException;
use yii\helpers\Json;

/**
 * @property integer id
 * @property integer subscriberId
 * @property string channel
 * @property integer type
 * @property int createdAt
 * @property int deleteAfter
 * @property string data
 * @property string uniqueIdentifier
 */
class Message extends ActiveRecord
{
    /** @var int */
    public $delay;

    /**
     * @return array
     */
    public function getItemForApi()
    {
        $response = [
            'id' => $this->id,
            'type' => $this->type,
            'channel' => $this->channel,
            'data' => Json::decode($this->data),
            'delay' => $this->delay,
        ];

        return $response;
    }

    /**
     * @param bool $runValidation
     * @param null|string[] $attributeNames
     * @return bool
     * @throws IntegrityException
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        $this->generateUniqueIdentifier();
        try {
            return parent::save($runValidation, $attributeNames);
        } catch (IntegrityException $e) {
            $result = $this->resolveSaveConflict($e, $runValidation, $attributeNames);
            if (!$result) {
                throw $e;
            }
            return true;
        }
    }

    /**
     * Generating unique identifier
     */
    public function generateUniqueIdentifier()
    {
        $this->uniqueIdentifier = $this->type . md5($this->data);
    }

    /**
     * Only if duplicate entry
     * @param $e
     * @param $runValidation
     * @param $attributeNames
     * @return bool
     * @throws IntegrityException
     */
    protected function resolveSaveConflict($e, $runValidation, $attributeNames)
    {
        $duplicateSqlErrorCode = 1062; /* ER_DUP_ENTRY */

        if (isset($e->errorInfo[1]) && $e->errorInfo[1] === $duplicateSqlErrorCode) {
            $this->deleteAll([
                'subscriberId' => $this->subscriberId,
                'channel' => $this->channel,
                'uniqueIdentifier' => $this->uniqueIdentifier,
            ]);
            try {
                return parent::save($runValidation, $attributeNames);
            } catch (IntegrityException $e) {
                if (isset($e->errorInfo[1]) && $e->errorInfo[1] === $duplicateSqlErrorCode) {
                    return true;
                }
                throw $e;
            }
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['createdAt', 'deleteAfter'], 'safe'],
            [['subscriberId', 'createdAt', 'type', 'data'], 'required'],
            [['subscriberId', 'createdAt', 'type'], 'integer'],
            [['uniqueIdentifier', 'channel'], 'string', 'max' => 0xff],
            [['data'], 'string', 'max' => 0xffff],
            [['channel'], 'default', 'value' => ''],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'subscriberId' => 'Subscriber ID',
            'channel' => 'Channel',
            'createdAt' => 'Created At',
            'deleteAfter' => 'Delete After',
            'type' => 'Type',
            'data' => 'Data',
        ];
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: sglushko
 * Date: 11.11.2016
 * Time: 11:51
 */

namespace reinvently\messagequeue;


use yii\db\ActiveRecord;
use yii\db\IntegrityException;
use yii\helpers\Json;

/**
 * @property string $id
 * @property string $userId
 * @property integer $type
 * @property string $createdAt
 * @property string $data
 * @property string $uniqueIdentifier
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
            'data' => Json::decode($this->data),
            'delay' => $this->delay,
        ];

        return $response;
    }

    /**
     * @param bool $runValidation
     * @param null $attributeNames
     * @return bool
     * @throws IntegrityException
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        $this->generateUniqueIdentifier();
        try {
            return parent::save($runValidation, $attributeNames);
        } catch (IntegrityException $e) {
            $this->resolveSaveConflict($e, $runValidation, $attributeNames);
            throw $e;
        }
    }

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
            $this->deleteAll(
                'userId = :userId AND uniqueIdentifier = :uniqueIdentifier',
                [
                    ':userId' => $this->userId,
                    ':uniqueIdentifier' => $this->uniqueIdentifier,
                ]
            );
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
            [['createdAt'], 'safe'],
            [['userId', 'createdAt', 'type'], 'required'],
            [['userId', 'createdAt', 'type'], 'integer'],
            [['uniqueIdentifier'], 'string', 'max' => 0xff],
            [['data'], 'string', 'max' => 0xffff],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'userId' => 'User ID',
            'createdAt' => 'Created At',
            'type' => 'Type',
            'data' => 'Data',
        ];
    }

}
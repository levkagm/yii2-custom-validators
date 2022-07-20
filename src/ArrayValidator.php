<?php

namespace Levkagm\Yii2\Validators;

use Yii;
use yii\base\DynamicModel;
use yii\base\Model;
use yii\validators\Validator;

/**
 * Class ArrayValidator
 * @package Levkagm\Yii2\Validators
 * @author Evgen Levchenko <levkagm@gmail.com>
 *
 * usage:
 *          ...
 * [
 *   'data',
 *   ArrayValidator::class,
 *   'subRules' => [
 *       [['foo', 'bar'], 'required'],
 *       [
 *           'foo',                                         // associative array
 *           ArrayValidator::class,
 *           'subRules' => [
 *               ['id', 'required'],
 *               ['id', 'integer'],
 *           ],
 *       ],
 *       ['bar', 'string'],
 *       [
 *           'payments',                                     // non-associative array
 *           CustomEachValidator::class,
 *           'rule' => [
 *               ArrayValidator::class,
 *               'subRules' => [
 *                   [['type', 'sum'], 'required'],
 *                   ['type', 'in', 'range' => [1, 2, 3]],
 *                   ['sum', 'double'],
 *               ],
 *           ],
 *       ],
 *       [
 *           'client',
 *           ArrayValidator::class,
 *           'subRules' => [                                // using closure inside 'when'. $client - internal validation model
 *               ['email', 'required', 'message' => '[email] cannot be blank when [phone] is blank', 'when' => static function($client) {
 *                   return empty($client->phone);
 *               }],
 *               ['phone', 'required', 'message' => '[phone] cannot be blank when [email] is blank', 'when' => static function($client) {
 *                   return empty($client->email);
 *               }],
 *               ['email', 'email'],
 *               ['phone', PhoneInputValidator::class],
 *               ['email', function($attribute, $params) {  // Using a closure as a validator. $this - internal validation model
 *                   if ($this->$attribute === 'test@example.com') {
 *                       $this->addError("forbidden $attribute value was passed");
 *                   }
 *               }],
 *           ],
 *       ],
 *   ],
 * ]
 *          ...
 */
class ArrayValidator extends Validator
{
    /** @var array */
    public $subRules = [];

    /** @var string */
    public $message;
    
    /** @var ValidationModel */
    protected $validationModel;

    public function init()
    {
        parent::init();
        $this->validationModel = new ValidationModel();

        if ($this->message === null) {
            $this->message = Yii::t('yii', 'The {attribute} argument must be an array');
        }
    }

    /**
     * @param Model $model
     * @param string $attribute
     */
    public function validateAttribute($model, $attribute)
    {
        $this->validationModel->scenario = $model->scenario;
        if ($this->skipOnEmpty && empty($model->$attribute)) {
            return;
        }
        
        if (isset($model->$attribute) && !is_array($model->$attribute)) {
            $this->addError($model, $attribute, $this->message);
            return;
        }

        if ($this->subRules) {
            $this->validationModel->setRules($this->subRules);
            $this->validationModel->setAttributes($model->$attribute);
            $this->validationModel->validate();
        }
        
        if ($this->validationModel->hasErrors()) {
            if ($model instanceof DynamicModel) {
                $model->addErrors($this->validationModel->getErrors());
            } else {
                $model->addErrors([$attribute => $this->validationModel->getErrors()]);
            }
            
            $this->validationModel->clearErrors();
        }
    }
}

<?php


namespace app\common;


use app\common\exceptions\PersistException;
use Yii;
use yii\db\ActiveRecord;

abstract class BaseActiveRecord extends ActiveRecord
{
    public function beforeValidate()
    {
        if($this->isNewRecord) {
            if ($this->hasAttribute('identifier')) {
                $this->identifier = $this->generateIdentifier();
            }

            $country = null;

            if($this->hasAttribute('ip')) {
                if(Yii::$app->request->isConsoleRequest) {
                    $this->setAttribute('ip', "::1");
                }
                else {
                    $this->setAttribute('ip', Yii::$app->request->getUserIP());
                    $country = Helper::getCountryIDFromIP($this->getAttribute('ip'));
                }

            }

            if($this->hasAttribute('useragent')){
                $this->setAttribute('useragent', Yii::$app->request->getUserAgent());
            }

            if($this->hasAttribute('ip_country_id'))
                $this->setAttribute('ip_country_id',$country);



        }
        return parent::beforeValidate();
    }


    public function generateIdentifier(){
        $prefix = 'SYS'; //system prefix

        $table = self::tableName();
        $table = str_replace('{{%','',$table);
        $table = str_replace('}}','',$table);

        $exploded = explode('_',$table);

        if(is_array($exploded) && count($exploded)>0){
            $prefix = "";
            foreach ($exploded as $word){
                $prefix .= substr($word,0,1);
            }
            $prefix = strtoupper($prefix);
        }

        $identifier = Helper::generateRandomKey($prefix);
        $identifier = str_replace(array('&','?','#','@'),'',$identifier);

        //check if unique
        $user = self::find()->where(['identifier'=>$identifier])->one();
        if($user != null){
            return $this->generateIdentifier();
        }
        return $identifier;
    }

    public static function get($id){
        $instance = self::findOne($id);

        return $instance;
    }

    public static function create($config){
        $class = get_called_class();
        $instance = new $class($config);

        if($instance->save())
            return $instance;
        else {
            throw new PersistException($instance);

        }

    }


}

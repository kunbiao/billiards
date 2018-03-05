<?php
Yii::setAlias('@common', dirname(__DIR__));
Yii::setAlias('@frontend', dirname(dirname(__DIR__)) . '/frontend');
Yii::setAlias('@backend', dirname(dirname(__DIR__)) . '/backend');
Yii::setAlias('@console', dirname(dirname(__DIR__)) . '/console');
//计算时间戳
function getMicrotime()
{
    list($usec,$sec) = explode(" ",microtime());
    return round(($usec+$sec) * 1000);
}
<?php

namespace Weijian\AllinPay;

class AllinPayFactory
{
    public static function factory($gateway)
    {
        $className = 'Weijian\Allinpay\Payment\WxPay' . ucfirst($gateway);
        if (class_exists($className)) {
            return new $className;
        } else {
            throw new \Exception('gateway is wrong');
        }
    }
}
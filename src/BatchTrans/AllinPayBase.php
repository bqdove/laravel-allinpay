<?php
namespace Weijian\AllinPay\BatchTrans;
use League\Flysystem\Exception;
class AllinPayBase
{
    protected $config = [];
    protected $values = [];
    protected $api_key;
    protected $need_params = [];
    protected $response;
    public function setValue($key, $value) {
        if (! isset($this->config[$key])) {
            $this->values[$key] = $value;
        }
    }
    public function getValue($key) {
        if (isset($this->values[$key])) {
            return $this->values[$key];
        }
        return null;
    }
    public function getResponse($key = null) {
        if ($key == null) {
            return $this->response;
        }
        return $this->response[$key];
    }
    public function config($config_name) {
        $config = config('wxpay');
        if (! isset($config[$config_name])) {
            // config不存在
            throw new Exception('');
        }
        $config = $config[$config_name];
        $this->config = $config;
        $this->values['appid'] = $config['appid'];
        $this->values['mch_id'] = $config['mch_id'];
        return $this;
    }
    protected function options($options = []) {
        foreach ($options as $key => $value) {
            $this->setValue($key, $value);
        }
    }
    protected function checkParams() {
        foreach ($this->need_params as $param) {
            if (! isset($this->values[$param]) || $this->values[$param] == '') {
                throw new Exception("必传参数{$param}不存在");
            }
        }
    }
    protected function simulationResponse() {
        return [];
    }
    public function getSign($sign_object = null) {
        $sign_object = $sign_object == null ? $this->values : $sign_object;
        ksort($sign_object);
        $pre_sign_str = '';
        foreach ($sign_object as $k => $v){
            $pre_sign_str .= "{$k}={$v}&";
        }
        $pre_sign_str .= "key={$this->api_key}";
        return strtoupper(md5($pre_sign_str));
    }
    public function send() {
        if ($this->config['mode'] != 'live') {
            $return_params = $this->simulationResponse();
            if ($return_params == []) {
                throw new Exception('缺少模拟参数');
            }
            $return_params['sign'] = $this->getSign($return_params);
            $return_str = $this->toXml($return_params);
        } else {
            $xml_str = $this->toXml($this->values);
            $return_str = $this->curl_post($xml_str, $this->url);
        }
        //todo: 调用异步通知
        $response = $this->fromXml($return_str);
        $new_response = new \stdClass();
        $new_response->mode = $this->config['mode'];
        $new_response->payment_response = $response;
        $this->response = $new_response;
        return $this;
    }
    public function isSuccessful() {
        if ($this->response->payment_response->return_code == 'SUCCESS') {
            return true;
        }
        return false;
    }
    protected function curl_post($xml, $url, $useCert = false, $second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $data = curl_exec($ch);
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
        }
    }
    protected function generate_nonce_str($length = 32) {
        // 密码字符集，可任意添加你需要的字符
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        for ( $i = 0; $i < $length; $i++ )
        {
            $password .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        return $password;
    }
    protected function toXml($options) {
        $xml = "<xml>";
        foreach ($options as $k => $v) {
            $xml .= "<$k>$v</$k>";
        }
        $xml .= "</xml>";
        return $xml;
    }
    protected function fromXml($xml_str) {
        libxml_disable_entity_loader(true);
        $obj = simplexml_load_string($xml_str, 'SimpleXMLElement', LIBXML_NOCDATA);
        return json_decode(json_encode($obj));
    }
}
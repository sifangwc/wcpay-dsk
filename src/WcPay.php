<?php
namespace Laowin\WcpaySdk;

/**
 * 使用老旧的写法以兼容php5.6
 */
class WcPay
{
    private $app_key;
    private $app_secret;
    private $gateway;

    private $params = [];

    /**
     * @param $app_key
     * @param $app_secret
     * @param $gateway
     * @throws WcException
     */
    public function __construct($app_key, $app_secret, $gateway)
    {
        if (strpos($gateway, "http") === false) {
            throw new WcException("api gateway must a http url");
        }
        $this->app_key = $app_key;
        $this->app_secret = $app_secret;
        $this->gateway = $gateway;
    }

    /**
     * @param $otn
     * @return $this
     * @throws WcException
     */
    public function SetOtn($otn){
        if (!is_string($otn))
            throw new WcException("otn must be the string val");

        if (strlen($otn) > 32 || strlen($otn) < 16) {
            throw new WcException("otn must be the string val and the length is between 16 and 32 char");
        }

        $this->params['otn'] = $otn;
        return $this;
    }

    /**
     * @param $code
     * @return $this
     * @throws WcException
     */
    public function SetCode($code){
        if (!is_string($code))
            return throw new WcException("the code should be string val");
        $this->params['code'] = $code;
        return $this;
    }

    /**
     * @param $url
     * @return $this
     * @throws WcException
     */
    public function SetNotifyUrl($url){
        if (!is_string($url)) {
            throw new WcException("notify url must be string");
        }
        if (strpos($url, "http") === false) {
            throw new WcException("the notify url should be http link");
        }
        $this->params['notify_url'] = $url;
        return $this;
    }

    /**
     * @param $price
     * @return $this
     * @throws WcException
     */
    public function SetPrice($price){
        if (!is_float($price))
            throw new WcException("Price must be float with 2 digital");

        $this->params['price'] = round($price, 2);
        return $this;
    }

    /**
     * @return array
     * @throws WcException
     */
    protected function GenerateOrderParams(){
        if (!isset($this->params['price'])) {
            throw new WcException("the order price can not be empty, you can use \$pay->SetPrice(\$price) to set the price of this transaction");
        }
        if (!isset($this->params['notify_url'])) {
            throw new WcException("the order notify url can not be empty, you can use \$pay->SetNotifyUrl(\$url) to set the notify url of this transaction");
        }
        if (!isset($this->params['code'])) {
            throw new WcException("the order code can not be empty, you can use \$pay->SetCode(\$url) to set the code of this transaction");
        }
        if (!isset($this->params['otn'])) {
            throw new WcException("the order otn can not be empty, you can use \$pay->SetOn(\$url) to set the otn of this transaction");
        }
        $request_params = $this->params;
        $request_params['app_key'] = $this->app_key;
        $request_params['rand_r'] = md5(microtime(true));
        $request_params['order_time'] = time();
        return $request_params;
    }

    /**
     * @return array
     * @throws WcException
     */
    protected function QueryOrderParams(){
        if (!isset($this->params['otn'])) {
            throw new WcException("the order otn can not be empty, you can use \$pay->SetOn(\$url) to set the otn of this transaction");
        }
        $request_params = $this->params;
        $request_params['app_key'] = $this->app_key;
        $request_params['rand_r'] = md5(microtime(true));
        $request_params['order_time'] = time();
        return $request_params;
    }

    /**
     * @param $app_secret
     * @param $params
     * @return string
     */
    protected function Sign($app_secret, $params) {
        ksort($params);
        unset($params['sign']); // 为了可以复用与回调验签
        $strs = [];
        foreach ($params as $key => $val) {
            $strs[] = "{$key}={$val}";
        }
        $strs[] = "app_secret={$app_secret}";
        return md5(implode("&", $strs)); // here is lower case
    }

    /**
     * @return bool|string
     * @throws WcException
     */
    public function GenerateOrder(){
        $api_url = rtrim($this->gateway, "/") . "/api/open/CreateOrder";
        $params = $this->GenerateOrderParams();
        $params['sign'] = $this->Sign($this->app_secret, $params);
        return $this->curl_post_https($api_url, $params);
    }

    /**
     * @param $otn
     * @return bool|string
     * @throws WcException
     */
    public function QueryOrder($otn) {
        $api_url = rtrim($this->gateway, "/") . "/api/open/QueryOrder";
        $params = $this->QueryOrderParams();
        $params['sign'] = $this->Sign($this->app_secret, $params);
        return $this->curl_post_https($api_url, $params);
    }

    /**
     * @param $notify_params
     * @return bool
     * @throws WcException
     */
    public function CheckNotify($notify_params) {
        if (!is_array($notify_params)) {
            throw new WcException("notify params must be type array, please convert it to array first");
        }
        $sign = $notify_params['sign'];
        if ($this->Sign($this->app_secret, $notify_params) == $sign) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $url
     * @param $data
     * @return bool|string
     */
    protected function curl_post_https($url, $data) { // 模拟提交数据函数
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);              // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);              // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, 'wcpay-sdk-php'); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $tmpInfo = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            echo 'Errno'.curl_error($curl);//捕抓异常
        }
        curl_close($curl); // 关闭CURL会话
        return $tmpInfo; // 返回数据，json格式
    }
}
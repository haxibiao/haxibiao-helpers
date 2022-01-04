<?php

namespace Haxibiao\Helpers\utils;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QPayUtils
{
    protected $client       = null;
    protected $curl_timeout = 30;
    protected $config       = [];

    /**
     * 相关接口文档日志:https://qpay.qq.com/buss/wiki/206/1215
     */
    public function __construct()
    {
        $this->config = config('pay.qq');
        $this->client = new Client(['time_out' => 5]);
    }

    public function userInfo($accessToken, $openID)
    {
        $userInfoUrl = 'https://graph.qq.com/user/get_user_info';
        $response    = $this->client->request('GET', $userInfoUrl, [
            'query' => [
                'access_token'       => $accessToken,
                'openid'             => $openID,
                'oauth_consumer_key' => $this->config['appid'],
            ],
        ]);
        $result = $response->getbody()->getContents();

        return empty($result) ? null : json_decode($result, true);
    }

    public function transfer(array $order)
    {
        $url                    = "https://api.qpay.qq.com/cgi-bin/epay/qpay_epay_b2c.cgi";
        $params                 = Arr::except($this->config, 'api_key');
        $params['nonce_str']    = Str::random(32);
        $params['out_trade_no'] = $order['outBizNo'];
        $params['openid']       = $order['openid'];
        $params['total_fee']    = intval($order['total_fee']);
        $params['memo']         = $order['memo'] ?? null;
        $params['sign']         = $this->generateSign($params, $this->config['api_key']);
        //数组转XML
        $XML      = $this->arrayToXml($params);
        $response = $this->requestUrl($XML, $url);
        //XML转数组
        $result = $this->xmlToArray($response);

        return empty($result) ? null : $result;
    }

    /**
     * 通过CURL请求去请求QQ转账
     */
    private function requestUrl($XML, $url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $XML);
        //设置该属性，在执行curl_exec后返回的才是xml数据
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:text/xml; charset=utf-8"));

        //证书路径
        //pem
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLCERT, base_path('cert/qq/apiclient_cert.pem'));
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLKEY, base_path('cert/qq/apiclient_key.pem'));

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * 根据请求参数计算签名
     */
    private function generateSign($parameter, $apiKey)
    {
        //通过Key排序
        ksort($parameter);
        $signTemp = "";

        //通过ASCII排序将请求参数拼接
        foreach ($parameter as $key => $value) {
            $signTemp = $signTemp . $key . '=' . $value . '&';
        }

        //拼接APIKey
        $signTemp = $signTemp . 'key' . '=' . $apiKey;
        //计算签名
        $sign = strtoupper(md5($signTemp));

        return $sign;
    }

    /**
     * 数组转XML
     */
    private function arrayToXml($arr)
    {
        if (!is_array($arr) || count($arr) == 0) {
            return '';
        }

        $xml = "<xml>";
        foreach ($arr as $key => $val) {

            $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
        }
        $xml .= "</xml>";
        return $xml;
    }

    /**
     * XML转数组
     */
    private function xmlToArray($XML)
    {
        return json_decode(json_encode(simplexml_load_string($XML, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }

    //使用统一支付接口
    public function pay($order)
    {
        //设置接口链接
        $url = "https://qpay.qq.com/cgi-bin/pay/qpay_unified_order.cgi";
        //设置curl超时时间
        $parameters                 = [];
        $parameters["body"]         = $order['description']; //商品描述
        $timeStamp                  = time();
        $out_trade_no               = $this->config['mch_id'] . "$timeStamp";
        $parameters["out_trade_no"] = $out_trade_no; //商品描述
        $parameters["notify_url"]   = $this->config['notify_url'];
        $parameters["total_fee"]    = $order['amount'];
        $parameters["trade_type"]   = $order['trade_type'] ?? 'APP';

        //获取统一支付接口结果
        $xml      = $this->createXml($parameters);
        $response = $this->postXmlCurl($xml, $url, $this->curl_timeout);
        $result   = $this->xmlToArray($response);

        //商户根据实际情况设置相应的处理流程
        if ($result["return_code"] == "FAIL") {
            //商户自行增加处理流程
            echo "通信出错：" . $result['return_msg'] . "<br>";
        } elseif ($result["result_code"] == "FAIL") {
            //商户自行增加处理流程
            echo "错误代码：" . $result['err_code'] . "<br>";
            echo "错误代码描述：" . $result['err_code_des'] . "<br>";
        }
        return $result;
    }

    /**
     *     作用：格式化参数，签名过程需要使用
     */
    public function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            //$buff .= strtolower($k) . "=" . $v . "&";
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar = null;
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }

    /**
     *     作用：生成签名
     */
    public function getSign($Obj)
    {
        foreach ($Obj as $k => $v) {
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        //echo '【string1】'.$String.'</br>';
        //签名步骤二：在string后加入KEY
        $String = $String . "&key=" . $this->config['api_key'];
        //echo "【string2】".$String."</br>";
        //签名步骤三：MD5加密
        $String = md5($String);
        //echo "【string3】 ".$String."</br>";
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        //echo "【result】 ".$result_."</br>";
        return $result_;
    }

    /**
     *     作用：以post方式提交xml到对应的接口url
     */
    public function postXmlCurl($xml, $url, $second = 30)
    {
        //初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, false);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //curl_close($ch);
        //返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error" . "<br>";
            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            curl_close($ch);
            return false;
        }
    }

    /**
     *     作用：打印数组
     */
    public function printErr($wording = '', $err = '')
    {
        print_r('<pre>');
        echo $wording . "</br>";
        var_dump($err);
        print_r('</pre>');
    }

    /**
     *     作用：post请求xml
     */
    public function postXml($parameters)
    {
        $xml            = $this->createXml($parameters);
        $this->response = $this->postXmlCurl($xml, $this->url, $this->curl_timeout);
        return $this->response;
    }

    /**
     *     作用：获取结果，默认不使用证书
     */
    public function getResult($parameters)
    {
        $this->postXml($parameters);
        $this->result = $this->xmlToArray($this->response);
        return $this->result;
    }

    /**
     * 生成接口参数xml
     */
    public function createXml($parameters)
    {
        try {
            //检测必填参数
            if ($parameters["out_trade_no"] == null) {
                throw new Exception("缺少统一支付接口必填参数out_trade_no！" . "<br>");
            } elseif ($parameters["body"] == null) {
                throw new Exception("缺少统一支付接口必填参数body！" . "<br>");
            } elseif ($parameters["total_fee"] == null) {
                throw new Exception("缺少统一支付接口必填参数total_fee！" . "<br>");
            } elseif ($parameters["notify_url"] == null) {
                throw new Exception("缺少统一支付接口必填参数notify_url！" . "<br>");
            } elseif ($parameters["trade_type"] == null) {
                throw new Exception("缺少统一支付接口必填参数trade_type！" . "<br>");
            }
            $parameters["mch_id"]           = $this->config['mch_id']; //商户号
            $parameters["fee_type"]         = "CNY"; //货币类型
            $parameters["spbill_create_ip"] = $this->config['spbill_create_ip']; //终端ip
            $parameters["nonce_str"]        = str_random(32); //随机字符串
            $parameters["sign"]             = $this->getSign($parameters); //签名
            return $this->arrayToXml($parameters);
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function createSimpleXml($parameters)
    {
        $parameters["mch_id"]    = $this->config['mch_id']; //商户号
        $parameters["nonce_str"] = str_random(32); //随机字符串
        $parameters["sign"]      = $this->getSign($parameters); //签名
    }

/**
 * 订单查询接口
 */
    public function orderQuery($parameters)
    {
        $url = "https://qpay.qq.com/cgi-bin/pay/qpay_order_query.cgi";
        //设置curl超时时间
        $curl_timeout = 30;
        try {
            //检测必填参数
            if ($parameters["out_trade_no"] == null &&
                $parameters["transaction_id"] == null) {
                throw new \Exception("订单查询接口中，out_trade_no、transaction_id至少填一个！" . "<br>");
            }
            $parameters["mch_id"]    = $this->config['mch_id']; //商户号
            $parameters["nonce_str"] = str_random(32); //随机字符串
            $parameters["sign"]      = $this->getSign($parameters); //签名
            return $this->arrayToXml($this->parameters);
        } catch (\Exception$e) {
            Log::error($e->getMessage());

        }
    }

/**
 * 退款申请接口
 */
    public function refund($parameters)
    {
        //设置接口链接
        $url = "https://api.qpay.qq.com/cgi-bin/pay/qpay_refund.cgi";
        //设置curl超时时间
        $curl_timeout = 30;

        /**
         * 生成接口参数xml
         */
        if ($parameters["out_trade_no"] == null && $parameters["transaction_id"] == null) {
            throw new \Exception("退款申请接口中，out_trade_no、transaction_id至少填一个！" . "<br>");
        } elseif ($parameters["out_refund_no"] == null) {
            throw new Exception("退款申请接口中，缺少必填参数out_refund_no！" . "<br>");
        } elseif ($parameters["total_fee"] == null) {
            throw new Exception("退款申请接口中，缺少必填参数total_fee！" . "<br>");
        } elseif ($parameters["refund_fee"] == null) {
            throw new Exception("退款申请接口中，缺少必填参数refund_fee！" . "<br>");
        } elseif ($parameters["op_user_id"] == null) {
            throw new Exception("退款申请接口中，缺少必填参数op_user_id！" . "<br>");
        }
        $parameters["mch_id"]    = $this->config['mch_id']; //商户号
        $parameters["nonce_str"] = str_random(32); //随机字符串
        $parameters["sign"]      = $this->getSign($parameters); //签名
        return $this->arrayToXml($parameters);
    }

/**
 * 退款查询接口
 */
    public function refundQuery($parameters)
    {
        $url = "https://qpay.qq.com/cgi-bin/pay/qpay_refund_query.cgi";
        /**
         * 生成接口参数xml
         */
        try {
            if ($parameters["out_refund_no"] == null &&
                $parameters["out_trade_no"] == null &&
                $parameters["transaction_id"] == null &&
                $parameters["refund_id "] == null) {
                throw new \Exception("退款查询接口中，out_refund_no、out_trade_no、transaction_id、refund_id四个参数必填一个！" . "<br>");
            }
            $parameters["mch_id"]    = $this->config['mch_id']; //商户号
            $parameters["nonce_str"] = str_random(32); //随机字符串
            $parameters["sign"]      = $this->getSign($parameters); //签名
            return $this->arrayToXml($parameters);
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function verify($data, $sign = null)
    {
        $data = $this->xmlToArray($data);
        $sign = is_null($sign) ? $data['sign'] : $sign;
        return $this->getSign($data) === $sign ? $data : false;
    }
}
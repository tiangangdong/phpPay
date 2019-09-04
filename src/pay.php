<?php


class Pay
{

    
    private $appid ; //应用id
    private $mch_id ; //商铺id
    private $privatekey ;//秘钥

    public function setBasicConfiguration($appid,$mch_id,$privatekey)
    {
        $this->appid = $appid;
        $this->mch_id = $mch_id;
        $this->privatekey = $privatekey;
    }

    /**
     * 支付接口
     *
     * @param string $body  简介
     * @param int $total    金额 单位分
     * @param string $out_trade_no 订单号
     * @param string $notify_url   支付回调地址
     * @return void
     */
    public function APPWxPay($body, $total, $out_trade_no,$notify_url)
    
    {
        $nonce_str = $this->getNonceStr();
        $data['appid'] = $this->appid; //appid
        $data['body'] = $body; //产品介绍
        $data['device_info'] = 'WEB'; //默认 WEB
        $data['mch_id'] = $this->mch_id; //商户号
        $data['nonce_str'] = $this->getNonceStr(); //随机字符串
        $data['notify_url'] =$notify_url ; //回调地址,用户接收支付后的通知,必须为能直接访问的网址,不能跟参数
        $data['out_trade_no'] = $out_trade_no; //商户订单号,不能重复
        $data['spbill_create_ip'] = '123.12.12.123'; //ip地址 （随便填写）
        $data['total_fee'] = $total ; //金额
        $data['trade_type'] = 'APP'; //支付方式
        $data['sign'] = $this->getSign($data); //签名
        $xml = $this->ToXml($data);
        //curl 传递给微信方
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        //header("Content-type:text/xml");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if (stripos($url, "https://") !== false) {
            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); //严格校验
        }
        //设置header
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_HEADER, false);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, true);
        //传输文件
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if ($data) {
            curl_close($ch);
            //返回成功,将xml数据转换为数组.
            $re = $this->FromXml($data);
            if ($re['result_code'] != 'SUCCESS') {
                return json(['code' => 0, 'msg' => '签名错误']);
            } else {
                //接收微信返回的数据,传给APP!
                $arr = array(
                    'appid' => $this->appid,
                    'noncestr' => $nonce_str,
                    'package' => 'Sign=WXPay',
                    'partnerid' => $this->mch_id,
                    'prepayid' => $re['prepay_id'],
                    'timestamp' => time(),
                );
                //第二次生成签名
                $sign = $this->getSign($arr);
                $arr['sign'] = $sign;
                return json(['code' => 1, 'msg' => '请求成功', 'data' => $arr]);
            }
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            return json(['code' => 0, 'msg' => "curl出错，错误码:$error"]);
        }
    }

    //生成随机字符串
    public function getNonceStr($length = 32)
    {
        $str = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; //62个字符
        $str = str_shuffle($str);
        $str = substr($str, 0, 32);
        return $str;
    }

    public function ToXml($data = array())
    {
        if (!is_array($data) || count($data) <= 0) {
            return '数组异常';
        }
        $xml = "<xml>";
        foreach ($data as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    //生成签名
    private function getSign($params)
    {

        ksort($params); //将参数数组按照参数名ASCII码从小到大排序
        foreach ($params as $key => $item) {
            if (!empty($item)) { //剔除参数值为空的参数
                $newArr[] = $key . '=' . $item; // 整合新的参数数组
            }
        }
        $stringA = implode("&", $newArr); //使用 & 符号连接参数
        $stringSignTemp = $stringA . "&key=" . $this->privatekey; //拼接key
        // key是在商户平台API安全里自己设置的
        $stringSignTemp = MD5($stringSignTemp); //将字符串进行MD5加密
        $sign = strtoupper($stringSignTemp); //将所有字符转换为大写
        return $sign;
    }

    //xml变数组
    public function FromXml($xml)
    {
        if (!$xml) {
            echo "xml数据异常！";
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $data;
    }


    /**
     * 微信支付回调实例
     * @param string $xmlData
     */
    public function wx_notify($xmlData)
    {

        //将xml格式转换为数组
        $data = $this->FromXml($xmlData);
        //用日志记录检查数据是否接受成功，验证成功一次之后，可删除。
        // $file = fopen('./log.txt', 'a+');
        // fwrite($file, var_export($data, true));
        //为了防止假数据，验证签名是否和返回的一样。
        //记录一下，返回回来的签名，生成签名的时候，必须剔除sign字段。
        $sign = $data['sign'];
        unset($data['sign']);
        if ($sign == $this->getSign($data)) {
            //签名验证成功后，判断返回微信返回的
            if ($data['result_code'] == 'SUCCESS') {
                //根据返回的订单号做业务逻辑

              

                //处理完成之后，告诉微信成功结果！
             echo '<xml>
              <return_code><![CDATA[SUCCESS]]></return_code>
              <return_msg><![CDATA[OK]]></return_msg>
              </xml>';
                    exit();
            } //支付失败，输出错误信息
            else {
                $file = fopen('./log.txt', 'a+');
                fwrite($file, "错误信息：" . $data['return_msg'] . date("Y-m-d H:i:s"), time() . "\r\n");
            }
        } else {
            $file = fopen('./log.txt', 'a+');
            fwrite($file, "错误信息：签名验证失败" . date("Y-m-d H:i:s"), time() . "\r\n");
        }
    }

    /**
     * 微信退单 DTG v2 
     * @param string $weixinOrderId  商户订单号
     * @param int $money  退款金额 单位分
     * @param string $order_id 退款订单 自定义
     * @param string $zs1      apiclient_cert.pem  证书的地址路径
     * @param string $zs2      apiclient_key.pem   证书的地址路径
     */
    public function returnOrderId($weixinOrderId,$money,$order_id,$zs1,$zs2)
    {
        //根据order_id 查出微信订单号
       
       

        //申请微信退钱 
        $body = "退款";
      
        $data['appid'] = $this->appid; //appid
        $data['mch_id'] = $this->mch_id; //商户号
        $data['nonce_str'] = $this->getNonceStr(); //随机字符串
        $data['out_refund_no'] = $order_id; //商户退单号,不能重复
        $data['out_trade_no'] = $weixinOrderId;     //商户订单号
        $data['refund_fee'] = $money;
        $data['total_fee'] = $money; //金额
        $data['sign'] = $this->getSign($data); //签名
        $xml = $this->ToXml($data);
        //curl 传递给微信方
        $url = "https://api.mch.weixin.qq.com/secapi/pay/refund";
        //header("Content-type:text/xml");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if (stripos($url, "https://") !== false) {
            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); //严格校验
        }
        //证书
        // $zs1 = CMF_ROOT . "cert/apiclient_cert.pem";
        // $zs2 = CMF_ROOT . "cert/apiclient_key.pem";
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLCERT, $zs1);
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLKEY, $zs2);

        //设置header
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_HEADER, false);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, true);
        //传输文件
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if ($data) {
            curl_close($ch);
            //返回成功,将xml数据转换为数组.
            $re = $this->FromXml($data);
            if ($re['return_code'] != 'SUCCESS') {
                return json(['code' => 0, 'msg' => '签字错误']);
            } else {
                if ($re["result_code"] == 'SUCCESS') {
                        //退款成功

                        return json(['code' => 1, 'msg' => '退款成功']);
                    } else  return json(['code' => 0, 'msg' => $re["err_code_des"]]);
            }
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            return json(['code' => 0, 'msg' => 'curl出错，错误码:$error', 'data' => []]);
        }
    }
}

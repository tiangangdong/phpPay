# <center> 接口文档 
composer require tiangang/phppay

### 使用：
#### 引入文件
use tiangang/phppay as pay;  
***
#### 实例化
$pay = new pay();  
***
#### 设置基础参数
 *$appid 应用id*  
 *$mch_id 商铺id*  
 *$privatekey 秘钥* 
    
$pay->setBasicConfiguration($appid,$mch_id,$privatekey);
*** 
#### app微信支付
 *$body 简介*  
 *$total 金额 单位分*  
 *$out_trade_no 订单号(自定义唯一)*  
 *$notify_url 支付回调地址*
    
 $res =  $pay->APPWxPay($body, $total, $out_trade_no,$notify_url);  
   
 ##### 返回参数判断:
 $res['code'] == 1    成功  
 $res['code'] == 0    失败

***
 #### app微信退款
 *$weixinOrderId 商户订单号*  
 *$money 退款金额 单位分*  
 *$order_id 退款订单 (自定义唯一)*  
 *$zs1 apiclient_cert.pem  证书的地址路径*  
 *$zs2 apiclient_key.pem   证书的地址路径*  
    
 $res = $pay->returnOrderId($weixinOrderId,$money,$order_id,$zs1,$zs2);
  
  ##### 返回参数判断：
  $res['code'] == 1    成功  
  $res['code'] == 0    失败

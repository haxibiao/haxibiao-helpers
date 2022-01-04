# haxibiao/helpers

> haxibiao/helpers 是哈希表内部通用函数和类
> 欢迎大家提交代码或提出建议



## QQ支付接口说明

### 1.qq配置文件路径
>/packages/haxibiao/wallet/config/pay.php

### 2.使用方法
>$order['amount'] = 1;  
$order['description'] = '测试商品';  
$order['trade_type'] = 'NATIVE';  
$q =new  QPayUtils();  
$q->pay($order)
>
如图：
![image-20210706004854830]($https://haxibiao-1251052432.cos.ap-guangzhou.myqcloud.com/images/WechatIMG476.jpeg)
![image-20210706004854830](https://haxibiao-1251052432.cos.ap-guangzhou.myqcloud.com/images/WechatIMG476.jpeg)

### 3.返回参数说明
![image-20210706004854830](https://haxibiao-1251052432.cos.ap-guangzhou.myqcloud.com/images/WechatIMG477.jpeg)

### 4.相关链接   

qq支付文档地址：
https://mp.qpay.tenpay.cn/buss/wiki/38/1203

github参考地址：  
https://github.com/tension/QPay-PHP-SDK

### 5.其他
.env 配置
>
QQ_MCH_ID=1602543245
QQ_OP_USER_ID=1602543245
QQ_OP_USER_PASSWD=C9E95C4F0E7E93E9EE69BBD143E98B1B
WITHDRAW_SERVER_IP=127.0.0.1
QQ_APP_KEY=EDPNvEYmTBs5YceQWgKucQHIB0fhBlAP
QQ_APP_ID=1108307538
>
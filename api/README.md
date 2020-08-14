# 家庭定位接口文档

**接口需要加密请求，请求方法POST/GET**

测试接口可以不加密，修改本地配置，不加密必须使用GET请求


## 一、用户登录

> URL: http://localhost/login.php

请求参数:

参数名 | 说明 | 是否必须
--- | --- | ---
user_id  | 用户唯一id(第三方登录返回的,后期需要和第三方校验) | 是
id_token | 登录token | 是
public_key | RSA公钥 | 是
phone | 电话 | 否
email | 邮箱 | 否
photo_url | 头像地址 | 否
display_name | 昵称 | 否

返回json格式如下：

```
{
    "code":0,
    "message":"success",
    "data":null
}
```

## 二、创建组

> URL: http://localhost/create.php

请求参数:

参数名 | 说明 | 是否必须
--- | --- | ---
owner  | 创建组用户id | 是
name | 组名 | 是

返回json格式如下：
```
{
    "code":0,
    "message":"success",
    "data":{
        "groupId":1,
        "shareCode":"5850H8YO"
    }
}
```

## 三、加入组

> URL: http://localhost/join.php

请求参数:

参数名 | 说明 | 是否必须
--- | --- | ---
userId  | 用户id | 是
shareCode | 群组分享码 | 是

返回json格式如下：
```
{
    "code":0,
    "message":"success",
    "data":{
        "groupId":1,
        "groupName":"test group",
        "owner":"chen",
        "shareCode":"A34G8U3J",
        "members":[
            {
                "userId":"chen",
                "type":1
            },
            {
                "userId":"haha",
                "type":2
            }
        ]
    }
}
```


## 四、查询用户的所有组信息

> URL: http://localhost/query.php

请求参数:

参数名 | 说明 | 是否必须
--- | --- | ---
userId  | 用户id | 是

返回json格式如下：
```
{
    "code":0,
    "message":"success",
    "data":[
        {
            "groupId":1,
            "groupName":"test group",
            "owner":"chen",
            "shareCode":"A34G8U3J",
            "members":[
                {
                    "userId":"chen",
                    "type":1
                },
                {
                    "userId":"haha",
                    "type":2
                }
            ]
        }
    ]
}
```


## 五、删除组

> URL: http://localhost/delete.php

请求参数:
 
参数名 | 说明 | 是否必须
--- | --- | ---
group  | 组id | 是

返回json格式如下：
```
{
    "code":0,
    "message":"success",
    "data":null
}
```


## 六、查询在线用户

> URL: http://localhost/online.php

请求参数:

无

返回json格式如下：
```
{
    "code":0,
    "message":"success",
    "data":[
        {
            "userId":"XXXXXX",
            "publicKey":"XXXXXXXXXXX"
        }
    ]
}
```


---

## 状态码含义

code | 含义
---|---
0 | 成功
1 | 数据为空
2 | 解密参数失败
3 | 签名校验失败
4 | 参数错误
5 | 插入失败
6 | 服务端异常
7 | 用户token验证失败

## 用户类型 type 

type | 含义
---|---
1 | 创建者
2 | 普通用户


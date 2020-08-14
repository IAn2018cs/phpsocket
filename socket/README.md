# 家庭定位 socket 文档

websocket 长连接，通信内容需要加密。

使用json格式信息数据通信。

> ws://localhost:9503

## 1. 客户端to服务端 通信规则

### 1.1 用户连接上线

需要在socket连接成功后向服务器发送。

请求信息：

字段名 | 说明 
--- | --- 
[action](#客户端to服务端action含义)  | 需要为 1
userId | 用户id
publicKey | 用户公钥

格式如下：

```
{
    "action":1,
    "userId":"XXXXXXXXXXXXXXXXXX",
    "publicKey":"XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX"
}
```

### 1.2 请求连接另一个用户

请求信息：

字段名 | 说明 
--- | --- 
[action](#客户端to服务端action含义)  | 需要为 2
otherUserId | 请求连接的用户id

格式如下：

```
{
    "action":2,
    "otherUserId":"XXXXXXXXXXXXXXXXXX"
}
```

### 1.3 聊天通信

请求信息：

字段名 | 说明 
--- | --- 
[action](#客户端to服务端action含义)  | 需要为 3
otherUserId | 接受方用户id
encryptKey | 加密后的AES key
encryptMsg | 加密后消息
fromId | 发送方用户id

格式如下：

```
{
    "action":3,
    "otherUserId":"XXXXXXXXXXXXXXXXXX",
    "encryptKey":"XXXXXXXXXXXXXX",
    "encryptMsg":"XXXXXXXXXXXXXXXXXXXXXXX",
    "fromId":"XXXXXXXXXXXXXXXXXX"
}
```

### 1.4 成功接收信息

请求信息：

字段名 | 说明 
--- | --- 
[action](#客户端to服务端action含义)  | 需要为 4
msgId | 该条消息的id

格式如下：

```
{
    "action":4,
    "msgId":1
}
```

### 1.5 成功接收历史信息

请求信息：

字段名 | 说明 
--- | --- 
[action](#客户端to服务端action含义)  | 需要为 5
msgIds | 多条消息的id，用 `,` 分隔，如 `1,2,3,4`

格式如下：

```
{
    "action":5,
    "msgId":"1,2,3,4"
}
```

### 1.6 请求连接组

请求信息：

字段名 | 说明 
--- | --- 
[action](#客户端to服务端action含义)  | 需要为 6
userId | 用户id
groupId | 群id

格式如下：

```
{
    "action":6,
    "userId":"XXXXXXXXXX",
    "groupId":1
}
```

### 1.7 组聊天通信

请求信息：

字段名 | 说明 
--- | --- 
[action](#客户端to服务端action含义)  | 需要为 7
fromId | 发送方用户id
groupId | 群id
encryptMsg | 加密后消息
members | 成员加密后key集合
userId | 成员用户id
aesKey | 成员加密后AES key

格式如下：

```
{
    "action":7,
    "fromId":"XXXXXXXXXX",
    "groupId":1,
    "encryptMsg":"XXXXXXXXXXXXXXXXXXXX",
    "members":[
        {
            "userId":"XXXXXXXX",
            "aesKey":"XXXXXXXXXXXXXXXX"
        },
        {
            "userId":"XXXXXXXX",
            "aesKey":"XXXXXXXXXXXXXXXX"
        }
    ]
}
```


## 2. 服务端to客户端 通信规则

### 2.1 返回被连接者公钥

返回信息：

字段名 | 说明 
--- | --- 
[action](#服务端to客户端action含义)  |  2
publicKey | 用户公钥

格式如下：

```
{
    "action":2,
    "publicKey":"XXXXXXXXXXXX"
}
```


### 2.2 聊天通信

返回信息：

字段名 | 说明 
--- | --- 
[action](#服务端to客户端action含义)  |  3
aesKey | 加密的AES密钥
msg | 加密的消息
time | 发送时间，单位 ms
ip | 发送方ip
msgId | 消息id
fromId | 发送方用户id
groupId | 组id，为 -1 则说明不是组内信息

格式如下：

```
{
    "action":3,
    "aesKey":"XXXXXXXX",
    "msg":"XXXXXXXXXXXXXXXX",
    "time":1597383879587,
    "ip":"127.0.0.0:5555",
    "msgId":2,
    "fromId":"XXXXXXXXXX",
    "groupId":-1
}
```


### 2.3 返回历史消息

返回信息：

字段名 | 说明 
--- | --- 
[action](#服务端to客户端action含义)  |  4
aesKey | 加密的AES密钥
msg | 加密的消息
time | 发送时间，单位 ms
ip | 发送方ip
msgId | 消息id
fromId | 发送方用户id
groupId | 组id，为 -1 则说明不是组内信息

格式如下：

```
{
    "action":4,
    "msgs":[
        {
            "aesKey":"XXXXXXXX",
            "msg":"XXXXXXXXXXXXXXXX",
            "time":1597383879587,
            "ip":"127.0.0.0:5555",
            "msgId":2,
            "fromId":"XXXXXXXXXX",
            "groupId":-1
        },
        {
            "aesKey":"XXXXXXXX",
            "msg":"XXXXXXXXXXXXXXXX",
            "time":1597383898587,
            "ip":"127.0.0.0:6666",
            "msgId":3,
            "fromId":"XXXXXXXXXX",
            "groupId":2
        }
    ]
}
```


### 2.4 返回群内成员公钥信息

返回信息：

字段名 | 说明 
--- | --- 
[action](#服务端to客户端action含义)  |  5
members | 成员信息集合
userId | 成员用户id
publicKey | 成员用户公钥

格式如下：

```
{
    "action":5,
    "members":[
        {
            "userId":"XXXXXXXX",
            "publicKey":"XXXXXXXXXXXXXXXX"
        },
        {
            "userId":"XXXXXXXX",
            "publicKey":"XXXXXXXXXXXXXXXX"
        }
    ]
}
```


## 客户端to服务端action含义

action | 含义
---|---
1 | 用户连接上线
2 | 请求连接另一个用户
3 | 聊天通信
4 | 成功接收信息
5 | 成功接收历史信息
6 | 请求连接组
7 | 组聊天通信


## 服务端to客户端action含义

action | 含义
---|---
1 | 返回被连接者公钥
2 | 聊天通信
3 | 返回历史消息
4 | 返回组内成员公钥信息
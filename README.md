# 家庭定位 服务端

## 部署方式

1.首先需要在项目目录新建一个叫 `conf` 的文件夹。

2.在刚刚新建的conf文件夹下新建一个 `localconfig.json` 文件，内容格式如下：
```
{
  "host": "数据库ip",
  "account": "数据库账号",
  "password": "数据库密码",
  "database": "连接的数据库名字",
  "port": 数据库端口号,
  "isSecurity": 是否加密 true|false,
  "securityKey": "加密key",
  "projectId": [
    "firebase项目id"
  ]
}
``` 

3.运行 `data/init.sql` ，初始化数据库表

4.运行 `ws_server.php` 脚本，开启websocket监听。
   

---

## 项目结构

```
.
├── composer // 第三方依赖环境
│
├── base // 通用信息，配置文件等
│   ├── SecurityApiController.php
│   └── config.php
│
├── api // 接口
│   ├── base // 接口基类
│   ├── dispatch // 接口分发器，工厂模式
│   ├── imp // 具体接口实现
│   ├── out // 暴露接口文件
│   │   
│   └── README.md
│
├── socket // websocket 脚本
│   ├── SocketBehavior.php
│   ├── ws_server.php // 运行的主程序
│   │
│   └── README.md
│
├── data // 数据库初始化
│   └── init.sql
│
├── conf 本地配置信息
│   └── localconfig.json
│
└── README.md

```


## 数据库表结构

### 1. user_key 用户表

列 | 类型 | 注释
--- | --- | ---  
id | varchar(255) | 主键，用户id
publicKey | varchar(512) | RSA公钥
fd | int | socket 通信id
online | int | 是否在线，默认0，（0:离线，1:在线）

### 2. msg 消息表 暂存消息

列 | 类型 | 注释
--- | --- | ---  
id | int | 主键自增，消息id
user_id | varchar(255) | 接收方用户id
encrypt_key | varchar(255) | 加密后的AES密钥
encrypt_msg | text | 加密后的消息
send_time | bigint | 发送时间
send_ip | varchar(255) | 发送方ip
group_id | int | 来自组id
from_id | varchar(255) | 发送方用户id

### 3. user_group 群组表

列 | 类型 | 注释
--- | --- | ---  
id | int | 主键自增，群组id
owner_id | varchar(255) | 创建者用户id
name | varchar(255) | 组名字
share_code | varchar(255) | 群分享码

### 4. group_member 群组成员表

列 | 类型 | 注释
--- | --- | ---  
group_id | int | 主键1，群组id
user_id | varchar(255) | 主键2，用户id
type | int | 用户类型，（1：创建者，0：普通用户）

### 5. public_key 登录用户校验 公钥暂存

列 | 类型 | 注释
--- | --- | ---  
id | int | 主键
securetoken | text | google公钥
update_timestemp | timestamp | 更新时间
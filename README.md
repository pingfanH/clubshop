# 萤火商城V2.0开源版

#### 项目介绍
萤火商城V2.0，是全新推出的一款轻量级、高性能、前后端分离的电商系统，支持微信小程序 + H5+ 公众号 + APP，前后端源码完全开源，看见及所得，完美支持二次开发，可学习可商用，让您快速搭建个性化独立商城。

    如果对您有帮助，您可以点右上角 “Star” 收藏一下 ，获取第一时间更新，谢谢！

#### 技术特点
* 前后端完全分离 (互不依赖 开发效率高)
* 采用PHP7.4 (强类型严格模式)
* Thinkphp6.0.5（轻量级PHP开发框架）
* Uni-APP（开发跨平台应用的前端框架）
* Ant Design Vue（企业级中后台产品UI组件库）
* RBAC（基于角色的权限控制管理）
* Composer一键引入三方扩展
* 部署运行的项目体积仅30多MB（真正的轻量化）
* 所有端代码开源 (服务端PHP、后台vue端、uniapp端)
* 简约高效的编码风格 (可能是最适合二开的源码)
* 源码中清晰中文注释 (小白也能看懂的代码)

#### 页面展示
![前端展示](https://images.gitee.com/uploads/images/2021/0316/215102_7bcb0802_2166072.png "前端展示.png")
![后台-首页](https://images.gitee.com/uploads/images/2021/0316/215827_7df5251c_2166072.png "后台-首页.png")
![后台-页面设计](https://images.gitee.com/uploads/images/2021/0316/215839_2d4ebccc_2166072.png "后台-页面设计.png")
![后台-编辑商品](https://images.gitee.com/uploads/images/2021/0316/215848_9d54adff_2166072.png "后台-编辑商品.png")
![后台-订单详情](https://images.gitee.com/uploads/images/2021/0316/215855_8606fce3_2166072.png "后台-订单详情.png")

#### 系统演示

- 商城后台演示：https://shop2.yiovo.com/admin/
- 用户名和密码：admin yinghuo
![前端演示二维码](https://images.gitee.com/uploads/images/2021/0316/104516_3778337e_2166072.png "111.png")
- QQ交流群 806461900
#### 源码下载
1. 主商城端（又称后端、服务端，PHP开发 用于管理后台和提供api接口）

    下载地址：https://gitee.com/xany/yoshop2.0

2. 用户端（也叫客户端、前端，uniapp开发 用于生成H5和微信小程序）

    下载地址：https://gitee.com/xany/yoshop2.0-uniapp

2. 后台VUE端（前端工程文件，部署时无需下载，仅前端开发时用到）

    下载地址：https://gitee.com/xany/yoshop2.0-store

    下载地址：https://gitee.com/xany/yoshop2.0-admin

#### 代码风格

* PHP7强类型严格模式
* 严格遵守MVC设计模式 同时具有service层和枚举类enum支持
* 简约整洁的编码风格 绝不冗余一行代码
* 代码注释完整易读性高 尽量保障初级程序员也可读懂 极大提升二开效率
* 不允许直接调用和使用DB类（破坏封装性）
* 不允许使用原生SQL语句 全部使用链式操作（可维护性强）
* 不允许存在复杂SQL查询语句（可维护性强）
* 所有的CURD操作均通过ORM模型类 并封装方法（扩展性强）
* 数据库设计满足第三范式
* 前端JS编码均采用ES6标准

#### 环境要求
- CentOS 7.0+
- Nginx 1.10+
- PHP 7.4
- MySQL 5.7+


#### 安装教程

1. 后端服务端（php）：https://www.yiovo.com/doc/10024
2. 微信小程序端：https://www.yiovo.com/doc/10047

#### 后台地址

- 超管后台：https://www.你的域名.com/admin
- 商户后台：https://www.你的域名.com/store
- 默认的账户密码：admin yinghuo

#### 定时任务
用于自动处理订单状态、优惠券状态、会员等级等
```sh
php think timer start
```

#### 安全&缺陷

如果您碰到安装和使用问题可以查阅[Issue](https://gitee.com/xany/yoshop2.0/issues?state=all)，或者将操作流程和截图详细发出来，我们看到后会给出解决方案。

如果有BUG或者安全问题，我们会第一时间修复。

#### 版权须知

1. 允许个人学习研究使用，支持二次开发，允许商业用途（仅限自运营）。
2. 允许商业用途，但仅限自运营，如果商用必须保留版权信息，望自觉遵守。
3. 不允许对程序代码以任何形式任何目的的再发行或出售，否则将追究侵权者法律责任。


本项目包含的第三方源码和二进制文件之版权信息另行标注。

版权所有Copyright © 2017-2024 By 萤火科技 (https://www.yiovo.com) All rights reserved。






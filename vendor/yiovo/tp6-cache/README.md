## tp6-cache

thinkphp6 缓存类 (二次开发版)

#### 新增方法：

`Cache::ttl(string $name)` 获取缓存剩余有效期(秒)

`Cache::update(string $name, $newVal, bool $isMerge = true)` 更新缓存内容 (该方法区别于set方法，set方法不支持更新内容同时保留缓存剩余有效期)



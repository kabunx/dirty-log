# 模型变动日志

## 使用说明

```php 
$logger = DirtyLogger::init();
$logger->inLog('日志名称')
    ->on('变化模型')
    ->write();
    
$logger->inLog('日志')
    ->on('subject')
    ->by('caser')
    ->changes(['key' => 'value'])
    ->write();
```

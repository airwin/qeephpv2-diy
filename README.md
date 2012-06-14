qeephpv2-diy
============

基于 qeephp v2 的自用修改

## core 部分 ##
1. 增加QBench，用于简单的耗时测试
2. QColl: 增加slice、unshift方法，及对原有values、shift、append方法的增强
3. QContext: 
    + 修正不同应用的routes缓存冲突的bug
    + 支持通过config设定url中controller、action的参数名称
4. QLog: 修改为按天存储文件日志

## cache 部分 ##
1. 操作加入log记录
2. QCache_File、QCache_Memcached、QCache_PHPDataFile增加add方法
3. 修正QCache_Memcached中prefix不生效的bug、增加getStats、getExt方法

## db 部分 ##
1. QDB_Adapter_Abstract:
    + 增加ping方法
    + insert方法增加第四个参数用于支持 on_duplicate_key_update 逻辑
2. QDB_Adapter_Mysql:
    + 修正connect方法$force_new不生效的bug
    + 修正qstr方法对浮点数返回字符串的bug
    + 增加nextID_EX方法，用于自增计数
    + execute方法增强，加入QBench记录
3. 修正QDB_Result_Abstract的fetchObject方法在$return_first为true且无数据时返回错误数据的bug
4. QDB_Select：
    + 修正getPagination方法对于group by语句的查询错误的bug
    + 增加getPagination方法中对页码超界的容错处理
    + 增加 toHashmap 方法
5. QDB_Table:
    + 增加主从数据库支持
    + insert方法支持 on_duplicate_key_update 逻辑

## orm 部分 ##
* QDB_ActiveRecord_Abstract:
    + 修改save方法支持 on_duplicate_key_update 逻辑
    + 增加xCrease方法用于字段增量更新
    + 增加__callStatic方法以支持behavior的静态方法绑定(php5.3+)

## web 部分 ##
1. 修改QRouter中import方法的逻辑以支持config中路由缓存时间的设定
2. 优化QView_Output中构造函数的参数顺序
3. QView_Render_PHP增强，支持arrayAccess接口，修改blocks逻辑(个人定制)

## q.php ##
1. 从v3移植 val/arr/request/get/post/cookie/server/env/get_request_uri/get_request_baseuri/get_request_dir/get_request_pathinfo/is_post/is_ajax/is_flash/get_http_header方法
2. 引入class_tools类，以支持在php5.3以下版本中的get_called_class方法
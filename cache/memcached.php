<?php
/**
 * 定义 QCache_Memcache 类
 * QCache_Memcache使用pecl-memcache或pecl-memcached扩展缓存数据
 * 当pecl-memcached和pecl-memcache同时存在时，以pecl-memcached优先
 * 
 * @link http://qeephp.com/
 * @package cache
 * @author yangyi <yangyi@surveypie.com> 
 */
class QCache_Memcached {
    /**
     * memcached连接句柄
     *
     * @var resource
     */
    protected $_conn;

    /**
     * 使用哪个库连接memcached
     * pecl-memcache或者pecl-memcached
     *
     * @var string
     * @access protected
     */
    protected $_extension;

    /**
     * 默认的缓存服务器
     *
     * @var array
     */
    protected $_default_server = array(
        /**
         * 缓存服务器地址或主机名
         */
        'host' => '127.0.0.1',

        /**
         * 缓存服务器端口
         */
        'port' => '11211',

        /**
         * 权重
         */
        'weight' => 100,
    );

    /**
     * 默认的缓存策略
     *
     * @var array
     */
    protected $_default_policy = array(
        /**
         * 缓存服务器配置，参看$_default_server
         * 允许多个缓存服务器
         */
        'servers' => array(),

        /**
         * 是否压缩缓存数据
         */
        'compressed' => false,

        /**
         * 缓存有效时间
         *
         * 如果设置为 0 表示缓存永不过期
         */
        'life_time' => 900,

        /**
         * 是否使用持久连接, pecl-memcache有效
         */
        'persistent' => false,
        
        /**
         * 默认的key前缀
         */
        'prefix' => '',
    );

    /**
     * 构造函数
     *
     * @param array $policy
     * @access public
     * @return void
     */
    public function __construct(array $policy = array()) {
        $policy = array_merge($this->_default_policy, $policy);
        if (empty($policy['servers'])) {
            //加入动态平台默认的memcached服务器
            $setting = Q::ini('app_config/CONFIG_CACHE_SETTINGS');
            if (isset($setting['QCache_Memcached'])) {
                $policy = array_merge($policy, $setting['QCache_Memcached']);
            } else {
                $policy['servers'][] = $this->_default_server;
            }
        }
        $this->_default_policy = $policy;

        if (extension_loaded('memcached')) {    // pecl-memcached优先
            $this->_extension = 'pecl-memcached';
            $this->_initMemcached();
        } elseif (extension_loaded('memcache')) {
            $this->_extension = 'pecl-memcache';
            $this->_initMemcache();
        } else {
            throw new QCache_Exception('The pecl-memcached or pecl-memcache extension must be loaded before use!');
        }
    }

    /**
     * 初始化连接，使用pecl-memcache
     *
     * @access protected
     * @return void
     */
    protected function _initMemcache() {
        $conn = new Memcache();

        while (list(, $server) = each($this->_default_policy['servers'])) {
            if (empty($server['weight'])) $server['weight'] = 100;
            if (!$conn->addServer($server['host'], $server['port'], $this->_default_policy['persistent'], $server['weight'])) {
                throw new QCache_Exception(sprintf('Connect memcached server [%s:%s] failed!', $server['host'], $server['port']));
            }
        }

        $this->_conn = $conn;
    }

    /**
     * 初始化连接，使用pecl-memcached
     *
     * @access protected
     * @return void
     */
    protected function _initMemcached() {
        $conn = new Memcached();
        $conn->setOption(Memcached::OPT_COMPRESSION, (boolean)$this->_default_policy['compressed']);
        if(!empty($this->_default_policy['prefix']))
        {
            $conn->setOption(Memcached::OPT_PREFIX_KEY, $this->_default_policy['prefix']);
        }

        while (list(, $server) = each($this->_default_policy['servers'])) {
            if (empty($server['weight'])) $server['weight'] = 100;
            if (!$conn->addServer($server['host'], $server['port'], $server['weight'])) {
                throw new QCache_Exception(sprintf('Connect memcached server [%s:%s] failed!', $server['host'], $server['port']));
            }
        }

        $this->_conn = $conn;
    }

    /**
     * 写入缓存
     * [code]
     * $memcache->add('key', 'value', 3600);
     * $memcache->add('key', 'value', array('life_time' => 3600, 'compressed' => true));
     * [/code]
     *
     * @access public
     * @return void
     */
    public function add($key, $value, $policy = 60) {
        if(is_numeric($policy)) {
            $life_time = intval($policy);
        } else {
            $life_time = isset($policy['life_time']) ? $policy['life_time'] : $this->_default_policy['life_time'];
        }

        if ($this->_extension === 'pecl-memcached') {
            QLog::log('QCache_Memcache(d) add: ' . $key, QLog::DEBUG);
            return $this->_conn->add($key, $value, $life_time);
        } else {
            $compressed = isset($policy['compressed']) ? $policy['compressed'] : $this->_default_policy['compressed'];
            $prefix = trim($this->_default_policy['prefix']);
            QLog::log('QCache_Memcache add: ' . $key, QLog::DEBUG);
            return $this->_conn->add($prefix . $key, $value, $compressed ? MEMCACHE_COMPRESSED : 0, $life_time);
        }
    }

    /**
     * 写入缓存
     * [code]
     * $memcache->set('key', 'value', 3600);
     * $memcache->set('key', 'value', array('life_time' => 3600, 'compressed' => true));
     * $memcache->set(array('key1' => 'value1', 'key2' => 'value2'), 3600);
     * $memcache->set(array('key1' => 'value1', 'key2' => 'value2'), array('life_time' => 3600, 'compressed' => true));
     * [/code]
     *
     * @access public
     * @return void
     */
    public function set() {
        $args = func_get_args();
        $params = array();
        if (is_array($args[0])) {
            $params[] = $args[0];
            if (isset($args[1])) {
                if (is_array($args[1])) {
                    $params[] = $args[1];
                } elseif (is_numeric($args[1])) {
                    $params[] = array('life_time' => $args[1]);
                }
            }
        } elseif (is_string($args[0]) AND isset($args[1])) {
            $params[] = array($args[0] => $args[1]);
            if (isset($args[2])) {
                if (is_array($args[2])) {
                    $params[] = $args[2];
                } elseif (is_numeric($args[2])) {
                    $params[] = array('life_time' => $args[2]);
                }
            }
        } else {
            throw new QCache_Exception('Invalid arguments');
        }

        if ($this->_extension === 'pecl-memcached') {
            return call_user_func_array(array($this, '_setMemcached'), $params);
        } else {
            return call_user_func_array(array($this, '_setMemcache'), $params);
        }
    }

    /**
     * 写入缓存，使用pecl-memcache
     *
     * @param array $set
     * @param array $policy
     * @access protected
     * @return boolean
     */
    protected function _setMemcache(array $set, array $policy = array()) {
        $compressed = isset($policy['compressed']) ? $policy['compressed'] : $this->_default_policy['compressed'];
        $life_time = isset($policy['life_time']) ? $policy['life_time'] : $this->_default_policy['life_time'];
        $prefix = trim($this->_default_policy['prefix']);

        while (list($key, $value) = each($set)) {
            QLog::log('QCache_Memcache set: ' . $key, QLog::DEBUG);
            if (!$this->_conn->set($prefix . $key, $value, $compressed ? MEMCACHE_COMPRESSED : 0, $life_time)) return false;
        }
        return true;
    }

    /**
     * 写入缓存，使用pecl-memcached
     *
     * @param array $set
     * @param array $policy
     * @access protected
     * @return boolean
     */
    protected function _setMemcached(array $set, array $policy = array()) {
        $life_time = isset($policy['life_time']) ? $policy['life_time'] : $this->_default_policy['life_time'];
        $expire_time = time() + $life_time;
        QLog::log('QCache_Memcache(d) set: ' . implode(',', array_keys($set)), QLog::DEBUG);
        return $this->_conn->setMulti($set, $expire_time);
    }

    /**
     * 读取缓存
     * [code]
     * $memcache->get('key');
     * $memcache->get(array('key1', 'key2'));
     * [/code]
     *
     * @access public
     * @return void
     */
    public function get($keys) {
        if ($this->_extension === 'pecl-memcached') {
            return call_user_func_array(array($this, '_getMemcached'), array($keys));
        } else {
            return call_user_func_array(array($this, '_getMemcache'), array($keys));
        }
    }

    /**
     * 读取缓存，使用pecl-memcache
     *
     * @param mixed $keys
     * @access protected
     * @return mixed
     */
    protected function _getMemcache($keys) {
        $prefix = trim($this->_default_policy['prefix']);
        if(is_string($keys)) {
            QLog::log('QCache_Memcache get: ' . $keys, QLog::DEBUG);
            $keys = $prefix . $keys;
        } else {
            QLog::log('QCache_Memcache get: ' . implode(',', $keys), QLog::DEBUG);
        }
        return $this->_conn->get($keys);
    }


    /**
     * 读取缓存，使用pecl-memcached
     *
     * @param mixed $keys
     * @access protected
     * @return mixed
     */
    protected function _getMemcached($keys) {
        if (is_array($keys)) {
            QLog::log('QCache_Memcache(d) get: ' . implode(',', $keys), QLog::DEBUG);
            return $this->_conn->getMulti($keys);
        } else {
            QLog::log('QCache_Memcache(d) get: ' . $keys, QLog::DEBUG);
            return $this->_conn->get($keys);
        }
    }

    /**
     * 删除缓存
     * [code]
     * $memcache->remove('key');
     * $memcache->remove(array('key1', 'key2'));
     * [/code]
     *
     * @access public
     * @return void
     */
    public function remove($keys) {
        if (is_array($keys)) {
            while (list(, $key) = each($keys)) {
                $this->remove($key);
            }
        } else {
            if($this->_extension === 'pecl-memcache') {
                QLog::log('QCache_Memcache remove: ' . $keys, QLog::DEBUG);
                $prefix = trim($this->_default_policy['prefix']);
                $keys = $prefix . $keys;
            } else {
                QLog::log('QCache_Memcache(d) remove: ' . $keys, QLog::DEBUG);
            }
            $this->_conn->delete($keys);
        }
    }

    /**
     * 清除所有缓存
     *
     * @access public
     * @return boolean
     */
    public function clean() {
        return false;//not allowed
        return $this->_conn->flush();
    }

    /**
     * 获得连接句柄
     *
     * @access public
     * @return object
     */
    public function getHandle() {
        return $this->_conn;
    }

    /**
     * 获得统计情况
     *
     * @access public
     * @return array
     */
    public function getStats() {
        if($this->_extension === 'pecl-memcache') {
            return $this->_conn->getExtendedStats();
        } else {
            return $this->_conn->getStats();
        }
    }
    
    /**
     * 获得扩展
     *
     * @access public
     * @return array
     */
    public function getExt() {
        return $this->_extension;
    }
}

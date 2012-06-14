<?php
/**
 * 类 QBench 实现了一个简单的执行时间记录服务
 *
 * @author g.airwin@gmail.com
 * @version $Id: bench.php 59959 2012-02-02 08:34:58Z mabiao $
 * @package core
 */
class QBench
{
    /**
     * 堆栈状态
     *
     * @var array
     */
    protected $_open = 0;
    
    /**
     * 哈希缓存
     *
     * @var array
     */
    protected $_hash = array();
    
    /**
     * 堆栈缓存
     *
     * @var array
     */
	protected $_stack = array();
	
	/**
	 * 执行时间记录
	 *
	 * @var array
	 */
	protected $_rec = array();
	
	/**
	 * 钩子
	 *
	 * @var array
	 */
	protected $_hooks = array();

    /**
     * 指示是否已经调用了析构函数
     *
     * @var boolean
     */
    private $_destruct = false;
    
    /**
     * 记录过滤的阈值
     *
     * @var float
     */
    protected $_threshold = 0.01;
    
    /**
     * 过滤列表
     *
     * @var array
     */
    protected $_filterMap = array(
        'sql' => array('USE', 'SET')
    );

    /**
     * 追加日志到缓存
     *
     * @param string $msg
     * @param string $type
     */
    static function bs($msg = null, $type = 'sql', $threshold = 0)
    {
        return self::instance($threshold)->add($msg, $type);
    }
    
    /**
     * 结束一个记录
     *
     */
    static function be($threshold = 0)
    {
        return self::instance($threshold)->add();
    }
    
    /**
     * 单例
     *
     * @return QBench
     */
    static function instance($threshold = 0)
    {
        static $instance;
        if (is_null($instance))
        {
            $instance = new QBench();
        }
        if($threshold > 0)
        {
            $instance->_threshold = $threshold;
        }
        return $instance;
    }
    
    /**
     * 绑定回调
     *
     * @param mixed $hook 回调函数
     * @param string $type 回调类型,add/end
     * @return array
     */
    function hook($hook = null, $type = 'end')
    {
        if(is_callable($hook))
        {
            $this->_hooks[$type][] = $hook;
        }
		return $this;
    }

    /**
     * 追加日志到日志缓存
     *
     * @param string $msg
     * @param string $type
     */
	function add($msg = null, $type = 'sql')
	{
	    if($this->_destruct) return;
        $t = microtime(true);
	    
	    if($this->_open > 0)
	    {
	        if(!is_null($msg))
	        {
	            throw new QException('a bench has been opened, pls call `'.__METHOD__.'()` to close it first');
	        }

	        $last = end($this->_stack);
	        $ut = round($t - $last[2], 4);
	        if($ut >= $this->_threshold && !$this->_checkFilter($last[1], $last[0]))
	        {
	            $hash = md5($last[0] . $last[1] . $t);
	            $this->_hash[$hash] = $ut;
	            $this->_rec[$hash] = array($last[0], $last[1], $ut);
	        }
	        $this->_open--;
	    }
	    else
	    {
	        $this->_open++;
	    }
	    $this->_stack[] = array($type, $msg, $t);
	    foreach ((array) $this->_hooks['add'] as $hook)
	    {
	        call_user_func_array($hook, array($type, $msg, $t));
	    }
		return $this;
	}

	function reset()
	{
		$this->_init();
		return $this;
	}
	
	function _checkFilter($msg, $type)
	{
	    foreach ((array) $this->_filterMap[$type] as $wd)
	    {
	        //if(strtolower(substr($msg, 0, strlen($wd))) == strtolower($wd))
	        if(strncasecmp($msg, $wd, strlen($wd)) == 0)
	        {
	            return true;
	        }
	    }
	    return false;
	}
	
	/**
     * 析构函数
     */
    function __destruct()
    {
        $this->_destruct = true;
        if(!empty($this->_hooks['end']) && !empty($this->_rec))
        {
            $hash = $this->_hash;
            $rec = $this->_rec;
            $total = array_sum($hash);
            array_multisort($hash, SORT_NUMERIC, SORT_DESC, $rec);
            foreach ((array) $this->_hooks['end'] as $hook)
            {
                call_user_func_array($hook, array($total, array_values($rec), array_values($this->_rec), $this->_stack));
            }
        }
		$this->_init();
    }

	/**
     * 初始化数据
     */
	function _init()
	{
		$this->_hooks = array();
		$this->_hash = array();
		$this->_rec = array();
		$this->_stack = array();
		$this->_open = 0;
	}
}

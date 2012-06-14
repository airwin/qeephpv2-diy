<?php
// $Id: element_abstract.php 2011 2009-01-08 18:56:49Z dualface $

/**
 * 定义 QForm_Element_Abstract 类
 *
 * @link http://qeephp.com/
 * @copyright Copyright (c) 2006-2009 Qeeyuan Inc. {@link http://www.qeeyuan.com}
 * @license New BSD License {@link http://qeephp.com/license/}
 * @version $Id: element_abstract.php 2011 2009-01-08 18:56:49Z dualface $
 * @package form
 */

/**
 * 类 QForm_Element_Abstract 是表单元素和群组的基础类
 *
 * @author YuLei Liao <liaoyulei@qeeyuan.com>
 * @version $Id: element_abstract.php 2011 2009-01-08 18:56:49Z dualface $
 * @package form
 */
abstract class QForm_Element_Abstract
{
    /**
     * 属性值
     *
     * @var array
     */
    protected $_attrs = array();

    /**
     * 构造函数
     *
     * @param string $id 元素ID
     * @param array $attrs 属性
     */
    function __construct($id = null, array $attrs = null)
    {
        if (is_array($attrs)) $this->_attrs = $attrs;
        $this->_attrs['id'] = $id;
        $this->_attrs['name'] = $id;
    }

    /**
     * 魔法方法，以便通过对象属性直接访问元素的属性值
     *
     * @code php
     * echo $element->title;
     * @endcode
     *
     * @param string $attr
     *
     * @return mixed
     */
    function __get($attr)
    {
        return $this->get($attr);
    }

    /**
     * 魔法方法，以便通过指定对象属性的方式来修改元素的属性值
     *
     * @param string $attr 属性名
     * @param mixed $value 属性值
     */
    function __set($attr, $value)
    {
        $this->_attrs[$attr] = $value;
    }

    /**
     * 获得属性值，如果属性不存在返回 $default 参数指定的默认值
     *
     * @param string $attr 属性名
     * @param mixed $default 默认值
     *
     * @return mixed 属性值
     */
    function get($attr, $default = null)
    {
        return isset($this->_attrs[$attr]) ? $this->_attrs[$attr] : $default;
    }

    /**
     * 修改属性值
     *
     * @param string $attr 属性名
     * @param mixed $value 属性值
     *
     * @return QForm_Element_Abstract
     */
    function set($attr, $value)
    {
        $this->_attrs[$attr] = $value;
        return $this;
    }

    /**
     * 确定是值元素还是群组，继承类必须覆盖此方法
     *
     * @return boolean
     */
    abstract function isGroup();

    /**
     * 返回所有不是以“_”开头的属性的值
     *
     * @return array
     */
    function attrs()
    {
        $ret = array();
        foreach ($this->_attrs as $attr => $value)
        {
            if ($attr{0} == '_') continue;
            $ret[$attr] = $value;
        }
        return $ret;
    }

    /**
     * 返回所有属性的值
     *
     * @return array
     */
    function allAttrs()
    {
        return $this->_attrs;
    }
}


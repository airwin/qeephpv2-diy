<?php
// $Id: dropdownlist.php 2014 2009-01-08 19:01:29Z dualface $

/**
 * 定义 Control_DropdownList 类
 *
 * @link http://qeephp.com/
 * @copyright Copyright (c) 2006-2009 Qeeyuan Inc. {@link http://www.qeeyuan.com}
 * @license New BSD License {@link http://qeephp.com/license/}
 * @version $Id: dropdownlist.php 2014 2009-01-08 19:01:29Z dualface $
 * @package webcontrols
 */

/**
 * Control_DropdownList 构造一个下拉列表框
 *
 * @author YuLei Liao <liaoyulei@qeeyuan.com>
 * @version $Id: dropdownlist.php 2014 2009-01-08 19:01:29Z dualface $
 * @package webcontrols
 */
class Control_DropdownList extends QUI_Control_Abstract
{
	function render()
	{
        $selected = $this->_extract('selected');
        $value    = $this->_extract('value');
		$items    = $this->_extract('items');
        $key = trim($this->_extract('key'));

        if (strlen($value) && strlen($selected) == 0)
        {
            $selected = $value;
        }

		$out = '<select ';
		$out .= $this->_printIdAndName();
		$out .= $this->_printDisabled();
		$out .= $this->_printAttrs();
		$out .= ">\n";

        foreach ((array)$items as $value => $caption)
        {
			$out .= '<option value="' . htmlspecialchars($value) . '" ';
            if ($value == $selected && strlen($value) == strlen($selected))
            {
                $out .= 'selected="selected" ';
            }
			$out .= '>';
			$out .= htmlspecialchars((is_array($caption) && $key) ? $caption[$key] : $caption);
			$out .= "</option>\n";
		}
		$out .= "</select>\n";

        $caption = $this->_extract('caption');
        if ($caption)
        {
			$attribs = array('for' => $this->id(), 'caption' => $caption, 'class' => $this->_extract('caption_class'));
			$label = Q::control('label', $this->id() . '_label', $attribs);
			$out = $label->render() . "\n" . $out;
        }

        return $out;
	}
}


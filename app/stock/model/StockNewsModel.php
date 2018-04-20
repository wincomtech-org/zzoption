<?php
namespace app\stock\model;

use think\Model;
use think\Db;

/**
* 股票新闻模型
*/
class StockNewsModel extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;
    public function getContentAttr($value)
    {
        return cmf_replace_content_file_url(htmlspecialchars_decode($value));
    }
    public function setContentAttr($value)
    {
        return htmlspecialchars(cmf_replace_content_file_url(htmlspecialchars_decode($value), true));
    }
    public function setCreateTimeAttr($value)
    {
        return strtotime($value);
    }

    public function cateTree($selectId=0, $option='')
    {
        $data = Db::name('stock_news_category')->field(['id','name'])->select()->toArray();

        $options = $this->createOptions($selectId, $option, $data);

        return $options;
    }

    // 选择框 从数据库
    public function createOptions($selectId=0, $option='', $data=[])
    {
        if ($option=='json') {
            return json_encode($data);
        } elseif ($option=='false' || $option===false) {
            return $data;
        }
        $options = (empty($option)) ? '':'<option value="">--'.$option.'--</option>';
        if (is_array($data)) {
            foreach ($data as $v) {
                $options .= '<option value="'.$v['id'].'" '.($selectId==$v['id']?'selected':'').'>'.$v['name'].'</option>';
            }
        }
        return $options;
    }
}
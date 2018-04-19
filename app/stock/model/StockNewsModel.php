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

    public function cateTree($selectId=0, $option='请选择')
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



    /**
     * 定向采集
     * 暂时只考虑获取第一页数据
     * @param  string  $url     [目标网站]
     * @param  string  $pattern [匹配规则]
     * @param  string  $source  [来源]
     * @param  integer $cate_id [分类ID：cmf_stock_news_category]
     * @param  string  $extra   [额外参数：按每天]
     * @return [type]           [description]
     */
    public function creeper($url, $pattern, $source, $cate_id=0, $extra=[])
    {
        $status = true;
        $content = cmf_curl_get($url);
        $content = lothar_transCoding($content);
// dump($content);die;
        if ($content) {
            list($rule, $rule2, $rule3, $rule4, $rule5) = $pattern;
// dump($rule);die;
            // 正则匹配
            preg_match($rule, $content, $body);
// dump($body);die;
            if (!empty($body)) {
                preg_match_all($rule2, $body[0], $list);
// dump($list);die;
                if (!empty($list)) {
                    $post = $links = [];

                    foreach ($list[0] as $row) {
                        preg_match_all($rule3, $row, $links);
// dump($links);die;
                        if (!empty($links)) {
                            $detail_url = $links[1][0];
                            // 新浪财经 出现跳转
                            // $detail_url = str_replace('?tj=fina','',$links[1][0]);
                            $detail = cmf_curl_get($detail_url);
// dump($detail);die;
                            preg_match($rule4, $detail, $ds);
                            unset($ds[0]);
// dump($ds);die;
                            if (!empty($ds)) {
                                // $source['name'] = strstr($ds[2],'：');
                                $create_time = strtotime(cmf_strip_chars($links[3][0], $rule5));
                                if (isset($extra['time'])) {
                                    if ($create_time>$extra['time']) {
                                        $post[] = $this->format_data($cate_id,$source,$detail_url,$links[2][0],$create_time,$ds[3]);
                                    }
                                } else {
                                    $post[] = $this->format_data($cate_id,$source,$detail_url,$links[2][0],$create_time,$ds[3]);
                                }
                            } else {
                                // dump($ds);
                                // dump($detail);
                                // dump($rule4);
                                // dump($links);die;
                            }
                            // dump($links);
                            // dump($post);
                            // die;
                        }
                    }
// dump($post);
// exit('ok');
                    // 第一种：TRUNCATE cmf_stock_news;
                    // 第二种：更新时需要与数据库中的数据作比较，排除已有的
                    // 第三种：按时间来获取
                    $result = $this->insertAll($post);
                    // var_dump($result);
                    return $result;
                }

            }
        }

        return 0;
    }

    public function gather($data,$pattern)
    {
        # code...
    }

    public function format_data($cate_id,$source,$detail_url,$title,$create_time,$content)
    {
        $data = [
            'cate_id'  => $cate_id,
            'type'        => $source['type'],
            'source'      => $source['name'],
            'link'        => $detail_url,
            'title'       => $title,
            'create_time' => $create_time,
            'content'     => htmlspecialchars(cmf_replace_content_file_url(htmlspecialchars_decode(trim($content)), true)),
        ];

        return $data;
    }
}
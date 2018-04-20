<?php
namespace stock;

/**
 * 采集数据
 */
class Creeper
{

    public function __construct()
    {
        # code...
    }

    /**
     * 定向采集 gather
     * 暂时只考虑获取第一页数据
     * @param  string  $url     [目标网站]
     * @param  string  $pattern [匹配规则]
     * @param  string  $source  [来源]
     * @param  integer $cate_id [分类ID：cmf_stock_news_category]
     * @param  string  $extra   [额外参数：按每天]
     * @return [type]           [description]
     */
    public function creeper($url, $pattern, $source, $cate_id = 0, $extra = [])
    {
        $content = cmf_curl_get($url);
        $content = lothar_transCoding($content);

        if ($content) {
            list($rule, $rule2, $rule3, $rule4, $rule5) = $pattern;

            // 正则匹配
            preg_match($rule, $content, $body);

            if (!empty($body)) {
                preg_match_all($rule2, $body[0], $list);

                if (!empty($list)) {
                    $post = $links = [];

                    foreach ($list[0] as $row) {
                        preg_match_all($rule3, $row, $links);

                        if (!empty($links)) {
                            $detail_url = $links[1][0];
                            // 新浪财经 出现跳转
                            // $detail_url = str_replace('?tj=fina','',$links[1][0]);
                            $detail = cmf_curl_get($detail_url);

                            preg_match($rule4, $detail, $ds);
                            unset($ds[0]);

                            if (!empty($ds)) {
                                // $source['name'] = strstr($ds[2],'：');
                                $create_time = strtotime(cmf_strip_chars($links[3][0], $rule5));
                                if (isset($extra['time'])) {
                                    if ($create_time > $extra['time']) {
                                        $post[] = $this->format_data($cate_id, $source, $detail_url, $links[2][0], $create_time, $ds[3]);
                                    }
                                } else {
                                    $post[] = $this->format_data($cate_id, $source, $detail_url, $links[2][0], $create_time, $ds[3]);
                                }
                            }
                        }
                    }

                    $result = $this->insertAll($post);
                    return $result;
                }

            }
        }

        return 0;
    }

    // 'content'     => strip_tags(str_replace("&nbsp;"," ",htmlspecialchars_decode($content))),
    public function format_data($cate_id, $source, $detail_url, $title, $create_time, $content)
    {
        $data = [
            'cate_id'     => $cate_id,
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

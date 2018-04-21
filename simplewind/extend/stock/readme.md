/**
 * 获取股票实时价格
 * Sina股票数据接口
 * 接口：
    http://hq.sinajs.cn/list=sh601003,sh601001
    http://hq.sinajs.cn/list=sh601006
 * 这个url会返回一串文本，例如：
    var hq_str_sh601006="大秦铁路, 27.55, 27.25, 26.91, 27.55, 26.20, 26.91, 26.92, 22114263, 589824680, 4695, 26.91, 57590, 26.90, 14700, 26.89, 14300, 26.88, 15100, 26.87, 3100, 26.92, 8900, 26.93, 14230, 26.94, 25150, 26.95, 15220, 26.96, 2008-01-11, 15:05:32";
    这个字符串由许多数据拼接在一起，不同含义的数据用逗号隔开了，按照程序员的思路，顺序号从0开始。
        0：”大秦铁路”，股票名字；
        1：”27.55″，今日开盘价；
        2：”27.25″，昨日收盘价；
        3：”26.91″，当前价格；
        4：”27.55″，今日最高价；
        5：”26.20″，今日最低价；
        6：”26.91″，竞买价，即“买一”报价；
        7：”26.92″，竞卖价，即“卖一”报价；
        8：”22114263″，成交的股票数，由于股票交易以一百股为基本单位，所以在使用时，通常把该值除以一百；
        9：”589824680″，成交金额，单位为“元”，为了一目了然，通常以“万元”为成交金额的单位，所以通常把该值除以一万；
        10：”4695″，“买一”申请4695股，即47手；
        11：”26.91″，“买一”报价；
        12：”57590″，“买二”
        13：”26.90″，“买二”
        14：”14700″，“买三”
        15：”26.89″，“买三”
        16：”14300″，“买四”
        17：”26.88″，“买四”
        18：”15100″，“买五”
        19：”26.87″，“买五”
        20：”3100″，“卖一”申报3100股，即31手；
        21：”26.92″，“卖一”报价
        (22, 23), (24, 25), (26,27), (28, 29)分别为“卖二”至“卖四的情况”
        30：”2008-01-11″，日期；
        31：”15:05:32″，时间；
 */
/*
 * 获取创业板、深圳、上海股票大盘指数
 * 大盘 - S内盘指数
 * 接口：http://hq.sinajs.cn/list=
 * 参数含义
    sh00开头：
        var hq_str_s_sh000001="上证指数,3094.668,-128.073,-3.97,436653,5458126";
        数据含义分别为：0指数名称，1当前点数、2当前价格、3涨跌率、4成交量（手）、5成交额（万元）
    sh60开头的：
        var hq_str_s_sh600000="浦发银行,11.770,0.270,2.35,287482,33608";
        参数含义：0指数名称、1当前价格、2涨跌、3涨跌率、4成交量、5成交额
 * 获取单个
    string(60) "var hq_str_s_sh000001="上证指数,3190.3216,0.0000,0.00,0,0";
    "
 * 获取多个
    string(168) "var hq_str_s_sh000001="上证指数,3190.3216,0.0000,0.00,0,0";
    var hq_str_s_sz399001="深证成指,0.00,0.000,0.00,0,0";
    var hq_str_s_sz399006="创业板指,0.00,0.000,0.00,0,0";
    "
 */

/**
 * 阿里
 * SS.ESA,SZ.ESA
    ["fields"] => array(45) {
        [0] => string(4) "iopv"
        [1] => string(14) "current_amount"
        [2] => string(7) "last_px"
        [3] => string(9) "vol_ratio"
        [4] => string(11) "dyn_pb_rate"
        [5] => string(9) "amplitude"
        [6] => string(11) "min5_chgpct"
        [7] => string(7) "wavg_px"
        [8] => string(9) "prod_name"
        [9] => string(15) "shares_per_hand"
        [10] => string(15) "debt_fund_value"
        [11] => string(12) "market_value"
        [12] => string(3) "bps"
        [13] => string(6) "amount"
        [14] => string(14) "turnover_ratio"
        [15] => string(12) "entrust_rate"
        [16] => string(12) "entrust_diff"
        [17] => string(18) "circulation_amount"
        [18] => string(17) "circulation_value"
        [19] => string(3) "eps"
        [20] => string(11) "prev_amount"
        [21] => string(11) "preclose_px"
        [22] => string(11) "market_date"
        [23] => string(7) "high_px"
        [24] => string(6) "low_px"
        [25] => string(15) "business_amount"
        [26] => string(14) "business_count"
        [27] => string(16) "business_balance"
        [28] => string(7) "open_px"
        [29] => string(7) "bid_grp"
        [30] => string(9) "offer_grp"
        [31] => string(12) "trade_status"
        [32] => string(14) "data_timestamp"
        [33] => string(5) "up_px"
        [34] => string(7) "down_px"
        [35] => string(18) "business_amount_in"
        [36] => string(19) "business_amount_out"
        [37] => string(10) "w52_low_px"
        [38] => string(11) "w52_high_px"
        [39] => string(9) "px_change"
        [40] => string(14) "px_change_rate"
        [41] => string(10) "trade_mins"
        [42] => string(12) "total_shares"
        [43] => string(7) "pe_rate"
        [44] => string(22) "business_balance_scale"
    }
 * XBHS.HY
    ["fields"] => array(26) {
        [0] => string(7) "last_px"
        [1] => string(11) "min5_chgpct"
        [2] => string(7) "wavg_px"
        [3] => string(10) "rise_count"
        [4] => string(10) "fall_count"
        [5] => string(12) "member_count"
        [6] => string(11) "preclose_px"
        [7] => string(7) "high_px"
        [8] => string(6) "low_px"
        [9] => string(15) "business_amount"
        [10] => string(14) "rise_first_grp"
        [11] => string(14) "fall_first_grp"
        [12] => string(16) "business_balance"
        [13] => string(7) "open_px"
        [14] => string(14) "data_timestamp"
        [15] => string(9) "px_change"
        [16] => string(14) "px_change_rate"
        [17] => string(10) "trade_mins"
        [18] => string(14) "up_limit_count"
        [19] => string(20) "touch_up_limit_count"
        [20] => string(17) "st_up_limit_count"
        [21] => string(23) "st_touch_up_limit_count"
        [22] => string(16) "down_limit_count"
        [23] => string(22) "touch_down_limit_count"
        [24] => string(19) "st_down_limit_count"
        [25] => string(25) "st_touch_down_limit_count"
      }
 */

# zzoption
个股期权


定时任务
### 定时测试
# */1 * * * * /usr/bin/wget -q http://106.14.74.155/task.php
### 每1分钟触发一次获取股票指数的方法
*/1 * * * * /usr/bin/wget -q -O - -b http://zzoption.wincomtech.cn/portal/time/indice
### 每天1点钟执行获取股票代码
* 1 * * * /usr/bin/wget -q -O - -b http://zzoption.wincomtech.cn/portal/time/stock_list
### 每日8:30、12:30、19:30爬新闻数据
30 8,12,19 * * * /usr/bin/wget -q -O - -b -T 10800 http://zzoption.wincomtech.cn/portal/time/news.html

portal/stockz下定时操作
/* 定时执行订单,把未持仓的订单过期掉,把持仓的订单判断是否可行权,每日2点执行
		清除用户密码锁定
     * //所有询价成功没确认买入的,过期 
     *  //已付款的，要返回权利金,并改为买入失败,短信提醒
     *  //把持仓的订单改为可以行权
     *  */ 
    public function order_old(){
    portal/stockz/order_old
    
    
     /* 判断订单是否要过期,下午2点执行，发送短信通知 */
    public function sell_notice(){
    portal/stockz/sell_notice
    
      /* 判断订单是否今日过期,下午2点30执行，发送短信通知 */
    public function sell_old(){
     portal/stockz/sell_old
     
     
      /* 订单今日过期,自动行权,下午2点50执行，发送短信通知 */
    public function sell_auto(){
      portal/stockz/sell_auto
      
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
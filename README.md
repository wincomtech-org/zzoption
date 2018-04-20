# zzoption
个股期权


定时任务
// */1 * * * * /usr/bin/wget -q http://106.14.74.155/task.php
*/5 * * * * /usr/bin/wget -q http://zzoption.wincomtech.cn/portal/time/indice
* 1 * * * /usr/bin/wget -q http://zzoption.wincomtech.cn/portal/time/stock_list
30 8,12,19 * * * /usr/bin/wget -q http://zzoption.wincomtech.cn/portal/time/news.html
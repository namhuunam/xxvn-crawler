# xxvn-crawler
```bash
composer config repositories.xxvn-crawler vcs https://github.com/namhuunam/xxvn-crawler.git
```
```bash
composer require namhuunam/xxvn-crawler
```
```bash
php artisan vendor:publish --provider="namhuunam\XxvnCrawler\XxvnCrawlerServiceProvider" --tag="config"
```
Sử dụng
Chạy lệnh sau để bắt đầu crawl dữ liệu:
```bash
php artisan xxvn:crawl --fromPage=300 --toPage=1
```
Các tùy chọn
--fromPage: Trang bắt đầu (mặc định: 300)
--toPage: Trang kết thúc (mặc định: 1)
--limit: Giới hạn số phim xử lý
--sleep: Thời gian nghỉ giữa các lần xử lý phim (mặc định: 1 giây)
Cấu hình
Bạn có thể tùy chỉnh package trong file config/xxvn-crawler.php:

Cấu hình API
Tùy chọn xử lý hình ảnh
Cấu hình logging
Yêu cầu
PHP >= 7.3
Laravel 8.x
Intervention/Image
Spatie/Image-Optimizer
Chạy migration để đảm bảo cột update_identity đã tồn tại:
```bash
php artisan migrate
```
# Chạy lại lệnh crawl với các log chi tiết
```bash
php artisan xxvn:crawl --fromPage=1 --toPage=1 -v
```
Bạn cũng có thể giới hạn số lượng phim để thử nghiệm:
```bash
php artisan xxvn:crawl --fromPage=2 --toPage=1 --limit=5
```
# Cấu hình cron
## Bước 1: Chỉnh sửa file Kernel.php
Đầu tiên, bạn cần mở file app/Console/Kernel.php trong dự án Laravel của bạn và thêm lịch trình cho lệnh xxvn:crawl trong phương thức schedule():
```bash
<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Thêm dòng này để chạy lệnh crawl mỗi 15 phút
        $schedule->command('xxvn:crawl --fromPage=1 --toPage=1')
                 ->everyFifteenMinutes()
                 ->appendOutputTo(storage_path('logs/crawler-schedule.log')); // Log output
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
```
## Bước 2: Cấu hình Cron
Tiếp theo, bạn cần đảm bảo rằng scheduler của Laravel được chạy mỗi phút. Thêm entry sau vào crontab của máy chủ:
```bash
* * * * * cd /đường/dẫn/đến/dự/án && php artisan schedule:run >> /dev/null 2>&1
```
## Bước 3: Kiểm tra log
Sau khi đã thiết lập, bạn có thể kiểm tra file log để xác nhận rằng lệnh đang chạy đúng:
```bash
tail -f storage/logs/crawler-schedule.log
```

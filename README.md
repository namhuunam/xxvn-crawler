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

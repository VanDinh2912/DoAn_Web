# Đồ án Quản lý Quán Bida

## 1. Giới thiệu
Đây là dự án quản lý quán bida, xây dựng bằng PHP, MySQL, HTML, CSS, JS. Dự án hỗ trợ quản lý bàn, dịch vụ, hóa đơn, khách hàng và các chức năng cơ bản cho một quán bida.

## 2. Yêu cầu hệ thống
- VertrigoServ (hoặc XAMPP, WAMP, ...)
- PHP >= 7.x
- MySQL
- Trình duyệt web hiện đại

## 3. Hướng dẫn cài đặt và vận hành

### Bước 1: Cài đặt và chạy VertrigoServ
- Tải VertrigoServ từ trang chủ: http://vertrigo-serv.com/
- Cài đặt theo hướng dẫn mặc định (Next liên tục cho đến khi hoàn thành).
- Sau khi cài đặt xong, mở VertrigoServ bằng cách nhấp vào biểu tượng Vertrigo trên Desktop hoặc Start Menu.
- Khi VertrigoServ chạy, bạn sẽ thấy biểu tượng chữ V màu xanh ở góc dưới bên phải màn hình (system tray).
- Đảm bảo cả hai dịch vụ **Apache** và **MySQL** đều có trạng thái "Running" (màu xanh). Nếu chưa, nhấn nút Start tương ứng.
- Để kiểm tra, mở trình duyệt và truy cập: [http://localhost/](http://localhost/) — Nếu hiện trang chào mừng Vertrigo là thành công.

### Bước 2: Cài đặt cơ sở dữ liệu
- Mở phpMyAdmin (thường tại http://localhost/phpmyadmin)
- Tạo database mới tên `bida`
- Import file `sql/database_bida.sql` vào database vừa tạo

### Bước 3: Cấu hình kết nối CSDL
- Mở file `config/db.php`
- Kiểm tra và chỉnh sửa thông tin kết nối cho phù hợp:
  - `$host = 'localhost';`
  - `$db = 'bida';`
  - `$user = 'root';` (hoặc tài khoản MySQL của bạn)
  - `$pass = '';` (mật khẩu MySQL)

### Bước 4: Chạy dự án
- Copy toàn bộ thư mục `DOAN_WED` vào thư mục `www` của VertrigoServ (hoặc `htdocs` nếu dùng XAMPP)
- Truy cập trình duyệt và mở địa chỉ: `http://localhost/DOAN_WED/index.php`

## 4. Cấu trúc thư mục
- `assets/` : Tài nguyên tĩnh (css, js, img)
- `config/` : Cấu hình hệ thống, kết nối CSDL
- `includes/` : Các thành phần giao diện lặp lại
- `modules/` : Xử lý logic nghiệp vụ
- `sql/` : File sao lưu CSDL
- `login.php` : Trang đăng nhập
- `logout.php` : Đăng xuất
- `index.php` : Trang chủ

## 5. Tài khoản mẫu
- Tài khoản đăng nhập quản lý: (xem trong CSDL bảng `users` hoặc hỏi người phát triển)

## 6. Ghi chú
- Nếu gặp lỗi kết nối CSDL, kiểm tra lại thông tin trong `config/db.php`.
- Nếu có thắc mắc, liên hệ người phát triển hoặc giảng viên hướng dẫn.

---
Chúc bạn sử dụng thành công!

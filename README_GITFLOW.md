# Quy trình Git Flow cho dự án Quản lý Quán Bida

## 1. Mục đích
Quy trình Git Flow giúp quản lý mã nguồn hiệu quả, đảm bảo phát triển, kiểm thử và triển khai dự án diễn ra mạch lạc, rõ ràng.

## 2. Các nhánh chính
- **main**: Chứa mã nguồn ổn định, đã kiểm thử và sẵn sàng triển khai.
- **develop**: Nhánh phát triển chính, tích hợp các tính năng mới trước khi hợp nhất vào main.
- **feature/**: Nhánh phát triển tính năng mới, tách ra từ develop. Ví dụ: `feature/quanly_ban`.
- **hotfix/**: Nhánh sửa lỗi khẩn cấp trên main. Ví dụ: `hotfix/fix_login_bug`.
- **release/**: Nhánh chuẩn bị phát hành, kiểm thử tổng thể trước khi merge vào main.

## 3. Quy trình làm việc
### Bắt đầu dự án
1. Khởi tạo repository và tạo nhánh develop từ main:
   ```sh
   git checkout -b develop main
   ```

### Thêm tính năng mới
1. Tạo nhánh tính năng:
   ```sh
   git checkout -b feature/ten_tinh_nang develop
   ```
2. Phát triển, commit code lên nhánh feature.
3. Khi hoàn thành, merge vào develop:
   ```sh
   git checkout develop
   git merge feature/ten_tinh_nang
   git branch -d feature/ten_tinh_nang
   ```

### Chuẩn bị phát hành
1. Tạo nhánh release:
   ```sh
   git checkout -b release/v1.0 develop
   ```
2. Kiểm thử, sửa lỗi nhỏ trên nhánh release.
3. Merge vào main và develop:
   ```sh
   git checkout main
   git merge release/v1.0
   git checkout develop
   git merge release/v1.0
   git branch -d release/v1.0
   ```

### Sửa lỗi khẩn cấp
1. Tạo nhánh hotfix từ main:
   ```sh
   git checkout -b hotfix/ten_loi main
   ```
2. Sửa lỗi, commit và merge vào main và develop:
   ```sh
   git checkout main
   git merge hotfix/ten_loi
   git checkout develop
   git merge hotfix/ten_loi
   git branch -d hotfix/ten_loi
   ```

## 4. Quy tắc commit
- Viết commit message ngắn gọn, rõ ràng.
- Ví dụ: `Thêm chức năng đăng nhập`, `Sửa lỗi hiển thị bàn`.

## 5. Lưu ý

## 6. Thêm remote cho dự án
Để kết nối dự án với repository trên GitHub, GitLab, Bitbucket...

1. Tạo repository trên nền tảng bạn muốn (ví dụ: GitHub).
2. Thêm remote vào dự án:
   ```sh
   git remote add origin <url-repo-cua-ban>
   ```
   Ví dụ:
   ```sh
   git remote add origin https://github.com/tenuser/tenrepo.git
   ```
3. Đẩy nhánh lên remote lần đầu:
   ```sh
   git push -u origin main
   ```
4. Để kiểm tra remote:
   ```sh
   git remote -v
   ```

---
Áp dụng Git Flow giúp dự án phát triển chuyên nghiệp, dễ bảo trì và mở rộng.
# API Documentation - Quản lý Quán Bida

## Cấu trúc Backend CRUD

Tất cả các module đã được triển khai với cấu trúc sau:
- `controller.php` - Chứa class Controller với các phương thức CRUD
- `api.php` - Định tuyến và xử lý HTTP requests

## Cách gọi API

### Base URL
```
http://localhost/DoAn_Web/modules/{module_name}/api.php
```

### Định dạng Request
Tất cả các API endpoint đều hỗ trợ:
- **GET**: Lấy dữ liệu
- **POST**: Tạo hoặc cập nhật hành động phức tạp
- **PUT**: Cập nhật dữ liệu
- **DELETE**: Xóa dữ liệu

Header cần thiết:
```
Content-Type: application/json
```

---

## 1. Quản lý Bàn (quanly_ban)

### 1.1 Lấy tất cả bàn
```
GET /modules/quanly_ban/api.php?action=getAll

Response:
{
  "success": true,
  "data": [
    {
      "MaBan": 1,
      "TenBan": "Bàn 1",
      "TrangThai": "Trống",
      "MaLoaiBan": 1,
      "GiaGio": 50000,
      "MaKhuVuc": 1,
      "TenLoai": "Standard",
      "TenKhuVuc": "Khu vực A"
    }
  ]
}
```

### 1.2 Lấy bàn theo ID
```
GET /modules/quanly_ban/api.php?action=getById&id=1

Response:
{
  "success": true,
  "data": { /* chi tiết bàn */ }
}
```

### 1.3 Lấy bàn theo khu vực
```
GET /modules/quanly_ban/api.php?action=getByKhuVuc&maKhuVuc=1

Response:
{
  "success": true,
  "data": [ /* danh sách bàn trong khu vực */ ]
}
```

### 1.4 Tạo bàn mới
```
POST /modules/quanly_ban/api.php?action=create

Body:
{
  "TenBan": "Bàn 5",
  "TrangThai": "Trống",
  "MaLoaiBan": 1,
  "GiaGio": 50000,
  "MaKhuVuc": 1
}

Response:
{
  "success": true,
  "message": "Tạo bàn thành công",
  "id": 5
}
```

### 1.5 Cập nhật bàn
```
PUT /modules/quanly_ban/api.php?action=update&id=1

Body:
{
  "TenBan": "Bàn 1 - VIP",
  "GiaGio": 60000,
  "MaKhuVuc": 2
}

Response:
{
  "success": true,
  "message": "Cập nhật bàn thành công"
}
```

### 1.6 Cập nhật trạng thái bàn
```
POST /modules/quanly_ban/api.php?action=updateStatus&id=1

Body:
{
  "status": "Đang sử dụng"
}

Response:
{
  "success": true,
  "message": "Cập nhật trạng thái thành công"
}
```

### 1.7 Xóa bàn
```
DELETE /modules/quanly_ban/api.php?action=delete&id=1

Response:
{
  "success": true,
  "message": "Xóa bàn thành công"
}
```

### 1.8 Lấy danh sách loại bàn
```
GET /modules/quanly_ban/api.php?action=getLoaiBan

Response:
{
  "success": true,
  "data": [
    {
      "MaLoaiBan": 1,
      "TenLoai": "Standard",
      "PhuThu": 0
    }
  ]
}
```

### 1.9 Thêm loại bàn
```
POST /modules/quanly_ban/api.php?action=createLoaiBan

Body:
{
  "TenLoai": "VIP",
  "PhuThu": 20000
}

Response:
{
  "success": true,
  "message": "Tạo loại bàn thành công",
  "id": 2
}
```

### 1.10 Lấy danh sách khu vực
```
GET /modules/quanly_ban/api.php?action=getKhuVuc

Response:
{
  "success": true,
  "data": [
    {
      "MaKhuVuc": 1,
      "TenKhuVuc": "Tầng trệt",
      "PhuThu": 0
    }
  ]
}
```

### 1.11 Thêm khu vực
```
POST /modules/quanly_ban/api.php?action=createKhuVuc

Body:
{
  "TenKhuVuc": "Phòng lạnh",
  "PhuThu": 10000
}

Response:
{
  "success": true,
  "message": "Tạo khu vực thành công",
  "id": 3
}
```

---

## 2. Quản lý Khách Hàng (quanly_khachhang)

### 2.1 Lấy tất cả khách hàng
```
GET /modules/quanly_khachhang/api.php?action=getAll

Response:
{
  "success": true,
  "data": [
    {
      "MaKhachHang": 1,
      "HoTen": "Nguyễn Văn A",
      "SDT": "0901234567",
      "DiemTichLuy": 100,
      "Hang": "VIP",
      "IsStaff": 0
    }
  ]
}
```

### 2.2 Lấy khách hàng theo ID
```
GET /modules/quanly_khachhang/api.php?action=getById&id=1

Response:
{
  "success": true,
  "data": { /* chi tiết khách hàng */ }
}
```

### 2.3 Tìm khách hàng theo số điện thoại
```
GET /modules/quanly_khachhang/api.php?action=searchByPhone&phone=0901234567

Response:
{
  "success": true,
  "data": [ /* danh sách khách hàng */ ]
}
```

### 2.4 Lấy khách hàng theo hạng
```
GET /modules/quanly_khachhang/api.php?action=getByHang&hang=VIP

Response:
{
  "success": true,
  "data": [ /* danh sách khách hàng VIP */ ]
}
```

### 2.5 Tạo khách hàng mới
```
POST /modules/quanly_khachhang/api.php?action=create

Body:
{
  "HoTen": "Trần Văn B",
  "SDT": "0987654321",
  "DiemTichLuy": 0,
  "Hang": "Thường",
  "IsStaff": 0
}

Response:
{
  "success": true,
  "message": "Tạo khách hàng thành công",
  "id": 5
}
```

### 2.6 Cập nhật khách hàng
```
PUT /modules/quanly_khachhang/api.php?action=update&id=1

Body:
{
  "HoTen": "Nguyễn Văn A (Updated)",
  "Hang": "VIP",
  "DiemTichLuy": 150
}

Response:
{
  "success": true,
  "message": "Cập nhật khách hàng thành công"
}
```

### 2.7 Cộng điểm tích lũy
```
POST /modules/quanly_khachhang/api.php?action=addDiem&id=1

Body:
{
  "diem": 50
}

Response:
{
  "success": true,
  "message": "Cộng điểm thành công"
}
```

### 2.8 Xóa khách hàng
```
DELETE /modules/quanly_khachhang/api.php?action=delete&id=1

Response:
{
  "success": true,
  "message": "Xóa khách hàng thành công"
}
```

---

## 3. Quản lý Dịch Vụ/Món (quanly_dichvu)

### 3.1 Lấy tất cả dịch vụ
```
GET /modules/quanly_dichvu/api.php?action=getAll

Response:
{
  "success": true,
  "data": [
    {
      "MaMon": 1,
      "TenMon": "Cà phê đen",
      "GiaBan": 15000,
      "MaDanhMuc": 1,
      "TenDanhMuc": "Đồ uống",
      "DonViTinhMaDVT": 1,
      "TenDVT": "Cốc",
      "SoLuongTon": 50,
      "TrangThai": 1
    }
  ]
}
```

### 3.2 Lấy dịch vụ theo ID
```
GET /modules/quanly_dichvu/api.php?action=getById&id=1

Response:
{
  "success": true,
  "data": { /* chi tiết dịch vụ */ }
}
```

### 3.3 Lấy dịch vụ theo danh mục
```
GET /modules/quanly_dichvu/api.php?action=getByDanhMuc&maDanhMuc=1

Response:
{
  "success": true,
  "data": [ /* danh sách dịch vụ trong danh mục */ ]
}
```

### 3.4 Tìm kiếm dịch vụ theo tên
```
GET /modules/quanly_dichvu/api.php?action=search&keyword=cà phê

Response:
{
  "success": true,
  "data": [ /* danh sách dịch vụ tìm được */ ]
}
```

### 3.5 Tạo dịch vụ mới
```
POST /modules/quanly_dichvu/api.php?action=create

Body:
{
  "TenMon": "Trà xanh",
  "GiaBan": 12000,
  "MaDanhMuc": 1,
  "DonViTinhMaDVT": 1,
  "SoLuongTon": 100
}

Response:
{
  "success": true,
  "message": "Tạo dịch vụ thành công",
  "id": 10
}
```

### 3.6 Cập nhật dịch vụ
```
PUT /modules/quanly_dichvu/api.php?action=update&id=1

Body:
{
  "TenMon": "Cà phê đen - Special",
  "GiaBan": 18000,
  "SoLuongTon": 60
}

Response:
{
  "success": true,
  "message": "Cập nhật dịch vụ thành công"
}
```

### 3.7 Cập nhật số lượng tồn
```
POST /modules/quanly_dichvu/api.php?action=updateSoLuong&id=1

Body:
{
  "soLuong": 40
}

Response:
{
  "success": true,
  "message": "Cập nhật số lượng thành công"
}
```

### 3.8 Xóa dịch vụ (Soft delete)
```
DELETE /modules/quanly_dichvu/api.php?action=delete&id=1

Response:
{
  "success": true,
  "message": "Xóa dịch vụ thành công"
}
```

---

## 4. Quản lý Hóa Đơn (quanly_hoadon)

### 4.1 Lấy tất cả hóa đơn
```
GET /modules/quanly_hoadon/api.php?action=getAll

Response:
{
  "success": true,
  "data": [
    {
      "MaHoaDon": 1,
      "NgayLap": "2024-01-15 10:30:00",
      "MaBan": 1,
      "TenBan": "Bàn 1",
      "MaNhanVien": 1,
      "TenNhanVien": "Admin hệ thống",
      "MaKhachHang": 1,
      "TenKhachHang": "Nguyễn Văn A",
      "TongTien": 75000,
      "TrangThai": 0,
      "NgayKetThuc": null,
      "TienGioLuyKe": 0
    }
  ]
}
```

### 4.2 Lấy hóa đơn theo ID (với chi tiết)
```
GET /modules/quanly_hoadon/api.php?action=getById&id=1

Response:
{
  "success": true,
  "data": {
    "MaHoaDon": 1,
    /* ...thông tin hóa đơn... */
    "ChiTiet": [
      {
        "MaChiTietHD": 1,
        "MaHoaDon": 1,
        "MaMon": 1,
        "TenMon": "Cà phê đen",
        "SoLuong": 3,
        "DonGia": 15000,
        "TenDVT": "Cốc",
        "GhiChu": null
      }
    ]
  }
}
```

### 4.3 Lấy hóa đơn đang phục vụ của bàn
```
GET /modules/quanly_hoadon/api.php?action=getByBan&maBan=1

Response:
{
  "success": true,
  "data": { /* hóa đơn đang mở của bàn (nếu có) */ }
}
```

### 4.4 Tạo hóa đơn mới
```
POST /modules/quanly_hoadon/api.php?action=create

Body:
{
  "MaBan": 1,
  "MaNhanVien": 1,
  "MaKhachHang": 1,
  "MaKhuyenMai": null,
  "TongTien": 0,
  "TrangThai": 0,
  "TienGioLuyKe": 0
}

Response:
{
  "success": true,
  "message": "Tạo hóa đơn thành công",
  "id": 10
}
```

### 4.5 Thêm dịch vụ vào hóa đơn
```
POST /modules/quanly_hoadon/api.php?action=addChiTiet&id=1

Body:
{
  "MaMon": 1,
  "SoLuong": 3,
  "DonGia": 15000,
  "GhiChu": "Không đường"
}

Response:
{
  "success": true,
  "message": "Thêm dịch vụ thành công",
  "id": 5
}
```

### 4.6 Cập nhật chi tiết hóa đơn
```
PUT /modules/quanly_hoadon/api.php?action=updateChiTiet&id=5

Body:
{
  "SoLuong": 4,
  "GhiChu": "Không đường, ít đá"
}

Response:
{
  "success": true,
  "message": "Cập nhật chi tiết thành công"
}
```

### 4.7 Xóa chi tiết hóa đơn
```
DELETE /modules/quanly_hoadon/api.php?action=deleteChiTiet&id=5

Response:
{
  "success": true,
  "message": "Xóa chi tiết thành công"
}
```

### 4.8 Cập nhật thông tin hóa đơn
```
PUT /modules/quanly_hoadon/api.php?action=update&id=1

Body:
{
  "MaKhachHang": 2,
  "MaKhuyenMai": 1
}

Response:
{
  "success": true,
  "message": "Cập nhật hóa đơn thành công"
}
```

### 4.9 Hoàn thành hóa đơn
```
POST /modules/quanly_hoadon/api.php?action=complete&id=1

Response:
{
  "success": true,
  "message": "Hoàn thành hóa đơn thành công"
}
```

### 4.10 Xóa hóa đơn
```
DELETE /modules/quanly_hoadon/api.php?action=delete&id=1

Response:
{
  "success": true,
  "message": "Xóa hóa đơn thành công"
}
```

---

## Mã Lỗi HTTP

- **200**: Thành công
- **400**: Request sai (thiếu tham số, dữ liệu không hợp lệ)
- **405**: Method không được hỗ trợ
- **500**: Lỗi server

## Mẫu Error Response

```json
{
  "success": false,
  "error": "Mô tả lỗi"
}
```

---

## Ví dụ cAbout frontend integration

### JavaScript Fetch Example
```javascript
// Lấy tất cả bàn
fetch('http://localhost/DoAn_Web/modules/quanly_ban/api.php?action=getAll')
  .then(response => response.json())
  .then(data => console.log(data))
  .catch(error => console.error('Error:', error));

// Tạo bàn mới
fetch('http://localhost/DoAn_Web/modules/quanly_ban/api.php?action=create', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    TenBan: 'Bàn 10',
    TrangThai: 'Trống',
    MaLoaiBan: 1,
    GiaGio: 50000,
    MaKhuVuc: 1
  })
})
  .then(response => response.json())
  .then(data => console.log(data))
  .catch(error => console.error('Error:', error));

// Cập nhật bàn
fetch('http://localhost/DoAn_Web/modules/quanly_ban/api.php?action=update&id=1', {
  method: 'PUT',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    TenBan: 'Bàn 1 - Updated',
    GiaGio: 60000
  })
})
  .then(response => response.json())
  .then(data => console.log(data))
  .catch(error => console.error('Error:', error));

// Xóa bàn
fetch('http://localhost/DoAn_Web/modules/quanly_ban/api.php?action=delete&id=1', {
  method: 'DELETE'
})
  .then(response => response.json())
  .then(data => console.log(data))
  .catch(error => console.error('Error:', error));
```

---

## Ghi chú Quan Trọng

1. **Validation**: Tất cả các input đều được validate trước khi lưu vào database
2. **Error Handling**: Các lỗi được trả về với thông báo chi tiết
3. **CORS**: API hỗ trợ CORS cho phép gọi từ các domain khác nhau
4. **Database Connection**: Sử dụng PDO prepared statements để tránh SQL Injection
5. **Soft Delete**: Dịch vụ (Món) sử dụng soft delete (chỉ cập nhật TrangThai = 0)
6. **Auto Update**: Tổng tiền hóa đơn tự động cập nhật khi thêm/sửa/xóa chi tiết

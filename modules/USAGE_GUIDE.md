# Backend CRUD Implementation Guide

## Tổng Quan

Toàn bộ backend CRUD cho 4 module chính đã được triển khai hoàn chỉnh, tuân theo cấu trúc tiêu chuẩn MVC (Model-View-Controller).

## Cấu Trúc Folder

```
modules/
├── quanly_ban/
│   ├── controller.php          # BanController class
│   └── api.php                 # RESTful endpoints
├── quanly_khachhang/
│   ├── controller.php          # KhachHangController class
│   └── api.php                 # RESTful endpoints
├── quanly_dichvu/
│   ├── controller.php          # DichVuController class
│   └── api.php                 # RESTful endpoints
├── quanly_hoadon/
│   ├── controller.php          # HoaDonController class
│   └── api.php                 # RESTful endpoints
└── API_DOCUMENTATION.md        # Tài liệu đầy đủ

includes/
├── DatabaseHelper.php          # Utility functions & API response helpers
└── ...
```

## Features Chính

### 1. **BanController** (quanly_ban)
- ✅ Xem tất cả bàn
- ✅ Xem chi tiết bàn
- ✅ Tạo bàn mới
- ✅ Cập nhật thông tin bàn
- ✅ Cập nhật trạng thái bàn
- ✅ Xóa bàn
- ✅ Lọc bàn theo khu vực

### 2. **KhachHangController** (quanly_khachhang)
- ✅ Xem tất cả khách hàng
- ✅ Xem chi tiết khách hàng
- ✅ Tạo khách hàng mới
- ✅ Cập nhật thông tin khách hàng
- ✅ Xóa khách hàng
- ✅ Cộng điểm tích lũy
- ✅ Tìm kiếm theo số điện thoại
- ✅ Lọc theo hạng khách hàng

### 3. **DichVuController** (quanly_dichvu)
- ✅ Xem tất cả dịch vụ
- ✅ Xem chi tiết dịch vụ
- ✅ Tạo dịch vụ mới
- ✅ Cập nhật dịch vụ
- ✅ Xóa dịch vụ (soft delete)
- ✅ Lọc theo danh mục
- ✅ Cập nhật số lượng tồn
- ✅ Tìm kiếm dịch vụ

### 4. **HoaDonController** (quanly_hoadon)
- ✅ Xem tất cả hóa đơn
- ✅ Xem chi tiết hóa đơn (với danh sách dịch vụ)
- ✅ Tạo hóa đơn mới
- ✅ Cập nhật hóa đơn
- ✅ Xóa hóa đơn
- ✅ Thêm dịch vụ vào hóa đơn
- ✅ Cập nhật chi tiết hóa đơn
- ✅ Xóa chi tiết hóa đơn
- ✅ Hoàn thành hóa đơn
- ✅ Lấy hóa đơn đang phục vụ của bàn
- ✅ Auto-calculate tổng tiền

## Cách Sử Dụng

### Cơ Bản

Tất cả API sử dụng định dạng:
```
http://localhost/DoAn_Web/modules/{module}/api.php?action={action}
```

### Ví Dụ GET Request

```bash
# Lấy tất cả bàn
curl http://localhost/DoAn_Web/modules/quanly_ban/api.php?action=getAll

# Lấy bàn ID 1
curl http://localhost/DoAn_Web/modules/quanly_ban/api.php?action=getById&id=1

# Lấy bàn theo khu vực
curl http://localhost/DoAn_Web/modules/quanly_ban/api.php?action=getByKhuVuc&maKhuVuc=1
```

### Ví Dụ POST Request

```bash
# Tạo bàn mới
curl -X POST http://localhost/DoAn_Web/modules/quanly_ban/api.php?action=create \
  -H "Content-Type: application/json" \
  -d '{
    "TenBan": "Bàn 5",
    "TrangThai": "Trống",
    "MaLoaiBan": 1,
    "GiaGio": 50000,
    "MaKhuVuc": 1
  }'
```

### Ví Dụ PUT Request

```bash
# Cập nhật bàn
curl -X PUT http://localhost/DoAn_Web/modules/quanly_ban/api.php?action=update&id=1 \
  -H "Content-Type: application/json" \
  -d '{
    "TenBan": "Bàn 1 - VIP",
    "GiaGio": 60000
  }'
```

### Ví Dụ DELETE Request

```bash
# Xóa bàn
curl -X DELETE http://localhost/DoAn_Web/modules/quanly_ban/api.php?action=delete&id=1
```

## JavaScript Integration (Frontend)

### Fetch Example

```javascript
// Helper function
async function callApi(module, action, method = 'GET', data = null, id = null) {
  const baseUrl = 'http://localhost/DoAn_Web/modules';
  let url = `${baseUrl}/${module}/api.php?action=${action}`;
  
  if (id) url += `&id=${id}`;
  
  const options = {
    method,
    headers: {
      'Content-Type': 'application/json'
    }
  };
  
  if (data && method !== 'GET') {
    options.body = JSON.stringify(data);
  }
  
  try {
    const response = await fetch(url, options);
    return await response.json();
  } catch (error) {
    console.error('API Error:', error);
    return { success: false, error: error.message };
  }
}

// Usage examples:

// Lấy tất cả bàn
const ban = await callApi('quanly_ban', 'getAll');
console.log(ban);

// Tạo bàn mới
const newBan = await callApi('quanly_ban', 'create', 'POST', {
  TenBan: 'Bàn 10',
  TrangThai: 'Trống',
  MaLoaiBan: 1,
  GiaGio: 50000,
  MaKhuVuc: 1
});
console.log(newBan);

// Cập nhật bàn
const updateBan = await callApi('quanly_ban', 'update', 'PUT', 
  { TenBan: 'Bàn 1 Updated', GiaGio: 60000 }, 
  1
);
console.log(updateBan);

// Xóa bàn
const deleteBan = await callApi('quanly_ban', 'delete', 'DELETE', null, 1);
console.log(deleteBan);

// Lấy hóa đơn với chi tiết
const hd = await callApi('quanly_hoadon', 'getById', 'GET', null, 1);
console.log(hd.data.ChiTiet); // Danh sách dịch vụ trong hóa đơn
```

## Response Format

### Success Response
```json
{
  "success": true,
  "message": "Tạo bàn thành công",
  "id": 5,
  "data": [ /* optional */ ]
}
```

### Error Response
```json
{
  "success": false,
  "error": "Thiếu dữ liệu bắt buộc"
}
```

## DatabaseHelper Utilities

File `includes/DatabaseHelper.php` cung cấp các hàm hỗ trợ:

### Thống Kê
```php
$helper = new DatabaseHelper($conn);

// Lấy thống kê chung
$stats = $helper->getStats();
// Returns: totalBan, banDangSuDung, totalKhachHang, totalDichVu, doanhThuNgay, hoaDonNgay

// Lấy báo cáo doanh thu
$report = $helper->getReportByDate('2024-01-01', '2024-01-31');

// Lấy dịch vụ bán chạy nhất
$topServices = $helper->getTopServices(10);
```

### Validation & Formatting
```php
// Validate email
DatabaseHelper::validateEmail($email);

// Validate phone (Vietnam)
DatabaseHelper::validatePhoneVN('0901234567');

// Format currency
echo DatabaseHelper::formatCurrency(75000); // Output: 75,000

// Format date
echo DatabaseHelper::formatDate($date, 'Y-m-d H:i'); // Output: 2024-01-15 10:30
```

## Security Features

✅ **SQL Injection Prevention**: PDO prepared statements  
✅ **CORS Support**: API endpoints hỗ trợ cross-origin requests  
✅ **Input Validation**: Tất cả input được validate trước lưu  
✅ **Type Hints**: PHP type declarations cho type safety  
✅ **Error Handling**: Exception handling & meaningful error messages  

## API Documentation Lengkap

Xem chi tiết tất cả endpoints tại: [modules/API_DOCUMENTATION.md](./API_DOCUMENTATION.md)

## Testing API Endpoints

### Sử dụng Postman

1. Tạo collection mới
2. Thêm requests với các endpoint trên
3. Set method (GET, POST, PUT, DELETE)
4. Thêm query parameters (action, id, etc.)
5. Với POST/PUT, thêm JSON body
6. Test từng endpoint

### Sử dụng Thunder Client (VS Code)

1. Install extension "Thunder Client"
2. Tạo new request
3. Paste URL: `http://localhost/DoAn_Web/modules/quanly_ban/api.php?action=getAll`
4. Send

## Troubleshooting

### Database Connection Error
- Check: `config/db.php` - ensure MySQL is running
- Verify database `QLBD` exists
- Check credentials

### 404 Error
- Verify URL path: `/DoAn_Web/modules/{module}/api.php`
- Check action parameter
- Ensure module folder contains `api.php`

### 500 Error
- Check error log
- Verify database schema matches code
- Check PDO extension enabled

## Next Steps

1. Tích hợp frontend forms để gọi API
2. Thêm authentication/authorization
3. Implement advanced filtering & sorting
4. Add real-time updates (WebSocket)
5. Generate reports/exports

## Support

Tất cả controllers tuân theo cấu trúc chung:
- Validation → Execute Query → Return Response
- Error messages bằng tiếng Việt
- Response format nhất quán (JSON)

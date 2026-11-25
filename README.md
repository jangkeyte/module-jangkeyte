# JangKeyte Modular Core & Visual Studio (v2.1.0)

Package nền tảng chuẩn **Laravel 11 / 12** cung cấp sẵn:
- **Core Admin UI (AdminLTE 4 + Bootstrap 5 + Bootstrap Icons + iziToast + Vanilla JS)** với Sidebar `MenuRegistry` động (zero jQuery, zero NPM/Vite build step).
- **Authentication & RBAC (Users, Roles, Permissions, Cached Gates)** với cơ chế Hook mở rộng (`ExternalAuthenticatorInterface`, `PostLoginRedirectorInterface`, `DashboardHandlerInterface`).
- **Modular Monolith Auto-Loader** tự động nạp các module nghiệp vụ trong `modules/*` thông qua `modules_statuses.json` (tương thích 100% `route:cache`, `config:cache` và môi trường Linux).
- **Visual Module & CRUD Studio (`/admin/modules`, `/admin/modules/create-crud`)** & **Unified CRUD Generator (`jangkeyte:crud`)** hỗ trợ `BelongsTo`, QuillJS `2.0` (`2.0.3` Snow), Media Picker, Column Sort, Bulk Delete, `SoftDeletes` Trash/Restore/Force-Delete và UTF-8 BOM CSV Export.
- **Dynamic Site Settings (`/admin/settings`, `setting()`)** lưu DB có cache và tích hợp tự động vào AdminLTE layout.
- **Centralized Media Manager (`/admin/media`, `<x-jangkeyte::forms.media-picker-modal />`, `jangkeyte_media_url()`)** quản lý thư mục/tệp tin an toàn dưới `public/storage/uploads`.

## 1. Cài đặt cho dự án Laravel mới

Chỉ cần 2 lệnh (không cần chỉnh tay `composer.json` hay chạy `dump-autoload` thủ công):

```bash
composer require jangkeyte/jangkeyte
php artisan jangkeyte:install
```

Lệnh `php artisan jangkeyte:install` sẽ tự động thực hiện toàn bộ quy trình khởi tạo:
- Publish cấu hình (`config/jangkeyte.php`) và tài nguyên AdminLTE 4 (`public/assets`, `public/storage/uploads/*`).
- Tạo thư mục `modules/` và tệp `modules_statuses.json` (`{}`).
- Tự động kiểm tra và chèn `"Modules\\": "modules/"` vào `autoload.psr-4` trong `composer.json` dự án đích, đồng thời chạy `composer dump-autoload`.
- Cập nhật `config/auth.php` sang `JangKeyte\Models\User::class`.
- Chạy `migrate` và seed Roles, Permissions, Settings cùng tài khoản mặc định (`admin` / `123456` — thêm `--without-seed` nếu muốn bỏ qua seed).

> **Tham khảo / Dự phòng (nếu môi trường không có `composer` trên CLI):** Đảm bảo `composer.json` có `"Modules\\": "modules/"` trong `autoload.psr-4` rồi chạy `composer dump-autoload`:
> ```json
> "autoload": {
>     "psr-4": {
>         "App\\": "app/",
>         "Modules\\": "modules/"
>     }
> }
> ```

Tài khoản mặc định sau khi cài đặt: `admin` / `123456`.

## 2. Visual Module & CRUD Studio (`/admin/modules`)

- Truy cập **`/admin/modules`** để xem danh sách các module đã phát hiện, các CRUD schema đã sinh (`modules/{ModuleName}/Database/Schemas/{model}.json`), bật/tắt trạng thái (`Enable`/`Disable`), hoặc xóa module (tùy chọn `drop_tables=1` để gỡ bảng CSDL, lịch sử migration và quyền RBAC).
- Truy cập **`/admin/modules/create-crud`** để tạo trực quan Module & CRUD mới (không cần CLI): thiết lập tên Module, Model, Bootstrap Icon, bật/tắt `SoftDeletes`, Export CSV, chạy Migration/Permissions, và xây dựng các cột dữ liệu (`string`, `text`, `richtext`, `integer`, `decimal`, `boolean`, `date`, `datetime`, `email`, `image`, `file`, `belongsTo`).

### Bảo mật Builder trên Production (`JANGKEYTE_BUILDER_ENABLED` & `BuilderGuardMiddleware`)
- Các route ghi/sinh mã nguồn của Studio (`create-crud`, `store-crud`, `toggle`, `destroy`) được bảo vệ bởi middleware **`jangkeyte.builder`** (`BuilderGuardMiddleware`).
- Thao tác ghi chỉ được phép khi `APP_ENV` là `local` / `testing` **hoặc** biến môi trường `JANGKEYTE_BUILDER_ENABLED=true`, đồng thời người dùng đăng nhập thuộc `super_admin_roles` (`administrator`, `admin`). Trên môi trường production mặc định (`JANGKEYTE_BUILDER_ENABLED=false`), mọi request ghi đều trả về `403 Forbidden`.

## 3. Unified CRUD Generator CLI & Quan hệ `BelongsTo`

Cả CLI và Studio UI dùng chung `CrudGeneratorService`, sinh mã nguồn PSR-4 thực thụ trong `modules/{ModuleName}/`:

```bash
php artisan jangkeyte:crud Trip --module=TripBooking --soft-deletes --fields="title:string,manager_id:belongsTo:JangKeyte\\Models\\User:name,description:richtext,banner:image,is_active:boolean"
```

- **Cú pháp `belongsTo` (CLI & Studio):** `foreign_key:belongsTo:Namespace\\Model:display_column` (Studio tự động khám phá các model `JangKeyte\Models\*` và `Modules\*\Models\*` qua `ModelDiscoveryService`). Sinh sẵn `foreignId()->constrained()`, phương thức `belongsTo()`, eager loading `$with`, rule `exists:`, và `<select>` dropdown trong form.
- **Tùy chọn CLI:** `--soft-deletes`, `--export` (mặc định bật) / `--no-export`, `--no-migrate`, `--force` (ghi đè an toàn và cập nhật migration). *(Hỗ trợ alias `php artisan crud:generate Trip`).*

## 4. Tính năng CRUD Nâng cao & Trình soạn thảo QuillJS 2.0

- **QuillJS `2.0` (`2.0.3` Snow Theme, Vanilla JS):** Tự động khởi tạo cho các trường `richtext` qua CDN, không phụ thuộc jQuery hay NPM/Vite.
- **Column Sort:** Sắp xếp theo cột qua `?sort=<col>&direction=asc|desc` được kiểm soát chặt bằng danh sách trắng `$sortable` trên Model (chống SQL injection).
- **Bulk Delete:** Chọn nhiều bản ghi và xóa hàng loạt qua `DELETE /admin/{slug}/bulk-destroy`.
- **SoftDeletes (Trash / Restore / Force-Delete):** Khi bật `--soft-deletes`, giao diện tự động có tab **Thùng rác** (`?trashed=1`), nút **Khôi phục** (`POST /{id}/restore`) và **Xóa vĩnh viễn** (`DELETE /{id}/force-destroy`).
- **UTF-8 BOM CSV Export:** Xuất dữ liệu CSV chuẩn UTF-8 BOM (`\xEF\xBB\xBF`) mở trực tiếp trên Excel không lỗi font tiếng Việt qua `GET /admin/{slug}/export` (kèm chống CSV formula injection).

## 5. Dynamic Site Settings (`/admin/settings` & `setting()`)

- Quản lý cấu hình động theo nhóm tab (`general`, `branding`, `contact`), hỗ trợ các kiểu `text`, `textarea`, `image`, `boolean` và tạo thêm setting tùy chỉnh.
- Tự động cache bằng key `jangkeyte.settings` (tự làm mới khi cập nhật) và tích hợp vào tiêu đề trang, logo, favicon của AdminLTE layout.
- Đọc giá trị bất kỳ trong code/Blade bằng helper: `setting('site.name', 'JangKeyte')`.

## 6. Centralized Media Manager (`/admin/media` & Picker Modal)

- Quản lý thư mục và tệp tin tập trung trong `public/storage/uploads` (cấu hình `jangkeyte.media`: giới hạn dung lượng, danh sách đuôi tệp cho phép, chống path traversal và kiểm tra MIME thực).
- Hỗ trợ duyệt thư mục, tạo thư mục, tải nhiều tệp lên, xem trước ảnh, sao chép URL công khai và xóa tệp.
- Tái sử dụng trong mọi form qua Blade component `<x-jangkeyte::forms.media-picker-modal />` (endpoint JSON `/admin/media?json=1`) và hiển thị URL an toàn, không xóa nhầm tệp dùng chung khi sửa/xóa bản ghi CRUD bằng helper `jangkeyte_media_url($value, $fallbackFolder)`.

## 7. Quản lý trạng thái Module qua CLI

```bash
php artisan jangkeyte:modules
php artisan jangkeyte:modules --disable=TripBooking
php artisan jangkeyte:modules --enable=TripBooking
```

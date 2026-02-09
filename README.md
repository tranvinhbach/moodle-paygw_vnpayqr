# VNPay QR payment gateway for Moodle (`paygw_vnpayqr`)

Plugin cổng thanh toán VNPay QR cho hệ thống thanh toán lõi của Moodle.

## Yêu cầu
- Moodle: 4.5+
- PHP: theo yêu cầu phiên bản Moodle bạn đang dùng
- Tài khoản merchant VNPay có:
  - `TMN Code`
  - `Hash secret`

## Cài đặt
1. Chép thư mục plugin vào: `payment/gateway/vnpayqr`
2. Vào **Site administration > Notifications** (hoặc chạy `admin/cli/upgrade.php`)
3. Cấu hình gateway theo payment account:
   - `TMN Code`
   - `Hash secret`
   - `Payment URL`

## Cấu hình endpoint trên cổng VNPay
- Return URL: `https://your-moodle-site/payment/gateway/vnpayqr/return.php`
- IPN URL: `https://your-moodle-site/payment/gateway/vnpayqr/ipn.php`

## Quyền riêng tư (Privacy)
Plugin triển khai Moodle Privacy API và khai báo:
- dữ liệu được xử lý qua `core_payment`
- một phần dữ liệu giao dịch được gửi tới endpoint VNPay để xử lý thanh toán

## Bảo mật
- Chữ ký callback được kiểm tra bằng HMAC SHA512
- Giao hàng đơn hàng dùng helper của Moodle và có kiểm tra idempotent

## Liên kết
- Source code: `https://github.com/tranvinhbach/moodle-paygw_vnpayqr`
- Issue tracker: `https://github.com/tranvinhbach/moodle-paygw_vnpayqr/issues`
- Documentation: `https://github.com/tranvinhbach/moodle-paygw_vnpayqr/blob/main/README.md`

## Hỗ trợ & Donate
- Báo lỗi / yêu cầu tính năng:  
  `https://github.com/tranvinhbach/moodle-paygw_vnpayqr/issues`
- Donate tự nguyện:  
  `https://github.com/tranvinhbach/moodle-paygw_vnpayqr/blob/main/DONATE.md`

> Plugin miễn phí theo GPL. Donate là tự nguyện, không bắt buộc để cài đặt hoặc sử dụng.

## License
GNU GPL v3 or later.

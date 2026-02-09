<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'VNPay QR';
$string['gatewayname'] = 'VNPay QR';

$string['config_tmncode'] = 'TMN Code';
$string['config_hashsecret'] = 'Khóa bí mật (Hash secret)';
$string['config_paymenturl'] = 'URL thanh toán';
$string['config_paymenturl_default'] = 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';

$string['missingconfig'] = 'Cổng thanh toán chưa được cấu hình (thiếu TMN Code / Hash secret / URL thanh toán).';
$string['invalidpaymenturl'] = 'URL thanh toán không hợp lệ. Vui lòng dùng URL HTTPS hợp lệ.';
$string['unsupportedcurrency'] = 'Tiền tệ không được hỗ trợ. Cổng VNPay chỉ hỗ trợ VND.';
$string['invalidpayment'] = 'Số tiền thanh toán không hợp lệ.';

$string['paymentreturn_ok'] = 'Thanh toán thành công. Bạn có thể quay lại khóa học.';
$string['paymentreturn_fail'] = 'Thanh toán chưa thành công.';
$string['paymentdeliverfail'] = 'Đã thanh toán nhưng chưa ghi danh được. Vui lòng liên hệ quản trị viên.';

$string['missingvnpaysignature'] = 'Thiếu chữ ký VNPay trong dữ liệu trả về.';
$string['cannotextractpaymentid'] = 'Không xác định được payment id từ phản hồi VNPay.';
$string['tmncode_mismatch'] = 'TMN Code không khớp.';
$string['invalidsignature'] = 'Chữ ký VNPay không hợp lệ.';

$string['gatewaydescription'] = 'Thanh toán qua VNPay QR.';
$string['pluginname_desc'] = 'Cổng thanh toán VNPay QR cho Moodle.';

$string['privacy:metadata:core_payment'] = 'Plugin sử dụng phân hệ core_payment của Moodle để lưu bản ghi thanh toán và hoàn tất đơn hàng.';
$string['privacy:metadata:vnpay'] = 'Để xử lý thanh toán, một phần dữ liệu giao dịch được gửi tới VNPay.';
$string['privacy:metadata:vnpay:txnref'] = 'Mã tham chiếu giao dịch phía người bán.';
$string['privacy:metadata:vnpay:orderinfo'] = 'Mô tả/tham chiếu đơn hàng.';
$string['privacy:metadata:vnpay:amount'] = 'Số tiền thanh toán.';
$string['privacy:metadata:vnpay:currency'] = 'Loại tiền tệ thanh toán.';
$string['privacy:metadata:vnpay:ipaddress'] = 'Địa chỉ IP của người dùng khi thanh toán.';
$string['privacy:metadata:vnpay:returnurl'] = 'Đường dẫn quay lại sau khi hoàn tất thanh toán.';

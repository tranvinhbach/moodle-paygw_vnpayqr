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

require(__DIR__ . '/../../../config.php');

require_login();

$component   = required_param('component', PARAM_COMPONENT);
$paymentarea = required_param('paymentarea', PARAM_ALPHANUMEXT);
$itemid      = required_param('itemid', PARAM_INT);

$payable   = \core_payment\helper::get_payable($component, $paymentarea, $itemid);
$userid    = (int)$USER->id;
$amount    = (float)$payable->get_amount();
$currency  = strtoupper((string)$payable->get_currency());
$accountid = (int)$payable->get_account_id();

if ($currency !== 'VND') {
    throw new \moodle_exception('unsupportedcurrency', 'paygw_vnpayqr');
}
if ($amount <= 0) {
    throw new \moodle_exception('invalidpayment', 'paygw_vnpayqr');
}

$gwconfig = \core_payment\helper::get_gateway_configuration(
    $component,
    $paymentarea,
    $itemid,
    'vnpayqr'
);

$tmncode    = trim((string)($gwconfig['tmncode'] ?? ''));
$hashsecret = preg_replace('/\s+/', '', (string)($gwconfig['hashsecret'] ?? ''));
$paymenturl = trim((string)($gwconfig['paymenturl'] ?? ''));

// Nếu admin dán URL có query/fragment thì loại bỏ.
$paymenturl = preg_replace('/\?.*$/', '', $paymenturl);
$paymenturl = preg_replace('/#.*$/', '', $paymenturl);

if ($tmncode === '' || $hashsecret === '' || $paymenturl === '') {
    throw new \moodle_exception('missingconfig', 'paygw_vnpayqr');
}

// FIX: production chỉ cho HTTPS.
if (!preg_match('#^https://#i', $paymenturl)) {
    throw new \moodle_exception('invalidpaymenturl', 'paygw_vnpayqr');
}

// Validate URL chặt hơn (phải có host).
$parsed = parse_url($paymenturl);
if ($parsed === false || empty($parsed['scheme']) || empty($parsed['host'])) {
    throw new \moodle_exception('invalidpaymenturl', 'paygw_vnpayqr');
}

// Save payment trước khi tạo VNPay URL.
$paymentid = \core_payment\helper::save_payment(
    $accountid,
    $component,
    $paymentarea,
    $itemid,
    $userid,
    $amount,
    $currency,
    'vnpayqr'
);

// TxnRef format để return.php/ipn.php parse được payment id.
$txnref = 'm' . $paymentid . '_' . time();

$returnurl = (new \moodle_url('/payment/gateway/vnpayqr/return.php'))->out(false);

$ipaddr = (string)getremoteaddr();
if ($ipaddr === '' || $ipaddr === '0.0.0.0') {
    // Fallback an toàn nếu môi trường reverse proxy trả rỗng.
    $ipaddr = '127.0.0.1';
}

// VNPay datetime format: YYYYMMDDHHIISS.
$tz  = new \DateTimeZone('Asia/Ho_Chi_Minh');
$now = new \DateTimeImmutable('now', $tz);

// VNPay amount = amount * 100.
$vnpamount = (int)round($amount * 100, 0);
if ($vnpamount <= 0) {
    throw new \moodle_exception('invalidpayment', 'paygw_vnpayqr');
}

$inputdata = [
    'vnp_Version'    => '2.1.0',
    'vnp_Command'    => 'pay',
    'vnp_TmnCode'    => $tmncode,
    'vnp_Amount'     => $vnpamount,
    'vnp_CurrCode'   => 'VND',
    'vnp_TxnRef'     => $txnref,
    'vnp_OrderInfo'  => 'MoodlePayment' . $paymentid,
    'vnp_OrderType'  => 'other',
    'vnp_Locale'     => 'vn',
    'vnp_ReturnUrl'  => $returnurl,
    'vnp_IpAddr'     => $ipaddr,
    'vnp_CreateDate' => $now->format('YmdHis'),
    'vnp_ExpireDate' => $now->modify('+30 minutes')->format('YmdHis'),
];

// VNPay signing: sort + urlencode key/value.
ksort($inputdata);

$pairs = [];
foreach ($inputdata as $key => $value) {
    if ($value === '' || $value === null) {
        continue;
    }
    $pairs[] = urlencode((string)$key) . '=' . urlencode((string)$value);
}

$hashdata   = implode('&', $pairs);
$query      = implode('&', $pairs);
$securehash = hash_hmac('sha512', $hashdata, $hashsecret);

// Add SecureHashType for broad compatibility.
$vnpayurl = rtrim($paymenturl, '?&')
    . '?' . $query
    . '&vnp_SecureHashType=HmacSHA512'
    . '&vnp_SecureHash=' . $securehash;

redirect($vnpayurl);

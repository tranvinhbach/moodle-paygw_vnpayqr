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

/**
 * Keep only VNPay params (keys prefixed with vnp_).
 *
 * @param array $src
 * @return array
 */
function paygw_vnpayqr_get_vnp_params(array $src): array {
    $out = [];
    foreach ($src as $k => $v) {
        if (strpos((string)$k, 'vnp_') !== 0) {
            continue;
        }
        if (!is_scalar($v)) {
            continue;
        }
        $out[(string)$k] = (string)$v;
    }
    return $out;
}

/**
 * Build hash data exactly like pay.php:
 * - remove vnp_SecureHash / vnp_SecureHashType
 * - ksort
 * - urlencode key/value
 * - join with "&"
 *
 * @param array $params
 * @return string
 */
function paygw_vnpayqr_build_hashdata(array $params): string {
    unset($params['vnp_SecureHash'], $params['vnp_SecureHashType']);
    ksort($params);

    $pairs = [];
    foreach ($params as $k => $v) {
        if ($v === '' || $v === null) {
            continue;
        }
        $pairs[] = urlencode((string)$k) . '=' . urlencode((string)$v);
    }

    return implode('&', $pairs);
}

/**
 * Verify VNPay signature.
 *
 * @param array $params
 * @param string $hashsecret
 * @param string $provided
 * @return bool
 */
function paygw_vnpayqr_verify_signature(array $params, string $hashsecret, string $provided): bool {
    $hashsecret = preg_replace('/\s+/', '', (string)$hashsecret);
    $provided = strtolower(trim((string)$provided));

    if ($hashsecret === '' || $provided === '') {
        return false;
    }

    $hashdata = paygw_vnpayqr_build_hashdata($params);
    $calculated = strtolower(hash_hmac('sha512', $hashdata, $hashsecret));

    return hash_equals($calculated, $provided);
}

/**
 * Extract Moodle payment id from VNPay callback.
 *
 * @param array $params
 * @return int
 */
function paygw_vnpayqr_extract_paymentid(array $params): int {
    $txnref = (string)($params['vnp_TxnRef'] ?? '');
    if (preg_match('/^m(\d+)(?:[-_].*)?$/', $txnref, $m)) {
        return (int)$m[1];
    }

    $orderinfo = (string)($params['vnp_OrderInfo'] ?? '');
    if (preg_match('/paymentid\s*=\s*(\d+)/i', $orderinfo, $m)) {
        return (int)$m[1];
    }
    if (preg_match('/MoodlePayment\s*(\d+)/i', $orderinfo, $m)) {
        return (int)$m[1];
    }

    return 0;
}

$params = paygw_vnpayqr_get_vnp_params($_GET);
$providedhash = (string)($params['vnp_SecureHash'] ?? '');

if ($providedhash === '') {
    redirect(
        new moodle_url('/'),
        get_string('missingvnpaysignature', 'paygw_vnpayqr'),
        2,
        \core\output\notification::NOTIFY_ERROR
    );
}

$paymentid = paygw_vnpayqr_extract_paymentid($params);
if ($paymentid <= 0) {
    redirect(
        new moodle_url('/'),
        get_string('cannotextractpaymentid', 'paygw_vnpayqr'),
        2,
        \core\output\notification::NOTIFY_ERROR
    );
}

$payment = $DB->get_record(
    'payments',
    ['id' => $paymentid, 'gateway' => 'vnpayqr'],
    '*',
    IGNORE_MISSING
);

if (!$payment) {
    redirect(
        new moodle_url('/'),
        get_string('paymentreturn_fail', 'paygw_vnpayqr'),
        2,
        \core\output\notification::NOTIFY_ERROR
    );
}

// Default target URL.
$targeturl = new moodle_url('/');
try {
    $targeturl = \core_payment\helper::get_success_url(
        $payment->component,
        $payment->paymentarea,
        (int)$payment->itemid
    );
} catch (\Throwable $e) {
    // Keep default '/'.
}

$gwconfig = \core_payment\helper::get_gateway_configuration(
    $payment->component,
    $payment->paymentarea,
    (int)$payment->itemid,
    'vnpayqr'
);

$tmncode = trim((string)($gwconfig['tmncode'] ?? ''));
$hashsecret = preg_replace('/\s+/', '', (string)($gwconfig['hashsecret'] ?? ''));

if ($hashsecret === '') {
    redirect(
        $targeturl,
        get_string('missingconfig', 'paygw_vnpayqr'),
        2,
        \core\output\notification::NOTIFY_ERROR
    );
}

// Optional TMN check to reject foreign callbacks.
$returntmn = (string)($params['vnp_TmnCode'] ?? '');
if ($tmncode !== '' && $returntmn !== '' && strcasecmp($tmncode, $returntmn) !== 0) {
    redirect(
        $targeturl,
        get_string('tmncode_mismatch', 'paygw_vnpayqr'),
        2,
        \core\output\notification::NOTIFY_ERROR
    );
}

// Verify secure hash.
if (!paygw_vnpayqr_verify_signature($params, $hashsecret, $providedhash)) {
    redirect(
        $targeturl,
        get_string('invalidsignature', 'paygw_vnpayqr'),
        2,
        \core\output\notification::NOTIFY_ERROR
    );
}

// Check status + amount + currency.
$responsecode = (string)($params['vnp_ResponseCode'] ?? '');
$transactionstatus = (string)($params['vnp_TransactionStatus'] ?? $responsecode);

$expectedamount = (int)round(((float)$payment->amount) * 100);

// Parse amount safely (ignore any non-digit chars).
$actualamountraw = (string)($params['vnp_Amount'] ?? '0');
$actualamountdigits = preg_replace('/\D+/', '', $actualamountraw);
$actualamount = (int)($actualamountdigits !== '' ? $actualamountdigits : 0);
$amountok = ($actualamount === $expectedamount);

$paymentcurrency = strtoupper((string)($payment->currency ?? 'VND'));
$returncurrency = strtoupper((string)($params['vnp_CurrCode'] ?? 'VND'));
$currencyok = ($paymentcurrency === '' || $returncurrency === '' || $paymentcurrency === $returncurrency);

if ($responsecode !== '00' || $transactionstatus !== '00' || !$amountok || !$currencyok) {
    redirect(
        $targeturl,
        get_string('paymentreturn_fail', 'paygw_vnpayqr'),
        2,
        \core\output\notification::NOTIFY_ERROR
    );
}

// Idempotent behavior: already completed => success.
if (!empty($payment->timecompleted)) {
    redirect(
        $targeturl,
        get_string('paymentreturn_ok', 'paygw_vnpayqr'),
        1,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Deliver order.
$delivered = true;
try {
    $result = \core_payment\helper::deliver_order(
        $payment->component,
        $payment->paymentarea,
        (int)$payment->itemid,
        (int)$payment->id,
        (int)$payment->userid
    );
    if ($result === false) {
        $delivered = false;
    }
} catch (\Throwable $e) {
    $delivered = false;
}

if (!$delivered) {
    redirect(
        $targeturl,
        get_string('paymentdeliverfail', 'paygw_vnpayqr'),
        2,
        \core\output\notification::NOTIFY_ERROR
    );
}

redirect(
    $targeturl,
    get_string('paymentreturn_ok', 'paygw_vnpayqr'),
    1,
    \core\output\notification::NOTIFY_SUCCESS
);

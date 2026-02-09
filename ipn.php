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

define('NO_MOODLE_COOKIES', true);
define('NO_DEBUG_DISPLAY', true);

require(__DIR__ . '/../../../config.php');

header('Content-Type: application/json; charset=utf-8');

/**
 * Output VNPay IPN response.
 *
 * @param string $code
 * @param string $message
 * @return void
 */
function paygw_vnpayqr_ipn_response(string $code, string $message): void {
    http_response_code(200);
    echo json_encode(
        ['RspCode' => $code, 'Message' => $message],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

/**
 * Collect only VNPay params (vnp_*) and scalar values.
 *
 * @param array $src
 * @return array
 */
function paygw_vnpayqr_collect_vnp_params(array $src): array {
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
 * - remove vnp_SecureHash, vnp_SecureHashType
 * - sort keys
 * - urlencode key/value
 * - join by "&"
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
 * Extract Moodle payment id from VNPay callback params.
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

try {
    global $DB;

    // Some environments may send IPN as GET, others as POST.
    // Use GET first (VNPay usually calls GET), then POST fallback.
    $rawparams = $_GET + $_POST;
    $params = paygw_vnpayqr_collect_vnp_params($rawparams);

    // FIX #1: Không trả "00" cho request rỗng/thiếu chữ ký.
    if (empty($params)) {
        paygw_vnpayqr_ipn_response('99', 'Invalid request');
    }
    if (empty($params['vnp_SecureHash'])) {
        paygw_vnpayqr_ipn_response('97', 'Invalid signature');
    }

    // Optional: basic required fields.
    foreach (['vnp_TxnRef', 'vnp_Amount', 'vnp_ResponseCode'] as $requiredkey) {
        if (!array_key_exists($requiredkey, $params) || $params[$requiredkey] === '') {
            paygw_vnpayqr_ipn_response('99', 'Missing parameter: ' . $requiredkey);
        }
    }

    $paymentid = paygw_vnpayqr_extract_paymentid($params);
    if ($paymentid <= 0) {
        paygw_vnpayqr_ipn_response('01', 'Order not found');
    }

    $payment = $DB->get_record(
        'payments',
        ['id' => $paymentid, 'gateway' => 'vnpayqr'],
        '*',
        IGNORE_MISSING
    );
    if (!$payment) {
        paygw_vnpayqr_ipn_response('01', 'Order not found');
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
        paygw_vnpayqr_ipn_response('99', 'Gateway not configured');
    }

    // Optional TMN check to reject foreign callbacks.
    $returntmn = (string)($params['vnp_TmnCode'] ?? '');
    if ($tmncode !== '' && $returntmn !== '' && strcasecmp($tmncode, $returntmn) !== 0) {
        paygw_vnpayqr_ipn_response('97', 'Invalid TMN');
    }

    if (!paygw_vnpayqr_verify_signature($params, $hashsecret, (string)$params['vnp_SecureHash'])) {
        paygw_vnpayqr_ipn_response('97', 'Invalid signature');
    }

    $responsecode = (string)($params['vnp_ResponseCode'] ?? '');
    $transactionstatus = (string)($params['vnp_TransactionStatus'] ?? $responsecode);

    // If transaction failed on VNPay side, acknowledge to stop retries.
    if ($responsecode !== '00' || $transactionstatus !== '00') {
        paygw_vnpayqr_ipn_response('00', 'Received');
    }

    // FIX #2: Parse amount an toàn (lọc ký tự lạ).
    $expectedamount = (int)round(((float)$payment->amount) * 100);
    $actualamountraw = (string)($params['vnp_Amount'] ?? '0');
    $actualamountdigits = preg_replace('/\D+/', '', $actualamountraw);
    $actualamount = (int)($actualamountdigits !== '' ? $actualamountdigits : 0);

    if ($actualamount !== $expectedamount) {
        paygw_vnpayqr_ipn_response('04', 'Invalid amount');
    }

    // FIX #3: Check currency nếu VNPay gửi lên.
    $paymentcurrency = strtoupper((string)($payment->currency ?? 'VND'));
    $returncurrency = strtoupper((string)($params['vnp_CurrCode'] ?? 'VND'));
    if ($paymentcurrency !== '' && $returncurrency !== '' && $returncurrency !== $paymentcurrency) {
        paygw_vnpayqr_ipn_response('04', 'Invalid currency');
    }

    // Idempotent: already completed.
    if (!empty($payment->timecompleted)) {
        paygw_vnpayqr_ipn_response('02', 'Already confirmed');
    }

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
        paygw_vnpayqr_ipn_response('99', 'Deliver failed');
    }

    paygw_vnpayqr_ipn_response('00', 'Confirm Success');

} catch (\Throwable $e) {
    paygw_vnpayqr_ipn_response('99', 'Server error');
}

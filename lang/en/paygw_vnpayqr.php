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
$string['config_hashsecret'] = 'Hash secret';
$string['config_paymenturl'] = 'Payment URL';
$string['config_paymenturl_default'] = 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';

$string['missingconfig'] = 'Gateway is not configured (missing TMN Code / Hash secret / Payment URL).';
$string['invalidpaymenturl'] = 'Invalid payment URL. Please use a valid HTTPS URL.';
$string['unsupportedcurrency'] = 'Unsupported currency. VNPay gateway supports VND only.';
$string['invalidpayment'] = 'Invalid payment amount.';

$string['paymentreturn_ok'] = 'Payment completed. You can go back to the course.';
$string['paymentreturn_fail'] = 'Payment not completed.';
$string['paymentdeliverfail'] = 'Payment was successful, but enrolment could not be completed. Please contact the administrator.';

$string['missingvnpaysignature'] = 'Missing VNPay signature in callback.';
$string['cannotextractpaymentid'] = 'Cannot determine payment id from VNPay response.';
$string['tmncode_mismatch'] = 'TMN Code mismatch.';
$string['invalidsignature'] = 'Invalid VNPay signature.';

$string['gatewaydescription'] = 'Pay via VNPay QR.';
$string['pluginname_desc'] = 'VNPay QR payment gateway for Moodle.';

$string['privacy:metadata:core_payment'] = 'The plugin uses the core payment subsystem to store payment and fulfilment records.';
$string['privacy:metadata:vnpay'] = 'To process payment, limited transaction data is sent to VNPay.';
$string['privacy:metadata:vnpay:txnref'] = 'Merchant transaction reference.';
$string['privacy:metadata:vnpay:orderinfo'] = 'Order description/reference.';
$string['privacy:metadata:vnpay:amount'] = 'Payment amount.';
$string['privacy:metadata:vnpay:currency'] = 'Payment currency.';
$string['privacy:metadata:vnpay:ipaddress'] = 'Customer IP address at checkout.';
$string['privacy:metadata:vnpay:returnurl'] = 'Return URL after payment completion.';

<?php
namespace paygw_vnpayqr;

defined('MOODLE_INTERNAL') || die();

class gateway extends \core_payment\gateway {

    public static function get_supported_currencies(): array {
        return ['VND'];
    }

    public static function add_configuration_to_gateway_form(\core_payment\form\account_gateway $form): void {
        $mform = $form->get_mform();

        $mform->addElement('text', 'tmncode', get_string('config_tmncode', 'paygw_vnpayqr'));
        $mform->setType('tmncode', PARAM_ALPHANUMEXT);

        $mform->addElement('passwordunmask', 'hashsecret', get_string('config_hashsecret', 'paygw_vnpayqr'));
        $mform->setType('hashsecret', PARAM_RAW_TRIMMED);

        $mform->addElement('text', 'paymenturl', get_string('config_paymenturl', 'paygw_vnpayqr'));
        // Giữ PARAM_URL để sanitize cơ bản, validate chi tiết ở validate_gateway_form().
        $mform->setType('paymenturl', PARAM_URL);
        $mform->setDefault('paymenturl', get_string('config_paymenturl_default', 'paygw_vnpayqr'));
    }

    public static function validate_gateway_form(
        \core_payment\form\account_gateway $form,
        \stdClass $data,
        array $files,
        array &$errors
    ): void {
        // Chỉ bắt buộc khi bật gateway.
        if (empty($data->enabled)) {
            return;
        }

        $tmncode    = trim((string)($data->tmncode ?? ''));
        $hashsecret = trim((string)($data->hashsecret ?? ''));
        $paymenturl = trim((string)($data->paymenturl ?? ''));

        if ($tmncode === '') {
            $errors['tmncode'] = get_string('required');
        }

        if ($hashsecret === '') {
            $errors['hashsecret'] = get_string('required');
        }

        if ($paymenturl === '') {
            $errors['paymenturl'] = get_string('required');
            return;
        }

        // Validate URL tổng quát trước.
        if (!filter_var($paymenturl, FILTER_VALIDATE_URL)) {
            $errors['paymenturl'] = get_string('invalidpaymenturl', 'paygw_vnpayqr');
            return;
        }

        // Bắt buộc HTTPS + có host hợp lệ.
        $parts  = parse_url($paymenturl);
        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        $host   = (string)($parts['host'] ?? '');

        if ($scheme !== 'https' || $host === '') {
            $errors['paymenturl'] = get_string('invalidpaymenturl', 'paygw_vnpayqr');
            return;
        }
    }
}

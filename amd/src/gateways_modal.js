// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * VNPay QR gateway modal processor.
 *
 * @module     paygw_vnpayqr/gateways_modal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Process payment by redirecting user to gateway entrypoint.
 *
 * @param {string} component Name of component that itemId belongs to
 * @param {string} paymentArea Name of payment area
 * @param {number|string} itemId Item id
 * @returns {Promise<string>}
 */
export const process = (component, paymentArea, itemId) => {
    const params = new URLSearchParams({
        component,
        paymentarea: paymentArea,
        itemid: String(itemId),
    });

    const url = `${M.cfg.wwwroot}/payment/gateway/vnpayqr/pay.php?${params.toString()}`;

    window.location.assign(url);

    // Keep promise pending while browser navigates away.
    return new Promise(() => {});
};

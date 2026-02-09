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

/**
 * Privacy provider for paygw_vnpayqr.
 *
 * @package   paygw_vnpayqr
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_vnpayqr\privacy;

use context;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider implementation for VNPay gateway.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Describe personal data handled by this plugin.
     *
     * @param collection $collection metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        // Data stored by Moodle core payment subsystem.
        $collection->add_subsystem_link(
            'core_payment',
            [],
            'privacy:metadata:core_payment'
        );

        // Data sent to VNPay.
        $collection->add_external_location_link(
            'vnpay',
            [
                'txnref' => 'privacy:metadata:vnpay:txnref',
                'orderinfo' => 'privacy:metadata:vnpay:orderinfo',
                'amount' => 'privacy:metadata:vnpay:amount',
                'currency' => 'privacy:metadata:vnpay:currency',
                'ipaddress' => 'privacy:metadata:vnpay:ipaddress',
                'returnurl' => 'privacy:metadata:vnpay:returnurl',
            ],
            'privacy:metadata:vnpay'
        );

        return $collection;
    }

    /**
     * This plugin does not store user data in plugin-owned tables.
     *
     * @param int $userid user id.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        return new contextlist();
    }

    /**
     * Get users in context. No plugin-owned user data.
     *
     * @param userlist $userlist userlist.
     */
    public static function get_users_in_context(userlist $userlist): void {
        // No-op.
    }

    /**
     * Export user data. No plugin-owned user data.
     *
     * @param approved_contextlist $contextlist approved contexts.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        // No-op.
    }

    /**
     * Delete all user data in context. No plugin-owned user data.
     *
     * @param context $context context.
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        // No-op.
    }

    /**
     * Delete data for one user. No plugin-owned user data.
     *
     * @param approved_contextlist $contextlist approved contexts.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        // No-op.
    }

    /**
     * Delete data for multiple users. No plugin-owned user data.
     *
     * @param approved_userlist $userlist approved user list.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        // No-op.
    }
}

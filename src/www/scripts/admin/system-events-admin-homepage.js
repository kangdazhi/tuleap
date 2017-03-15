/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
(function() {
    var admin_homepage_queues = document.querySelectorAll('.siteadmin-homepage-system-events-queue');
    [].forEach.call(admin_homepage_queues, function(admin_homepage_queue) {
        admin_homepage_queue.addEventListener('click', function(event) {
            if (! event.target.classList.contains('system-event-type-count') && ! event.target.parentNode.classList.contains('system-event-type-count')) {
                window.location = window.location.origin + admin_homepage_queue.dataset.href;
            }
        });
    });
})();
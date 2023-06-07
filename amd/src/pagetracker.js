
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

// jshint unused: true, undef:true

define(['jquery', 'core/log', 'core/config'], function($, log, cfg) {

    var pagetracker = {

        init: function() {
            $('.pagetracker-toggle-handle').bind('click', this.toggle);

            log.debug("AMD Page Tracker Initialized");
        },

        toggle: function(e) {

            e.stopPropagation();
            e.preventDefault();

            var that = $(this);
            var url = cfg.wwwroot + '/blocks/page_tracker/ajax/service.php';
            var itemid = that.attr('id').replace('toggle-page-', '');
            var blockid = that.attr('data-blockid');
            var courseid = that.attr('data-courseid');
            url += '?id=' + courseid;
            url += '&blockid=' + blockid;
            url += '&itemid=' + itemid;

            log.debug("AMD Page Tracker : Got for " + that.attr('id'));
            var subnodes = $('ul[data-parent="' + that.attr('id') + '"]');
            if (subnodes.hasClass('collapsed')) {
                $('#' + that.attr('id') + ' i').removeClass('fa-plus');
                $('#' + that.attr('id') + ' i').addClass('fa-minus');
                subnodes.removeClass('collapsed');
                url += '&what=expand';
            } else {
                subnodes.addClass('collapsed');
                $('#' + that.attr('id') + ' i').removeClass('fa-minus');
                $('#' + that.attr('id') + ' i').addClass('fa-plus');
                url += '&what=collapse';
            }

            // Send session change.
            $.get(url);
        }

    };

    return pagetracker;
});

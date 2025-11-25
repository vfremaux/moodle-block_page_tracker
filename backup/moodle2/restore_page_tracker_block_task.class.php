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
 * Restore tasks
 *
 * @package     block_page_tracker
 * @author      Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright   2016 onwards Valery Fremaux (valery.fremaux@gmail.com)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/blocks/page_tracker/backup/moodle2/restore_page_tracker_stepslib.php');

/**
 * Specialised restore task for the page_tracker block
 * (has own DB structures to backup)
 */
class restore_page_tracker_block_task extends restore_block_task {

    /**
     * Restore settings definition
     */
    protected function define_my_settings() {
        assert(true);
    }

    /**
     * Restore steps definition
     */
    protected function define_my_steps() {
        $this->add_step(new restore_page_tracker_block_structure_step('page_tracker_structure', 'page_tracker.xml'));
    }

    /**
     * Identify fileareas to restore
     */
    public function get_fileareas() {
        // No associated fileareas.
        return [];
    }

    /**
     * Define attributes to encode
     */
    public function get_configdata_encoded_attributes() {
        // No special handling of configdata.
        return [];
    }

    /**
     * Define contents to encode
     */
    public static function define_decode_contents() {
        return [];
    }

    /**
     * Define decoding rules.
     */
    public static function define_decode_rules() {
        return [];
    }

    /**
     * Each block will be responsible for his own remapping in is associated pageid.
     */
    public function after_restore() {
        global $DB;

        $courseid = $this->get_courseid();
        $blockid = $this->get_blockid();
        $oldblockid = $this->get_old_blockid();

        // These are fake blocks that can be cached in backup.
        if (!$blockid) {
            return;
        }

        // Adjust the serialized configdata->startpage to the actualized format_page id.
        // Get the configdata.
        $configdata = $DB->get_field('block_instances', 'configdata', ['id' => $blockid]);
        // Extract configdata.
        $config = unserialize(base64_decode($configdata));
        // Set array of used rss feeds.
        // TODO check this, not sure course modules are stored in backup mapping tables as this.
        $config->startpage = $config->startpage ?? 0;
        if ($config && $config->startpage) {
            if ($config->startpage > 0) {
                $config->startpage = $this->get_mappingid('format_page', $config->startpage);
            }
            // Serialize back the configdata
            $configdata = base64_encode(serialize($config));
            // Set the configdata back.
            $DB->set_field('block_instances', 'configdata', $configdata, ['id' => $blockid]);
        }
    }

    /**
     * Return the new id of a mapping for the given itemname
     *
     * @param string $itemname the type of item
     * @param int $oldid the item ID from the backup
     * @param mixed $ifnotfound what to return if $oldid wasnt found. Defaults to false
     */
    public function get_mappingid($itemname, $oldid, $ifnotfound = false) {
        $mapping = $this->get_mapping($itemname, $oldid);
        return $mapping ? $mapping->newitemid : $ifnotfound;
    }

    /**
     * Return the complete mapping from the given itemname, itemid
     * @param string $itemname
     * @param int $oldid
     */
    public function get_mapping($itemname, $oldid) {
        return restore_dbops::get_backup_ids_record($this->plan->get_restoreid(), $itemname, $oldid);
    }
}

/**
 * Specialised restore_decode_content provider that unserializes the configdata
 * field, and adds a instance contextualized field for restore integrity
 */
class restore_page_tracker_block_decode_content extends restore_decode_content {

    /** @var string Temp storage for unserialized configdata. */
    protected $configdata;

    /**
     * Content iterator
     */
    protected function get_iterator() {
        global $DB;

        // Build the SQL dynamically here.
        $fieldslist = 't.' . implode(', t.', $this->fields);
        $sql = "
            SELECT
                t.id, $fieldslist
            FROM
                {" . $this->tablename . "} t
            JOIN
                {backup_ids_temp} b ON b.newitemid = t.id
            WHERE
                b.backupid = ? AND
                b.itemname = ? AND
                t.blockname = 'page_tracker'
        ";
        $params = [$this->restoreid, $this->mapping];
        return ($DB->get_recordset_sql($sql, $params));
    }

    /**
     * Attribute preprocessor
     */
    protected function preprocess_field($field) {
        $this->configdata = unserialize(base64_decode($field));
        return isset($this->configdata->text) ? $this->configdata->text : '';
    }

    /**
     * Attribute postprocessor
     */
    protected function postprocess_field($field) {
        $this->configdata->text = $field;
        return base64_encode(serialize($this->configdata));
    }
}

<?php
/**
 * A dummy database layer class.
 *
 */


class DBLayer {
	var $saved_queries = array();

	function error() { return array('error_sql' => '', 'error_no' => 0,'error_msg' => 'No DB connection'); }
	function get_version() { return array('name' => 'Dummy DB', 'version'	=> ''); }
	function get_num_queries() { return 0; }
	function close() { return; }
	function escape($str) { return $str; }
/*	
	function start_transaction() return;
	function end_transaction() return;
	function query($sql, $unbuffered = false) return false;
	function query_build($query, $return_query_string = false, $unbuffered = false) return false;
	function result($query_id = 0, $row = 0, $col = 0) return false;
	function fetch_assoc($query_id = 0) return false;
	function fetch_row($query_id = 0) return false;
	function num_rows($query_id = 0) return false;
	function affected_rows() return 0;
	function insert_id() return false;
	function get_saved_queries() return array();
	function free_result($query_id = false) return;
	function set_names($names) return;
	function table_exists($table_name, $no_prefix = false) return false;
	function field_exists($table_name, $field_name, $no_prefix = false) return false;
	function index_exists($table_name, $index_name, $no_prefix = false) return false;
	function create_table($table_name, $schema, $no_prefix = false) return false;
	function drop_table($table_name, $no_prefix = false) return false;
	function alter_field($table_name, $field_name, $field_type, $allow_null, $default_value = null, $after_field = null, $no_prefix = false) return false;
	function drop_field($table_name, $field_name, $no_prefix = false) return false;
	function add_index($table_name, $index_name, $index_fields, $unique = false, $no_prefix = false) return false;
	function drop_index($table_name, $index_name, $no_prefix = false) return false;
*/	
}

<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

$wpmerge_lang = array();
$wpmerge_lang['create_table_error'] = 'Error while creating table.';
$wpmerge_lang['invalid_options'] = 'Invalid options.';
$wpmerge_lang['old_or_new_db_prefix_missing'] = 'Old or new DB prefix is missing.';
$wpmerge_lang['no_queries_to_apply'] = 'No queries to apply.';
$wpmerge_lang['unexpected_empty_unique_insert_id'] = 'Unexpected empty unqiue insert ID.';
$wpmerge_lang['query_error'] = 'Query error.';
$wpmerge_lang['invalid_insert_id'] = 'Invalid insert ID.';
$wpmerge_lang['old_or_new_insert_id_is_missing'] = 'Old or new insert ID is missing.';
$wpmerge_lang['invalid_params'] = 'Invalid parameters.';
$wpmerge_lang['find_replace_get_query_error'] = 'Error while performing find and replace.';
$wpmerge_lang['find_replace_get_query_error'] = 'Error while performing find and replace.';
$wpmerge_lang['invalid_response_json_failed'] = 'Invalid response. JSON failed.';
$wpmerge_lang['http_error'] = 'HTTP error.';
$wpmerge_lang['zip_error'] = 'Zip error.';
$wpmerge_lang['timedout'] = 'Timed out.';
$wpmerge_lang['log_make_dir_failed'] = 'Error while creating directory.';
$wpmerge_lang['tmp_make_dir_failed'] = 'Error while creating temp directory.';
$wpmerge_lang['already_changes_for_prod_in_dev_is_applied'] = 'You recently tested a merge. If you want to modify dev DB please "Clone Prod DB &amp; Apply Dev Changes" once.';//need to review form here
$wpmerge_lang['create_trigger_no_tables_found'] = 'No tables found while trying to create triggers.';
$wpmerge_lang['alter_query_error'] = 'Error in alter query.';
$wpmerge_lang['create_trigger_error'] = 'Error while creating trigger.';
$wpmerge_lang['invalid_request'] = 'Invalid request.';
$wpmerge_lang['changes_already_applied_import_db_again'] = 'Dev changes have already been applied. Clone Prod DB once to reapply.';
$wpmerge_lang['import_db_again_and_try'] = 'Please clone prod DB and try again.';
$wpmerge_lang['invalid_connect_str_please_reconnect_prod_site'] = 'Connection to prod site has been broken. Please reconnect under Settings.';
$wpmerge_lang['invalid_prod_admin_url'] = 'Invalid prod admin url.';
$wpmerge_lang['invalid_prod_site_url'] = 'Invalid prod site url.';
$wpmerge_lang['invalid_response_code'] = 'Invalid response code.';
$wpmerge_lang['db_backup_error'] = 'DB backup error.';
$wpmerge_lang['invalid_response_value'] = 'Invalid response value.';
$wpmerge_lang['unexpected_offset'] = 'Unexpected offset.';
$wpmerge_lang['prepare_compress_file_list_table_empty'] = 'File list to compress is empty.';
$wpmerge_lang['download_db_table_backup_failed_unhandled'] = 'Pre-download backup check failed.';
$wpmerge_lang['prepare_download_remote_db_unexpected_error'] = 'Unexpected error while preparing download from remote db.';
$wpmerge_lang['prepare_download_remote_db_invalid_data'] = 'Invalid data while preparing to download remote db.';
$wpmerge_lang['download_db_make_dir_failed'] = 'Create directory failed.';
$wpmerge_lang['download_db_create_file_failed'] = 'Unable to create file.';
$wpmerge_lang['download_db_file_not_exists'] = 'Downloaded file is missing.';
$wpmerge_lang['download_db_empty_json'] = 'Empty json while downloading db.';
$wpmerge_lang['download_db_empty_path'] = 'Empty path while downloading db.';
$wpmerge_lang['download_db_search_table_key_missing'] = 'Failure while mapping table.';
$wpmerge_lang['download_db_file_put_failed'] = 'Create file failed while downloading the db.';
$wpmerge_lang['cannot_open_gzfile_to_uncompress_sql'] = 'Cannot open gz file.';
$wpmerge_lang['cannot_open_tmp_file_to_uncompress_sql'] = 'Cannot open temp file.';
$wpmerge_lang['got_empty_gzread'] = 'Empty data while uncompressing.';
$wpmerge_lang['run_queries_data_missing'] = 'Queries are missing.';
$wpmerge_lang['run_queries_unhandled_download_error'] = 'Pre-run queries download check failed.';
$wpmerge_lang['run_queries_error'] = 'Error while running query.';
$wpmerge_lang['run_queries_invalid_response_value'] = 'Invalid response value.';
$wpmerge_lang['run_queries_import_error_unhandled'] = 'DB import error.';
$wpmerge_lang['run_queries_prefix_missing'] = 'Prefix is missing while running queries.';
$wpmerge_lang['replace_db_links_data_missing'] = 'Data missing for replacing db links.';
$wpmerge_lang['prod_abspath_missing'] = 'Prod ABSPATH is missing.';
$wpmerge_lang['find_replace_data_missing'] = 'Find and replace data missing.';
$wpmerge_lang['replace_db_links_unhandled_import_error'] = 'Pre-replace links DB import check failed.';
$wpmerge_lang['tables_missing_for_rename'] = 'Tables missing while renaming.';
$wpmerge_lang['tables_missing_for_delete'] = 'Tables missing while deleting.';
$wpmerge_lang['invalid_rows_count'] = 'Invalid rows count.';
$wpmerge_lang['no_changes_to_apply'] = 'No changes to apply.';
$wpmerge_lang['invalid_prod_post_max_size'] = 'Invalid prod POST max size.';
$wpmerge_lang['no_rows_returned'] = 'No rows returned.';
$wpmerge_lang['prod_response_error'] = 'Prod response error.';
$wpmerge_lang['response_data_missing'] = 'Response data missing.';
$wpmerge_lang['response_no_progress'] = 'No progress in response.';
$wpmerge_lang['invalid_offset_or_total'] = 'Invalid offset or total.';
$wpmerge_lang['prod_db_tables_list_missing'] = 'Prod db tables list missing.';
$wpmerge_lang['remote_replace_db_links_data_missing'] = 'Data missing while replacing remote db links.';
$wpmerge_lang['get_files_meta_error'] = 'Error while getting meta data of files.';
$wpmerge_lang['invalid_eof'] = 'Invalid EOF.';
$wpmerge_lang['unexpected_response'] = 'Unexpected response.';
$wpmerge_lang['base_64_decode_failed'] = 'Base64 decode failed.';
$wpmerge_lang['json_decode_failed'] = 'JSON decode failed.';
$wpmerge_lang['unexpected_data'] = 'Unexpected data.';
$wpmerge_lang['modify_db_to_proceed'] = 'Modify Dev DB once to apply dev changes(See advanced options).';
$wpmerge_lang['create_temp_file_invalid_request'] = 'Invalid request to create temp file.';
$wpmerge_lang['create_temp_file_make_dir_failed'] = 'Creating a directory to create temp file failed.';
$wpmerge_lang['create_temp_file_failed'] = 'Create temp file failed.';
$wpmerge_lang['gz_compress_failed'] = 'Compression failed.';
$wpmerge_lang['upload_file_writing_failed'] = 'File writing for uploading failed.';
$wpmerge_lang['invalid_uploaded_file'] = 'Invalid upload file.';
$wpmerge_lang['unable_to_open_uploaded_file'] = 'Unable to open uploaded file.';
$wpmerge_lang['gzuncompress_failed'] = 'Uncompress failed.';
$wpmerge_lang['file_has_empty_data'] = 'File has empty data.';
$wpmerge_lang['invalid_response'] = 'Invalid response.';
$wpmerge_lang['wp_table_listing_error'] = 'Error while listing WP tables.';
$wpmerge_lang['alter_foreign_key_query_error'] = 'Error while temporarily altering foreign key query.';
$wpmerge_lang['invalid_old_tables_list'] = 'Invalid old tables list.';
$wpmerge_lang['invalid_new_tables_list'] = 'Invalid old tables list.';
$wpmerge_lang['server_wp_info_missing'] = 'Server WP info missing.';
$wpmerge_lang['src_site_db_table_prefix_missing'] = 'Source site DB table prefix is missing.';
$wpmerge_lang['wp_should_use_mysqli'] = 'WP should use MySQLi for this feature to work.';
$wpmerge_lang['invalid_testing_db_prefix'] = 'Invalid testing DB prefix.';
$wpmerge_lang['invalid_db_prefixes'] = 'Invalid DB prefixes.';
$wpmerge_lang['remote_clone_db_state_data_missing'] = 'Remote DB clone status data missing.';
$wpmerge_lang['remote_clone_invalid_status'] = 'Remote DB clone invalid status.';
$wpmerge_lang['remote_clone_db_unexpected_status'] = 'Remote DB clone unexpected status.';
$wpmerge_lang['clone_table_structure_query_error'] = 'Clone table structure query error.';
$wpmerge_lang['clone_view_structure_not_view_table'] = 'Clone view, not a view table.';
$wpmerge_lang['clone_view_structure_query_error'] = 'Clone view structure query error.';
$wpmerge_lang['clone_table_content_query_error'] = 'Clone table content query error.';
$wpmerge_lang['clone_table_details_invalid_data'] = 'Clone table details are invalid.';
$wpmerge_lang['clone_table_details_invalid_tables_count'] = 'Clone table details is having invalid tables count.';
$wpmerge_lang['no_changes_applied_to_prod_all_action_done_on_tmp_tables'] = 'No changes applied to Production site\'s DB tables. Changes applied on temporary tables which will be discarded.';
$wpmerge_lang['unable_find_unique_prefixes'] = 'Unable to find the unique prefixes.';
$wpmerge_lang['invalid_dev_plugin_version'] = 'Invalid dev plugin version.';
$wpmerge_lang['invalid_prod_plugin_version'] = 'Invalid prod plugin version.';
$wpmerge_lang['prod_dev_plugins_incompatible'] = 'WPMerge prod and dev plugins are incompatible.';
$wpmerge_lang['fix_db_serialization_in_dev_data_missing'] = 'Data is missing while fixing DB serialization in Dev.';
$wpmerge_lang['remote_fix_db_serialization_data_missing'] = 'Data is missing while fixing DB serialization in Prod.';



//service API messages
$wpmerge_lang['service_invalid_response'] = 'Invalid response. Please try again.';
$wpmerge_lang['service_login_error'] = 'Email or password is incorrect.';
$wpmerge_lang['service_expired'] = 'Your subscribed plan has expired. Please <a href="'.WPMERGE_SITE_SUBSCRIPTION_URL.'" target="_blank">renew your license</a>.';
$wpmerge_lang['service_limit_reached'] = 'You have reached the sites limit for your plan. Please <a href="'.WPMERGE_SITE_UPGRADE_URL.'" target="_blank">upgrade</a>.';

//recording detailed state
$wpmerge_lang['changes_applied_for_prod_do_prod_db_clone_and_apply_changes_for_dev'] = 'A test merge was performed. Please "Clone Prod DB & Apply Dev Changes" to enable recording.';
$wpmerge_lang['db_modification_not_present'] = 'DB modification required.';
$wpmerge_lang['changes_for_dev_in_dev_not_applied'] = 'Please "Clone Prod DB & Apply Dev Changes" to enable recording.';
$wpmerge_lang['recording_set_to_off'] = 'Recording set to off.';
$wpmerge_lang['all_ok'] = 'All ok.';



//sub-task names
$wpmerge_lang['list_db_tables'] = 'List prod DB tables';
$wpmerge_lang['backup_db'] = 'Backup DB';
$wpmerge_lang['compress_db'] = 'Compress DB';
$wpmerge_lang['download_db'] = 'Download DB';
$wpmerge_lang['un_compress_db'] = 'Uncompress DB';
$wpmerge_lang['pre_run_queries'] = 'Prepare to import DB';
$wpmerge_lang['run_queries'] = 'Import DB';
$wpmerge_lang['get_server_info'] = 'Get prod server info';
$wpmerge_lang['replace_db_links'] = 'Replace DB links';
$wpmerge_lang['post_run_queries'] = 'Clean up and finalise';

$wpmerge_lang['prepare_prod_bridge'] = 'Prepare prod bridge';
$wpmerge_lang['push_db_delta'] = 'Push DB changes';
$wpmerge_lang['remote_run_delta_queries'] = 'Run delta queries';
$wpmerge_lang['remote_replace_db_links'] = 'Replace prod DB links';
$wpmerge_lang['remote_run_db_final_modifications'] = 'Run DB final modification';
$wpmerge_lang['delete_prod_bridge'] = 'Delete prod bridge';
$wpmerge_lang['remote_clone_db'] = 'Create temporary, working tables for merge in prod.';
$wpmerge_lang['remote_finalise_tables'] = 'Finalize tables in prod.';

$wpmerge_lang['get_all_changed_files'] = 'Get list of changed files';
$wpmerge_lang['zip_changed_files'] = 'Zip changed files';

$wpmerge_lang['do_apply_changes_for_prod_in_dev'] = 'Test merge';
$wpmerge_lang['apply_changes_for_dev_in_dev_pre_check'] = 'Check before applying changes';
$wpmerge_lang['do_db_modification'] = 'Modify Dev DB';
$wpmerge_lang['do_apply_changes_for_dev_in_dev'] = 'Apply dev changes';

$wpmerge_lang['list_dev_db_tables'] = 'List dev DB tables';
$wpmerge_lang['do_fix_db_serialization_in_dev'] = 'Fix DB serialization';

$wpmerge_lang['remote_fix_db_serialization'] = 'Fix DB serialization';

//overall/main task names
$wpmerge_lang['import_prod_db'] = 'Clone prod DB';
$wpmerge_lang['export_dev_db_delta_2_prod'] = 'Apply dev changes to prod';
$wpmerge_lang['export_changed_files_in_dev'] = 'Download changed files';
$wpmerge_lang['apply_changes_for_prod_in_dev'] = 'Test merge';
$wpmerge_lang['apply_changes_for_dev_in_dev'] = 'Apply dev changes';
$wpmerge_lang['do_db_modification_in_dev'] = 'Modify Dev DB';
$wpmerge_lang['fix_db_serialization_in_dev'] = 'Fix DB serialization in Dev';
$wpmerge_lang['fix_db_serialization_in_prod'] = 'Fix DB serialization in Prod';

<?php
/**
 * WPMerge
 * Copyright (c) 2018 Revmakx LLC
 * revmakx.com
 */

if(!defined('ABSPATH')){ exit; }

/*  Copyright 2015  Matthew Van Andel  (email : matt@mattvanandel.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


include_once(WPMERGE_PATH . '/templates/list_table.php');


class wpmerge_query_browser_list_table extends wpmerge_list_table {
    
    /** ************************************************************************
     * Normally we would be querying data from a database and manipulating that
     * for use in your list table. For this example, we're going to simplify it
     * slightly and create a pre-built array. Think of this as the data that might
     * be returned by $wpdb->query()
     * 
     * In a real-world scenario, you would make your own custom query inside
     * this class' prepare_items() method.
     * 
     * @var array 
     **************************************************************************/


    private $date_time_format;
    private $query_browser_all_data;


    /** ************************************************************************
     * REQUIRED. Set up a constructor that references the parent constructor. We 
     * use the parent reference to set some default configs.
     ***************************************************************************/
    function __construct(){
        global $status, $page;
        $date_format = get_option('date_format');
        //$time_format = get_option('time_format');
        $time_format = 'g:i:s a';
        $this->date_time_format = $time_format.' @ '.$date_format;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'query',     //singular name of the listed records
            'plural'    => 'queries',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        
    }


    /** ************************************************************************
     * Recommended. This method is called when the parent class can't find a method
     * specifically build for a given column. Generally, it's recommended to include
     * one method for each column you want to render, keeping your package class
     * neat and organized. For example, if the class needs to process a column
     * named 'title', it would first see if a method named $this->column_title() 
     * exists - if it does, that method will be used. If it doesn't, this one will
     * be used. Generally, you should try to use custom column methods as much as 
     * possible. 
     * 
     * Since we have defined a column_title() method later on, this method doesn't
     * need to concern itself with any column with a name of 'title'. Instead, it
     * needs to handle everything else.
     * 
     * For more detailed insight into how columns are handled, take a look at 
     * WP_List_Table::single_row_columns()
     * 
     * @param array $item A singular item (one full row's worth of data)
     * @param array $column_name The name/slug of the column to be processed
     * @return string Text or HTML to be placed inside the column <td>
     **************************************************************************/
    function column_default($item, $column_name){
        switch($column_name){
            // case 'http_request_id':
            // case 'director':
            //     return $item[$column_name];
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }


    /** ************************************************************************
     * Recommended. This is a custom column method and is responsible for what
     * is rendered in any column with a name/slug of 'title'. Every time the class
     * needs to render a column, it first looks for a method named 
     * column_{$column_title} - if it exists, that method is run. If it doesn't
     * exist, column_default() is called instead.
     * 
     * This example also illustrates how to implement rollover actions. Actions
     * should be an associative array formatted as 'slug'=>'link html' - and you
     * will need to generate the URLs yourself. You could even ensure the links
     * 
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     **************************************************************************/
    // function column_title($item){
        
    //     //Build row actions
    //     $actions = array(
    //         'edit'      => sprintf('<a href="?page=%s&action=%s&movie=%s">Edit</a>',$_REQUEST['page'],'edit',$item['ID']),
    //         'delete'    => sprintf('<a href="?page=%s&action=%s&movie=%s">Delete</a>',$_REQUEST['page'],'delete',$item['ID']),
    //     );
        
    //     //Return the title contents
    //     return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
    //         /*$1%s*/ $item['title'],
    //         /*$2%s*/ $item['ID'],
    //         /*$3%s*/ $this->row_actions($actions)
    //     );
    // }

    function column_http_request_id($item){
        $date_time = date($this->date_time_format, $item['logtime']);

        $is_all_checked = true;
        $queries_content = '';
        $query_count = count($item['queries']);
        foreach($item['queries'] as $query_data){
            $checked_html = '';
            if($query_data['is_record_on'] == '1'){
                $checked_html = ' checked="checked"';
            }
            else{
                $is_all_checked = false;
            }
            $query_content = '<tr><td><input type="checkbox" name="%1$s[]" value="'.$query_data['id'].'" '.$checked_html.' class="query_cb" /></td><td class="query_cont">'.htmlentities($query_data['query']).'</td></tr>';
            $query_content .= "\n";
            $queries_content .= $query_content;
        }

        $row_html = '
        <div class="row"><div class="row_main_content"><span class="timestamp_cont">'.$date_time.'</span><span class="group_query_count_cont"> '.$query_count.' '.($query_count != 1 ? 'Queries' : 'Query').'</span></div>
            <div class="row_details" style="display:none ;">
            <table>
            '.$queries_content.'
            </table>
            </div>       
        </div>';

        return $row_html;

    }


    /** ************************************************************************
     * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
     * is given special treatment when columns are processed. It ALWAYS needs to
     * have it's own method.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     **************************************************************************/
    function column_cb($item){
        $is_all_queries_checked = $this->is_all_queries_checked($item['queries']);
        $checked_html = '';
        if($is_all_queries_checked){
            $checked_html = ' checked="checked"';
        }
        return '<input type="checkbox" name="" value="" '.$checked_html.'  class="query_group_cb"/>';
    }

    function is_all_queries_checked($queries){
        if(empty($queries)){
            return false;
        }
        foreach($queries as $query_data){
            if($query_data['is_record_on'] != '1'){
                return false;
            }
        }
        return true;
    }


    /** ************************************************************************
     * REQUIRED! This method dictates the table's columns and titles. This should
     * return an array where the key is the column slug (and class) and the value 
     * is the column's title text. If you need a checkbox for bulk actions, refer
     * to the $columns array below.
     * 
     * The 'cb' column is treated differently than the rest. If including a checkbox
     * column in your table you must create a column_cb() method. If you don't need
     * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox"/>', //Render a checkbox instead of text
            'http_request_id'     => 'Timestamp'/* ,
            'rating'    => 'Rating',
            'director'  => 'Director' */
        );
        return $columns;
    }


    /** ************************************************************************
     * Optional. If you want one or more columns to be sortable (ASC/DESC toggle), 
     * you will need to register it here. This should return an array where the 
     * key is the column that needs to be sortable, and the value is db column to 
     * sort by. Often, the key and value will be the same, but this is not always
     * the case (as the value is a column name from the database, not the list table).
     * 
     * This method merely defines which columns should be sortable and makes them
     * clickable - it does not handle the actual sorting. You still need to detect
     * the ORDERBY and ORDER querystring variables within prepare_items() and sort
     * your data accordingly (usually by modifying your query).
     * 
     * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
     **************************************************************************/
    function get_sortable_columns() {
        $sortable_columns = array(
           'http_request_id'    => array('id', false),
           // 'title'     => array('title',false),     //true means it's already sorted
           // 'rating'    => array('rating',false),
            //'director'  => array('director',false)
        );
        return $sortable_columns;
    }


    /** ************************************************************************
     * Optional. If you need to include bulk actions in your list table, this is
     * the place to define them. Bulk actions are an associative array in the format
     * 'slug'=>'Visible Title'
     * 
     * If this method returns an empty value, no bulk action will be rendered. If
     * you specify any bulk actions, the bulk actions box will be rendered with
     * the table automatically on display().
     * 
     * Also note that list tables are not automatically wrapped in <form> elements,
     * so you will need to create those manually in order for bulk actions to function.
     * 
     * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_bulk_actions() {
        // $actions = array(
        //     //'delete'    => 'Delete'
        // );
        // return $actions;
    }


    /** ************************************************************************
     * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
     * For this example package, we will handle it in the class to keep things
     * clean and organized.
     * 
     * @see $this->prepare_items()
     **************************************************************************/
    function process_bulk_action() {
        
        //Detect when a bulk action is being triggered...
        // if( 'delete'===$this->current_action() ) {
        //     wp_die('Items deleted (or they would be if we had items to delete)!');
        // }
        
    }


    /** ************************************************************************
     * REQUIRED! This is where you prepare your data for display. This method will
     * usually be used to query the database, sort and filter the data, and generally
     * get it ready to be displayed. At a minimum, we should set $this->items and
     * $this->set_pagination_args(), although the following properties and methods
     * are frequently interacted with here...
     * 
     * @global WPDB $wpdb
     * @uses $this->_column_headers
     * @uses $this->items
     * @uses $this->get_columns()
     * @uses $this->get_sortable_columns()
     * @uses $this->get_pagenum()
     * @uses $this->set_pagination_args()
     **************************************************************************/
    function prepare_items() {
        global $wpdb; //This is used only if making any database queries

        /**
         * First, lets decide how many records per page to show
         */
        $per_page = 5;
        
        
        /**
         * REQUIRED. Now we need to define our column headers. This includes a complete
         * array of columns to be displayed (slugs & titles), a list of columns
         * to keep hidden, and a list of columns that are sortable. Each of these
         * can be defined in another method (as we've done here) before being
         * used to build the value for our _column_headers property.
         */
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        
        /**
         * REQUIRED. Finally, we build an array to be used by the class for column 
         * headers. The $this->_column_headers property takes an array which contains
         * 3 other arrays. One for all columns, one for hidden columns, and one
         * for sortable columns.
         */
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        
        /**
         * Optional. You can handle your bulk actions however you see fit. In this
         * case, we'll handle them within our package just to keep things clean.
         */
        $this->process_bulk_action();
    
        $query_selector_obj = new wpmerge_dev_query_selector();
        $browse_options = array();
        $browse_options['pagination']['current_page'] = isset($_GET['paged']) ? $_GET['paged'] : '';
        $browse_options['pagination']['order'] = isset($_GET['order']) ? $_GET['order'] : '';
        $browse_options['pagination']['items_per_page'] = isset($_GET['items_per_page']) ? $_GET['items_per_page'] : '';
        $browse_options['filters']['show_queries'] = isset($_GET['show_queries']) ? $_GET['show_queries'] : '';
        $query_browser_all_data = $query_selector_obj->get_all_page_data($browse_options);
     
        
        /**
         * REQUIRED. Now we can add our *sorted* data to the items property, where 
         * it can be used by the rest of the class.
         */
        $this->items = $query_browser_all_data['page_data'];

        $this->query_browser_all_data = $query_browser_all_data;//custom
        
        
        /**
         * REQUIRED. We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args( array(
            'total_items' => $query_browser_all_data['pagination']['total_items'],                  //WE have to calculate the total number of items
            'per_page'    => $query_browser_all_data['pagination']['items_per_page'],                     //WE have to determine how many items to show on a page
            'total_pages' => $query_browser_all_data['pagination']['total_pages']   //WE have to calculate the total number of pages
        ) );
    }

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @since 3.1.0
	 * @param string $which
	 */
	protected function display_tablenav( $which ) {
		if ( 'top' === $which ) {
			wp_nonce_field( 'bulk-' . $this->_args['plural'] );
		}
		?>
	<div class="tablenav <?php echo esc_attr( $which ); ?>">

		<?php if ( $this->has_items() ): ?>
		<div class="alignleft actions bulkactions">
			<?php $this->bulk_actions( $which ); ?>
		</div>
		<?php endif;
		$this->extra_tablenav( $which );
        $this->pagination( $which );
        $this->show_items_per_page_selector( $which );
?>

		<br class="clear" />
	</div>
<?php
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination
	 *
	 * @since 3.1.0
	 *
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {
        if($which == 'top'){  
            $show_queries = $this->query_browser_all_data['filters']['show_queries'];
            $current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

            $total_selected_queries = $this->query_browser_all_data['total_selected_queries'];
            $queries_selected_txt = ' queries selected for merging';
            if($queries_selected_txt == 1){
                $queries_selected_txt = ' query selected for merging';
            }
        ?>
        <div class="alignleft">
            List of HTTP calls
            <select name="show_queries" id="show_queries">
                <option value="recorded" <?php echo ($show_queries == 'recorded' ? 'selected' : ''); ?> data-onselect-url="<?php echo esc_url( add_query_arg( 'show_queries', 'recorded', $current_url ) ); ?>">Show calls with recorded queries</option>
                <option value="all" <?php echo ($show_queries == 'all' ? 'selected' : ''); ?> data-onselect-url="<?php echo esc_url( add_query_arg( 'show_queries', 'all', $current_url ) ); ?>">Show all queries</option>
            </select>
			<span id="queries_selected_cont"><?php echo $total_selected_queries.' '.$queries_selected_txt; ?> </span> 
		</div>
        <?php
         }
    }

    protected function show_items_per_page_selector( $which ) {
        if($which == 'top'){  
            $allowed_items_per_page = array(10, 25, 50);            $items_per_page = $this->query_browser_all_data['pagination']['items_per_page'];
            $current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
        ?>

        <div class="alignright" style="margin-right: 15px;">
            <select name="items_per_page" id="items_per_page">
                <?php foreach($allowed_items_per_page as $per_page){
                ?>
                <option value="<?php echo $per_page; ?>" <?php echo ($items_per_page == $per_page ? 'selected' : ''); ?> data-onselect-url="<?php echo esc_url( add_query_arg( 'items_per_page', $per_page, $current_url ) ); ?>">Show <?php echo $per_page; ?> per page</option>
                <?php
                }
                ?>
            </select>
		</div>
        <?php
         }
    }


}





/** ************************ REGISTER THE TEST PAGE ****************************
 *******************************************************************************
 * Now we just need to define an admin page. For this example, we'll add a top-level
 * menu item to the bottom of the admin menus.
 */
// function tt_add_menu_items(){
//     add_menu_page('Example Plugin List Table', 'List Table Example', 'activate_plugins', 'tt_list_test', 'tt_render_list_page');
// } add_action('admin_menu', 'tt_add_menu_items');





/** *************************** RENDER TEST PAGE ********************************
 *******************************************************************************
 * This function renders the admin page and the example list table. Although it's
 * possible to call prepare_items() and display() from the constructor, there
 * are often times where you may need to include logic here between those steps,
 * so we've instead called those methods explicitly. It keeps things flexible, and
 * it's the way the list tables are used in the WordPress core.
 */
function wpmerge_dev_query_browser_page(){
    
    //Create an instance of our package class...
    $testListTable = new wpmerge_query_browser_list_table();
    //Fetch, prepare, sort, and filter our data...
    $testListTable->prepare_items();

    $wpmerge_page = 'dev_query_browser';
    include(WPMERGE_PATH . '/templates/dev_header.php');
    

    
    ?>
    <!-- <div class="wrap">
        <h1 class="wp-heading-inline">WPMerge.io</h1>
        <div style="float: right;"><a href="mailto:help@wpmerge.io?body=WPMerge Plugin v<?php echo WPMERGE_VERSION; ?>" target="_blank">Support</a></div>

        <div style="clear:both;"></div> -->

        <?php if(wpmerge_is_help_toggle_state('dev_selected_queries_info', '1')){ ?>
        <div class="notice notice-info is-dismissible wpmerge_notice_dismiss" id="wpmerge_selected_queries_info">
            <p>The selected queries will be included when applying changes. If you do not want certain queries to be applied, you can unselect them before applying changes.</p>
        </div>
        <?php } ?>

        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="" method="get">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo isset($_REQUEST['page']) ? $_REQUEST['page'] : ''; ?>" />
            <!-- Now we can render the completed list table -->
            <div id="wpmerge_query_group_cont">
            <?php $testListTable->display(); ?>
            </div>            
        </form>
        
    <!-- </div> -->
    <?php
    include(WPMERGE_PATH . '/templates/dev_footer.php');
}
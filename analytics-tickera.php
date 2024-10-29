<?php

/*
 * Plugin Name: Analytics for Tickera
  Plugin URI: 
  Description: Shows the number of tickets sold by event and category
  Version: 1.00
  Author: Владимир Дубровский
  Author URI: https://t.me/inferus_v
 */

add_action( 'admin_menu', 'anlti_add_sub' );

function anlti_add_sub( $context ){
    add_submenu_page(
      'edit.php?post_type=tc_events',
      'Analytics',
      'Analytics',
      true,
      'analytics-tickera',
      'anlti_analytics_content'
  );
}

function anlti_analytics_content(){
    $ta_date = isset($_REQUEST['ta_date']) ? sanitize_option('date_format', $_REQUEST['ta_date']) : date('Y-m-d'); 

    ?>
        <form style="margin-top: 20px; margin-bottom: -20px" action="<?php echo admin_url( 'edit.php?post_type=tc_events&page=analytics-tickera' ); ?>" method="POST"><label class="tc-calendar-field-title">Date</label><br />
                                <input type="date" name='ta_date'class="ta_date_field" value='<?php echo esc_attr($ta_date)?>' />
        <?php submit_button('Search', 'small', 'submit', false); ?>
        </form>
    <?php
  
    $res = array();
    $cats = array();
    $loop = new WP_Query( array( 'post_type' => 'shop_order', 'post_status' => 'wc-completed', 'posts_per_page' => -1));

    while ($loop->have_posts()) : $loop->the_post();
        $id = get_the_ID();

        $order = wc_get_order($id)->get_items();

        foreach ($order as $product) {
            $eId = get_post_meta($product['product_id'], '_event_name', true);
            $eDate = (new DateTime(get_post_meta($eId, 'event_date_time', true)))->Format('Y-m-d');
            
            if($eDate == $ta_date){
              $q = $product['quantity'];
              
              $terms = get_the_terms($product['product_id'], 'product_cat' );   
              $eTitle = get_the_title($eId);
              
              foreach ($terms as $cat){
                  $cats[$cat->term_id] = $cat->name;
                    
                  if(array_key_exists($eId, $res)){
                      $res[$eId]['total']+= $q;
                      if(array_key_exists($cat->term_id, $res[$eId]['categories']))
                          $res[$eId]['categories'][$cat->term_id] += $q;
                      else
                          $res[$eId]['categories'][$cat->term_id] = $q;
                  } else {
                      $res[$eId] = array(
                          'categories' => array(
                              $cat->term_id => $q
                          ), 
                          'name' => $eTitle,
                          'total' => $q
                      );
                  }
              }
           }
        }
    endwhile;

    //printf("<pre>%s</pre>", print_r($cats, true));
    //printf("<pre>%s</pre>", print_r($res, true));

    anlti_render_ticket_analytics($cats, $res);

    echo 'See also!<br>
    - Plugin for deep copying events – creating a new event by cloning "event - seating chart - tickets"<br>
    - Project management system - <a href="https://decima.business">decima.business</a><br>
    For details write to developer  in telegram (<a href="https://t.me/inferus_v">@inferus_v</a>), or in WhatsApp (+79910201066)';
} 




function anlti_render_ticket_analytics($cats, $res){
    class anlti_List_Table extends WP_List_Table {
        var $tab_data = array(); 
        var $cats = array(); 

        function get_columns(){
            return $this->cats;
        }

        function column_default( $item, $column_name ) {
            return $item[ $column_name];
        }
         
        function prepare_items() {
            $columns = $this->get_columns();
            $hidden = array();
            $sortable = array();
        
            $this->_column_headers = array($columns, $hidden, $sortable);
            $this->items = $this->tab_data;   
        }
    }

    if( ! class_exists( 'WP_List_Table' ) ) {
        require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
    }

    $myListTable = new anlti_List_Table();

    $myListTable->cats = ['name' => 'Name'] + $cats;

    $td = array();
    foreach ($res as $r) {
        $td[] = $r['categories'] + ['name' => $r['name'].' ('.$r['total'].')'];
    }
    $myListTable->tab_data = $td;

    $myListTable->prepare_items();
    $myListTable->display();
}

?>
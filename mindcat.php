<?php
/*
Plugin Name: MindCat
Plugin URI: http://ecolosites.eelv.fr/mindcat/
Description: Displays categories and subcategories as a mindmap
Version: 1.1.1
Author: bastho
Author URI: http://ecolosites.eelv.fr/
License: GPLv2
Text Domain: mindcat
Domain Path: /languages/
Tags: category, categories, mindmap
*/


$MindCat = new MindCat();
class MindCat{
    function __construct(){
        load_plugin_textdomain( 'mindcat', false, 'mindcat/languages' );

        add_action('wp_enqueue_scripts', array(&$this,'scripts'));
        add_action('widgets_init', array(&$this,'register_widget'));

        add_action('category_edit_form_fields', array(&$this,'edit'));
        add_action('delete_category', array(&$this,'delete'));
        add_action('edited_category', array(&$this,'save'));

        add_shortcode('mindcat', array(&$this,'mindmap'));

    }
    /**
     * PHP4 Constructor
     */
    public function MindCat(){
        $this->__construct();
    }
    function scripts(){
        wp_enqueue_style('mindcat', plugins_url('/mindcat.css', __FILE__), false, null);
        wp_enqueue_script('mindcat', plugins_url('/mindcat.js', __FILE__),array('jquery'),false,true);		
    }
    function register_widget(){
        register_widget('MindCat_Widget');	
    }
    function subcat($cat,$level,$args=array()){
        $terms = get_terms( 'category', array(
            'parent'=>$cat,
            'hirearchical'=>false,
            'hide_empty'=>$args['hide_empty'],
         ) );
         $ret='';
         $MindCatColors = get_option('MindCatColors',array());
         if(!is_array($MindCatColors)) $MindCatColors=array();

         if(sizeof($terms)>0){
            $ret.='<ul>';
            foreach ($terms as $term) {
                    $link=get_term_link( $term, 'category' );
                $bgcolor = isset($MindCatColors[$term->term_id]['bg'])?$MindCatColors[$term->term_id]['bg']:'#CCCCCC';
                $color = isset($MindCatColors[$term->term_id]['txt'])?$MindCatColors[$term->term_id]['txt']:'#333333';
                    $ret.='<li data-id="'.$term->term_id.'" class="mindcat_child"><a href="'.$link.'" style="background:'.$bgcolor.';color:'.$color.';">'.$term->name;
                    if(1==$args['count']){
                            $ret.='<span class="mindcat_count">'.$term->count.'</span>';
                    }
                    $ret.='</a>';
                    if($args['max_level']>0 && $args['max_level']>$level){
                            $ret.=self::subcat($term->term_id,$level+1,$args);
                    }
                    $ret.='</li>';
            }
            $ret.='</ul>';
        }
        return $ret;
    }
    function mindmap($args=''){
        extract( shortcode_atts( array(
                  'cat' => '',
                  'size' => 50,
                  'title'=>'',
                  'hide_empty'=>0,
                  'count'=>0,
                  'max_level'=>0
         ), $args ) );	
        $root = get_option('blogname');
        $link= get_option('siteurl');


        $bgcolor = '#CCCCCC';
        $color = '#33333';
        $posts_count=0;

        if($cat==''){
            $cat=0;
        }	
        if(is_string($cat) || $cat!='0'){

            if(is_numeric($cat)){
                    $term=get_term($cat,'category');
            }
            else{
                    $term=get_term_by('slug',$cat,'category');
            }	
            $cat=0;
            if(!is_wp_error($term)){
                $root=$term->name;
                $link=get_term_link( $term, 'category' );
                $cat=$term->term_id;
                $posts_count=$term->count;
                $MindCatColors = get_option('MindCatColors',array());
                if(!is_array($MindCatColors)) $MindCatColors=array();
                $bgcolor = isset($MindCatColors[$term->term_id]['bg'])?$MindCatColors[$term->term_id]['bg']:'#CCCCCC';
                $color = isset($MindCatColors[$term->term_id]['txt'])?$MindCatColors[$term->term_id]['txt']:'#333333';
            }
        }
        if(!empty($title)){
            $root=$title;
        }

        if(false == $hide_empty || $count>0){
            $ret='<div class="mindcat" data-size="'.$size.'"><ul><li class="mindcat_root">';
            $ret.='<a href="'.$link.'" style="background:'.$bgcolor.';color:'.$color.';">'.$root;
            if($cat!=0 && 1==$count){
                   $ret.='<span class="mindcat_count">'.$posts_count.'</span>';
            }
            $ret.='</a>';
            $ret.=self::subcat($cat,1,$args);
            $ret.='</li></ul></div>';
        }
        return $ret;
    }
    // Save options
    function save($id) {
        if(isset($_POST['MindCatColor']) ){
            $MindCatColors = get_option('MindCatColors',array());
            if(!is_array($MindCatColors)) $MindCatColors=array();
            $MindCatColors[$id]=(isset($_POST['MindCatColor']) && is_array($_POST['MindCatColor'])) ? $_POST['MindCatColor'] : array('bg'=>'#CCCCCC','txt'=>'#333333');
            update_option('MindCatColors',$MindCatColors);		
        }
    }
    function delete($id) {
        $MindCatColors = get_option('MindCatColors',array());
        if(isset($MindCatColors[$id])){
            unset($MindCatColors[$id]);
        }
        update_option('MindCatColors',$MindCatColors);
    }
    // add to the edit form
    function edit($tax){
        $id = $tax->term_id;
        $MindCatColors = get_option('MindCatColors',array());

        if(!is_array($MindCatColors)){
            $MindCatColors=array();
        }
        $bgcolor = isset($MindCatColors[$id]['bg'])?$MindCatColors[$id]['bg']:'#CCCCCC';
        $color = isset($MindCatColors[$id]['txt'])?$MindCatColors[$id]['txt']:'#333333';
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'my-script-handle', plugins_url('my-script.js', __FILE__ ), array( 'wp-color-picker' ), false, true );
        wp_enqueue_script('mindcat', plugins_url('/mindcat.js', __FILE__),array('jquery'),false,true);	
        ?>
        <table id="sortableTableNaN" class="form-table">
                <tbody>
                        <tr class="form-field form-required">
                                <th valign="top" scope="row">
                                        <label for="name"><?php _e('Background color','mindcat'); ?></label>
                                </th>
                                <td>
                                        <input type="text" name="MindCatColor[bg]" value="<?=$bgcolor?>" class="mindcat-color-field" data-default-color="#cccccc" />
                                </td>
                        </tr>
                        <tr class="form-field form-required">
                                <th valign="top" scope="row">
                                        <label for="name"><?php _e('Color','mindcat'); ?></label>
                                </th>
                                <td>
                                        <input type="text" name="MindCatColor[txt]" value="<?=$color?>" class="mindcat-color-field" data-default-color="#333333" />
                                </td>
                        </tr>
                </tbody>
        </table>	     
        <?php
    }
	
}



class MindCat_Widget extends WP_Widget {
   function MindCat_Widget() {
  	  parent::__construct(false, __( 'MindCat', 'mindcat' ),array('description'=>__( 'Displays categories and subcategories as a mindmap', 'mindcat' )));
   }
   function widget($args, $instance) {
        extract( $args );
	$title = isset($instance['title'])?$instance['title']:'';
        $size = isset($instance['size'])?$instance['size']:50;
        $cat = isset($instance['cat'])?$instance['title']:0;
 		
        global $MindCat;

        echo $args['before_widget'];
        if(!empty($title)){
                echo $args['before_title'];
                echo $title;
                echo $args['after_title'];
        }			
        echo $MindCat->mindmap($instance);
        echo $args['after_widget'];	
   }
   
   function update($new_instance, $old_instance) {
       return $new_instance;
   }

   function form($instance) { 	    
	$title = isset($instance['title'])?$instance['title']:'';
        $size = isset($instance['size'])?$instance['size']:50;
        $cat = isset($instance['cat'])?$instance['cat']:0;
        $count = isset($instance['count'])?$instance['count']:0;
        $hide_empty = isset($instance['hide_empty'])?$instance['hide_empty']:0;
        $max_level = isset($instance['max_level'])?$instance['max_level']:0;

         ?>
         <input type="hidden" id="<?php echo $this->get_field_id('title'); ?>-title" value="<?php echo $title; ?>">
         <p>
         <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title','mindcat'); ?>
         <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
         </label>
         </p>       

         <p style="margin-top:10px;">
         <label for="<?php echo $this->get_field_id('size'); ?>"><?php _e('Size','mindcat'); ?>
         <input class="widefat" id="<?php echo $this->get_field_id('size'); ?>" name="<?php echo $this->get_field_name('size'); ?>" type="range" min="20" max="90" value="<?php echo $size; ?>" />
         </label>
         </p>

         <p>
          <label for="<?php echo $this->get_field_id('cat'); ?>"><?php _e('Root','mindcat'); ?>
          <select  id="<?php echo $this->get_field_id('cat'); ?>" name="<?php echo $this->get_field_name('cat'); ?>">
       		<option value='0'><?php _e('Site root','mindcat') ?></option>
       <?php 
	   	$cats = get_terms( 'category', array(
		 	'hirearchical'=>false,
		 	'hide_empty'=>false,
		 ) );
		foreach($cats as $cate){ ?>
       	<option value="<?=$cate->term_id?>" <?php if($cate->term_id==$cat){ echo'selected';} ?>><?=$cate->name?></option>
       <?php  }  ?>
       </select>
       </label>
       </p>
       
       <p>
       	<label for="<?php echo $this->get_field_id('count'); ?>"><?php _e('Show posts count','mindcat'); ?>
       	<select  id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>">
  	       	<option value="0" <?php if(0==$count){ echo'selected';} ?>><?php _e('No','mindcat'); ?></option>
	       	<option value="1" <?php if(1==$count){ echo'selected';} ?>><?php _e('Yes','mindcat'); ?></option>
       </select>
       </label>
       </p>
       
       <p>
       	<label for="<?php echo $this->get_field_id('hide_empty'); ?>"><?php _e('Hide empties','mindcat'); ?>
       	<select  id="<?php echo $this->get_field_id('hide_empty'); ?>" name="<?php echo $this->get_field_name('hide_empty'); ?>">
  	       	<option value="0" <?php if(0==$hide_empty){ echo'selected';} ?>><?php _e('No','mindcat'); ?></option>
	       	<option value="1" <?php if(1==$hide_empty){ echo'selected';} ?>><?php _e('Yes','mindcat'); ?></option>
       </select>
       </label>
       </p>
       
        <p>
       	<label for="<?php echo $this->get_field_id('max_level'); ?>"><?php _e('Max level','mindcat'); ?>
       	<select  id="<?php echo $this->get_field_id('max_level'); ?>" name="<?php echo $this->get_field_name('max_level'); ?>">
  	       	<option value="0" <?php if(0==$max_level){ echo'selected';} ?>><?php _e('None','mindcat'); ?></option>
       		<?php for($l=1 ; $l<10 ; $l++) : ?>
  	       	<option value="<?php echo $l; ?>" <?php if($l==$max_level){ echo'selected';} ?>><?php echo $l; ?></option>
       		<?php endfor; ?>
       </select>
       </label>
       </p>      
       
       <?php
   }
}
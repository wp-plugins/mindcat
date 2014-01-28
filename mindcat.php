<?php
/*
Plugin Name: MindCat
Plugin URI: http://ecolosites.eelv.fr/mindcat/
Description: Displays categories and subcategories as a mindmap
Version: 1.0
Author: bastho
Author URI: http://ecolosites.eelv.fr/
License: GPLv2
Text Domain: mindcat
Domain Path: /languages/
Tags: category, categories, mindmap
*/

load_plugin_textdomain( 'mindcat', false, 'mindcat/languages' );

add_action('wp_enqueue_scripts', array('MindCat','scripts'));
add_action('widgets_init', array('MindCat','register_widget'));

add_action('category_edit_form_fields', array('MindCat','edit'));
add_action('delete_category', array('MindCat','delete'));
add_action('edited_category', array('MindCat','save'));

class MindCat{

	function scripts(){
		wp_enqueue_style('mindcat', plugins_url('/mindcat.css', __FILE__), false, null);
		wp_enqueue_script('mindcat', plugins_url('/mindcat.js', __FILE__),array('jquery'),false,true);		
	}
	function register_widget(){
		register_widget('MindCat_Widget');	
	}
	function subcat($cat){
		$terms = get_terms( 'category', array(
		 	'parent'=>$cat,
		 	'hirearchical'=>false,
		 	'hide_empty'=>false,
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
				$ret.='<li data-id="'.$term->term_id.'"><a href="'.$link.'" style="background:'.$bgcolor.';color:'.$color.';">'.$term->name.'</a>'.self::subcat($term->term_id).'</li>';
			}
			$ret.='</ul>';
		 }
		return $ret;
	}
	function mindmap($args=''){
		extract( shortcode_atts( array(
		      'cat' => '',
		      'size' => 50,
		      'title'=>''
	     ), $args ) );	
		 $root = get_option('blogname');
		 $link= get_option('siteurl');
		 
		$bgcolor = '#CCCCCC';
		$color = '#33333';
		 
		 if($cat!=0){
		 	$term=get_term($cat,'category');
			 $cat=0;
			 if(!is_wp_error($term)){
				$root=$term->name;
				$link=get_term_link( $term, 'category' );
				$cat=$term->term_id;
				$MindCatColors = get_option('MindCatColors',array());
	   			if(!is_array($MindCatColors)) $MindCatColors=array();
	   			$bgcolor = isset($MindCatColors[$term->term_id]['bg'])?$MindCatColors[$term->term_id]['bg']:'#CCCCCC';
			    $color = isset($MindCatColors[$term->term_id]['txt'])?$MindCatColors[$term->term_id]['txt']:'#333333';
				
			 }
		 }
		 
		 $ret='<div class="mindcat" data-size="'.$size.'"><ul><li>';
		 $ret.='<a href="'.$link.'" style="background:'.$bgcolor.';color:'.$color.';">'.$root.'</a>';
		 $ret.=self::subcat($cat);
		 $ret.='</li></ul></div>';
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
		if(isset($MindCatColors[$id])) unset($MindCatColors[$id]);
		update_option('MindCatColors',$MindCatColors);
	}
	// add to the edit form
	function edit($tax){
	   $id = $tax->term_id;
	   $MindCatColors = get_option('MindCatColors',array());
	   
	   if(!is_array($MindCatColors)) $MindCatColors=array();
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
  	  parent::WP_Widget(false, __( 'MindCat', 'mindcat' ),array('description'=>__( 'Displays categories and subcategories as a mindmap', 'mindcat' )));
   }
   function widget($args, $instance) {
       extract( $args );
	   $title = isset($instance['title'])?$instance['title']:'';
       $size = isset($instance['size'])?$instance['size']:50;
       $cat = isset($instance['cat'])?$instance['title']:0;
 		
		
		echo $args['before_widget'];
		if(!empty($title)){
			echo $args['before_title'];
			echo $title;
			echo $args['after_title'];
		}			
		echo MindCat::mindmap($instance);
		echo $args['after_widget'];	
   }
   
   function update($new_instance, $old_instance) {
       return $new_instance;
   }

   function form($instance) { 	    
	  $title = isset($instance['title'])?$instance['title']:'';
      $size = isset($instance['size'])?$instance['size']:50;
      $cat = isset($instance['cat'])?$instance['cat']:0;
      
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
       <?php
   }

}


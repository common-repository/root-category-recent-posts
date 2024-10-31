<?php

/*
Plugin Name: Root Category Recent Posts
Description: This widget displays your recent posts according the current main category. Based on the "Recent Posts" widget. The plugin "Category class" must be installed.
Author: LordPretender
Version: 1.2
Author URI: http://www.duy-pham.fr
Domain Path: /languages
*/

//http://www.fruityfred.com/2012/08/20/internationaliser-traduire-un-plugin-wordpress/
load_plugin_textdomain('root-category-recent-posts', false, dirname( plugin_basename( __FILE__ ) ). '/languages/');

//Déclaration de notre extention en tant que Widget
function register_RCRP_Widget() {
    register_widget( 'SLPW_Widget' );
}
add_action( 'widgets_init', 'register_RCRP_Widget' );

/**
* Documentation : http://codex.wordpress.org/Widgets_API
* S'inspirer de wp-includes/default-widgets.php (WP_Widget_Recent_Posts)
*/
class SLPW_Widget extends WP_Widget {
	private $libInstalled;
	
	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		parent::__construct(
			'rcrp_widget', // Base ID
			'Root Category Recent Posts', // Name
			array( 'description' => __('This widget displays your recent posts according the current main category. Based on the "Recent Posts" widget. The plugin "Category class" must be installed.', 'root-category-recent-posts'), ) // Args
		);
		
		$this->libInstalled = class_exists("CategoryClassSingleton");
	}
	
	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		
		if($this->libInstalled){
			ob_start();
			extract($args);

			$title = ( ! empty( $instance['title'] ) ) ? $instance['title'] : __( 'Recent Posts' );
			$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );
			$number = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : 10;
			if ( ! $number )
	 			$number = 10;
			$show_date = isset( $instance['show_date'] ) ? $instance['show_date'] : false;
			$show_category = isset( $instance['category'] ) ? $instance['category'] : false;
			
			//Chargement des catégories avec les derniers articles
			CategoryClassSingleton::CCS_loadCategory(0, TRUE, TRUE, $number, FALSE, "DESC");
			
			if(!is_front_page()){
				//On récupère la catégorie racine où se trouve la catégorie actuelle
				$category = CategoryClassSingleton::CCS_getCategories(CategoryClassSingleton::CCS_getCurrentCategories(), TRUE, TRUE);
				
				//On change la catégorie racine pour celle que nous venons de trouver
				if(count($category) > 0)CategoryClassSingleton::CCS_setRootCategory($category[0]);
			}
			
			//Lecture des articles.
			$posts = CategoryClassSingleton::CCS_getPosts($number, TRUE);
			
			echo $before_widget;
			if ( $title ) echo $before_title . $title . $after_title;
?>
			<ul>
<?php
			foreach($posts as $post){
				//Ajouter dans le titre la catégorie associée ?
				if($show_category){
					//On récupère la catégorie
					$category = CategoryClassSingleton::CCS_getCategories($post->getCategorieID(), TRUE, FALSE, TRUE);
					
					//Ensuite, on récupère le titre d'un des catégories
					if(count($category) > 0){
						$catTitre = $category[0]->getTitre() . " - ";
					}else $catTitre = "";
				}else $catTitre = "";
				
				$lien = $post->getLien();
				$titre = $catTitre . $post->getTitre();
				$date = $post->getDate();
?>
				<li>
					<a href="<?php echo $lien; ?>" title="<?php echo $titre; ?>"><?php echo $titre; ?></a>
				<?php if ( $show_date ) : ?>
					<span class="post-date"><?php echo $date; ?></span>
				<?php endif; ?>
				</li>
<?php
			}
?>
			</ul>
<?php
			echo $after_widget;
		}
		
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
 	public function form( $instance ) {
		
		if($this->libInstalled){
			$title     = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
			$number    = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
			$show_date = isset( $instance['show_date'] ) ? (bool) $instance['show_date'] : false;
			$category = isset( $instance['category'] ) ? (bool) $instance['category'] : false;
			
?>
			<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

			<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of posts to show:' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>

			<p><input class="checkbox" type="checkbox" <?php checked( $show_date ); ?> id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'show_date' ); ?>"><?php _e( 'Display post date?' ); ?></label></p>

			<p><input class="checkbox" type="checkbox" <?php checked( $category ); ?> id="<?php echo $this->get_field_id( 'category' ); ?>" name="<?php echo $this->get_field_name( 'category' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'category' ); ?>"><?php _e( 'Display post category?', 'root-category-recent-posts' ); ?></label></p>
<?php
		}else{
?>
			<p><?php _e( 'The plugin "Category class" must be installed and enabled.', 'root-category-recent-posts' ); ?></p>
<?php
		}
		
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		
		if($this->libInstalled){
			$instance['title'] = strip_tags($new_instance['title']);
			$instance['number'] = (int) $new_instance['number'];
			$instance['show_date'] = isset( $new_instance['show_date'] ) ? (bool) $new_instance['show_date'] : false;
			$instance['category'] = isset( $new_instance['category'] ) ? (bool) $new_instance['category'] : false;
		}

		return $instance;
	}
}

?>
<?php

	namespace SpiroAB;

	/**
	 * Class SmallBoxes
	 * @package SpiroAB
	 */
	class SmallBoxes
	{
		/**
		 * SmallBoxes constructor.
		 * Register hooks and shortcodes
		 */
		public function __construct()
		{
			add_action( 'init', [$this, 'init'] );
			add_shortcode( 'smallboxes', [$this, 'shortcode'] );
		}

		/**
		 * Register content-type small-boxes
		 */
		public function init()
		{
			load_plugin_textdomain('small_boxes', FALSE, dirname(plugin_basename(__FILE__)) . '/lang/');
			register_post_type(
				'small-boxes',
				[
					'labels' => [
						'name' => __('Small boxes', 'small_boxes'),
						'singular_name' => __('Small box', 'small_boxes'),
						'add_new' => __('Create', 'small_boxes'),
						'add_new_item' => __('Create small box', 'small_boxes'),
						'edit_item' => __('Edit small box', 'small_boxes'),
						'new_item' => __('Create small box', 'small_boxes'),
						'all_items' => __('All small boxes', 'small_boxes'),
						'view_item' => __('Show small box', 'small_boxes'),
						'search_items' => __('Search small box', 'small_boxes'),
						'not_found' => __('small box not found', 'small_boxes'),
						'menu_name' => __('Small boxes', 'small_boxes'),
					],
					'public' => TRUE,
					'capability_type' => 'page',
					'supports' => [
						'title',
						'editor',
						'author',
						'custom-fields',
						'revisions',
						'page-attributes',
						'thumbnail',
					],
					'taxonomies' => [
						'category'
					],
					'has_archive' => FALSE,
					'orderby' => 'menu_order',
				]
			);
		}

		/**
		 * shortcode smallboxes
		 *
		 * @param string[] $attributes class, cat: category-id or category-slug
		 *
		 * @return string
		 */
		public function shortcode($attributes)
		{
			$default_attributes = [
				'cat' => NULL,
				'class' => NULL
			];

			$classes = ['small_boxes_list'];

			$attributes = shortcode_atts($default_attributes, $attributes);
			$filters = [
				'post_type' => 'small-boxes',
				'orderby' => 'menu_order',
				'order'   => 'ASC',
			];

			if ($attributes['class']) {
				$classes[] = $attributes['class'];
			}

			if(!$attributes['cat'])
			{
				return '[smallboxes cat="" error="no category"]';
			}

			if(is_numeric($attributes['cat']))
			{
				$category = get_term( (int) $attributes['cat'], 'category' );
			}
			else
			{
				$category = get_term_by( 'slug', $attributes['cat'], 'category' );
			}
			if(!$category)
			{
				return '[smallboxes cat="' . htmlentities($attributes['cat']) . '" error="category not found"]';
			}
			$classes[] = 'small_boxes_list-' . $category->slug;
			$filters['cat'] = $category->term_id;

			do_action( 'page_part_list', $category, 'small-boxes');

			$query = new \WP_Query( $filters );

			$classes = implode(' ', $classes);
			$html = [];
			$html[] = '<div class="'. $classes .'">';

			foreach($query->get_posts() as $current_post)
			{
				do_action( 'page_part_item', $current_post->ID, $current_post->post_title, 'small-boxes');

				$edit_url = get_edit_post_link($current_post->ID);

				$classes = implode(' ', get_post_class('small_box small_box-' . $current_post->post_name, $current_post->ID));
				$html[] = '<div class="' . $classes . '">';

				if(has_post_thumbnail($current_post))
				{
					$link_url = null;
					if(substr($current_post->post_content, 0, 3) === '<a ')
					{
						if(preg_match('#href="([^"]+)"#', $current_post->post_content, $m))
						{
							$link_url = $m[1];
						}
					}
					$post_thumbnail_id = get_post_thumbnail_id( $current_post );
					if($link_url)
					{
						$html[] = '<a href="' . $link_url . '" class="small_box_image">';
					}
					else
					{
						$html[] = '<div class="small_box_image">';
					}
					$html[] = wp_get_attachment_image($post_thumbnail_id, 'large', FALSE);
					if($link_url)
					{
						$html[] = '</a>';
					}
					else
					{
						$html[] = '</div>';
					}
				}

				if($edit_url) {
					$html[] = '<a class="inline_edit_button" href="' . htmlentities($edit_url, 0, null, false) . '"><i class="fa fa-edit"></i></a>';
				}

				$html[] = apply_filters('the_content', $current_post->post_content);
				$html[] = '</div>';
			}

			if(count($html) < 4) return '[smallboxes cat="' . htmlentities($attributes['cat']) . '" error="category empty"]';

			$html[] = '</div>';

			return implode(PHP_EOL, $html);
		}
	}

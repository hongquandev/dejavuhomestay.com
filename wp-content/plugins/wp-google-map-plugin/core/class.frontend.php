<?php
/**
 * Generate Template and it's Elements.
 *
 * @author Flipper Code <hello@flippercode.com>
 * @version 3.0.0
 * @package Posts
 */

if ( ! class_exists( 'FlipperCode_Layout' ) ) {

	/**
	 * Generate Layout and it's Elements.
	 *
	 * @author Flipper Code <hello@flippercode.com>
	 * @version 3.0.0
	 * @package Posts
	 */
	class FlipperCode_Layout {

		function users_templates( $param = null ) {
			$all_templates = array();
			$template_paths = array(
			TEMPLATEPATH . '/wpp_template/',
			WPP_DIR . '/wpp_template/',
			);
			foreach ( $template_paths as $template ) {
				if ( is_dir( $template ) && scandir( $template ) ) {
					foreach ( scandir( $template ) as $file ) {
						if ( ('.' != $file[0]) && (substr( $file, -4 ) == '.php') &&
						is_file( $template . $file ) && is_readable( $template . $file ) ) {
							$template_name = substr( $file, 0, strlen( $file ) -4 );
							if ( ! in_array( $template_name, $all_templates ) ) {
								$all_templates[] = $template_name;
							}
						}
					}
				}
			}
			return $all_templates;
		}
		/**
		 * Get Template Preview.
		 *
		 * @param  array $args Template Options.
		 * @return string       Template Preview.
		 */
		function wpp_template_preview( $args ) {
			$template_id = (int) $args['template_id'];
			$layout_content = $this->get_layout_content( $template_id );
			$source_code = $layout_content;
			$data['title'] = '<div class="wpp_title"><a href="#">Lorem ipsum dolor sit amet, consectetur</a></div>';
			$data['content'] = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.';
			if ( 'right' == $args['thumbnail'] ) {
				$data['thumbnail'] = "<img class='wpp_image_right' align='right'  src='" . WPP_IMAGES . "sample.jpeg' />";
			} else if ( 'left' == $args['thumbnail'] ) {
				$data['thumbnail'] = "<img class='wpp_image_left' align='left' src='" . WPP_IMAGES . "sample.jpeg' />";
			} else {
				$data['thumbnail'] = "<img class='wpp_image_top' src='" . WPP_IMAGES . "sample.jpeg' />";
			}
			$data['date'] = '<i class="icon-calendar-o"></i><span itemprop="datePublished">January,03 2014</span>';
			$data['author'] = '<i class="icon-pencil2"></i><span itemprop="author">by admin</span>';
			$data['comments'] = '<i class="icon-comments-o"></i><span itemprop="comments">Comments(0)</span>';
			$data['categories'] = 'WordPress Plugin';
			$data['read_more'] = "<a href='#' >Read More...</a>";
			$data['tags'] = 'wordpress posts, posts listing';
			foreach ( $data as $key => $value ) {
				$layout_content = str_replace( "{{$key}}", $value, $layout_content );
			}
			$listing_div = '<div class="wpp_choose_design layout_' . $template_id . ' "  data-thumbnail="' . $args['thumbnail'] . '" rel="' . $template_id . '">
			<div class="wp-posts-pro">
			<div class="wpp_col wpp_span_1_of_1">' . $layout_content . '</div>
			</div>
			<input type="hidden" class="wpp_cs_source_code" value="' . esc_textarea( $source_code ) . '" />
			</div>
			';
			return $listing_div;
		}
		/**
		 * Get Post's Terms.
		 *
		 * @param  array   $tags     Terms.
		 * @param  boolean $link    Link to Term.
		 * @param  boolean $preview Echo or Return.
		 * @return string           Terms String.
		 */
		function wpp_post_taxonomy( $tags, $link = false, $preview = false ) {

			if ( $tags ) {

				$tags_links = array();

				foreach ( $tags as $tag ) {
					if ( true == $link ) {
						$tags_links[] = '<a href="' . get_tag_link( $tag->term_id ) . '" >' . $tag->name . '</a>';
					} else {
						$tags_links[] = $tag->name;
					}
				}
				if ( ! empty( $tags_links ) ) {
					return implode( ', ', $tags_links );
				} else { 					return ''; }
			}

			return '';

		}
		/**
		 * Get Posts via ajax for Infinity Scrolling.
		 *
		 * @param  array $data Post Dats.
		 * @return string       Posts.
		 */
		function wpp_load_posts( $data ) {
			global $wpdb;
			$layout_id = $data['value'];
			$ajax_page = $data['ajax_page'];
			$modelFactory = new WPP_Model();
			$layout_obj = $modelFactory->create_object( 'layout' );
			if ( isset( $layout_id ) ) {
				$layout_obj = $layout_obj->fetch( array( array( 'layout_id', '=', intval( wp_unslash( $layout_id ) ) ) ) );
				$data = (array) $layout_obj[0];
				$obj = new FlipperCode_Layout();
				$data['ajax'] = true;
				$data['ajax_page'] = $ajax_page;
				// echo '<pre>'; print_r($data);
				$shortcode_data = $obj->wpp_load_template( $data );
				return $shortcode_data['html'];
			} else {
				return __( 'Something went wrong.',WPP_TEXT_DOMAIN );
			}
		}
		/**
		 * Display Template with posts' data.
		 *
		 * @param  array $data Post Data.
		 * @return array       Posts Html & Layout Source Code.
		 */
		function wpp_load_template( $data ) {

			if ( ! isset( $data['entityID'] ) and ! isset( $data['layout_id'] ) ) {
				$return_data['html'] = "<div class='wpp_preview_message'>" . __( 'New Template? Choose a design, settings and tap on save template.',WPP_TEXT_DOMAIN ) . '</div>';
				$return_data['source_code'] = $this->get_layout_content( $data['layout_type'] );
				return $return_data;
			}
			$value = (isset( $data['entityID'] )) ? $data['entityID'] : $data['layout_id'];
			if ( trim( $data['source_code'] ) != '' ) {
				$layout_content = $data['source_code'];
			} else {
				if ( $data['layout_post_setting']['source_code'] ) {
					$layout_content = $data['layout_post_setting']['source_code'];
				} else {
					$layout_content = $this->get_layout_content( $data['layout_type'] );
				}
			}
			if ( isset( $data['ajax_page'] ) ) {
				$all_posts_data = $this->get_posts( $value, (int) $data['ajax_page'] );
			} else {
				$all_posts_data = $this->get_posts( $value, 1 );
			}

			if ( ! isset( $all_posts_data['posts'] ) or empty( $all_posts_data['posts'] ) ) {
				if ( ! isset( $data['ajax_page'] ) ) {
					$return_data['html'] = "<div class='wpp_no_posts'>" . __( 'No Posts found.',WPP_TEXT_DOMAIN ) . '</div>';
				} else {
					$return_data['html'] = '';
				}
				return $return_data;
			}
			$all_posts = $all_posts_data['posts'];
			$listing_html = array();
			if ( is_array( $all_posts ) and ! empty( $all_posts ) ) {
				foreach ( $all_posts as $post ) {
					$rdata = array();
					$title = get_the_title( $post->ID );
					$url = get_permalink( $post->ID );
					$temp_layout_content = $layout_content;
					$author_id = $post->post_author;
					$categories = wp_get_post_terms( $post->ID,'category',array( 'fields' => 'all' ) );
					$tags = wp_get_post_terms( $post->ID,'post_tag', array( 'fields' => 'all' ) );
					$comments = wp_count_comments( $post->ID );
					if ( '' != $data['layout_post_setting']['thumb_width'] and '' != $data['layout_post_setting']['thumb_height'] ) {
						$image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), array( $data['layout_post_setting']['thumb_width'], $data['layout_post_setting']['thumb_height'] ) );
					} else {
						$image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), $data['layout_post_setting']['thumb_size'] );
					}
					if ( is_array( $image ) ) {
						$image_src = $image[0];
					} elseif ( isset( $data['layout_post_setting']['default_image'] ) ) {
						$image_src = $data['layout_post_setting']['default_image'];
					} else {
						$image_src = WPP_IMAGES . 'sample.jpeg';
					}

					if ( true == $data['layout_post_setting']['post_link'] ) {
						if ( true == $data['layout_post_setting']['post_link_new_tab'] ) {
							$title = '<a href="' . $url . '" target="_blank">' . $title . '</a>';
						} else {
							$title = '<a href="' . $url . '">' . $title . '</a>';
						}
					}
					$rdata['post_link'] = $url;
					$rdata['title'] = $this->apply_wrapper( $data['layout_post_setting']['title_html'],$title );

					if ( 'content' == $data['layout_post_setting']['content_display'] ) {
						$rdata['content'] = apply_filters( 'the_content', $post->post_content );

					} else {
						$rdata['content'] = $this->apply_wrapper( $data['layout_post_setting']['content_html'],wp_trim_words( $post->post_content,$data['layout_post_setting']['post_content_limit'] ) );
					}
					if ( true == $data['layout_post_setting']['enable_shortcode'] ) {
						$rdata['content'] = do_shortcode( $rdata['content'] );
					} else {
						$rdata['content'] = strip_shortcodes( $rdata['content'] );
					}

					if ( ! isset( $data['layout_post_setting']['authr_name'] ) ) {
						$data['layout_post_setting']['authr_name'] = 'display_name';
					}
					$rdata['date'] = '<i class="icon-calendar-o"></i>' . $this->apply_wrapper( $data['layout_post_setting']['date_html'],get_the_date( $data['layout_post_setting']['date_format'],$post->ID ) );
					$author_name = get_the_author_meta( $data['layout_post_setting']['authr_name'] , $author_id );

					if ( true == $data['layout_post_setting']['author_link'] ) {
						if ( true == $data['layout_post_setting']['author_link_new_tab'] ) {
							$author_name = '<a href="' . get_author_posts_url( $author_id ) . '" target="_blank">' . $author_name . '</a>';
						} else {
							$author_name = '<a href="' . get_author_posts_url( $author_id ) . '">' . $author_name . '</a>';
						}
					}

					$rdata['author'] = '<i class="icon-pencil2"></i>' . $this->apply_wrapper( $data['layout_post_setting']['author_html'],$author_name );

					$comments_count = $this->apply_wrapper( $data['layout_post_setting']['comments_html'],$comments->approved . '' );
					if ( true == $data['layout_post_setting']['comments_link'] ) {
						if ( true == $data['layout_post_setting']['comments_link_new_tab'] ) {
							$comments_count = '<a href="' . get_comments_link( $post->ID ) . '" target="_blank">' . $comments_count . '</a>';
						} else {
							$comments_count = '<a href="' . get_comments_link( $post->ID ) . '">' . $comments_count . '</a>';
						}
					}

					$rdata['comments'] = '<i class="icon-comments-o"></i>' . $comments_count;
					$all_categories = array();
					if ( is_array( $categories ) and ! empty( $categories ) ) {
						foreach ( $categories as $category ) {
							$category_name = ucwords( $category->name );

							if ( true == $data['layout_post_setting']['category_link'] ) {
								if ( true == $data['layout_post_setting']['category_link_new_tab'] ) {
									$category_name = "<a target='_blank' href='" . get_term_link( $category->term_id,'category' ) . "' >" . $category_name . '</a>';
								} else {
									$category_name = "<a href='" . get_term_link( $category->term_id,'category' ) . "' >" . $category_name . '</a>';
								}
							}
							$all_categories[] = $category_name;
						}
					}
					$all_tags = array();
					if ( is_array( $tags ) and ! empty( $tags ) ) {
						foreach ( $tags as $tag ) {
							$tag_name = ucwords( $tag->name );
							if ( true == $data['layout_post_setting']['tags_link'] ) {
								if ( true == $data['layout_post_setting']['tags_link_new_tab'] ) {
									$tag_name = "<a target='_blank' href='" . get_term_link( $tag->term_id,'post_tag' ) . "' >" . $tag_name . '</a>';
								} else {
									$tag_name = "<a href='" . get_term_link( $tag->term_id,'post_tag' ) . "' >" . $tag_name . '</a>';
								}
							}
							$all_tags[] = $tag_name;
						}
					}
					$rdata['categories'] = $this->apply_wrapper( $data['layout_post_setting']['category_html'],implode( ', ',$all_categories ) );
					$rdata['tags'] = $this->apply_wrapper( $data['layout_post_setting']['tags_html'],implode( ', ',$all_tags ) );

					if ( true == $data['layout_post_setting']['readmore_link_new_tab'] ) {
						$rdata['read_more'] = '<a target="_blank" href="' . $url . '">' . $data['layout_post_setting']['readmore_html'] . '</a>';
					} else {
						$rdata['read_more'] = '<a href="' . $url . '">' . $data['layout_post_setting']['readmore_html'] . '</a>';
					}
					if ( isset( $data['layout_post_setting']['thumb_width'] ) and '' != $data['layout_post_setting']['thumb_width'] ) {
						$thumb_width = "width='" . $data['layout_post_setting']['thumb_width'] . "'";
					} else {
						$thumb_width = '';
					}

					if ( isset( $data['layout_post_setting']['thumb_height'] ) and '' != $data['layout_post_setting']['thumb_height'] ) {
						$thumb_height = "height='" . $data['layout_post_setting']['thumb_height'] . "'";
					} else {
						$thumb_height = '';
					}
					if ( isset( $data['layout_post_setting']['thumb_position'] ) and  ('top' == $data['layout_post_setting']['thumb_position']) ) {
						$rdata['thumbnail'] = '<img ' . $thumb_width . '  ' . $thumb_height . ' class="wpp_image_' . $data['layout_post_setting']['thumb_position'] . '" src="' . $image_src . '" align="' . $data['layout_post_setting']['thumb_position'] . '" /><br style="clear:both;" />';
					} else if ( isset( $data['layout_post_setting']['thumb_position'] ) ) {
						$rdata['thumbnail'] = '<img ' . $thumb_width . '  ' . $thumb_height . ' class="wpp_image_' . $data['layout_post_setting']['thumb_position'] . '" src="' . $image_src . '" align="' . $data['layout_post_setting']['thumb_position'] . '" />';
					} else {
						$rdata['thumbnail'] = '<img ' . $thumb_width . '  ' . $thumb_height . ' class="wpp_image_top" src="' . $image_src . '" align="' . $data['layout_post_setting']['thumb_position'] . '" />';
					}

					if ( true == $data['layout_post_setting']['thumbnail_link'] ) {
						if ( true == $data['layout_post_setting']['thumbnail_link_new_tab'] ) {
							$rdata['thumbnail'] = '<a href="' . $url . '" target="_blank">' . $rdata['thumbnail'] . '</a>';
						} else {
							$rdata['thumbnail'] = '<a href="' . $url . '">' . $rdata['thumbnail'] . '</a>';
						}
					}
					$rdata['thumbnail'] = apply_filters( 'wpp_thumbnail_html',$rdata['thumbnail'],$post->ID,get_post_thumbnail_id( $post->ID ) );
					preg_match_all( '/{\s*taxonomy\s*=\s*(.*?)}/',  $layout_content, $matches );

					if ( isset( $matches[0] ) ) {

						foreach ( $matches[0] as $k => $m ) {
							$post_meta_key = $matches[1][ $k ];
							$terms = wp_get_post_terms( $post->ID,$post_meta_key,array( 'fields' => 'all' ) );
							$meta_value = $this->wpp_post_taxonomy( $terms );
							$rdata[ 'taxonomy=' . $post_meta_key ] = $meta_value;
						}
					}

					$matches = array();

					preg_match_all( '/{%(.*?)%}/',  $layout_content, $matches );

					if ( isset( $matches[0] ) ) {

						foreach ( $matches[0] as $k => $m ) {
							$post_meta_key = $matches[1][ $k ];
							$meta_value = get_post_meta( $post->ID, $post_meta_key, true )? get_post_meta( $post->ID, $post_meta_key, true ) : '';
							$rdata[ '%' . $post_meta_key . '%' ] = $meta_value;
						}
					}
					if ( isset( $data['layout_post_setting']['hide_title'] ) and 'true' == $data['layout_post_setting']['hide_title'] ) {
						$rdata['title'] = '';
					}
					if ( isset( $data['layout_post_setting']['hide_excerpt'] ) and 'true' == $data['layout_post_setting']['hide_excerpt'] ) {
						$rdata['content'] = '';
					}
					if ( isset( $data['layout_post_setting']['hide_thumbnail'] ) and 'true' == $data['layout_post_setting']['hide_thumbnail'] ) {
						$rdata['thumbnail'] = '';
					}
					if ( isset( $data['layout_post_setting']['hide_read_more_link'] ) and 'true' == $data['layout_post_setting']['hide_read_more_link'] ) {
						$rdata['read_more'] = '';
					}
					if ( isset( $data['layout_post_setting']['hide_publish_date'] ) and 'true' == $data['layout_post_setting']['hide_publish_date'] ) {
						$rdata['date'] = '';
					}
					if ( isset( $data['layout_post_setting']['hide_author'] ) and 'true' == $data['layout_post_setting']['hide_author'] ) {
						$rdata['author'] = '';
					}
					if ( isset( $data['layout_post_setting']['hide_comments'] ) and 'true' == $data['layout_post_setting']['hide_comments'] ) {
						$rdata['comments'] = '';
					}
					if ( isset( $data['layout_post_setting']['hide_post_tags'] ) and 'true' == $data['layout_post_setting']['hide_post_tags'] ) {
						$rdata['tags'] = '';
					}
					if ( isset( $data['layout_post_setting']['hide_post_categories'] ) and 'true' == $data['layout_post_setting']['hide_post_categories'] ) {
						$rdata['categories'] = '';
					}
					$rdata = apply_filters( 'wpp_posts_data',$rdata,$post->ID );
					$temp_layout_content = apply_filters( 'wpp_template',$temp_layout_content,$data,$post->ID );
					foreach ( $rdata as $key => $value ) {
						$temp_layout_content = str_replace( "{{$key}}", $value, $temp_layout_content );
					}
					$listing_html[] = $temp_layout_content;
				}
			}
			if ( isset( $data['layout_post_setting']['columns_in_row'] ) ) {
				$total_cols = $data['layout_post_setting']['columns_in_row'];
			} else {
				$total_cols = 1;
			}

			if ( isset( $data['ajax'] ) and $data['ajax'] = true ) {
				$listing_div = '';
			} else {
				if ( true == $data['layout_post_setting']['pagination'] and 'infinite' == $data['layout_post_setting']['pagination_style'] ) {
					$effect_class = 'wpp_infinity_scroll load_more_btn';
				}else if ( true == $data['layout_post_setting']['pagination'] and 'autoload_infinite_scroll' == $data['layout_post_setting']['pagination_style'] ) {
					$effect_class = 'wpp_load_more_on_pagescroll';
				} else if ( true == $data['layout_post_setting']['pagination'] and 'carousel' == $data['layout_post_setting']['pagination_style'] ) {
					$effect_class = 'owl-carousel';
				} else {
					$effect_class = '';
				}
				if ( 'carousel' == $data['layout_post_setting']['pagination_style'] ) {
					if ( $data['layout_post_setting']['carousel_auto_play'] > 0 ) {
						$carousel_auto_play = $data['layout_post_setting']['carousel_auto_play'];
					} else {
						$carousel_auto_play = 5000;
					}
					if ( isset( $data['layout_post_setting']['carousel_stop_on_hover'] ) and 'true' == $data['layout_post_setting']['carousel_stop_on_hover'] ) {
						$carousel_stop_on_hover = 'true';
					} else {
						$carousel_stop_on_hover = 'false';
					}
					if ( isset( $data['layout_post_setting']['carousel_navigation'] ) and 'true' == $data['layout_post_setting']['carousel_navigation'] ) {
						$carousel_navigation = 'true';
					} else {
						$carousel_navigation = 'false';
					}
					if ( isset( $data['layout_post_setting']['carousel_pagination'] ) and 'true' == $data['layout_post_setting']['carousel_pagination'] ) {
						$carousel_pagination = 'true';
					} else {
						$carousel_pagination = 'false';
					}
				}
				$listing_div = '<div class="layout_' . $data['layout_type'] . '"><div class="wp-posts-pro ' . $effect_class . '" data-layout="' . $data['layout_id'] . '" data-auto_play="' . $carousel_auto_play . '" data-stop_on_hover="' . $carousel_stop_on_hover . '" data-navigation="' . $carousel_navigation . '" data-pagination="' . $carousel_pagination . '">';
			}
			if ( is_array( $listing_html ) ) {
				foreach ( $listing_html as $k => $list ) {
					if ( ($k % $total_cols) == 0 ) {
						$listing_div .= "<div class='wpp_section wpp_group pid=".$post->ID."'>";
					}
					$listing_div .= "<div class='wpp_col wpp_span_1_of_" . $total_cols . "'>" . $list . '</div>';
					if (  (($k + 1) % $total_cols ) == 0 ) {
						$listing_div .= '</div>';
					}
				}
			}

			if ( true == $data['layout_post_setting']['pagination'] and 'number' == $data['layout_post_setting']['pagination_style'] ) {
				$listing_div .= $all_posts_data['pagination'];
			} else if ( true == $data['layout_post_setting']['pagination'] and 'infinite' == $data['layout_post_setting']['pagination_style'] and count( $listing_html ) > 0 ) {

				$listing_div .= '<a class="wpp_loadmore_pager" href="javascript:void(0);">' . __( 'Load More',WPP_TEXT_DOMAIN ) . '</a>';
			} else if ( true == $data['layout_post_setting']['pagination'] and 'autoload_infinite_scroll' == $data['layout_post_setting']['pagination_style'] and count( $listing_html ) > 0 ) {
				
				$loadingImg = '<img class="wpp_load_more page_load_img" src="'.WPP_URL.'/assets/images/loader.gif" alt="Loading" style="display:none" />';

				if ( is_admin() && defined( 'DOING_AJAX' ) && DOING_AJAX )	{
					// Do in case of ajax call
					$listing_div .= $loadingImg;
				} else {
					// Add hidden fields only when first time form loads. Do not add again when ajax is called. Same function works for simple load and ajax load.
					//echo WPP_URL;
				 $hiddenFields = '<input type="hidden" name="next_offset_for_ajax" id="next_offset_for_ajax" value="2"><input type="hidden" name="next_limit_for_ajax" id="next_limit_for_ajax" value="'.$data['layout_post_setting']['dynamic_posts_load'].'">';
				    $listing_div .= $loadingImg.$hiddenFields;	
				}

			}

			if ( ! isset( $data['ajax'] ) or true != $data['ajax'] ) {
				$listing_div .= '</div>';
				$listing_div .= '</div>';
			}

			if ( true == $data['layout_post_setting']['pagination'] and 'carousel' == $data['layout_post_setting']['pagination_style'] ) {
					$listing_div .= '<script>jQuery(document).ready(function($){$(".layout_' . $data['layout_type'] . ' .wp-posts-pro.owl-carousel").owlCarousel({
        autoPlay: ' . $carousel_auto_play . ',
        stopOnHover: ("' . $carousel_stop_on_hover . '" == "true"),
        navigation: ("' . $carousel_navigation . '" == "true"),
        pagination: ("' . $carousel_pagination . '" == "true"),
        singleItem: true
    }); });</script>';
			}
			// $listing_div = implode('',$listing_html);
			$return_data['html'] = $listing_div;
			$return_data['source_code'] = $this->get_layout_content( $data['layout_type'] );

			return $return_data;

		}

		/**
		 * Get Posts by rule.
		 *
		 * @param  array   $rule  Rule Record.
		 * @param  boolean $args_only  Return arguments.
		 * @return array       Posts.
		 */
		function get_posts_by_rule( $rule, $args_only = false ) {

			
			$post_obj = new FlipperCode_Posts();
			$rule_match = $rule->rule_match;
			$args['post_type']	= $rule_match['post_type'];

			if ( ! empty( $rule_match['wprpw_hasthumbnail'] ) && 'true' == $rule_match['wprpw_hasthumbnail'] ) {
				$post_obj->has_thumbnail = true;
			}

			if ( ! empty( $rule_match['ignoresticky'] ) ) {
				$post_obj->ignore_sticky_posts = true;

			}

			if ( ! empty( $rule_match['authorname'] ) ) {
				$post_obj->set_author( $rule_match['authorname'] );
			}

			if ( ! empty( $rule_match['exclude_authorname'] ) ) {
				$post_obj->hide_author( $rule_match['exclude_authorname'] );
			}

			if ( ! empty( $rule_match['posts_categories'] ) ) {
				$post_obj->set_taxonomies( 'category',$rule_match['posts_categories'] );

			}

			if ( ! empty( $rule_match['exclude_posts_categories'] ) ) {
				$post_obj->hide_taxonomies( 'category',$rule_match['exclude_posts_categories'] );

			}

			if ( ! empty( $rule_match['post-formats'] ) ) {
				$post_obj->set_post_formats( $rule_match['post-formats'] );

			}
			if ( true == $rule_match['advanced_filters'] ) {
				if ( 'all' == $rule_match['post_type'] ) {
					$post_types = get_post_types( '','names' );
					$post_obj->set_post_types( $post_types );
				} else {
					$post_obj->set_post_types( $rule_match['post_type'] );
				}
				if ( ! empty( $rule_match['category_term'] ) ) {
					$post_obj->set_taxonomies( $rule_match['category_taxonomy'],$rule_match['category_term'] );
				}
			}
			if ( true == $rule_match['date_filters'] ) {
				if ( 'between' == $rule_match['period'] ) {
					$post_obj->date_range( 'between',array( 'start' => $rule_match['start_date'], 'end' => $rule_match['end_date'] ) );
				} else if ( 'n_days' == $rule_match['period'] ) {
					$post_obj->date_range( 'n_days',array( 'days' => $rule_match['n_days'] ) );
				} else if ( 'next_days' == $rule_match['period'] ) {
					$post_obj->date_range( 'next_days',array( 'next_days' => $rule_match['next_days'] ) );
				} else {
					$post_obj->date_range( $rule_match['period'] );
				}
			}

			if ( true == $rule_match['custom_fields_filters'] ) {
				if ( is_array( $rule->rule_customfield ) and ! empty( $rule->rule_customfield ) ) {
					$post_obj->set_custom_fields( $rule->rule_customfield );
				}
			}


			// Condition for releated Posts here...
			if ( is_single() ) {
				global $post;
				$author = get_the_author( $post->ID );
				if ( 'author' == $rule_match['post_criteria'] ) {
					$post_obj->set_author( array( 'author' => $post->post_author ) );
				} elseif ( 'category' == $rule_match['post_criteria'] ) {
					  $cat = get_the_category( $post->ID );
					  $all_posts_categories = array();
					if ( ! empty( $cat ) ) {
					  	foreach ( $cat as $c ) {
					  		$all_posts_categories[] = $c->term_taxonomy_id;
					  	}
					}
					  $post_obj->set_taxonomies( 'category',$all_posts_categories );
				} elseif ( 'date' == $rule_match['post_criteria'] ) {

					$post_year = get_the_time( 'Y', $post->ID );
					$post_month = get_the_time( 'm', $post->ID );
					$post_day = get_the_time( 'j', $post->ID );

					$post_obj->set_year( $post_year );
					$post_obj->set_month( $post_month );
					$post_obj->set_day( $post_day );
				} elseif ( 'tag' == $rule_match['post_criteria'] ) {
					$tags_array = get_the_tags( $post->ID );
					$all_posts_tags = array();
					if ( ! empty( $tags_array ) ) {
						foreach ( $tags_array as $c ) {
							$all_posts_tags[] = $c->term_taxonomy_id;
						}
					}
					$post_obj->set_tag( $all_posts_tags );

				}
			}

			if ( ! empty( $rule->rule_offset ) ) {
				 $post_obj->offset = $rule->rule_offset;
			}
			if ( (int) $rule->rule_number > 0 ) {
				$post_obj->posts_per_page     = $rule->rule_number;
			} else {
				$post_obj->posts_per_page     = -1;
			}
			if ( isset( $rule_match['post_ids'] ) and '' != $rule_match['post_ids'] ) {
				$include_posts = explode( ',', $rule_match['post_ids'] );
				if ( isset( $rule_match['exclude_post_ids'] ) and '' != $rule_match['exclude_post_ids'] ) {
					$exclude_posts = explode( ',', $rule_match['exclude_post_ids'] );
					$post_obj->post__in  = array_diff( $include_posts,$exclude_posts );
				} else {
					$post_obj->post__in  = $include_posts;
				}
				$post_types = get_post_types( '','names' );
				$post_obj->set_post_types( $post_types );
			} else if ( isset( $rule_match['exclude_post_ids'] ) and '' != $rule_match['exclude_post_ids'] ) {
				$exclude_posts = explode( ',', $rule_match['exclude_post_ids'] );
				$post_obj->post__not_in  = $exclude_posts;
			}

			$post_obj->page  = 1;
			$post_obj->orderby = $rule->rule_order_by;
			$post_obj->order = $rule->rule_order;
			$stack = array( 'tomorrow', 'next_week','next_month','next_days' );

			if ( true == $rule_match['date_filters'] and in_array( $rule->rule_match['period'], $stack ) ) {
				$post_obj->post_status = 'future';
			} else {
				$post_obj->post_status = 'publish';
			}
			if ( true == $args_only ) {
				$new_args = $post_obj->get_args();
				if ( isset( $new_args['posts_per_page'] ) ) {
					unset( $new_args['posts_per_page'] );
				}
				if ( isset( $new_args['paged'] ) ) {
					unset( $new_args['paged'] );
				}
				if ( isset( $new_args['page'] ) ) {
					unset( $new_args['page'] );
				}

				return $new_args;
			} else {
				return $post_obj->get_posts();
			}

		}
		/**
		 * Get Posts assigned to a Template.
		 *
		 * @param  int $layout_id Template ID.
		 *
		 * @param  int $set_page  Paginated Page.
		 * @return array           Posts Data.
		 */
		function get_posts( $layout_id, $set_page ) {
			$modelFactory = new WPP_Model( );
			$layout_obj = $modelFactory->create_object( 'layout' );
			$layout = $layout_obj->fetch( array( array( 'layout_id', '=', intval( wp_unslash( $layout_id ) ) ) ) );
			$data = (array) $layout[0];
			if ( empty( $data ) ) {
				return $data;
			}
			$all_rules = $data['layout_rule_id'];

			$rule_obj = $modelFactory->create_object( 'rules' );
			$rules = array();
			if ( is_array( $all_rules ) ) {
				foreach ( $all_rules as $key => $rule_id ) {
					$rule_data = $rule_obj->fetch( array( array( 'rule_id', '=', intval( wp_unslash( $rule_id ) ) ) ) );
					$rules[] = $rule_data[0];
				}
			}
			$all_posts = array();
			if ( is_array( $rules ) ) {
				foreach ( $rules as $key => $rule ) {
					$args = array();
					$posts = $this->get_posts_by_rule( $rule );
					$fetch_posts = $posts['posts'];
					$all_posts = array_merge( $all_posts, $fetch_posts );
				}

				$postids = array();

				foreach ( $all_posts as $item ) {

					$postids[] = $item->ID;

				}

				$uniqueposts = array_unique( $postids );
				$page = 1;
				$post_per_page = -1;
				if ( true == $data['layout_post_setting']['pagination'] and 'number' == $data['layout_post_setting']['pagination_style'] ) {
					if ( get_query_var( 'paged' ) ) {
						$page = get_query_var( 'paged' );
					} elseif ( get_query_var( 'page' ) ) {
						$page = get_query_var( 'page' );
					}
					if ( (int) $data['layout_post_setting']['per_page'] > 0 ) {
						$post_per_page = (int) $data['layout_post_setting']['per_page'];
					} else {
						$post_per_page = 10;
					}
				} else if ( true == $data['layout_post_setting']['pagination'] and ( 'infinite' == $data['layout_post_setting']['pagination_style'] or 'autoload_infinite_scroll' == $data['layout_post_setting']['pagination_style'] ) ) {

					$perPage = $data['layout_post_setting']['per_page'];
					$dynamicPosts = $data['layout_post_setting']['dynamic_posts_load'];

					if ( is_admin() && defined( 'DOING_AJAX' ) && DOING_AJAX ) {

						// Loading Template via ajax call
						if ( isset( $dynamicPosts ) and $dynamicPosts > 0 ) {
							$post_per_page = (int) $dynamicPosts;
						} else { $post_per_page = 10; }
					} else if ( isset( $perPage ) and (int) $perPage > 0 ) {

						// Loading Template on normal page load.
						$post_per_page = (int) $perPage;

					} else {
							$post_per_page = 10;
					}

					$page = $set_page;
				}

				$final_post_obj = new FlipperCode_Posts();
				$post_types = get_post_types( '','names' );

				if ( empty( $uniqueposts ) ) {
					$final_post_obj->post__in  = array( 999999999 );
				} else {
					$final_post_obj->post__in  = $uniqueposts;
				}
				$final_post_obj->set_post_types( $post_types );

				$final_post_obj->post_status = array( 'publish','future' );

				$final_post_obj->posts_per_page = $post_per_page;
				$final_post_obj->orderby = 'post__in';
				$final_post_obj->ignore_sticky_posts = true;
				$final_post_obj->page = $page;
				$final_post_obj->post_type 		= $post_types;
				$all_posts_data = $final_post_obj->get_posts();
				//echo '<pre>'; print_r($all_posts_data);
				return $all_posts_data;
			}

			exit;

		}
		/**
		 * Replace element's placeholder with content.
		 *
		 * @param  string $wrapper Placeholder.
		 * @param  string $string  Data.
		 * @return string          Final Data.
		 */
		function apply_wrapper( $wrapper, $string ) {

			if ( '' == $wrapper or '' == $string ) {
				return $string; } else {

				$new_string = str_replace( '%s',$string,stripcslashes( $wrapper ) );

				return $new_string;

				}

		}
		/**
		 * Get Layout Html by layout ID.
		 *
		 * @param  int $layout_id Layout ID.
		 * @return html Layout Html.
		 */
		function get_layout_content( $layout_id ) {

			$filname = 'layout_' . $layout_id . '.php';

			$layout_file = WPP_DIR . '/wpp_templates/' . $filname;

			if ( file_exists( $layout_file ) == false ) {

				return '<div id="messages" class="error">Sorry layout ' . $layout_id . ' not found.</div>'; }

			ob_start();

			include( $layout_file );

			$content = ob_get_contents();

			ob_clean();

			return $content;

		}
	}
}

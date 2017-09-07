<?php

/*
 * Plugin Name: DIVI ACF Module
 * Plugin URI:  http://www.sean-barton.co.uk
 * Description: A plugin to add the ability to use Advanced Custom Fields in it's own module within the layout builder
 * Author:      Sean Barton - Tortoise IT
 * Version:     2.7
 * Author URI:  http://www.sean-barton.co.uk
 *
 *
 * Changelog:
 *
 * V1.9
 * - Initial versions
 *
 * V2.0
 * - Added support for url, email and taxonomy fields
 *
 * V2.1
 * - Abstracted out the field type processing for easier updating
 * - Added better support for multiple taxonomy fields
 *
 * V2.2
 * - Fixed responsive text size/line height issues
 *
 * V2.3
 * - Moved style.css to correct enqueue point
 *
 * V2.4 - 6/2/17
 * - Tested for empty item before pushing single item module out to screen.
 * - Removed php notice relating to an undeclared title
 *
 * V2.5 - 28/2/17
 * - Fixed Image Size optionting to an undeclared title
 *
 * V2.6 - 28/2/17
 * - Added lightbox gallery functionality from Divi to 'gallery' ACF field type (courtesy of a user code submission)
 *
 * V2.7 - 28/2/17
 * - Added support for the Google Maps Field
 *
 */

    add_action('plugins_loaded', 'sb_mod_acf_init');
		
    function sb_mod_acf_init() {
        add_action('init', 'sb_mod_acf_theme_setup', 9999);
        add_action('admin_head', 'sb_mod_acf_admin_head', 9999);
        add_action('wp_enqueue_scripts', 'sb_mod_acf_enqueue', 9999);
				
				add_filter('acf/fields/google_map/api', 'sb_mod_acf_google_map_api');
    }
		
		function sb_mod_acf_google_map_api( $api ){
				$google = get_option('et_google_api_settings');
				
				if ($google['api_key']) {
						$api['key'] = $google['api_key'];
				}
				
				return $api;
		}
		
		function sb_mod_acf_enqueue() {
				wp_enqueue_style('sb_mod_acf_css', plugins_url( '/style.css', __FILE__ ));
		}
		
		function sb_mod_acf_parse_value_by_type($field, $image_size='medium') {
				$value = $field['value'];
				
				if (is_array($value) && $field['type'] == 'image') {
						$value = '<a href="' . $value['sizes']['large'] . '" class="sb-divi-acf-table-image-item"><img src="' . (@$value['sizes'][$image_size] ? $value['sizes'][$image_size]:$value['sizes']['medium']) . '" /></a>';
						
				} else if (is_array($value) && $field['type'] == 'checkbox') {
						foreach ($value as &$val) {
								$val = '<li>' . $val . '</li>';
						}
						$value = '<ul class="sb-acf-field-checkboxes">' . implode("\n", $value) . '</ul>';
						
				} else if (is_array($value) && $field['type'] == 'gallery') {
						$value_cache = $value;
						$value = '';
						$value .='<div class="et_pb_gallery et_slider_auto et_slider_speed_7000 et_pb_slider et_pb_gallery_fullwidth">';
				        $value .='<div class="et_pb_gallery_items et_post_gallery">';
						foreach ($value_cache as $val) {
								$value .= '<div class="et_pb_gallery_item et_pb_bg_layout_light">';
								$value .= '<div class="et_pb_gallery_image landscape">';
								$value .= '<a href="' . $val['sizes']['large'] . '">';
								$value .= '<img src="' . (@$val['sizes'][$image_size] ? $val['sizes'][$image_size]:$val['sizes']['large']) . '" data-lazy-loaded="true" style="display: inline;">';
								$value .= '<span class="et_overlay et_pb_inline_icon" data-icon="T"></span>';
								$value .= '</a>';
								$value .= '</div>';
								$value .= '</div>';
						}

						$value .= '</div>';
						$value .= '</div>';
						
				} else if ($field['type'] == 'url') {
					$value = '<a href="'.$field['value'].'" target="_blank">'.$field['value'].'</a>';
					
				} else if ($field['type'] == 'email') {
					 $value = '<a href="mailto:'.$field['value'].'">'.$field['value'].'</a>';
					 
				} else if ($field['type'] == 'google_map') {
						$google = get_option('et_google_api_settings');
						
						//print_r($field);
						$rand = mt_rand(1000, 9999);
						
						if ($google['api_key']) {
								
								$zoom = ($field['zoom'] ? $field['zoom']:16);
				
								$value = '<script src="https://maps.googleapis.com/maps/api/js?key=' . $google['api_key'] . '"></script>
										<script type="text/javascript">
										(function($) {
										
												function et_acf_new_map( $el ) {
														var $markers = $el.find(".marker");
														
														var args = {
																zoom		: ' . $zoom . ',
																center		: new google.maps.LatLng(0, 0),
																mapTypeId	: google.maps.MapTypeId.ROADMAP,
																scrollwheel : false
														};
														
														var map = new google.maps.Map( $el[0], args);
														
														map.markers = [];
														
														$markers.each(function(){
																et_acf_add_marker( $(this), map );
														});
														
														et_acf_center_map( map );
														
														return map;
												}
										
												function et_acf_add_marker( $marker, map ) {
														var latlng = new google.maps.LatLng( $marker.attr("data-lat"), $marker.attr("data-lng") );
													
														var marker = new google.maps.Marker({
																position	: latlng,
																map			: map
														});
													
														map.markers.push( marker );
													
														if( $marker.html() ) {
																var infowindow = new google.maps.InfoWindow({
																		content		: $marker.html()
																});
														
																google.maps.event.addListener(marker, "click", function() {
																		infowindow.open( map, marker );
																});
														}
												}
										
												function et_acf_center_map( map ) {
														var bounds = new google.maps.LatLngBounds();
													
														jQuery.each( map.markers, function( i, marker ){
																var latlng = new google.maps.LatLng( marker.position.lat(), marker.position.lng() );
																bounds.extend( latlng );
														});
													
														if ( map.markers.length == 1 ) {
																map.setCenter( bounds.getCenter() );
																map.setZoom( ' . $zoom . ' );
														} else {
																map.fitBounds( bounds );
														}
												}
												
												var map = null;
												
												jQuery(document).ready(function($){
														jQuery(".et_pb_acf_map_' . $rand . '").each(function(){
																map = et_acf_new_map( jQuery(this) );
														});
												});
										
										})(jQuery);
										</script>';
								
								if (!empty($field['value'])) {
										$value .= '<div class="et_pb_acf_map et_pb_acf_map_' . $rand . '" ' . ($field['height'] ? ' style="height: ' . $field['height'] . 'px;"':'') . '>
												<div class="marker" data-lat="' . $field['value']['lat'] . '" data-lng="' . $field['value']['lng'] . '"></div>
										</div>';
								}
						} else {
								$value .= '<p style="color red;">Please enter a Google API Key in your Divi Settings</p>';
						}
					 
				} else if ($field['type'] == 'taxonomy') {
					
					if (is_array($field['value'])) { // multiple values selected
							$value = '';
							$ACF_t = $field['taxonomy'];
							
							foreach ($field['value'] as $v){
									$ACF_taxonomy = get_term_by('id', $v,$ACF_t);
									$value .= $ACF_taxonomy->name . apply_filters('sb_et_mod_acf_tax_divider', '<br />');
							}
					
							$value = trim($value, apply_filters('sb_et_mod_acf_tax_divider', '<br />'));
							
					} else {									 // single value selected
							$ACF_tv = (int) $field['value'];
							$ACF_taxonomy = get_term_by('id',$ACF_tv,$ACF_t);
							$value = $ACF_taxonomy -> name;
							
					}										
					
				} else if (!is_array($value)) {
					$value = (do_shortcode($value));
					
				}
				
				//echo '"';
				//print_r($value);
				//echo '"';
				//print_r($field);
				
				if (!is_array($value) && !strip_tags($value)) {
						$value = apply_filters('sb_et_mod_acf_field_fallback', $value, $field);
						$value = apply_filters('sb_et_mod_acf_field_fallback_' . $field['name'], $value, $field);
				}
				
				$value = apply_filters('sb_et_mod_acf_field_parse', $value, $field);
				
				return $value;
		}
    
    function sb_mod_acf_admin_head() {
	
	if (stripos($_SERVER['PHP_SELF'], 'wp-admin/index.php') !== false || isset($_GET['post_type']) && $_GET['post_type'] == 'acf-field-group' || isset($_GET['sb_purge_cache'])) {
	    $prop_to_remove = array(
		'et_pb_templates_et_pb_acf_single_item'
		, 'et_pb_templates_et_pb_acf_table_item'
		, 'et_pb_templates_et_pb_acf_table_items'
		, 'et_pb_templates_et_pb_acf_repeater_table'
	    );
	    
	    $js_prop_to_remove = 'var sb_ls_remove = ["' . implode('","', $prop_to_remove) . '"];';
    
	    echo '<script>
	    
	    ' . $js_prop_to_remove . '
	    
	    for (var prop in localStorage) {
		if (sb_ls_remove.indexOf(prop) != -1) {
		    //console.log("found "+prop);
		    console.log(localStorage.removeItem(prop));
		}
	    }
	    
	    </script>';
	}
    }
    
    function sb_mod_acf_theme_setup() {
    
        if ( class_exists('ET_Builder_Module')) {
            
			
			class et_pb_acf_table extends ET_Builder_Module {
				function init() {
					$this->name            = esc_html__( 'ACF Items', 'et_builder' );
					$this->slug            = 'et_pb_acf_table_items';
					$this->child_slug      = 'et_pb_acf_table_item';
					$this->child_item_text = esc_html__( 'ACF Item', 'et_builder' );
			
					$this->whitelisted_fields = array(
						'admin_label',
						'module_id',
						'module_class',
						'default_style',
						'odd_row_colour',
						'even_row_colour',
						'odd_text_colour',
						'even_text_colour',
						'v_padding',
						'h_padding',
					);
			
					$this->main_css_element = '%%order_class%%.et_pb_acf_table';
					
					$this->advanced_options = array(
                                        'fonts' => array(
                                                'text'   => array(
                                                                'label'    => esc_html__( 'Text', 'et_builder' ),
                                                                'css'      => array(
                                                                        'main' => "{$this->main_css_element} p",
                                                                ),
                                                                'font_size' => array('default' => '14px'),
                                                                'line_height'    => array('default' => '1.5em'),
                                                ),
                                                'headings'   => array(
                                                                'label'    => esc_html__( 'Headings', 'et_builder' ),
                                                                'css'      => array(
                                                                        'main' => "{$this->main_css_element} h1, {$this->main_css_element} h2, {$this->main_css_element} h1 a, {$this->main_css_element} h2 a, {$this->main_css_element} h3, {$this->main_css_element} h4",
                                                                ),
                                                                'font_size' => array('default' => '30px'),
                                                                'line_height'    => array('default' => '1.5em'),
                                                ),
                                        ),
                                        'background' => array(
                                                'settings' => array(
                                                        'color' => 'alpha',
                                                ),
                                        ),
                                        'border' => array(),
                                        'custom_margin_padding' => array(
                                                'css' => array(
                                                        'important' => 'all',
                                                ),
                                        ),
                                );
				}
			
				function get_fields() {
					$fields = array(
						'admin_label' => array(
							'label'       => esc_html__( 'Admin Label', 'et_builder' ),
							'type'        => 'text',
							'description' => esc_html__( 'This will change the label of the module in the builder for easy identification.', 'et_builder' ),
						),
						'module_id' => array(
							'label'           => esc_html__( 'CSS ID', 'et_builder' ),
							'type'            => 'text',
							'option_category' => 'configuration',
							'tab_slug'        => 'custom_css',
							'option_class'    => 'et_pb_custom_css_regular',
						),
						'module_class' => array(
							'label'           => esc_html__( 'CSS Class', 'et_builder' ),
							'type'            => 'text',
							'option_category' => 'configuration',
							'tab_slug'        => 'custom_css',
							'option_class'    => 'et_pb_custom_css_regular',
						),
						'default_style' => array(
							'label'             => esc_html__( 'Use default styling', 'et_builder' ),
							'type'              => 'yes_no_button',
							'option_category'   => 'configuration',
							'options'           => array(
								'on'  => esc_html__( 'Yes', 'et_builder' ),
								'off' => esc_html__( 'No', 'et_builder' ),
							),
							'affects'           => array(
								'#et_pb_odd_row_colour',
								'#et_pb_even_row_colour',
								'#et_pb_odd_text_colour',
								'#et_pb_even_text_colour',
								'#et_pb_v_padding',
								'#et_pb_h_padding',
							),
							'description'        => esc_html__( 'This will turn on or off the detault layout for the table.', 'et_builder' ),
						),
						'odd_row_colour' => array(
							'label'             => esc_html__( 'Odd Row Colour', 'et_builder' ),
							'type'              => 'color-alpha',
							'custom_color'      => true,
							'depends_show_if'   => 'off',
							'description'       => esc_html__( 'Here you can define a custom color for the ODD rows in the table', 'et_builder' ),
						),
						'odd_text_colour' => array(
							'label'             => esc_html__( 'Odd Text Colour', 'et_builder' ),
							'type'              => 'color-alpha',
							'custom_color'      => true,
							'depends_show_if'   => 'off',
							'description'       => esc_html__( 'Here you can define a custom text color for the ODD rows in the table', 'et_builder' ),
						),
						'even_row_colour' => array(
							'label'             => esc_html__( 'Even Row Colour', 'et_builder' ),
							'type'              => 'color-alpha',
							'custom_color'      => true,
							'depends_show_if'   => 'off',
							'description'       => esc_html__( 'Here you can define a custom color for the EVEN rows in the table', 'et_builder' ),
						),
						'even_text_colour' => array(
							'label'             => esc_html__( 'Even Text Colour', 'et_builder' ),
							'type'              => 'color-alpha',
							'custom_color'      => true,
							'depends_show_if'   => 'off',
							'description'       => esc_html__( 'Here you can define a custom text color for the EVEN rows in the table', 'et_builder' ),
						),
						'v_padding' => array(
							'label'           => esc_html__( 'Vertical Padding', 'et_builder' ),
							'type'            => 'text',
							'depends_show_if'   => 'off',
							'mobile_options'  => true,
							'validate_unit'   => true,
						),
						'h_padding' => array(
							'label'           => esc_html__( 'Horizontal Padding', 'et_builder' ),
							'type'            => 'text',
							'depends_show_if'   => 'off',
							'mobile_options'  => true,
							'validate_unit'   => true,
						),
					);
					return $fields;
				}
			
				function shortcode_callback( $atts, $content = null, $function_name ) {
					$module_id = $this->shortcode_atts['module_id'];
					$module_class = $this->shortcode_atts['module_class'];
					$default_style = $this->shortcode_atts['default_style'];
					$odd_row_colour = $this->shortcode_atts['odd_row_colour'];
					$even_row_colour = $this->shortcode_atts['even_row_colour'];
					$odd_text_colour = $this->shortcode_atts['odd_text_colour'];
					$even_text_colour = $this->shortcode_atts['even_text_colour'];
					$vpadding = $this->shortcode_atts['v_padding'];
					$hpadding = $this->shortcode_atts['h_padding'];
					
					$module_class = ET_Builder_Element::add_module_order_class( $module_class, $function_name ) . ($default_style == 'on' ? ' et_pb_acf_table_styled':'');
					
					if ($default_style == 'off') {
					    if ( '' !== $odd_row_colour ) {
						    ET_Builder_Element::set_style( $function_name, array(
							    'selector'    => '%%order_class%%.et_pb_acf_table tbody tr:nth-child(odd)',
							    'declaration' => sprintf(
								    'background-color: %1$s;',
								    esc_html( $odd_row_colour )
							    ),
						    ) );
					    }
					    
					    if ( '' !== $even_row_colour ) {
						    ET_Builder_Element::set_style( $function_name, array(
							    'selector'    => '%%order_class%%.et_pb_acf_table tbody tr:nth-child(even)',
							    'declaration' => sprintf(
								    'background-color: %1$s;',
								    esc_html( $even_row_colour )
							    ),
						    ) );
					    }
					    if ( '' !== $odd_text_colour ) {
						    ET_Builder_Element::set_style( $function_name, array(
							    'selector'    => '%%order_class%%.et_pb_acf_table tbody tr:nth-child(odd) td',
							    'declaration' => sprintf(
								    'color: %1$s;',
								    esc_html( $odd_text_colour )
							    ),
						    ) );
					    }
					    
					    if ( '' !== $even_text_colour ) {
						    ET_Builder_Element::set_style( $function_name, array(
							    'selector'    => '%%order_class%%.et_pb_acf_table tbody tr:nth-child(even) td',
							    'declaration' => sprintf(
								    'color: %1$s;',
								    esc_html( $even_text_colour )
							    ),
						    ) );
					    }
					    
					    if ( '' !== $vpadding ) {
						    ET_Builder_Element::set_style( $function_name, array(
							    'selector'    => '%%order_class%%.et_pb_acf_table tbody tr td',
							    'declaration' => sprintf(
								    'padding-top: %1$s; padding-bottom: %1$s;',
								    esc_html( $vpadding )
							    ),
						    ) );
					    }
					    
					    if ( '' !== $hpadding ) {
						    ET_Builder_Element::set_style( $function_name, array(
							    'selector'    => '%%order_class%%.et_pb_acf_table tbody tr td',
							    'declaration' => sprintf(
								    'padding-left: %1$s; padding-right: %1$s;',
								    esc_html( $hpadding )
							    ),
						    ) );
					    }
					}
			
					$all_tabs_content = $this->shortcode_content;
			
					$output = '<div ' . ( '' !== $module_id ? 'id="' . esc_attr( $module_id ) . '" ' : '' ) . ' class="et_pb_module et_pb_acf_table ' . $module_class . '">
						    <table class=""><tbody>
							    ' . $all_tabs_content . '
						    </tbody></table>
						</div> <!-- .et_pt_acf_tables -->';
			
					return $output;
				}
			}
			new et_pb_acf_table;
			
			class et_pb_acf_table_item extends ET_Builder_Module {
				function init() {
					$this->name                        = esc_html__( 'ACF Field', 'et_builder' );
					$this->slug                        = 'et_pb_acf_table_item';
					$this->type                        = 'child';
					$this->child_title_var             = 'title';
			
					$this->whitelisted_fields = array(
						'title',
						'field_name',
						'image_size',
						'module_id',
						'module_class',
					);
			
					$this->advanced_setting_title_text = esc_html__( 'New ACF Field', 'et_builder' );
					$this->settings_text               = esc_html__( 'ACF Field Settings', 'et_builder' );
					$this->main_css_element = '%%order_class%%';
					
					$this->advanced_options = array(
                                        'fonts' => array(
                                                'text'   => array(
                                                                'label'    => esc_html__( 'Text', 'et_builder' ),
                                                                'css'      => array(
                                                                        'main' => "{$this->main_css_element} p",
                                                                ),
                                                                'font_size' => array('default' => '14px'),
                                                                'line_height'    => array('default' => '1.5em'),
                                                ),
                                                'headings'   => array(
                                                                'label'    => esc_html__( 'Headings', 'et_builder' ),
                                                                'css'      => array(
                                                                        'main' => "{$this->main_css_element} h1, {$this->main_css_element} h2, {$this->main_css_element} h1 a, {$this->main_css_element} h2 a, {$this->main_css_element} h3, {$this->main_css_element} h4",
                                                                ),
                                                                'font_size' => array('default' => '30px'),
                                                                'line_height'    => array('default' => '1.5em'),
                                                ),
                                        ),
                                        'background' => array(
                                                'settings' => array(
                                                        'color' => 'alpha',
                                                ),
                                        ),
                                        'border' => array(),
                                        'custom_margin_padding' => array(
                                                'css' => array(
                                                        'important' => 'all',
                                                ),
                                        ),
                                );
				}
			
				function get_fields() {
					$options = sb_mod_acf_get_fields();
					
					$image_options = array();
					$sizes = get_intermediate_image_sizes();
					
					foreach ($sizes as $size) {
						$image_options[$size] = $size;
					}
							
					$fields = array(
						'field_name' => array(
							'label'           => __( 'Field', 'et_builder' ),
							'type'            => 'select',
							'options'         => $options,
							'description'       => __( 'Pick which field to show.', 'et_builder' ),
						),
						'image_size' => array(
							    'label'           => __( 'Image Size', 'et_builder' ),
							    'type'            => 'select',
							    'options'         => $image_options,
							    'description'       => __( 'If this is an image type then choose a size from here. If there is no size you like in the list consider using the free <a href="https://wordpress.org/plugins/simple-image-sizes/" target="_blank">Simple Image Sizes</a> plugin where you can define your own.', 'et_builder' ),
						),
						'title' => array(
							'label'       => esc_html__( 'Title', 'et_builder' ),
							'type'        => 'text',
							'description' => esc_html__( 'The label will be used for this field on the front end.', 'et_builder' ),
						),
						'module_id' => array(
							'label'           => esc_html__( 'CSS ID', 'et_builder' ),
							'type'            => 'text',
							'option_category' => 'configuration',
							'tab_slug'        => 'custom_css',
							'option_class'    => 'et_pb_custom_css_regular',
						),
						'module_class' => array(
							'label'           => esc_html__( 'CSS Class', 'et_builder' ),
							'type'            => 'text',
							'option_category' => 'configuration',
							'tab_slug'        => 'custom_css',
							'option_class'    => 'et_pb_custom_css_regular',
						),
					);
					return $fields;
				}
			
				function shortcode_callback( $atts, $content = null, $function_name ) {
					global $et_pt_acf_table_titles;
					global $et_pt_acf_table_classes;
			
						$title = $this->shortcode_atts['title'];
						$field = $this->shortcode_atts['field_name'];
						
						$module_class = ET_Builder_Element::add_module_order_class( '', $function_name );
			
					$i = 0;
			
					$et_pt_acf_table_titles[]  = '' !== $title ? $title : esc_html__( 'ACF Field', 'et_builder' );
					$et_pt_acf_table_classes[] = $module_class;
							
							$output = '';
			
							if (function_exists('get_field')) {
								if ($field_arr = explode('|', $field)) {
										$field = $field_arr[1];
								}
								
								if ($field = get_field_object($field)) {
									
									//$non_tabular_types = array('repeater'); //@todo
									
									$image_size = $this->shortcode_atts['image_size'];
									
									if (!$title) {
										$title = $field['label'];
									}
			
									$value = sb_mod_acf_parse_value_by_type($field, $image_size);
									
									if ($value) {
										$output = '<tr>
												<td valign="top" class="sb_mod_acf_table_item clearfix ' . esc_attr( $module_class ) . '">
												' . $title . '
												</td>
												<td valign="top">' . $value . '</td>
											    </tr>';
									}    
								}
							}
			
					return $output;
				}
			}
			new et_pb_acf_table_item;
			
			class et_pb_acf_single extends ET_Builder_Module {
				function init() {
					$this->name            = esc_html__( 'ACF Single Item', 'et_builder' );
					$this->slug            = 'et_pb_acf_single_item';
					
					$this->whitelisted_fields = array(
					    'module_id',
					    'module_class',
					    'field_name',
					    'image_size',
					    'format_output',
					    'date_format',
					    'title',
					);
            
					$this->fields_defaults = array();
					//$this->main_css_element = '.et_pb_acf_single';
					
					$this->main_css_element = '%%order_class%%';
                    
					$this->advanced_options = array(
                                        'fonts' => array(
                                                'text'   => array(
                                                                'label'    => esc_html__( 'Text', 'et_builder' ),
                                                                'css'      => array(
                                                                        'main' => "{$this->main_css_element} p",
                                                                ),
                                                                'font_size' => array('default' => '14px'),
                                                                'line_height'    => array('default' => '1.5em'),
                                                ),
                                                'headings'   => array(
                                                                'label'    => esc_html__( 'Headings', 'et_builder' ),
                                                                'css'      => array(
                                                                        'main' => "{$this->main_css_element} h1, {$this->main_css_element} h2, {$this->main_css_element} h1 a, {$this->main_css_element} h2 a, {$this->main_css_element} h3, {$this->main_css_element} h4",
                                                                ),
                                                                'font_size' => array('default' => '30px'),
                                                                'line_height'    => array('default' => '1.5em'),
                                                ),
                                        ),
                                        'background' => array(
                                                'settings' => array(
                                                        'color' => 'alpha',
                                                ),
                                        ),
                                        'border' => array(),
                                        'custom_margin_padding' => array(
                                                'css' => array(
                                                        'important' => 'all',
                                                ),
                                        ),
                                );
			
				}
			
				function get_fields() {
						$options = sb_mod_acf_get_fields();
						
						$image_options = array();
						$sizes = get_intermediate_image_sizes();
						
						foreach ($sizes as $size) {
							$image_options[$size] = $size;
						}
								
						$fields = array(
						'title' => array(
							'label'       => esc_html__( 'Title', 'et_builder' ),
							'type'        => 'text',
							'description' => esc_html__( 'The label that will be used for this field on the front end. (Optional)', 'et_builder' ),
						),
						'field_name' => array(
								'label'           => __( 'Field', 'et_builder' ),
								'type'            => 'select',
								'options'         => $options,
								'description'       => __( 'Pick which field to show.', 'et_builder' ),
							),
						'image_size' => array(
							    'label'           => __( 'Image Size', 'et_builder' ),
							    'type'            => 'select',
							    'options'         => $image_options,
							    'description'       => __( 'If this is an image type then choose a size from here. If there is no size you like in the list consider using the free <a href="https://wordpress.org/plugins/simple-image-sizes/" target="_blank">Simple Image Sizes</a> plugin where you can define your own.', 'et_builder' ),
							),
						'date_format' => array(
							'label'       => esc_html__( 'Date Format', 'et_builder' ),
							'type'        => 'text',
							'description' => esc_html__( 'If this is a date picker type, enter format here. (Optional)', 'et_builder' ),
						),
						'format_output' => array(
							'label'           => __( 'Output Format', 'et_builder' ),
							'type'            => 'select',
							'options'         => array('none'=>'None', 'autop'=>'Add Paragraphs', 'audio'=>'Show Audio Player', 'video'=>'Show Video Player'),
							'description'       => __( 'How should the output be formatted? None is default.', 'et_builder' ),
						),
						'admin_label' => array(
							'label'       => esc_html__( 'Admin Label', 'et_builder' ),
							'type'        => 'text',
							'description' => esc_html__( 'This will change the label of the module in the builder for easy identification.', 'et_builder' ),
						),
						'module_id' => array(
							'label'           => esc_html__( 'CSS ID', 'et_builder' ),
							'type'            => 'text',
							'option_category' => 'configuration',
							'tab_slug'        => 'custom_css',
							'option_class'    => 'et_pb_custom_css_regular',
						),
						'module_class' => array(
							'label'           => esc_html__( 'CSS Class', 'et_builder' ),
							'type'            => 'text',
							'option_category' => 'configuration',
							'tab_slug'        => 'custom_css',
							'option_class'    => 'et_pb_custom_css_regular',
						),
					);
					return $fields;
				}
			
				function shortcode_callback( $atts, $content = null, $function_name ) {
						$module_id          = $this->shortcode_atts['module_id'];
						$module_class       = $this->shortcode_atts['module_class'];
						$image_size           = $this->shortcode_atts['image_size'];
						$date_format           = $this->shortcode_atts['date_format'];
						$field = $this->shortcode_atts['field_name'];
						
						$title       = $this->shortcode_atts['title'];
            
						$module_class = ET_Builder_Element::add_module_order_class( $module_class, $function_name );
            
            //////////////////////////////////////////////////////////////////////
						
						$output = '';
						$content = '';
	
						if (function_exists('get_field')) {
								if ($field_arr = explode('|', $field)) {
										$field = $field_arr[1];
								}
								
							if ($field = get_field_object($field)) {
									
								//$non_tabular_types = array('repeater');
								
								if ($title) {
									$content .= '<h2 itemprop="name" class="acf_label">' . $title . '</h2>';
								}
		
								$value = sb_mod_acf_parse_value_by_type($field, $image_size);
								
								if ($this->shortcode_atts['format_output']) {
										switch ($this->shortcode_atts['format_output']) {
									case 'autop':
											$value = wpautop($value);
											break;
									case 'audio':
											$value = do_shortcode('[audio src="' . $value . '"]');
											break;
									case 'video':
											$value = do_shortcode('[video src="' . $value . '"]');
											break;
										}
								}
								
								if (trim($value)) {
										$content .= '<div class="sb_mod_acf_single_item clearfix">' . $value . '</div>';
								} else {
										$content = '';
								}
							}
						}
							
            //////////////////////////////////////////////////////////////////////
            
						if (trim($content) && trim($value)) {
								$output = sprintf(
										'<div%5$s class="%1$s%3$s%6$s">
												%2$s
										%4$s',
										'clearfix ',
										$content,
										esc_attr( 'et_pb_module' ),
										'</div>',
										( '' !== $module_id ? sprintf( ' id="%1$s"', esc_attr( $module_id ) ) : '' ),
										( '' !== $module_class ? sprintf( ' %1$s', esc_attr( $module_class ) ) : '' )
								);
						}
            
					return $output;
				}
			}
			new et_pb_acf_single;			
			
			class et_pb_acf_repeater_table extends ET_Builder_Module {
				function init() {
					$this->name            = esc_html__( 'ACF Repeater Table', 'et_builder' );
					$this->slug            = 'et_pb_acf_repeater_table';
					
					$this->whitelisted_fields = array(
					    'field_name',
					    'image_size',
					    'title',
					    'admin_label',
					    'module_id',
					    'module_class',
					    'default_style',
					    'odd_row_colour',
					    'even_row_colour',
					    'odd_text_colour',
					    'even_text_colour',
					    'v_padding',
					    'h_padding',
					);
            
					$this->fields_defaults = array();
					$this->main_css_element = '%%order_class%%.et_pb_acf_repeater_table';
                    
					$this->advanced_options = array(
                                        'fonts' => array(
                                                'text'   => array(
                                                                'label'    => esc_html__( 'Text', 'et_builder' ),
                                                                'css'      => array(
                                                                        'main' => "{$this->main_css_element} p",
                                                                ),
                                                                'font_size' => array('default' => '14px'),
                                                                'line_height'    => array('default' => '1.5em'),
                                                ),
                                                'headings'   => array(
                                                                'label'    => esc_html__( 'Headings', 'et_builder' ),
                                                                'css'      => array(
                                                                        'main' => "{$this->main_css_element} h1, {$this->main_css_element} h2, {$this->main_css_element} h1 a, {$this->main_css_element} h2 a, {$this->main_css_element} h3, {$this->main_css_element} h4",
                                                                ),
                                                                'font_size' => array('default' => '30px'),
                                                                'line_height'    => array('default' => '1.5em'),
                                                ),
                                        ),
                                        'background' => array(
                                                'settings' => array(
                                                        'color' => 'alpha',
                                                ),
                                        ),
                                        'border' => array(),
                                        'custom_margin_padding' => array(
                                                'css' => array(
                                                        'important' => 'all',
                                                ),
                                        ),
                                );
			
				}
			
				function get_fields() {
						$options = sb_mod_acf_get_fields(true);
						
						$image_options = array();
						$sizes = get_intermediate_image_sizes();
						
						foreach ($sizes as $size) {
							$image_options[$size] = $size;
						}
								
						$fields = array(
						'field_name' => array(
								'label'           => __( 'Field', 'et_builder' ),
								'type'            => 'select',
								'options'         => $options,
								'description'       => __( 'Pick which field to show.', 'et_builder' ),
							),
						'image_size' => array(
							    'label'           => __( 'Image Size', 'et_builder' ),
							    'type'            => 'select',
							    'options'         => $image_options,
							    'description'       => __( 'If this is an image type then choose a size from here. If there is no size you like in the list consider using the free <a href="https://wordpress.org/plugins/simple-image-sizes/" target="_blank">Simple Image Sizes</a> plugin where you can define your own.', 'et_builder' ),
							),
							'title' => array(
							'label'       => esc_html__( 'Title', 'et_builder' ),
							'type'        => 'text',
							'description' => esc_html__( 'The label that will be used for this field on the front end. (Optional)', 'et_builder' ),
						),
						'admin_label' => array(
							'label'       => esc_html__( 'Admin Label', 'et_builder' ),
							'type'        => 'text',
							'description' => esc_html__( 'This will change the label of the module in the builder for easy identification.', 'et_builder' ),
						),
						'module_id' => array(
							'label'           => esc_html__( 'CSS ID', 'et_builder' ),
							'type'            => 'text',
							'option_category' => 'configuration',
							'tab_slug'        => 'custom_css',
							'option_class'    => 'et_pb_custom_css_regular',
						),
						'module_class' => array(
							'label'           => esc_html__( 'CSS Class', 'et_builder' ),
							'type'            => 'text',
							'option_category' => 'configuration',
							'tab_slug'        => 'custom_css',
							'option_class'    => 'et_pb_custom_css_regular',
						),
						'default_style' => array(
							'label'             => esc_html__( 'Use default styling', 'et_builder' ),
							'type'              => 'yes_no_button',
							'option_category'   => 'configuration',
							'options'           => array(
								'on'  => esc_html__( 'Yes', 'et_builder' ),
								'off' => esc_html__( 'No', 'et_builder' ),
							),
							'affects'           => array(
								'#et_pb_odd_row_colour',
								'#et_pb_even_row_colour',
								'#et_pb_odd_text_colour',
								'#et_pb_even_text_colour',
								'#et_pb_v_padding',
								'#et_pb_h_padding',
							),
							'description'        => esc_html__( 'This will turn on or off the detault layout for the table.', 'et_builder' ),
						),
						'odd_row_colour' => array(
							'label'             => esc_html__( 'Odd Row Colour', 'et_builder' ),
							'type'              => 'color-alpha',
							'custom_color'      => true,
							'depends_show_if'   => 'off',
							'description'       => esc_html__( 'Here you can define a custom color for the ODD rows in the table', 'et_builder' ),
						),
						'odd_text_colour' => array(
							'label'             => esc_html__( 'Odd Text Colour', 'et_builder' ),
							'type'              => 'color-alpha',
							'custom_color'      => true,
							'depends_show_if'   => 'off',
							'description'       => esc_html__( 'Here you can define a custom text color for the ODD rows in the table', 'et_builder' ),
						),
						'even_row_colour' => array(
							'label'             => esc_html__( 'Even Row Colour', 'et_builder' ),
							'type'              => 'color-alpha',
							'custom_color'      => true,
							'depends_show_if'   => 'off',
							'description'       => esc_html__( 'Here you can define a custom color for the EVEN rows in the table', 'et_builder' ),
						),
						'even_text_colour' => array(
							'label'             => esc_html__( 'Even Text Colour', 'et_builder' ),
							'type'              => 'color-alpha',
							'custom_color'      => true,
							'depends_show_if'   => 'off',
							'description'       => esc_html__( 'Here you can define a custom text color for the EVEN rows in the table', 'et_builder' ),
						),
						'v_padding' => array(
							'label'           => esc_html__( 'Vertical Padding', 'et_builder' ),
							'type'            => 'text',
							'depends_show_if'   => 'off',
							'mobile_options'  => true,
							'validate_unit'   => true,
						),
						'h_padding' => array(
							'label'           => esc_html__( 'Horizontal Padding', 'et_builder' ),
							'type'            => 'text',
							'depends_show_if'   => 'off',
							'mobile_options'  => true,
							'validate_unit'   => true,
						),
					);
					return $fields;
				}
			
				function shortcode_callback( $atts, $content = null, $function_name ) {
				    
					if (!function_exists('get_field')) {
					    return '';
					}
					
					$output = '';
				    
					$title = $this->shortcode_atts['title'];
					$field = $this->shortcode_atts['field_name'];
					$image_size = $this->shortcode_atts['image_size'];
					$module_id = $this->shortcode_atts['module_id'];
					$module_class = $this->shortcode_atts['module_class'];
					$default_style = $this->shortcode_atts['default_style'];
					$odd_row_colour = $this->shortcode_atts['odd_row_colour'];
					$even_row_colour = $this->shortcode_atts['even_row_colour'];
					$odd_text_colour = $this->shortcode_atts['odd_text_colour'];
					$even_text_colour = $this->shortcode_atts['even_text_colour'];
					$vpadding = $this->shortcode_atts['v_padding'];
					$hpadding = $this->shortcode_atts['h_padding'];
					
					$module_class = ET_Builder_Element::add_module_order_class( $module_class, $function_name ) . ($default_style == 'on' ? ' et_pb_acf_table_styled':'');
					
					if ($default_style == 'off') {
					    if ( '' !== $odd_row_colour ) {
						    ET_Builder_Element::set_style( $function_name, array(
							    'selector'    => '%%order_class%%.et_pb_acf_table tbody tr:nth-child(odd)',
							    'declaration' => sprintf(
								    'background-color: %1$s;',
								    esc_html( $odd_row_colour )
							    ),
						    ) );
					    }
					    
					    if ( '' !== $even_row_colour ) {
						    ET_Builder_Element::set_style( $function_name, array(
							    'selector'    => '%%order_class%%.et_pb_acf_table tbody tr:nth-child(even)',
							    'declaration' => sprintf(
								    'background-color: %1$s;',
								    esc_html( $even_row_colour )
							    ),
						    ) );
					    }
					    if ( '' !== $odd_text_colour ) {
						    ET_Builder_Element::set_style( $function_name, array(
							    'selector'    => '%%order_class%%.et_pb_acf_table tbody tr:nth-child(odd) td',
							    'declaration' => sprintf(
								    'color: %1$s;',
								    esc_html( $odd_text_colour )
							    ),
						    ) );
					    }
					    
					    if ( '' !== $even_text_colour ) {
						    ET_Builder_Element::set_style( $function_name, array(
							    'selector'    => '%%order_class%%.et_pb_acf_table tbody tr:nth-child(even) td',
							    'declaration' => sprintf(
								    'color: %1$s;',
								    esc_html( $even_text_colour )
							    ),
						    ) );
					    }
					    
					    if ( '' !== $vpadding ) {
						    ET_Builder_Element::set_style( $function_name, array(
							    'selector'    => '%%order_class%%.et_pb_acf_table tbody tr td',
							    'declaration' => sprintf(
								    'padding-top: %1$s; padding-bottom: %1$s;',
								    esc_html( $vpadding )
							    ),
						    ) );
					    }
					    
					    if ( '' !== $hpadding ) {
						    ET_Builder_Element::set_style( $function_name, array(
							    'selector'    => '%%order_class%%.et_pb_acf_table tbody tr td',
							    'declaration' => sprintf(
								    'padding-left: %1$s; padding-right: %1$s;',
								    esc_html( $hpadding )
							    ),
						    ) );
					    }
					}
					
					if ($field) {
						
						if ($field_arr = explode('|', $field)) {
								$field = $field_arr[1];
						}
						
						$field = get_field_object($field);
						
						$labels = array();
						
						foreach ($field['sub_fields'] as $sub_field) {
						    $labels[$sub_field['name']] = $sub_field['label'];
						}
						
						//echo '<pre>';
						//print_r($field);
						//echo '</pre>';

						if (isset($field['value']) && is_array($field['value'])) {
						    foreach ($field['value'] as $value) {
							$output .= '<tr>';
							foreach ($value as $key=>$val) {
							    if (is_array($value) && $field['type'] == 'image') {
								    $val = '<a href="' . $val['sizes']['large'] . '" class="sb-divi-acf-table-image-item"><img src="' . (@$val['sizes'][$image_size] ? $val['sizes'][$image_size]:'medium') . '" /></a>';
							    }
                                if (is_array($value) && $sub_field['type'] == 'post_object') {
                                    $titolo = get_the_title($val);
                                    $url = get_permalink($val);
                                    $val = '<a href="'.$url.'">'.$titolo.'</a>';
                                }
							    $output .= '<td valign="top" class="sb_mod_acf_table_item clearfix">' . $val . '</td>';
							}

							$output .= '</tr>';
						    }
						}
					}

					if ($output) {
					    $output = ($title ? '<h3>' . $title . '</h3>':'') .
							'<div ' . ( '' !== $module_id ? 'id="' . esc_attr( $module_id ) . '" ' : '' ) . ' class="et_pb_module et_pb_acf_table et_pb_acf_repeater_table ' . $module_class . '">
							<table class="">
							    <tbody>
								' . $output . '
							    </tbody>
							</table>
						    </div> <!-- .et_pt_acf_repeater_table -->';
					}
			
					return $output;
				}
			}
			new et_pb_acf_repeater_table;			
						
        }
    }
    
    function sb_mod_acf_get_fields($repeater_only=false) {
	
				$options = array();
	
				if ($acf_posts = get_posts(array('post_type'=>'acf', 'posts_per_page'=>-1))) {
						foreach ($acf_posts as $acf_post) {
								
								$acf_meta = get_post_custom( $acf_post->ID );
								$acf_fields = array();
		
								foreach ( $acf_meta as $key => $val ) {
										if ( preg_match( "/^field_/", $key ) ) {
												$acf_fields[$key] = $val;
										}
								}
		    
								if ($acf_fields) {
										foreach ($acf_fields as $field) {
												$field = unserialize($field[0]);
				    
												//echo '<pre>';
												//print_r($field);
												//echo '</pre>';
				    
												if (!$repeater_only || $repeater_only && $field['type'] == 'repeater') {
														$options[$acf_post->post_title . '|' . $field['name']] = $acf_post->post_title . ' - ' . $field['label'];
												}
										}
								}
						}
				}
	
				if ($acf_pro_groups = get_posts(array('post_type'=>'acf-field-group', 'posts_per_page'=>-1))) {
						foreach ($acf_pro_groups as $acf_fg) {
								
								//echo '<pre>';
								//print_r($acf_fg);
								//echo '</pre>';
								
								if ($fields = get_posts(array('post_type'=>'acf-field', 'post_parent'=>$acf_fg->ID, 'posts_per_page'=>-1))) {
										foreach ($fields as $field) {
												$field_obj = unserialize($field->post_content);
			    
												//echo '<pre>';
												//print_r($field_obj);
												//echo '</pre>';
			    
											  if (!$repeater_only || $repeater_only && $field_obj['type'] == 'repeater') {
														$options[$acf_fg->post_title . '|' . $field->post_excerpt] = $acf_fg->post_title . ' - ' . $field->post_title;
												}
										}
								}
						}
				}
				
				//echo '<pre>';
				//print_r($options);
				//echo '</pre>';
	
				return $options;
    }
		
		//sb_mod_acf_get_fields();
    
?>
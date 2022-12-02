<?php

namespace Elementor;

defined('ABSPATH') || exit;

class Ekit_Wb_50 extends Widget_Base {

	public function get_name() {
		return 'ekit_wb_50';
	}


	public function get_title() {
		return esc_html__( 'New Widget', 'elementskit-lite' );
	}


	public function get_categories() {
		return ['basic'];
	}


	public function get_icon() {
		return 'eicon-cog';
	}


	protected function register_controls() {

		$this->start_controls_section(
			'content_section_50_0',
			array(
				'label' => esc_html__( 'Title', 'elementskit-lite' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'ekit_wb_50_icons',
			array(
				'label' => esc_html__( '87879461664', 'elementskit-lite' ),
				'type'  => Controls_Manager::ICONS,
				'separator' => 'after' ,
				'show_label' => true ,
				'label_block' => true ,
				'skin' => 'media' ,
				'default' => array(
					'value' => 'fas fa-phone-volume',
					'library' => 'fa-solid',
				)
			)
		);

		$this->end_controls_section();

	}


	protected function render() {
	}


}

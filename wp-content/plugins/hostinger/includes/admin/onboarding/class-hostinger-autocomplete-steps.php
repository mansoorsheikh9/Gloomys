<?php

defined( 'ABSPATH' ) || exit;

class Hostinger_AutoComplete_Steps {
	private array $completed_steps;

	public function __construct() {
		if ( ! session_id() ) {
			session_start();
		}

		$this->completed_steps = get_option( 'hostinger_onboarding_steps', [] );
		add_action( 'customize_save', [ $this, 'logo_upload' ] );
		add_action( 'wp_handle_upload', [ $this, 'image_upload' ] );
		add_action( 'post_updated', [ $this, 'post_content_change' ], 10, 3 );
		add_action( 'customize_save', [ $this, 'edit_site_title' ] );
		add_action( 'save_post_page', [ $this, 'new_page_creation' ], 10, 3 );
	}

	public function logo_upload( WP_Customize_Manager $data ): void {
		$action = Hostinger_Admin_Actions::LOGO_UPLOAD;

		$logo_updated = array_filter( $data->changeset_data(), function ( $key ) {
			return strpos( $key, 'custom_logo' ) !== false;
		}, ARRAY_FILTER_USE_KEY );

		$has_logo      = reset( $logo_updated )['value'] ?? false;
		$session_value = $_SESSION[ $action ] ?? '';

		if ( in_array( $action, $this->completed_steps, true ) || $logo_updated && ! $has_logo ) {
			return;
		}

		if ( $logo_updated && $session_value === $action ) {
			$this->completed_steps[] = $action;
			Hostinger_Settings::update_setting( 'onboarding_steps', $this->completed_steps );
			unset( $_SESSION[ $action ] );
		}
	}

	public function image_upload( array $data ): array {
		$action        = Hostinger_Admin_Actions::IMAGE_UPLOAD;
		$file_type     = $data['type'] ?? '';
		$session_value = $_SESSION[ $action ] ?? '';

		if ( in_array( $action, $this->completed_steps, true ) || strpos( $file_type, 'image' ) !== 0 ) {
			return $data;
		}

		if ( $session_value === $action ) {
			$this->completed_steps[] = $action;
			Hostinger_Settings::update_setting( 'onboarding_steps', $this->completed_steps );
			unset( $_SESSION[ $action ] );
		}

		return $data;
	}

	public function post_content_change( int $post_id, WP_Post $post_after, WP_Post $post_before ) {
		$action         = Hostinger_Admin_Actions::EDIT_DESCRIPTION;
		$post_date      = get_the_date( 'Y-m-d H:i:s', $post_id );
		$modified_date  = get_the_modified_date( 'Y-m-d H:i:s', $post_id );
		$post_type      = get_post_type( $post_id );
		$session_value  = $_SESSION[ $action ] ?? '';
		$content_before = $post_before->post_content;
		$content_after  = $post_after->post_content;

		if ( in_array( $action, $this->completed_steps, true ) || $post_date === $modified_date ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( $post_type === 'post' && $content_before !== $content_after && $session_value === $action ) {
			$this->completed_steps[] = $action;
			Hostinger_Settings::update_setting( 'onboarding_steps', $this->completed_steps );
			unset( $_SESSION[ $action ] );
		}

	}

	public function edit_site_title( WP_Customize_Manager $data ): void {
		$action        = Hostinger_Admin_Actions::EDIT_SITE_TITLE;
		$changed_title = $data->changeset_data()['blogname']['value'] ?? '';
		$session_value = $_SESSION[ $action ] ?? '';

		if ( in_array( $action, $this->completed_steps, true ) ) {
			return;
		}

		if ( $session_value === $action && $changed_title !== '' && get_bloginfo( 'name' ) !== $changed_title ) {
			$this->completed_steps[] = $action;
			Hostinger_Settings::update_setting( 'onboarding_steps', $this->completed_steps );
			unset( $_SESSION[ $action ] );
		}
	}

	public function new_page_creation( int $post_id, WP_Post $post, bool $update ): void {
		$action        = Hostinger_Admin_Actions::ADD_PAGE;
		$session_value = $_SESSION[ $action ] ?? '';

		if ( in_array( $action, $this->completed_steps, true ) || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( $post->post_type === 'page' && ! $update && $session_value === $action ) {
			$this->completed_steps[] = $action;
			Hostinger_Settings::update_setting( 'onboarding_steps', $this->completed_steps );
			unset( $_SESSION[ $action ] );
		}
	}
}

new Hostinger_AutoComplete_Steps();

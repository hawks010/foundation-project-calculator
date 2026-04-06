<?php
/**
 * GitHub-backed updater bootstrap.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Foundation_Github_Updater {
	/**
	 * Singleton instance.
	 *
	 * @var Foundation_Github_Updater|null
	 */
	private static $instance = null;

	/**
	 * Underlying update checker instance.
	 *
	 * @var object|null
	 */
	private $checker = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Foundation_Github_Updater
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register the updater bootstrap.
	 */
	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'boot' ), 5 );
	}

	/**
	 * Boot the bundled plugin-update-checker library.
	 *
	 * @return void
	 */
	public function boot() {
		if ( null !== $this->checker ) {
			return;
		}

		$loader = FOUNDATION_PATH . 'plugin-update-checker/plugin-update-checker.php';
		if ( ! file_exists( $loader ) ) {
			return;
		}

		require_once $loader;

		if ( ! class_exists( '\YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
			return;
		}

		try {
			$this->checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
				'https://github.com/' . $this->get_repository(),
				FOUNDATION_FILE,
				$this->get_plugin_slug()
			);

			$token = $this->get_auth_token();
			if ( '' !== $token && method_exists( $this->checker, 'setAuthentication' ) ) {
				$this->checker->setAuthentication( $token );
			}
		} catch ( \Exception $exception ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Foundation Project Calculator updater error: ' . $exception->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}
	}

	/**
	 * Get the configured GitHub repository.
	 *
	 * @return string
	 */
	private function get_repository() {
		$repository = apply_filters( 'foundation_project_calculator_github_repository', 'hawks010/foundation-project-calculator' );

		return is_string( $repository ) ? trim( $repository ) : 'hawks010/foundation-project-calculator';
	}

	/**
	 * Get the configured auth token.
	 *
	 * @return string
	 */
	private function get_auth_token() {
		$token = '';

		if ( defined( 'FOUNDATION_PROJECT_CALCULATOR_GITHUB_TOKEN' ) && is_string( FOUNDATION_PROJECT_CALCULATOR_GITHUB_TOKEN ) ) {
			$token = trim( FOUNDATION_PROJECT_CALCULATOR_GITHUB_TOKEN );
		}

		$token = apply_filters( 'foundation_project_calculator_github_token', $token );

		return is_string( $token ) ? trim( $token ) : '';
	}

	/**
	 * Get the plugin slug.
	 *
	 * @return string
	 */
	private function get_plugin_slug() {
		return dirname( plugin_basename( FOUNDATION_FILE ) );
	}
}

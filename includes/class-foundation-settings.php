<?php
/**
 * Handles plugin settings for emails, branding and uploads.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Foundation_Settings {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function add_admin_menu() {
		$parent_slug = 'foundation-by-inkfire';
		add_submenu_page(
			$parent_slug,
			__( 'Project Calculator Settings', 'foundation-customer-form' ),
			__( 'Calculator Settings', 'foundation-customer-form' ),
			'manage_options',
			'foundation-form-settings',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting(
			'foundation_form_settings_group',
			'foundation_form_settings',
			array( 'sanitize_callback' => 'foundation_sanitize_settings' )
		);
	}

	public function render_settings_page() {
		$settings = foundation_get_settings();
		?>
		<div class="wrap foundation-settings-wrap">
			<style>
				.foundation-settings-wrap { max-width: 1240px; margin-top: 24px; }
				.foundation-settings-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-top: 20px; }
				.foundation-settings-card { background: #fff; border: 1px solid #dfe3e8; border-radius: 20px; padding: 24px; box-shadow: 0 10px 30px rgba(15,23,42,.05); }
				.foundation-settings-card h2 { margin-top: 0; font-size: 1.2rem; }
				.foundation-settings-card p.description { margin-top: 0; color: #52606d; }
				.foundation-settings-field { margin-bottom: 18px; }
				.foundation-settings-field label { display: block; font-weight: 700; margin-bottom: 7px; }
				.foundation-settings-field input[type="text"],
				.foundation-settings-field input[type="email"],
				.foundation-settings-field input[type="number"],
				.foundation-settings-field textarea { width: 100%; padding: 12px 14px; border-radius: 12px; border: 1px solid #c6cdd4; }
				.foundation-settings-field textarea { min-height: 110px; }
				.foundation-settings-note { background: #f6fbfa; border: 1px solid #cfe8df; color: #174a3b; padding: 14px 16px; border-radius: 14px; }
				.foundation-settings-actions { display: flex; align-items: center; gap: 14px; margin-top: 24px; }
				.foundation-settings-chip { display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 999px; background: #eef6ff; color: #114c7c; font-weight: 600; }
				@media (prefers-reduced-motion: no-preference) { .foundation-settings-card { transition: transform .2s ease, box-shadow .2s ease; } .foundation-settings-card:hover { transform: translateY(-2px); box-shadow: 0 14px 35px rgba(15,23,42,.08); } }
			</style>

			<h1><?php esc_html_e( 'Foundation Project Calculator Settings', 'foundation-customer-form' ); ?></h1>
			<p class="foundation-settings-note">These settings control who receives new leads, what your customer sees, and how uploaded files are packaged for staff. The builder controls the questions. This page controls the plumbing.</p>

			<form method="post" action="options.php">
				<?php settings_fields( 'foundation_form_settings_group' ); ?>
				<div class="foundation-settings-grid">
					<div class="foundation-settings-card">
						<h2><?php esc_html_e( 'Notifications & sender details', 'foundation-customer-form' ); ?></h2>
						<p class="description">Where new enquiries go, and what the outgoing emails look like.</p>
						<?php $this->text_field( 'admin_email', __( 'Admin email', 'foundation-customer-form' ), $settings['admin_email'], 'email' ); ?>
						<?php $this->text_field( 'cc_emails', __( 'CC emails', 'foundation-customer-form' ), $settings['cc_emails'], 'text', 'Comma separated. Leave blank if not needed.' ); ?>
						<?php $this->text_field( 'from_name', __( 'From name', 'foundation-customer-form' ), $settings['from_name'] ); ?>
						<?php $this->text_field( 'from_email', __( 'From email', 'foundation-customer-form' ), $settings['from_email'], 'email', 'If blank, WordPress will use its own defaults.' ); ?>
						<?php $this->checkbox_field( 'customer_confirmation_enabled', __( 'Send customer confirmation email', 'foundation-customer-form' ), ! empty( $settings['customer_confirmation_enabled'] ) ); ?>
						<?php $this->text_field( 'admin_subject_prefix', __( 'Admin email subject prefix', 'foundation-customer-form' ), $settings['admin_subject_prefix'] ); ?>
						<?php $this->text_field( 'customer_subject', __( 'Customer email subject', 'foundation-customer-form' ), $settings['customer_subject'] ); ?>
					</div>

					<div class="foundation-settings-card">
						<h2><?php esc_html_e( 'Frontend branding & copy', 'foundation-customer-form' ); ?></h2>
						<p class="description">Keeps the public form visually on-brand without hard-coding project content in JS.</p>
						<?php $this->text_field( 'launch_button_label', __( 'Launch button label', 'foundation-customer-form' ), $settings['launch_button_label'] ); ?>
						<?php $this->text_field( 'wizard_title', __( 'Wizard title', 'foundation-customer-form' ), $settings['wizard_title'] ); ?>
						<?php $this->text_field( 'currency_symbol', __( 'Currency symbol', 'foundation-customer-form' ), $settings['currency_symbol'] ); ?>
						<?php $this->text_field( 'logo_url', __( 'Logo URL', 'foundation-customer-form' ), $settings['logo_url'] ); ?>
						<?php $this->text_field( 'intro_image_url', __( 'Intro image URL', 'foundation-customer-form' ), $settings['intro_image_url'] ); ?>
						<?php $this->textarea_field( 'intro_heading', __( 'Intro heading', 'foundation-customer-form' ), $settings['intro_heading'], 3 ); ?>
						<?php $this->textarea_field( 'intro_text', __( 'Intro text', 'foundation-customer-form' ), $settings['intro_text'], 5 ); ?>
					</div>

					<div class="foundation-settings-card">
						<h2><?php esc_html_e( 'Testimonial & customer follow-up', 'foundation-customer-form' ); ?></h2>
						<p class="description">Used on the final step and customer email.</p>
						<?php $this->text_field( 'testimonial_image_url', __( 'Testimonial image URL', 'foundation-customer-form' ), $settings['testimonial_image_url'] ); ?>
						<?php $this->textarea_field( 'testimonial_heading', __( 'Testimonial heading', 'foundation-customer-form' ), $settings['testimonial_heading'], 3 ); ?>
						<?php $this->textarea_field( 'testimonial_quote', __( 'Testimonial quote', 'foundation-customer-form' ), $settings['testimonial_quote'], 5 ); ?>
						<?php $this->text_field( 'testimonial_attribution', __( 'Testimonial attribution', 'foundation-customer-form' ), $settings['testimonial_attribution'] ); ?>
						<?php $this->textarea_field( 'customer_intro', __( 'Customer email intro', 'foundation-customer-form' ), $settings['customer_intro'], 4 ); ?>
						<?php $this->textarea_field( 'success_message', __( 'Success screen message', 'foundation-customer-form' ), $settings['success_message'], 3 ); ?>
						<?php $this->text_field( 'portfolio_url', __( 'Customer CTA URL', 'foundation-customer-form' ), $settings['portfolio_url'] ); ?>
					</div>

					<div class="foundation-settings-card">
						<h2><?php esc_html_e( 'Social links & file handling', 'foundation-customer-form' ); ?></h2>
						<p class="description">Controls customer email links and how uploads are validated and packaged.</p>
						<div class="foundation-settings-chip">PDF summary + JSON + uploads package</div>
						<?php $this->text_field( 'linkedin_url', __( 'LinkedIn URL', 'foundation-customer-form' ), $settings['linkedin_url'] ); ?>
						<?php $this->text_field( 'twitter_url', __( 'Twitter/X URL', 'foundation-customer-form' ), $settings['twitter_url'] ); ?>
						<?php $this->text_field( 'facebook_url', __( 'Facebook URL', 'foundation-customer-form' ), $settings['facebook_url'] ); ?>
						<?php $this->text_field( 'instagram_url', __( 'Instagram URL', 'foundation-customer-form' ), $settings['instagram_url'] ); ?>
						<?php $this->text_field( 'tiktok_url', __( 'TikTok URL', 'foundation-customer-form' ), $settings['tiktok_url'] ); ?>
						<?php $this->text_field( 'allowed_file_types', __( 'Allowed file types', 'foundation-customer-form' ), $settings['allowed_file_types'], 'text', 'Comma separated extensions, for example: pdf,jpg,png,docx' ); ?>
						<?php $this->text_field( 'max_file_size_mb', __( 'Max file size per file (MB)', 'foundation-customer-form' ), $settings['max_file_size_mb'], 'number' ); ?>
						<?php $this->text_field( 'max_total_upload_mb', __( 'Max total upload size (MB)', 'foundation-customer-form' ), $settings['max_total_upload_mb'], 'number' ); ?>
						<?php $this->text_field( 'max_files_per_field', __( 'Max files per upload field', 'foundation-customer-form' ), $settings['max_files_per_field'], 'number' ); ?>
						<?php $this->checkbox_field( 'attach_pdf_summary', __( 'Attach PDF summary to admin email', 'foundation-customer-form' ), ! empty( $settings['attach_pdf_summary'] ) ); ?>
						<?php $this->checkbox_field( 'attach_json_summary', __( 'Attach JSON summary to admin email', 'foundation-customer-form' ), ! empty( $settings['attach_json_summary'] ) ); ?>
						<?php $this->checkbox_field( 'attach_zip_package', __( 'Attach ZIP package for staff', 'foundation-customer-form' ), ! empty( $settings['attach_zip_package'] ) ); ?>
					</div>
				</div>

				<div class="foundation-settings-actions">
					<?php submit_button( __( 'Save settings', 'foundation-customer-form' ), 'primary', 'submit', false ); ?>
					<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=foundation-form-builder' ) ); ?>"><?php esc_html_e( 'Back to builder', 'foundation-customer-form' ); ?></a>
				</div>
			</form>
		</div>
		<?php
	}

	private function text_field( $key, $label, $value, $type = 'text', $help = '' ) {
		?>
		<div class="foundation-settings-field">
			<label for="foundation_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
			<input id="foundation_<?php echo esc_attr( $key ); ?>" type="<?php echo esc_attr( $type ); ?>" name="foundation_form_settings[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>">
			<?php if ( $help ) : ?><p class="description"><?php echo esc_html( $help ); ?></p><?php endif; ?>
		</div>
		<?php
	}

	private function textarea_field( $key, $label, $value, $rows = 4 ) {
		?>
		<div class="foundation-settings-field">
			<label for="foundation_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
			<textarea id="foundation_<?php echo esc_attr( $key ); ?>" rows="<?php echo esc_attr( $rows ); ?>" name="foundation_form_settings[<?php echo esc_attr( $key ); ?>]"><?php echo esc_textarea( $value ); ?></textarea>
		</div>
		<?php
	}

	private function checkbox_field( $key, $label, $checked ) {
		?>
		<div class="foundation-settings-field">
			<label>
				<input type="checkbox" name="foundation_form_settings[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( $checked ); ?>>
				<?php echo esc_html( $label ); ?>
			</label>
		</div>
		<?php
	}
}

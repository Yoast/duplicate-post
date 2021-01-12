<?php
/**
 * Duplicate Post plugin file.
 *
 * @package Yoast\WP\Duplicate_Post\Admin\Views
 */

if ( ! defined( 'DUPLICATE_POST_CURRENT_VERSION' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
?>
<div class="wrap">
	<h1>
		<?php \esc_html_e( 'Duplicate Post Options', 'duplicate-post' ); ?>
	</h1>

	<form id="duplicate_post_settings_form" method="post" action="options.php" style="clear: both">
		<?php \settings_fields( 'duplicate_post_group' ); ?>

		<header role="tablist" aria-label="<?php \esc_attr_e( 'Settings sections', 'duplicate-post' ); ?>"
				class="nav-tab-wrapper">
			<button
					type="button"
					role="tab"
					class="nav-tab nav-tab-active"
					aria-selected="true"
					aria-controls="what-tab"
					id="what"><?php \esc_html_e( 'What to copy', 'duplicate-post' ); ?>
			</button>
			<button
					type="button"
					role="tab"
					class="nav-tab"
					aria-selected="false"
					aria-controls="who-tab"
					id="who"
					tabindex="-1"><?php \esc_html_e( 'Permissions', 'duplicate-post' ); ?>
			</button>
			<button
					type="button"
					role="tab"
					class="nav-tab"
					aria-selected="false"
					aria-controls="where-tab"
					id="where"
					tabindex="-1"><?php \esc_html_e( 'Display', 'duplicate-post' ); ?>
			</button>
		</header>

		<section
				tabindex="0"
				role="tabpanel"
				id="what-tab"
				aria-labelledby="what">
			<h2 class="hide-if-js"><?php \esc_html_e( 'What to copy', 'duplicate-post' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php \esc_html_e( 'Post/page elements to copy', 'duplicate-post' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php \esc_html_e( 'Post/page elements to copy', 'duplicate-post' ); ?></legend>
							<?php
							// phpcs:ignore WordPress.Security.EscapeOutput -- Already escapes correctly.
							echo $this->generate_tab_inputs( 'what-to-copy', 'elements-to-copy' );
							?>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="duplicate-post-title-prefix"><?php \esc_html_e( 'Title prefix', 'duplicate-post' ); ?></label>
					</th>
					<td>
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput -- Already escapes correctly.
						echo $this->generate_input( 'duplicate_post_title_prefix' );
						?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="duplicate-post-title-suffix"><?php \esc_html_e( 'Title suffix', 'duplicate-post' ); ?></label>
					</th>
					<td>
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput -- Already escapes correctly.
						echo $this->generate_input( 'duplicate_post_title_suffix' );
						?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="duplicate-post-increase-menu-order-by"><?php \esc_html_e( 'Increase menu order by', 'duplicate-post' ); ?></label>
					</th>
					<td>
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput -- Already escapes correctly.
						echo $this->generate_input( 'duplicate_post_increase_menu_order_by' );
						?>

					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="duplicate-post-blacklist"><?php \esc_html_e( 'Do not copy these fields', 'duplicate-post' ); ?></label>
					</th>
					<td id="textfield">
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput -- Already escapes correctly.
						echo $this->generate_input( 'duplicate_post_blacklist' );
						?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php \esc_html_e( 'Do not copy these taxonomies', 'duplicate-post' ); ?><br/>
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php \esc_html_e( 'Do not copy these taxonomies', 'duplicate-post' ); ?></legend>
							<?php
							// phpcs:ignore WordPress.Security.EscapeOutput -- Already escapes correctly.
							echo $this->generate_input( 'duplicate_post_taxonomies_blacklist' );
							?>
							<button type="button" class="button-link hide-if-no-js toggle-private-taxonomies"
									aria-expanded="false">
								<?php \esc_html_e( 'Show/hide private taxonomies', 'duplicate-post' ); ?>
							</button>
						</fieldset>
					</td>
				</tr>
			</table>
		</section>
		<section
				tabindex="0"
				role="tabpanel"
				id="who-tab"
				aria-labelledby="who"
				hidden="hidden">
			<h2 class="hide-if-js"><?php \esc_html_e( 'Permissions', 'duplicate-post' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php if ( \current_user_can( 'promote_users' ) ) { ?>
					<tr>
						<th scope="row"><?php \esc_html_e( 'Roles allowed to copy', 'duplicate-post' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><?php \esc_html_e( 'Roles allowed to copy', 'duplicate-post' ); ?></legend>
								<?php
								// phpcs:ignore WordPress.Security.EscapeOutput -- Already escapes correctly.
								echo $this->generate_input( 'duplicate_post_roles' );
								?>
								<p>
									<?php \esc_html_e( 'Warning: users will be able to copy, rewrite and republish all posts, even those of other users.', 'duplicate-post' ); ?>
									<br/>
									<?php \esc_html_e( 'Passwords and contents of password-protected posts may become visible to undesired users and visitors.', 'duplicate-post' ); ?>
								</p>
							</fieldset>
						</td>
					</tr>
				<?php } ?>
				<tr>
					<th scope="row"><?php \esc_html_e( 'Enable for these post types', 'duplicate-post' ); ?>
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php \esc_html_e( 'Enable for these post types', 'duplicate-post' ); ?></legend>
							<?php
										// phpcs:ignore WordPress.Security.EscapeOutput -- Already escapes correctly.
							echo $this->generate_input( 'duplicate_post_types_enabled' );
							?>
							<p>
								<?php \esc_html_e( 'Select the post types you want the plugin to be enabled for.', 'duplicate-post' ); ?>
								<br/>
								<?php \esc_html_e( 'Whether the links are displayed for custom post types registered by themes or plugins depends on their use of standard WordPress UI elements.', 'duplicate-post' ); ?>
							</p>
						</fieldset>
					</td>
				</tr>
			</table>
		</section>
		<section
				tabindex="0"
				role="tabpanel"
				id="where-tab"
				aria-labelledby="where"
				hidden="hidden">
			<h2 class="hide-if-js"><?php \esc_html_e( 'Display', 'duplicate-post' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php \esc_html_e( 'Show these links', 'duplicate-post' ); ?></th>
					<td>
						<fieldset>
							<?php
							// phpcs:ignore WordPress.Security.EscapeOutput -- Already escapes correctly.
							echo $this->generate_tab_inputs( 'display', 'show-links' );
							?>
						</fieldset>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php \esc_html_e( 'Show links in', 'duplicate-post' ); ?></th>
					<td>
						<fieldset>
							<?php
							// phpcs:ignore WordPress.Security.EscapeOutput -- Already escapes correctly.
							echo $this->generate_tab_inputs( 'display', 'show-links-in' );
							?>
						</fieldset>
						<p>
							<?php \esc_html_e( 'Whether the links are displayed for custom post types registered by themes or plugins depends on their use of standard WordPress UI elements.', 'duplicate-post' ); ?>
							<br/>
							<?php
							\printf(
							/* translators: 1: Code start tag, 2: Code closing tag, 3: Link start tag to the template tag documentation, 4: Link closing tag. */
								\esc_html__( 'You can also use the template tag %1$sduplicate_post_clone_post_link( $link, $before, $after, $id )%2$s. %3$sMore info on the template tag%4$s.', 'duplicate-post' ),
								'<code>',
								'</code>',
								'<a href="' . \esc_url( 'https://developer.yoast.com/duplicate-post/functions-template-tags#duplicate_post_clone_post_link' ) . '">',
								'</a>'
							);
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php \esc_html_e( 'Show original item:', 'duplicate-post' ); ?></th>
					<td>
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput -- Already escapes correctly.
						echo $this->generate_tab_inputs( 'display', 'show-original' );
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php \esc_html_e( 'Update notice', 'duplicate-post' ); ?></th>
					<td>
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput -- Already escapes correctly.
						echo $this->generate_input( 'duplicate_post_show_notice' );
						?>
					</td>
				</tr>
			</table>
		</section>
		<p class="submit">
			<input type="submit" class="button button-primary" value="<?php \esc_html_e( 'Save changes', 'duplicate-post' ); ?>"/>
		</p>
	</form>
</div>

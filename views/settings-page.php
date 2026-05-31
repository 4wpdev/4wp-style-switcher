<?php
/**
 * Admin settings template.
 *
 * @package ForWP\StyleSwitcher
 */

use ForWP\StyleSwitcher\Block_Theme_Guard;

defined( 'ABSPATH' ) || exit;

$heading_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28" fill="none" focusable="false" aria-hidden="true"><circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.75"/><path d="M12 3.5v17M3.5 12h17" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>';
?>
<div class="wrap forwp-ss-admin-shell">
	<h1 class="forwp-ss-admin-heading">
		<span class="forwp-ss-admin-heading__icon" aria-hidden="true">
			<?php
			echo wp_kses(
				$heading_svg,
				array(
					'svg'    => array(
						'xmlns'        => true,
						'viewbox'      => true,
						'width'        => true,
						'height'       => true,
						'fill'         => true,
						'focusable'    => true,
						'aria-hidden'  => true,
					),
					'circle' => array(
						'cx'           => true,
						'cy'           => true,
						'r'            => true,
						'stroke'       => true,
						'stroke-width' => true,
					),
					'path'   => array(
						'd'              => true,
						'stroke'         => true,
						'stroke-width'   => true,
						'stroke-linecap' => true,
					),
				)
			);
			?>
		</span>
		<span class="forwp-ss-admin-heading__text"><?php esc_html_e( '4WP Theme Style Switcher', '4wp-style-switcher' ); ?></span>
	</h1>

	<?php if ( ! Block_Theme_Guard::is_supported() ) : ?>
		<div class="notice notice-warning">
			<p>
				<?php
				esc_html_e(
					'This plugin requires an active block theme (FSE) with theme.json style variations in /styles/.',
					'4wp-style-switcher'
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<div id="forwp-ss-settings-status" class="forwp-ss-status forwp-ss-status--global" aria-live="polite"></div>

	<div class="forwp-ss-admin-app">
		<div class="forwp-ss-tab-panel components-tab-panel">
			<div class="components-tab-panel__tabs" role="tablist" aria-label="<?php esc_attr_e( '4WP Style Switcher settings', '4wp-style-switcher' ); ?>">
				<button type="button" role="tab" id="forwp-ss-tab-general" class="components-button components-tab-panel__tabs-item forwp-ss-tab is-active" aria-selected="true" aria-controls="forwp-ss-panel-general" data-tab="general">
					<?php esc_html_e( 'General', '4wp-style-switcher' ); ?>
				</button>
				<button type="button" role="tab" id="forwp-ss-tab-variations" class="components-button components-tab-panel__tabs-item forwp-ss-tab" aria-selected="false" aria-controls="forwp-ss-panel-variations" data-tab="variations" tabindex="-1">
					<?php esc_html_e( 'Variations', '4wp-style-switcher' ); ?>
				</button>
				<button type="button" role="tab" id="forwp-ss-tab-ab-testing" class="components-button components-tab-panel__tabs-item forwp-ss-tab" aria-selected="false" aria-controls="forwp-ss-panel-ab-testing" data-tab="ab-testing" tabindex="-1">
					<?php esc_html_e( 'A/B Testing', '4wp-style-switcher' ); ?>
				</button>
				<button type="button" role="tab" id="forwp-ss-tab-documentation" class="components-button components-tab-panel__tabs-item forwp-ss-tab" aria-selected="false" aria-controls="forwp-ss-panel-documentation" data-tab="documentation" tabindex="-1">
					<?php esc_html_e( 'Documentation', '4wp-style-switcher' ); ?>
				</button>
			</div>

			<div id="forwp-ss-panel-general" role="tabpanel" aria-labelledby="forwp-ss-tab-general" class="components-tab-panel__tab-content">
				<div class="forwp-ss-intro-card">
					<div class="forwp-ss-intro-card__body">
						<h3 class="forwp-ss-intro-card__title"><?php esc_html_e( 'Site-wide defaults', '4wp-style-switcher' ); ?></h3>
						<p class="forwp-ss-intro-card__text">
							<?php esc_html_e( 'Choose the default theme.json style variation and whether visitors can switch styles on the frontend.', '4wp-style-switcher' ); ?>
						</p>
					</div>
				</div>

				<div class="forwp-ss-panel">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Frontend switcher', '4wp-style-switcher' ); ?></th>
							<td>
								<label>
									<input type="checkbox" id="forwp-ss-visitor-switcher" />
									<?php esc_html_e( 'Allow visitors to choose a style variation', '4wp-style-switcher' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'When disabled, only per-page and site defaults apply. Locked pages always hide the switcher.', '4wp-style-switcher' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="forwp-ss-default-variation"><?php esc_html_e( 'Default variation', '4wp-style-switcher' ); ?></label></th>
							<td>
								<select id="forwp-ss-default-variation"></select>
								<p class="description"><?php esc_html_e( 'Used when a page has no style set and the visitor has no saved preference.', '4wp-style-switcher' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="forwp-ss-switcher-position"><?php esc_html_e( 'Switcher position', '4wp-style-switcher' ); ?></label></th>
							<td>
								<select id="forwp-ss-switcher-position">
									<option value="bottom-right"><?php esc_html_e( 'Bottom right', '4wp-style-switcher' ); ?></option>
									<option value="bottom-left"><?php esc_html_e( 'Bottom left', '4wp-style-switcher' ); ?></option>
									<option value="top-right"><?php esc_html_e( 'Top right', '4wp-style-switcher' ); ?></option>
									<option value="top-left"><?php esc_html_e( 'Top left', '4wp-style-switcher' ); ?></option>
								</select>
							</td>
						</tr>
					</table>
				</div>

				<div class="forwp-ss-panel forwp-ss-panel--storage">
					<h2><?php esc_html_e( 'Visitor storage', '4wp-style-switcher' ); ?></h2>
					<p class="description forwp-ss-panel-lead">
						<?php esc_html_e( 'How long the visitor’s style choice is kept in the browser (localStorage, synced to cookie for server-side rendering).', '4wp-style-switcher' ); ?>
					</p>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="forwp-ss-storage-days"><?php esc_html_e( 'localStorage retention', '4wp-style-switcher' ); ?></label></th>
							<td>
								<input type="number" id="forwp-ss-storage-days" min="1" max="3650" step="1" value="365" class="small-text" />
								<span><?php esc_html_e( 'days', '4wp-style-switcher' ); ?></span>
								<p class="description"><?php esc_html_e( 'After this period the saved style expires and the site default applies again.', '4wp-style-switcher' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<div class="forwp-ss-panel forwp-ss-panel--user-prefs">
					<h2>
						<?php esc_html_e( 'User preferences', '4wp-style-switcher' ); ?>
						<span class="forwp-ss-badge forwp-ss-badge--soon"><?php esc_html_e( 'Coming soon', '4wp-style-switcher' ); ?></span>
					</h2>
					<p class="description forwp-ss-panel-lead">
						<?php esc_html_e( 'Logged-in users will be able to save a personal style variation in their profile. Until then, all visitors use the shared localStorage/cookie preference.', '4wp-style-switcher' ); ?>
					</p>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Per-user styles', '4wp-style-switcher' ); ?></th>
							<td>
								<label>
									<input type="checkbox" id="forwp-ss-user-prefs-enabled" disabled />
									<?php esc_html_e( 'Save style preference per logged-in user', '4wp-style-switcher' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Planned: store in user meta and override visitor cookie when authenticated.', '4wp-style-switcher' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<div class="forwp-ss-panel forwp-ss-panel--light-dark">
					<h2><?php esc_html_e( 'Light / Dark (menu block)', '4wp-style-switcher' ); ?></h2>
					<p class="description forwp-ss-panel-lead">
						<?php esc_html_e( 'Map two style variations for the Light / Dark block in the navigation menu. This does not change the floating frontend switcher — that always lists all allowed variations.', '4wp-style-switcher' ); ?>
					</p>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Menu block', '4wp-style-switcher' ); ?></th>
							<td>
								<label>
									<input type="checkbox" id="forwp-ss-light-dark-enabled" />
									<?php esc_html_e( 'Enable Light / Dark mapping for the navigation block', '4wp-style-switcher' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'When enabled, the Light / Dark block in the menu shows sun/moon icons for the two mapped variations. Both must be allowed on the Variations tab.', '4wp-style-switcher' ); ?>
								</p>
							</td>
						</tr>
						<tr class="forwp-ss-light-dark-row">
							<th scope="row"><label for="forwp-ss-light-variation"><?php esc_html_e( 'Light variation', '4wp-style-switcher' ); ?></label></th>
							<td>
								<select id="forwp-ss-light-variation"></select>
								<p class="description"><?php esc_html_e( 'Style variation used for the light appearance.', '4wp-style-switcher' ); ?></p>
							</td>
						</tr>
						<tr class="forwp-ss-light-dark-row">
							<th scope="row"><label for="forwp-ss-dark-variation"><?php esc_html_e( 'Dark variation', '4wp-style-switcher' ); ?></label></th>
							<td>
								<select id="forwp-ss-dark-variation"></select>
								<p class="description"><?php esc_html_e( 'Style variation used for the dark appearance.', '4wp-style-switcher' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<p class="forwp-ss-inline-actions">
					<button type="button" class="button button-primary" id="forwp-ss-save-settings">
						<?php esc_html_e( 'Save settings', '4wp-style-switcher' ); ?>
					</button>
				</p>
			</div>

			<div id="forwp-ss-panel-variations" role="tabpanel" aria-labelledby="forwp-ss-tab-variations" class="components-tab-panel__tab-content" hidden>
				<div class="forwp-ss-intro-card">
					<div class="forwp-ss-intro-card__body">
						<h3 class="forwp-ss-intro-card__title"><?php esc_html_e( 'Allowed variations', '4wp-style-switcher' ); ?></h3>
						<p class="forwp-ss-intro-card__text">
							<?php esc_html_e( 'Choose which style variations visitors and editors can use. Only checked variations appear in the frontend switcher and page editor.', '4wp-style-switcher' ); ?>
						</p>
					</div>
				</div>

				<div class="forwp-ss-panel forwp-ss-panel--muted">
					<p class="forwp-ss-variations-toolbar">
						<button type="button" class="button button-secondary" id="forwp-ss-allowed-select-all">
							<?php esc_html_e( 'Select all', '4wp-style-switcher' ); ?>
						</button>
						<button type="button" class="button button-secondary" id="forwp-ss-allowed-select-none">
							<?php esc_html_e( 'Select none', '4wp-style-switcher' ); ?>
						</button>
					</p>
					<ul id="forwp-ss-variations-list" class="forwp-ss-variations-list forwp-ss-variations-list--allowed"></ul>
					<p class="forwp-ss-inline-actions">
						<button type="button" class="button button-primary" id="forwp-ss-save-allowed">
							<?php esc_html_e( 'Save allowed variations', '4wp-style-switcher' ); ?>
						</button>
					</p>
				</div>
			</div>

			<div id="forwp-ss-panel-ab-testing" role="tabpanel" aria-labelledby="forwp-ss-tab-ab-testing" class="components-tab-panel__tab-content" hidden>
				<div class="forwp-ss-intro-card">
					<div class="forwp-ss-intro-card__body">
						<h3 class="forwp-ss-intro-card__title">
							<?php esc_html_e( 'A/B testing', '4wp-style-switcher' ); ?>
						</h3>
						<p class="forwp-ss-intro-card__text">
							<?php esc_html_e( 'Split new visitors between two style variations. Returning visitors keep their assigned variation via cookie. Assignment counts are stored in a lightweight daily stats table.', '4wp-style-switcher' ); ?>
						</p>
					</div>
				</div>

				<div class="forwp-ss-panel">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable A/B test', '4wp-style-switcher' ); ?></th>
							<td>
								<label>
									<input type="checkbox" id="forwp-ss-ab-enabled" />
									<?php esc_html_e( 'Assign new visitors to Variation A or B', '4wp-style-switcher' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Skips visitors who already have a style cookie or a locked page style.', '4wp-style-switcher' ); ?></p>
							</td>
						</tr>
						<tr class="forwp-ss-ab-row">
							<th scope="row"><label for="forwp-ss-ab-variation-a"><?php esc_html_e( 'Variation A', '4wp-style-switcher' ); ?></label></th>
							<td>
								<select id="forwp-ss-ab-variation-a"></select>
							</td>
						</tr>
						<tr class="forwp-ss-ab-row">
							<th scope="row"><label for="forwp-ss-ab-variation-b"><?php esc_html_e( 'Variation B', '4wp-style-switcher' ); ?></label></th>
							<td>
								<select id="forwp-ss-ab-variation-b"></select>
							</td>
						</tr>
						<tr class="forwp-ss-ab-row">
							<th scope="row"><label for="forwp-ss-ab-split"><?php esc_html_e( 'Traffic split', '4wp-style-switcher' ); ?></label></th>
							<td>
								<div class="forwp-ss-split-control">
									<input type="range" id="forwp-ss-ab-split" min="0" max="100" step="1" value="50" />
									<div class="forwp-ss-split-labels">
										<span id="forwp-ss-ab-split-a-label">A: 50%</span>
										<span id="forwp-ss-ab-split-b-label">B: 50%</span>
									</div>
								</div>
								<p class="description"><?php esc_html_e( 'Percentage of new visitors assigned to Variation A. The remainder goes to Variation B.', '4wp-style-switcher' ); ?></p>
							</td>
						</tr>
					</table>

					<div class="forwp-ss-panel forwp-ss-panel--muted forwp-ss-ab-stats" id="forwp-ss-ab-stats" hidden>
						<h3><?php esc_html_e( 'Traffic statistics', '4wp-style-switcher' ); ?></h3>
						<p class="description"><?php esc_html_e( 'Daily assignment counts used to monitor the live split. Only new visitor assignments are recorded — no per-user rows.', '4wp-style-switcher' ); ?></p>
						<table class="widefat striped forwp-ss-ab-stats-table">
							<thead>
								<tr>
									<th scope="col"><?php esc_html_e( 'Period', '4wp-style-switcher' ); ?></th>
									<th scope="col"><?php esc_html_e( 'A', '4wp-style-switcher' ); ?></th>
									<th scope="col"><?php esc_html_e( 'B', '4wp-style-switcher' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Actual A%', '4wp-style-switcher' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Target A%', '4wp-style-switcher' ); ?></th>
								</tr>
							</thead>
							<tbody id="forwp-ss-ab-stats-body"></tbody>
						</table>
					</div>

					<div class="forwp-ss-panel forwp-ss-panel--muted forwp-ss-analytics-note">
						<h3><?php esc_html_e( 'Analytics integration', '4wp-style-switcher' ); ?></h3>
						<p><?php esc_html_e( 'New assignments also fire hooks for external analytics. Built-in stats above cover split monitoring only.', '4wp-style-switcher' ); ?></p>
						<ul class="forwp-ss-steps">
							<li><code>forwp_style_switcher_ab_assigned</code> — <?php esc_html_e( 'PHP action when a visitor is assigned to cohort A or B', '4wp-style-switcher' ); ?></li>
							<li><code>forwp_style_switcher_analytics_track</code> — <?php esc_html_e( 'PHP action for all analytics events', '4wp-style-switcher' ); ?></li>
							<li><code>forwp-ss-analytics</code> — <?php esc_html_e( 'JS CustomEvent on document (detail: cohort, variation, …)', '4wp-style-switcher' ); ?></li>
							<li><code>window.forwpStyleSwitcherAnalytics.push(event, data)</code> — <?php esc_html_e( 'JS helper; auto-forwards to dataLayer / gtag when present', '4wp-style-switcher' ); ?></li>
						</ul>
						<p class="description"><?php esc_html_e( 'Example GTM trigger: Custom Event → forwp_style_switcher_ab_assigned.', '4wp-style-switcher' ); ?></p>
					</div>

					<p class="forwp-ss-inline-actions">
						<button type="button" class="button button-primary" id="forwp-ss-save-ab">
							<?php esc_html_e( 'Save A/B settings', '4wp-style-switcher' ); ?>
						</button>
					</p>
				</div>
			</div>

			<div id="forwp-ss-panel-documentation" role="tabpanel" aria-labelledby="forwp-ss-tab-documentation" class="components-tab-panel__tab-content" hidden>
				<div class="forwp-ss-doc-accordion">
					<details open>
						<summary><?php esc_html_e( 'Resolution priority', '4wp-style-switcher' ); ?></summary>
						<ol class="forwp-ss-steps">
							<li><?php esc_html_e( 'Locked page style (admin checkbox in the editor)', '4wp-style-switcher' ); ?></li>
							<li><?php esc_html_e( 'Visitor cookie preference (if switcher enabled and page not locked)', '4wp-style-switcher' ); ?></li>
							<li><?php esc_html_e( 'Per-page style from the block editor', '4wp-style-switcher' ); ?></li>
							<li><?php esc_html_e( 'Site default from this settings screen', '4wp-style-switcher' ); ?></li>
						</ol>
					</details>
					<details>
						<summary><?php esc_html_e( 'Per-page style (editor)', '4wp-style-switcher' ); ?></summary>
						<p><?php esc_html_e( 'Open any page or post and use the “Page style” panel in the document sidebar to pick a variation or lock it for visitors.', '4wp-style-switcher' ); ?></p>
					</details>
					<details>
						<summary><?php esc_html_e( 'Navigation block (Light / Dark)', '4wp-style-switcher' ); ?></summary>
						<p><?php esc_html_e( 'Edit the site header navigation and insert the “Light / Dark” block next to your menu links. It uses the Light and Dark variations configured in General settings.', '4wp-style-switcher' ); ?></p>
					</details>
					<details>
						<summary><?php esc_html_e( 'A/B testing & analytics', '4wp-style-switcher' ); ?></summary>
						<p><?php esc_html_e( 'Use the A/B Testing tab to choose Variation A, Variation B, and a traffic split (e.g. 50/50). New visitors without a style cookie are assigned automatically; counts appear in the Traffic statistics table.', '4wp-style-switcher' ); ?></p>
					</details>
					<details>
						<summary><?php esc_html_e( 'Visitor storage', '4wp-style-switcher' ); ?></summary>
						<p><?php esc_html_e( 'The visitor’s choice is stored in localStorage with an expiry (configurable under General → Visitor storage) and mirrored to a cookie so PHP can apply the style on the next page load.', '4wp-style-switcher' ); ?></p>
					</details>
					<details>
						<summary><?php esc_html_e( 'User preferences (planned)', '4wp-style-switcher' ); ?></summary>
						<p><?php esc_html_e( 'Logged-in users will eventually save a personal variation in user meta, overriding the shared visitor cookie when enabled.', '4wp-style-switcher' ); ?></p>
					</details>
					<details>
						<summary><?php esc_html_e( 'Requirements', '4wp-style-switcher' ); ?></summary>
						<p><?php esc_html_e( 'FSE block theme only. Style variations must be defined in the theme’s /styles/ directory.', '4wp-style-switcher' ); ?></p>
					</details>
				</div>
			</div>
		</div>
	</div>
</div>

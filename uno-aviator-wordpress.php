<?php
/**
 * Plugin Name: UNO Aviator Demo
 * Description: Responsive Aviator-style crash game interface for WordPress, connected to a demo-credit backend. No real-money wagering or payments.
 * Version: 1.0.0
 * Author: UNO Aviator
 * License: GPL-2.0-or-later
 * Text Domain: uno-aviator-demo
 */
if (!defined('ABSPATH')) exit;

class UNO_Aviator_Demo_Plugin {
    const OPT = 'uno_aviator_demo_options';
    public function __construct() {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_init', [$this, 'settings']);
        add_shortcode('uno_aviator', [$this, 'shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
    }
    public function menu() {
        add_options_page('UNO Aviator Demo', 'UNO Aviator Demo', 'manage_options', 'uno-aviator-demo', [$this, 'settings_page']);
    }
    public function settings() {
        register_setting('uno_aviator_demo_group', self::OPT, [
            'type' => 'array',
            'sanitize_callback' => function($v) {
                $v = is_array($v) ? $v : [];
                return ['api_url' => esc_url_raw(trim($v['api_url'] ?? ''))];
            },
            'default' => ['api_url' => 'http://localhost:4000']
        ]);
        add_settings_section('uno_aviator_main', 'Backend connection', function() {
            echo '<p>Set the base URL of your UNO Aviator demo API. Keep the API in demo-credit mode.</p>';
        }, 'uno-aviator-demo');
        add_settings_field('api_url', 'API base URL', function() {
            $opts = get_option(self::OPT, []); $value = $opts['api_url'] ?? 'http://localhost:4000';
            printf('<input class="regular-text" type="url" name="%s[api_url]" value="%s" placeholder="https://api.example.com" />', esc_attr(self::OPT), esc_attr($value));
            echo '<p class="description">Enter the API origin only, without trailing /api. For local development, use http://localhost:4000.</p>';
        }, 'uno-aviator-demo', 'uno_aviator_main');
    }
    public function settings_page() {
        if (!current_user_can('manage_options')) return;
        echo '<div class="wrap"><h1>UNO Aviator Demo</h1><p><strong>Demo mode only.</strong> This plugin does not process deposits, withdrawals, or real-money bets.</p><form method="post" action="options.php">';
        settings_fields('uno_aviator_demo_group'); do_settings_sections('uno-aviator-demo'); submit_button(); echo '</form><hr><p>Add the shortcode <code>[uno_aviator]</code> to any page or post.</p></div>';
    }
    public function register_assets() {
        wp_register_style('uno-aviator-demo', plugin_dir_url(__FILE__) . 'assets/uno-aviator.css', [], '1.0.0');
        wp_register_script('uno-aviator-demo', plugin_dir_url(__FILE__) . 'assets/uno-aviator.js', [], '1.0.0', true);
    }
    public function shortcode($atts = []) {
        $opts = get_option(self::OPT, []);
        $api = isset($opts['api_url']) ? untrailingslashit($opts['api_url']) : 'http://localhost:4000';
        wp_enqueue_style('uno-aviator-demo'); wp_enqueue_script('uno-aviator-demo');
        wp_localize_script('uno-aviator-demo', 'UNO_Aviator_Config', [
            'apiUrl' => esc_url_raw($api),
            'nonce' => wp_create_nonce('uno_aviator_demo'),
            'loggedIn' => is_user_logged_in(),
        ]);
        ob_start(); ?>
        <div class="ua-wrap" data-ua-root>
          <header class="ua-topbar">
            <div class="ua-brand"><span class="ua-plane-mark">✈</span><span class="ua-logo">UNO <i>Aviator</i></span><small>CRASH GAME DEMO</small></div>
            <div class="ua-wallet"><span class="ua-online-dot"></span><span>DEMO BALANCE</span><strong data-ua-balance>— INR</strong></div>
            <button class="ua-ghost ua-account-toggle" data-ua-open-auth>Login / Register</button>
          </header>
          <div class="ua-notice"><strong>DEMO MODE</strong><span>Virtual credits only. No deposits, withdrawals, or real-money wagering.</span></div>
          <main class="ua-layout">
            <section class="ua-main-column">
              <div class="ua-history" data-ua-history aria-label="Recent round history"><span>Loading rounds…</span></div>
              <section class="ua-flight-card">
                <div class="ua-flight-glow"></div><div class="ua-flight-label"><span class="ua-live-dot"></span><span data-ua-round>CONNECTING TO GAME</span></div>
                <div class="ua-flight-scene"><div class="ua-gridline"></div><div class="ua-flight-trail" data-ua-trail></div><div class="ua-aircraft" data-ua-aircraft>✈</div><div class="ua-multiplier" data-ua-multiplier>1.00<span>x</span></div></div>
                <div class="ua-flight-footer"><span data-ua-status>Connecting to server…</span><span data-ua-hash title="Round commitment">Waiting for round</span></div>
              </section>
              <section class="ua-bet-grid">
                <?php foreach ([1,2] as $panel): ?>
                <div class="ua-bet-card" data-ua-panel="<?php echo esc_attr($panel); ?>">
                  <div class="ua-tabs"><button class="is-active" data-ua-tab="bet">Bet</button><button data-ua-tab="auto">Auto</button></div>
                  <label class="ua-amount-label">Stake amount (demo INR)</label>
                  <div class="ua-amount-row"><button data-ua-minus aria-label="Decrease stake">−</button><input type="number" min="1" step="1" value="10" data-ua-amount aria-label="Stake amount"><button data-ua-plus aria-label="Increase stake">+</button></div>
                  <div class="ua-presets"><button data-ua-preset="10">10</button><button data-ua-preset="50">50</button><button data-ua-preset="100">100</button><button data-ua-preset="500">500</button></div>
                  <label class="ua-auto-row"><input type="checkbox" data-ua-auto-bet> <span>Auto Bet</span></label>
                  <label class="ua-auto-row"><input type="checkbox" data-ua-auto-cash> <span>Auto Cash Out</span><input class="ua-auto-value" type="number" min="1.01" step="0.01" value="1.25" data-ua-auto-value aria-label="Auto cash out multiplier"> <b>x</b></label>
                  <button class="ua-bet-button" data-ua-bet>BET <strong><span data-ua-bet-amount>10.00</span> INR</strong></button>
                  <button class="ua-cashout-button" data-ua-cashout disabled>Cash Out</button>
                  <p class="ua-panel-message" data-ua-message></p>
                </div>
                <?php endforeach; ?>
              </section>
              <section class="ua-table-card"><div class="ua-table-head"><h3>Round Activity</h3><div class="ua-table-tabs"><button class="is-active" data-ua-list="mine">My Bets</button><button data-ua-list="all">All Bets</button><button data-ua-list="rounds">History</button></div></div><div class="ua-table-scroll"><table><thead><tr><th>Round / Player</th><th>Bet</th><th>Multiplier</th><th>Payout</th><th>Status</th></tr></thead><tbody data-ua-table><tr><td colspan="5">Log in to view demo activity.</td></tr></tbody></table></div></section>
            </section>
            <aside class="ua-side-column"><div class="ua-side-card"><h3><span>◈</span> Game Information</h3><div class="ua-stat"><span>Round status</span><strong data-ua-side-state>Connecting</strong></div><div class="ua-stat"><span>Round ID</span><strong data-ua-side-round>—</strong></div><div class="ua-stat"><span>Currency</span><strong>Demo INR</strong></div><div class="ua-stat"><span>Mode</span><strong class="ua-green-text">Virtual credits</strong></div></div><div class="ua-side-card ua-responsible"><div class="ua-shield">✓</div><h3>Play responsibly</h3><p>This is a simulation for entertainment and testing. Credits have no cash value and cannot be purchased or withdrawn.</p></div><div class="ua-side-card"><h3>How to play</h3><ol class="ua-howto"><li>Sign in or create a demo account.</li><li>Choose a stake using virtual credits.</li><li>Place your bet before the round starts.</li><li>If the round is flying, cash out before it crashes.</li></ol></div><div class="ua-side-card ua-auth-card" data-ua-auth-card><h3>Player account</h3><p data-ua-auth-status>Sign in to save your demo activity.</p><button class="ua-outline-button" data-ua-open-auth>Login / Create account</button><button class="ua-outline-button ua-hidden" data-ua-logout>Log out</button></div></aside>
          </main>
          <div class="ua-modal ua-hidden" data-ua-modal role="dialog" aria-modal="true" aria-label="Player login and registration"><div class="ua-modal-panel"><button class="ua-modal-close" data-ua-close aria-label="Close">×</button><div class="ua-logo ua-modal-logo">UNO <i>Aviator</i></div><h2 data-ua-auth-title>Welcome back</h2><p>Sign in to your demo account or create one.</p><form data-ua-auth-form><label>Display name <input type="text" name="displayName" autocomplete="nickname" minlength="2" maxlength="40" placeholder="Only needed for registration"></label><label>Email address <input type="email" name="email" autocomplete="email" required></label><label>Password <input type="password" name="password" autocomplete="current-password" minlength="10" required></label><button class="ua-bet-button" type="submit" data-ua-auth-submit>Login</button></form><button class="ua-link-button" data-ua-auth-switch>Create a demo account instead</button><p class="ua-panel-message" data-ua-auth-message></p></div></div>
          <div class="ua-toast" data-ua-toast role="status" aria-live="polite"></div>
        </div>
        <?php return ob_get_clean();
    }
}
new UNO_Aviator_Demo_Plugin();

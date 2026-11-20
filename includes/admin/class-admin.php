<?php
/**
 * Bacheca: menu, schede e azioni.
 *
 * @package GRSDigital\Pop
 */

namespace GRSDigital\Pop\Admin;

use GRSDigital\Pop\Frontend;
use GRSDigital\Pop\I18n_Bridge;
use GRSDigital\Pop\Index;
use GRSDigital\Pop\Maintenance;
use GRSDigital\Pop\Post_Type;
use GRSDigital\Pop\Presets;
use GRSDigital\Pop\Privacy;
use GRSDigital\Pop\Renderer;
use GRSDigital\Pop\Schema;
use GRSDigital\Pop\Settings;
use GRSDigital\Pop\Stats;

defined( 'ABSPATH' ) || exit;

/**
 * Voce "Pop" nel menu GRS DEVs con le schede Popup, Editor, Statistiche e Impostazioni.
 */
final class Admin {

	/**
	 * Slug della pagina.
	 */
	const SLUG = 'grs-pop';

	/**
	 * Versione di GRS Tracking che ascolta gli eventi di GRS Pop (contratto pop-tracking,
	 * implementato da GRS Tracking 1.1.0; la 1.0.0 non li ascolta).
	 */
	const TRACKING_POP_VERSION = '1.1.0';

	/**
	 * Hook della pagina.
	 *
	 * @var string
	 */
	private static $hook = '';

	/**
	 * Avvio.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'grs_devs_plugins', array( __CLASS__, 'register_in_grs_devs' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( GRS_POP_FILE ), array( __CLASS__, 'action_links' ) );
		add_action( 'admin_post_grs_pop_row', array( __CLASS__, 'handle_row_action' ) );
		add_action( 'admin_post_grs_pop_reset_stats', array( __CLASS__, 'handle_reset_stats' ) );
		add_action( 'admin_post_grs_pop_ms_new_key', array( Maintenance_Page::class, 'handle_new_key' ) );
		add_action( 'admin_post_grs_pop_ms_preset', array( Maintenance_Page::class, 'handle_preset' ) );
		add_action( 'admin_init', array( Privacy::class, 'add_policy_content' ) );
		add_action( 'admin_init', array( I18n_Bridge::class, 'maybe_register_all' ) );
	}

	/**
	 * URL di una scheda.
	 *
	 * @param string               $tab  Scheda.
	 * @param array<string, mixed> $args Altri parametri.
	 * @return string
	 */
	public static function url( $tab = 'popups', array $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::SLUG ), 'popups' === $tab ? array() : array( 'tab' => $tab ), $args ), admin_url( 'admin.php' ) );
	}

	/**
	 * Registrazione nella Panoramica GRS DEVs.
	 *
	 * Le integrazioni hanno id, descrizione e versioni identici in tutti i plugin coinvolti
	 * (dev/ecosistema.md, "Testi delle dichiarazioni in Panoramica"): testo fisso italiano.
	 *
	 * @param array<string, array<string, mixed>> $plugins Plugin registrati.
	 * @return array<string, array<string, mixed>>
	 */
	public static function register_in_grs_devs( $plugins ) {
		$plugins['grs-pop'] = array(
			'name'         => 'GRS Pop',
			'version'      => GRS_POP_VERSION,
			'description'  => __( 'Popups, slide-ins, bars, welcome mats, inline boxes and floating tabs.', 'grs-pop' ),
			'url'          => self::url(),
			'integrations' => array(
				'pop-forms'          => array(
					'label'    => 'I popup mostrano i form di GRS Forms',
					'requires' => array(
						'grs-pop'   => array(
							'name'    => 'GRS Pop',
							'version' => '1.0.0',
						),
						'grs-forms' => array(
							'name'    => 'GRS Forms',
							'version' => '1.0.0',
						),
					),
				),
				'pop-forms-audience' => array(
					'label'    => 'I popup si mostrano o si nascondono in base ai form che il visitatore ha già compilato',
					'requires' => array(
						'grs-pop'   => array(
							'name'    => 'GRS Pop',
							'version' => '1.1.0',
						),
						'grs-forms' => array(
							'name'    => 'GRS Forms',
							'version' => '1.0.0',
						),
					),
				),
				'pop-tracking'       => array(
					'label'    => 'Visualizzazioni, clic e conversioni dei popup misurati in GA4, senza contare due volte i contatti dei form',
					'requires' => array(
						'grs-pop'      => array(
							'name'    => 'GRS Pop',
							'version' => '1.0.0',
						),
						'grs-tracking' => array(
							'name'    => 'GRS Tracking',
							'version' => self::TRACKING_POP_VERSION,
						),
					),
				),
			),
		);
		return $plugins;
	}

	/**
	 * Voce di sottomenu.
	 *
	 * @return void
	 */
	public static function add_menu() {
		self::$hook = (string) add_submenu_page(
			'grs-devs',
			'GRS Pop',
			'Pop',
			'grs_pop_manage_popups',
			self::SLUG,
			array( __CLASS__, 'render' )
		);
		if ( self::$hook ) {
			add_action( 'load-' . self::$hook, array( __CLASS__, 'on_load' ) );
		}
	}

	/**
	 * Link nella riga del plugin.
	 *
	 * @param string[] $links Link.
	 * @return string[]
	 */
	public static function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( self::url() ) . '">' . esc_html__( 'Popups', 'grs-pop' ) . '</a>' );
		return $links;
	}

	/**
	 * Scheda corrente (solo quelle consentite all'utente).
	 *
	 * @return string
	 */
	public static function current_tab() {
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'popups'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigazione.
		$tabs = self::tabs();
		if ( 'edit' === $tab ) {
			return 'edit';
		}
		return isset( $tabs[ $tab ] ) ? $tab : 'popups';
	}

	/**
	 * Schede visibili.
	 *
	 * @return array<string, string>
	 */
	private static function tabs() {
		$tabs = array( 'popups' => __( 'Popups', 'grs-pop' ) );
		if ( current_user_can( 'grs_pop_view_stats' ) ) {
			$tabs['stats'] = __( 'Statistics', 'grs-pop' );
		}
		if ( current_user_can( 'manage_options' ) ) {
			$tabs['maintenance'] = __( 'Coming soon', 'grs-pop' );
			$tabs['settings']    = __( 'Settings', 'grs-pop' );
		}
		return $tabs;
	}

	/**
	 * Caricamento della pagina: azioni di gruppo della tabella.
	 *
	 * @return void
	 */
	public static function on_load() {
		if ( 'popups' !== self::current_tab() ) {
			return;
		}
		$table  = new Popups_Table();
		$action = $table->current_action();
		if ( ! $action || ! current_user_can( 'grs_pop_manage_popups' ) ) {
			return;
		}
		check_admin_referer( 'bulk-popups' );
		$ids = isset( $_GET['popup'] ) ? array_map( 'absint', (array) wp_unslash( $_GET['popup'] ) ) : array();
		$ids = array_filter( $ids );
		$n   = 0;
		foreach ( $ids as $id ) {
			if ( Post_Type::NAME !== get_post_type( $id ) ) {
				continue;
			}
			if ( 'activate' === $action || 'deactivate' === $action ) {
				wp_update_post(
					array(
						'ID'          => $id,
						'post_status' => 'activate' === $action ? 'publish' : 'draft',
					)
				);
				++$n;
			} elseif ( 'delete' === $action ) {
				self::delete_popup( $id );
				++$n;
			}
		}
		Index::rebuild();
		wp_safe_redirect(
			self::url(
				'popups',
				array(
					'grs_msg' => sanitize_key( $action ),
					'grs_n'   => $n,
				)
			)
		);
		exit;
	}

	/**
	 * Elimina un popup e le sue statistiche.
	 *
	 * @param int $id ID.
	 * @return void
	 */
	private static function delete_popup( $id ) {
		wp_delete_post( $id, true );
		Stats::delete_popup( $id );
	}

	/**
	 * Azioni di riga (attiva/disattiva, duplica, elimina).
	 *
	 * @return void
	 */
	public static function handle_row_action() {
		$id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
		$do = isset( $_GET['do'] ) ? sanitize_key( wp_unslash( $_GET['do'] ) ) : '';
		if ( ! current_user_can( 'grs_pop_manage_popups' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'grs-pop' ), 403 );
		}
		check_admin_referer( 'grs_pop_row_' . $id );
		$post = get_post( $id );
		if ( ! $post || Post_Type::NAME !== $post->post_type ) {
			wp_die( esc_html__( 'Popup not found.', 'grs-pop' ), 404 );
		}
		$msg = '';
		switch ( $do ) {
			case 'toggle':
				wp_update_post(
					array(
						'ID'          => $id,
						'post_status' => 'publish' === $post->post_status ? 'draft' : 'publish',
					)
				);
				$msg = 'publish' === $post->post_status ? 'deactivate' : 'activate';
				break;
			case 'duplicate':
				$new_id = wp_insert_post(
					wp_slash(
						array(
							'post_type'   => Post_Type::NAME,
							/* translators: %s: popup name. */
							'post_title'  => sprintf( __( '%s (copy)', 'grs-pop' ), $post->post_title ),
							'post_status' => 'draft',
						)
					)
				);
				if ( $new_id && ! is_wp_error( $new_id ) ) {
					$config = Post_Type::save_config( (int) $new_id, Post_Type::get_config( $id ) );
					I18n_Bridge::register_popup( (int) $new_id, $config );
				}
				$msg = 'duplicate';
				break;
			case 'delete':
				self::delete_popup( $id );
				$msg = 'delete';
				break;
		}
		Index::rebuild();
		wp_safe_redirect(
			self::url(
				'popups',
				array(
					'grs_msg' => $msg,
					'grs_n'   => 1,
				)
			)
		);
		exit;
	}

	/**
	 * Azzera le statistiche di un popup.
	 *
	 * @return void
	 */
	public static function handle_reset_stats() {
		$id = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
		if ( ! current_user_can( 'grs_pop_manage_popups' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'grs-pop' ), 403 );
		}
		check_admin_referer( 'grs_pop_reset_stats_' . $id );
		Stats::reset_popup( $id );
		wp_safe_redirect(
			self::url(
				'stats',
				array(
					'popup'   => $id,
					'grs_msg' => 'reset',
				)
			)
		);
		exit;
	}

	/**
	 * Asset della bacheca.
	 *
	 * @param string $hook_suffix Pagina.
	 * @return void
	 */
	public static function enqueue( $hook_suffix ) {
		if ( self::$hook !== $hook_suffix ) {
			return;
		}
		$deps = wp_style_is( 'grs-devs-ui', 'enqueued' ) ? array( 'grs-devs-ui' ) : array();
		wp_enqueue_style( 'grs-pop-admin', GRS_POP_URL . 'assets/css/admin.css', $deps, GRS_POP_VERSION );

		if ( 'maintenance' === self::current_tab() ) {
			Maintenance_Page::enqueue();
			return;
		}
		if ( 'edit' !== self::current_tab() ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'grs-pop', GRS_POP_URL . 'assets/css/grs-pop.css', array(), GRS_POP_VERSION );
		wp_add_inline_style( 'grs-pop', Renderer::palette_css() );
		wp_enqueue_style( 'grs-pop-builder', GRS_POP_URL . 'assets/css/builder.css', array( 'grs-pop', 'grs-pop-admin' ), GRS_POP_VERSION );
		wp_enqueue_script( 'grs-pop-builder', GRS_POP_URL . 'assets/js/builder.js', array( 'wp-element', 'wp-api-fetch', 'wp-i18n' ), GRS_POP_VERSION, true );
		wp_set_script_translations( 'grs-pop-builder', 'grs-pop', GRS_POP_PATH . 'languages' );
		wp_add_inline_script( 'grs-pop-builder', 'window.grsPopBuilder = ' . wp_json_encode( self::builder_data() ) . ';', 'before' );
	}

	/**
	 * Dati per il builder.
	 *
	 * @return array<string, mixed>
	 */
	private static function builder_data() {
		$id    = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigazione.
		$popup = null;
		$post  = $id ? get_post( $id ) : null;
		if ( $post && Post_Type::NAME === $post->post_type ) {
			$popup = array(
				'id'          => (int) $post->ID,
				'title'       => (string) $post->post_title,
				'status'      => 'publish' === $post->post_status ? 'publish' : 'draft',
				'config'      => Post_Type::get_config( $post->ID ),
				'preview_url' => Frontend::preview_url( $post->ID ),
			);
		}

		$post_types = array();
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $type ) {
			if ( 'attachment' === $type->name ) {
				continue;
			}
			$post_types[] = array(
				'value'   => $type->name,
				'label'   => $type->labels->name,
				'archive' => (bool) $type->has_archive || 'post' === $type->name,
			);
		}
		$taxonomies = array();
		foreach ( get_taxonomies( array( 'public' => true ), 'objects' ) as $tax ) {
			if ( 'post_format' === $tax->name ) {
				continue;
			}
			$taxonomies[] = array(
				'value' => $tax->name,
				'label' => $tax->labels->name,
			);
		}
		$roles = array();
		foreach ( wp_roles()->get_names() as $role => $name ) {
			$roles[] = array(
				'value' => $role,
				'label' => translate_user_role( $name ),
			);
		}

		$settings = Settings::get();
		return array(
			'popup'           => $popup,
			'defaults'        => Schema::new_popup_defaults(),
			'sectionDefaults' => array_combine(
				Schema::SECTION_TYPES,
				array_map( array( Schema::class, 'section_defaults' ), Schema::SECTION_TYPES )
			),
			'presets'         => Presets::all(),
			'categories'      => Presets::categories(),
			'palette'         => $settings['palette'],
			'paletteLabels'   => Settings::palette_labels(),
			'bp'              => array(
				'm' => (int) $settings['bp_mobile'],
				't' => (int) $settings['bp_tablet'],
			),
			'postTypes'       => $post_types,
			'taxonomies'      => $taxonomies,
			'roles'           => $roles,
			'languages'       => self::languages(),
			// Elenco dei form letto nel builder dalla REST pubblica di GRS Forms (GET grs-forms/v1/forms).
			'formsActive'     => defined( 'GRS_FORMS_VERSION' ),
			'formsUrl'        => admin_url( 'admin.php?page=grs-forms' ),
			'listUrl'         => self::url(),
			'editUrl'         => self::url( 'edit' ),
			'settingsUrl'     => current_user_can( 'manage_options' ) ? self::url( 'settings' ) : '',
			'siteName'        => get_bloginfo( 'name' ),
			'canShortcode'    => current_user_can( 'edit_posts' ),
		);
	}

	/**
	 * Lingue del sito (WPML o Polylang), vuoto se monolingua.
	 *
	 * @return array<int, array<string, string>>
	 */
	private static function languages() {
		$out = array();
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$langs = apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 0 ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- filtro di WPML (anche Polylang lo supporta).
			if ( is_array( $langs ) ) {
				foreach ( $langs as $code => $lang ) {
					$out[] = array(
						'value' => (string) $code,
						'label' => isset( $lang['native_name'] ) ? (string) $lang['native_name'] : (string) $code,
					);
				}
			}
		} elseif ( function_exists( 'pll_languages_list' ) ) {
			$codes = pll_languages_list( array( 'fields' => 'slug' ) );
			$names = pll_languages_list( array( 'fields' => 'name' ) );
			foreach ( (array) $codes as $n => $code ) {
				$out[] = array(
					'value' => (string) $code,
					'label' => isset( $names[ $n ] ) ? (string) $names[ $n ] : (string) $code,
				);
			}
		}
		return $out;
	}

	/**
	 * Pagina.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! current_user_can( 'grs_pop_manage_popups' ) ) {
			wp_die( esc_html__( 'You are not allowed to access this page.', 'grs-pop' ) );
		}
		$tab = self::current_tab();

		if ( 'edit' === $tab ) {
			echo '<div class="wrap grs-ui grs-pop-admin grs-pop-admin--edit">';
			echo '<div id="grs-pop-builder" class="grs-pop-builder"><p class="grs-pop-builder__loading">' . esc_html__( 'Loading the editor…', 'grs-pop' ) . '</p></div>';
			echo '<noscript><div class="notice notice-error inline"><p>' . esc_html__( 'The editor requires JavaScript.', 'grs-pop' ) . '</p></div></noscript>';
			echo '</div>';
			return;
		}

		echo '<div class="wrap grs-ui grs-pop-admin">';
		$meta = '<span class="grs-ui-badge grs-ui-badge--ok">' . esc_html__( 'Active', 'grs-pop' ) . '</span> <span class="grs-ui-version">v' . esc_html( GRS_POP_VERSION ) . '</span>';
		if ( method_exists( 'GRS_Devs_Menu', 'render_header' ) ) {
			\GRS_Devs_Menu::render_header( 'GRS Pop', __( 'Popups, slide-ins, bars, welcome mats, inline boxes and floating tabs.', 'grs-pop' ), $meta );
		} else {
			echo '<h1>GRS Pop</h1><hr class="wp-header-end">';
		}

		echo '<nav class="nav-tab-wrapper" aria-label="' . esc_attr__( 'Sections', 'grs-pop' ) . '">';
		foreach ( self::tabs() as $key => $label ) {
			printf(
				'<a href="%1$s" class="nav-tab%2$s"%3$s>%4$s</a>',
				esc_url( self::url( $key ) ),
				$key === $tab ? ' nav-tab-active' : '',
				$key === $tab ? ' aria-current="page"' : '',
				esc_html( $label )
			);
		}
		echo '</nav>';

		self::notices();

		switch ( $tab ) {
			case 'stats':
				Stats_Page::render();
				break;
			case 'settings':
				Settings_Page::render();
				break;
			case 'maintenance':
				Maintenance_Page::render();
				break;
			default:
				self::render_list();
		}

		if ( method_exists( 'GRS_Devs_Menu', 'render_footer' ) ) {
			\GRS_Devs_Menu::render_footer();
		}
		echo '</div>';
	}

	/**
	 * Avvisi dopo le azioni.
	 *
	 * @return void
	 */
	private static function notices() {
		$msg = isset( $_GET['grs_msg'] ) ? sanitize_key( wp_unslash( $_GET['grs_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo messaggio.
		$n   = isset( $_GET['grs_n'] ) ? absint( wp_unslash( $_GET['grs_n'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo messaggio.
		$map = array(
			/* translators: %d: number of popups. */
			'activate'   => _n( '%d popup activated.', '%d popups activated.', $n, 'grs-pop' ),
			/* translators: %d: number of popups. */
			'deactivate' => _n( '%d popup deactivated.', '%d popups deactivated.', $n, 'grs-pop' ),
			/* translators: %d: number of popups. */
			'delete'     => _n( '%d popup deleted.', '%d popups deleted.', $n, 'grs-pop' ),
			'duplicate'  => __( 'Popup duplicated as an inactive copy.', 'grs-pop' ),
			'reset'      => __( 'Statistics reset.', 'grs-pop' ),
			'newkey'     => __( 'New secret link created: the previous one no longer works.', 'grs-pop' ),
		);
		if ( isset( $map[ $msg ] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( sprintf( $map[ $msg ], $n ) ) . '</p></div>';
		}
		if ( 'preset' === $msg ) {
			$preset_id = isset( $_GET['grs_preset'] ) ? sanitize_key( wp_unslash( $_GET['grs_preset'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo messaggio.
			$presets   = Maintenance_Page::presets();
			if ( isset( $presets[ $preset_id ] ) ) {
				if ( 'off' !== Maintenance::mode() ) {
					/* translators: %s: template name. */
					$text = __( 'Template "%s" applied and saved: visitors already see the new page. Check the texts and the date in the preview.', 'grs-pop' );
				} elseif ( 'maintenance' === $presets[ $preset_id ]['mode'] ) {
					/* translators: %s: template name. */
					$text = __( 'Template "%s" applied: check the texts and the date in the preview, then choose the Maintenance mode and save.', 'grs-pop' );
				} else {
					/* translators: %s: template name. */
					$text = __( 'Template "%s" applied: check the texts and the date in the preview, then choose the Coming soon mode and save.', 'grs-pop' );
				}
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( sprintf( $text, $presets[ $preset_id ]['name'] ) ) . '</p></div>';
			}
		}
		if ( 'settings' === self::current_tab() ) {
			settings_errors();
		}
	}

	/**
	 * Scheda Popup.
	 *
	 * @return void
	 */
	private static function render_list() {
		$table = new Popups_Table();
		$table->prepare_items();

		echo '<div class="grs-ui-card grs-pop-list">';
		echo '<div class="grs-pop-list__head"><h2>' . esc_html__( 'Your popups', 'grs-pop' ) . '</h2>';
		echo '<a class="button button-primary" href="' . esc_url( self::url( 'edit' ) ) . '">' . esc_html__( 'Create popup', 'grs-pop' ) . '</a></div>';

		if ( ! $table->has_items() && '' === $table->search_term() ) {
			echo '<div class="grs-pop-empty"><p><strong>' . esc_html__( 'No popups yet.', 'grs-pop' ) . '</strong></p>';
			echo '<p>' . esc_html__( 'Start from one of the ready-made templates: newsletter, discount, bar, slide-in, welcome mat and more. You can customize everything afterwards.', 'grs-pop' ) . '</p>';
			echo '<p><a class="button button-primary" href="' . esc_url( self::url( 'edit' ) ) . '">' . esc_html__( 'Choose a template', 'grs-pop' ) . '</a></p></div>';
		} else {
			echo '<form method="get">';
			echo '<input type="hidden" name="page" value="' . esc_attr( self::SLUG ) . '">';
			$table->search_box( __( 'Search popups', 'grs-pop' ), 'grs-pop-search' );
			$table->display();
			echo '</form>';
		}
		echo '</div>';

		self::render_help();
	}

	/**
	 * Suggerimenti sotto l'elenco.
	 *
	 * @return void
	 */
	private static function render_help() {
		echo '<div class="grs-ui-card grs-pop-help"><h2>' . esc_html__( 'How to use', 'grs-pop' ) . '</h2><ul>';
		echo '<li>' . wp_kses(
			sprintf(
				/* translators: 1: example link markup, 2: example CSS class. */
				__( 'Open a popup from any link or button: use the address %1$s or the CSS class %2$s (replace 12 with the popup ID).', 'grs-pop' ),
				'<code>#grs-pop-12</code>',
				'<code>grs-pop-open-12</code>'
			),
			array( 'code' => array() )
		) . '</li>';
		echo '<li>' . wp_kses(
			sprintf(
				/* translators: 1: shortcode for inline boxes, 2: shortcode for links. */
				__( 'Inline boxes: shortcode %1$s or the "GRS Pop inline" block. Link that opens a popup anywhere: %2$s.', 'grs-pop' ),
				'<code>[grs_pop id="12"]</code>',
				'<code>[grs_pop id="12"]' . esc_html__( 'Open', 'grs-pop' ) . '[/grs_pop]</code>'
			),
			array( 'code' => array() )
		) . '</li>';
		echo '<li>' . esc_html__( 'Forms inside popups come from GRS Forms: leads, anti-spam and conversion tracking work as usual.', 'grs-pop' ) . '</li>';
		echo '</ul></div>';
	}
}

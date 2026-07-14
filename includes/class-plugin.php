<?php
/**
 * Zentrale Plugin-Klasse.
 *
 * @package VerwaltungBoote
 */

namespace Verwaltung_Boote;

defined( 'ABSPATH' ) || exit;

/**
 * Initialisiert das Plugin und registriert seine WordPress-Hooks.
 */
final class Plugin {

	/**
	 * Singleton-Instanz.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Liefert die einzige Plugin-Instanz.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Registriert alle Hooks des Plugins.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'init', array( $this, 'register_content_types' ) );
		add_action( 'init', array( $this, 'ensure_boat_identifiers' ), 20 );
		add_shortcode( 'verwaltung_boote_liste', array( $this, 'render_boat_list' ) );
		add_shortcode( 'verwaltung_boote_bootswart', array( $this, 'render_bootswart_dashboard' ) );
		add_shortcode( 'verwaltung_boote_bootswart_boote', array( $this, 'render_bootswart_boats' ) );
		add_shortcode( 'verwaltung_boote_bootswart_reservierungen', array( $this, 'render_bootswart_reservations' ) );
		add_shortcode( 'verwaltung_boote_bootswart_nutzungen', array( $this, 'render_bootswart_usages' ) );
		add_shortcode( 'verwaltung_boote_bootswart_schaeden', array( $this, 'render_bootswart_damages' ) );
		add_shortcode( 'verwaltung_boote_bootswart_nutzer', array( $this, 'render_bootswart_users' ) );
		add_shortcode( 'verwaltung_boote_bootswart_qr', array( $this, 'render_bootswart_qr_codes' ) );
		add_shortcode( 'verwaltung_boote_boot_einstieg', array( $this, 'render_boat_qr_entry' ) );
		add_action( 'init', array( $this, 'ensure_bootswart_pages' ), 30 );
		add_action( 'init', array( $this, 'ensure_qr_entry_page' ), 31 );
		add_action( 'template_redirect', array( $this, 'protect_bootswart_page' ) );
		add_filter( 'wp_get_nav_menu_items', array( $this, 'filter_bootswart_menu_item' ) );
		add_action( 'pre_get_posts', array( $this, 'hide_bootswart_page' ) );
		add_action( 'add_meta_boxes_vb_boot', array( $this, 'add_berth_meta_box' ) );
		add_action( 'add_meta_boxes_vb_boot', array( $this, 'add_type_meta_box' ) );
		add_action( 'add_meta_boxes_vb_boot', array( $this, 'add_boat_identifier_meta_box' ) );
		add_action( 'save_post_vb_boot', array( $this, 'save_berth' ) );
		add_action( 'save_post_vb_boot', array( $this, 'save_type' ) );
		add_action( 'save_post_vb_boot', array( $this, 'save_boat_identifier' ) );
		add_filter( 'manage_vb_boot_posts_columns', array( $this, 'boat_columns' ) );
		add_action( 'manage_vb_boot_posts_custom_column', array( $this, 'render_boat_column' ), 10, 2 );
		add_action( 'admin_post_verwaltung_boote_start', array( $this, 'start_usage' ) );
		add_action( 'admin_post_verwaltung_boote_end', array( $this, 'end_usage' ) );
		add_action( 'admin_post_verwaltung_boote_reservieren', array( $this, 'create_reservation' ) );
		add_action( 'admin_post_verwaltung_boote_reservierung_loeschen', array( $this, 'delete_reservation' ) );
		add_action( 'admin_post_verwaltung_boote_admin_nutzung_beenden', array( $this, 'admin_end_usage' ) );
		add_action( 'admin_post_verwaltung_boote_schaden_beheben', array( $this, 'resolve_damage' ) );
		add_action( 'admin_post_verwaltung_boote_admin_reservierung_loeschen', array( $this, 'admin_delete_reservation' ) );
		add_filter( 'post_row_actions', array( $this, 'admin_row_actions' ), 10, 2 );
		add_filter( 'bulk_actions-edit-vb_reservierung', array( $this, 'remove_reservation_delete_actions' ) );
		add_action( 'admin_notices', array( $this, 'render_admin_notice' ) );
		add_filter( 'manage_vb_logbuch_posts_columns', array( $this, 'logbook_columns' ) );
		add_action( 'manage_vb_logbuch_posts_custom_column', array( $this, 'render_logbook_column' ), 10, 2 );
		add_action( 'add_meta_boxes_vb_logbuch', array( $this, 'add_logbook_meta_box' ) );
		add_filter( 'manage_vb_schaden_posts_columns', array( $this, 'damage_columns' ) );
		add_action( 'manage_vb_schaden_posts_custom_column', array( $this, 'render_damage_column' ), 10, 2 );
		add_action( 'add_meta_boxes_vb_schaden', array( $this, 'add_damage_meta_box' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_filter( 'manage_vb_reservierung_posts_columns', array( $this, 'reservation_columns' ) );
		add_action( 'manage_vb_reservierung_posts_custom_column', array( $this, 'render_reservation_column' ), 10, 2 );
	}

	/**
	 * Registriert Boote, Bootstypen und Liegeplaetze.
	 *
	 * @return void
	 */
	public function register_content_types() {
		$capabilities = array(
			'edit_post'              => 'edit_boot',
			'read_post'              => 'read_boot',
			'delete_post'            => 'delete_boot',
			'edit_posts'             => 'edit_boote',
			'edit_others_posts'      => 'edit_others_boote',
			'publish_posts'          => 'publish_boote',
			'read_private_posts'     => 'read_private_boote',
			'delete_posts'           => 'delete_boote',
			'delete_private_posts'   => 'delete_private_boote',
			'delete_published_posts' => 'delete_published_boote',
			'delete_others_posts'    => 'delete_others_boote',
			'edit_private_posts'     => 'edit_private_boote',
			'edit_published_posts'   => 'edit_published_boote',
			'create_posts'           => 'edit_boote',
		);

		register_post_type(
			'vb_boot',
			array(
				'labels' => array(
					'name'          => __( 'Boote', 'verwaltung-boote' ),
					'singular_name' => __( 'Boot', 'verwaltung-boote' ),
					'add_new_item'  => __( 'Boot hinzufügen', 'verwaltung-boote' ),
					'edit_item'     => __( 'Boot bearbeiten', 'verwaltung-boote' ),
					'menu_name'     => __( 'Boote', 'verwaltung-boote' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => true,
				'menu_icon'    => 'dashicons-sos',
				'supports'     => array( 'title', 'editor', 'thumbnail' ),
				'capabilities' => $capabilities,
				'map_meta_cap' => true,
			)
		);

		register_post_type(
			'vb_logbuch',
			array(
				'labels' => array(
					'name'          => __( 'Logbuch', 'verwaltung-boote' ),
					'singular_name' => __( 'Logbucheintrag', 'verwaltung-boote' ),
					'menu_name'     => __( 'Logbuch', 'verwaltung-boote' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => 'edit.php?post_type=vb_boot',
				'supports'            => array( 'title', 'author' ),
				'capability_type'     => array( 'boot', 'boote' ),
				'capabilities'        => array_merge( $capabilities, array( 'create_posts' => 'do_not_allow' ) ),
				'map_meta_cap'        => true,
			)
		);

		register_post_type(
			'vb_schaden',
			array(
				'labels' => array(
					'name'          => __( 'Bootsschäden', 'verwaltung-boote' ),
					'singular_name' => __( 'Bootsschaden', 'verwaltung-boote' ),
					'menu_name'     => __( 'Bootsschäden', 'verwaltung-boote' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'edit.php?post_type=vb_boot',
				'supports'        => array( 'title', 'editor', 'author' ),
				'capability_type' => array( 'boot', 'boote' ),
				'capabilities'    => array_merge( $capabilities, array( 'create_posts' => 'do_not_allow' ) ),
				'map_meta_cap'    => true,
			)
		);

		register_post_type(
			'vb_reservierung',
			array(
				'labels' => array(
					'name'          => __( 'Reservierungen', 'verwaltung-boote' ),
					'singular_name' => __( 'Reservierung', 'verwaltung-boote' ),
					'menu_name'     => __( 'Reservierungen', 'verwaltung-boote' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'edit.php?post_type=vb_boot',
				'supports'        => array( 'title', 'author' ),
				'capability_type' => array( 'boot', 'boote' ),
				'capabilities'    => array_merge( $capabilities, array( 'create_posts' => 'do_not_allow' ) ),
				'map_meta_cap'    => true,
			)
		);

		$term_capabilities = array(
			'manage_terms' => 'edit_boote',
			'edit_terms'   => 'edit_boote',
			'delete_terms' => 'edit_boote',
			'assign_terms' => 'edit_boote',
		);

		register_taxonomy(
			'vb_bootstyp',
			'vb_boot',
			array(
				'labels'            => array(
					'name'          => __( 'Bootstypen', 'verwaltung-boote' ),
					'singular_name' => __( 'Bootstyp', 'verwaltung-boote' ),
				),
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'hierarchical'      => true,
				'meta_box_cb'       => false,
				'capabilities'      => $term_capabilities,
			)
		);

	}

	/**
	 * Fuegt das einzelne Liegeplatzfeld zur Bootsmaske hinzu.
	 *
	 * @return void
	 */
	public function add_berth_meta_box() {
		add_meta_box(
			'verwaltung-boote-liegeplatz',
			__( 'Liegeplatz', 'verwaltung-boote' ),
			array( $this, 'render_berth_meta_box' ),
			'vb_boot',
			'side',
			'default'
		);
	}

	/**
	 * Fuegt die Einzelauswahl fuer den Bootstyp hinzu.
	 *
	 * @return void
	 */
	public function add_type_meta_box() {
		add_meta_box(
			'verwaltung-boote-bootstyp',
			__( 'Bootstyp', 'verwaltung-boote' ),
			array( $this, 'render_type_meta_box' ),
			'vb_boot',
			'side',
			'default'
		);
	}

	/**
	 * Fuegt das eindeutige Feld Boots-ID zur Bootsmaske hinzu.
	 *
	 * @return void
	 */
	public function add_boat_identifier_meta_box() {
		add_meta_box(
			'verwaltung-boote-boots-id',
			__( 'Boots-ID', 'verwaltung-boote' ),
			array( $this, 'render_boat_identifier_meta_box' ),
			'vb_boot',
			'side',
			'default'
		);
	}

	/**
	 * Zeigt ein Dropdown fuer genau einen Bootstyp.
	 *
	 * @param \WP_Post $post Aktuelles Boot.
	 * @return void
	 */
	public function render_type_meta_box( $post ) {
		wp_nonce_field( 'verwaltung_boote_save_type', 'verwaltung_boote_type_nonce' );
		$assigned = wp_get_object_terms( $post->ID, 'vb_bootstyp', array( 'fields' => 'ids' ) );
		$selected = ( ! is_wp_error( $assigned ) && ! empty( $assigned ) ) ? (int) reset( $assigned ) : 0;

		wp_dropdown_categories(
			array(
				'taxonomy'     => 'vb_bootstyp',
				'name'         => 'verwaltung_boote_bootstyp',
				'id'           => 'verwaltung-boote-bootstyp-auswahl',
				'class'        => 'widefat',
				'hide_empty'   => false,
				'hierarchical' => false,
				'orderby'      => 'name',
				'selected'     => $selected,
				'value_field'  => 'term_id',
				'required'     => true,
			)
		);
	}

	/**
	 * Zeigt ein Dropdown fuer genau einen Liegeplatz.
	 *
	 * @param \WP_Post $post Aktuelles Boot.
	 * @return void
	 */
	public function render_berth_meta_box( $post ) {
		wp_nonce_field( 'verwaltung_boote_save_berth', 'verwaltung_boote_berth_nonce' );
		$value = (string) get_post_meta( $post->ID, '_vb_liegeplatz', true );
		?>
		<label for="verwaltung-boote-liegeplatz-auswahl"><?php esc_html_e( 'Liegeplatz', 'verwaltung-boote' ); ?></label>
		<input class="widefat" id="verwaltung-boote-liegeplatz-auswahl" name="verwaltung_boote_liegeplatz" type="text" value="<?php echo esc_attr( $value ); ?>">
		<p class="description"><?php esc_html_e( 'Freitext, zum Beispiel Steg A – Platz 12.', 'verwaltung-boote' ); ?></p>
		<?php
	}

	/**
	 * Zeigt die dauerhafte Kennung, die in QR-Code-Links verwendet wird.
	 *
	 * @param \WP_Post $post Aktuelles Boot.
	 * @return void
	 */
	public function render_boat_identifier_meta_box( $post ) {
		wp_nonce_field( 'verwaltung_boote_save_boat_identifier', 'verwaltung_boote_boat_identifier_nonce' );
		$value = (string) get_post_meta( $post->ID, '_vb_boots_id', true );
		?>
		<label for="verwaltung-boote-boots-id"><?php esc_html_e( 'Boots-ID', 'verwaltung-boote' ); ?></label>
		<input class="widefat" id="verwaltung-boote-boots-id" name="verwaltung_boote_boots_id" type="text" value="<?php echo esc_attr( $value ); ?>" required>
		<p class="description"><?php esc_html_e( 'Eindeutige, dauerhafte Kennung fuer QR-Codes nur aus Kleinbuchstaben und Ziffern, z. B. hboot1. Sie sollte nach dem Drucken nicht mehr geaendert werden.', 'verwaltung-boote' ); ?></p>
		<?php
	}

	/**
	 * Speichert genau einen ausgewaehlten Liegeplatz.
	 *
	 * @param int $post_id Boot-ID.
	 * @return void
	 */
	public function save_berth( $post_id ) {
		if (
			! isset( $_POST['verwaltung_boote_berth_nonce'] ) ||
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['verwaltung_boote_berth_nonce'] ) ),
				'verwaltung_boote_save_berth'
			) ||
			( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
			! current_user_can( 'edit_post', $post_id ) ||
			! isset( $_POST['verwaltung_boote_liegeplatz'] )
		) {
			return;
		}

		update_post_meta(
			$post_id,
			'_vb_liegeplatz',
			sanitize_text_field( wp_unslash( $_POST['verwaltung_boote_liegeplatz'] ) )
		);
	}

	/**
	 * Speichert eine eindeutige, URL-taugliche Boots-ID.
	 *
	 * @param int $post_id Boot-ID.
	 * @return void
	 */
	public function save_boat_identifier( $post_id ) {
		if (
			! isset( $_POST['verwaltung_boote_boat_identifier_nonce'] ) ||
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['verwaltung_boote_boat_identifier_nonce'] ) ),
				'verwaltung_boote_save_boat_identifier'
			) ||
			( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
			! current_user_can( 'edit_post', $post_id ) ||
			! isset( $_POST['verwaltung_boote_boots_id'] )
		) {
			return;
		}

		$identifier = $this->sanitize_boat_identifier( wp_unslash( $_POST['verwaltung_boote_boots_id'] ) );
		if ( '' === $identifier ) {
			$identifier = 'boot' . absint( $post_id );
		}

		update_post_meta( $post_id, '_vb_boots_id', $this->get_unique_boat_identifier( $identifier, $post_id ) );
	}

	/**
	 * Speichert genau einen gueltigen Bootstyp.
	 *
	 * @param int $post_id Boot-ID.
	 * @return void
	 */
	public function save_type( $post_id ) {
		if (
			! isset( $_POST['verwaltung_boote_type_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['verwaltung_boote_type_nonce'] ) ), 'verwaltung_boote_save_type' ) ||
			( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
			! current_user_can( 'edit_post', $post_id ) ||
			! isset( $_POST['verwaltung_boote_bootstyp'] )
		) {
			return;
		}

		$term_id = absint( wp_unslash( $_POST['verwaltung_boote_bootstyp'] ) );
		$term    = get_term( $term_id, 'vb_bootstyp' );
		if ( ! $term || is_wp_error( $term ) ) {
			return;
		}

		wp_set_object_terms( $post_id, array( $term_id ), 'vb_bootstyp', false );
	}

	/**
	 * Fuegt die Freitext-Spalte Liegeplatz zur Bootsverwaltung hinzu.
	 *
	 * @param array<string,string> $columns Standardspalten.
	 * @return array<string,string>
	 */
	public function boat_columns( $columns ) {
		$columns['vb_boots_id']   = __( 'Boots-ID', 'verwaltung-boote' );
		$columns['vb_liegeplatz'] = __( 'Liegeplatz', 'verwaltung-boote' );
		return $columns;
	}

	/**
	 * Gibt den Freitext-Liegeplatz in der Bootsverwaltung aus.
	 *
	 * @param string $column  Spaltenname.
	 * @param int    $post_id Boot-ID.
	 * @return void
	 */
	public function render_boat_column( $column, $post_id ) {
		if ( 'vb_boots_id' === $column ) {
			echo esc_html( $this->get_boat_identifier( $post_id ) );
		}

		if ( 'vb_liegeplatz' === $column ) {
			echo esc_html( $this->get_boat_berth( $post_id ) );
		}
	}

	/**
	 * Prueft, ob der aktuelle Benutzer die Rolle Bootswart besitzt.
	 *
	 * @return bool
	 */
	private function is_bootswart() {
		$user = wp_get_current_user();
		return $user->exists() && in_array( 'bootswart', (array) $user->roles, true );
	}

	/**
	 * Schuetzt die eigene Bootswart-Seite vor anderen Rollen.
	 *
	 * @return void
	 */
	public function protect_bootswart_page() {
		$page_ids = $this->get_bootswart_page_ids();
		if ( empty( $page_ids ) || ! is_page( $page_ids ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}

		if ( ! $this->is_bootswart() ) {
			wp_die(
				esc_html__( 'Diese Seite ist ausschließlich für den Bootswart bestimmt.', 'verwaltung-boote' ),
				esc_html__( 'Zugriff verweigert', 'verwaltung-boote' ),
				array( 'response' => 403 )
			);
		}
	}

	/**
	 * Entfernt die Bootswart-Seite fuer andere Rollen aus klassischen Menues.
	 *
	 * @param array<int,object> $items Menueeintraege.
	 * @return array<int,object>
	 */
	public function filter_bootswart_menu_item( $items ) {
		if ( $this->is_bootswart() ) {
			return $items;
		}

		$page_ids = $this->get_bootswart_page_ids();
		return array_values(
			array_filter(
				$items,
				static function ( $item ) use ( $page_ids ) {
					return ! in_array( (int) $item->object_id, $page_ids, true );
				}
			)
		);
	}

	/**
	 * Blendet die Bootswart-Seite in oeffentlichen Abfragen anderer Rollen aus.
	 *
	 * @param \WP_Query $query Aktuelle Abfrage.
	 * @return void
	 */
	public function hide_bootswart_page( $query ) {
		if ( is_admin() || ! $query->is_main_query() || $this->is_bootswart() || $query->is_singular() ) {
			return;
		}

		$page_ids = $this->get_bootswart_page_ids();
		if ( empty( $page_ids ) ) {
			return;
		}

		$excluded   = (array) $query->get( 'post__not_in' );
		$excluded   = array_merge( $excluded, $page_ids );
		$query->set( 'post__not_in', array_unique( array_map( 'absint', $excluded ) ) );
	}

	/**
	 * Legt eine zeitlich begrenzte Bootsreservierung an.
	 *
	 * @return void
	 */
	public function create_reservation() {
		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}

		$boat_id = isset( $_POST['boot_id'] ) ? absint( wp_unslash( $_POST['boot_id'] ) ) : 0;
		check_admin_referer( 'verwaltung_boote_reservieren_' . $boat_id );

		$boat = get_post( $boat_id );
		if ( ! $boat || 'vb_boot' !== $boat->post_type || 'publish' !== $boat->post_status ) {
			$this->redirect_with_message( 'ungueltiges_boot' );
		}

		$start_input = isset( $_POST['reservierung_start'] ) ? sanitize_text_field( wp_unslash( $_POST['reservierung_start'] ) ) : '';
		$end_input   = isset( $_POST['reservierung_ende'] ) ? sanitize_text_field( wp_unslash( $_POST['reservierung_ende'] ) ) : '';
		$start       = $this->parse_local_datetime( $start_input );
		$end         = $this->parse_local_datetime( $end_input );

		if ( ! $start || ! $end || $end <= $start ) {
			$this->redirect_with_message( 'reservierung_zeit_fehlt' );
		}

		$now = new \DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) );
		if ( $start < $now ) {
			$this->redirect_with_message( 'reservierung_vergangenheit' );
		}

		$start_utc = $start->format( 'Y-m-d H:i:s' );
		$end_utc   = $end->format( 'Y-m-d H:i:s' );
		if ( $this->has_overlapping_reservation( $boat_id, $start_utc, $end_utc ) ) {
			$this->redirect_with_message( 'reservierung_ueberschneidung' );
		}

		$user           = wp_get_current_user();
		$reservation_id = wp_insert_post(
			array(
				'post_type'   => 'vb_reservierung',
				'post_status' => 'publish',
				'post_author' => $user->ID,
				'post_title'  => sprintf(
					'%s – %s',
					$boat->post_title,
					$user->display_name
				),
			),
			true
		);

		if ( is_wp_error( $reservation_id ) ) {
			$this->redirect_with_message( 'reservierung_fehler' );
		}

		update_post_meta( $reservation_id, '_vb_boot_id', $boat_id );
		update_post_meta( $reservation_id, '_vb_user_id', $user->ID );
		update_post_meta( $reservation_id, '_vb_reservierung_start', $start_utc );
		update_post_meta( $reservation_id, '_vb_reservierung_ende', $end_utc );

		$this->redirect_with_message( 'reserviert' );
	}

	/**
	 * Loescht eine eigene, noch nicht gestartete Reservierung.
	 *
	 * @return void
	 */
	public function delete_reservation() {
		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}

		$reservation_id = isset( $_POST['reservierung_id'] ) ? absint( wp_unslash( $_POST['reservierung_id'] ) ) : 0;
		check_admin_referer( 'verwaltung_boote_reservierung_loeschen_' . $reservation_id );

		$reservation = get_post( $reservation_id );
		if (
			! $reservation ||
			'vb_reservierung' !== $reservation->post_type ||
			'publish' !== $reservation->post_status ||
			(int) get_post_meta( $reservation_id, '_vb_user_id', true ) !== get_current_user_id() ||
			get_post_meta( $reservation_id, '_vb_started_log_id', true ) ||
			get_post_meta( $reservation_id, '_vb_storniert_am', true )
		) {
			$this->redirect_with_message( 'reservierung_ungueltig' );
		}

		update_post_meta( $reservation_id, '_vb_storniert_am', current_time( 'mysql', true ) );
		update_post_meta( $reservation_id, '_vb_storniert_von', get_current_user_id() );
		$this->redirect_with_message( 'reservierung_geloescht' );
	}

	/**
	 * Wandelt eine lokale Formularzeit in UTC um.
	 *
	 * @param string $value Wert aus datetime-local.
	 * @return \DateTimeImmutable|null
	 */
	private function parse_local_datetime( $value ) {
		$date   = \DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i', $value, wp_timezone() );
		$errors = \DateTimeImmutable::getLastErrors();

		if ( ! $date || ( $errors && ( $errors['warning_count'] || $errors['error_count'] ) ) ) {
			return null;
		}

		return $date->setTimezone( new \DateTimeZone( 'UTC' ) );
	}

	/**
	 * Prueft auf zeitliche Ueberschneidungen fuer dasselbe Boot.
	 *
	 * @param int    $boat_id Boot-ID.
	 * @param string $start   Start in UTC.
	 * @param string $end     Ende in UTC.
	 * @return bool
	 */
	private function has_overlapping_reservation( $boat_id, $start, $end ) {
		$reservations = get_posts(
			array(
				'post_type'      => 'vb_reservierung',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					'relation' => 'AND',
					array( 'key' => '_vb_boot_id', 'value' => $boat_id, 'type' => 'NUMERIC' ),
					array( 'key' => '_vb_reservierung_start', 'value' => $end, 'compare' => '<', 'type' => 'DATETIME' ),
					array( 'key' => '_vb_reservierung_ende', 'value' => $start, 'compare' => '>', 'type' => 'DATETIME' ),
					array( 'key' => '_vb_started_log_id', 'compare' => 'NOT EXISTS' ),
					array( 'key' => '_vb_storniert_am', 'compare' => 'NOT EXISTS' ),
				),
			)
		);

		return ! empty( $reservations );
	}

	/**
	 * Beginnt die Nutzung eines freien Boots.
	 *
	 * @return void
	 */
	public function start_usage() {
		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}

		$boat_id        = isset( $_POST['boot_id'] ) ? absint( wp_unslash( $_POST['boot_id'] ) ) : 0;
		$reservation_id = isset( $_POST['reservierung_id'] ) ? absint( wp_unslash( $_POST['reservierung_id'] ) ) : 0;
		check_admin_referer( 'verwaltung_boote_start_' . $boat_id );

		$boat = get_post( $boat_id );
		if ( ! $boat || 'vb_boot' !== $boat->post_type || 'publish' !== $boat->post_status ) {
			$this->redirect_with_message( 'ungueltiges_boot' );
		}

		if ( $this->get_active_log_for_boat( $boat_id ) ) {
			$this->redirect_with_message( 'bereits_vergeben' );
		}

		if ( $reservation_id ) {
			$reservation = get_post( $reservation_id );
			if (
				! $reservation ||
				'vb_reservierung' !== $reservation->post_type ||
				'publish' !== $reservation->post_status ||
				(int) get_post_meta( $reservation_id, '_vb_boot_id', true ) !== $boat_id ||
				(int) get_post_meta( $reservation_id, '_vb_user_id', true ) !== get_current_user_id() ||
				get_post_meta( $reservation_id, '_vb_started_log_id', true )
				|| get_post_meta( $reservation_id, '_vb_storniert_am', true )
			) {
				$this->redirect_with_message( 'reservierung_ungueltig' );
			}
			if ( ! $this->is_reservation_start_allowed( $reservation_id ) ) {
				$this->redirect_with_message( 'reservierung_startfenster' );
			}
		} else {
			$blocking_reservation = $this->get_blocking_reservation( $boat_id );
			if ( $blocking_reservation ) {
				if (
					(int) get_post_meta( $blocking_reservation->ID, '_vb_user_id', true ) !== get_current_user_id() ||
					! $this->is_reservation_start_allowed( $blocking_reservation->ID )
				) {
					$this->redirect_with_message( 'boot_reserviert' );
				}
				$reservation_id = $blocking_reservation->ID;
			}
		}

		$user       = wp_get_current_user();
		$started_at = current_time( 'mysql', true );
		$log_id     = wp_insert_post(
			array(
				'post_type'   => 'vb_logbuch',
				'post_status' => 'publish',
				'post_author' => $user->ID,
				'post_title'  => sprintf(
					'%s – %s',
					$boat->post_title,
					$user->display_name
				),
			),
			true
		);

		if ( is_wp_error( $log_id ) ) {
			$this->redirect_with_message( 'fehler' );
		}

		update_post_meta( $log_id, '_vb_boot_id', $boat_id );
		update_post_meta( $log_id, '_vb_user_id', $user->ID );
		update_post_meta( $log_id, '_vb_start', $started_at );
		delete_post_meta( $log_id, '_vb_end' );
		if ( $reservation_id ) {
			update_post_meta( $log_id, '_vb_reservierung_id', $reservation_id );
			update_post_meta( $reservation_id, '_vb_started_log_id', $log_id );
		}

		$this->redirect_with_message( 'gestartet' );
	}

	/**
	 * Beendet einen eigenen laufenden Logbucheintrag.
	 *
	 * @return void
	 */
	public function end_usage() {
		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}

		$log_id = isset( $_POST['log_id'] ) ? absint( wp_unslash( $_POST['log_id'] ) ) : 0;
		check_admin_referer( 'verwaltung_boote_end_' . $log_id );

		$log = get_post( $log_id );
		if (
			! $log ||
			'vb_logbuch' !== $log->post_type ||
			(int) get_post_meta( $log_id, '_vb_user_id', true ) !== get_current_user_id() ||
			get_post_meta( $log_id, '_vb_end', true )
		) {
			$this->redirect_with_message( 'ungueltiger_eintrag' );
		}

		$has_damage = isset( $_POST['schaden_vorhanden'] ) ? sanitize_key( wp_unslash( $_POST['schaden_vorhanden'] ) ) : '';
		if ( ! in_array( $has_damage, array( 'ja', 'nein' ), true ) ) {
			$this->redirect_with_message( 'schaden_abfrage_fehlt' );
		}

		$damage_id = 0;
		if ( 'ja' === $has_damage ) {
			$severity = isset( $_POST['schadenschwere'] ) ? sanitize_key( wp_unslash( $_POST['schadenschwere'] ) ) : '';
			$allowed  = $this->get_damage_severities();
			if ( ! isset( $allowed[ $severity ] ) ) {
				$this->redirect_with_message( 'schwere_fehlt' );
			}

			$description = isset( $_POST['schadensbeschreibung'] )
				? sanitize_textarea_field( wp_unslash( $_POST['schadensbeschreibung'] ) )
				: '';
			$boat_id     = (int) get_post_meta( $log_id, '_vb_boot_id', true );
			$reported_at = current_time( 'mysql', true );
			$damage_id   = wp_insert_post(
				array(
					'post_type'    => 'vb_schaden',
					'post_status'  => 'publish',
					'post_author'  => get_current_user_id(),
					'post_title'   => sprintf(
						'%s – %s',
						get_the_title( $boat_id ),
						$allowed[ $severity ]
					),
					'post_content' => $description,
				),
				true
			);

			if ( is_wp_error( $damage_id ) ) {
				$this->redirect_with_message( 'schaden_fehler' );
			}

			update_post_meta( $damage_id, '_vb_boot_id', $boat_id );
			update_post_meta( $damage_id, '_vb_user_id', get_current_user_id() );
			update_post_meta( $damage_id, '_vb_log_id', $log_id );
			update_post_meta( $damage_id, '_vb_schwere', $severity );
			update_post_meta( $damage_id, '_vb_gemeldet_am', $reported_at );
			$this->notify_boatswarte_about_damage( $damage_id, $log_id, $reported_at );
		}

		update_post_meta( $log_id, '_vb_schaden_vorhanden', $has_damage );
		if ( $damage_id ) {
			update_post_meta( $log_id, '_vb_schaden_id', $damage_id );
			update_post_meta( $log_id, '_vb_schadenschwere', $severity );
			update_post_meta( $log_id, '_vb_schadensbeschreibung', $description );
		}
		$ended_at = current_time( 'mysql', true );
		update_post_meta( $log_id, '_vb_end', $ended_at );
		$this->complete_linked_reservation( $log_id, $ended_at );
		$this->redirect_with_message( 'beendet' );
	}

	/**
	 * Benachrichtigt alle Bootswarte über eine neue Schadensmeldung.
	 *
	 * @param int    $damage_id   Schadens-ID.
	 * @param int    $log_id      Logbuch-ID.
	 * @param string $reported_at Meldezeitpunkt in UTC.
	 * @return void
	 */
	private function notify_boatswarte_about_damage( $damage_id, $log_id, $reported_at ) {
		$recipients = array();
		foreach ( get_users( array( 'role' => 'bootswart', 'fields' => array( 'user_email' ) ) ) as $user ) {
			if ( ! empty( $user->user_email ) && is_email( $user->user_email ) ) {
				$recipients[] = $user->user_email;
			}
		}

		$recipients = array_unique( $recipients );
		if ( empty( $recipients ) ) {
			update_post_meta( $damage_id, '_vb_benachrichtigung_gesendet', 'nein' );
			return;
		}

		$boat_id       = (int) get_post_meta( $damage_id, '_vb_boot_id', true );
		$user_id       = (int) get_post_meta( $damage_id, '_vb_user_id', true );
		$severity_key  = (string) get_post_meta( $damage_id, '_vb_schwere', true );
		$severities    = $this->get_damage_severities();
		$reporter      = get_userdata( $user_id );
		$start         = (string) get_post_meta( $log_id, '_vb_start', true );
		$end           = current_time( 'mysql', true );
		$reservation_id = (int) get_post_meta( $log_id, '_vb_reservierung_id', true );
		$description   = (string) get_post_field( 'post_content', $damage_id );
		$boat_type     = $this->get_boat_type( $boat_id );
		$berth         = $this->get_boat_berth( $boat_id );
		$subject       = sprintf( __( '[%1$s] Neue Schadensmeldung: %2$s', 'verwaltung-boote' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), get_the_title( $boat_id ) );
		$message       = implode(
			"\n",
			array(
				__( 'Für ein Vereinsboot wurde ein Schaden gemeldet.', 'verwaltung-boote' ),
				'',
				sprintf( __( 'Boot: %s', 'verwaltung-boote' ), get_the_title( $boat_id ) ),
				sprintf( __( 'Bootstyp: %s', 'verwaltung-boote' ), $boat_type ),
				sprintf( __( 'Liegeplatz: %s', 'verwaltung-boote' ), $berth ),
				sprintf( __( 'Gemeldet von: %s', 'verwaltung-boote' ), $reporter ? $reporter->display_name : '–' ),
				sprintf( __( 'Gemeldet am: %s', 'verwaltung-boote' ), $this->format_logbook_time( $reported_at, '–' ) ),
				sprintf( __( 'Nutzung begonnen: %s', 'verwaltung-boote' ), $this->format_logbook_time( $start, '–' ) ),
				sprintf( __( 'Nutzung beendet: %s', 'verwaltung-boote' ), $this->format_logbook_time( $end, '–' ) ),
				sprintf( __( 'Aus Reservierung: %s', 'verwaltung-boote' ), $reservation_id ? __( 'Ja', 'verwaltung-boote' ) : __( 'Nein', 'verwaltung-boote' ) ),
				sprintf( __( 'Logbucheintrag: #%d', 'verwaltung-boote' ), $log_id ),
				sprintf( __( 'Schwere des Schadens: %s', 'verwaltung-boote' ), isset( $severities[ $severity_key ] ) ? $severities[ $severity_key ] : '–' ),
				'',
				__( 'Schadensbeschreibung:', 'verwaltung-boote' ),
				$description ? $description : __( 'Keine Beschreibung angegeben.', 'verwaltung-boote' ),
			)
		);
		$sent          = wp_mail( $recipients, $subject, $message, array( 'Content-Type: text/plain; charset=UTF-8' ) );

		update_post_meta( $damage_id, '_vb_benachrichtigung_gesendet', $sent ? 'ja' : 'nein' );
		if ( $sent ) {
			update_post_meta( $damage_id, '_vb_benachrichtigung_gesendet_am', current_time( 'mysql', true ) );
		}
	}

	/**
	 * Beendet eine laufende Nutzung durch den Bootswart.
	 *
	 * @return void
	 */
	public function admin_end_usage() {
		$log_id = isset( $_POST['log_id'] ) ? absint( wp_unslash( $_POST['log_id'] ) ) : 0;
		check_admin_referer( 'verwaltung_boote_admin_nutzung_beenden_' . $log_id );

		$log = get_post( $log_id );
		if (
			! $log ||
			'vb_logbuch' !== $log->post_type ||
			! current_user_can( 'edit_post', $log_id ) ||
			get_post_meta( $log_id, '_vb_end', true )
		) {
			$this->redirect_admin_list( 'vb_logbuch', 'aktion_fehler' );
		}

		$ended_at = current_time( 'mysql', true );
		update_post_meta( $log_id, '_vb_end', $ended_at );
		$this->complete_linked_reservation( $log_id, $ended_at );
		update_post_meta( $log_id, '_vb_beendet_von_bootswart', get_current_user_id() );
		$this->redirect_admin_list( 'vb_logbuch', 'nutzung_beendet' );
	}

	/**
	 * Schliesst die verknuepfte Reservierung eines beendeten Logbucheintrags ab.
	 *
	 * @param int    $log_id   Logbuch-ID.
	 * @param string $ended_at Ende der Nutzung in UTC.
	 * @return void
	 */
	private function complete_linked_reservation( $log_id, $ended_at ) {
		$reservation_id = (int) get_post_meta( $log_id, '_vb_reservierung_id', true );
		$reservation    = $reservation_id ? get_post( $reservation_id ) : null;

		if ( ! $reservation || 'vb_reservierung' !== $reservation->post_type ) {
			return;
		}

		update_post_meta( $reservation_id, '_vb_nutzung_beendet_am', $ended_at );
		update_post_meta( $reservation_id, '_vb_beendeter_log_id', $log_id );
	}

	/**
	 * Markiert einen Schaden als behoben, ohne ihn zu loeschen.
	 *
	 * @return void
	 */
	public function resolve_damage() {
		$damage_id = isset( $_POST['damage_id'] ) ? absint( wp_unslash( $_POST['damage_id'] ) ) : 0;
		check_admin_referer( 'verwaltung_boote_schaden_beheben_' . $damage_id );

		$damage = get_post( $damage_id );
		if (
			! $damage ||
			'vb_schaden' !== $damage->post_type ||
			! current_user_can( 'edit_post', $damage_id )
		) {
			$this->redirect_admin_list( 'vb_schaden', 'aktion_fehler' );
		}

		if ( ! get_post_meta( $damage_id, '_vb_behoben_am', true ) ) {
			update_post_meta( $damage_id, '_vb_behoben_am', current_time( 'mysql', true ) );
			update_post_meta( $damage_id, '_vb_behoben_von', get_current_user_id() );
		}

		$this->redirect_admin_list( 'vb_schaden', 'schaden_behoben' );
	}

	/**
	 * Loescht eine beliebige Reservierung durch den Bootswart.
	 *
	 * @return void
	 */
	public function admin_delete_reservation() {
		$reservation_id = isset( $_POST['reservierung_id'] ) ? absint( wp_unslash( $_POST['reservierung_id'] ) ) : 0;
		check_admin_referer( 'verwaltung_boote_admin_reservierung_loeschen_' . $reservation_id );

		$reservation = get_post( $reservation_id );
		if (
			! $reservation ||
			'vb_reservierung' !== $reservation->post_type ||
			! current_user_can( 'delete_post', $reservation_id ) ||
			get_post_meta( $reservation_id, '_vb_started_log_id', true ) ||
			get_post_meta( $reservation_id, '_vb_storniert_am', true )
		) {
			$this->redirect_admin_list( 'vb_reservierung', 'aktion_fehler' );
		}

		update_post_meta( $reservation_id, '_vb_storniert_am', current_time( 'mysql', true ) );
		update_post_meta( $reservation_id, '_vb_storniert_von', get_current_user_id() );
		$this->redirect_admin_list( 'vb_reservierung', 'reservierung_geloescht' );
	}

	/**
	 * Ergaenzt Verwaltungsaktionen in Logbuch und Schadensliste.
	 *
	 * @param array<string,string> $actions Zeilenaktionen.
	 * @param \WP_Post            $post    Eintrag.
	 * @return array<string,string>
	 */
	public function admin_row_actions( $actions, $post ) {
		if ( 'vb_reservierung' === $post->post_type ) {
			unset( $actions['trash'], $actions['delete'] );
			$data = $this->get_reservation_data( $post->ID );
			if ( ! $data['log_id'] && ! $data['cancelled_utc'] && strtotime( $data['end_utc'] . ' UTC' ) >= time() && current_user_can( 'delete_post', $post->ID ) ) {
				$actions['vb_reservierung_stornieren'] = $this->get_admin_action_form( 'verwaltung_boote_admin_reservierung_loeschen', 'reservierung_id', $post->ID, 'verwaltung_boote_admin_reservierung_loeschen_' . $post->ID, __( 'Stornieren', 'verwaltung-boote' ) );
			}
		}

		if ( 'vb_logbuch' === $post->post_type && ! get_post_meta( $post->ID, '_vb_end', true ) && current_user_can( 'edit_post', $post->ID ) ) {
			$actions['vb_nutzung_beenden'] = $this->get_admin_action_form( 'verwaltung_boote_admin_nutzung_beenden', 'log_id', $post->ID, 'verwaltung_boote_admin_nutzung_beenden_' . $post->ID, __( 'Nutzung beenden', 'verwaltung-boote' ) );
		}

		if ( 'vb_schaden' === $post->post_type && ! get_post_meta( $post->ID, '_vb_behoben_am', true ) && current_user_can( 'edit_post', $post->ID ) ) {
			$actions['vb_schaden_beheben'] = $this->get_admin_action_form( 'verwaltung_boote_schaden_beheben', 'damage_id', $post->ID, 'verwaltung_boote_schaden_beheben_' . $post->ID, __( 'Als behoben markieren', 'verwaltung-boote' ) );
		}

		return $actions;
	}

	/**
	 * Erstellt eine geschützte POST-Aktion für eine WordPress-Zeilenaktion.
	 *
	 * @param string $action      Admin-Post-Aktion.
	 * @param string $id_field    Name des ID-Felds.
	 * @param int    $id          Objekt-ID.
	 * @param string $nonce_action Nonce-Aktion.
	 * @param string $label       Beschriftung.
	 * @return string
	 */
	private function get_admin_action_form( $action, $id_field, $id, $nonce_action, $label ) {
		return '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline">'
			. '<input type="hidden" name="action" value="' . esc_attr( $action ) . '">'
			. '<input type="hidden" name="' . esc_attr( $id_field ) . '" value="' . esc_attr( $id ) . '">'
			. wp_nonce_field( $nonce_action, '_wpnonce', true, false )
			. '<button type="submit" class="button-link">' . esc_html( $label ) . '</button></form>';
	}

	/**
	 * Entfernt endgueltige Loeschaktionen aus der Reservierungshistorie.
	 *
	 * @param array<string,string> $actions Massenaktionen.
	 * @return array<string,string>
	 */
	public function remove_reservation_delete_actions( $actions ) {
		unset( $actions['trash'], $actions['delete'] );
		return $actions;
	}

	/**
	 * Leitet zur passenden Verwaltungsliste zurueck.
	 *
	 * @param string $post_type Inhaltstyp.
	 * @param string $message   Meldungsschluessel.
	 * @return void
	 */
	private function redirect_admin_list( $post_type, $message ) {
		$frontend = isset( $_REQUEST['frontend'] ) && '1' === sanitize_text_field( wp_unslash( $_REQUEST['frontend'] ) );
		$referer  = wp_get_referer();
		if ( $frontend && $referer ) {
			wp_safe_redirect( add_query_arg( 'vb_bootswart_meldung', sanitize_key( $message ), $referer ) );
			exit;
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'post_type'         => sanitize_key( $post_type ),
					'vb_admin_meldung' => sanitize_key( $message ),
				),
				admin_url( 'edit.php' )
			)
		);
		exit;
	}

	/**
	 * Zeigt Rueckmeldungen zu Bootswart-Aktionen.
	 *
	 * @return void
	 */
	public function render_admin_notice() {
		$key = isset( $_GET['vb_admin_meldung'] ) ? sanitize_key( wp_unslash( $_GET['vb_admin_meldung'] ) ) : '';
		$messages = array(
			'nutzung_beendet' => __( 'Die Nutzung wurde beendet.', 'verwaltung-boote' ),
			'schaden_behoben' => __( 'Der Schaden wurde als behoben markiert und bleibt gespeichert.', 'verwaltung-boote' ),
			'aktion_fehler'   => __( 'Die Aktion konnte nicht ausgeführt werden.', 'verwaltung-boote' ),
			'reservierung_geloescht' => __( 'Die Reservierung wurde storniert und bleibt in der Historie erhalten.', 'verwaltung-boote' ),
		);

		if ( isset( $messages[ $key ] ) ) {
			$class = 'aktion_fehler' === $key ? 'notice notice-error' : 'notice notice-success is-dismissible';
			echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( $messages[ $key ] ) . '</p></div>';
		}
	}

	/**
	 * Definiert die vollstaendige Logbuchuebersicht.
	 *
	 * @param array<string,string> $columns Standardspalten.
	 * @return array<string,string>
	 */
	public function logbook_columns( $columns ) {
		return array(
			'cb'              => isset( $columns['cb'] ) ? $columns['cb'] : '<input type="checkbox">',
			'title'           => __( 'Vorgang', 'verwaltung-boote' ),
			'vb_boot'         => __( 'Boot', 'verwaltung-boote' ),
			'vb_mitglied'     => __( 'Mitglied', 'verwaltung-boote' ),
			'vb_start'        => __( 'Ausleihzeitpunkt', 'verwaltung-boote' ),
			'vb_ende'         => __( 'Rückgabezeitpunkt', 'verwaltung-boote' ),
			'vb_reservierung' => __( 'Aus Reservierung', 'verwaltung-boote' ),
			'vb_schaden'      => __( 'Schaden', 'verwaltung-boote' ),
			'vb_schwere'      => __( 'Schadensgrad', 'verwaltung-boote' ),
			'vb_beschreibung' => __( 'Beschreibung', 'verwaltung-boote' ),
		);
	}

	/**
	 * Gibt die Inhalte einer Logbuchspalte sicher aus.
	 *
	 * @param string $column  Spaltenname.
	 * @param int    $post_id Logbuch-ID.
	 * @return void
	 */
	public function render_logbook_column( $column, $post_id ) {
		$data = $this->get_logbook_data( $post_id );

		switch ( $column ) {
			case 'vb_boot':
				echo esc_html( $data['boat'] );
				break;
			case 'vb_mitglied':
				echo esc_html( $data['user'] );
				break;
			case 'vb_start':
				$this->render_browser_datetime( $data['start_utc'] );
				break;
			case 'vb_ende':
				$this->render_browser_datetime( $data['end_utc'], __( 'Nutzung läuft', 'verwaltung-boote' ) );
				break;
			case 'vb_reservierung':
				echo esc_html( $data['reservation_id'] ? sprintf( __( 'Ja, Reservierung #%d', 'verwaltung-boote' ), $data['reservation_id'] ) : __( 'Nein', 'verwaltung-boote' ) );
				break;
			case 'vb_schaden':
				echo esc_html( $data['damage'] );
				break;
			case 'vb_schwere':
				echo esc_html( $data['severity'] );
				break;
			case 'vb_beschreibung':
				echo esc_html( $data['description'] );
				break;
		}
	}

	/**
	 * Fuegt die schreibgeschuetzte Vorgangsansicht hinzu.
	 *
	 * @return void
	 */
	public function add_logbook_meta_box() {
		add_meta_box(
			'verwaltung-boote-logbuch-details',
			__( 'Ausleih- und Rückgabevorgang', 'verwaltung-boote' ),
			array( $this, 'render_logbook_meta_box' ),
			'vb_logbuch',
			'normal',
			'high'
		);
	}

	/**
	 * Zeigt alle Angaben eines Logbucheintrags.
	 *
	 * @param \WP_Post $post Logbucheintrag.
	 * @return void
	 */
	public function render_logbook_meta_box( $post ) {
		$data = $this->get_logbook_data( $post->ID );
		$rows = array(
			'boat'        => array( __( 'Boot', 'verwaltung-boote' ), $data['boat'] ),
			'user'        => array( __( 'Mitglied', 'verwaltung-boote' ), $data['user'] ),
			'start'       => array( __( 'Ausleihzeitpunkt', 'verwaltung-boote' ), $data['start'] ),
			'end'         => array( __( 'Rückgabezeitpunkt', 'verwaltung-boote' ), $data['end'] ),
			'reservation' => array( __( 'Aus Reservierung', 'verwaltung-boote' ), $data['reservation_label'] ),
			'damage'      => array( __( 'Schaden vorhanden', 'verwaltung-boote' ), $data['damage'] ),
			'severity'    => array( __( 'Schadensgrad', 'verwaltung-boote' ), $data['severity'] ),
			'description' => array( __( 'Beschreibung', 'verwaltung-boote' ), $data['description'] ),
		);
		?>
		<table class="widefat striped">
			<tbody>
			<?php foreach ( $rows as $key => $row ) : ?>
				<tr>
					<th scope="row" style="width: 200px;"><?php echo esc_html( $row[0] ); ?></th>
					<td>
					<?php if ( 'start' === $key ) : ?>
						<?php $this->render_browser_datetime( $data['start_utc'] ); ?>
					<?php elseif ( 'end' === $key ) : ?>
						<?php $this->render_browser_datetime( $data['end_utc'], __( 'Nutzung läuft', 'verwaltung-boote' ) ); ?>
					<?php else : ?>
						<?php echo nl2br( esc_html( $row[1] ) ); ?>
					<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Bereitet alle sichtbaren Angaben eines Vorgangs auf.
	 *
	 * @param int $log_id Logbuch-ID.
	 * @return array<string,string>
	 */
	private function get_logbook_data( $log_id ) {
		$boat_id      = (int) get_post_meta( $log_id, '_vb_boot_id', true );
		$user_id      = (int) get_post_meta( $log_id, '_vb_user_id', true );
		$user         = get_userdata( $user_id );
		$start        = (string) get_post_meta( $log_id, '_vb_start', true );
		$end          = (string) get_post_meta( $log_id, '_vb_end', true );
		$has_damage   = (string) get_post_meta( $log_id, '_vb_schaden_vorhanden', true );
		$severity_key = (string) get_post_meta( $log_id, '_vb_schadenschwere', true );
		$description  = (string) get_post_meta( $log_id, '_vb_schadensbeschreibung', true );
		$reservation_id = (int) get_post_meta( $log_id, '_vb_reservierung_id', true );
		$severities   = $this->get_damage_severities();

		if ( 'ja' === $has_damage && ( ! $severity_key || ! $description ) ) {
			$damage_id = (int) get_post_meta( $log_id, '_vb_schaden_id', true );
			if ( $damage_id ) {
				$severity_key = $severity_key ? $severity_key : (string) get_post_meta( $damage_id, '_vb_schwere', true );
				$description  = $description ? $description : (string) get_post_field( 'post_content', $damage_id );
			}
		}

		if ( 'ja' === $has_damage ) {
			$damage = __( 'Ja', 'verwaltung-boote' );
		} elseif ( 'nein' === $has_damage ) {
			$damage = __( 'Nein', 'verwaltung-boote' );
		} else {
			$damage = $end ? __( 'Keine Angabe', 'verwaltung-boote' ) : __( 'Nutzung läuft', 'verwaltung-boote' );
		}

		return array(
			'boat'        => $boat_id ? get_the_title( $boat_id ) : '–',
			'user'        => $user ? $user->display_name : '–',
			'start'       => $this->format_logbook_time( $start, '–' ),
			'end'         => $this->format_logbook_time( $end, __( 'Nutzung läuft', 'verwaltung-boote' ) ),
			'start_utc'   => $start,
			'end_utc'     => $end,
			'damage'      => $damage,
			'severity'    => isset( $severities[ $severity_key ] ) ? $severities[ $severity_key ] : '–',
			'description' => $description ? $description : '–',
			'reservation_id'    => $reservation_id,
			'reservation_label' => $reservation_id ? sprintf( __( 'Ja, Reservierung #%d', 'verwaltung-boote' ), $reservation_id ) : __( 'Nein', 'verwaltung-boote' ),
		);
	}

	/**
	 * Formatiert einen gespeicherten UTC-Zeitpunkt in der WordPress-Zeitzone.
	 *
	 * @param string $time     UTC-Zeitpunkt.
	 * @param string $fallback Ersatzwert.
	 * @return string
	 */
	private function format_logbook_time( $time, $fallback ) {
		if ( ! $time ) {
			return $fallback;
		}

		return get_date_from_gmt( $time, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
	}

	/**
	 * Gibt einen UTC-Zeitpunkt zur Lokalisierung durch den Browser aus.
	 *
	 * @param string $time     UTC-Zeitpunkt.
	 * @param string $fallback Ersatztext.
	 * @param string $format   datetime oder time.
	 * @return void
	 */
	private function render_browser_datetime( $time, $fallback = '–', $format = 'datetime' ) {
		if ( ! $time ) {
			echo esc_html( $fallback );
			return;
		}

		$timestamp = strtotime( $time . ' UTC' );
		if ( ! $timestamp ) {
			echo esc_html( $fallback );
			return;
		}

		echo '<time class="verwaltung-boote-browser-datetime" datetime="' . esc_attr( gmdate( 'c', $timestamp ) ) . '" data-vb-format="' . esc_attr( $format ) . '">' . esc_html( $this->format_logbook_time( $time, $fallback ) ) . '</time>';
	}

	/**
	 * Laedt die Browser-Lokalisierung auch in den Plugin-Verwaltungslisten.
	 *
	 * @param string $hook_suffix Aktuelle Adminseite.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, array( 'edit.php', 'post.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, array( 'vb_logbuch', 'vb_reservierung', 'vb_schaden' ), true ) ) {
			return;
		}

		wp_enqueue_script(
			'verwaltung-boote-frontend',
			plugins_url( 'assets/js/frontend.js', VERWALTUNG_BOOTE_FILE ),
			array(),
			VERWALTUNG_BOOTE_VERSION,
			true
		);
	}

	/**
	 * Definiert die Spalten der Reservierungsliste.
	 *
	 * @param array<string,string> $columns Standardspalten.
	 * @return array<string,string>
	 */
	public function reservation_columns( $columns ) {
		return array(
			'cb'         => isset( $columns['cb'] ) ? $columns['cb'] : '<input type="checkbox">',
			'title'      => __( 'Reservierung', 'verwaltung-boote' ),
			'vb_boot'    => __( 'Boot', 'verwaltung-boote' ),
			'vb_mitglied' => __( 'Mitglied', 'verwaltung-boote' ),
			'vb_start'   => __( 'Start', 'verwaltung-boote' ),
			'vb_ende'    => __( 'Ende', 'verwaltung-boote' ),
			'vb_status'  => __( 'Status', 'verwaltung-boote' ),
		);
	}

	/**
	 * Gibt eine Spalte der Reservierungsliste aus.
	 *
	 * @param string $column         Spaltenname.
	 * @param int    $reservation_id Reservierungs-ID.
	 * @return void
	 */
	public function render_reservation_column( $column, $reservation_id ) {
		$data = $this->get_reservation_data( $reservation_id );

		switch ( $column ) {
			case 'vb_boot':
				echo esc_html( $data['boat'] );
				break;
			case 'vb_mitglied':
				echo esc_html( $data['user'] );
				break;
			case 'vb_start':
				$this->render_browser_datetime( $data['start_utc'] );
				break;
			case 'vb_ende':
				$this->render_browser_datetime( $data['end_utc'] );
				break;
			case 'vb_status':
				echo esc_html( $data['status'] );
				break;
		}
	}

	/**
	 * Definiert die Spalten der Bootsschaeden-Liste.
	 *
	 * @param array<string,string> $columns Standardspalten.
	 * @return array<string,string>
	 */
	public function damage_columns( $columns ) {
		return array(
			'cb'            => isset( $columns['cb'] ) ? $columns['cb'] : '<input type="checkbox">',
			'title'         => __( 'Schadensmeldung', 'verwaltung-boote' ),
			'vb_boot'       => __( 'Boot', 'verwaltung-boote' ),
			'vb_ausleiher'  => __( 'Ausleihender', 'verwaltung-boote' ),
			'vb_schwere'    => __( 'Schwere des Schadens', 'verwaltung-boote' ),
			'vb_status'     => __( 'Status', 'verwaltung-boote' ),
			'vb_kommentar'  => __( 'Kommentar', 'verwaltung-boote' ),
			'vb_logbuch'    => __( 'Logbucheintrag', 'verwaltung-boote' ),
			'vb_gemeldet'   => __( 'Gemeldet am', 'verwaltung-boote' ),
		);
	}

	/**
	 * Gibt eine Spalte der Bootsschaeden-Liste aus.
	 *
	 * @param string $column    Spaltenname.
	 * @param int    $damage_id Schadens-ID.
	 * @return void
	 */
	public function render_damage_column( $column, $damage_id ) {
		$data = $this->get_damage_data( $damage_id );

		switch ( $column ) {
			case 'vb_boot':
				echo esc_html( $data['boat'] );
				break;
			case 'vb_ausleiher':
				echo esc_html( $data['user'] );
				break;
			case 'vb_schwere':
				echo esc_html( $data['severity'] );
				break;
			case 'vb_status':
				echo esc_html( $data['resolved_utc'] ? __( 'Behoben', 'verwaltung-boote' ) : __( 'Offen', 'verwaltung-boote' ) );
				if ( $data['resolved_utc'] ) {
					echo '<br>';
					$this->render_browser_datetime( $data['resolved_utc'] );
				}
				break;
			case 'vb_kommentar':
				echo nl2br( esc_html( $data['comment'] ) );
				break;
			case 'vb_logbuch':
				if ( $data['log_id'] ) {
					$edit_link = get_edit_post_link( $data['log_id'] );
					$label     = sprintf( __( 'Logbuch #%d', 'verwaltung-boote' ), $data['log_id'] );
					if ( $edit_link ) {
						echo '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $label ) . '</a>';
					} else {
						echo esc_html( $label );
					}
				} else {
					echo '–';
				}
				break;
			case 'vb_gemeldet':
				$this->render_browser_datetime( $data['reported_utc'] );
				break;
		}
	}

	/**
	 * Fuegt eine Detailbox zur Schadensmeldung hinzu.
	 *
	 * @return void
	 */
	public function add_damage_meta_box() {
		add_meta_box(
			'verwaltung-boote-schaden-details',
			__( 'Angaben zur Schadensmeldung', 'verwaltung-boote' ),
			array( $this, 'render_damage_meta_box' ),
			'vb_schaden',
			'normal',
			'high'
		);
	}

	/**
	 * Zeigt alle Bezuege und Angaben einer Schadensmeldung.
	 *
	 * @param \WP_Post $post Schadensmeldung.
	 * @return void
	 */
	public function render_damage_meta_box( $post ) {
		$data     = $this->get_damage_data( $post->ID );
		$log_link = $data['log_id'] ? get_edit_post_link( $data['log_id'] ) : '';
		?>
		<table class="widefat striped">
			<tbody>
				<tr><th scope="row" style="width: 200px;"><?php esc_html_e( 'Boot', 'verwaltung-boote' ); ?></th><td><?php echo esc_html( $data['boat'] ); ?></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Ausleihender', 'verwaltung-boote' ); ?></th><td><?php echo esc_html( $data['user'] ); ?></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Schwere des Schadens', 'verwaltung-boote' ); ?></th><td><?php echo esc_html( $data['severity'] ); ?></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Status', 'verwaltung-boote' ); ?></th><td>
				<?php if ( $data['resolved_utc'] ) : ?>
					<?php esc_html_e( 'Behoben am ', 'verwaltung-boote' ); ?><?php $this->render_browser_datetime( $data['resolved_utc'] ); ?><?php echo esc_html( sprintf( __( ' von %s', 'verwaltung-boote' ), $data['resolved_by'] ) ); ?>
				<?php else : ?>
					<?php esc_html_e( 'Offen', 'verwaltung-boote' ); ?>
				<?php endif; ?>
				</td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Kommentar', 'verwaltung-boote' ); ?></th><td><?php echo nl2br( esc_html( $data['comment'] ) ); ?></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Gemeldet am', 'verwaltung-boote' ); ?></th><td><?php $this->render_browser_datetime( $data['reported_utc'] ); ?></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Logbucheintrag', 'verwaltung-boote' ); ?></th><td>
				<?php if ( $data['log_id'] && $log_link ) : ?>
					<a href="<?php echo esc_url( $log_link ); ?>"><?php echo esc_html( sprintf( __( 'Logbuch #%d öffnen', 'verwaltung-boote' ), $data['log_id'] ) ); ?></a>
				<?php elseif ( $data['log_id'] ) : ?>
					<?php echo esc_html( sprintf( __( 'Logbuch #%d', 'verwaltung-boote' ), $data['log_id'] ) ); ?>
				<?php else : ?>
					<?php echo '–'; ?>
				<?php endif; ?>
				</td></tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Bereitet die sichtbaren Daten einer Schadensmeldung auf.
	 *
	 * @param int $damage_id Schadens-ID.
	 * @return array<string,mixed>
	 */
	private function get_damage_data( $damage_id ) {
		$boat_id      = (int) get_post_meta( $damage_id, '_vb_boot_id', true );
		$user_id      = (int) get_post_meta( $damage_id, '_vb_user_id', true );
		$log_id       = (int) get_post_meta( $damage_id, '_vb_log_id', true );
		$severity_key = (string) get_post_meta( $damage_id, '_vb_schwere', true );
		$reported_utc = (string) get_post_meta( $damage_id, '_vb_gemeldet_am', true );
		$resolved_utc = (string) get_post_meta( $damage_id, '_vb_behoben_am', true );
		$resolved_by_id = (int) get_post_meta( $damage_id, '_vb_behoben_von', true );
		$resolved_by = $resolved_by_id ? get_userdata( $resolved_by_id ) : false;
		$severities   = $this->get_damage_severities();
		$user         = get_userdata( $user_id );

		return array(
			'boat'     => $boat_id ? get_the_title( $boat_id ) : '–',
			'user'     => $user ? $user->display_name : '–',
			'severity' => isset( $severities[ $severity_key ] ) ? $severities[ $severity_key ] : '–',
			'comment'  => (string) get_post_field( 'post_content', $damage_id ) ?: '–',
			'log_id'   => $log_id,
			'reported_utc' => $reported_utc,
			'resolved_utc' => $resolved_utc,
			'resolved_by'  => $resolved_by ? $resolved_by->display_name : '–',
		);
	}

	/**
	 * Leitet nach einer Logbuchaktion sicher zur Ausgangsseite zurueck.
	 *
	 * @param string $message Meldungsschluessel.
	 * @return void
	 */
	private function redirect_with_message( $message ) {
		$url = wp_get_referer();
		if ( ! $url ) {
			$page_id = absint( get_option( 'verwaltung_boote_list_page_id' ) );
			$url     = $page_id ? get_permalink( $page_id ) : home_url( '/' );
		}

		wp_safe_redirect( add_query_arg( 'vb_meldung', sanitize_key( $message ), $url ) );
		exit;
	}

	/** @return string */
	public function render_bootswart_boats() { return $this->render_bootswart_section( 'boats' ); }

	/** @return string */
	public function render_bootswart_reservations() { return $this->render_bootswart_section( 'reservations' ); }

	/** @return string */
	public function render_bootswart_usages() { return $this->render_bootswart_section( 'usages' ); }

	/** @return string */
	public function render_bootswart_damages() { return $this->render_bootswart_section( 'damages' ); }

	/** @return string */
	public function render_bootswart_users() { return $this->render_bootswart_section( 'users' ); }

	/**
	 * Zeigt eine lokale Druckansicht mit QR-Codes für alle Boote.
	 *
	 * @return string
	 */
	public function render_bootswart_qr_codes() {
		if ( ! $this->is_bootswart() ) {
			return '<p>' . esc_html__( 'Diese Ansicht ist ausschließlich für den Bootswart bestimmt.', 'verwaltung-boote' ) . '</p>';
		}

		$entry_page_id = absint( get_option( 'verwaltung_boote_qr_entry_page_id' ) );
		if ( ! $entry_page_id ) {
			return '<p>' . esc_html__( 'Die Einstiegsseite für QR-Codes ist noch nicht verfügbar.', 'verwaltung-boote' ) . '</p>';
		}

		wp_enqueue_style( 'verwaltung-boote-frontend', plugins_url( 'assets/css/frontend.css', VERWALTUNG_BOOTE_FILE ), array(), VERWALTUNG_BOOTE_VERSION );
		wp_enqueue_script( 'verwaltung-boote-qrcode', plugins_url( 'assets/js/qrcode.min.js', VERWALTUNG_BOOTE_FILE ), array(), VERWALTUNG_BOOTE_VERSION, true );
		wp_enqueue_script( 'verwaltung-boote-frontend', plugins_url( 'assets/js/frontend.js', VERWALTUNG_BOOTE_FILE ), array( 'verwaltung-boote-qrcode' ), VERWALTUNG_BOOTE_VERSION, true );

		$boats = $this->sort_boats_by_type( get_posts( array( 'post_type' => 'vb_boot', 'post_status' => 'publish', 'posts_per_page' => -1 ) ) );
		ob_start();
		?>
		<div class="verwaltung-boote-qr-druck">
			<p class="verwaltung-boote-qr-navigation"><a href="<?php echo esc_url( get_permalink( $this->get_bootswart_pages()['overview'] ) ); ?>">&larr; <?php esc_html_e( 'Zur Bootswart-Verwaltung', 'verwaltung-boote' ); ?></a></p>
			<h2><?php esc_html_e( 'QR-Codes für Vereinsboote', 'verwaltung-boote' ); ?></h2>
			<p><?php esc_html_e( 'Jeder Code öffnet die passende Bootsseite zum Nutzen, Reservieren oder Beenden einer Nutzung.', 'verwaltung-boote' ); ?></p>
			<button class="verwaltung-boote-qr-drucken" type="button"><?php esc_html_e( 'QR-Codes drucken', 'verwaltung-boote' ); ?></button>
			<div class="verwaltung-boote-qr-liste">
			<?php foreach ( $boats as $boat ) : ?>
				<?php $url = add_query_arg( 'boot', $this->get_boat_identifier( $boat->ID ), get_permalink( $entry_page_id ) ); ?>
				<article class="verwaltung-boote-qr-karte">
					<h3><?php echo esc_html( $boat->post_title ); ?></h3>
					<p><?php echo esc_html( $this->get_boat_type( $boat->ID ) ); ?> · <?php echo esc_html( $this->get_boat_berth( $boat->ID ) ); ?></p>
					<div class="verwaltung-boote-qr-code" data-vb-qr-url="<?php echo esc_attr( $url ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'QR-Code für %s', 'verwaltung-boote' ), $boat->post_title ) ); ?>"></div>
					<p class="verwaltung-boote-qr-url"><?php echo esc_html( $url ); ?></p>
				</article>
			<?php endforeach; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Rendert eine der geschützten Bootswart-Ansichten.
	 *
	 * @param string $section Bereich.
	 * @return string
	 */
	private function render_bootswart_section( $section ) {
		if ( ! $this->is_bootswart() ) {
			return '<p>' . esc_html__( 'Diese Ansicht ist ausschließlich für den Bootswart bestimmt.', 'verwaltung-boote' ) . '</p>';
		}

		wp_enqueue_style( 'verwaltung-boote-frontend', plugins_url( 'assets/css/frontend.css', VERWALTUNG_BOOTE_FILE ), array(), VERWALTUNG_BOOTE_VERSION );
		wp_enqueue_script( 'verwaltung-boote-frontend', plugins_url( 'assets/js/frontend.js', VERWALTUNG_BOOTE_FILE ), array(), VERWALTUNG_BOOTE_VERSION, true );
		$pages = $this->get_bootswart_pages();

		ob_start();
		?>
		<div class="verwaltung-boote-bootswart">
			<?php $this->render_bootswart_message(); ?>
			<?php if ( 'overview' === $section ) : ?>
				<p><?php esc_html_e( 'Wähle einen Bereich für die Verwaltung der Vereinsboote.', 'verwaltung-boote' ); ?></p>
				<nav class="verwaltung-boote-bootswart-navigation" aria-label="<?php esc_attr_e( 'Bootswart-Verwaltung', 'verwaltung-boote' ); ?>"><ul>
					<?php foreach ( array( 'boats', 'reservations', 'usages', 'damages', 'users', 'qr' ) as $key ) : ?>
						<?php if ( ! empty( $pages[ $key ] ) ) : ?><li><a href="<?php echo esc_url( get_permalink( $pages[ $key ] ) ); ?>"><?php echo esc_html( get_the_title( $pages[ $key ] ) ); ?></a></li><?php endif; ?>
					<?php endforeach; ?>
				</ul></nav>
			<?php else : ?>
				<p><a href="<?php echo esc_url( get_permalink( $pages['overview'] ) ); ?>">&larr; <?php esc_html_e( 'Zur Bootswart-Verwaltung', 'verwaltung-boote' ); ?></a></p>
				<?php $this->render_bootswart_table( $section ); ?>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Zeigt nach dem Scannen eines QR-Codes genau ein Boot mit seinen Aktionen.
	 *
	 * @return string
	 */
	public function render_boat_qr_entry() {
		$boat_identifier = isset( $_GET['boot'] ) ? sanitize_key( wp_unslash( $_GET['boot'] ) ) : '';
		$boat            = $this->get_boat_by_identifier( $boat_identifier );
		$boat_id         = $boat ? $boat->ID : 0;

		if ( ! $boat_id || ! $boat || 'vb_boot' !== $boat->post_type || 'publish' !== $boat->post_status ) {
			return '<p class="verwaltung-boote-hinweis">' . esc_html__( 'Der QR-Code verweist auf kein verfügbares Boot.', 'verwaltung-boote' ) . '</p>';
		}

		if ( ! is_user_logged_in() ) {
			$return_url = add_query_arg( 'boot', $boat_id, get_permalink() );
			return sprintf(
				'<p class="verwaltung-boote-hinweis">%s</p>',
				wp_kses_post(
					sprintf(
						__( 'Bitte <a href="%s">anmelden</a>, um dieses Boot zu nutzen.', 'verwaltung-boote' ),
						esc_url( wp_login_url( $return_url ) )
					)
				)
			);
		}

		wp_enqueue_style( 'verwaltung-boote-frontend', plugins_url( 'assets/css/frontend.css', VERWALTUNG_BOOTE_FILE ), array(), VERWALTUNG_BOOTE_VERSION );
		wp_enqueue_script( 'verwaltung-boote-frontend', plugins_url( 'assets/js/frontend.js', VERWALTUNG_BOOTE_FILE ), array(), VERWALTUNG_BOOTE_VERSION, true );

		$active_log           = $this->get_active_log_for_boat( $boat_id );
		$blocking_reservation = $this->get_blocking_reservation( $boat_id );
		$current_user         = get_current_user_id();

		ob_start();
		$this->render_usage_message();
		?>
		<section class="verwaltung-boote-qr-einstieg">
			<h2><?php echo esc_html( get_the_title( $boat_id ) ); ?></h2>
			<dl>
				<div><dt><?php esc_html_e( 'Bootstyp', 'verwaltung-boote' ); ?></dt><dd><?php echo esc_html( $this->get_boat_type( $boat_id ) ); ?></dd></div>
				<div><dt><?php esc_html_e( 'Liegeplatz', 'verwaltung-boote' ); ?></dt><dd><?php echo esc_html( $this->get_boat_berth( $boat_id ) ); ?></dd></div>
			</dl>
			<div class="verwaltung-boote-qr-aktionen">
			<?php if ( $active_log && (int) get_post_meta( $active_log->ID, '_vb_user_id', true ) === $current_user ) : ?>
				<?php $this->render_end_form( $active_log->ID ); ?>
			<?php elseif ( $active_log ) : ?>
				<?php $active_user = get_userdata( (int) get_post_meta( $active_log->ID, '_vb_user_id', true ) ); ?>
				<p><?php echo esc_html( sprintf( __( 'Derzeit in Nutzung von %s.', 'verwaltung-boote' ), $active_user ? $active_user->display_name : __( 'unbekanntem Mitglied', 'verwaltung-boote' ) ) ); ?></p>
			<?php elseif ( $blocking_reservation && (int) get_post_meta( $blocking_reservation->ID, '_vb_user_id', true ) === $current_user && $this->is_reservation_start_allowed( $blocking_reservation->ID ) ) : ?>
				<?php $this->render_reservation_start_form( $blocking_reservation->ID ); ?>
			<?php elseif ( $blocking_reservation ) : ?>
				<?php $reservation_data = $this->get_reservation_data( $blocking_reservation->ID ); ?>
				<p><?php echo esc_html( sprintf( __( 'Reserviert von %s bis ', 'verwaltung-boote' ), $reservation_data['user'] ) ); ?><?php $this->render_browser_datetime( $reservation_data['end_utc'] ); ?></p>
			<?php else : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="verwaltung_boote_start">
					<input type="hidden" name="boot_id" value="<?php echo esc_attr( $boat_id ); ?>">
					<?php wp_nonce_field( 'verwaltung_boote_start_' . $boat_id ); ?>
					<button type="submit"><?php esc_html_e( 'Jetzt nutzen', 'verwaltung-boote' ); ?></button>
				</form>
				<?php $this->render_reservation_form( $boat_id ); ?>
			<?php endif; ?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Rendert die Tabelle eines Verwaltungsbereichs.
	 *
	 * @param string $section Bereich.
	 * @return void
	 */
	private function render_bootswart_table( $section ) {
		if ( 'users' === $section ) {
			$counts = array();
			$logs   = get_posts( array( 'post_type' => 'vb_logbuch', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids' ) );
			foreach ( $logs as $log_id ) {
				$user_id = (int) get_post_meta( $log_id, '_vb_user_id', true );
				if ( $user_id ) {
					$counts[ $user_id ] = isset( $counts[ $user_id ] ) ? $counts[ $user_id ] + 1 : 1;
				}
			}

			$users = array();
			foreach ( $counts as $user_id => $count ) {
				$user    = get_userdata( $user_id );
				$users[] = array( 'name' => $user ? $user->display_name : sprintf( __( 'Gelöschter Nutzer #%d', 'verwaltung-boote' ), $user_id ), 'count' => $count );
			}
			usort( $users, static function ( $first, $second ) { $count_comparison = $second['count'] - $first['count']; return 0 !== $count_comparison ? $count_comparison : strcasecmp( $first['name'], $second['name'] ); } );
			?> <section><h2><?php esc_html_e( 'Alle Nutzer der Boote', 'verwaltung-boote' ); ?></h2><div class="verwaltung-boote-tabellen-scroll"><table><thead><tr><th><?php esc_html_e( 'Nutzer', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Anzahl Nutzungen', 'verwaltung-boote' ); ?></th></tr></thead><tbody><?php foreach ( $users as $user ) : ?><tr><th scope="row"><?php echo esc_html( $user['name'] ); ?></th><td><?php echo esc_html( number_format_i18n( $user['count'] ) ); ?></td></tr><?php endforeach; if ( empty( $users ) ) : ?><tr><td colspan="2"><?php esc_html_e( 'Es sind noch keine Nutzungen vorhanden.', 'verwaltung-boote' ); ?></td></tr><?php endif; ?></tbody></table></div></section><?php
			return;
		}

		if ( 'boats' === $section ) {
			$boats = $this->sort_boats_by_type( get_posts( array( 'post_type' => 'vb_boot', 'post_status' => 'publish', 'posts_per_page' => -1 ) ) );
			?> <section><h2><?php esc_html_e( 'Alle Boote', 'verwaltung-boote' ); ?></h2><div class="verwaltung-boote-tabellen-scroll"><table><thead><tr><th><?php esc_html_e( 'Boot', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Bootstyp', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Liegeplatz', 'verwaltung-boote' ); ?></th></tr></thead><tbody><?php $current_type = ''; foreach ( $boats as $boat ) : $boat_type = $this->get_boat_type( $boat->ID ); if ( $boat_type !== $current_type ) : ?><tr class="verwaltung-boote-gruppe"><th colspan="3" scope="rowgroup"><?php echo esc_html( $boat_type ); ?></th></tr><?php $current_type = $boat_type; endif; ?><tr><td><?php echo esc_html( $boat->post_title ); ?></td><td><?php echo esc_html( $boat_type ); ?></td><td><?php echo esc_html( $this->get_boat_berth( $boat->ID ) ); ?></td></tr><?php endforeach; if ( empty( $boats ) ) : ?><tr><td colspan="3"><?php esc_html_e( 'Keine Boote vorhanden.', 'verwaltung-boote' ); ?></td></tr><?php endif; ?></tbody></table></div></section><?php
			return;
		}

		if ( 'reservations' === $section ) {
			$reservations = get_posts( array( 'post_type' => 'vb_reservierung', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC' ) );
			?>
			<section>
				<h2><?php esc_html_e( 'Alle Reservierungen', 'verwaltung-boote' ); ?></h2>
				<p class="verwaltung-boote-freitext-filter"><label for="verwaltung-boote-reservierungen-filter"><?php esc_html_e( 'Reservierungen filtern', 'verwaltung-boote' ); ?></label><input id="verwaltung-boote-reservierungen-filter" class="verwaltung-boote-freitext-filter-eingabe" type="search" placeholder="<?php echo esc_attr__( 'Freitext suchen …', 'verwaltung-boote' ); ?>" autocomplete="off"></p>
				<div class="verwaltung-boote-tabellen-scroll"><table><thead><tr><th><?php esc_html_e( 'Boot', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Mitglied', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Start', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Ende', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Status', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Aktion', 'verwaltung-boote' ); ?></th></tr></thead><tbody class="verwaltung-boote-freitext-filter-tabelle"><?php foreach ( $reservations as $reservation ) : $data = $this->get_reservation_data( $reservation->ID ); ?><tr class="verwaltung-boote-freitext-filter-zeile"><td><?php echo esc_html( $data['boat'] ); ?></td><td><?php echo esc_html( $data['user'] ); ?></td><td><?php $this->render_browser_datetime( $data['start_utc'] ); ?></td><td><?php $this->render_browser_datetime( $data['end_utc'] ); ?></td><td><?php echo esc_html( $data['status'] ); ?></td><td><?php if ( ! $data['log_id'] && ! $data['cancelled_utc'] && strtotime( $data['end_utc'] . ' UTC' ) >= time() ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="verwaltung_boote_admin_reservierung_loeschen"><input type="hidden" name="reservierung_id" value="<?php echo esc_attr( $reservation->ID ); ?>"><input type="hidden" name="frontend" value="1"><?php wp_nonce_field( 'verwaltung_boote_admin_reservierung_loeschen_' . $reservation->ID ); ?><button type="submit"><?php esc_html_e( 'Stornieren', 'verwaltung-boote' ); ?></button></form><?php else : ?>–<?php endif; ?></td></tr><?php endforeach; if ( empty( $reservations ) ) : ?><tr><td colspan="6"><?php esc_html_e( 'Keine Reservierungen vorhanden.', 'verwaltung-boote' ); ?></td></tr><?php endif; ?><tr class="verwaltung-boote-freitext-filter-kein-treffer" hidden><td colspan="6"><?php esc_html_e( 'Keine passenden Reservierungen gefunden.', 'verwaltung-boote' ); ?></td></tr></tbody></table></div>
			</section>
			<?php
			return;
		}

		if ( 'usages' === $section ) {
			$logs = get_posts( array( 'post_type' => 'vb_logbuch', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC' ) );
			?>
			<section>
				<h2><?php esc_html_e( 'Alle Nutzungen', 'verwaltung-boote' ); ?></h2>
				<p class="verwaltung-boote-freitext-filter">
					<label for="verwaltung-boote-nutzungs-filter"><?php esc_html_e( 'Nutzungen filtern', 'verwaltung-boote' ); ?></label>
					<input id="verwaltung-boote-nutzungs-filter" class="verwaltung-boote-freitext-filter-eingabe" type="search" placeholder="<?php echo esc_attr__( 'Freitext suchen …', 'verwaltung-boote' ); ?>" autocomplete="off">
				</p>
				<div class="verwaltung-boote-tabellen-scroll">
					<table>
						<thead><tr><th><?php esc_html_e( 'Boot', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Mitglied', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Ausleihe', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Rückgabe', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Reservierung', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Aktion', 'verwaltung-boote' ); ?></th></tr></thead>
						<tbody class="verwaltung-boote-freitext-filter-tabelle">
							<?php foreach ( $logs as $log ) : $data = $this->get_logbook_data( $log->ID ); ?>
								<tr class="verwaltung-boote-freitext-filter-zeile"><td><?php echo esc_html( $data['boat'] ); ?></td><td><?php echo esc_html( $data['user'] ); ?></td><td><?php $this->render_browser_datetime( $data['start_utc'] ); ?></td><td><?php $this->render_browser_datetime( $data['end_utc'], __( 'Nutzung läuft', 'verwaltung-boote' ) ); ?></td><td><?php echo esc_html( $data['reservation_label'] ); ?></td><td><?php if ( ! $data['end_utc'] ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="verwaltung_boote_admin_nutzung_beenden"><input type="hidden" name="log_id" value="<?php echo esc_attr( $log->ID ); ?>"><input type="hidden" name="frontend" value="1"><?php wp_nonce_field( 'verwaltung_boote_admin_nutzung_beenden_' . $log->ID ); ?><button type="submit"><?php esc_html_e( 'Nutzung beenden', 'verwaltung-boote' ); ?></button></form><?php else : ?>–<?php endif; ?></td></tr>
							<?php endforeach; ?>
							<?php if ( empty( $logs ) ) : ?><tr><td colspan="6"><?php esc_html_e( 'Keine Nutzungen vorhanden.', 'verwaltung-boote' ); ?></td></tr><?php endif; ?>
							<tr class="verwaltung-boote-freitext-filter-kein-treffer" hidden><td colspan="6"><?php esc_html_e( 'Keine passenden Nutzungen gefunden.', 'verwaltung-boote' ); ?></td></tr>
						</tbody>
					</table>
				</div>
			</section>
			<?php
			return;
		}

		$damages = get_posts( array( 'post_type' => 'vb_schaden', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC' ) );
		usort( $damages, static function ( $first, $second ) { $first_resolved = (bool) get_post_meta( $first->ID, '_vb_behoben_am', true ); $second_resolved = (bool) get_post_meta( $second->ID, '_vb_behoben_am', true ); return $first_resolved !== $second_resolved ? ( $first_resolved ? 1 : -1 ) : strcmp( $second->post_date_gmt, $first->post_date_gmt ); } );
		?>
		<section>
			<h2><?php esc_html_e( 'Alle Bootsschäden', 'verwaltung-boote' ); ?></h2>
			<p class="verwaltung-boote-freitext-filter"><label for="verwaltung-boote-schaeden-filter"><?php esc_html_e( 'Bootsschäden filtern', 'verwaltung-boote' ); ?></label><input id="verwaltung-boote-schaeden-filter" class="verwaltung-boote-freitext-filter-eingabe" type="search" placeholder="<?php echo esc_attr__( 'Freitext suchen …', 'verwaltung-boote' ); ?>" autocomplete="off"></p>
			<div class="verwaltung-boote-tabellen-scroll"><table><thead><tr><th><?php esc_html_e( 'Boot', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Ausleihender', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Gemeldet am', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Schwere', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Kommentar', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Status', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Aktion', 'verwaltung-boote' ); ?></th></tr></thead><tbody class="verwaltung-boote-freitext-filter-tabelle"><?php foreach ( $damages as $damage ) : $data = $this->get_damage_data( $damage->ID ); ?><tr class="verwaltung-boote-freitext-filter-zeile"><td><?php echo esc_html( $data['boat'] ); ?></td><td><?php echo esc_html( $data['user'] ); ?></td><td><?php $this->render_browser_datetime( $data['reported_utc'] ); ?></td><td><?php echo esc_html( $data['severity'] ); ?></td><td><?php echo nl2br( esc_html( $data['comment'] ) ); ?></td><td><?php echo esc_html( $data['resolved_utc'] ? __( 'Behoben', 'verwaltung-boote' ) : __( 'Offen', 'verwaltung-boote' ) ); ?></td><td><?php if ( ! $data['resolved_utc'] ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="verwaltung_boote_schaden_beheben"><input type="hidden" name="damage_id" value="<?php echo esc_attr( $damage->ID ); ?>"><input type="hidden" name="frontend" value="1"><?php wp_nonce_field( 'verwaltung_boote_schaden_beheben_' . $damage->ID ); ?><button type="submit"><?php esc_html_e( 'Als behoben markieren', 'verwaltung-boote' ); ?></button></form><?php else : ?><?php $this->render_browser_datetime( $data['resolved_utc'] ); ?><?php endif; ?></td></tr><?php endforeach; if ( empty( $damages ) ) : ?><tr><td colspan="7"><?php esc_html_e( 'Keine Bootsschäden vorhanden.', 'verwaltung-boote' ); ?></td></tr><?php endif; ?><tr class="verwaltung-boote-freitext-filter-kein-treffer" hidden><td colspan="7"><?php esc_html_e( 'Keine passenden Bootsschäden gefunden.', 'verwaltung-boote' ); ?></td></tr></tbody></table></div>
		</section>
		<?php
	}

	/**
	 * Zeigt die Bootswart-Übersicht.
	 *
	 * @return string
	 */
	public function render_bootswart_dashboard() {
		return $this->render_bootswart_section( 'overview' );

		if ( ! $this->is_bootswart() ) {
			return '<p>' . esc_html__( 'Diese Ansicht ist ausschließlich für den Bootswart bestimmt.', 'verwaltung-boote' ) . '</p>';
		}

		wp_enqueue_style( 'verwaltung-boote-frontend', plugins_url( 'assets/css/frontend.css', VERWALTUNG_BOOTE_FILE ), array(), VERWALTUNG_BOOTE_VERSION );
		wp_enqueue_script( 'verwaltung-boote-frontend', plugins_url( 'assets/js/frontend.js', VERWALTUNG_BOOTE_FILE ), array(), VERWALTUNG_BOOTE_VERSION, true );

		$boats        = $this->sort_boats_by_type( get_posts( array( 'post_type' => 'vb_boot', 'post_status' => 'publish', 'posts_per_page' => -1 ) ) );
		$reservations = get_posts( array( 'post_type' => 'vb_reservierung', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC' ) );
		$logs         = get_posts( array( 'post_type' => 'vb_logbuch', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC' ) );
		$damages      = get_posts( array( 'post_type' => 'vb_schaden', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC' ) );
		usort(
			$damages,
			static function ( $first, $second ) {
				$first_resolved  = (bool) get_post_meta( $first->ID, '_vb_behoben_am', true );
				$second_resolved = (bool) get_post_meta( $second->ID, '_vb_behoben_am', true );

				if ( $first_resolved !== $second_resolved ) {
					return $first_resolved ? 1 : -1;
				}

				return strcmp( $second->post_date_gmt, $first->post_date_gmt );
			}
		);

		ob_start();
		$this->render_bootswart_message();
		?>
		<div class="verwaltung-boote-bootswart">
			<section>
				<h2><?php esc_html_e( 'Alle Boote', 'verwaltung-boote' ); ?></h2>
				<div class="verwaltung-boote-tabellen-scroll"><table><thead><tr>
					<th><?php esc_html_e( 'Boot', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Bootstyp', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Liegeplatz', 'verwaltung-boote' ); ?></th>
				</tr></thead><tbody>
				<?php $current_type = ''; ?>
				<?php foreach ( $boats as $boat ) : ?>
					<?php $boat_type = $this->get_boat_type( $boat->ID ); ?>
					<?php if ( $boat_type !== $current_type ) : ?>
						<tr class="verwaltung-boote-gruppe"><th colspan="3" scope="rowgroup"><?php echo esc_html( $boat_type ); ?></th></tr>
						<?php $current_type = $boat_type; ?>
					<?php endif; ?>
					<tr><td><?php echo esc_html( $boat->post_title ); ?></td><td><?php echo esc_html( $this->get_term_names( $boat->ID, 'vb_bootstyp' ) ); ?></td><td><?php echo esc_html( $this->get_boat_berth( $boat->ID ) ); ?></td></tr>
				<?php endforeach; ?>
				<?php if ( empty( $boats ) ) : ?><tr><td colspan="3"><?php esc_html_e( 'Keine Boote vorhanden.', 'verwaltung-boote' ); ?></td></tr><?php endif; ?>
				</tbody></table></div>
			</section>

			<section>
				<h2><?php esc_html_e( 'Alle Reservierungen', 'verwaltung-boote' ); ?></h2>
				<div class="verwaltung-boote-tabellen-scroll"><table><thead><tr>
					<th><?php esc_html_e( 'Boot', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Mitglied', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Start', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Ende', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Status', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Aktion', 'verwaltung-boote' ); ?></th>
				</tr></thead><tbody>
				<?php foreach ( $reservations as $reservation ) : $data = $this->get_reservation_data( $reservation->ID ); ?>
					<tr><td><?php echo esc_html( $data['boat'] ); ?></td><td><?php echo esc_html( $data['user'] ); ?></td><td><?php $this->render_browser_datetime( $data['start_utc'] ); ?></td><td><?php $this->render_browser_datetime( $data['end_utc'] ); ?></td><td><?php echo esc_html( $data['status'] ); ?></td><td>
						<?php if ( ! $data['log_id'] && ! $data['cancelled_utc'] && strtotime( $data['end_utc'] . ' UTC' ) >= time() ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="verwaltung_boote_admin_reservierung_loeschen"><input type="hidden" name="reservierung_id" value="<?php echo esc_attr( $reservation->ID ); ?>"><input type="hidden" name="frontend" value="1"><?php wp_nonce_field( 'verwaltung_boote_admin_reservierung_loeschen_' . $reservation->ID ); ?><button type="submit"><?php esc_html_e( 'Stornieren', 'verwaltung-boote' ); ?></button></form><?php else : ?>–<?php endif; ?>
					</td></tr>
				<?php endforeach; ?>
				<?php if ( empty( $reservations ) ) : ?><tr><td colspan="6"><?php esc_html_e( 'Keine Reservierungen vorhanden.', 'verwaltung-boote' ); ?></td></tr><?php endif; ?>
				</tbody></table></div>
			</section>

			<section>
				<h2><?php esc_html_e( 'Alle Nutzungen', 'verwaltung-boote' ); ?></h2>
				<div class="verwaltung-boote-tabellen-scroll"><table><thead><tr>
					<th><?php esc_html_e( 'Boot', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Mitglied', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Ausleihe', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Rückgabe', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Reservierung', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Aktion', 'verwaltung-boote' ); ?></th>
				</tr></thead><tbody>
				<?php foreach ( $logs as $log ) : $data = $this->get_logbook_data( $log->ID ); ?>
					<tr><td><?php echo esc_html( $data['boat'] ); ?></td><td><?php echo esc_html( $data['user'] ); ?></td><td><?php $this->render_browser_datetime( $data['start_utc'] ); ?></td><td><?php $this->render_browser_datetime( $data['end_utc'], __( 'Nutzung läuft', 'verwaltung-boote' ) ); ?></td><td><?php echo esc_html( $data['reservation_label'] ); ?></td><td>
					<?php if ( ! $data['end_utc'] ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="verwaltung_boote_admin_nutzung_beenden"><input type="hidden" name="log_id" value="<?php echo esc_attr( $log->ID ); ?>"><input type="hidden" name="frontend" value="1"><?php wp_nonce_field( 'verwaltung_boote_admin_nutzung_beenden_' . $log->ID ); ?><button type="submit"><?php esc_html_e( 'Nutzung beenden', 'verwaltung-boote' ); ?></button></form><?php else : ?>–<?php endif; ?>
					</td></tr>
				<?php endforeach; ?>
				<?php if ( empty( $logs ) ) : ?><tr><td colspan="6"><?php esc_html_e( 'Keine Nutzungen vorhanden.', 'verwaltung-boote' ); ?></td></tr><?php endif; ?>
				</tbody></table></div>
			</section>

			<section>
				<h2><?php esc_html_e( 'Alle Bootsschäden', 'verwaltung-boote' ); ?></h2>
				<div class="verwaltung-boote-tabellen-scroll"><table><thead><tr>
					<th><?php esc_html_e( 'Boot', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Ausleihender', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Gemeldet am', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Schwere', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Kommentar', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Status', 'verwaltung-boote' ); ?></th><th><?php esc_html_e( 'Aktion', 'verwaltung-boote' ); ?></th>
				</tr></thead><tbody>
				<?php foreach ( $damages as $damage ) : $data = $this->get_damage_data( $damage->ID ); ?>
					<tr><td><?php echo esc_html( $data['boat'] ); ?></td><td><?php echo esc_html( $data['user'] ); ?></td><td><?php $this->render_browser_datetime( $data['reported_utc'] ); ?></td><td><?php echo esc_html( $data['severity'] ); ?></td><td><?php echo nl2br( esc_html( $data['comment'] ) ); ?></td><td><?php echo esc_html( $data['resolved_utc'] ? __( 'Behoben', 'verwaltung-boote' ) : __( 'Offen', 'verwaltung-boote' ) ); ?></td><td>
					<?php if ( ! $data['resolved_utc'] ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="verwaltung_boote_schaden_beheben"><input type="hidden" name="damage_id" value="<?php echo esc_attr( $damage->ID ); ?>"><input type="hidden" name="frontend" value="1"><?php wp_nonce_field( 'verwaltung_boote_schaden_beheben_' . $damage->ID ); ?><button type="submit"><?php esc_html_e( 'Als behoben markieren', 'verwaltung-boote' ); ?></button></form><?php else : ?><?php $this->render_browser_datetime( $data['resolved_utc'] ); ?><?php endif; ?>
					</td></tr>
				<?php endforeach; ?>
				<?php if ( empty( $damages ) ) : ?><tr><td colspan="7"><?php esc_html_e( 'Keine Bootsschäden vorhanden.', 'verwaltung-boote' ); ?></td></tr><?php endif; ?>
				</tbody></table></div>
			</section>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/** Zeigt Rueckmeldungen auf der Bootswart-Seite. */
	private function render_bootswart_message() {
		$key = isset( $_GET['vb_bootswart_meldung'] ) ? sanitize_key( wp_unslash( $_GET['vb_bootswart_meldung'] ) ) : '';
		$messages = array( 'nutzung_beendet' => __( 'Die Nutzung wurde beendet.', 'verwaltung-boote' ), 'schaden_behoben' => __( 'Der Schaden wurde als behoben markiert.', 'verwaltung-boote' ), 'reservierung_geloescht' => __( 'Die Reservierung wurde storniert und bleibt in der Historie erhalten.', 'verwaltung-boote' ), 'aktion_fehler' => __( 'Die Aktion konnte nicht ausgeführt werden.', 'verwaltung-boote' ) );
		if ( isset( $messages[ $key ] ) ) {
			echo '<p class="verwaltung-boote-meldung" role="status">' . esc_html( $messages[ $key ] ) . '</p>';
		}
	}

	public function render_boat_list() {
		if ( ! is_user_logged_in() ) {
			return sprintf(
				'<p class="verwaltung-boote-hinweis">%s</p>',
				wp_kses_post(
					sprintf(
						/* translators: %s: URL zur Anmeldung. */
						__( 'Bitte <a href="%s">anmelden</a>, um die Vereinsboote zu sehen.', 'verwaltung-boote' ),
						esc_url( wp_login_url( get_permalink() ) )
					)
				)
			);
		}

		wp_enqueue_style(
			'verwaltung-boote-frontend',
			plugins_url( 'assets/css/frontend.css', VERWALTUNG_BOOTE_FILE ),
			array(),
			VERWALTUNG_BOOTE_VERSION
		);
		wp_enqueue_script(
			'verwaltung-boote-frontend',
			plugins_url( 'assets/js/frontend.js', VERWALTUNG_BOOTE_FILE ),
			array(),
			VERWALTUNG_BOOTE_VERSION,
			true
		);

		$active_logs    = $this->get_active_logs();
		$active_by_boat = array();
		$my_active_logs = array();
		$current_user   = get_current_user_id();
		$user_history   = $this->get_user_logbook( $current_user );
		$user_reservations = $this->get_user_active_reservations( $current_user );

		foreach ( $active_logs as $active_log ) {
			$active_boat_id = (int) get_post_meta( $active_log->ID, '_vb_boot_id', true );
			$active_user_id = (int) get_post_meta( $active_log->ID, '_vb_user_id', true );
			if ( $active_boat_id ) {
				$active_by_boat[ $active_boat_id ] = $active_log;
			}
			if ( $current_user === $active_user_id ) {
				$my_active_logs[] = $active_log;
			}
		}

		$query = new \WP_Query(
			array(
				'post_type'      => 'vb_boot',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);

		if ( ! $query->have_posts() ) {
			return '<p>' . esc_html__( 'Aktuell sind noch keine Boote eingetragen.', 'verwaltung-boote' ) . '</p>';
		}

		$boats = $this->sort_boats_by_type( $query->posts );

		ob_start();
		$this->render_usage_message();
		$this->render_current_usage( $my_active_logs );
		$this->render_user_reservations( $user_reservations );
		?>
		<h2 class="verwaltung-boote-listen-ueberschrift"><?php esc_html_e( 'Liste der Boote', 'verwaltung-boote' ); ?></h2>
		<div class="verwaltung-boote-liste">
			<table>
				<thead><tr>
					<th scope="col"><?php esc_html_e( 'Boot', 'verwaltung-boote' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Bootstyp', 'verwaltung-boote' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Liegeplatz', 'verwaltung-boote' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Nutzung', 'verwaltung-boote' ); ?></th>
				</tr></thead>
				<tbody>
				<?php $current_type = ''; ?>
				<?php foreach ( $boats as $boat ) : ?>
					<?php global $post; $post = $boat; setup_postdata( $post ); ?>
					<?php $boat_type = $this->get_boat_type( $boat->ID ); ?>
					<?php if ( $boat_type !== $current_type ) : ?>
						<tr class="verwaltung-boote-gruppe"><th colspan="4" scope="rowgroup"><?php echo esc_html( $boat_type ); ?></th></tr>
						<?php $current_type = $boat_type; ?>
					<?php endif; ?>
					<?php
					$active_log           = isset( $active_by_boat[ get_the_ID() ] ) ? $active_by_boat[ get_the_ID() ] : null;
					$blocking_reservation = $this->get_blocking_reservation( get_the_ID() );
					$today_reservations   = $this->get_today_reservations( get_the_ID() );
					?>
					<tr>
						<th scope="row"><?php echo esc_html( get_the_title() ); ?></th>
						<td><?php echo esc_html( $this->get_term_names( get_the_ID(), 'vb_bootstyp' ) ); ?></td>
						<td><?php echo esc_html( $this->get_boat_berth( get_the_ID() ) ); ?></td>
						<td>
						<?php if ( ! $active_log && ! $blocking_reservation ) : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<input type="hidden" name="action" value="verwaltung_boote_start">
								<input type="hidden" name="boot_id" value="<?php echo esc_attr( get_the_ID() ); ?>">
								<?php wp_nonce_field( 'verwaltung_boote_start_' . get_the_ID() ); ?>
								<button type="submit"><?php esc_html_e( 'Jetzt nutzen', 'verwaltung-boote' ); ?></button>
							</form>
						<?php elseif ( $active_log && (int) get_post_meta( $active_log->ID, '_vb_user_id', true ) === $current_user ) : ?>
							<?php $this->render_end_form( $active_log->ID ); ?>
						<?php elseif ( $active_log ) : ?>
							<?php $active_user = get_userdata( (int) get_post_meta( $active_log->ID, '_vb_user_id', true ) ); ?>
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: Anzeigename des ausleihenden Mitglieds. */
									__( 'Derzeit in Nutzung von %s', 'verwaltung-boote' ),
									$active_user ? $active_user->display_name : __( 'unbekanntem Mitglied', 'verwaltung-boote' )
								)
							);
							?>
						<?php elseif ( (int) get_post_meta( $blocking_reservation->ID, '_vb_user_id', true ) === $current_user && $this->is_reservation_start_allowed( $blocking_reservation->ID ) ) : ?>
							<?php $this->render_reservation_start_form( $blocking_reservation->ID ); ?>
						<?php else : ?>
							<?php $reservation_data = $this->get_reservation_data( $blocking_reservation->ID ); ?>
							<?php echo esc_html( sprintf( __( 'Reserviert von %s bis ', 'verwaltung-boote' ), $reservation_data['user'] ) ); ?><?php $this->render_browser_datetime( $reservation_data['end_utc'] ); ?>
						<?php endif; ?>
						<?php $this->render_today_reservations( $today_reservations, $blocking_reservation ? $blocking_reservation->ID : 0 ); ?>
						<?php $this->render_reservation_form( get_the_ID() ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		$this->render_user_history( $user_history );
		wp_reset_postdata();

		return (string) ob_get_clean();
	}

	/**
	 * Liefert alle noch nicht beendeten Logbucheintraege.
	 *
	 * @return \WP_Post[]
	 */
	private function get_active_logs() {
		return get_posts(
			array(
				'post_type'      => 'vb_logbuch',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'ASC',
				'meta_query'     => array(
					array(
						'key'     => '_vb_end',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);
	}

	/**
	 * Liefert ausschliesslich die Logbucheintraege eines Benutzers.
	 *
	 * @param int $user_id Benutzer-ID.
	 * @return \WP_Post[]
	 */
	private function get_user_logbook( $user_id ) {
		return get_posts(
			array(
				'post_type'      => 'vb_logbuch',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'meta_query'     => array(
					array(
						'key'     => '_vb_user_id',
						'value'   => absint( $user_id ),
						'compare' => '=',
						'type'    => 'NUMERIC',
					),
				),
			)
		);
	}

	/**
	 * Sucht einen laufenden Eintrag fuer ein Boot.
	 *
	 * @param int $boat_id Boot-ID.
	 * @return \WP_Post|null
	 */
	private function get_active_log_for_boat( $boat_id ) {
		$logs = get_posts(
			array(
				'post_type'      => 'vb_logbuch',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'all',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => '_vb_boot_id',
						'value'   => $boat_id,
						'compare' => '=',
						'type'    => 'NUMERIC',
					),
					array(
						'key'     => '_vb_end',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);

		return $logs ? reset( $logs ) : null;
	}

	/**
	 * Liefert die Reservierung, die ein Boot jetzt blockiert.
	 *
	 * Die Blockierung beginnt eine Stunde vor dem Reservierungsstart und endet
	 * mit dem reservierten Endzeitpunkt.
	 *
	 * @param int $boat_id Boot-ID.
	 * @return \WP_Post|null
	 */
	private function get_blocking_reservation( $boat_id ) {
		$now         = current_time( 'mysql', true );
		$one_hour_on = gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS );
		$items       = get_posts(
			array(
				'post_type'      => 'vb_reservierung',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'orderby'        => 'meta_value',
				'meta_key'       => '_vb_reservierung_start',
				'order'          => 'ASC',
				'meta_query'     => array(
					'relation' => 'AND',
					array( 'key' => '_vb_boot_id', 'value' => $boat_id, 'type' => 'NUMERIC' ),
					array( 'key' => '_vb_reservierung_start', 'value' => $one_hour_on, 'compare' => '<=', 'type' => 'DATETIME' ),
					array( 'key' => '_vb_reservierung_ende', 'value' => $now, 'compare' => '>=', 'type' => 'DATETIME' ),
					array( 'key' => '_vb_started_log_id', 'compare' => 'NOT EXISTS' ),
					array( 'key' => '_vb_storniert_am', 'compare' => 'NOT EXISTS' ),
				),
			)
		);

		return $items ? reset( $items ) : null;
	}

	/**
	 * Liefert noch relevante Reservierungen des heutigen Tages fuer ein Boot.
	 *
	 * @param int $boat_id Boot-ID.
	 * @return \WP_Post[]
	 */
	private function get_today_reservations( $boat_id ) {
		$day_start_local = current_datetime()->setTime( 0, 0, 0 );
		$day_end_local   = $day_start_local->modify( '+1 day' );
		$utc             = new \DateTimeZone( 'UTC' );
		$day_start       = $day_start_local->setTimezone( $utc )->format( 'Y-m-d H:i:s' );
		$day_end         = $day_end_local->setTimezone( $utc )->format( 'Y-m-d H:i:s' );

		return get_posts(
			array(
				'post_type'      => 'vb_reservierung',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'meta_value',
				'meta_key'       => '_vb_reservierung_start',
				'order'          => 'ASC',
				'meta_query'     => array(
					'relation' => 'AND',
					array( 'key' => '_vb_boot_id', 'value' => $boat_id, 'type' => 'NUMERIC' ),
					array( 'key' => '_vb_reservierung_start', 'value' => $day_end, 'compare' => '<', 'type' => 'DATETIME' ),
					array( 'key' => '_vb_reservierung_ende', 'value' => $day_start, 'compare' => '>=', 'type' => 'DATETIME' ),
					array( 'key' => '_vb_reservierung_ende', 'value' => current_time( 'mysql', true ), 'compare' => '>=', 'type' => 'DATETIME' ),
					array( 'key' => '_vb_started_log_id', 'compare' => 'NOT EXISTS' ),
					array( 'key' => '_vb_storniert_am', 'compare' => 'NOT EXISTS' ),
				),
			)
		);
	}

	/**
	 * Prueft das Startfenster von einer Stunde vor bis 45 Minuten nach Beginn.
	 *
	 * @param int $reservation_id Reservierungs-ID.
	 * @return bool
	 */
	private function is_reservation_start_allowed( $reservation_id ) {
		$start = (string) get_post_meta( $reservation_id, '_vb_reservierung_start', true );
		if ( ! $start ) {
			return false;
		}

		$start_timestamp = strtotime( $start . ' UTC' );
		$now             = time();

		return $now >= ( $start_timestamp - HOUR_IN_SECONDS ) && $now <= ( $start_timestamp + ( 45 * MINUTE_IN_SECONDS ) );
	}

	/**
	 * Liefert die aktiven, noch nicht genutzten Reservierungen eines Mitglieds.
	 *
	 * @param int $user_id Benutzer-ID.
	 * @return \WP_Post[]
	 */
	private function get_user_active_reservations( $user_id ) {
		return get_posts(
			array(
				'post_type'      => 'vb_reservierung',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'meta_value',
				'meta_key'       => '_vb_reservierung_start',
				'order'          => 'ASC',
				'meta_query'     => array(
					'relation' => 'AND',
					array( 'key' => '_vb_user_id', 'value' => absint( $user_id ), 'type' => 'NUMERIC' ),
					array( 'key' => '_vb_reservierung_ende', 'value' => current_time( 'mysql', true ), 'compare' => '>=', 'type' => 'DATETIME' ),
					array( 'key' => '_vb_started_log_id', 'compare' => 'NOT EXISTS' ),
					array( 'key' => '_vb_storniert_am', 'compare' => 'NOT EXISTS' ),
				),
			)
		);
	}

	/**
	 * Bereitet eine Reservierung fuer Frontend und Verwaltung auf.
	 *
	 * @param int $reservation_id Reservierungs-ID.
	 * @return array<string,mixed>
	 */
	private function get_reservation_data( $reservation_id ) {
		$boat_id  = (int) get_post_meta( $reservation_id, '_vb_boot_id', true );
		$user_id  = (int) get_post_meta( $reservation_id, '_vb_user_id', true );
		$user     = get_userdata( $user_id );
		$start    = (string) get_post_meta( $reservation_id, '_vb_reservierung_start', true );
		$end      = (string) get_post_meta( $reservation_id, '_vb_reservierung_ende', true );
		$log_id   = (int) get_post_meta( $reservation_id, '_vb_started_log_id', true );
		$cancelled_utc = (string) get_post_meta( $reservation_id, '_vb_storniert_am', true );
		$completed_utc = (string) get_post_meta( $reservation_id, '_vb_nutzung_beendet_am', true );
		$cancelled_by_id = (int) get_post_meta( $reservation_id, '_vb_storniert_von', true );
		$cancelled_by = $cancelled_by_id ? get_userdata( $cancelled_by_id ) : false;

		if ( $completed_utc ) {
			$status = __( 'Nutzung beendet', 'verwaltung-boote' );
		} elseif ( $log_id ) {
			$status = __( 'Nutzung gestartet', 'verwaltung-boote' );
		} elseif ( $cancelled_utc ) {
			$status = __( 'Storniert', 'verwaltung-boote' );
		} elseif ( $end && strtotime( $end . ' UTC' ) < time() ) {
			$status = __( 'Abgelaufen', 'verwaltung-boote' );
		} else {
			$status = __( 'Reserviert', 'verwaltung-boote' );
		}

		return array(
			'boat_id'   => $boat_id,
			'boat'      => $boat_id ? get_the_title( $boat_id ) : '–',
			'user_id'   => $user_id,
			'user'      => $user ? $user->display_name : '–',
			'start'     => $this->format_logbook_time( $start, '–' ),
			'end'       => $this->format_logbook_time( $end, '–' ),
			'start_utc' => $start,
			'end_utc'   => $end,
			'log_id'    => $log_id,
			'status'    => $status,
			'cancelled_utc' => $cancelled_utc,
			'cancelled_by'  => $cancelled_by ? $cancelled_by->display_name : '–',
			'completed_utc' => $completed_utc,
			'can_start' => $this->is_reservation_start_allowed( $reservation_id ),
		);
	}

	/**
	 * Zeigt die aktuell vom Mitglied genutzten Boote.
	 *
	 * @param \WP_Post[] $logs Laufende eigene Eintraege.
	 * @return void
	 */
	private function render_current_usage( $logs ) {
		?>
		<section class="verwaltung-boote-aktuell">
			<h2><?php esc_html_e( 'Meine aktuelle Nutzung', 'verwaltung-boote' ); ?></h2>
			<?php if ( empty( $logs ) ) : ?>
				<p><?php esc_html_e( 'Du nutzt momentan kein Boot.', 'verwaltung-boote' ); ?></p>
			<?php else : ?>
				<ul>
				<?php foreach ( $logs as $log ) : ?>
					<?php $boat_id = (int) get_post_meta( $log->ID, '_vb_boot_id', true ); ?>
					<li>
						<strong><?php echo esc_html( get_the_title( $boat_id ) ); ?></strong>
						<?php $this->render_end_form( $log->ID ); ?>
					</li>
				<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * Zeigt die eigene Ausleihhistorie ohne Bearbeitungsfunktionen.
	 *
	 * @param \WP_Post[] $logs Eigene Logbucheintraege.
	 * @return void
	 */
	private function render_user_history( $logs ) {
		?>
		<section class="verwaltung-boote-historie">
			<h2><?php esc_html_e( 'Meine Ausleihhistorie', 'verwaltung-boote' ); ?></h2>
			<?php if ( empty( $logs ) ) : ?>
				<p><?php esc_html_e( 'Es sind noch keine Ausleihvorgänge vorhanden.', 'verwaltung-boote' ); ?></p>
			<?php else : ?>
				<div class="verwaltung-boote-tabellen-scroll">
					<table>
						<thead><tr>
							<th scope="col"><?php esc_html_e( 'Boot', 'verwaltung-boote' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Ausgeliehen', 'verwaltung-boote' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Zurückgegeben', 'verwaltung-boote' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Reservierung', 'verwaltung-boote' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Schaden', 'verwaltung-boote' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Schadensgrad', 'verwaltung-boote' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Beschreibung', 'verwaltung-boote' ); ?></th>
						</tr></thead>
						<tbody>
						<?php foreach ( $logs as $log ) : ?>
							<?php $data = $this->get_logbook_data( $log->ID ); ?>
							<tr>
								<th scope="row"><?php echo esc_html( $data['boat'] ); ?></th>
								<td><?php $this->render_browser_datetime( $data['start_utc'] ); ?></td>
								<td><?php $this->render_browser_datetime( $data['end_utc'], __( 'Nutzung läuft', 'verwaltung-boote' ) ); ?></td>
								<td><?php echo esc_html( $data['reservation_label'] ); ?></td>
								<td><?php echo esc_html( $data['damage'] ); ?></td>
								<td><?php echo esc_html( $data['severity'] ); ?></td>
								<td><?php echo nl2br( esc_html( $data['description'] ) ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * Zeigt die aktiven Reservierungen des angemeldeten Mitglieds.
	 *
	 * @param \WP_Post[] $reservations Reservierungen.
	 * @return void
	 */
	private function render_user_reservations( $reservations ) {
		?>
		<section class="verwaltung-boote-reservierungen">
			<h2><?php esc_html_e( 'Meine aktiven Reservierungen', 'verwaltung-boote' ); ?></h2>
			<?php if ( empty( $reservations ) ) : ?>
				<p><?php esc_html_e( 'Du hast derzeit keine aktive Reservierung.', 'verwaltung-boote' ); ?></p>
			<?php else : ?>
				<div class="verwaltung-boote-tabellen-scroll">
					<table>
						<thead><tr>
							<th scope="col"><?php esc_html_e( 'Boot', 'verwaltung-boote' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Start', 'verwaltung-boote' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Ende', 'verwaltung-boote' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Aktionen', 'verwaltung-boote' ); ?></th>
						</tr></thead>
						<tbody>
						<?php foreach ( $reservations as $reservation ) : ?>
							<?php $data = $this->get_reservation_data( $reservation->ID ); ?>
							<tr>
								<th scope="row"><?php echo esc_html( $data['boat'] ); ?></th>
								<td><?php $this->render_browser_datetime( $data['start_utc'] ); ?></td>
								<td><?php $this->render_browser_datetime( $data['end_utc'] ); ?></td>
								<td>
									<?php if ( $data['can_start'] ) : ?>
										<?php $this->render_reservation_start_form( $reservation->ID ); ?>
									<?php else : ?>
										<p><?php esc_html_e( 'Start ab einer Stunde vor Reservierungsbeginn möglich.', 'verwaltung-boote' ); ?></p>
									<?php endif; ?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<input type="hidden" name="action" value="verwaltung_boote_reservierung_loeschen">
										<input type="hidden" name="reservierung_id" value="<?php echo esc_attr( $reservation->ID ); ?>">
										<?php wp_nonce_field( 'verwaltung_boote_reservierung_loeschen_' . $reservation->ID ); ?>
										<button type="submit"><?php esc_html_e( 'Reservierung stornieren', 'verwaltung-boote' ); ?></button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * Zeigt heutige Reservierungen direkt beim Boot.
	 *
	 * @param \WP_Post[] $reservations          Heutige Reservierungen.
	 * @param int        $already_displayed_id Bereits als blockierend angezeigte ID.
	 * @return void
	 */
	private function render_today_reservations( $reservations, $already_displayed_id ) {
		$visible = array_filter(
			$reservations,
			static function ( $reservation ) use ( $already_displayed_id ) {
				return (int) $reservation->ID !== (int) $already_displayed_id;
			}
		);

		if ( empty( $visible ) ) {
			return;
		}
		?>
		<div class="verwaltung-boote-heute-reserviert">
			<strong><?php esc_html_e( 'Heute reserviert:', 'verwaltung-boote' ); ?></strong>
			<ul>
			<?php foreach ( $visible as $reservation ) : ?>
				<?php
				$data      = $this->get_reservation_data( $reservation->ID );
				$start_utc = (string) get_post_meta( $reservation->ID, '_vb_reservierung_start', true );
				$end_utc   = (string) get_post_meta( $reservation->ID, '_vb_reservierung_ende', true );
				?>
				<li><?php echo esc_html( $data['user'] ); ?>, <?php $this->render_browser_datetime( $start_utc, '–', 'time' ); ?>–<?php $this->render_browser_datetime( $end_utc, '–', 'time' ); ?></li>
			<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Zeigt das Formular zum Reservieren eines Boots.
	 *
	 * @param int $boat_id Boot-ID.
	 * @return void
	 */
	private function render_reservation_form( $boat_id ) {
		$start = current_datetime()->modify( '+5 minutes' );
		$end   = $start->modify( '+1 hour' );
		?>
		<details class="verwaltung-boote-reservieren-bereich">
			<summary><?php esc_html_e( 'Jetzt reservieren', 'verwaltung-boote' ); ?></summary>
			<form class="verwaltung-boote-reservieren" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="verwaltung_boote_reservieren">
				<input type="hidden" name="boot_id" value="<?php echo esc_attr( $boat_id ); ?>">
				<?php wp_nonce_field( 'verwaltung_boote_reservieren_' . $boat_id ); ?>
				<label>
					<?php esc_html_e( 'Reservierung beginnt', 'verwaltung-boote' ); ?>
					<input class="verwaltung-boote-reservierung-start" type="datetime-local" name="reservierung_start" value="<?php echo esc_attr( $start->format( 'Y-m-d\TH:i' ) ); ?>" min="<?php echo esc_attr( current_datetime()->format( 'Y-m-d\TH:i' ) ); ?>" required>
				</label>
				<label>
					<?php esc_html_e( 'Reservierung endet', 'verwaltung-boote' ); ?>
					<input class="verwaltung-boote-reservierung-ende" type="datetime-local" name="reservierung_ende" value="<?php echo esc_attr( $end->format( 'Y-m-d\TH:i' ) ); ?>" required>
				</label>
				<button type="submit"><?php esc_html_e( 'Reservierung speichern', 'verwaltung-boote' ); ?></button>
			</form>
		</details>
		<?php
	}

	/**
	 * Zeigt das Formular zum Starten einer Reservierung.
	 *
	 * @param int $reservation_id Reservierungs-ID.
	 * @return void
	 */
	private function render_reservation_start_form( $reservation_id ) {
		$data = $this->get_reservation_data( $reservation_id );
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="verwaltung_boote_start">
			<input type="hidden" name="boot_id" value="<?php echo esc_attr( $data['boat_id'] ); ?>">
			<input type="hidden" name="reservierung_id" value="<?php echo esc_attr( $reservation_id ); ?>">
			<?php wp_nonce_field( 'verwaltung_boote_start_' . $data['boat_id'] ); ?>
			<button type="submit"><?php esc_html_e( 'Reservierte Nutzung starten', 'verwaltung-boote' ); ?></button>
		</form>
		<?php
	}

	/**
	 * Zeigt das Formular zum Beenden eines eigenen Eintrags.
	 *
	 * @param int $log_id Logbuch-ID.
	 * @return void
	 */
	private function render_end_form( $log_id ) {
		$format     = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		$started_at = (string) get_post_meta( $log_id, '_vb_start', true );
		$start      = $started_at ? get_date_from_gmt( $started_at, $format ) : '–';
		$end_utc    = current_time( 'mysql', true );
		$end        = get_date_from_gmt( $end_utc, $format );
		$start_iso  = $started_at ? gmdate( 'c', strtotime( $started_at . ' UTC' ) ) : '';
		$end_iso    = gmdate( 'c', strtotime( $end_utc . ' UTC' ) );
		?>
		<details class="verwaltung-boote-rueckgabe-bereich">
			<summary><?php esc_html_e( 'Nutzung beenden', 'verwaltung-boote' ); ?></summary>
			<form class="verwaltung-boote-rueckgabe" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="verwaltung_boote_end">
				<input type="hidden" name="log_id" value="<?php echo esc_attr( $log_id ); ?>">
				<?php wp_nonce_field( 'verwaltung_boote_end_' . $log_id ); ?>
				<label>
					<?php esc_html_e( 'Nutzungsbeginn', 'verwaltung-boote' ); ?>
					<input class="verwaltung-boote-browser-datetime" data-vb-utc="<?php echo esc_attr( $start_iso ); ?>" data-vb-format="datetime" type="text" value="<?php echo esc_attr( $start ); ?>" readonly aria-readonly="true">
				</label>
				<label>
					<?php esc_html_e( 'Nutzungsende', 'verwaltung-boote' ); ?>
					<input class="verwaltung-boote-browser-datetime" data-vb-utc="<?php echo esc_attr( $end_iso ); ?>" data-vb-format="datetime" type="text" value="<?php echo esc_attr( $end ); ?>" readonly aria-readonly="true">
				</label>
				<label>
					<?php esc_html_e( 'Gab es Schäden am Boot?', 'verwaltung-boote' ); ?>
					<select class="verwaltung-boote-schaden-auswahl" name="schaden_vorhanden" required>
						<option value=""><?php esc_html_e( 'Bitte auswählen', 'verwaltung-boote' ); ?></option>
						<option value="nein" selected><?php esc_html_e( 'Nein', 'verwaltung-boote' ); ?></option>
						<option value="ja"><?php esc_html_e( 'Ja', 'verwaltung-boote' ); ?></option>
					</select>
				</label>
				<div class="verwaltung-boote-schadendetails" hidden>
					<label>
						<?php esc_html_e( 'Schwere des Schadens', 'verwaltung-boote' ); ?>
						<select class="verwaltung-boote-schwere" name="schadenschwere">
							<option value=""><?php esc_html_e( 'Bitte auswählen', 'verwaltung-boote' ); ?></option>
							<?php foreach ( $this->get_damage_severities() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label>
						<?php esc_html_e( 'Schadensbeschreibung', 'verwaltung-boote' ); ?>
						<textarea name="schadensbeschreibung" rows="3"></textarea>
					</label>
				</div>
				<button type="submit"><?php esc_html_e( 'Rückgabe bestätigen', 'verwaltung-boote' ); ?></button>
			</form>
		</details>
		<?php
	}

	/**
	 * Liefert die erlaubten Schadensgrade.
	 *
	 * @return string[]
	 */
	private function get_damage_severities() {
		return array(
			'voll_benutzbar'        => __( 'Boot voll benutzbar', 'verwaltung-boote' ),
			'eingeschraenkt'        => __( 'Boot eingeschränkt benutzbar', 'verwaltung-boote' ),
			'nicht_benutzbar'       => __( 'Boot nicht benutzbar', 'verwaltung-boote' ),
		);
	}

	/**
	 * Zeigt eine Rueckmeldung nach einer Aktion.
	 *
	 * @return void
	 */
	private function render_usage_message() {
		$key = isset( $_GET['vb_meldung'] ) ? sanitize_key( wp_unslash( $_GET['vb_meldung'] ) ) : '';
		$messages = array(
			'gestartet'          => __( 'Die Nutzung wurde im Logbuch gestartet.', 'verwaltung-boote' ),
			'beendet'            => __( 'Die Nutzung wurde beendet und im Logbuch gespeichert.', 'verwaltung-boote' ),
			'bereits_vergeben'   => __( 'Dieses Boot wird inzwischen bereits genutzt.', 'verwaltung-boote' ),
			'ungueltiges_boot'   => __( 'Das ausgewählte Boot ist nicht verfügbar.', 'verwaltung-boote' ),
			'ungueltiger_eintrag'=> __( 'Dieser Logbucheintrag kann nicht beendet werden.', 'verwaltung-boote' ),
			'fehler'             => __( 'Der Logbucheintrag konnte nicht gespeichert werden.', 'verwaltung-boote' ),
			'schaden_abfrage_fehlt' => __( 'Bitte angeben, ob Schäden am Boot entstanden sind.', 'verwaltung-boote' ),
			'schwere_fehlt'      => __( 'Bitte die Schwere des Schadens auswählen.', 'verwaltung-boote' ),
			'schaden_fehler'     => __( 'Der Bootsschaden konnte nicht gespeichert werden. Die Nutzung bleibt aktiv.', 'verwaltung-boote' ),
			'reserviert'          => __( 'Die Reservierung wurde gespeichert.', 'verwaltung-boote' ),
			'reservierung_geloescht' => __( 'Die Reservierung wurde storniert und bleibt in der Historie erhalten.', 'verwaltung-boote' ),
			'reservierung_zeit_fehlt' => __( 'Bitte gültige Start- und Endzeiten angeben. Das Ende muss nach dem Start liegen.', 'verwaltung-boote' ),
			'reservierung_vergangenheit' => __( 'Der Reservierungsbeginn darf nicht in der Vergangenheit liegen.', 'verwaltung-boote' ),
			'reservierung_ueberschneidung' => __( 'Für diesen Zeitraum besteht bereits eine Reservierung des Boots.', 'verwaltung-boote' ),
			'reservierung_fehler' => __( 'Die Reservierung konnte nicht gespeichert werden.', 'verwaltung-boote' ),
			'reservierung_ungueltig' => __( 'Diese Reservierung kann nicht verwendet oder gelöscht werden.', 'verwaltung-boote' ),
			'reservierung_startfenster' => __( 'Die Nutzung kann nur von einer Stunde vor bis 45 Minuten nach Reservierungsbeginn gestartet werden.', 'verwaltung-boote' ),
			'boot_reserviert'     => __( 'Dieses Boot ist derzeit reserviert.', 'verwaltung-boote' ),
		);

		if ( isset( $messages[ $key ] ) ) {
			echo '<p class="verwaltung-boote-meldung" role="status">' . esc_html( $messages[ $key ] ) . '</p>';
		}
	}

	/**
	 * Gibt zugewiesene Begriffe kommasepariert aus.
	 *
	 * @param int    $post_id  Boot-ID.
	 * @param string $taxonomy Taxonomie.
	 * @return string
	 */
	private function get_term_names( $post_id, $taxonomy ) {
		$terms = get_the_terms( $post_id, $taxonomy );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return '–';
		}

		return implode( ', ', wp_list_pluck( $terms, 'name' ) );
	}

	/**
	 * Liefert den einzelnen Bootstyp eines Boots.
	 *
	 * @param int $post_id Boot-ID.
	 * @return string
	 */
	private function get_boat_type( $post_id ) {
		$terms = get_the_terms( $post_id, 'vb_bootstyp' );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return __( 'Ohne Bootstyp', 'verwaltung-boote' );
		}

		return (string) reset( $terms )->name;
	}

	/**
	 * Vergibt bestehenden Booten einmalig eine stabile Boots-ID, falls noch keine
	 * vorhanden ist. Die Kennung wird aus dem Namen ohne Leer- und Sonderzeichen
	 * abgeleitet und bei Bedarf eindeutig erweitert.
	 *
	 * @return void
	 */
	public function ensure_boat_identifiers() {
		$boats = get_posts(
			array(
				'post_type'      => 'vb_boot',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);

		foreach ( $boats as $boat ) {
			$current_identifier = (string) get_post_meta( $boat->ID, '_vb_boots_id', true );
			$legacy_default     = sanitize_title( $boat->post_title );
			if ( '' === $legacy_default ) {
				$legacy_default = 'boot-' . $boat->ID;
			}

			if ( '' !== $current_identifier && $legacy_default !== $current_identifier ) {
				continue;
			}

			update_post_meta( $boat->ID, '_vb_boots_id', $this->get_unique_boat_identifier( $this->get_default_boat_identifier( $boat->post_title, $boat->ID ), $boat->ID ) );
		}
	}

	/**
	 * Liefert die dauerhafte Boots-ID eines Boots.
	 *
	 * @param int $post_id Boot-ID.
	 * @return string
	 */
	private function get_boat_identifier( $post_id ) {
		$identifier = (string) get_post_meta( $post_id, '_vb_boots_id', true );
		return '' !== $identifier ? $identifier : 'boot' . absint( $post_id );
	}

	/**
	 * Findet ein Boot anhand seiner Boots-ID.
	 *
	 * @param string $identifier Boots-ID.
	 * @return \WP_Post|null
	 */
	private function get_boat_by_identifier( $identifier ) {
		if ( '' === $identifier ) {
			return null;
		}

		$boats = get_posts(
			array(
				'post_type'      => 'vb_boot',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'meta_key'       => '_vb_boots_id',
				'meta_value'     => $identifier,
			)
		);

		return ! empty( $boats ) ? $boats[0] : null;
	}

	/**
	 * Macht eine Boots-ID eindeutig.
	 *
	 * @param string $identifier Gewuenschte Boots-ID.
	 * @param int    $post_id    Boot, das die Kennung erhaelt.
	 * @return string
	 */
	private function get_unique_boat_identifier( $identifier, $post_id ) {
		$base      = $this->sanitize_boat_identifier( $identifier );
		$candidate = '' !== $base ? $base : 'boot' . absint( $post_id );
		$counter   = 2;

		while ( $this->boat_identifier_exists( $candidate, $post_id ) ) {
			$candidate = $base . $counter;
			$counter++;
		}

		return $candidate;
	}

	/**
	 * Bildet aus einem Bootsnamen die Standard-Boots-ID ohne Leer- oder Sonderzeichen.
	 *
	 * @param string $title   Bootsname.
	 * @param int    $post_id Boot-ID als Rueckfallwert.
	 * @return string
	 */
	private function get_default_boat_identifier( $title, $post_id ) {
		$identifier = $this->sanitize_boat_identifier( $title );
		return '' !== $identifier ? $identifier : 'boot' . absint( $post_id );
	}

	/**
	 * Erlaubt fuer Boots-IDs nur Kleinbuchstaben und Ziffern.
	 *
	 * @param string $identifier Rohwert der Boots-ID.
	 * @return string
	 */
	private function sanitize_boat_identifier( $identifier ) {
		return str_replace( array( '-', '_' ), '', sanitize_key( remove_accents( (string) $identifier ) ) );
	}

	/**
	 * Prueft, ob eine Boots-ID bereits einem anderen Boot zugeordnet ist.
	 *
	 * @param string $identifier Boots-ID.
	 * @param int    $exclude_id Auszuschliessendes Boot.
	 * @return bool
	 */
	private function boat_identifier_exists( $identifier, $exclude_id ) {
		$boats = get_posts(
			array(
				'post_type'      => 'vb_boot',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'post__not_in'   => array( absint( $exclude_id ) ),
				'meta_key'       => '_vb_boots_id',
				'meta_value'     => $identifier,
				'fields'         => 'ids',
			)
		);

		return ! empty( $boats );
	}

	/**
	 * Sortiert Boote zuerst nach Typ und danach nach Name.
	 *
	 * @param \WP_Post[] $boats Boote.
	 * @return \WP_Post[]
	 */
	private function sort_boats_by_type( $boats ) {
		usort(
			$boats,
			function ( $first, $second ) {
				$type_comparison = strcasecmp( $this->get_boat_type( $first->ID ), $this->get_boat_type( $second->ID ) );
				return 0 !== $type_comparison ? $type_comparison : strcasecmp( $first->post_title, $second->post_title );
			}
		);

		return $boats;
	}

	/**
	 * Gibt genau einen zugewiesenen Begriff aus.
	 *
	 * @param int    $post_id  Boot-ID.
	 * @param string $taxonomy Taxonomie.
	 * @return string
	 */
	private function get_single_term_name( $post_id, $taxonomy ) {
		$terms = get_the_terms( $post_id, $taxonomy );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return '–';
		}

		return (string) reset( $terms )->name;
	}

	/**
	 * Liefert den einzelnen Freitext-Liegeplatz eines Boots.
	 *
	 * @param int $post_id Boot-ID.
	 * @return string
	 */
	private function get_boat_berth( $post_id ) {
		$berth = (string) get_post_meta( $post_id, '_vb_liegeplatz', true );
		return '' !== $berth ? $berth : '–';
	}

	/**
	 * Legt die Bootswart-Seiten bei Installation und späteren Updates an.
	 *
	 * @return void
	 */
	public function ensure_bootswart_pages() {
		$definitions = array(
			'overview'     => array( 'Bootswart-Verwaltung', 'bootswart-verwaltung', '[verwaltung_boote_bootswart]' ),
			'boats'        => array( 'Bootswart – Alle Boote', 'bootswart-alle-boote', '[verwaltung_boote_bootswart_boote]' ),
			'reservations' => array( 'Bootswart – Alle Reservierungen', 'bootswart-alle-reservierungen', '[verwaltung_boote_bootswart_reservierungen]' ),
			'usages'       => array( 'Bootswart – Alle Nutzungen', 'bootswart-alle-nutzungen', '[verwaltung_boote_bootswart_nutzungen]' ),
			'damages'      => array( 'Bootswart – Alle Bootsschäden', 'bootswart-alle-bootschaeden', '[verwaltung_boote_bootswart_schaeden]' ),
			'users'        => array( 'Bootswart – Alle Nutzer', 'bootswart-alle-nutzer', '[verwaltung_boote_bootswart_nutzer]' ),
			'qr'           => array( 'Bootswart – QR-Codes', 'bootswart-qr-codes', '[verwaltung_boote_bootswart_qr]' ),
		);
		$page_ids = array();

		foreach ( $definitions as $key => $definition ) {
			$page = get_page_by_path( $definition[1] );
			if ( ! $page ) {
				$page_id = wp_insert_post( array( 'post_title' => __( $definition[0], 'verwaltung-boote' ), 'post_name' => $definition[1], 'post_content' => $definition[2], 'post_status' => 'publish', 'post_type' => 'page' ) );
				if ( ! is_wp_error( $page_id ) ) {
					$page_ids[ $key ] = (int) $page_id;
				}
			} else {
				$page_ids[ $key ] = (int) $page->ID;
			}
		}

		if ( ! empty( $page_ids ) ) {
			update_option( 'verwaltung_boote_bootswart_pages', $page_ids );
			if ( ! empty( $page_ids['overview'] ) ) {
				update_option( 'verwaltung_boote_bootswart_page_id', $page_ids['overview'] );
			}
		}
	}

	/**
	 * Legt die einzelne Einstiegsseite für alle Boots-QR-Codes an.
	 *
	 * @return void
	 */
	public function ensure_qr_entry_page() {
		$page = get_page_by_path( 'boot-verwenden' );
		if ( ! $page ) {
			$page_id = wp_insert_post(
				array(
					'post_title'   => __( 'Boot verwenden', 'verwaltung-boote' ),
					'post_name'    => 'boot-verwenden',
					'post_content' => '[verwaltung_boote_boot_einstieg]',
					'post_status'  => 'publish',
					'post_type'    => 'page',
				)
			);
			if ( ! is_wp_error( $page_id ) ) {
				update_option( 'verwaltung_boote_qr_entry_page_id', $page_id );
			}
			return;
		}

		update_option( 'verwaltung_boote_qr_entry_page_id', $page->ID );
	}

	/**
	 * Liefert die IDs der geschützten Bootswart-Seiten.
	 *
	 * @return array<string,int>
	 */
	private function get_bootswart_pages() {
		$pages = get_option( 'verwaltung_boote_bootswart_pages', array() );
		if ( ! is_array( $pages ) ) {
			$pages = array();
		}
		if ( empty( $pages['overview'] ) ) {
			$pages['overview'] = absint( get_option( 'verwaltung_boote_bootswart_page_id' ) );
		}
		return array_filter( array_map( 'absint', $pages ) );
	}

	/**
	 * @return int[]
	 */
	private function get_bootswart_page_ids() {
		return array_values( $this->get_bootswart_pages() );
	}

	/**
	 * Richtet Rolle, Standardwerte und Listenseite ein.
	 *
	 * @return void
	 */
	public static function activate() {
		$plugin = self::instance();
		$plugin->register_content_types();

		$capabilities = array(
			'read', 'upload_files', 'edit_boote', 'edit_others_boote', 'publish_boote',
			'read_private_boote', 'delete_boote', 'delete_private_boote',
			'delete_published_boote', 'delete_others_boote', 'edit_private_boote',
			'edit_published_boote', 'edit_boot', 'read_boot', 'delete_boot',
		);

		$role = add_role( 'bootswart', __( 'Bootswart', 'verwaltung-boote' ), array( 'read' => true ) );
		if ( ! $role ) {
			$role = get_role( 'bootswart' );
		}

		$administrator = get_role( 'administrator' );
		foreach ( $capabilities as $capability ) {
			if ( $role ) {
				$role->add_cap( $capability );
			}
			if ( $administrator ) {
				$administrator->add_cap( $capability );
			}
		}

		if ( ! term_exists( 'Motorboot', 'vb_bootstyp' ) ) {
			wp_insert_term( 'Motorboot', 'vb_bootstyp' );
		}
		if ( ! term_exists( 'Segelboot', 'vb_bootstyp' ) ) {
			wp_insert_term( 'Segelboot', 'vb_bootstyp' );
		}

		$page = get_page_by_path( 'bootsnutzung' );
		if ( ! $page ) {
			$page = get_page_by_path( 'bootsliste' );
		}
		if ( ! $page ) {
			$page_id = wp_insert_post(
				array(
					'post_title'   => __( 'Nutzung Vereinsboote', 'verwaltung-boote' ),
					'post_name'    => 'bootsnutzung',
					'post_content' => '[verwaltung_boote_liste]',
					'post_status'  => 'publish',
					'post_type'    => 'page',
				)
			);
			if ( ! is_wp_error( $page_id ) ) {
				update_option( 'verwaltung_boote_list_page_id', $page_id );
			}
		} else {
			if ( 'bootsnutzung' !== $page->post_name ) {
				wp_update_post(
					array(
						'ID'        => $page->ID,
						'post_name' => 'bootsnutzung',
					)
				);
			}
			update_option( 'verwaltung_boote_list_page_id', $page->ID );
		}

		$bootswart_page = get_page_by_path( 'bootswart-verwaltung' );
		if ( ! $bootswart_page ) {
			$bootswart_page_id = wp_insert_post(
				array(
					'post_title'   => __( 'Bootswart-Verwaltung', 'verwaltung-boote' ),
					'post_name'    => 'bootswart-verwaltung',
					'post_content' => '[verwaltung_boote_bootswart]',
					'post_status'  => 'publish',
					'post_type'    => 'page',
				)
			);
			if ( ! is_wp_error( $bootswart_page_id ) ) {
				update_option( 'verwaltung_boote_bootswart_page_id', $bootswart_page_id );
			}
		} else {
			update_option( 'verwaltung_boote_bootswart_page_id', $bootswart_page->ID );
		}

		$plugin->ensure_bootswart_pages();
		$plugin->ensure_qr_entry_page();

		flush_rewrite_rules();
	}

	/**
	 * Aktualisiert Umschreiberegeln bei Deaktivierung.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Direkte Instanziierung verhindern.
	 */
	private function __construct() {}
}

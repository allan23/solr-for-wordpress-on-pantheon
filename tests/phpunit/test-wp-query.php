<?php

class SolrWPQueryTest extends WP_UnitTestCase {
	/**
	 * @var integer Term ID of custom taxonomy term.
	 */
	var $term_id;

	function __construct() {
		parent::__construct();
		// For tests, we're not using https.
		add_filter( 'solr_scheme', function () {
			return 'http';
		} );
		SolrPower_Options::get_instance()->initalize_options();
		$this->__setup_taxonomy();
	}

	/**
	 * Setup for every test.
	 */
	function setUp() {
		parent::setUp();
		// Delete the entire index.
		SolrPower_Sync::get_instance()->delete_all();
		// Setup options (if not already set)
		$solr_options = solr_options();
		if ( 1 !== $solr_options['s4wp_solr_initialized'] ) {
			$options = SolrPower_Options::get_instance()->initalize_options();
			update_option( 'plugin_s4wp_settings', $options );
		}

	}

	function __setup_taxonomy() {

		$labels = array(
			'name'              => _x( 'Genres', 'taxonomy general name', 'textdomain' ),
			'singular_name'     => _x( 'Genre', 'taxonomy singular name', 'textdomain' ),
			'search_items'      => __( 'Search Genres', 'textdomain' ),
			'all_items'         => __( 'All Genres', 'textdomain' ),
			'parent_item'       => __( 'Parent Genre', 'textdomain' ),
			'parent_item_colon' => __( 'Parent Genre:', 'textdomain' ),
			'edit_item'         => __( 'Edit Genre', 'textdomain' ),
			'update_item'       => __( 'Update Genre', 'textdomain' ),
			'add_new_item'      => __( 'Add New Genre', 'textdomain' ),
			'new_item_name'     => __( 'New Genre Name', 'textdomain' ),
			'menu_name'         => __( 'Genre', 'textdomain' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'genre' ),
		);

		register_taxonomy( 'genre', array( 'post' ), $args );


		// Create 'Horror' genre
		wp_insert_term( 'Horror', 'genre' );
		$term          = get_term_by( 'slug', 'horror', 'genre' );
		$this->term_id = $term->term_id;
	}


	/**
	 * Creates a new post.
	 * @return int|WP_Error
	 */
	function __create_test_post( $post_type = 'post' ) {
		$args = array(
			'post_type'    => $post_type,
			'post_status'  => 'publish',
			'post_title'   => 'Test Post ' . time(),
			'post_content' => 'This is a solr test.',
		);

		return wp_insert_post( $args );
	}

	function __create_multiple( $number = 1 ) {
		for ( $i = 0; $i < $number; $i ++ ) {
			$this->__create_test_post();
		}
	}

	function __change_option( $key, $value ) {
		$solr_options         = solr_options();
		$solr_options[ $key ] = $value;
		update_option( 'plugin_s4wp_settings', $solr_options );
	}

	/**
	 * Performs simple search query with WP_Query.
	 * @global WP_Post $post
	 */
	function test_simple_wp_query() {
		$this->__create_test_post();
		$args  = array(
			's' => 'solr'
		);
		$query = new WP_Query( $args );
		$this->assertEquals( $query->post_count, 1 );
		$this->assertEquals( $query->found_posts, 1 );
		while ( $query->have_posts() ) {
			$query->the_post();

			global $post;

			$wp_post = get_post( get_the_ID() );
			$this->assertEquals( $post->solr, true );
			$this->assertEquals( $post->post_title, get_the_title() );
			$this->assertEquals( $post->post_content, get_the_content() );
			$this->assertEquals( $post->post_date, $wp_post->post_date );
			$this->assertEquals( $post->post_modified, $wp_post->post_modified );
			$this->assertEquals( $post->post_name, $wp_post->post_name );
			$this->assertEquals( $post->post_parent, $wp_post->post_parent );
			$this->assertEquals( $post->post_excerpt, $wp_post->post_excerpt );
		}

		wp_reset_postdata();
	}

	function test_wp_query_by_id() {
		$post_id = $this->__create_test_post();
		SolrPower_Sync::get_instance()->load_all_posts( 0, 'post', 100, false );
		$args  = array(
			'solr_integrate' => true,
			'p'              => $post_id
		);
		$query = new WP_Query( $args );
		$this->assertEquals( $post_id, $query->post->ID );
	}

	function test_wp_query_by_post_type() {
		$post_id = $this->__create_test_post();
		$page_id = $this->__create_test_post( 'page' );
		SolrPower_Sync::get_instance()->load_all_posts( 0, 'post', 100, false );
		$args  = array(
			'solr_integrate' => true,
			'post_type'      => 'page'
		);
		$query = new WP_Query( $args );
		$this->assertEquals( $page_id, $query->post->ID );
	}

	function test_wp_query_by_post_type_arr() {
		$post_id = $this->__create_test_post();
		$page_id = $this->__create_test_post( 'page' );
		SolrPower_Sync::get_instance()->load_all_posts( 0, 'post', 100, false );
		$args  = array(
			'solr_integrate' => true,
			'post_type'      => array( 'page', 'post' ),
		);
		$query = new WP_Query( $args );
		$this->assertEquals( 2, $query->post_count );
		$this->assertEquals( 2, $query->found_posts );
	}

	function test_wp_query_by_tax() {
		$this->__create_test_post();

		$p_id = $this->__create_test_post();
		wp_set_object_terms( $p_id, 'Horror', 'genre', true );

		SolrPower_Sync::get_instance()->load_all_posts( 0, 'post', 100, false );
		$args  = array(
			'solr_integrate' => true,
			'tax_query'      => array(
				array(
					'taxonomy' => 'genre',
					'terms'    => array( 'Horror' ),
					'field'    => 'name',
				),
			),
		);
		$query = new WP_Query( $args );

		$this->assertEquals( $p_id, $query->post->ID );
	}

	function test_wp_query_by_tax_slug() {
		$this->__create_test_post();

		$p_id = $this->__create_test_post();
		wp_set_object_terms( $p_id, 'Horror', 'genre', true );

		SolrPower_Sync::get_instance()->load_all_posts( 0, 'post', 100, false );
		$args  = array(
			'solr_integrate' => true,
			'tax_query'      => array(
				array(
					'taxonomy' => 'genre',
					'terms'    => array( 'horror' ),
					'field'    => 'slug',
				),
			),
		);
		$query = new WP_Query( $args );
		$this->assertEquals( $p_id, $query->post->ID );
	}

	function test_wp_query_by_tax_id() {
		$this->__create_test_post();

		$p_id = $this->__create_test_post();
		wp_set_object_terms( $p_id, 'Horror', 'genre', true );

		SolrPower_Sync::get_instance()->load_all_posts( 0, 'post', 100, false );
		$args  = array(
			'solr_integrate' => true,
			'tax_query'      => array(
				array(
					'taxonomy' => 'genre',
					'terms'    => array( $this->term_id ),
					'field'    => 'id',
				),
			),
		);
		$query = new WP_Query( $args );

		$this->assertEquals( $p_id, $query->post->ID );
	}

	function test_wp_query_by_tax_cat() {
		$this->__create_test_post();
		$cat_id_one = wp_create_category( 'Term Slug' );

		$p_id = $this->__create_test_post();
		wp_set_object_terms( $p_id, $cat_id_one, 'category', true );

		SolrPower_Sync::get_instance()->load_all_posts( 0, 'post', 100, false );
		$args  = array(
			'solr_integrate' => true,
			'tax_query'      => array(
				array(
					'taxonomy' => 'category',
					'terms'    => array( 'Term Slug' ),
					'field'    => 'name',
				),
			),
		);
		$query = new WP_Query( $args );
		$this->assertEquals( $p_id, $query->post->ID );
	}

	function test_wp_query_by_tax_cat_slug() {
		$this->__create_test_post();
		$cat_id_one = wp_create_category( 'Term Slug' );

		$p_id = $this->__create_test_post();
		wp_set_object_terms( $p_id, $cat_id_one, 'category', true );

		SolrPower_Sync::get_instance()->load_all_posts( 0, 'post', 100, false );
		$args  = array(
			'solr_integrate' => true,
			'tax_query'      => array(
				array(
					'taxonomy' => 'category',
					'terms'    => array( 'term-slug' ),
					'field'    => 'slug',
				),
			),
		);
		$query = new WP_Query( $args );

		$this->assertEquals( $p_id, $query->post->ID );
	}

	function test_wp_query_by_tax_cat_id() {
		$this->__create_test_post();
		$cat_id_one = wp_create_category( 'Term Slug' );

		$p_id = $this->__create_test_post();
		wp_set_object_terms( $p_id, $cat_id_one, 'category', true );

		SolrPower_Sync::get_instance()->load_all_posts( 0, 'post', 100, false );
		$args  = array(
			'solr_integrate' => true,
			'tax_query'      => array(
				array(
					'taxonomy' => 'category',
					'terms'    => array( $cat_id_one ),
					'field'    => 'id',
				),
			),
		);
		$query = new WP_Query( $args );
		$this->assertEquals( $p_id, $query->post->ID );
	}

	function test_wp_query_meta() {

		$this->__change_option( 's4wp_index_custom_fields', array( 'price' ) );

		$post_one = $this->__create_test_post();
		update_post_meta( $post_one, 'price', 33 );
		$post_two = $this->__create_test_post();
		update_post_meta( $post_two, 'price', 10 );
		$post_three = $this->__create_test_post();
		update_post_meta( $post_three, 'price', 76 );

		SolrPower_Sync::get_instance()->load_all_posts( 0, 'post', 100, false );
		$args  = array(
			'solr_integrate' => true,
			'meta_query'     => array(
				array(
					'key'     => 'price',
					'value'   => 50,
					'compare' => '<='
				)
			),
		);
		$query = new WP_Query( $args );
		$this->assertEquals( 2, $query->post_count );
	}

	function test_wp_query_meta_two() {

		$this->__change_option( 's4wp_index_custom_fields', array( 'price', 'has_discount' ) );
		$post_one = $this->__create_test_post();
		update_post_meta( $post_one, 'price', 33 );
		update_post_meta( $post_one, 'has_discount', false );
		$post_two = $this->__create_test_post();
		update_post_meta( $post_two, 'price', 10 );
		update_post_meta( $post_two, 'has_discount', true );
		$post_three = $this->__create_test_post();
		update_post_meta( $post_three, 'price', 76 );
		update_post_meta( $post_three, 'has_discount', false );

		SolrPower_Sync::get_instance()->load_all_posts( 0, 'post', 100, false );
		$args  = array(
			'solr_integrate' => true,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => 'price',
					'value'   => 50,
					'compare' => '<='
				),
				array(
					'key'   => 'has_discount',
					'value' => 1
				)
			),
		);
		$query = new WP_Query( $args );
		$this->assertEquals( 1, $query->post_count );
	}

	function test_wp_query_meta_three() {

		$this->__change_option( 's4wp_index_custom_fields', array( 'price', 'has_discount', 'color' ) );
		$post_one = $this->__create_test_post();
		update_post_meta( $post_one, 'price', 33 );
		update_post_meta( $post_one, 'has_discount', false );
		update_post_meta( $post_one, 'color', 'red' );
		$post_two = $this->__create_test_post();
		update_post_meta( $post_two, 'price', 10 );
		update_post_meta( $post_two, 'has_discount', true );
		update_post_meta( $post_two, 'color', 'red' );
		$post_three = $this->__create_test_post();
		update_post_meta( $post_three, 'price', 76 );
		update_post_meta( $post_three, 'has_discount', false );
		update_post_meta( $post_three, 'color', 'green' );

		SolrPower_Sync::get_instance()->load_all_posts( 0, 'post', 100, false );
		$args  = array(
			'solr_integrate' => true,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'relation' => 'AND',
					array(
						'key'     => 'price',
						'value'   => 32,
						'compare' => '<='
					),
					array(
						'key'   => 'has_discount',
						'value' => 1
					)

				),
				array(
					array(
						'key'   => 'color',
						'value' => 'red',
					)
				)

			),
		);
		$query = new WP_Query( $args );
		$this->assertEquals( 1, $query->post_count );
	}

	function test_wp_query_date() {

		$post_one = $this->__create_test_post();
		wp_update_post( array( 'ID' => $post_one, 'post_date' => '2010-06-20' ) );
		$post_two = $this->__create_test_post();
		wp_update_post( array( 'ID' => $post_two, 'post_date' => '2015-06-20' ) );
		$post_three = $this->__create_test_post();


		SolrPower_Sync::get_instance()->load_all_posts( 0, 'post', 100, false );
		$args  = array(
			'solr_integrate' => true,
			'date_query'     => array(
				array(
					'year'  => '2015',
					'month' => '06',
					'day'   => '20'
				)
			)
		);
		$query = new WP_Query( $args );
		print_r( $query->date_query->get_sql() );
		print_r( SolrPower_WP_Query::get_instance()->qry );
		$this->assertEquals( $post_two, $query->post->ID );
	}
}
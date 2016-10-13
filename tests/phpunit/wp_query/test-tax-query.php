<?php

class SolrTaxQueryTest extends SolrTestBase {
	function setUp() {
		parent::setUp();
	}

	function tearDown() {
		parent::tearDown();
	}

	function show_query() {
		print_r( SolrPower_WP_Query::get_instance()->qry );
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
					'field'    => 'term_id',
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
					'field'    => 'term_id',
				),
			),
		);
		$query = new WP_Query( $args );
		$this->assertEquals( $p_id, $query->post->ID );
	}

	public function test_tax_query_single_query_single_term_field_slug() {
		$t  = self::factory()->term->create( array(
			'taxonomy' => 'category',
			'slug'     => 'foo',
			'name'     => 'Foo',
		) );
		$p1 = self::factory()->post->create();
		$p2 = self::factory()->post->create();

		wp_set_post_terms( $p1, $t, 'category' );
		SolrPower_Sync::get_instance()->load_all_posts( 0, 'post', 100, false );
		$q = new WP_Query( array(
			'solr_integrate'         => true,
			'fields'                 => 'ids',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'tax_query'              => array(
				array(
					'taxonomy' => 'category',
					'terms'    => array( 'foo' ),
					'field'    => 'slug',
				),
			),
		) );

		$this->assertEquals( array( $p1 ), wp_list_pluck( $q->posts, 'ID' ) );
	}

	public function test_tax_query_single_query_single_term_field_name() {
		$t  = self::factory()->term->create( array(
			'taxonomy' => 'category',
			'slug'     => 'foo',
			'name'     => 'Foo',
		) );
		$p1 = self::factory()->post->create();
		$p2 = self::factory()->post->create();

		wp_set_post_terms( $p1, $t, 'category' );
		SolrPower_Sync::get_instance()->load_all_posts( 0, 'post', 100, false );
		$q = new WP_Query( array(
			'solr_integrate'         => true,
			'fields'                 => 'ids',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'tax_query'              => array(
				array(
					'taxonomy' => 'category',
					'terms'    => array( 'Foo' ),
					'field'    => 'name',
				),
			),
		) );

		$this->assertEquals( array( $p1 ), wp_list_pluck( $q->posts, 'ID' ) );
	}

	public function test_field_name_should_work_for_names_with_spaces() {
		register_taxonomy( 'wptests_tax', 'post' );

		$t  = self::factory()->term->create( array(
			'taxonomy' => 'wptests_tax',
			'slug'     => 'foo',
			'name'     => 'Foo Bar',
		) );
		$p1 = self::factory()->post->create();
		$p2 = self::factory()->post->create();

		wp_set_object_terms( $p1, $t, 'wptests_tax' );

		SolrPower_Sync::get_instance()->load_all_posts( 0, 'post', 100, false );
		$q = new WP_Query( array(
			'solr_integrate' => true,
			'fields'         => 'ids',
			'tax_query'      => array(
				array(
					'taxonomy' => 'wptests_tax',
					'terms'    => array( 'Foo Bar' ),
					'field'    => 'name',
				),
			),
		) );

		$this->assertEquals( array( $p1 ), wp_list_pluck( $q->posts, 'ID' ) );
	}

	public function test_tax_query_single_query_single_term_field_term_taxonomy_id() {
		$t  = self::factory()->term->create( array(
			'taxonomy' => 'category',
			'slug'     => 'foo',
			'name'     => 'Foo',
		) );
		$p1 = self::factory()->post->create();
		$p2 = self::factory()->post->create();

		$tt_ids = wp_set_post_terms( $p1, $t, 'category' );
		SolrPower_Sync::get_instance()->load_all_posts( 0, 'post', 100, false );
		$q = new WP_Query( array(
			'solr_integrate'         => true,
			'fields'                 => 'ids',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'tax_query'              => array(
				array(
					'taxonomy' => 'category',
					'terms'    => $tt_ids,
					'field'    => 'term_taxonomy_id',
				),
			),
		) );

		$this->assertEquals( array( $p1 ), wp_list_pluck( $q->posts, 'ID' ) );
	}

	public function test_tax_query_single_query_single_term_field_term_id() {
		$t  = self::factory()->term->create( array(
			'taxonomy' => 'category',
			'slug'     => 'foo',
			'name'     => 'Foo',
		) );
		$p1 = self::factory()->post->create();
		$p2 = self::factory()->post->create();

		wp_set_post_terms( $p1, $t, 'category' );
		SolrPower_Sync::get_instance()->load_all_posts( 0, 'post', 100, false );
		$q = new WP_Query( array(
			'solr_integrate'         => true,
			'fields'                 => 'ids',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'tax_query'              => array(
				array(
					'taxonomy' => 'category',
					'terms'    => array( $t ),
					'field'    => 'term_id',
				),
			),
		) );

		$this->assertEquals( array( $p1 ), wp_list_pluck( $q->posts, 'ID' ) );
	}

	public function test_tax_query_single_query_single_term_operator_in() {
		$t  = self::factory()->term->create( array(
			'taxonomy' => 'category',
			'slug'     => 'foo',
			'name'     => 'Foo',
		) );
		$p1 = self::factory()->post->create();
		$p2 = self::factory()->post->create();

		wp_set_post_terms( $p1, $t, 'category' );
		SolrPower_Sync::get_instance()->load_all_posts( 0, 'post', 100, false );
		$q = new WP_Query( array(
			'solr_integrate'         => true,
			'fields'                 => 'ids',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'tax_query'              => array(
				array(
					'taxonomy' => 'category',
					'terms'    => array( 'foo' ),
					'field'    => 'slug',
					'operator' => 'IN',
				),
			),
		) );

		$this->assertEquals( array( $p1 ), wp_list_pluck( $q->posts, 'ID' ) );
	}

	public function test_tax_query_single_query_single_term_operator_not_in() {
		$t  = self::factory()->term->create( array(
			'taxonomy' => 'category',
			'slug'     => 'foo',
			'name'     => 'Foo',
		) );
		$p1 = self::factory()->post->create();
		$p2 = self::factory()->post->create();

		wp_set_post_terms( $p1, $t, 'category' );
		SolrPower_Sync::get_instance()->load_all_posts( 0, 'post', 100, false );

		$q = new WP_Query( array(
			'solr_integrate'         => true,
			'fields'                 => 'ids',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'tax_query'              => array(
				array(
					'taxonomy' => 'category',
					'terms'    => array( 'foo' ),
					'field'    => 'slug',
					'operator' => 'NOT IN',
				),
			),
		) );
		$this->show_query();
		$this->assertEquals( array( $p2 ), wp_list_pluck( $q->posts, 'ID' ) );
	}
}
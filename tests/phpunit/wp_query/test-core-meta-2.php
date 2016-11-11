<?php

class Tests_Solr_MetaQuery_2 extends SolrTestBase {
	/**
	 * @var array Custom fields that need to be indexed in unit tests.
	 */
	var $postmeta = array(
		'foo',
		'oof',
		'bar',
		'froo',
		'baz',
		'tango',
		'vegetable',
		'color',
		'number_of_colors',
		'decimal_value',
		'bar1',
		'bar2',
		'foo2',
		'foo3',
		'foo4',
		'time',
		'city',
		'address'
	);

	function setUp() {
		parent::setUp();

		$this->__change_option( 's4wp_index_custom_fields', $this->postmeta );
		SolrPower_Sync::get_instance()->bulk_sync = true;
	}

	function tearDown() {
		parent::tearDown();
		SolrPower_Sync::get_instance()->bulk_sync = false;
	}

	public function test_meta_query_relation_and() {
		$post_id = self::factory()->post->create();
		add_post_meta( $post_id, 'foo', rand_str() );
		add_post_meta( $post_id, 'foo', rand_str() );
		$post_id2 = self::factory()->post->create();
		add_post_meta( $post_id2, 'bar', 'val2' );
		add_post_meta( $post_id2, 'foo', rand_str() );
		$post_id3 = self::factory()->post->create();
		add_post_meta( $post_id3, 'baz', rand_str() );
		$post_id4 = self::factory()->post->create();
		add_post_meta( $post_id4, 'froo', rand_str() );
		$post_id5 = self::factory()->post->create();
		add_post_meta( $post_id5, 'tango', 'val2' );
		$post_id6 = self::factory()->post->create();
		add_post_meta( $post_id6, 'bar', 'val1' );
		add_post_meta( $post_id6, 'foo', rand_str() );
		$post_id7 = self::factory()->post->create();
		add_post_meta( $post_id7, 'foo', rand_str() );
		add_post_meta( $post_id7, 'froo', rand_str() );
		add_post_meta( $post_id7, 'baz', rand_str() );
		add_post_meta( $post_id7, 'bar', 'val2' );

		$this->sync();

		$query = new WP_Query( array(
			'meta_query'             => array(
				array(
					'key' => 'foo'
				),
				array(
					'key'   => 'bar',
					'value' => 'val2'
				),
				array(
					'key' => 'baz'
				),
				array(
					'key' => 'froo'
				),
				'relation' => 'AND',
			),
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'fields'                 => 'ids',
			'solr_integrate'         => true,
		) );

		$expected = array( $post_id7 );
		$returned = array();
		foreach ( $query->posts as $post ) {
			$returned[] = $post->ID;
		}

		$this->assertEqualSets( $expected, $returned );

		$query = new WP_Query( array(
			'meta_query'             => array(
				array(
					'key' => 'foo'
				),
				array(
					'key' => 'bar',
				),
				'relation' => 'AND',
			),
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'fields'                 => 'ids',
			'solr_integrate'         => true,
		) );

		$expected = array( $post_id2, $post_id6, $post_id7 );
		$returned = array();
		foreach ( $query->posts as $post ) {
			$returned[] = $post->ID;
		}

		$this->assertEqualSets( $expected, $returned );
	}

	public function test_meta_query_relation_and_compare_in_different_keys() {
		$posts = self::factory()->post->create_many( 4 );
		add_post_meta( $posts[0], 'color', 'orange' );
		add_post_meta( $posts[1], 'color', 'blue' );
		add_post_meta( $posts[1], 'vegetable', 'onion' );
		add_post_meta( $posts[1], 'vegetable', 'shallot' );
		add_post_meta( $posts[2], 'vegetable', 'shallot' );
		add_post_meta( $posts[3], 'vegetable', 'banana' );
		$this->sync();
		$query = new WP_Query( array(
			'solr_integrate'         => true,
			'meta_query'             => array(
				'relation' => 'AND',
				array(
					'key'     => 'vegetable',
					'value'   => array( 'onion', 'shallot' ),
					'compare' => 'IN',
				),
				array(
					'key'     => 'color',
					'value'   => array( 'blue' ),
					'compare' => 'IN',
				),
			),
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'fields'                 => 'ids',
		) );

		$expected = array( $posts[1] );
		$returned = array();
		foreach ( $query->posts as $post ) {
			$returned[] = $post->ID;
		}

		$this->assertEqualSets( $expected, $returned );
	}

	public function test_meta_query_relation_and_compare_not_equals() {
		$posts = self::factory()->post->create_many( 4 );
		add_post_meta( $posts[0], 'color', 'orange' );
		add_post_meta( $posts[1], 'color', 'blue' );
		add_post_meta( $posts[1], 'vegetable', 'onion' );
		add_post_meta( $posts[2], 'vegetable', 'shallot' );
		add_post_meta( $posts[3], 'vegetable', 'banana' );
		$this->sync();
		$query = new WP_Query( array(
			'solr_integrate'         => true,
			'meta_query'             => array(
				'relation' => 'AND',
				array(
					'key'     => 'vegetable',
					'value'   => 'onion',
					'compare' => '!=',
				),
				array(
					'key'     => 'vegetable',
					'value'   => 'shallot',
					'compare' => '!=',
				),
			),
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'fields'                 => 'ids',
		) );

		$expected = array( $posts[3] );
		$returned = array();
		foreach ( $query->posts as $post ) {
			$returned[] = $post->ID;
		}

		$this->assertEqualSets( $expected, $returned );
	}

	public function test_meta_query_relation_and_compare_not_equals_different_keys() {
		$posts = self::factory()->post->create_many( 4 );

		// !shallot, but orange.
		add_post_meta( $posts[0], 'color', 'orange' );
		add_post_meta( $posts[0], 'vegetable', 'onion' );

		// !orange, but shallot.
		add_post_meta( $posts[1], 'color', 'blue' );
		add_post_meta( $posts[1], 'vegetable', 'shallot' );

		// Neither.
		add_post_meta( $posts[2], 'color', 'blue' );
		add_post_meta( $posts[2], 'vegetable', 'onion' );
		$this->sync();
		$query = new WP_Query( array(
			'solr_integrate'         => true,
			'meta_query'             => array(
				'relation' => 'AND',
				array(
					'key'     => 'vegetable',
					'value'   => 'shallot',
					'compare' => '!=',
				),
				array(
					'key'     => 'color',
					'value'   => 'orange',
					'compare' => '!=',
				),
			),
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'fields'                 => 'ids',
		) );

		$expected = array( $posts[2] );
		$returned = array();
		foreach ( $query->posts as $post ) {
			$returned[] = $post->ID;
		}

		$this->assertEqualSets( $expected, $returned );
	}

	public function test_meta_query_relation_and_compare_not_equals_not_in() {
		$posts = self::factory()->post->create_many( 4 );
		add_post_meta( $posts[0], 'color', 'orange' );
		add_post_meta( $posts[1], 'color', 'blue' );
		add_post_meta( $posts[1], 'vegetable', 'onion' );
		add_post_meta( $posts[2], 'vegetable', 'shallot' );
		add_post_meta( $posts[3], 'vegetable', 'banana' );
		$this->sync();
		$query = new WP_Query( array(
			'solr_integrate'         => true,
			'meta_query'             => array(
				'relation' => 'AND',
				array(
					'key'     => 'vegetable',
					'value'   => 'onion',
					'compare' => '!=',
				),
				array(
					'key'     => 'vegetable',
					'value'   => array( 'shallot' ),
					'compare' => 'NOT IN',
				),
			),
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'fields'                 => 'ids',
		) );

		$expected = array( $posts[3] );
		$returned = array();
		foreach ( $query->posts as $post ) {
			$returned[] = $post->ID;
		}

		$this->assertEqualSets( $expected, $returned );
	}

	public function test_meta_query_relation_and_compare_not_equals_and_not_like() {
		$posts = self::factory()->post->create_many( 4 );
		add_post_meta( $posts[0], 'color', 'orange' );
		add_post_meta( $posts[1], 'color', 'blue' );
		add_post_meta( $posts[1], 'vegetable', 'onion' );
		add_post_meta( $posts[2], 'vegetable', 'shallot' );
		add_post_meta( $posts[3], 'vegetable', 'banana' );
		$this->sync();
		$query = new WP_Query( array(
			'solr_integrate'         => true,
			'meta_query'             => array(
				'relation' => 'AND',
				array(
					'key'     => 'vegetable',
					'value'   => 'onion',
					'compare' => '!=',
				),
				array(
					'key'     => 'vegetable',
					'value'   => 'hall',
					'compare' => 'NOT LIKE',
				),
			),
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'fields'                 => 'ids',
		) );

		$expected = array( $posts[3] );
		$returned = array();
		foreach ( $query->posts as $post ) {
			$returned[] = $post->ID;
		}

		$this->assertEqualSets( $expected, $returned );
	}


}
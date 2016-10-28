<?php

class SolrDateQueryTest extends SolrTestBase {

	public $q;

	function setUp() {
		parent::setUp();
		unset( $this->q );
		$this->q = new WP_Query();
	}

	function tearDown() {
		parent::tearDown();
	}

	function show_query() {
		print_r( SolrPower_WP_Query::get_instance()->backup );
		print_r( SolrPower_Api::get_instance()->log );
	}

	public function _get_query_result( $args = array() ) {
		SolrPower_Sync::get_instance()->load_all_posts( 0, 'post', 100, false );
		$args = wp_parse_args( $args, array(
			'solr_integrate'         => true,  // Use Solr!
			'post_status'            => 'any', // For the future post
			'posts_per_page'         => '-1',  // To make sure results are accurate
			'orderby'                => 'ID',  // Same order they were created
			'order'                  => 'ASC',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		) );

		return $this->q->query( $args );
	}

	public function test_date_query_before_array() {
		$p1 = self::factory()->post->create( array( 'post_date' => '2007-09-24 07:17:23', ) );
		$p2 = self::factory()->post->create( array( 'post_date' => '2008-03-29 07:17:23', ) );
		$p3 = self::factory()->post->create( array( 'post_date' => '2008-07-15 07:17:23', ) );
		$p4 = self::factory()->post->create( array( 'post_date' => '2009-06-11 07:17:23', ) );

		$posts = $this->_get_query_result( array(
			'date_query' => array(
				array(
					'before' => array(
						'year'  => 2008,
						'month' => 6,
					),
				),
			),
		) );

		$this->assertEqualSets( array( $p1, $p2 ), wp_list_pluck( $posts, 'ID' ) );
	}
	public function test_date_query_before_array_test_defaulting() {
		$p1 = self::factory()->post->create( array( 'post_date' => '2007-09-24 07:17:23',) );
		$p2 = self::factory()->post->create( array( 'post_date' => '2008-03-29 07:17:23',) );

		$posts = $this->_get_query_result( array(
			'date_query' => array(
				array(
					'before' => array(
						'year' => 2008,
					),
				),
			),
		) );

		$this->assertEqualSets( array( $p1 ), wp_list_pluck( $posts, 'ID' ) );
	}

	public function test_date_query_before_string() {
		$p1 = self::factory()->post->create( array( 'post_date' => '2007-09-24 07:17:23',) );
		$p2 = self::factory()->post->create( array( 'post_date' => '2008-03-29 07:17:23',) );
		$p3 = self::factory()->post->create( array( 'post_date' => '2008-07-15 07:17:23',) );
		$p4 = self::factory()->post->create( array( 'post_date' => '2009-06-11 07:17:23',) );

		$posts = $this->_get_query_result( array(
			'date_query' => array(
				array(
					'before' => 'May 4th, 2008',
				),
			),
		) );

		$this->assertEquals( array( $p1, $p2 ), wp_list_pluck( $posts, 'ID' ) );
	}

	public function test_date_query_after_array() {
		$p1 = self::factory()->post->create( array( 'post_date' => '2009-10-18 10:42:29', ) );
		$p2 = self::factory()->post->create( array( 'post_date' => '2009-12-18 10:42:29', ) );
		$p3 = self::factory()->post->create( array( 'post_date' => '2010-06-11 07:17:23', ) );

		$posts = $this->_get_query_result( array(
			'date_query' => array(
				array(
					'after' => array(
						'year'  => 2009,
						'month' => 12,
						'day'   => 31,
					),
				),
			),
		) );

		$this->assertEqualSets( array( $p3 ), wp_list_pluck( $posts, 'ID' ) );
	}


	public function test_date_query_after_array_test_defaulting() {
		$p1 = self::factory()->post->create( array( 'post_date' => '2008-12-18 10:42:29', ) );
		$p2 = self::factory()->post->create( array( 'post_date' => '2009-01-18 10:42:29', ) );

		$posts = $this->_get_query_result( array(
			'date_query' => array(
				array(
					'after' => array(
						'year' => 2008,
					),
				),
			),
		) );

		$this->assertEquals( array( $p2 ), wp_list_pluck( $posts, 'ID' ) );
	}
	public function test_date_query_after_string() {
		$p1 = self::factory()->post->create( array( 'post_date' => '2009-12-18 09:42:29', ) );
		$p2 = self::factory()->post->create( array( 'post_date' => '2009-12-18 10:42:29', ) );
		$p3 = self::factory()->post->create( array( 'post_date' => '2009-12-19 10:42:29', ) );

		$posts = $this->_get_query_result( array(
			'date_query' => array(
				array(
					'after' => '2009-12-18 10:42:29',
				),
			),
		) );

		$this->assertEquals( array( $p3 ), wp_list_pluck( $posts, 'ID' ) );
	}

	public function test_date_query_after_string_inclusive() {
		$p1 = self::factory()->post->create( array( 'post_date' => '2009-12-18 09:42:29', ) );
		$p2 = self::factory()->post->create( array( 'post_date' => '2009-12-18 10:42:29', ) );
		$p3 = self::factory()->post->create( array( 'post_date' => '2009-12-19 10:42:29', ) );

		$posts = $this->_get_query_result( array(
			'date_query' => array(
				array(
					'after'     => '2009-12-18 10:42:29',
					'inclusive' => true,
				),
			),
		) );

		$this->assertEquals( array( $p2, $p3 ), wp_list_pluck( $posts, 'ID' ) );
	}
	public function test_date_query_inclusive_between_dates() {
		$p1 = self::factory()->post->create( array( 'post_date' => '2006-12-18 09:42:29', ) );
		$p2 = self::factory()->post->create( array( 'post_date' => '2007-01-18 10:42:29', ) );
		$p3 = self::factory()->post->create( array( 'post_date' => '2007-12-19 10:42:29', ) );
		$p4 = self::factory()->post->create( array( 'post_date' => '2008-12-19 10:42:29', ) );
		$p5 = self::factory()->post->create( array( 'post_date' => '2009-12-19 10:42:29', ) );

		$posts = $this->_get_query_result( array(
			'date_query' => array(
				'after' => array(
					'year' => 2007,
					'month' => 1
				),
				'before' => array(
					'year' => 2008,
					'month' => 12
				),
				'inclusive' => true
			),
		) );
		$this->assertEquals( array( $p2, $p3, $p4 ), wp_list_pluck( $posts, 'ID' ) );
	}
}
<?php
/**
 * Tests for class Block.
 *
 * @package SiteCounts
 */

namespace XWP\SiteCounts;

use Mockery;
use stdClass;
use WP_Block;
use WP_Mock;

/**
 * Tests for class Block.
 */
class TestBlock extends TestCase {

	/**
	 * The instance to test.
	 *
	 * @var Block
	 */
	public $instance;

	/**
	 * The mock class name to pass in the block attributes.
	 *
	 * @var string
	 */
	const CLASS_NAME = 'example-class';

	/**
	 * The mock post ID.
	 *
	 * @var int
	 */
	const POST_ID = 12345;

	/**
	 * Sets up each unit test.
	 *
	 * @inheritdoc
	 */
	public function setUp() : void {
		parent::setUp();
		$this->instance = new Block( new Plugin( __FILE__ ) );
	}

	/**
	 * Test init.
	 *
	 * @covers \XWP\SiteCounts\Block::init()
	 */
	public function test_init() {
		WP_Mock::expectActionAdded( 'init', [ $this->instance, 'register_block' ], 10, 1 );
		$this->instance->init();
	}

	/**
	 * Test register_block.
	 *
	 * @covers \XWP\SiteCounts\Block::register_block()
	 */
	public function test_register_block() {
		WP_Mock::userFunction( 'register_block_type_from_metadata' )
			->once()
			->with(
				dirname( __FILE__ ),
				[
					'render_callback' => [ $this->instance, 'render_callback' ],
				]
			);

		$this->instance->register_block();
	}

	/**
	 * Gets the test data for test_render_callback().
	 *
	 * @return array The test data.
	 */
	public function get_render_callback_data() {
		return [
			'zero_post_counts' => [
				0,
				0,
				0,
				[
					'className'           => self::CLASS_NAME,
					'displayTemplateMode' => true,
				],
				'<div class="' . self::CLASS_NAME . '"><h2>Post Counts</h2><ul><li>There are 0 Posts.</li><li>There are 0 Pages.</li><li>There are 0 Media.</li></ul><p>The current post ID is ' . self::POST_ID . '.</p><h2>Any 5 posts with the tag of foo and the category of baz</h2><ul><li>Example Post 0</li><li>Example Post 1</li><li>Example Post 2</li><li>Example Post 3</li><li>Example Post 4</li></ul></div>',
			],
			'with_post_counts' => [
				63,
				13,
				139,
				[
					'className'           => self::CLASS_NAME,
					'displayTemplateMode' => true,
				],
				'<div class="' . self::CLASS_NAME . '"><h2>Post Counts</h2><ul><li>There are 63 Posts.</li><li>There are 13 Pages.</li><li>There are 139 Media.</li></ul><p>The current post ID is ' . self::POST_ID . '.</p><h2>Any 5 posts with the tag of foo and the category of baz</h2><ul><li>Example Post 0</li><li>Example Post 1</li><li>Example Post 2</li><li>Example Post 3</li><li>Example Post 4</li></ul></div>',
			],
		];
	}

	/**
	 * Test render_callback.
	 *
	 * @dataProvider get_render_callback_data
	 * @covers \XWP\SiteCounts\Block::render_callback()
	 *
	 * @param int    $post_count       Amount of posts.
	 * @param int    $page_count       Amount of pages.
	 * @param int    $attachment_count Amount of attachments.
	 * @param array  $attributes       Block attributes.
	 * @param string $expected         Expected rendered output.
	 */
	public function test_render_callback( $post_count, $page_count, $attachment_count, $attributes, $expected ) {
		$_GET = [ 'post_id' => self::POST_ID ];
		WP_Mock::userFunction( 'get_post_types' )
			->with( [ 'public' => true ] )
			->andReturn( [ 'post', 'page', 'attachment' ] );

		$this->mock_get_post_type_object( 'Post', 'Posts' );
		$this->mock_get_post_type_object( 'Page', 'Pages' );
		$this->mock_get_post_type_object( 'Media', 'Media' );

		$this->mock_get_posts( $post_count );
		$this->mock_get_posts( $page_count );
		$this->mock_get_posts( $attachment_count );

		WP_Mock::userFunction( 'get_the_ID' );
		Mockery::mock( 'WP_Block' );

		$mock_posts    = $this->get_posts();
		$wp_query_mock = Mockery::instanceMock( 'overload:WP_Query' );
		$wp_query_mock->shouldReceive( '__construct' )
			->once()
			->andSet( 'found_posts', [ count( $mock_posts ) ] )
			->andSet( 'posts', $mock_posts );

		$actual = $this->instance->render_callback( $attributes, '', new WP_Block() );
		$actual = preg_replace( '/(?<=>)\s+/', '', $actual );
		$actual = preg_replace( '/\s+(?=<)/', '', $actual );

		$this->assertEquals(
			$expected,
			$actual
		);
	}

	/**
	 * Mocks get_post_type_object().
	 *
	 * @param string $singular The singular form of the CPT.
	 * @param string $plural   The plural form of the CPT.
	 */
	function mock_get_post_type_object( $singular, $plural ) {
		$post                        = new stdClass();
		$post->labels                = new stdClass();
		$post->labels->name          = $plural;
		$post->labels->singular_name = $singular;

		WP_Mock::userFunction( 'get_post_type_object' )
			->once()
			->andReturn( $post );
	}

	/**
	 * Mocks get_posts().
	 *
	 * @param int $post_count The number of posts.
	 */
	function mock_get_posts( $post_count ) {
		WP_Mock::userFunction( 'get_posts' )
			->once()
			->andReturn( array_fill( 0, $post_count, new stdClass() ) );
	}

	/**
	 * Gets the posts.
	 *
	 * @return stdClass[] The posts.
	 */
	public function get_posts() {
		$posts = [];
		for ( $i = 0; $i < 5; $i++ ) {
			$post             = new stdClass();
			$post->post_title = "Example Post {$i}";
			$posts[]          = $post;
		}

		return $posts;
	}
}

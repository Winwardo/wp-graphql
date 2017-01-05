<?php
namespace DFM\WPGraphQL\Setup;
use DFM\WPGraphQL\Fields\MediaDetailsFieldType;
use Youshido\GraphQL\Execution\ResolveInfo;
use Youshido\GraphQL\Type\Scalar\IntType;
use Youshido\GraphQL\Type\Scalar\StringType;


/**
 * Class Init
 *
 * This sets up the PostType entities to be exposed to the RootQuery
 *
 * @package DFM\WPGraphQL\Queries\PostEntities
 * @since 0.0.2
 */
class PostEntities {

	/**
	 * allowed_post_types
	 *
	 * Holds an array of the post_types allowed to be exposed in the GraphQL Queries
	 *
	 * @var array
	 * @since 0.0.2
	 */
	public $allowed_post_types = [];

	/**
	 * PostsQueries constructor.
	 *
	 * Placeholder
	 *
	 * @since 0.0.2
	 */
	public function __construct() {
		// Placeholder
	}

	/**
	 * init
	 *
	 * Setup the root queries for each allowed_post_type
	 *
	 * @return void
	 * @since 0.0.2
	 *
	 */
	public function init() {

		// Add the post_types to the root_queries
		add_action( 'wpgraphql_root_queries', [ $this, 'setup_post_type_queries' ], 999, 1 );

		// Set default query args for the attachment post_type
		add_action( 'wpgraphql_post_object_query_query_arg_defaults_attachment', [ $this, 'default_attachment_query_args' ] );

		// Add fields to the attachment post_type
		add_filter( 'wpgraphql_post_object_type_fields_attachment', [ $this, 'add_attachment_post_object_fields' ], 10, 1 );

	}


	/**
	 * Filter the core post types to "show_in_graphql"
	 *
	 * Additional post_types can be given GraphQL support in the same way, by adding the
	 * "show_in_graphql" and optionally a "graphql_query_class". If no "graphql_query_class" is provided
	 * the default "PostObjectQuery" class will be used which provides the standard fields for all
	 * post objects.
	 *
	 * @since 0.0.2
	 */
	public function show_post_types_in_graphql(){

		global $wp_post_types;

		if ( isset( $wp_post_types['attachment'] ) ) {
			$wp_post_types['attachment']->show_in_graphql = true;
			//$wp_post_types['attachment']->graphql_query_class = '\DFM\WPGraphQL\Types\Attachments\Query';
			//$wp_post_types['attachment']->graphql_mutation_class = '\DFM\WPGraphQL\Types\Attachments\Mutation';
			//$wp_post_types['attachment']->graphql_type_class = '\DFM\WPGraphQL\Types\Attachments\AttachmentType';
		}

		if ( isset( $wp_post_types['page'] ) ) {
			$wp_post_types['page']->show_in_graphql = true;
			//$wp_post_types['page']->graphql_query_class = '\DFM\WPGraphQL\Types\Pages\Query';
			//$wp_post_types['page']->graphql_mutation_class = '\DFM\WPGraphQL\Types\Pages\Mutation';
			//$wp_post_types['page']->graphql_type_class = '\DFM\WPGraphQL\Types\Pages\PageType';
		}

		if ( isset( $wp_post_types['post'] ) ) {
			$wp_post_types['post']->show_in_graphql = true;
			//$wp_post_types['post']->graphql_query_class = '\DFM\WPGraphQL\Types\Posts\Query';
			//$wp_post_types['post']->graphql_mutation_class = '\DFM\WPGraphQL\Types\Posts\Mutation';
			//$wp_post_types['post']->graphql_type_class = '\DFM\WPGraphQL\Types\Posts\PostType';
		}

	}

	/**
	 * setup_post_type_queries
	 *
	 * This sets up post_type_queries for all post_types that have "set_in_graphql"
	 * set to "true" on their post_type_object
	 *
	 * @since 0.0.2
	 * @param $fields
	 * @return array
	 */
	public function setup_post_type_queries( $fields ) {

		/**
		 * Add core post_types to show in GraohQL
		 */
		$this->show_post_types_in_graphql();

		/**
		 * Get all post_types that have been registered to "show_in_graphql"
		 */
		$post_types = get_post_types( [ 'show_in_graphql' => true ] );

		/**
		 * Define the $allowed_post_types to be exposed by GraphQL Queries
		 * Pass through a filter to allow the post_types to be modified (for example if
		 * a certain post_type should not be exposed to the GraphQL API)
		 */
		$this->allowed_post_types = apply_filters( 'wpgraphql_post_queries_allowed_post_types', $post_types );

		if ( ! empty( $this->allowed_post_types ) && is_array( $this->allowed_post_types ) ) {

			/**
			 * Loop through each of the allowed_post_types
			 */
			foreach( $this->allowed_post_types as $allowed_post_type ) {

				/**
				 * Get the query class from the post_type_object
				 */
				$post_type_query_class = get_post_type_object( $allowed_post_type )->graphql_query_class;

				/**
				 * If the post_type has a "graphql_query_class" defined, use it
				 * Otherwise fall back to the standard PostObjectQuery class
				 */
				$class = ( ! empty( $post_type_query_class ) && class_exists( $post_type_query_class ) ) ? $post_type_query_class  : '\DFM\WPGraphQL\Types\PostObject\PostObjectQueryType';

				/**
				 * Adds the class to the RootQueryType
				 */
				$fields[] = new $class( [ 'post_type' => $allowed_post_type ] );

				/**
				 * Run an action after each allowed_post_type is added to the root_query
				 *
				 * @since 0.0.2
				 */
				do_action( 'wpgraphql_after_setup_post_type_query_' . $allowed_post_type, $allowed_post_type, $this->allowed_post_types );

			}

		}

		/**
		 * Returns the fields
		 */
		return $fields;

	}

	/**
	 * @param $args
	 * @return mixed
	 * @since 0.0.2
	 */
	public function default_attachment_query_args( $args ) {

		$args['post_status'] = 'inherit';
		return $args;

	}

	/**
	 * This adds additional fields to the Attachment post_object
	 *
	 * @param $fields
	 * @return array
	 * @since 0.0.2
	 */
	public function add_attachment_post_object_fields( $fields ) {

		$fields[] = [
			'name' => 'caption',
			'type' => new StringType(),
			'description' => __( 'The caption for the resource', 'wp-graphql' ),
			'resolve' => function( $value, array $args, ResolveInfo $info ) {
				return esc_html( $value->post_excerpt );
			}
		];

		$fields[] = [
			'name' => 'alt_text',
			'type' => new StringType(),
			'description' => __( 'Alternative text to display when resource is not displayed', 'wp-graphql' ),
			'resolve' => function( $value, array $args, ResolveInfo $info ) {
				return esc_html( get_post_meta( $value->ID, '_wp_attachment_image_alt', true ) );
			}
		];

		$fields[] = [
			'name' => 'description',
			'type' => new StringType(),
			'description' => __( 'The description for the resource', 'wp-graphql' ),
			'resolve' => function( $value, array $args, ResolveInfo $info ) {
				return esc_html( $value->post_excerpt );
			}
		];

		$fields[] = [
			'name' => 'media_type',
			'type' => new StringType(),
			'description' => __( 'Type of resource', 'wp-graphql' ),
			'resolve' => function( $value, array $args, ResolveInfo $info ) {
				return wp_attachment_is_image( $value->ID ) ? 'image' : 'file';
			}
		];

		$fields[] = [
			'name' => 'mime_type',
			'type' => new StringType(),
			'description' => __( 'Mime type of resource', 'wp-graphql' ),
			'resolve' => function( $value, array $args, ResolveInfo $info ) {
				return esc_html( $value->post_mime_type );
			}
		];

		// @todo: add support for media details

		$fields[] = [
			'name' => 'associtated_post_id',
			'type' => new IntType(),
			'description' => __( 'The id for the associated post of the resource.', 'wp-graphql' ),
			'resolve' => function( $value, array $args, ResolveInfo $info ) {
				return ! empty( $value->post_parent ) ? (int) $value->post_parent : null;
			}
		];

		$fields[] = [
			'name' => 'source_url',
			'type' => new IntType(),
			'description' => __( 'The id for the associated post of the resource.', 'wp-graphql' ),
			'resolve' => function( $value, array $args, ResolveInfo $info ) {
				return wp_get_attachment_url( $value->ID );
			}
		];

		$fields[] = [
			'name' => 'media_details',
			'type' => new MediaDetailsFieldType(),
			'description' => __( 'Details about the media object.', 'wp-graphql' ),
			'resolve' => function( $value, array $args, ResolveInfo $info ) {
				return $value;
			}
		];

		return $fields;

	}

}
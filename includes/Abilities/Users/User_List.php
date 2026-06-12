<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Users;

use AcrossAI_Abilities_Manager\Includes\Modules\Library\Ability_Definition;
use Acrossai_Core_Abilities\Includes\Utilities\User_Helpers;

defined( 'ABSPATH' ) || exit;

class User_List extends Ability_Definition {

	protected function ability(): array {
		return array(
			'name' => 'acrossai-core-abilities/user-list',
			'args' => array(
				'label'               => __( 'List Users', 'acrossai-core-abilities' ),
				'description'         => __( 'List WordPress users with optional role filter, search, and pagination.', 'acrossai-core-abilities' ),
				'category'            => 'acrossai-core-abilities-users',
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'list_users' );
				},
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'role'    => array(
							'type'        => 'string',
							'description' => __( 'Filter by role slug (e.g. administrator, editor).', 'acrossai-core-abilities' ),
						),
						'search'  => array(
							'type'        => 'string',
							'description' => __( 'Search string matched against login, email, display name, and URL.', 'acrossai-core-abilities' ),
						),
						'number'  => array(
							'type'        => 'integer',
							'default'     => 100,
							'description' => __( 'Number of users to return (1–500).', 'acrossai-core-abilities' ),
						),
						'paged'   => array(
							'type'        => 'integer',
							'default'     => 1,
							'description' => __( 'Page number for pagination.', 'acrossai-core-abilities' ),
						),
						'orderby' => array(
							'type'        => 'string',
							'enum'        => array( 'id', 'login', 'email', 'registered', 'display_name' ),
							'default'     => 'id',
							'description' => __( 'Order results by field.', 'acrossai-core-abilities' ),
						),
						'order'   => array(
							'type'        => 'string',
							'enum'        => array( 'ASC', 'DESC' ),
							'default'     => 'ASC',
							'description' => __( 'Sort direction.', 'acrossai-core-abilities' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'users' => array( 'type' => 'array' ),
						'total' => array( 'type' => 'integer' ),
						'page'  => array( 'type' => 'integer' ),
					),
				),
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
						'type'   => 'tool',
					),
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			),
		);
	}

	public function execute( array $input = array() ): array {
		$number = isset( $input['number'] ) ? (int) $input['number'] : 100;
		$number = max( 1, min( 500, $number ) );

		$paged = isset( $input['paged'] ) ? max( 1, (int) $input['paged'] ) : 1;

		$orderby = isset( $input['orderby'] ) ? sanitize_text_field( $input['orderby'] ) : 'id';
		if ( ! in_array( $orderby, array( 'id', 'login', 'email', 'registered', 'display_name' ), true ) ) {
			$orderby = 'id';
		}

		$order = isset( $input['order'] ) ? strtoupper( sanitize_text_field( $input['order'] ) ) : 'ASC';
		if ( ! in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
			$order = 'ASC';
		}

		$args = array(
			'number'  => $number,
			'paged'   => $paged,
			'orderby' => $orderby,
			'order'   => $order,
		);

		if ( ! empty( $input['role'] ) ) {
			$args['role'] = sanitize_text_field( $input['role'] );
		}

		if ( ! empty( $input['search'] ) ) {
			$args['search']         = '*' . sanitize_text_field( $input['search'] ) . '*';
			$args['search_columns'] = array( 'user_login', 'user_email', 'display_name', 'user_url' );
		}

		$query    = new \WP_User_Query( $args );
		$users    = $query->get_results();
		$total    = (int) $query->get_total();
		$formatted = array();

		foreach ( $users as $user ) {
			if ( $user instanceof \WP_User ) {
				$formatted[] = User_Helpers::format_user( $user );
			}
		}

		return array(
			'users' => $formatted,
			'total' => $total,
			'page'  => $paged,
		);
	}
}

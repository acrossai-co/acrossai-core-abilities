<?php
namespace Acrossai_Core_Abilities\Includes\Abilities\Comments;

defined( 'ABSPATH' ) || exit;

/**
 * Shared helper for Approve / Unapprove / Mark-as-Spam abilities — all three
 * dispatch the same POST /wp/v2/comments/{id} with a different status value.
 */
final class Moderation {

	public static function set_status( int $id, string $status ): array {
		if ( $id <= 0 ) {
			return array( 'success' => false, 'message' => __( 'A valid id is required.', 'acrossai-core-abilities' ) );
		}

		$request = new \WP_REST_Request( 'POST', '/wp/v2/comments/' . $id );
		$request->set_param( 'status', $status );

		$response = rest_do_request( $request );
		if ( $response->is_error() ) {
			return array(
				'success' => false,
				'message' => $response->as_error()->get_error_message(),
			);
		}

		return array(
			'success' => true,
			'comment' => (array) $response->get_data(),
			/* translators: 1: comment ID, 2: status */
			'message' => sprintf( __( 'Comment #%1$d set to "%2$s".', 'acrossai-core-abilities' ), $id, $status ),
		);
	}
}

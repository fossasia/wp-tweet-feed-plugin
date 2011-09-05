<?php

class TLC_Transient_Update_Server {
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {
		if ( isset( $_POST['_tlc_update'] ) ) {
			$update = get_transient( 'tlc_up__' . $_POST['key'] );
			if ( $update && $update[0] == $_POST['_tlc_update'] ) {
				tlc_transient( $update[1] )
					->expires_in( $update[2] )
					->updates_with( $update[3], (array) $update[4] )
					->set_lock( $update[0] )
					->fetch_and_cache();
			}
			exit();
		}
	}
}

new TLC_Transient_Update_Server;

if ( !class_exists( 'TLC_Transient' ) ) {
	class TLC_Transient {
		public $key;
		private $lock;
		private $callback;
		private $params;
		private $expiration = 0;
		private $force_background_updates = false;

		public function __construct( $key ) {
			$this->key = substr( $key, 0, 37 );
		}

		public function get() {
			$data = get_transient( $this->key );
			if ( false === $data ) {
				// Hard expiration
				if ( $this->force_background_updates ) {
					// In this mode, we never do a just-in-time update
					// We return false, and schedule a fetch on shutdown
					$this->schedule_background_fetch();
					return false;
				} else {
					// Bill O'Reilly mode: "We'll do it live!"
					return $this->fetch_and_cache();
				}
			} else {
				// Soft expiration
				if ( $data[0] !== 0 && $data[0] < time() )
					$this->schedule_background_fetch();
				return $data[1];
			}
		}

		private function schedule_background_fetch() {
			if ( !$this->has_update_lock() ) {
				set_transient( 'tlc_up__' . $this->key, array( $this->new_update_lock(), $this->key, $this->expiration, $this->callback, $this->params ), 300 );
				add_action( 'shutdown', array( $this, 'spawn_server' ) );
			}
			return $this;
		}

		public function spawn_server() {
			$server_url = home_url( '/?tlc_transients_request' );
			wp_remote_post( $server_url, array( 'body' => array( '_tlc_update' => $this->lock, 'key' => $this->key ), 'timeout' => 0.01, 'blocking' => false, 'sslverify' => apply_filters( 'https_local_ssl_verify', true ) ) );
		}

		public function fetch_and_cache() {
			// If you don't supply a callback, we can't update it for you!
			if ( empty( $this->callback ) )
				return false;
			if ( $this->has_update_lock() && !$this->owns_update_lock() )
				return; // Race... let the other process handle it
			try {
				$data = call_user_func_array( $this->callback, $this->params );
				$this->set( $data );
			} catch( Exception $e ) {}
			$this->release_update_lock();
			return $data;
		}

		public function set( $data ) {
			// We set the timeout as part of the transient data.
			// The actual transient has no TTL. This allows for soft expiration.
			$expiration = ( $this->expiration > 0 ) ? time() + $this->expiration : 0;
			set_transient( $this->key, array( $expiration, $data ) );
			return $this;
		}

		public function updates_with( $callback, $params = array() ) {
			$this->callback = $callback;
			if ( is_array( $params ) )
				$this->params = $params;
			return $this;
		}

		private function new_update_lock() {
			$this->lock = md5( uniqid( microtime() . mt_rand(), true ) );
			return $this->lock;
		}

		private function release_update_lock() {
			delete_transient( 'tlc_up__' . $this->key );
		}

		private function get_update_lock() {
			$lock = get_transient( 'tlc_up__' . $this->key );
			if ( $lock )
				return $lock[0];
			else
				return false;
		}

		private function has_update_lock() {
			return (bool) $this->get_update_lock();
		}

		private function owns_update_lock() {
			return $this->lock == $this->get_update_lock();
		}

		public function expires_in( $seconds ) {
			$this->expiration = (int) $seconds;
			return $this;
		}

		public function set_lock( $lock ) {
			$this->lock = $lock;
			return $this;
		}

		public function background_only() {
			$this->force_background_updates = true;
			return $this;
		}
	}
}

// API so you don't have to use "new"
function tlc_transient( $key ) {
	$transient = new TLC_Transient( $key );
	return $transient;
}

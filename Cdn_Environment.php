<?php
namespace W3TC;






/**
 * class Cdn_Environment
 */
class Cdn_Environment {

	/**
	 * Fixes environment in each wp-admin request
	 *
	 * @param Config  $config
	 * @param bool    $force_all_checks
	 * @throws Util_Environment_Exceptions
	 */
	public function fix_on_wpadmin_request( $config, $force_all_checks ) {
		$exs = new Util_Environment_Exceptions();

		if ( $config->get_boolean( 'config.check' ) || $force_all_checks ) {
			if ( $config->get_boolean( 'cdn.enabled' ) ) {
				$this->rules_add( $config, $exs );
			} else {
				$this->rules_remove( $exs );
			}
		}

		if ( count( $exs->exceptions() ) > 0 )
			throw $exs;
	}

	/**
	 * Fixes environment once event occurs
	 *
	 * @param Config  $config
	 * @param string  $event
	 * @param Config|null $old_config
	 * @throws Util_Environment_Exceptions
	 */
	public function fix_on_event( $config, $event, $old_config = null ) {
		if ( $config->get_boolean( 'cdn.enabled' ) &&
			!Cdn_Util::is_engine_mirror( $config->get_string( 'cdn.engine' ) ) ) {
			if ( $old_config != null &&
				$config->get_integer( 'cdn.queue.interval' ) !=
				$old_config->get_integer( 'cdn.queue.interval' ) ) {
				$this->unschedule_queue_process();
			}

			if ( !wp_next_scheduled( 'w3_cdn_cron_queue_process' ) ) {
				wp_schedule_event( time(),
					'w3_cdn_cron_queue_process', 'w3_cdn_cron_queue_process' );
			}
		} else {
			$this->unschedule_queue_process();
		}

		if ( $config->get_boolean( 'cdn.enabled' ) &&
			$config->get_boolean( 'cdn.autoupload.enabled' ) &&
			!Cdn_Util::is_engine_mirror( $config->get_string( 'cdn.engine' ) ) ) {
			if ( $old_config != null &&
				$config->get_integer( 'cdn.autoupload.interval' ) !=
				$old_config->get_integer( 'cdn.autoupload.interval' ) ) {
				$this->unschedule_upload();
			}

			if ( !wp_next_scheduled( 'w3_cdn_cron_upload' ) ) {
				wp_schedule_event( time(),
					'w3_cdn_cron_upload', 'w3_cdn_cron_upload' );
			}
		} else {
			$this->unschedule_upload();
		}

		$exs = new Util_Environment_Exceptions();

		if ( $config->get_boolean( 'cdn.enabled' ) ) {
			try {
				$this->table_create( $event == 'activate' );
			} catch ( \Exception $ex ) {
				$exs->push( $ex );
			}
		}

		if ( count( $exs->exceptions() ) > 0 )
			throw $exs;
	}

	/**
	 * Fixes environment after plugin deactivation
	 */
	public function fix_after_deactivation() {
		$exs = new Util_Environment_Exceptions();

		$this->rules_remove( $exs );
		$this->table_delete();

		if ( count( $exs->exceptions() ) > 0 )
			throw $exs;
	}

	/**
	 * Returns required rules for module
	 *
	 * @param Config  $config
	 * @return array|null
	 */
	function get_required_rules( $config ) {
		if ( !$config->get_boolean( 'cdn.enabled' ) )
			return null;

		$rewrite_rules = array();
		$rules = $this->rules_generate( $config );

		if ( strlen( $rules ) > 0 ) {
			if ( $config->get_string( 'cdn.engine' ) == 'ftp' ) {
				$common = Dispatcher::component( 'Cdn_Core' );
				$domain = $common->get_cdn()->get_domain();
				$cdn_rules_path = sprintf( 'ftp://%s/%s', $domain,
					Util_Rule::get_cdn_rules_path() );
				$rewrite_rules[] = array(
					'filename' => $cdn_rules_path,
					'content' => $rules
				);
			}

			$path = Util_Rule::get_browsercache_rules_cache_path();
			$rewrite_rules[] = array(
				'filename' => $path,
				'content' => $rules
			);
		}
		return $rewrite_rules;
	}

	/**
	 *
	 *
	 * @param Config  $config
	 * @return array|null
	 */
	function get_instructions( $config ) {
		if ( !$config->get_boolean( 'cdn.enabled' ) )
			return null;

		$instructions = array();
		$instructions[] = array( 'title'=>__( 'CDN module: Required Database SQL', 'w3-total-cache' ),
			'content' => $this->generate_table_sql(), 'area' => 'database' );

		return $instructions;
	}

	/**
	 * Generate rules for FTP
	 */
	public function rules_generate_for_ftp( $config ) {
		return $this->rules_generate( $config, true );
	}



	/*
	 * table operations
	 */

    /**
     * Create or update Google Drive table
     *
     * @param string $charset_collate
     * @throws Util_Environment_Exception
     */
    private function google_drive_table_create($charset_collate) {
        // NOTE: We have a problem if two different paths have the same MD5
        // hash. The likelihood of this is so astronomically low that I think
        // it is safe to ignore it.
        global $wpdb;
        $table_name = $wpdb->base_prefix . W3TC_CDN_TABLE_GOOGLE_DRIVE;
        $q_table_exists = "SELECT id FROM `$table_name` LIMIT 1";
        if (FALSE === $wpdb->query($q_table_exists)) {
            $q_create_table = "
                CREATE TABLE `$table_name` (
                    -- Relative file path.
                    -- For reference, not actually used for finding files.
                    remote_path TEXT NOT NULL,
                    -- MD5 hash of remote path, used for finding files.
                    path_hash VARCHAR(32) CHARACTER SET ascii NOT NULL,
                    -- Google Drive document identifier.
                    id VARCHAR(64) CHARACTER SET ascii,
                    PRIMARY KEY (path_hash)
                ) $charset_collate";
            if (FALSE === $wpdb->query($q_create_table)) {
                throw new Util_Environment_Exception(
                    "Can't create table $table_name");
            }
        } else {
            $q_column_exists = (
                "SELECT path_hash FROM `$table_name` LIMIT 1");
            if (FALSE === $wpdb->query($q_column_exists)) {
                // We can't calculate correct MD5 for these, so we'll have to
                // remove them and let them get uploaded again.
                $q_delete_long = "
                    DELETE FROM `$table_name`
                        WHERE LENGTH(remote_path) >= 500";
                $q_add_column = "
                    ALTER TABLE `$table_name`
                        CHANGE COLUMN remote_path
                            remote_path TEXT NOT NULL,
                        CHANGE COLUMN id id VARCHAR(64) CHARACTER SET ascii,
                        ADD COLUMN
                            path_hash VARCHAR(32) CHARACTER SET ascii
                            NOT NULL";
                $q_hash_paths = "
                    UPDATE `$table_name`
                        SET path_hash=MD5(remote_path)";
                // Separate from ALTER TABLE above because we can't do this
                // until after the path_hash column is populated.
                $q_primary_key = "
                    ALTER TABLE `$table_name`
                        DROP primary key,
                        ADD primary key (path_hash)";
                $success = (
                    (FALSE !== $wpdb->query($q_delete_long)) &&
                    (FALSE !== $wpdb->query($q_add_column)) &&
                    (FALSE !== $wpdb->query($q_hash_paths)) &&
                    (FALSE !== $wpdb->query($q_primary_key)));
                if (! $success) {
                    throw new Util_Environment_Exception(
                        "Can't alter table $table_name");
                }
            }
        }
    }

	/**
	 * Create queue and Google Drive tables
	 *
	 * @param bool    $drop
	 * @throws Util_Environment_Exception
	 */
	private function table_create( $drop = false ) {
		global $wpdb;

		if ( $drop ) {
			$sql = sprintf( 'DROP TABLE IF EXISTS `%s%s`;', $wpdb->base_prefix, W3TC_CDN_TABLE_QUEUE );

			$wpdb->query( $sql );
		}

		$charset_collate = '';

		if ( ! empty( $wpdb->charset ) )
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if ( ! empty( $wpdb->collate ) )
			$charset_collate .= " COLLATE $wpdb->collate";

		$sql = sprintf( "CREATE TABLE IF NOT EXISTS `%s%s` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `local_path` varchar(500) NOT NULL DEFAULT '',
            `remote_path` varchar(500) NOT NULL DEFAULT '',
            `command` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1 - Upload, 2 - Delete, 3 - Purge',
            `last_error` varchar(150) NOT NULL DEFAULT '',
            `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (`id`),
            KEY `date` (`date`)
        ) $charset_collate;", $wpdb->base_prefix, W3TC_CDN_TABLE_QUEUE );

		$wpdb->query( $sql );

		if ( !$wpdb->result )
			throw new Util_Environment_Exception( 'Can\'t create table ' .
				$wpdb->base_prefix . W3TC_CDN_TABLE_QUEUE );

        $this->google_drive_table_create($charset_collate);
	}

	/**
	 * Delete queue table
	 *
	 * @return void
	 */
	private function table_delete() {
		global $wpdb;

		$sql = sprintf( 'DROP TABLE IF EXISTS `%s%s`', $wpdb->base_prefix, W3TC_CDN_TABLE_QUEUE );
		$wpdb->query( $sql );
	}

	private function generate_table_sql() {
		global $wpdb;
		$charset_collate = '';

		if ( ! empty( $wpdb->charset ) )
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if ( ! empty( $wpdb->collate ) )
			$charset_collate .= " COLLATE $wpdb->collate";

		$sql = sprintf( 'DROP TABLE IF EXISTS `%s%s`;', $wpdb->base_prefix, W3TC_CDN_TABLE_QUEUE );
		$sql .= "\n" . sprintf( "CREATE TABLE IF NOT EXISTS `%s%s` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `local_path` varchar(500) NOT NULL DEFAULT '',
            `remote_path` varchar(500) NOT NULL DEFAULT '',
            `command` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1 - Upload, 2 - Delete, 3 - Purge',
            `last_error` varchar(150) NOT NULL DEFAULT '',
            `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (`id`),
            KEY `date` (`date`)
        ) $charset_collate;", $wpdb->base_prefix, W3TC_CDN_TABLE_QUEUE );

		return $sql;
	}

	/**
	 * schedules
	 */

	/**
	 * Unschedules cron events
	 */
	private function unschedule_queue_process() {
		if ( wp_next_scheduled( 'w3_cdn_cron_queue_process' ) ) {
			wp_clear_scheduled_hook( 'w3_cdn_cron_queue_process' );
		}
	}

	/**
	 * Unschedule upload event
	 */
	private function unschedule_upload() {
		if ( wp_next_scheduled( 'w3_cdn_cron_upload' ) ) {
			wp_clear_scheduled_hook( 'w3_cdn_cron_upload' );
		}
	}



	/*
	 * rules core modification
	 */

	/**
	 * Writes directives to WP .htaccess
	 *
	 * @param Config  $config
	 * @param Util_Environment_Exceptions $exs
	 * @throws Util_WpFile_FilesystemOperationException with S/FTP form if it can't get the required filesystem credentials
	 */
	private function rules_add( $config, $exs ) {
		Util_Rule::add_rules( $exs, Util_Rule::get_browsercache_rules_cache_path(),
			$this->rules_generate( $config ),
			W3TC_MARKER_BEGIN_CDN,
			W3TC_MARKER_END_CDN,
			array(
				W3TC_MARKER_BEGIN_MINIFY_CORE => 0,
				W3TC_MARKER_BEGIN_PGCACHE_CORE => 0,
				W3TC_MARKER_BEGIN_BROWSERCACHE_NO404WP => 0,
				W3TC_MARKER_BEGIN_BROWSERCACHE_CACHE => 0,
				W3TC_MARKER_BEGIN_WORDPRESS => 0,
				W3TC_MARKER_END_PGCACHE_CACHE => strlen( W3TC_MARKER_END_PGCACHE_CACHE ) + 1,
				W3TC_MARKER_END_MINIFY_CACHE => strlen( W3TC_MARKER_END_MINIFY_CACHE ) + 1
			)
		);
	}

	/**
	 * Removes Page Cache core directives
	 *
	 * @param Util_Environment_Exceptions $exs
	 * @throws Util_WpFile_FilesystemOperationException with S/FTP form if it can't get the required filesystem credentials
	 */
	private function rules_remove( $exs ) {
		Util_Rule::remove_rules( $exs,
			Util_Rule::get_browsercache_rules_cache_path(),
			W3TC_MARKER_BEGIN_CDN,
			W3TC_MARKER_END_CDN );
	}

	/**
	 * Generates rules for WP dir
	 *
	 * @param Config  $config
	 * @param bool    $cdnftp
	 * @return string
	 */
	private function rules_generate( $config, $cdnftp = false ) {
		$rules = '';
		if ( Dispatcher::canonical_generated_by( $config, $cdnftp ) == 'cdn' )
			$rules .= Util_RuleSnippet::canonical( $config, $cdnftp );
		if ( Dispatcher::allow_origin_generated_by( $config ) == 'cdn' )
			$rules .= Util_RuleSnippet::allow_origin( $config, $cdnftp );

		if ( strlen( $rules ) > 0 )
			$rules =
				W3TC_MARKER_BEGIN_CDN . "\n" .
				$rules .
				W3TC_MARKER_END_CDN . "\n";

		return $rules;
	}
}

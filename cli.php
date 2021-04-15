<?php

class ONPAGE_CLI {

	public function import($args, $assoc_args) {
        op_record("Beginning import...");
        $t1 = microtime(true);
        op_import_snapshot((bool) @$assoc_args['force_slug_regen'], (string) @$assoc_args['file_name']);
        $t2 = microtime(true);
        print_r([
          'log' => op_record('finish'),
          'c_count' => OpLib\Term::localized()->count(),
          'p_count' => OpLib\Post::localized()->count(),
          'time' => $t2 - $t1,
        ]);
	}

}

add_action( 'cli_init', function() {
	WP_CLI::add_command( 'onpage', 'ONPAGE_CLI' );
});
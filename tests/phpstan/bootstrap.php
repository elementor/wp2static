<?php

// Core constants
define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'WP_DEBUG', true );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS',   60 * MINUTE_IN_SECONDS );
define( 'DAY_IN_SECONDS',    24 * HOUR_IN_SECONDS );
define( 'WEEK_IN_SECONDS',    7 * DAY_IN_SECONDS );
define( 'MONTH_IN_SECONDS',  30 * DAY_IN_SECONDS );
define( 'YEAR_IN_SECONDS',  365 * DAY_IN_SECONDS );
define( 'OBJECT', 'OBJECT' );
define( 'OBJECT_K', 'OBJECT_K' );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'ARRAY_N', 'ARRAY_N' );

// We use core constants
define( 'WPINC', '' );
define( 'WP_CONTENT_DIR', '' );
define( 'WP_PLUGIN_DIR', '' );

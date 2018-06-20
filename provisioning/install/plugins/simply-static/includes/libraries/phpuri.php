<?php
	/**
	 * A php library for converting relative urls to absolute.
	 * Website: https://github.com/monkeysuffrage/phpuri
	 *
	 * <pre>
	 * echo phpUri::parse('https://www.google.com/')->join('foo');
	 * //==> https://www.google.com/foo
	 * </pre>
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @author  P Guardiario <pguardiario@gmail.com>
	 * @version 1.0
	 */

	/**
	 * phpUri
	 */
	class phpUri
	{

		/**
		 * http(s)://
		 * @var string
		 */
		public $scheme;

		/**
		 * www.example.com
		 * @var string
		 */
		public $authority;

		/**
		 * /search
		 * @var string
		 */
		public $path;

		/**
		 * ?q=foo
		 * @var string
		 */
		public $query;

		/**
		 * #bar
		 * @var string
		 */
		public $fragment;

		private function __construct( $string )
		{
			preg_match_all( '/^(([^:\/?#]+):)?(\/\/([^\/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?$/', $string, $m );
			$this->scheme    = $m[ 2 ][ 0 ];
			$this->authority = $m[ 4 ][ 0 ];

			/**
			 * CHANGE:
			 * @author Dominik Habichtsberg <Dominik.Habichtsberg@Hbg-IT.de>
			 * @since  24 Mai 2015 10:02 Uhr
			 *
			 * Former code:  $this->path = ( empty( $m[ 5 ][ 0 ] ) ) ? '/' : $m[ 5 ][ 0 ];
			 * No tests failed, when the path is empty.
			 * With the former code, the relative urls //g and #s failed
			 */
			$this->path     = $m[ 5 ][ 0 ];
			$this->query    = $m[ 7 ][ 0 ];
			$this->fragment = $m[ 9 ][ 0 ];
		}

		private function to_str()
		{
			$ret = '';
			if ( !empty( $this->scheme ) )
			{
				$ret .= "{$this->scheme}:";
			}

			if ( !empty( $this->authority ) )
			{
				$ret .= "//{$this->authority}";
			}

			$ret .= $this->normalize_path( $this->path );

			if ( !empty( $this->query ) )
			{
				$ret .= "?{$this->query}";
			}

			if ( !empty( $this->fragment ) )
			{
				$ret .= "#{$this->fragment}";
			}

			return $ret;
		}

		private function normalize_path( $path )
		{
			if ( empty( $path ) )
			{
				return '';
			}

			$normalized_path = $path;
			$normalized_path = preg_replace( '`//+`', '/', $normalized_path, -1, $c0 );
			$normalized_path = preg_replace( '`^/\\.\\.?/`', '/', $normalized_path, -1, $c1 );
			$normalized_path = preg_replace( '`/\\.(/|$)`', '/', $normalized_path, -1, $c2 );

			/**
			 * CHANGE:
			 * @author Dominik Habichtsberg <Dominik.Habichtsberg@Hbg-IT.de>
			 * @since  24 Mai 2015 10:05 Uhr
			 * changed limit form -1 to 1, because climbing up the directory-tree failed
			 */
			$normalized_path = preg_replace( '`/[^/]*?/\\.\\.(/|$)`', '/', $normalized_path, 1, $c3 );
			$num_matches     = $c0 + $c1 + $c2 + $c3;

			return ( $num_matches > 0 ) ? $this->normalize_path( $normalized_path ) : $normalized_path;
		}

		/**
		 * Parse an url string
		 *
		 * @param string $url the url to parse
		 *
		 * @return phpUri
		 */
		public static function parse( $url )
		{
			$uri = new phpUri( $url );

			/**
			 * CHANGE:
			 * @author Dominik Habichtsberg <Dominik.Habichtsberg@Hbg-IT.de>
			 * @since  24 Mai 2015 10:25 Uhr
			 * The base-url should always have a path
			 */
			if ( empty( $uri->path ) )
			{
				$uri->path = '/';
			}

			return $uri;
		}

		/**
		 * Join with a relative url
		 *
		 * @param string $relative the relative url to join
		 *
		 * @return string
		 */
		public function join( $relative )
		{
			$uri = new phpUri( $relative );
			switch ( TRUE )
			{
				case !empty( $uri->scheme ):
					break;

				case !empty( $uri->authority ):
					break;

				case empty( $uri->path ):
					$uri->path = $this->path;
					if ( empty( $uri->query ) )
					{
						$uri->query = $this->query;
					}
					break;

				case strpos( $uri->path, '/' ) === 0:
					break;

				default:
					$base_path = $this->path;
					if ( strpos( $base_path, '/' ) === FALSE )
					{
						$base_path = '';
					}
					else
					{
						$base_path = preg_replace( '/\/[^\/]+$/', '/', $base_path );
					}
					if ( empty( $base_path ) && empty( $this->authority ) )
					{
						$base_path = '/';
					}
					$uri->path = $base_path . $uri->path;
			}

			if ( empty( $uri->scheme ) )
			{
				$uri->scheme = $this->scheme;
				if ( empty( $uri->authority ) )
				{
					$uri->authority = $this->authority;
				}
			}

			return $uri->to_str();
		}
	}
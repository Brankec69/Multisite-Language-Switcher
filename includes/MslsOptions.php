<?php

/**
 * Options
 *
 * @package Msls
 */

/**
 * MslsOptions extends MslsGetSet
 */
require_once dirname( __FILE__ ) . '/MslsMain.php';

/**
 * MslsOptions implements IMslsRegistryInstance
 */
require_once dirname( __FILE__ ) . '/MslsRegistry.php';

/**
 * MslsOptions
 * 
 * @package Msls
 */
class MslsOptions extends MslsGetSet implements IMslsRegistryInstance {

    /**
     * @var array
     */
    protected $args;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var bool
     */
    protected $exists = false;

    /**
     * @var string
     */
    protected $sep = '';

    /**
     * @var string
     */
    protected $autoload = 'yes';

    /**
     * @var string
     */
    protected $base;

    /**
     * Factory method
     * 
     * @param int $id
     * @return MslsOptions
     */
    static function create( $id = 0 ) {
        if ( is_admin() ) {
            $id  = (int) $id;
            $obj = MslsContentTypes::create();
            if ( $obj->is_taxonomy() ) {
                if ( 'category' == $obj->get_request() ) {
                    return new MslsCategoryOptions( $id );
                }
                return new MslsTermOptions( $id );
            }
            return new MslsPostOptions( $id );
        }
        else {
            global $wp_query;
            if ( is_home() || is_front_page() ) {
                return new MslsOptions();
            } 
            elseif ( is_category() ) {
                return new MslsCategoryOptions( $wp_query->get_queried_object_id() );
            } 
            elseif ( is_tag() || is_tax() ) {
                return new MslsTermOptions( $wp_query->get_queried_object_id() );
            }
            return new MslsPostOptions( $wp_query->get_queried_object_id() );
        }
        return null;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->args   = func_get_args();
        $this->name   = 'msls' . $this->sep . implode( $this->sep, $this->args );
        $this->exists = $this->set( get_option( $this->name ) );
        $this->base   = $this->get_base();
    }

    /**
     * Save
     * 
     * @param mixed $arr
     */
    public function save( $arr ) {
        if ( $this->set( $arr ) ) {
            delete_option( $this->name );
            add_option( $this->name, $this->get_arr(), '', $this->autoload );
        }
    }

    /**
     * Set
     * 
     * @param mixed $arr
     * @return bool
     */
    public function set( $arr ) {
        if ( is_array( $arr ) ) {
            foreach ( $arr as $key => $value ) {
                $this->__set( $key, $value );
            }
            return true;
        }
        return false;
    }

    /**
     * Get base
     * 
     * @return null
     */
    protected function get_base() {
        return null;
    }

    /**
     * Get permalink
     * 
     * @param string $language
     * @return string
     */
    public function get_permalink( $language ) {
        $postlink = $this->get_postlink( $language );
        return(
            $postlink ?
            $postlink :
            site_url()
        );
    }

    /**
     * Get postlink
     * 
     * @param string $language
     * @return null
     */
    public function get_postlink( $language ) {
        return null;
    }

    /**
     * Get current link
     * 
     * @return string
     */
    public function get_current_link() {
        return site_url();
    }

    /**
     * Is excluded
     * 
     * @return bool
     */
    public function is_excluded() {
        return $this->has_value( 'exclude_current_blog' );
    }

    /**
     * Is content
     * 
     * @return bool
     */
    public function is_content_filter() {
        return $this->has_value( 'content_filter' );
    }

    /**
     * Get order
     * 
     * @return string
     */
    public function get_order() {
        return ( 
            $this->has_value( 'sort_by_description' ) ?
            'description' :
            'language'
        );
    }

    /**
     * Get url
     * 
     * @param string $dir
     * @return string
     */
    public function get_url( $dir ) {
        $url = sprintf(
            '%s/%s/%s',
            WP_PLUGIN_URL, 
            dirname( MSLS_PLUGIN_PATH ),
            $dir
        );
        return esc_url( $url );
    }

    /**
     * Get flag url
     * 
     * @param string $language
     * @param bool $plugin
     * @return string
     */
    public function get_flag_url( $language, $plugin = false ) {
        if ( !$plugin && $this->has_value( 'image_url' ) ) {
            $url = $this->__get( 'image_url' );
        }
        else {
            $url = $this->get_url( 'flags' );
        }
        if ( 5 == strlen( $language ) )
            $language = strtolower( substr( $language, -2 ) );
        return sprintf(
            '%s/%s.png',
            $url,
            $language
        );
    }

    /**
     * Instance
     * 
     * @return MslsOptions
     */
    public static function instance() {
        $registry = MslsRegistry::singleton();
        $cls      = __CLASS__;
        $obj      = $registry->get_object( $cls );
        if ( is_null( $obj ) ) {
            $obj = new $cls;
            $registry->set_object( $cls, $obj );
        }
        return $obj;
    }

}

/**
 * MslsPostOptions
 * 
 * @package Msls
 */
class MslsPostOptions extends MslsOptions {

    /**
     * @var string
     */
    protected $sep = '_';

    /**
     * @var string
     */
    protected $autoload = 'no';

    /**
     * Get postlink
     * 
     * @param string $language
     * @return string
     */
    public function get_postlink( $language ) {
        return(
            $this->has_value( $language ) ? 
            get_permalink( (int) $this->__get( $language ) ) :
            null
        );
    }

    /**
     * Get current link
     * 
     * @return string
     */
    public function get_current_link() {
        return get_permalink( (int) $this->args[0] );
    }

}

/**
 * MslsTermOptions
 * 
 * @package Msls
 */
class MslsTermOptions extends MslsOptions {

    /**
     * @var string
     */
    protected $sep = '_term_';

    /**
     * @var string
     */
    protected $autoload = 'no';

    /**
     * @var string
     */
    protected $base_option = 'tag_base';

    /**
     * @var string
     */
    protected $base_defined = 'tag';

    /**
     * @var string
     */
    protected $taxonomy = 'post_tag';

    /**
     * Get base
     * 
     * @return string
     */
    protected function get_base() {
        $base = get_option( $this->base_option );
        return(
            !empty ($base) ?
            $base :
            $this->base_defined
        );
    }

    /**
     * Get postlink
     * 
     * @param string $language
     * @return string|null
     */
    public function get_postlink( $language ) {
        if ( $this->has_value( $language ) ) {
            $url = get_term_link(
                (int) $this->__get( $language ), 
                $this->taxonomy
            );
            if ( empty( $url ) || !is_string( $url ) )
                return null;
            $base = $this->get_base();
            if ( $this->base != $base ) {
                $search  = '/' . $this->base . '/';
                $replace = '/' . $base . '/';
                $count   = 1;
                $url     = str_replace( $search, $replace, $url, $count );
            }
            return $url;
        }
        return null;
    }

    /**
     * Get current link
     * 
     * @return string
     */
    public function get_current_link() {
        return get_tag_link( (int) $this->args[0] );
    }

}

/**
 * MslsCategoryOptions
 * 
 * @package Msls
 */
class MslsCategoryOptions extends MslsTermOptions {

    /**
     * @var string
     */
    protected $base_option = 'category_base';

    /**
     * @var string
     */
    protected $base_defined = 'category';

    /**
     * @var string
     */
    protected $taxonomy = 'category';

    /**
     * Get current link
     * 
     * @return string
     */
    public function get_current_link() {
        return get_category_link( (int) $this->args[0] );
    }

}

?>

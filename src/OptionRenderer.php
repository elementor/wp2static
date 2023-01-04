<?php

namespace WP2Static;

class OptionRenderer {

    const INPUT_TYPE_FNS = [
        'array' => 'optionInputArray',
        'boolean' => 'optionInputBoolean',
        'integer' => 'optionInputInteger',
        'password' => 'optionInputPassword',
        'string' => 'optionInputString',
    ];

    /**
     * @param array<string, mixed> $option
     * @return string
     */
    public static function optionInput( array $option ) : string {
        $option_input = call_user_func(
            [ 'WP2Static\OptionRenderer', self::INPUT_TYPE_FNS[ $option['type'] ] ],
            $option
        );

        return strval( $option_input );
    }

    /**
     * @param array<string, mixed> $option
     * @return string
     */
    public static function optionInputArray( array $option ) : string {
        return '<textarea class="widefat" cols=30 rows=10 id="' . $option['name'] . '" name="' .
               $option['name'] . '">' . $option['blob_value'] . '</textarea>';
    }

    /**
     * @param array<string, mixed> $option
     * @return string
     */
    public static function optionInputBoolean( array $option ) : string {
        /**
         * @var int $unfiltered_value
         */
        $unfiltered_value = $option['unfiltered_value'];
        $checked = (int) $unfiltered_value === 1 ? ' checked' : '';
        return '<input id="' . $option['name'] . '" name="' . $option['name'] . '" value="1"' .
               ' type="checkbox"' . $checked . '>';
    }

    /**
     * @param array<string, mixed> $option
     * @return string
     */
    public static function optionInputInteger( array $option ) : string {
        return '<input class="widefat" id="' . $option['name'] . '" name="' . $option['name'] .
               '" type="number" value="' . esc_html( strval( $option['value'] ) ) . '">';
    }

    /**
     * @param array<string, mixed> $option
     * @return string
     */
    public static function optionInputPassword( array $option ) : string {
        return '<input class="widefat" id="' . $option['name'] . '" name="' . $option['name'] .
               '" type="password" value="' . esc_html( strval( $option['value'] ) ) . '">';
    }

    /**
     * @param array<string, mixed> $option
     * @return string
     */
    public static function optionInputString( array $option ) : string {
        return '<input class="widefat" id="' . $option['name'] . '" name="' . $option['name'] .
               '" type="text" value="' . esc_html( strval( $option['value'] ) ) . '">';
    }

    /**
     * @param array<string, mixed> $option
     * @return string
     */
    public static function optionLabel( array $option, bool $description = false ) : string {
        $descr = $description && $option['description'] ? '<br>' . $option['description'] : '';
        return '<label for="' . $option['name'] . '" style="font-weight: bold">' .
               $option['label'] . '</label>' . $descr;
    }

}

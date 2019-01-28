<?php

// phpcs:disable
class TemplateHelper {
    public function __construct() {

    }

    public function displayCheckbox( $tpl_vars, $field_name, $field_label ) {
        echo "
      <fieldset>
        <label for='{$field_name}'>
          <input name='{$field_name}' id='{$field_name}' value='1' type='checkbox' " . ( $tpl_vars->options->{$field_name} === '1' ? 'checked' : '' ) . ' />
          <span>' . __( $field_label, 'static-html-output-plugin' ) . '</span>
        </label>
      </fieldset>
    ';
    }

    public function displayTextfield( $tpl_vars, $field_name, $field_label, $description, $type = 'text' ) {
        echo "
      <input name='{$field_name}' class='regular-text' id='{$field_name}' type='{$type}' value='" . esc_attr( $tpl_vars->options->{$field_name} ) . "' placeholder='" . __( $field_label, 'static-html-output-plugin' ) . "' />
      <span class='description'>$description</span>
      <br>
    ";
    }

    public function displaySelectMenu( $tpl_vars, $menu_options, $field_name, $field_label, $description, $type = 'text' ) {

        $menu_code = "
      <select name='{$field_name}' id='{$field_name}'>
        <option></option>";

        foreach ( $menu_options as $value => $text ) {
            if ( $tpl_vars->options->{$field_name} === $value ) {
                $menu_code .= "
            <option value='{$value}' selected>{$text}</option>";
            } else {
                $menu_code .= "
            <option value='{$value}'>{$text}</option>";
            }
        }

        $menu_code .= '</select>';

        echo $menu_code;
    }

    public function ifNotEmpty( $value, $substitute = '' ) {
        $value = $value ? $value : $substitute;

        echo $value;
    }
}
// phpcs:enable


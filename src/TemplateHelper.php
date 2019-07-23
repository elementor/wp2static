<?php

namespace WP2Static;

// phpcs:disable
class TemplateHelper {
    public function __construct() {

    }

    public function displayCheckbox(
        Controller $tpl_vars,
        string $field_name,
        string $field_label
    ) : void {
        echo "
      <fieldset>
        <label for='{$field_name}'>
          <input name='{$field_name}' id='{$field_name}' value='1' type='checkbox' " . ( $tpl_vars->options->{$field_name} === '1' ? 'checked' : '' ) . ' />
          <span>' . __( $field_label, 'static-html-output-plugin' ) . '</span>
        </label>
      </fieldset>
    ';
    }

    public function displayTextfield(
        Controller $tpl_vars,
        string $field_name,
        string $field_label,
        string $description,
        string $type = 'text'
    ) : void {

        if ( $type ) {
        echo "
      <input name='{$field_name}' class='regular-text' id='{$field_name}' type='{$type}' value='" . esc_attr( $tpl_vars->options->{$field_name} ) . "' placeholder='" . __( $field_label, 'static-html-output-plugin' ) . "' />
      <span class='description'>$description</span>
    ";
        } else {
        echo "
      <input name='{$field_name}' class='regular-text' id='{$field_name}' value='" . esc_attr( $tpl_vars->options->{$field_name} ) . "' />
      <span class='description'>$description</span>
    ";
        }


    }
}
// phpcs:enable


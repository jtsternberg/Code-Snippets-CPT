<div class="wrap">
  <h2><?php esc_html_e( 'Code Snippets Settings', 'code-snippets-cpt' ); ?></h2>
  <?php settings_errors(); ?>
  <form method="post" action="options.php">
    <?php settings_fields( 'code-snippets-cpt' ); ?>
    <table class="form-table">
      <tbody>
        <tr valign="top">
          <th scope="row"><label for="enable-ace"><?php esc_html_e( 'Enable Ace Editor Theme on Frontend', 'code-snippets-cpt' ); ?></label></th>
          <td>
            <input name="code-snippets-cpt[ace]" type="checkbox" id="enable-ace" <?php checked( $options['ace'] ); ?> value="1">
          </td>
        </tr>
        <tr valign="top">
          <?php if ( $options['ace'] ) : ?>
          <th scope="row"><label for="snippets-theme"><?php esc_html_e( 'Ace Editor Theme (frontend)', 'code-snippets-cpt' ); ?></label></th>
          <td>
            <select name="code-snippets-cpt[theme]" type="text" id="snippets-theme">
              <?php echo $ace_theme_options; ?>
            </select>
          </td>
          <?php else: ?>
          <th scope="row"><label for="snippets-theme"><?php esc_html_e( 'Frontend Theme', 'code-snippets-cpt' ); ?></label></th>
          <td>
            <select name="code-snippets-cpt[theme]" type="text" id="snippets-theme" style="min-width: 200px;">
              <option value="ace/theme/xcode" <?php selected( $options['theme'], 'ace/theme/xcode' ); ?>><?php _e( 'Default', 'code-snippets-cpt' ); ?></option>
              <option value="ace/theme/monokai" <?php selected( $options['theme'], 'ace/theme/monokai' ); ?>><?php _e( 'Monokai', 'code-snippets-cpt' ); ?></option>
            </select>
          </td>
          <?php endif; ?>
        </tr>
      </tbody>
    </table>
    <?php submit_button( __( 'Save Changes', 'code-snippets-cpt' ) ); ?>
  </form>
</div>

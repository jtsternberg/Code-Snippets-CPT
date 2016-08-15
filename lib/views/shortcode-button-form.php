<style type="text/css">
	.snippet-cpt-dialog {
		max-width: 800px;
	}
	#snippet-cpt-form {
		padding: 0 10px 20px;
	}
	#snippet-cpt-form table {
		width: 100%;
	}
	#snippet-cpt-form select {
		width: 100%;
		max-width: 100%;
	}
	#snippet-cpt-form .th {
		width: 50%;
	}
	#snippet-cpt-form .th label {
		padding-right: 9px;
		text-align: right;
	}

	.snippet-cpt-errors p {
		display: none;
		background: rgba( 255, 0, 0, 0.5 );
		padding: 5px;
		border: 1px solid #F00;
	}

	.add-new-snippet {
		display: none;
	}

	.add-new-snippet > div {
		margin-top: 0.5em;
	}

	.add-new-snippet label {
		font-weight: bold;
	}

	.snippet-overlay {
		display: none;
		position: absolute;
		top: 0;
		left: 0;
		height: 100%;
		width: 100%;
		background-color: rgba( 0,0,0,0.25 );
	}

	.snippet-overlay img {
		position: absolute;
		top: 0;
		left: 0;
		bottom: 0;
		right: 0;
		margin: auto;
	}

	.snippet-cpt-label {
		display: block;
		margin-bottom: .5em;
	}

	.new-snippet-content-wrap, .snippet-main-content {
		height: 400px;
	}
</style>
<div style="display: none;" id="snippet-cpt-form" title="<?php esc_attr_e( 'Code Snippets', 'code-snippets-cpt' ); ?>">
	<div class="snippet-cpt-errors"><p></p></div>
	<form id="cpt-snippet-form">
	<fieldset class="select-a-snippet">
		<table>
			<?php if ( ! empty( $snippets ) ) : ?>
			<tr>
				<th><label for="snippet-cpt-posts"><?php _e( 'Choose a Snippet', 'code-snippets-cpt' ); ?></label></th>
			</tr>
			<tr>
				<td>
					<select name="snippet-cpt-posts" id="snippet-cpt-posts" value="left" class="text ui-widget-content ui-corner-all">
						<option value=""><?php _e( 'Select a Snippet', 'code-snippets-cpt' ); ?></option>
						<?php foreach ( $snippets as $snippet ) :
							$lang_slug	= '';
							if ( $has_slug = $this->language->language_slug_from_post( $snippet->ID ) ) {
								$lang_slug = $has_slug;
							}
						?>
							<option value="<?php echo $snippet->post_name;?>" data-lang="<?php echo $lang_slug; ?>" data-id="<?php echo $snippet->ID; ?>"><?php echo $snippet->post_title; ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<td>
					<p>
						<input type="checkbox" name="snippet-cpt-line-nums" id="snippet-cpt-line-nums" value="1" checked="checked" class="text ui-widget-content ui-corner-all" />
						<label for="snippet-cpt-line-nums"><?php _e( 'Display Line Numbers?', 'code-snippets-cpt' ); ?></label>
					</p>
				</td>
			</tr>

			<?php else : ?>
			<tr>
				<th><label id="no-snippets-exist"><?php _e( 'No Snippets Yet!', 'code-snippets-cpt' ); ?></label></th>
			</tr>
			<?php endif; ?>
		</table>
		<hr />
	</fieldset>
	<fieldset>
		<div style="text-align:right; padding: 10px 0;">
			<input type='button' class="add-new-snippet-btn button button-secondary" value="<?php _e( 'Add New', 'code-snippets-cpt' ); ?> " />
			<input type='button' class="cancel-new-snippet-btn button button-secondary hidden" value="<?php _e( 'Cancel', 'code-snippets-cpt' ); ?>">
		</div>
	</fieldset>
	<fieldset class="add-new-snippet">
		<input type="hidden" name="edit-snippet-id" id="edit-snippet-id" value=""/>
		<div>
			<label class="snippet-cpt-label" for="new-snippet-title"><?php _e( 'Snippet Title', 'code-snippets-cpt' ); ?></label>
			<input type="text" name="new-snippet-title" id="new-snippet-title" class="new-snippet-title widefat">
		</div>

		<div>
			<p>
				<input type="checkbox" name="snippet-cpt-line-nums-2" id="snippet-cpt-line-nums-2" value="1" checked="checked" class="text ui-widget-content ui-corner-all" />
				<label for="snippet-cpt-line-nums-2"><span><?php _e( 'Display Line Numbers?', 'code-snippets-cpt' ); ?></span></label>
			</p>
		</div>

		<div>
			<label class="snippet-cpt-label" for="new-snippet-content"><?php _e( 'Snippet', 'code-snippets-cpt' ); ?></label>
			<pre id="snippet-content" class="hidden"></pre>
			<pre class="new-snippet-content-wrap"><textarea name="new-snippet-content" id="new-snippet-content" class="widefat snippet-main-content"></textarea></pre>
		</div>
		<hr />
		<div>
			<label class="snippet-cpt-label" for="snippet-categories"><?php _e( 'Snippet Categories', 'code-snippets-cpt' ); ?></label>
			<?php post_categories_meta_box( $post, $cat_box_config ); ?>
		</div>
		<hr />
		<div>
			<label class="snippet-cpt-label" for="snippet-categories"><?php _e( 'Snippet Tags', 'code-snippets-cpt' ); ?></label>
			<?php post_tags_meta_box( $post, $tag_box_config ); ?>
		</div>
		<hr />
		<div>
			<label class="snippet-cpt-label" for="snippet-language"><?php _e( 'Programming Language', 'code-snippets-cpt' ); ?></label>
			<select name="snippet-language" id="snippet-language">
				<option value=""><?php _e( 'Select One', 'code-snippets-cpt' ); ?></option>
				<?php if ( ! empty( $languages ) ) : foreach ( $languages as $language ) : ?>
				<option value="<?php echo $language->term_id; ?>" data-slug="<?php echo $language->slug; ?>"><?php echo $language->name; ?></option>
				<?php endforeach; endif; ?>
			</select>
		</div>

	</fieldset>
	</form>
	<div class="snippet-overlay">
		<img src="<?php echo DWSNIPPET_URL . 'assets/images/ajax-loader.gif'; ?>" height="32" width="32" >
	</div>
</div>

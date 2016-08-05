</p>
<style type="text/css" media="screen">
	html, body {
		background: #fff;
		height: 100%;
	}
	h5 {
		font-size: 14px;
		height: 20px;
		margin: 0 0 10px 0;
		padding: 0;
	}
	#snippet-wrap, #snippet {
		display: block;
		width: 100%;
		height: calc( 100% - 30px );
		min-height: 200px;
		-moz-box-sizing: border-box;
		-webkit-box-sizing: border-box;
		box-sizing: border-box;
		font-family: monospace;
	}
	#snippet {
		height: 100%;
		max-width: 100%;
		padding: 5px;
	}
	#error-page {
		max-width: 96%;
		padding: 2%;
		margin: 0;
		height: 96%;
	}
	#error-page p {
		margin: 0;
		display: block;
		width: 100%;
		height: 100%;
	}
	#error-page p {
		display: none;
	}
	<?php if ( $this->do_monokai_theme() ) : ?>
	html, body, textarea {
		background: #2b2d26;
		color: #ccc;
	}
	::selection {
		background: #5ae4e4; /* WebKit/Blink Browsers */
		color: white; /* WebKit/Blink Browsers */
	}
	::-moz-selection {
		background: #5ae4e4; /* Gecko Browsers */
		color: white; /* Gecko Browsers */
	}
	#snippet:focus {
		outline: none !important;
		border:2px solid #66cccc;
		box-shadow: 0 0 10px #66cccc;
		border:2px solid #66cccc;
		box-shadow: 0 0 10px #66cccc;
	}
	<?php endif; ?>
</style>
<h5><?php _e( 'Copy Snippet (cmd/ctrl+c)', 'snippet-cpt' ); ?></h5>
<pre id="snippet-wrap"><textarea id="snippet"><?php print_r( $snippet_post->post_content ); ?></textarea></pre>
<script type="text/javascript">
	window.onload = document.getElementById( 'snippet' ).select();
</script>
<p>
